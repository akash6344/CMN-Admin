<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Razorpay payments for plans, written by the live website
 * (cmn_laravel_13.payment_transactions).
 *
 * Amounts are in PAISE (249900 = ₹2,499). plan_key is the plan's base slug
 * ("owner-plus") for both billing periods, so the matching plan is found by
 * plan_key + billing_period (yearly plans have slugs like "owner-plus-2").
 */
class PaymentTransaction extends Model
{
    protected $table = 'payment_transactions';

    protected $guarded = [];

    public const STATUSES = ['paid', 'pending', 'failed', 'refunded'];

    private static ?Collection $plans = null;

    public function getConnectionName(): ?string
    {
        return config('payments.connection');
    }

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'base_amount' => 'integer', 'tax_amount' => 'integer', 'amount' => 'integer'];
    }

    public static function available(): bool
    {
        try {
            return Schema::connection(config('payments.connection'))->hasTable('payment_transactions');
        } catch (\Throwable) {
            return false;
        }
    }

    public function rupees(string $field = 'amount'): float
    {
        return ((int) $this->{$field}) / 100;
    }

    /** The plan this payment was for (plan_key + billing_period). */
    public function plan(): ?PaymentsPlan
    {
        self::$plans ??= self::loadPlans();

        return self::$plans->first(fn (PaymentsPlan $p) => $p->billing_frequency === $this->billing_period
                && ($p->slug === $this->plan_key || preg_match('/^'.preg_quote((string) $this->plan_key, '/').'-\d+$/', (string) $p->slug)))
            ?? self::$plans->firstWhere('slug', $this->plan_key);
    }

    public function audience(): ?string
    {
        return $this->plan()?->target_audience;
    }

    /** Payer name/email from the live users table when it is reachable. */
    public function payer(): array
    {
        static $users = null;
        if ($users === null) {
            $users = collect();
            try {
                $conn = config('payments.connection');
                if (Schema::connection($conn)->hasTable('users') && Schema::connection($conn)->hasColumn('users', 'email')) {
                    $users = \DB::connection($conn)->table('users')->get(['id', 'name', 'email'])->keyBy('id');
                }
            } catch (\Throwable) {
            }
        }
        $u = $users[$this->user_id] ?? null;

        return ['name' => $u->name ?? 'User #'.$this->user_id, 'email' => $u->email ?? null];
    }

    public function invoiceUrl(): ?string
    {
        $base = rtrim((string) config('payments.invoice_base_url'), '/');

        return $this->invoice && $base ? $base.'/'.ltrim($this->invoice, '/') : null;
    }

    public static function flushPlanCache(): void
    {
        self::$plans = null;
    }

    private static function loadPlans(): Collection
    {
        try {
            return PaymentsPlan::query()->get();
        } catch (\Throwable) {
            return collect();
        }
    }
}
