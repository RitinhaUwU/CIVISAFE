<?php

namespace Database\Seeders;

use App\Models\EntityType;
use Illuminate\Database\Seeder;

class EntityTypesSeeder extends Seeder
{
    public function run(): void
    {
        EntityType::factory()->create([
            'name' => 'Corpos de Bombeiros',
            'description' => 'Primeira linha de combate a incêndios e socorro'
        ]);

        EntityType::factory()->create([
            'name' => 'Forças de Segurança',
            'description' => 'Garantem a segurança pública, evacuações e controlo de tráfego'
        ]);

        EntityType::factory()->create([
            'name' => 'Forças Armadas',
            'description' => 'Prestam apoio logístico, transporte e meios humanos em situações de catástrofe'
        ]);

        EntityType::factory()->create([
            'name' => 'Autoridade Marítima Nacional',
            'description' => 'Atua na proteção de zonas costeiras e busca e salvamento no mar'
        ]);

        EntityType::factory()->create([
            'name' => 'Instituto Nacional de Emergência Médica (INEM)',
            'description' => 'Responsável pelo socorro médico pré-hospitalar'
        ]);

        EntityType::factory()->create([
            'name' => 'Autoridade Nacional de Aviação Civil (ANAC)',
            'description' => ' Regula e coopera na segurança aérea'
        ]);

    }
}
