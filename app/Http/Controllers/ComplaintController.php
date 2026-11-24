<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Housing\AppNotification;
use App\Models\Housing\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Complaints = every support ticket raised across the portals (shared housing DB).
 * Admin can reply to the builder, add internal notes, and change status / priority — same rules as BRM.
 */
class ComplaintController extends Controller
{
    public const TABS = ['all' => 'All Complaints', 'open' => 'Open', 'in_progress' => 'In Progress', 'escalated' => 'Escalated', 'resolved' => 'Resolved'];

    public function index(Request $request): View
    {
        $tab = array_key_exists((string) $request->query('status'), self::TABS) ? (string) $request->query('status') : 'all';
        $q = trim((string) $request->query('q', ''));
        $priority = array_key_exists((string) $request->query('priority'), SupportTicket::PRIORITIES) ? (string) $request->query('priority') : '';
        $available = SupportTicket::available();

        $tickets = $available
            ? SupportTicket::query()
                ->when($tab !== 'all', fn ($w) => $w->where('status', $tab))
                ->when($priority !== '', fn ($w) => $w->where('priority', $priority))
                ->when($q !== '', fn ($w) => $w->where(fn ($s) => $s->where('code', 'like', "%{$q}%")
                    ->orWhere('subject', 'like', "%{$q}%")->orWhere('builder_name', 'like', "%{$q}%")))
                ->with(['messages' => fn ($m) => $m->whereIn('type', ['description', 'reply'])])
                ->orderByRaw("CASE status WHEN 'escalated' THEN 0 WHEN 'open' THEN 1 WHEN 'in_progress' THEN 2 ELSE 3 END")
                ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
                ->orderByDesc('id')
                ->paginate(10)->withQueryString()
            : null;

        $counts = $available
            ? SupportTicket::query()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status')->all()
            : [];

        return view('pages.complaints', [
            'available' => $available,
            'tickets' => $tickets,
            'counts' => $counts + ['all' => array_sum($counts)],
            'openCount' => array_sum(array_intersect_key($counts, array_flip(SupportTicket::OPEN))),
            'overdueCount' => $available ? SupportTicket::query()->open()->where('due_at', '<', now())->count() : 0,
            'filters' => ['tab' => $tab, 'q' => $q, 'priority' => $priority],
        ]);
    }

    public function show(SupportTicket $ticket): View
    {
        $ticket->load('messages');

        return view('pages.complaint-show', ['ticket' => $ticket, 'projectName' => $ticket->projectName()]);
    }

