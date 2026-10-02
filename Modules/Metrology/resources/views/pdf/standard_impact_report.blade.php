<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laudo de Impacto Metrológico - {{ $standard->name }}</title>
    <style>
        @page {
            margin: 12mm 12mm 18mm 12mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9px;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.35;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid {{ $identity['accent_color'] ?? '#dc2626' }};
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .lab-name {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }
        .lab-sub {
            font-size: 8.5px;
            color: #64748b;
        }
        .report-title-box {
            text-align: center;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 14px;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            color: #991b1b;
            letter-spacing: 0.5px;
        }
        .report-subtitle {
            font-size: 8.5px;
            font-weight: bold;
            color: #7f1d1d;
            margin-top: 3px;
            text-transform: uppercase;
        }
        .meta-strip {
            margin-top: 5px;
            font-size: 8px;
            color: #555;
        }
        
        .section-box {
            margin-bottom: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            overflow: hidden;
        }
        .section-header {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: bold;
            font-size: 9.5px;
            padding: 4px 8px;
            border-bottom: 1px solid #cbd5e1;
            text-transform: uppercase;
        }
        .section-body {
            padding: 6px 8px;
        }
        
        .grid-table {
            width: 100%;
            border-collapse: collapse;
        }
        .grid-table td {
            padding: 2.5px 4px;
            vertical-align: top;
        }
        .prop-label {
            font-weight: bold;
            color: #475569;
            width: 28%;
        }
        .prop-value {
            color: #0f172a;
        }
        
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .stats-table td {
            border: 1px solid #cbd5e1;
            padding: 6px;
            text-align: center;
            background-color: #ffffff;
        }
        .stat-val {
            font-size: 14px;
            font-weight: bold;
            font-family: monospace;
            display: block;
        }
        .stat-lbl {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
            text-align: left;
        }
        .data-table th {
            background-color: #f8fafc;
            color: #334155;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7.5px;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        .badge {
            display: inline-block;
            padding: 1.5px 4px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 7px;
            text-transform: uppercase;
        }
        .badge-critical {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #f87171;
        }
        .badge-moderate {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fbbf24;
        }
        .badge-low {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        
        .signatures-table {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
        }
        .signatures-table td {
            width: 50%;
            text-align: center;
            padding: 0 20px;
            vertical-align: top;
        }
        .sig-line {
            border-top: 1px solid #334155;
            margin-top: 35px;
            padding-top: 4px;
            font-size: 8.5px;
            font-weight: bold;
        }
        .sig-sub {
            font-size: 7.5px;
            color: #64748b;
        }
        
        .footer {
            position: fixed;
            bottom: -14mm;
            left: 0;
            right: 0;
            height: 12mm;
            font-size: 7.5px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 3px;
        }
        .hash-code {
            font-family: monospace;
            font-weight: bold;
            color: #334155;
        }
        .page-num:before {
            content: "Página " counter(page);
        }
    </style>
</head>
<body>

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <div class="lab-name">{{ $identity['lab_name'] ?? 'Laboratório Metrológico' }}</div>
                <div class="lab-sub">{{ $identity['lab_address'] ?? 'Sistema de Gestão Metrológica ISO/IEC 17025' }}</div>
                <div class="lab-sub">Controle de Trabalho Não Conforme & Gestão de Riscos Metrológicos</div>
            </td>
            <td style="width: 30%; text-align: right; vertical-align: middle;">
                <div style="font-size: 8px; font-weight: bold; color: #475569;">CÓDIGO DO LAUDO</div>
                <div style="font-size: 11px; font-weight: bold; font-family: monospace; color: #991b1b;">
                    {{ $reportCode }}
                </div>
                <div class="meta-strip">Emissão: {{ now()->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <!-- Title Banner -->
    <div class="report-title-box">
        <div class="report-title">LAUDO TÉCNICO DE IMPACTO METROLÓGICO E RECALL</div>
        <div class="report-subtitle">Avaliação de Trabalho Não Conforme em Padrão de Referência — ABNT NBR ISO/IEC 17025:2017 §7.10</div>
    </div>

    <!-- Section 1: Compromised Standard Details -->
    <div class="section-box">
        <div class="section-header">1. Identificação do Padrão de Referência Comprometido</div>
        <div class="section-body">
            <table class="grid-table">
                <tr>
                    <td class="prop-label">Padrão / Descrição:</td>
                    <td class="prop-value"><strong>{{ $standard->name }}</strong> ({{ $standard->description ?? 'Sem descrição' }})</td>
                    <td class="prop-label">Código Patrimonial (Tag):</td>
                    <td class="prop-value font-mono"><strong>{{ $standard->stock_number ?? '-' }}</strong></td>
                </tr>
                <tr>
                    <td class="prop-label">Nº de Série:</td>
                    <td class="prop-value font-mono">{{ $standard->serial_number ?? '-' }}</td>
                    <td class="prop-label">Valor Nominal / Faixa:</td>
                    <td class="prop-value">{{ $standard->nominal_value ?? '-' }} {{ $standard->unit ?? '' }}</td>
                </tr>
                <tr>
                    <td class="prop-label">Certificado de Origem:</td>
                    <td class="prop-value font-mono">{{ $standard->certificate_number ?? '-' }}</td>
                    <td class="prop-label">Laboratório Calibrador:</td>
                    <td class="prop-value">{{ $standard->accredited_lab ?? 'RBC / Rastreável' }}</td>
                </tr>
                <tr>
                    <td class="prop-label">Incerteza Declarada (U):</td>
                    <td class="prop-value font-mono">±{{ $standard->uncertainty ?? '-' }} {{ $standard->unit ?? '' }}</td>
                    <td class="prop-label">Data de Validade:</td>
                    <td class="prop-value font-mono">{{ $standard->calibration_due ? $standard->calibration_due->format('d/m/Y') : '-' }}</td>
                </tr>
                <tr>
                    <td class="prop-label" style="color: #991b1b;">Motivo do Comprometimento:</td>
                    <td class="prop-value" colspan="3" style="color: #991b1b; font-weight: bold;">
                        {{ $reason ?? 'Desvio metrológico / Rejeição em calibração periódica externa com impacto retroativo potencial.' }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Section 2: Temporal Exposure Window & Matrix -->
    <div class="section-box">
        <div class="section-header">2. Janela Crítica de Exposição & Matriz de Severidade</div>
        <div class="section-body">
            <table class="grid-table" style="margin-bottom: 6px;">
                <tr>
                    <td class="prop-label">Período de Exposição Avaliado:</td>
                    <td class="prop-value">
                        <strong>{{ $startDate ? \Carbon\Carbon::parse($startDate)->format('d/m/Y') : 'Desde o início dos registros' }}</strong>
                        até
                        <strong>{{ $endDate ? \Carbon\Carbon::parse($endDate)->format('d/m/Y') : now()->format('d/m/Y') }}</strong>
                    </td>
                    <td class="prop-label">Status do Padrão:</td>
                    <td class="prop-value"><span class="badge badge-critical">BLOQUEADO / EM QUARENTENA</span></td>
                </tr>
            </table>

            <table class="stats-table">
                <tr>
                    <td style="width: 20%;">
                        <span class="stat-val" style="color: #0f172a;">{{ $stats['total_calibrations'] }}</span>
                        <span class="stat-lbl">Calibrações Afetadas</span>
                    </td>
                    <td style="width: 20%;">
                        <span class="stat-val" style="color: #0f172a;">{{ $stats['unique_instruments'] }}</span>
                        <span class="stat-lbl">Instrumentos Únicos</span>
                    </td>
                    <td style="width: 20%; background-color: #fef2f2;">
                        <span class="stat-val" style="color: #dc2626;">{{ $stats['critical_count'] }}</span>
                        <span class="stat-lbl" style="color: #991b1b;">Alto Risco (Recall Imediato)</span>
                    </td>
                    <td style="width: 20%; background-color: #fffbeb;">
                        <span class="stat-val" style="color: #d97706;">{{ $stats['moderate_count'] }}</span>
                        <span class="stat-lbl" style="color: #92400e;">Médio Risco (Recalibrar)</span>
                    </td>
                    <td style="width: 20%; background-color: #f0fdf4;">
                        <span class="stat-val" style="color: #16a34a;">{{ $stats['low_count'] }}</span>
                        <span class="stat-lbl" style="color: #166534;">Baixo Risco (Documental)</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Section 3: Detailed Inventory of Impacted Calibrations -->
    <div class="section-box">
        <div class="section-header">3. Inventário de Calibrações e Instrumentos Afetados</div>
        <div class="section-body" style="padding: 0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 12%;">Certificado</th>
                        <th style="width: 9%;">Data</th>
                        <th style="width: 22%;">Instrumento / Tag</th>
                        <th style="width: 14%;">Posto / Local</th>
                        <th style="width: 9%;">MPE</th>
                        <th style="width: 9%;">Incerteza</th>
                        <th style="width: 7%;">TUR</th>
                        <th style="width: 9%;">Risco</th>
                        <th style="width: 9%;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($impactedCalibrations as $row)
                        <tr>
                            <td class="font-mono"><strong>{{ $row['cert_code'] }}</strong></td>
                            <td>{{ $row['date'] }}</td>
                            <td>
                                <strong>{{ $row['instrument_name'] }}</strong>
                                <div style="font-size: 7px; color: #64748b;">Tag: {{ $row['tag'] }} | SN: {{ $row['serial'] }}</div>
                            </td>
                            <td>{{ $row['location'] }}</td>
                            <td class="font-mono">{{ $row['mpe'] ? '±' . $row['mpe'] : '-' }}</td>
                            <td class="font-mono">{{ $row['uncertainty'] ? '±' . $row['uncertainty'] : '-' }}</td>
                            <td class="font-mono">{{ $row['tur'] ? number_format($row['tur'], 1) : '-' }}</td>
                            <td>
                                @if($row['risk'] === 'CRITICAL')
                                    <span class="badge badge-critical">Alto</span>
                                @elseif($row['risk'] === 'MODERATE')
                                    <span class="badge badge-moderate">Médio</span>
                                @else
                                    <span class="badge badge-low">Baixo</span>
                                @endif
                            </td>
                            <td>
                                <strong style="font-size: 7.5px; {{ $row['risk'] === 'CRITICAL' ? 'color: #dc2626;' : ($row['risk'] === 'MODERATE' ? 'color: #d97706;' : 'color: #16a34a;') }}">
                                    {{ $row['action'] }}
                                </strong>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 12px; color: #64748b;">
                                Nenhuma calibração oficial encontrada no período utilizando este padrão.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 4: Corrective Actions & Containment Protocol -->
    <div class="section-box">
        <div class="section-header">4. Protocolo de Contenção e Ações Corretivas (ISO 17025 §7.10.1)</div>
        <div class="section-body">
            <ol style="margin: 0; padding-left: 14px; font-size: 8.5px;">
                <li><strong>Bloqueio de Padrão:</strong> O padrão de referência foi imediatamente retirado de serviço e sinalizado como <em>Quarentena / Bloqueado</em> no banco de dados operacional.</li>
                <li><strong>Recall de Instrumentos Críticos:</strong> Todos os instrumentos classificados como <strong>Alto Risco</strong> devem ser imediatamente recolhidos do chão de fábrica e submetidos a recalibração com padrão íntegro.</li>
                <li><strong>Inspeção Retroativa de Lotes:</strong> A garantia da qualidade deve rastrear as Ordens de Produção (OPs) inspecionadas pelos instrumentos de Alto Risco durante a janela de exposição para avaliar se houve liberação de produtos fora de tolerância.</li>
                <li><strong>Abertura de RNC:</strong> Formalização de Relatório de Não Conformidade (RNC) no sistema da qualidade para condução de análise de causa-raiz e verificação de eficácia.</li>
            </ol>
        </div>
    </div>

    <!-- Section 5: Technical Sign-off -->
    <table class="signatures-table">
        <tr>
            <td>
                <div class="sig-line">{{ $technicalManager ?? 'Responsável Técnico / Metrologista' }}</div>
                <div class="sig-sub">Metrologia & Calibração — Amemiya System</div>
            </td>
            <td>
                <div class="sig-line">{{ $qualityManager ?? 'Gerência da Garantia da Qualidade' }}</div>
                <div class="sig-sub">Homologação de Trabalho Não Conforme (ISO 17025 §7.10.3)</div>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        <table style="width: 100%;">
            <tr>
                <td style="width: 70%;">
                    <div>Documento emitido pelo Sistema de Metrologia Lean Tech. Assinado eletronicamente conforme ABNT NBR ISO/IEC 17025.</div>
                    <div>Fingerprint Forense SHA-256: <span class="hash-code">{{ $documentHash ?? 'PENDING' }}</span></div>
                </td>
                <td style="width: 30%; text-align: right; vertical-align: top;">
                    <div class="page-num"></div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
