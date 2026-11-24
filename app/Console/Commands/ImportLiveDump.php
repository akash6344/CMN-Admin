<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * LOCAL DEV ONLY: load phpMyAdmin dumps of live-site tables into the SQLite
 * fallback of the `live` connection (database/live.sqlite).
 *
 *   php artisan live:import-dump ../payments_plans.sql ../payment_transactions.sql --fresh
 *
 * In production point LIVE_DB_* at MySQL instead — nothing to import.
 */
class ImportLiveDump extends Command
{
    protected $signature = 'live:import-dump {files* : One or more .sql dump files} {--fresh : Drop each table before importing}';

    protected $description = 'Import MySQL table dumps into the local live.sqlite database';

    public function handle(): int
    {
        $db = DB::connection('live');
        if ($db->getDriverName() !== 'sqlite') {
            $this->error('The live connection is MySQL — import dumps with phpMyAdmin / mysql instead.');

            return self::FAILURE;
        }
        $path = $db->getConfig('database');
        if (! file_exists($path)) {
            touch($path);
        }

        foreach ($this->argument('files') as $file) {
            if (! is_file($file)) {
                $this->error("Not found: {$file}");

                return self::FAILURE;
            }
            $sql = file_get_contents($file);

            if (! preg_match_all('/CREATE TABLE `(\w+)` \((.*?)\n\)[^;]*;/s', $sql, $tables, PREG_SET_ORDER)) {
                $this->warn("No CREATE TABLE in {$file}");

                continue;
            }

            foreach ($tables as [$all, $table, $cols]) {
                $cols = $this->toSqlite($cols);
                if ($this->option('fresh')) {
                    $db->statement("DROP TABLE IF EXISTS `{$table}`");
                }
                $db->statement("CREATE TABLE IF NOT EXISTS `{$table}` ({$cols})");

                preg_match_all('/INSERT INTO `'.$table.'` .*?\);\s*$/ms', $sql, $inserts);
                foreach ($inserts[0] as $stmt) {
                    $stmt = strtr($stmt, ["\\'" => "''", '\\"' => '"', '\\\\' => '\\']);
                    $db->unprepared(preg_replace('/^INSERT INTO/', 'INSERT OR REPLACE INTO', $stmt));
                }
                $this->info("{$table}: ".$db->table($table)->count().' row(s)');
            }
        }

        return self::SUCCESS;
    }

    /** MySQL column list → SQLite. */
    private function toSqlite(string $cols): string
    {
        $cols = preg_replace('/`id` bigint\(\d+\) UNSIGNED NOT NULL/i', '`id` INTEGER PRIMARY KEY AUTOINCREMENT', $cols, 1);
        $cols = preg_replace('/\s+CHECK\s*\((?:[^()]|\((?:[^()]|\([^()]*\))*\))*\)/i', '', $cols);
        $cols = preg_replace('/ CHARACTER SET \w+/i', '', $cols);
        $cols = preg_replace('/ COLLATE \w+/i', '', $cols);

        return preg_replace('/ UNSIGNED/i', '', $cols);
    }
}
