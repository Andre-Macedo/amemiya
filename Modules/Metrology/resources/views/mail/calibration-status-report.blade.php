<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
        .container { background: #fff; max-width: 600px; margin: 0 auto; padding: 20px; border-radius: 8px; }
        .header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #eee; }
        .alert { color: #d9534f; font-weight: bold; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th { background: #eee; text-align: left; padding: 8px; }
        .table td { border-bottom: 1px solid #ddd; padding: 8px; }
        .btn { display: inline-block; background: #007bff; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="{{$message->embed(storage_path('/app/public/Lean_Tech_logo.jpg'))}}" alt="Logo" width="200"
             style="width: 200px; height: auto;">
        <h2>Relatório de Calibração</h2>
    </div>

    <p>Olá,</p>
    <p>Segue o resumo diário dos instrumentos.</p>

    @if($expiredInstruments->count() > 0)
        <div style="background: #fff5f5; border-left: 4px solid #fc8181; padding: 10px; margin: 15px 0;">
            <h3 class="alert" style="margin: 0;">🚨 {{ $expiredInstruments->count() }} Vencidos</h3>
            <ul style="margin: 10px 0 0 20px;">
                @foreach($expiredInstruments as $instrument)
                    <li><b>{{ $instrument->stock_number }}</b>: {{ $instrument->name }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($dueSoonInstruments->count() > 0)
        <h3>⚠️ Vencendo em Breve</h3>
        <table class="table">
            <tr>
                <th>Código</th>
                <th>Instrumento</th>
                <th>Vencimento</th>
            </tr>
            @foreach($dueSoonInstruments as $instrument)
                <tr>
                    <td>{{ $instrument->stock_number }}</td>
                    <td>{{ $instrument->name }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($instrument->calibration_due)->format('d/m/Y') }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div style="text-align: center;">
        <a href="{{ route('filament.admin.metrology.resources.instruments.index') }}" class="btn">
            Acessar Painel
        </a>
    </div>
</div>
</body>
</html>
