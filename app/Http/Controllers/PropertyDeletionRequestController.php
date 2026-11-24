<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PostProperty;
use App\Models\PropertyDeletionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropertyDeletionRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = strtolower(trim((string) $request->query('status', 'pending'))) ?: 'pending';

        $requestsQuery = PropertyDeletionRequest::query()
            ->with(['property.user', 'requester', 'reviewer'])
            ->latest('created_at')
            ->latest('id');

        if (in_array($status, [
            PropertyDeletionRequest::STATUS_PENDING,
            PropertyDeletionRequest::STATUS_APPROVED,
            PropertyDeletionRequest::STATUS_REJECTED,
        ], true)) {
            $requestsQuery->where('status', $status);
        }

        $counts = [
            'pending' => PropertyDeletionRequest::query()->where('status', PropertyDeletionRequest::STATUS_PENDING)->count(),
            'approved' => PropertyDeletionRequest::query()->where('status', PropertyDeletionRequest::STATUS_APPROVED)->count(),
            'rejected' => PropertyDeletionRequest::query()->where('status', PropertyDeletionRequest::STATUS_REJECTED)->count(),
            'all' => PropertyDeletionRequest::query()->count(),
        ];

        return view('pages.property-deletion-requests', [
            'requests' => $requestsQuery->paginate(15)->withQueryString(),
            'counts' => $counts,
            'filters' => ['status' => $status],
        ]);
    }

    public function store(Request $request, PostProperty $postProperty): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $existingPending = PropertyDeletionRequest::query()
            ->where('post_property_id', $postProperty->id)
            ->where('status', PropertyDeletionRequest::STATUS_PENDING)
            ->exists();

        if ($existingPending) {
            return back()->withErrors([
                'reason' => 'A deletion request is already pending for this property.',
            ]);
        }

        $deletionRequest = PropertyDeletionRequest::query()->create([
            'post_property_id' => $postProperty->id,
            'property_code' => $postProperty->property_id,
            'property_title' => $postProperty->property_title,
            'requested_by' => $request->user()->id,
            'status' => PropertyDeletionRequest::STATUS_PENDING,
            'reason' => $validated['reason'],
        ]);

        AuditLog::record(
            'property.deletion_requested',
            "Deletion requested for property {$postProperty->property_id}.",
            $deletionRequest,
            [
                'property_id' => $postProperty->property_id,
                'reason' => $validated['reason'],
            ],
        );

        return back()->with('success', "Deletion request submitted for {$postProperty->property_id}.");
    }

    public function approve(Request $request, PropertyDeletionRequest $propertyDeletionRequest): RedirectResponse
    {
        if (! $propertyDeletionRequest->isPending()) {
            return back()->withErrors(['request' => 'Only pending deletion requests can be approved.']);
        }

        $property = $propertyDeletionRequest->property;
        $propertyId = $propertyDeletionRequest->property_code ?: $property?->property_id;

        $propertyDeletionRequest->update([
            'status' => PropertyDeletionRequest::STATUS_APPROVED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $request->input('review_note'),
        ]);

        if ($property !== null) {
            $property->delete();
        }

        AuditLog::record(
            'property.deletion_approved',
            "Deletion approved for property {$propertyId}.",
            $propertyDeletionRequest,
            [
                'property_id' => $propertyId,
                'review_note' => $request->input('review_note'),
            ],
        );

        return back()->with('success', "Deletion request approved. Property {$propertyId} deleted.");
    }

    public function reject(Request $request, PropertyDeletionRequest $propertyDeletionRequest): RedirectResponse
    {
        if (! $propertyDeletionRequest->isPending()) {
            return back()->withErrors(['request' => 'Only pending deletion requests can be rejected.']);
        }

        $validated = $request->validate([
            'review_note' => ['required', 'string', 'min:5', 'max:2000'],
        ], [], ['review_note' => 'rejection comment']);

        $propertyDeletionRequest->update([
            'status' => PropertyDeletionRequest::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $validated['review_note'],
        ]);

        $propertyId = $propertyDeletionRequest->property_code
            ?: $propertyDeletionRequest->property?->property_id;

        AuditLog::record(
            'property.deletion_rejected',
            "Deletion request rejected for property {$propertyId}.",
            $propertyDeletionRequest,
            [
                'property_id' => $propertyId,
                'review_note' => $validated['review_note'],
            ],
        );

        return back()->with('success', "Deletion request for {$propertyId} was rejected.");
    }
}
