<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected array $columns = ['created_at', 'updated_at', 'deleted_at'];
    protected string $timezone = 'Europe/Lisbon';

    /**
     * Tabelas a ignorar (ex: tabelas de sistema, migrations, jobs, etc.)
     */
    protected array $except = [
        'migrations',
        'failed_jobs',
        'password_reset_tokens',
        'personal_access_tokens',
        'sessions',
        'jobs',
        'job_batches',
        'cache',
        'cache_locks',
    ];

    public function up(): void
    {
        $this->convert(
            fromType: 'timestamp without time zone',
            toType: 'timestamptz',
        );
    }

    public function down(): void
    {
        $this->convert(
            fromType: 'timestamp with time zone',
            toType: 'timestamp',
        );
    }

    /**
     * Percorre todas as colunas do schema 'public' que correspondam aos
     * nomes pretendidos e ao tipo de origem, e converte-as, aplicando
     * AT TIME ZONE para preservar o instante correto.
     */
    protected function convert(string $fromType, string $toType): void
    {
        $columnsList = "'" . implode("','", $this->columns) . "'";
        $exceptList  = "'" . implode("','", $this->except) . "'";

        $rows = DB::select("
            SELECT table_name, column_name
            FROM information_schema.columns
            WHERE table_schema = 'public'
              AND column_name IN ({$columnsList})
              AND data_type = '{$fromType}'
              AND table_name NOT IN ({$exceptList})
            ORDER BY table_name, column_name
        ");

        if (empty($rows)) {
            return;
        }

        foreach ($rows as $row) {
            $table  = $row->table_name;
            $column = $row->column_name;

            DB::statement(sprintf(
                'ALTER TABLE %s ALTER COLUMN %s TYPE %s USING %s AT TIME ZONE %s',
                $this->quoteIdent($table),
                $this->quoteIdent($column),
                $toType,
                $this->quoteIdent($column),
                DB::getPdo()->quote($this->timezone)
            ));
        }
    }

    protected function quoteIdent(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
};
