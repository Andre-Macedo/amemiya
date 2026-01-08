<div style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem; margin-top: 1rem;">

    <div style="border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem; margin-bottom: 0.5rem;">
        <h3 style="font-weight: bold; color: #374151; margin: 0;">Demonstrativo de Cálculo da Incerteza</h3>
        <p style="font-size: 0.75rem; color: #6b7280; margin: 0;">Valores expressos em milímetros (mm)</p>
    </div>

    <div>
        @foreach($budget as $index => $item)
            @php
                $divisor = is_numeric($item['divisor']) ? round($item['divisor'], 3) : $item['divisor'];
                // Definindo cores manualmente para inline
                $color = match($item['distribution']) {
                    'Normal' => '#2563eb', // Blue
                    'Retangular' => '#d97706', // Orange
                    default => '#4b5563' // Gray
                };
            @endphp

            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #f3f4f6;">

                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="background-color: #f3f4f6; color: #4b5563; font-size: 0.75rem; font-weight: bold; padding: 2px 6px; border-radius: 4px;">
                            #{{ $index + 1 }}
                        </span>
                        <strong style="color: #111827;">{{ $item['source'] }}</strong>
                    </div>
                    <div style="font-size: 0.75rem; color: #6b7280; margin-top: 4px; margin-left: 2rem;">
                        Distribuição:
                        <span style="font-weight: bold; color: {{ $color }};">{{ $item['distribution'] }}</span>
                        (dividir por {{ $item['distribution'] == 'Retangular' ? '√3 ≈' : '' }} {{ $divisor }})
                    </div>
                </div>

                <div style="text-align: right;">
                    <div style="font-family: monospace; font-size: 0.875rem; color: #4b5563;">
                        {{ number_format($item['value'], 4) }} <span style="color: #9ca3af;">/</span> {{ $divisor }}
                    </div>
                    <div style="font-family: monospace; font-weight: bold; color: #d97706;">
                        = {{ number_format($item['standard_uncertainty'], 5) }} mm
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div style="background-color: #f9fafb; border-radius: 0.5rem; padding: 1rem; margin-top: 1rem;">

        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.875rem; margin-bottom: 0.5rem;">
            <div>
                <span style="font-weight: 500; color: #374151;">Incerteza Combinada (u<sub>c</sub>)</span>
                <div style="font-size: 0.75rem; color: #9ca3af;">Raiz da soma dos quadrados (&radic;&sum;u<sub>i</sub>&sup2;)</div>
            </div>
            <div style="font-family: monospace; font-weight: bold; color: #1f2937;">
                {{ $ucFormatted }} mm
            </div>
        </div>

        <div style="border-top: 1px solid #e5e7eb; margin: 0.5rem 0;"></div>

        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span style="font-weight: 800; font-size: 1.125rem; color: #d97706;">Incerteza Expandida (U)</span>
                <div style="font-size: 0.75rem; color: #6b7280;">Multiplicado pelo fator k = {{ $k }} (95,45%)</div>
            </div>
            <div style="font-family: monospace; font-size: 1.25rem; font-weight: 900; color: #d97706;">
                &plusmn; {{ $expandedUFormatted }} mm
            </div>
        </div>
    </div>
</div>
