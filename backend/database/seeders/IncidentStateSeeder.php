<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class IncidentStateSeeder extends Seeder
{
    public function run(): void
    {
        $csv = database_path('data/estados.csv');
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
                    'name' => $row_csv[0],
                    'description' => $row_csv[1],
                    'hex_color' => $row_csv[3],
                    'terminates_incident' => $row_csv[2] === "true",
                    'is_active' => true
                ];
            }

            fclose($file);
        }

        DB::table('incident_states')->upsert($data, 'id');
    }
}
