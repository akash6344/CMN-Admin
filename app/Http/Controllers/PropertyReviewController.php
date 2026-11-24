<?php

namespace App\Http\Controllers;

use App\Models\Agent\AgentUser;
use App\Models\AuditLog;
use App\Models\Dashboard\PropertyReview;
use App\Models\Housing\AppNotification;
use App\Support\ListingStatus;
use App\Support\WebsiteNotifier;
use App\Support\WebsiteSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Admin's decision on an owner / agent listing Operations (CMN-Emp) has reviewed — taken from the Properties section.
 * Approve (goes live) or raise a query, which goes back to Operations. Operations deals with the owner / agent.
 * Builder projects are reviewed by BRM only: a project review is refused here.
 */
class PropertyReviewController extends Controller
{
    public function approve(Request $request, PropertyReview $review): RedirectResponse
    {
        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:1000']]);
        if ($stop = $this->notAListing($review)) {
            return $stop;
        }
        if ($review->status !== 'ready_for_admin') {
            return back()->withErrors(['review' => 'Only submissions awaiting approval can be approved.']);
        }
        if ($stale = $this->changedSinceHandover($review)) {
            return back()->withErrors(['review' => $stale]);
        }
        $admin = $request->user();
        $sum = $review->summary();

        $l = $review->listing;
        $l->update(['post_status' => 'Approved', 'show_on_website' => 'Show', 'rejection_reason' => null]
            + (WebsiteSchema::has('post_properties', 'rejection_comment') ? ['rejection_comment' => null] : []));
        $this->notifyOwner($l, 'Your listing is live', 'Your property has been approved and is now visible on CMNHousing.');
        $this->notifyAgent($l);
        $review->update(['status' => 'approved', 'decided_at' => now(), 'decided_by' => $admin->name, 'admin_note' => $data['admin_note'] ?? null]);
        $review->log('approved', $admin->name, $data['admin_note'] ?? null);

        AuditLog::record('listing.approved', "{$sum['kind']} {$sum['code']} {$sum['title']} approved after Operations review.", null,
            ['property_review_id' => $review->id, 'kind' => $review->kind, 'ops_score' => $review->score, 'reviewer' => $review->reviewer_name]);
        AppNotification::send('ops', $review->kind, 'Admin approved '.$sum['code'], $sum['title'].' is live on the website.',
            '/ops/property-queue?review='.$review->id.'&status=all', 'success', null, null, 'ops:decided:'.$review->id.':approved:'.now()->timestamp);

        return redirect()->route('properties.show', 'listing-'.$review->post_property_id)
            ->with('success', "{$sum['code']} approved and live on the website.");
    }

    /** Admin isn't happy yet — the comment goes to Operations, who fix it or ask the owner / agent for edits. */
    public function query(Request $request, PropertyReview $review): RedirectResponse
    {
        $data = $request->validate(['admin_note' => ['required', 'string', 'min:5', 'max:1000']], ['admin_note.required' => 'Write what Operations should look at.'], ['admin_note' => 'comment']);
        if ($stop = $this->notAListing($review)) {
            return $stop;
        }
        if ($review->status !== 'ready_for_admin') {
            return back()->withErrors(['review' => 'Only submissions awaiting approval can be queried.']);
        }
        $admin = $request->user();
        $sum = $review->summary();
        $review->update(['status' => 'admin_query', 'admin_note' => $data['admin_note'], 'admin_query_at' => now()]);
        $review->log('admin_query', $admin->name, $data['admin_note']);

        AuditLog::record('review.queried', "Query raised on {$sum['code']} {$sum['title']}.", null, ['property_review_id' => $review->id, 'comment' => $data['admin_note']]);
        AppNotification::send('ops', $review->kind, 'Admin query on '.$sum['code'], $data['admin_note'],
            '/ops/property-queue?review='.$review->id.'&status=all', 'danger', null, null, 'ops:query:'.$review->id.':'.now()->timestamp);

        return redirect()->route('properties.show', 'listing-'.$review->post_property_id)
            ->with('success', "Query sent to Operations on {$sum['code']}.");
    }

    /** Builder projects are reviewed by BRM only — Admin never approves or queries them. */
    private function notAListing(PropertyReview $review): ?RedirectResponse
    {
        return $review->isProject()
            ? back()->withErrors(['review' => 'Builder projects are reviewed by the BRM team in the BRM portal — Admin can’t approve or query them.'])
            : null;
    }

    /**
     * Approving must act on what Operations actually reviewed. If the listing was removed / withdrawn after Operations
     * handed it over, Admin is told to send it back instead.
     */
    private function changedSinceHandover(PropertyReview $review): ?string
    {
        $l = $review->listing;
        if (! $l) {
            return 'This listing was withdrawn after Operations reviewed it — there is nothing to approve.';
        }
        // Status only: updated_at also moves on view-count bumps, so it can't tell an owner edit apart.
        if (in_array(strtolower(trim((string) $l->post_status)), ['withdrawn', 'inactive', 'rejected'], true)
            || in_array(ListingStatus::of($l), ListingStatus::OFF_PIPELINE, true)) {   // deleted, paused or back to draft
            return 'The owner withdrew or changed this listing after Operations reviewed it — send it back to Operations.';
        }

        return null;
    }

    /**
     * Listings posted by an agent (user_id 0 on the website): tell the agent in their portal inbox.
     * The agent is found by the listing's email / mobile in the CMN-Agent users table — the same match the Agent portal uses.
     */
    private function notifyAgent($l): void
    {
        try {
            $email = strtolower(trim((string) $l->user_email));
            $digits = substr((string) preg_replace('/\D/', '', (string) $l->user_mobile), -10);
            if ($email === '' && strlen($digits) !== 10) {
                return;
            }
            $agentId = AgentUser::query()->where(function ($q) use ($email, $digits): void {
                $q->when($email !== '', fn ($w) => $w->whereRaw('LOWER(TRIM(email)) = ?', [$email]));
                if (strlen($digits) === 10) {
                    $q->orWhereRaw("REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '-', ''), '+', '') LIKE ?", ['%'.$digits]);
                }
            })->value('id');
            if (! $agentId) {
                return;
            }
            AppNotification::send('agent', 'listing_approved', 'Your listing is live', mb_substr((string) $l->property_title, 0, 120).' was approved and is now visible on CMNHousing.',
                '/properties', 'success', (int) $agentId, null, 'agent:approved:listing:'.$l->id.':'.now()->timestamp);
        } catch (\Throwable $e) {
            report($e);   // the agent DB may be unreachable; approval still stands
        }
    }

    /** The owner's CMNHousing dashboard inbox (website notifications). */
    private function notifyOwner($l, string $title, string $body): void
    {
        try {
            WebsiteNotifier::listing($l, 'property.approved', $title, $body, 'success', 'fas fa-circle-check');
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
