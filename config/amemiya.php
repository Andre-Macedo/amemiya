<?php

return [
    /**
     * Lista de módulos/add-ons disponíveis no ecossistema Amemiya.
     * Estes IDs serão usados para controlar o acesso e faturamento.
     */
    'modules' => [
        'metrology_core' => [
            'name' => 'Metrologia Base',
            'description' => 'Gestão de Instrumentos, Calibrações e Padrões.',
            'is_core' => true,
        ],
        'production_tracking' => [
            'name' => 'Rastreabilidade de Peças',
            'description' => 'Vincula medições de linha a ordens de produção e peças específicas.',
            'is_core' => false,
        ],
        'quality_qms' => [
            'name' => 'Qualidade Avançada (QMS)',
            'description' => 'Módulo completo de auditorias, CAPAs e gestão ISO.',
            'is_core' => false,
        ],
        'logistics_rfid' => [
            'name' => 'Logística & RFID',
            'description' => 'Controle físico de movimentação via tags e portais de leitura.',
            'is_core' => false,
        ],
        'billing_addon' => [
            'name' => 'Faturamento & Comercial',
            'description' => 'Gestão financeira para laboratórios de prestação de serviços.',
            'is_core' => false,
        ],
    ],
];
