<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Etiqueta</title>
    <style>
        @page {
            size: 50mm 30mm;
            margin: 0;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 2mm;
            text-align: center;
        }
        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
        }
        .title {
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .asset-id {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .name {
            font-size: 7px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 48mm;
        }
        .qr-code {
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="title">CONTROLE DE METROLOGIA</div>
        <div class="asset-id">{{ $record->asset_number ?? $record->serial_number }}</div>
        <div class="name">{{ Str::limit($record->name, 25) }}</div>
        
        <div class="qr-code">
            <img src="data:image/svg+xml;base64,{{ base64_encode($qrCode) }}" alt="QR Code" width="60" height="60">
        </div>
        <div style="font-size: 6px; margin-top: 2px;">ID: {{ $record->id }}</div>
    </div>
</body>
</html>


