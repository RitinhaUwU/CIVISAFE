<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DonationGoodsTypeSeeder extends Seeder
{
    public function run(): void
    {
        $csv = database_path('data/goodTypes.csv');
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

                $countable = !($row_csv[1] == null || $row_csv[1] == "");

                $data[] = [
                    'name' => $row_csv[0],
                    'is_type_countable' => $countable,
                    'unit' => $countable ? $row_csv[1] : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            fclose($file);
        }

        DB::table('donation_goods_types')->upsert($data, 'id');
    }
}
