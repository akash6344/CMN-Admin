<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Support\AdminInbox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin inbox (housing app_notifications, audience "admin"): full list, open → mark read → go to the link, mark all read. */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $available = AdminInbox::available();
        $tab = $request->query('tab') === 'unread' ? 'unread' : 'all';

        return view('pages.notifications', [
            'available' => $available,
            'tab' => $tab,
            'items' => $available
                ? AdminInbox::query()->when($tab === 'unread', fn ($q) => $q->whereNull('read_at'))->latest()->latest('id')->paginate(20)->withQueryString()
                : null,
            'unread' => $available ? AdminInbox::query()->whereNull('read_at')->count() : 0,
        ]);
    }

    public function open(int $id): RedirectResponse
    {
        abort_unless(AdminInbox::available(), 404);
        $n = AdminInbox::query()->findOrFail($id);   // admin rows only
        $n->markRead();

        return $n->link ? redirect()->to(url($n->link)) : redirect()->route('notifications.index');
    }

    public function readAll(Request $request): RedirectResponse
    {
        if (AdminInbox::available()) {
            $count = AdminInbox::query()->whereNull('read_at')->update(['read_at' => now()]);
            if ($count) {
                AuditLog::record('notifications.read_all', "{$request->user()->name} marked {$count} admin notifications as read.");
            }
        }

        return back()->with('success', 'All notifications marked as read.');
    }
}
