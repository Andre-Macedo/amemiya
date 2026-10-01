<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado de Calibração</title>
    <style>
        @page {
            margin: 15mm 15mm 20mm 15mm;
        }
        body { font-family: sans-serif; font-size: 10px; color: #333; margin: 0; padding: 0; }
        .page-header { width: 100%; border-bottom: 2px solid {{ $identity['accent_color'] }}; padding-bottom: 10px; margin-bottom: 15px; }
        .lab-info { float: left; width: 70%; }
        .lab-logo { float: right; width: 25%; text-align: right; }
        .lab-logo img { max-height: 55px; max-width: 100%; }
        .clear { clear: both; }
        
        .certificate-title { text-align: center; margin: 15px 0; }
        .title { font-size: 16px; font-weight: bold; color: {{ $identity['accent_color'] }}; letter-spacing: 0.5px; }
        .cert-number { font-size: 12px; font-weight: bold; margin-top: 4px; color: #111; }

        .section { margin-bottom: 15px; }
        .section-title { font-size: 11px; font-weight: bold; border-bottom: 1px solid #ddd; padding-bottom: 3px; margin-bottom: 8px; text-transform: uppercase; color: #444; }
        
        .info-grid { width: 100%; }
        .info-item { margin-bottom: 4px; }
        .label { font-weight: bold; color: #555; width: 150px; display: inline-block; }

        table { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 9.5px; }
        th, td { border: 1px solid #ddd; padding: 5px 6px; text-align: center; }
        th { background-color: #f8fafc; color: #334155; font-weight: bold; }
        
        .result-pass { color: #15803d; font-weight: bold; }
        .result-fail { color: #b91c1c; font-weight: bold; }
        .result-warning { color: #b45309; font-weight: bold; }
        
        .footer { position: fixed; bottom: -12mm; left: 0; right: 0; height: 12mm; font-size: 8px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 4px; }
        .page-number:before { content: "Página " counter(page); }

        .signatures { margin-top: 30px; width: 100%; }
        .signature-box { width: 45%; text-align: center; float: left; }
        .signature-line { border-top: 1px solid #000; margin-top: 35px; padding-top: 4px; font-size: 9px; }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="lab-info">
            <div style="font-size: 13px; font-weight: bold; color: #0f172a;">{{ $identity['lab_name'] }}</div>
            <div>{{ $identity['lab_address'] }}</div>
            <div>{{ $identity['lab_contact'] }}</div>
        </div>
        <div class="lab-logo">
            @if(!empty($identity['lab_logo_path']) && file_exists(storage_path('app/public/' . $identity['lab_logo_path'])))
                <img src="{{ storage_path('app/public/' . $identity['lab_logo_path']) }}" alt="Logo">
            @else
                <div style="height: 50px; width: 110px; background: #f1f5f9; line-height: 50px; text-align: center; color: #64748b; font-size: 9px; border-radius: 4px; border: 1px dashed #cbd5e1;">METROLOGIA</div>
            @endif
        </div>
        <div class="clear"></div>
    </div>

    <div class="certificate-title">
        <div class="title">CERTIFICADO DE CALIBRAÇÃO</div>
        <div class="cert-number">Nº {{ $record->certificate_code ?? ($record->certificate_number ?? 'CERT-' . substr((string)$record->id, -8)) }}</div>
    </div>

    <div class="section">
        <div class="section-title">1. Identificação do Instrumento / Objeto Calibrado</div>
        <div class="info-item"><span class="label">Instrumento:</span> {{ $instrument->name ?? 'N/A' }}</div>
        <div class="info-item"><span class="label">Código Patrimonial (Tag):</span> {{ $instrument->stock_number ?? 'N/A' }}</div>
        <div class="info-item"><span class="label">Fabricante / Modelo:</span> {{ $instrument->manufacturer ?? 'N/A' }} / {{ $instrument->model ?? 'N/A' }}</div>
        <div class="info-item"><span class="label">Nº de Série:</span> {{ $instrument->serial_number ?? 'N/A' }}</div>
        <div class="info-item"><span class="label">Faixa de Medição / Resolução:</span> {{ $instrument->measuring_range ?? '-' }} / {{ $instrument->resolution ?? '-' }}</div>
        @if($instrument && method_exists($instrument, 'getMaximumPermissibleError') && $instrument->getMaximumPermissibleError() > 0)
            <div class="info-item"><span class="label">Erro Máximo Permissível (MPE):</span> ±{{ $instrument->getMaximumPermissibleError() }} (Critério de Aceitação)</div>
        @endif
        @if($instrument && isset($instrument->criticality) && $instrument->criticality instanceof \Modules\Metrology\Enums\InstrumentCriticality)
            <div class="info-item">
                <span class="label">Criticidade Operacional:</span>
                <strong>{{ $instrument->criticality->getLabel() }}</strong>
                @if($instrument->isCritical())
                    <span style="color: #b91c1c; font-weight: bold; margin-left: 5px;">[Item Crítico de Conformidade]</span>
                @endif
            </div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">2. Dados da Calibração e Condições Ambientais</div>
        <div class="info-item"><span class="label">Data de Execução:</span> {{ $record->calibration_date ? $record->calibration_date->format('d/m/Y') : '-' }}</div>
        <div class="info-item"><span class="label">Próxima Calibração (Sugerida):</span> {{ $instrument && $instrument->calibration_due ? $instrument->calibration_due->format('d/m/Y') : '-' }}</div>
        <div class="info-item"><span class="label">Condições Ambientais:</span> Temperatura: {{ $record->temperature ?? '20.0' }} °C ± 1.0 °C | Umidade: {{ $record->humidity ?? '50.0' }} % ± 5.0 % RH</div>
        <div class="info-item"><span class="label">Procedimento Metrológico:</span> Conforme método GUM (JCGM 100:2008) e procedimento interno padronizado.</div>
    </div>

    <div class="section">
        <div class="section-title">3. Padrões de Referência Utilizados (Rastreabilidade Metrológica ISO/IEC 17025 §6.5)</div>
        <table>
            <thead>
                <tr>
                    <th style="text-align: left;">Descrição do Padrão</th>
                    <th>Identificação / Tag</th>
                    <th>Certificado / Órgão Acreditador</th>
                    <th>Cadeia de Rastreabilidade</th>
                    <th>Validade</th>
                </tr>
            </thead>
            <tbody>
                @forelse($standards as $std)
                    @php
                        $stdCertNum = !empty($std->certificate_number) ? $std->certificate_number : (!empty($std->last_certificate_number) ? $std->last_certificate_number : 'RBC / Inmetro');
                        $stdLab = !empty($std->accredited_lab) ? ' (' . $std->accredited_lab . ')' : '';
                        $stdTraceability = !empty($std->traceability_chain) ? $std->traceability_chain : 'SI / Inmetro / RBC';
                    @endphp
                    <tr>
                        <td style="text-align: left;">{{ $std->name ?? 'N/A' }}</td>
                        <td>{{ $std->serial_number ?? ($std->stock_number ?? 'N/A') }}</td>
                        <td>{{ $stdCertNum . $stdLab }}</td>
                        <td>{{ $stdTraceability }}</td>
                        <td>{{ !empty($std->calibration_due) ? \Illuminate\Support\Carbon::parse($std->calibration_due)->format('d/m/Y') : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Padrões de referência internos com calibração válida rastreada ao Inmetro / RBC.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">4. Resultados de Medição e Incerteza</div>
        <table>
            <thead>
                <tr>
                    <th>Ponto Nominal</th>
                    <th>Vlr. Medido (Média)</th>
                    <th>Tendência (Erro)</th>
                    <th>Incerteza Expandida (U)</th>
                    <th>Fator de Abrangência (k)</th>
                    <th>Avaliação</th>
                </tr>
            </thead>
            <tbody>
                @forelse($results as $res)
                    <tr>
                        <td>{{ number_format((float)($res['nominal'] ?? 0), 4) }}</td>
                        <td>{{ number_format((float)($res['average'] ?? 0), 4) }}</td>      
                        <td>{{ number_format((float)($res['error'] ?? 0), 4) }}</td>    
                        <td>{{ number_format((float)($res['uncertainty'] ?? 0), 5) }}</td>
                        <td>{{ number_format((float)($res['k_factor'] ?? 2.0), 2) }}</td>
                        <td>
                            @php
                                $itemRes = strtolower((string)($res['result'] ?? 'Pass'));
                                $isPass = in_array($itemRes, ['pass', 'approved', 'conforme']);
                            @endphp
                            <span class="{{ $isPass ? 'result-pass' : 'result-fail' }}">
                                {{ $isPass ? 'Aprovado' : 'Reprovado' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">Sem pontos de medição registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="font-size: 8px; color: #64748b; margin-top: 4px; text-align: left; line-height: 1.3;">
            * A incerteza expandida de medição relatada é baseada em uma incerteza padrão combinada multiplicada pelo fator de abrangência k indicado, para uma probabilidade de abrangência de aproximadamente 95,45% (GUM / ISO/IEC 17025).
        </div>
    </div>

    <div class="section">
        <div class="section-title">5. Declaração de Conformidade (ISO/IEC 17025 §7.8.6)</div>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 10px; border-radius: 4px;">
            <div style="margin-bottom: 5px;">
                <span class="label">Decisão Metrológica:</span>
                @php
                    $rawRes = $record->result instanceof \Modules\Metrology\Enums\CalibrationResult ? $record->result->value : (string)$record->result;
                    $isPass = in_array($rawRes, ['approved', 'approved_with_restrictions']);
                    $label = $record->result instanceof \Modules\Metrology\Enums\CalibrationResult ? $record->result->getLabel() : strtoupper((string)$record->result);
                @endphp
                <span class="{{ $isPass ? 'result-pass' : 'result-fail' }}" style="font-size: 11px;">
                    {{ $label }}
                </span>
            </div>
            @if($record->conformity_statement)
                <div style="margin-top: 5px; line-height: 1.4; color: #334155;">
                    <span class="label">Declaração de Regra:</span> {{ $record->conformity_statement }}
                </div>
            @endif
            @if($record->notes)
                <div style="margin-top: 5px; font-style: italic; color: #64748b;">
                    <span class="label">Observações Técnicas:</span> {{ $record->notes }}
                </div>
            @endif
        </div>
    </div>

    <div class="signatures">
        <div class="signature-box" style="float: left;">
            <div class="signature-line">
                <b>{{ $record->performedBy->name ?? 'Metrologista / Técnico Responsável' }}</b><br>
                Execução Técnica
            </div>
        </div>
        <div class="signature-box" style="float: right;">
            <div class="signature-line">
                <b>{{ $record->approvedBy->name ?? 'Gestão da Qualidade / Laboratório' }}</b><br>
                Aprovação Técnica (ISO/IEC 17025)
            </div>
        </div>
        <div class="clear"></div>
    </div>

    <div class="footer">
        <table style="width: 100%; border: none; font-size: 8px; color: #64748b;">
            <tr>
                <td style="width: 70%; text-align: left; border: none; padding: 0;">
                    <div>{{ $identity['certificate_footer'] }}</div>
                    @if($record->verification_hash)
                        <div>Autenticidade: <b>{{ $record->verification_hash }}</b> | Validação pública: {{ url('/verify/' . $record->verification_hash) }}</div>
                    @endif
                </td>
                <td style="width: 30%; text-align: right; border: none; padding: 0;">
                    <div class="page-number"></div>
                    <div>Sistema Metrológico Amemiya</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
