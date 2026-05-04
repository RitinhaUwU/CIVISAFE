<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class IncidentTypesSeeder extends Seeder
{
    public function run(): void
    {
        $csv = database_path('data/ocorrencias_nop3101.csv');
        if (!file_exists($csv) || !is_readable($csv)) {
            throw new \Exception("CSV file not found or not readable: {$csv}");
        }

        $header = null;
        $data = [];

        if (($file = fopen($csv, 'r')) !== false) {
            while (($row_csv = fgetcsv($file, 1000, ',')) !== false) {
                if (!$header) {
                    $header = $row_csv;
                    continue;
                }

                $data[] = [
                    'code' => (int)$row_csv[0],
                    'species' => $row_csv[1],
                    'type' => $row_csv[2],
                    'description' => $row_csv[3],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            fclose($file);
        }

        DB::table('incident_types')->upsert(
            $data,
            ['id'],
            ['species', 'type', 'description', 'created_at', 'updated_at'],
        );
    }
}
