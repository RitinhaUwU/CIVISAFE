<?php

namespace Database\Seeders;

use App\Models\IncidentPriority;
use Illuminate\Database\Seeder;

class IncidentPrioritySeeder extends Seeder
{
    public function run(): void
    {
        IncidentPriority::factory()->create([
            'name' => 'P1',
            'description' => 'Emergente',
            'hex_color' => '#ff0000'
        ]);

        IncidentPriority::factory()->create([
            'name' => 'P2',
            'description' => 'Muito Urgente',
            'hex_color' => '#ff7300'
        ]);

        IncidentPriority::factory()->create([
            'name' => 'P3',
            'description' => 'Urgente',
            'hex_color' => '#ffff00'
        ]);

        IncidentPriority::factory()->create([
            'name' => 'P4',
            'description' => 'Pouco Urgente',
            'hex_color' => '#00ff00'
        ]);

        IncidentPriority::factory()->create([
            'name' => 'P5',
            'description' => 'Não Urgente',
            'hex_color' => '#00bffff'
        ]);

    }
}
