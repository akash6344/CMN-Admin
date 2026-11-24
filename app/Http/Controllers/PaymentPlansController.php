<?php

namespace App\Http\Controllers;

use App\Models\PaymentsPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PaymentPlansController extends Controller
{
    public function index(Request $request): View
    {
        $plans = PaymentsPlan::query()
            ->when($request->string('audience')->isNotEmpty(), fn ($query) => $query->where('target_audience', $request->string('audience')->toString()))
            ->when($request->string('billing')->isNotEmpty(), fn ($query) => $query->where('billing_frequency', $request->string('billing')->toString()))
            ->when($request->string('status')->toString() === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->string('status')->toString() === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($request->string('search')->isNotEmpty(), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(fn ($query) => $query->where('plan_name', 'like', "%{$search}%")->orWhere('target_audience', 'like', "%{$search}%"));
            })->orderBy('sort_order')->orderBy('id')->get();

        return view('pages.payments-plans', [
            'plans' => $plans,
            'totalPlans' => PaymentsPlan::query()->count(),
            'activePlans' => PaymentsPlan::query()->where('is_active', true)->count(),
            'subscriptionPlans' => PaymentsPlan::query()->where('plan_type', 'subscription')->count(),
            'boostPlans' => PaymentsPlan::query()->where('plan_type', 'boost')->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePlan($request);
        $plan = new PaymentsPlan($this->planAttributes($request, $validated));
        $plan->slug = $this->uniqueSlug($validated['plan_name']);
        $plan->save();

        return back()->with('success', 'Plan created successfully.');
    }

    public function update(Request $request, PaymentsPlan $paymentsPlan): RedirectResponse
    {
        $paymentsPlan->update($this->planAttributes($request, $this->validatePlan($request)));

        return back()->with('success', 'Plan updated successfully.');
    }

    public function toggle(PaymentsPlan $paymentsPlan): RedirectResponse
    {
        $paymentsPlan->update(['is_active' => ! $paymentsPlan->is_active]);

        return back()->with('success', $paymentsPlan->is_active ? 'Plan enabled.' : 'Plan disabled.');
    }

    public function destroy(PaymentsPlan $paymentsPlan): RedirectResponse
    {
        $paymentsPlan->delete();

        return back()->with('success', 'Plan deleted successfully.');
    }

    /** @return array<string, mixed> */
    private function validatePlan(Request $request): array
    {
        return $request->validate([
            'plan_name' => ['required', 'string', 'max:150'], 'plan_type' => ['required', 'in:subscription,boost'],
            'target_audience' => ['nullable', 'in:buyers_renters,agents_brokers,owners_landlords,builders_developers'], 'billing_frequency' => ['required', 'in:monthly,yearly'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'], 'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'active_listings_quota' => ['nullable', 'required_unless:is_unlimited_listings,1', 'integer', 'min:0'], 'button_text' => ['nullable', 'string', 'max:255'], 'is_unlimited_listings' => ['nullable', 'boolean'],
            'gst_percentage' => ['required', 'numeric', 'in:0,5,12,18,28'], 'gst_sac_code' => ['nullable', 'string', 'max:20'], 'gst_description' => ['nullable', 'string', 'max:255'],
            'team_seats' => ['nullable', 'integer', 'min:0'], 'boosted_listings_quota' => ['nullable', 'integer', 'min:0'],
            'has_ai_features' => ['nullable', 'boolean'], 'has_priority_leads' => ['nullable', 'boolean'], 'has_verified_badge' => ['nullable', 'boolean'],
            'features' => ['nullable', 'string', 'max:5000'], 'description' => ['nullable', 'string'], 'sort_order' => ['required', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'], 'is_active' => ['nullable', 'boolean'],
        ]);
    }

    /** @param array<string, mixed> $validated
     *  @return array<string, mixed> */
    private function planAttributes(Request $request, array $validated): array
    {
        $price = (float) $validated['price'];
        $discount = (float) ($validated['discount'] ?? 0);
        $features = preg_split('/\r\n|\r|\n/', $validated['features'] ?? '') ?: [];

        return ['plan_name' => $validated['plan_name'], 'plan_type' => $validated['plan_type'], 'target_audience' => $validated['target_audience'] ?? null, 'billing_frequency' => $validated['billing_frequency'], 'price' => $price, 'discount' => $discount, 'final_price' => round($price - ($price * $discount / 100), 2), 'active_listings_quota' => $request->boolean('is_unlimited_listings') ? null : ($validated['active_listings_quota'] ?? null), 'button_text' => $validated['button_text'] ?? null, 'is_unlimited_listings' => $request->boolean('is_unlimited_listings'), 'gst_percentage' => $validated['gst_percentage'], 'gst_sac_code' => $validated['gst_sac_code'] ?? null, 'gst_description' => $validated['gst_description'] ?? null, 'team_seats' => $validated['team_seats'] ?? null, 'boosted_listings_quota' => $validated['boosted_listings_quota'] ?? null, 'has_ai_features' => $request->boolean('has_ai_features'), 'has_priority_leads' => $request->boolean('has_priority_leads'), 'has_verified_badge' => $request->boolean('has_verified_badge'), 'features' => array_values(array_filter(array_map('trim', $features))), 'description' => $validated['description'] ?? null, 'sort_order' => $validated['sort_order'], 'is_featured' => $request->boolean('is_featured'), 'is_active' => $request->boolean('is_active')];
    }

    private function uniqueSlug(string $planName): string
    {
        $baseSlug = Str::limit(Str::slug($planName), 180, '') ?: 'plan';
        $slug = $baseSlug;
        $suffix = 2;
        while (PaymentsPlan::query()->where('slug', $slug)->exists()) {
            $suffixText = '-'.$suffix++;
            $slug = Str::limit($baseSlug, 180 - strlen($suffixText), '').$suffixText;
        }

        return $slug;
    }
}
