<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Which columns the website DB (connection "dashboard") actually has. The local CMN-Dashboard copy and the live
 * cmn_laravel_13 DB differ (documents, notifications, post_properties.form_status …), so code asks here before
 * touching a column. Looked up once per request / job (scoped singleton, see AppServiceProvider).
 */
class WebsiteSchema
{
    /** @var array<string, list<string>> */
    private array $columns = [];

    public static function has(string $table, string $column): bool
    {
        return in_array(strtolower($column), self::columns($table), true);
    }

    public static function hasTable(string $table): bool
    {
        return self::columns($table) !== [];
    }

    /** @return list<string> lower-case column names; [] when the table (or the DB) is missing */
    public static function columns(string $table): array
    {
        $self = app(self::class);
        if (! array_key_exists($table, $self->columns)) {
            try {
                $self->columns[$table] = array_map('strtolower', Schema::connection('dashboard')->getColumnListing($table));
            } catch (\Throwable) {
                $self->columns[$table] = [];
            }
        }

        return $self->columns[$table];
    }

    /** Only the keys of $values that are real columns of $table. */
    public static function only(string $table, array $values): array
    {
        return array_filter($values, fn ($k) => self::has($table, (string) $k), ARRAY_FILTER_USE_KEY);
    }
}
