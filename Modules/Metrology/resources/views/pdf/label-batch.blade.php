<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Etiquetas em Lote</title>
    <style>
        @page {
            size: 50mm 30mm;
            margin: 0;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .page {
            page-break-after: always;
            height: 30mm;
            width: 50mm;
            box-sizing: border-box;
            padding: 2mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            overflow: hidden;
        }
        .page:last-child {
            page-break-after: avoid;
        }
        .title {
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 2px;
            width: 100%;
        }
        .asset-id {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 2px;
            width: 100%;
        }
        .name {
            font-size: 7px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 46mm;
            margin: 0 auto;
        }
        .qr-code {
            margin-top: 2px;
        }
    </style>
</head>
<body>
    @foreach ($labels as $data)
    <div class="page">
        <div class="title">CONTROLE DE METROLOGIA</div>
        <div class="asset-id">{{ $data['record']->asset_number ?? $data['record']->serial_number }}</div>
        <div class="name">{{ Str::limit($data['record']->name, 25) }}</div>
        
        <div class="qr-code">
            <img src="data:image/svg+xml;base64,{{ base64_encode($data['qrCode']) }}" alt="QR Code" width="60" height="60">
        </div>
        <div style="font-size: 6px; margin-top: 2px;">ID: {{ $data['record']->id }}</div>
    </div>
    @endforeach
</body>
</html>
