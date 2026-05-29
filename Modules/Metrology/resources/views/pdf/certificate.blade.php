<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; margin: 0; padding: 0; }
        .page-header { width: 100%; border-bottom: 2px solid {{ $identity['accent_color'] }}; padding-bottom: 10px; margin-bottom: 20px; }
        .lab-info { float: left; width: 70%; }
        .lab-logo { float: right; width: 25%; text-align: right; }
        .lab-logo img { max-height: 60px; max-width: 100%; }
        .clear { clear: both; }
        
        .certificate-title { text-align: center; margin: 20px 0; }
        .title { font-size: 18px; font-weight: bold; color: {{ $identity['accent_color'] }}; }
        .cert-number { font-size: 12px; font-weight: bold; margin-top: 5px; }

        .section { margin-bottom: 20px; }
        .section-title { font-size: 12px; font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 3px; margin-bottom: 10px; text-transform: uppercase; color: #666; }
        
        .info-grid { width: 100%; }
        .info-item { margin-bottom: 5px; }
        .label { font-weight: bold; color: #555; width: 130px; display: inline-block; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: center; }
        th { background-color: #f9f9f9; color: #444; font-weight: bold; }
        
        .result-pass { color: green; font-weight: bold; }
        .result-fail { color: red; font-weight: bold; }
        
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        .signatures { margin-top: 40px; width: 100%; }
        .signature-box { width: 45%; text-align: center; float: left; }
        .signature-line { border-top: 1px solid #000; margin-top: 40px; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="lab-info">
            <div style="font-size: 14px; font-weight: bold;">{{ $identity['lab_name'] }}</div>
            <div>{{ $identity['lab_address'] }}</div>
            <div>{{ $identity['lab_contact'] }}</div>
        </div>
        <div class="lab-logo">
            @if($identity['lab_logo_path'])
                <img src="{{ storage_path('app/public/' . $identity['lab_logo_path']) }}" alt="Logo">
            @else
                <div style="height: 60px; width: 100px; background: #eee; line-height: 60px; text-align: center; color: #999; font-size: 10px; border-radius: 4px;">NO LOGO</div>
            @endif
        </div>
        <div class="clear"></div>
    </div>

    <div class="certificate-title">
        <div class="title">CERTIFICADO DE CALIBRAÇÃO</div>
        <div class="cert-number">Nº {{ str_pad((string)$record->id, 6, '0', STR_PAD_LEFT) }} / {{ $record->calibration_date->format('Y') }}</div>
    </div>

    <div class="section">
        <div class="section-title">Identificação do Instrumento</div>
        <div class="info-item"><span class="label">Instrumento:</span> {{ $instrument->name }}</div>
        <div class="info-item"><span class="label">Código (Tag):</span> {{ $instrument->stock_number }}</div>
        <div class="info-item"><span class="label">Fabricante / Modelo:</span> {{ $instrument->manufacturer ?? 'N/A' }} / {{ $instrument->model ?? 'N/A' }}</div>
        <div class="info-item"><span class="label">Nº de Série:</span> {{ $instrument->serial_number }}</div>
        <div class="info-item"><span class="label">Capacidade / Resolução:</span> {{ $instrument->measuring_range ?? '-' }} / {{ $instrument->resolution ?? '-' }}</div>
    </div>

    <div class="section">
        <div class="section-title">Dados da Calibração</div>
        <div class="info-item"><span class="label">Data de Execução:</span> {{ $record->calibration_date->format('d/m/Y') }}</div>
        <div class="info-item"><span class="label">Vencimento:</span> {{ \Illuminate\Support\Carbon::parse($instrument->next_calibration_date)?->format('d/m/Y') ?? '-' }}</div>
        <div class="info-item"><span class="label">Condições Ambientais:</span> {{ $record->temperature }}°C ± 2°C | {{ $record->humidity }}% ± 10% RH</div>
    </div>

    <div class="section">
        <div class="section-title">Padrões de Referência</div>
        <table>
            <thead>
                <tr>
                    <th style="text-align: left;">Descrição do Padrão</th>
                    <th>Identificação</th>
                    <th>Certificado de Calibração</th>
                    <th>Validade</th>
                </tr>
            </thead>
            <tbody>
                @forelse($standards as $std)
                    <tr>
                        <td style="text-align: left;">{{ $std->name }}</td>
                        <td>{{ $std->serial_number }}</td>
                        <td>{{ $std->last_certificate_number ?? 'N/A' }}</td>
                        <td>{{ $std->next_calibration_date ? \Illuminate\Support\Carbon::parse($std->next_calibration_date)->format('d/m/Y') : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Nenhum padrão registrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Resultados de Medição</div>
        <table>
            <thead>
                <tr>
                    <th>Ponto Nominal</th>
                    <th>Vlr. Medido (Média)</th>
                    <th>Erro (Desvio)</th>
                    <th>Incerteza (U) k=2</th>
                    <th>Resultado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($results as $res)
                    <tr>
                        <td>{{ number_format($res['nominal'], 4) }}</td>
                        <td>{{ number_format($res['average'], 4) }}</td>      
                        <td>{{ number_format($res['error'], 4) }}</td>    
                        <td>{{ number_format((float)$res['uncertainty'], 5) }}</td>
                        <td>
                            <span class="{{ $res['result'] == 'Pass' || $res['result'] == 'Approved' ? 'result-pass' : 'result-fail' }}">
                                {{ $res['result'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Conclusão</div>
        <div style="background: #fcfcfc; border: 1px solid #eee; padding: 10px; border-radius: 4px;">
            <div>Status Final: 
                <span class="{{ $record->result == 'approved' ? 'result-pass' : 'result-fail' }}" style="font-size: 14px;">
                    {{ $record->result == 'approved' ? 'CONFORME (APROVADO)' : 'NÃO CONFORME (REPROVADO)' }}
                </span>
            </div>
            @if($record->notes)
                <div style="margin-top: 10px; font-style: italic;">Obs: {{ $record->notes }}</div>
            @endif
        </div>
    </div>

    <div class="signatures">
        <div class="signature-box" style="float: right;">
            <div class="signature-line">
                <b>{{ $record->performedBy->name }}</b><br>
                Responsável Técnico
            </div>
        </div>
        <div class="clear"></div>
    </div>

    <div class="footer">
        <div>{{ $identity['certificate_footer'] }}</div>
        <div>Emitido eletronicamente via MetroLab SaaS - Documento assinado digitalmente.</div>
    </div>
</body>
</html>
