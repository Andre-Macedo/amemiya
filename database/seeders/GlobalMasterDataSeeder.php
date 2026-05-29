<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Metrology\Models\ChecklistTemplate;
use Modules\Metrology\Models\InstrumentType;
use Modules\Metrology\Models\Material;

class GlobalMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Materiais Globais
        Material::create(['name' => 'Aço Carbono', 'cte' => 11.5, 'category' => 'Metal', 'tenant_id' => null]);
        Material::create(['name' => 'Aço Inoxidável', 'cte' => 16.0, 'category' => 'Metal', 'tenant_id' => null]);
        Material::create(['name' => 'Alumínio', 'cte' => 23.0, 'category' => 'Metal', 'tenant_id' => null]);
        Material::create(['name' => 'Cerâmica', 'cte' => 8.0, 'category' => 'Non-Metal', 'tenant_id' => null]);

        // 2. Tipos de Instrumentos Globais
        $paquimetro = InstrumentType::create(['name' => 'Paquímetro Digital', 'tenant_id' => null]);
        $micrometro = InstrumentType::create(['name' => 'Micrômetro Externo', 'tenant_id' => null]);

        // 3. Procedimentos (Templates) Globais
        $templatePaq = ChecklistTemplate::create([
            'name' => 'Procedimento Padrão: Paquímetro',
            'instrument_type_id' => $paquimetro->id,
            'tenant_id' => null,
        ]);

        $templatePaq->items()->createMany([
            ['step' => 'Limpeza das faces de medição', 'order' => 1, 'question_type' => 'boolean', 'tenant_id' => null],
            ['step' => 'Verificação do erro de indicação (Ponto 0)', 'order' => 2, 'question_type' => 'numeric', 'nominal_value' => 0, 'tenant_id' => null],
            ['step' => 'Verificação do erro de indicação (Ponto Médio)', 'order' => 3, 'question_type' => 'numeric', 'nominal_value' => 75, 'tenant_id' => null],
        ]);
    }
}
