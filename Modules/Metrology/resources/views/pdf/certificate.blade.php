<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 18px; font-weight: bold; }
        .section { margin-bottom: 15px; }
        .label { font-weight: bold; width: 120px; display: inline-block; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 5px; text-align: center; }
        th { background-color: #eee; }
        .footer { margin-top: 50px; text-align: center; font-size: 10px; }
    </style>
</head>
<body>
<div class="header">
    <div class="title">CERTIFICADO DE CALIBRAÇÃO</div>
    <div>Nº {{ str_pad($record->id, 6, '0', STR_PAD_LEFT) }} / {{ $record->calibration_date->format('Y') }}</div>
</div>

<div class="section">
    <div><span class="label">Instrumento:</span> {{ $instrument->name }}</div>
    <div><span class="label">Código (Tag):</span> {{ $instrument->stock_number }}</div>
    <div><span class="label">Fabricante:</span> {{ $instrument->manufacturer->name ?? 'N/A' }}</div>
    <div><span class="label">Nº de Série:</span> {{ $instrument->serial_number }}</div>
    <div><span class="label">Capacidade:</span> {{ $instrument->measuring_range ?? '-' }}</div>
    <div><span class="label">Resolução:</span> {{ $instrument->resolution ?? '-' }}</div>
</div>

<div class="section">
    <div><span class="label">Data Calibração:</span> {{ $record->calibration_date->format('d/m/Y') }}</div>
    <div><span class="label">Próxima Calib.:</span> {{ \Illuminate\Support\Carbon::parse($instrument->calibration_due)?->format('d/m/Y') ?? '-' }}</div>
    <div><span class="label">Local:</span> Laboratório Interno</div>
    <div><span class="label">Temperatura:</span> {{ $record->temperature }}°C ± 2°C</div>
    <div><span class="label">Umidade:</span> {{ $record->humidity }}% ± 10%</div>
</div>

<div class="section">
    <h3>Padrões Utilizados</h3>
    <ul>
        @foreach($record->checklist->items->unique('reference_standard_id') as $item)
            @if($item->referenceStandard)
                <li>{{ $item->referenceStandard->name }} ({{ $item->referenceStandard->stock_number }}) - Val: {{ $item->referenceStandard->calibration_due?->format('d/m/Y') }}</li>
            @endif
        @endforeach
    </ul>
</div>

<div class="section">
    <h3>Resultados de Medição</h3>
    <table>
        <thead>
        <tr>
            <th>Ponto Nominal</th>
            <th>Leituras</th>
            <th>Média</th>
            <th>Erro (Desvio)</th>
            <th>Incerteza</th>
        </tr>
        </thead>
        <tbody>
        @foreach($record->checklist->items->where('question_type', 'numeric') as $item)
            @php
                $readings = is_string($item->readings) ? json_decode($item->readings) : $item->readings;
                $avg = collect($readings)->avg();

                // 1. Nominal: Vem do template (se existir) ou fallback no step
                $nominal = $item->nominal_value ?? (float) preg_replace('/[^0-9.]/', '', $item->step);

                // 2. Valor Verdadeiro: Idealmente viria do Padrão ($item->referenceStandard->actual_value)
                // Se não tiver o valor exato cadastrado, assumimos que o padrão é nominalmente perfeito (simplificação)
                $refValue = $nominal;

                $error = $avg - $refValue;
            @endphp
            <tr>
                <td>{{ number_format($nominal, 2) }} mm</td>
                <td>{{ number_format($refValue, 3) }}</td> <td>{{ number_format($avg, 3) }}</td>      <td>{{ number_format($error, 4) }}</td>    <td>{{ $record->uncertainty }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="section" style="margin-top: 30px;">
    <div><span class="label">Resultado Final:</span>
        <b style="color: {{ $record->result == 'approved' ? 'green' : 'red' }}">
            {{ $record->result == 'approved' ? 'APROVADO' : 'REPROVADO' }}
        </b>
    </div>
</div>

<div class="footer">
    <p>Calibrado por: {{ $record->performedBy->name }} em {{ $record->created_at->format('d/m/Y H:i') }}</p>
    <p>Este certificado não deve ser reproduzido parcialmente sem aprovação.</p>
</div>
</body>
</html>
