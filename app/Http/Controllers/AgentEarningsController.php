<?php

namespace App\Http\Controllers;

use App\Models\Agent\AgentCommission;
use App\Models\Agent\AgentPayout;
use App\Models\Agent\AgentUser;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Admin side of agent Earnings: record commissions, mark them received and
 * process payout requests. Writes to the CMN-Agent `commissions` / `payouts`
 * tables that power the Agent portal's Earnings page.
 */
class AgentEarningsController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'payouts' ? 'payouts' : 'commissions';
        $filters = [
            'agent' => (int) $request->query('agent', 0),
            'status' => (string) $request->query('status', ''),
            'type' => array_key_exists((string) $request->query('type'), AgentCommission::DEAL_TYPES) ? $request->query('type') : '',
        ];
        $available = self::available();

        $empty = ['available' => $available, 'tab' => $tab, 'filters' => $filters, 'agents' => collect(), 'stats' => array_fill_keys(['pending', 'received', 'requested', 'paid', 'requested_count'], 0),
            'commissions' => null, 'payouts' => null, 'dealTypes' => AgentCommission::DEAL_TYPES];
        if (! $available) {
            return view('pages.agent-earnings', $empty);
        }

        $agents = AgentUser::query()->orderBy('name')->get(['id', 'name', 'email']);
        $stats = [
            'pending' => (int) AgentCommission::query()->where('status', 'pending')->sum('amount'),
            'received' => (int) AgentCommission::query()->where('status', 'received')->sum('amount'),
            'requested' => (int) AgentPayout::query()->whereIn('status', ['requested', 'processing'])->sum('amount'),
            'requested_count' => AgentPayout::query()->whereIn('status', ['requested', 'processing'])->count(),
            'paid' => (int) AgentPayout::query()->where('status', 'paid')->sum('amount'),
        ];

        $commissions = null;
        $payouts = null;
        if ($tab === 'commissions') {
            $commissions = AgentCommission::query()->with('agent')
                ->when($filters['agent'], fn ($q, $a) => $q->where('agent_id', $a))
                ->when(in_array($filters['status'], ['pending', 'received'], true), fn ($q) => $q->where('status', $filters['status']))
                ->when($filters['type'], fn ($q, $t) => $q->where('deal_type', $t))
                ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")->orderByDesc('earned_at')
                ->paginate(15)->withQueryString();
        } else {
            $payouts = AgentPayout::query()->with('agent')
                ->when($filters['agent'], fn ($q, $a) => $q->where('agent_id', $a))
                ->when(in_array($filters['status'], ['requested', 'processing', 'paid', 'rejected'], true), fn ($q) => $q->where('status', $filters['status']))
                ->orderByRaw("CASE status WHEN 'requested' THEN 0 WHEN 'processing' THEN 1 ELSE 2 END")->orderByDesc('requested_at')
                ->paginate(15)->withQueryString();
        }

        return view('pages.agent-earnings', compact('available', 'tab', 'filters', 'agents', 'stats', 'commissions', 'payouts') + ['dealTypes' => AgentCommission::DEAL_TYPES]);
    }

    public function storeCommission(Request $request): RedirectResponse
    {
        abort_unless(self::available(), 503, 'Agent database is not available.');
        $data = $request->validate([
            'agent_id' => ['required', 'integer'],
            'entry_type' => ['required', 'in:commission,adjustment'],
            'deal_type' => ['required', 'in:sale,rent,other'],
            'deal_code' => ['required', 'string', 'max:20'],
            'property_title' => ['required', 'string', 'max:255'],
            'property_location' => ['nullable', 'string', 'max:255'],
            'client_name' => ['required', 'string', 'max:120'],
            'deal_value' => ['nullable', 'integer', 'min:0'],
            'amount' => ['required', 'integer', 'not_in:0', 'min:-100000000', 'max:100000000'],
            'status' => ['required', 'in:pending,received'],
            'earned_at' => ['required', 'date'],
        ]);
        $agent = AgentUser::query()->findOrFail($data['agent_id']);

        $c = AgentCommission::query()->create($data + [
            'received_at' => $data['status'] === 'received' ? now() : null,
        ]);

        AuditLog::record('agent_commission.created', "Commission {$c->deal_code} of ₹".number_format($c->amount)." recorded for {$agent->name}.", null,
            ['commission_id' => $c->id, 'agent_id' => $agent->id, 'amount' => $c->amount, 'entry_type' => $c->entry_type]);

        return back()->with('success', "Commission {$c->deal_code} added for {$agent->name}.");
    }

    public function markReceived(int $commission): RedirectResponse
    {
        $c = AgentCommission::query()->findOrFail($commission);
        if ($c->status !== 'pending') {
            return back()->withErrors(['commission' => 'Only pending commissions can be marked received.']);
        }
        $c->update(['status' => 'received', 'received_at' => now()]);

        AuditLog::record('agent_commission.received', "Commission {$c->deal_code} (₹".number_format($c->amount).') marked received.', null,
            ['commission_id' => $c->id, 'agent_id' => $c->agent_id]);

        return back()->with('success', "Commission {$c->deal_code} marked received. It is now available for the agent's payout.");
    }

    public function processPayout(int $payout): RedirectResponse
    {
        $p = AgentPayout::query()->findOrFail($payout);
        if ($p->status !== 'requested') {
            return back()->withErrors(['payout' => 'Only requested payouts can be moved to processing.']);
        }
        $p->update(['status' => 'processing']);
        AuditLog::record('agent_payout.processing', "Payout {$p->payout_code} moved to processing.", null, ['payout_id' => $p->id, 'agent_id' => $p->agent_id]);

        return back()->with('success', "Payout {$p->payout_code} is processing.");
    }

    public function payPayout(Request $request, int $payout): RedirectResponse
    {
        $p = AgentPayout::query()->findOrFail($payout);
        if (! in_array($p->status, ['requested', 'processing'], true)) {
            return back()->withErrors(['payout' => 'This payout is already closed.']);
        }
        $data = $request->validate(['bank_reference' => ['required', 'string', 'max:80']]);
        $p->update(['status' => 'paid', 'bank_reference' => $data['bank_reference'], 'paid_at' => now()]);

        // Link the agent's received, unlinked commissions to this payout (oldest first, up to the amount).
        $left = (int) $p->amount;
        AgentCommission::query()->where('agent_id', $p->agent_id)->where('status', 'received')->whereNull('payout_id')
            ->orderBy('received_at')->get()
            ->each(function (AgentCommission $c) use (&$left, $p) {
                if ($left <= 0 || $c->amount > $left) {
                    return false;
                }
                $c->update(['payout_id' => $p->id]);
                $left -= $c->amount;
            });

        AuditLog::record('agent_payout.paid', "Payout {$p->payout_code} of ₹".number_format($p->amount).' paid (UTR '.$data['bank_reference'].').', null,
            ['payout_id' => $p->id, 'agent_id' => $p->agent_id, 'bank_reference' => $data['bank_reference']]);

        return back()->with('success', "Payout {$p->payout_code} marked paid.");
    }

    public function rejectPayout(int $payout): RedirectResponse
    {
        $p = AgentPayout::query()->findOrFail($payout);
        if (! in_array($p->status, ['requested', 'processing'], true)) {
            return back()->withErrors(['payout' => 'This payout is already closed.']);
        }
        $p->update(['status' => 'rejected']);
        AuditLog::record('agent_payout.rejected', "Payout {$p->payout_code} rejected; amount returned to the agent's balance.", null, ['payout_id' => $p->id, 'agent_id' => $p->agent_id]);

        return back()->with('success', "Payout {$p->payout_code} rejected. The amount is available to the agent again.");
    }

    public static function available(): bool
    {
        try {
            return Schema::connection('agent')->hasTable('commissions') && Schema::connection('agent')->hasTable('payouts');
        } catch (\Throwable) {
            return false;
        }
    }
}