    /** Reply to the builder (they see it in their portal and get a notification) or add an internal note. */
    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['reply', 'note'])],
            'body' => ['required', 'string', 'min:2', 'max:5000'],
            'then' => ['nullable', Rule::in(['', 'escalated', 'resolved'])],
        ]);
        $me = $this->author($request);

        DB::connection('housing')->transaction(function () use ($ticket, $data, $me): void {
            $ticket->messages()->create(['type' => $data['type'], 'author_type' => 'brm', 'author' => $me, 'body' => $data['body']]);
            $changes = [];
            if ($data['type'] === 'reply' && ! $ticket->first_response_at) {
                $changes['first_response_at'] = now();
            }
            if ($data['type'] === 'reply' && $ticket->status === 'open') {
                $changes['status'] = 'in_progress';
            }
            if (! empty($data['then'])) {
                $changes = array_merge($changes, $this->statusChanges($ticket, $data['then']));
                $ticket->messages()->create(['type' => 'event', 'author_type' => 'brm', 'author' => $me, 'body' => 'Status → '.SupportTicket::STATUSES[$data['then']]]);
            }
            if ($changes) {
                $ticket->update($changes);
            }
        });

        if ($data['type'] === 'reply') {
            AppNotification::send('builder', 'ticket_reply', "CMNHousing replied · {$ticket->code}", Str::limit($data['body'], 140),
                "/support/{$ticket->id}", 'info', null, $ticket->builder_name);
        }
        if (! empty($data['then'])) {
            $this->notifyStatus($ticket, $data['then']);
        }
        AuditLog::record('complaint.'.$data['type'], ($data['type'] === 'note' ? 'Internal note on ' : 'Replied to ')."complaint {$ticket->code}.", null,
            ['ticket' => $ticket->code, 'then' => $data['then'] ?? null]);

        return redirect()->to(route('complaints.show', $ticket).'#thread')
            ->with('success', $data['type'] === 'note' ? 'Internal note added — the builder can’t see it.' : 'Reply sent — the builder sees it in their portal.');
    }

    /** "Take action": change status and/or priority, with an optional internal note explaining why. */
    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(SupportTicket::STATUSES))],
            'priority' => ['required', Rule::in(array_keys(SupportTicket::PRIORITIES))],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['note'] = trim((string) ($data['note'] ?? ''));
        $me = $this->author($request);
        $events = [];
        $changes = [];

        if ($data['status'] !== $ticket->status) {
            $changes += $this->statusChanges($ticket, $data['status']);
            $events[] = 'Status → '.SupportTicket::STATUSES[$data['status']];
        }
        if ($data['priority'] !== $ticket->priority) {
            $changes['priority'] = $data['priority'];
            $changes['due_at'] = SupportTicket::slaDue($data['priority'], $ticket->created_at);
            $events[] = 'Priority → '.SupportTicket::PRIORITIES[$data['priority']][0];
        }

        if (! $changes && blank($data['note'])) {
            return back()->with('success', 'Nothing changed.');
        }

        DB::connection('housing')->transaction(function () use ($ticket, $changes, $events, $data, $me): void {
            if ($changes) {
                $ticket->update($changes);
                $ticket->messages()->create(['type' => 'event', 'author_type' => 'brm', 'author' => $me, 'body' => implode(' · ', $events)]);
            }
            if (filled($data['note'])) {
                $ticket->messages()->create(['type' => 'note', 'author_type' => 'brm', 'author' => $me, 'body' => $data['note']]);
            }
        });

        if (isset($changes['status'])) {
            $this->notifyStatus($ticket, $changes['status']);
        }
        AuditLog::record('complaint.updated', "Complaint {$ticket->code}: ".($events ? implode(' · ', $events) : 'note added').'.', null, ['ticket' => $ticket->code]);

        return back()->with('success', $events ? "{$ticket->code}: ".implode(' · ', $events).'.' : 'Internal note added.');
    }

    /** Shown on the ticket thread, e.g. "Priya Shah · Admin". */
    private function author(Request $request): string
    {
        return trim(($request->user()?->name ?: 'CMNHousing').' · Admin');
    }

    /** @return array<string, mixed> */
    private function statusChanges(SupportTicket $ticket, string $status): array
    {
        $changes = ['status' => $status];
        if ($status === 'resolved') {
            $changes['resolved_at'] = $ticket->resolved_at ?? now();
        } else {
            $changes['resolved_at'] = null;
            if ($ticket->status === 'resolved') {   // reopened: fresh SLA from now
                $changes['due_at'] = SupportTicket::slaDue($ticket->priority);
            }
        }

        return $changes;
    }

    /** The builder hears about resolved / escalated / reopened; BRM hears when Admin escalates. */
    private function notifyStatus(SupportTicket $ticket, string $status): void
    {
        $link = "/support/{$ticket->id}";
        match ($status) {
            'resolved' => AppNotification::send('builder', 'ticket_resolved', "Request resolved · {$ticket->code}", $ticket->subject.' — reply if it comes back.', $link, 'success', null, $ticket->builder_name),
            'escalated' => AppNotification::send('builder', 'ticket_escalated', "Request escalated · {$ticket->code}", $ticket->subject.' — a senior manager is now on it.', $link, 'warning', null, $ticket->builder_name),
            'open', 'in_progress' => AppNotification::send('builder', 'ticket_reopened', "Request reopened · {$ticket->code}", $ticket->subject.' — CMNHousing is looking at it again.', $link, 'info', null, $ticket->builder_name),
            default => null,
        };
        if ($status === 'escalated') {
            AppNotification::send('brm', 'ticket_escalated', "Admin escalated {$ticket->code}", $ticket->builder_name.' · '.$ticket->subject, "/builder-support/{$ticket->id}", 'warning');
        }
    }
}
