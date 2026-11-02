import os
import re
import subprocess
import markdown

MD_PATH = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\docs\Arquitetura_Software_e_Banco_de_Dados_IoT.md"
PDF_PATH_1 = r"C:\Users\andrl\OneDrive - SENAC - SP\Senac\Eng. Computação\SENAC 5º Semestre\Projeto\Arquitetura_Software_e_Banco_de_Dados_IoT.pdf"
PDF_PATH_2 = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\docs\Arquitetura_Software_e_Banco_de_Dados_IoT.pdf"
HTML_TEMP = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\docs\temp_architecture.html"
LOGO_PATH = r"C:\Users\andrl\OneDrive - SENAC - SP\Senac\Eng. Computação\SENAC 5º Semestre\Projeto\senac_logo.png"

with open(MD_PATH, "r", encoding="utf-8") as f:
    md_content = f.read()

# Preprocess mermaid code blocks so that markdown turns them into <div class="mermaid">...</div>
def replace_mermaid_blocks(match):
    code = match.group(1).strip()
    return f"\n<div class=\"mermaid\">\n{code}\n</div>\n"

# Replace ```mermaid ... ```
processed_md = re.sub(r"```mermaid(.*?)```", replace_mermaid_blocks, md_content, flags=re.DOTALL)

# Convert Markdown to HTML
html_body = markdown.markdown(processed_md, extensions=['tables', 'fenced_code', 'toc'])

# Build complete HTML with modern styling, print layout, and Mermaid.js
html_template = f"""<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Arquitetura de Software e Modelo de Banco de Dados — IoT</title>
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {{
            mermaid.initialize({{
                startOnLoad: true,
                theme: 'neutral',
                fontFamily: 'Segoe UI, system-ui, sans-serif',
                securityLevel: 'loose'
            }});
        }});
    </script>
    <style>
        @page {{
            size: A4 portrait;
            margin: 18mm 15mm 20mm 15mm;
            @bottom-right {{
                content: counter(page);
            }}
        }}

        body {{
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif;
            font-size: 10.5pt;
            line-height: 1.55;
            color: #1e293b;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }}

        .header-cover {{
            border-bottom: 3px solid #004a8f;
            padding-bottom: 12px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }}

        .header-title-area h1 {{
            font-size: 20pt;
            color: #004a8f;
            margin: 0 0 6px 0;
            font-weight: 700;
            letter-spacing: -0.5px;
        }}

        .header-meta {{
            font-size: 9.5pt;
            color: #64748b;
            line-height: 1.4;
        }}

        .header-logo {{
            max-height: 60px;
            object-fit: contain;
        }}

        h2 {{
            font-size: 14pt;
            color: #0f2240;
            border-bottom: 1.5px solid #e2e8f0;
            padding-bottom: 6px;
            margin-top: 28px;
            margin-bottom: 14px;
            page-break-after: avoid;
        }}

        h3 {{
            font-size: 11.5pt;
            color: #004a8f;
            margin-top: 20px;
            margin-bottom: 8px;
            page-break-after: avoid;
        }}

        p, li {{
            font-size: 10pt;
            color: #334155;
            text-align: justify;
        }}

        ul, ol {{
            margin-top: 6px;
            margin-bottom: 10px;
            padding-left: 22px;
        }}

        li {{
            margin-bottom: 4px;
        }}

        strong {{
            color: #0f2240;
        }}

        table {{
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            font-size: 9pt;
            page-break-inside: avoid;
        }}

        th {{
            background-color: #0f2240;
            color: #ffffff;
            font-weight: 600;
            text-align: left;
            padding: 8px 10px;
            border: 1px solid #0f2240;
        }}

        td {{
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }}

        tr:nth-child(even) td {{
            background-color: #f8fafc;
        }}

        .mermaid {{
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px;
            margin: 18px 0;
            display: flex;
            justify-content: center;
            page-break-inside: avoid;
        }}

        code {{
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 8.5pt;
            background-color: #f1f5f9;
            padding: 2px 5px;
            border-radius: 4px;
            color: #0369a1;
        }}

        pre code {{
            display: block;
            padding: 10px;
            background-color: #0f172a;
            color: #f8fafc;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 8.5pt;
            line-height: 1.4;
        }}

        blockquote {{
            border-left: 4px solid #f37021;
            background-color: #fffaf0;
            margin: 14px 0;
            padding: 10px 14px;
            color: #9a3412;
            font-size: 9.5pt;
            page-break-inside: avoid;
        }}

        hr {{
            border: 0;
            border-top: 1px solid #e2e8f0;
            margin: 24px 0;
        }}

        .footer-note {{
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #cbd5e1;
            font-size: 8pt;
            color: #94a3b8;
            text-align: center;
        }}
    </style>
</head>
<body>
    <div class="header-cover">
        <div class="header-title-area">
            <h1>Arquitetura de Software & Modelo de Banco de Dados</h1>
            <div class="header-meta">
                <strong>Projeto Integrador:</strong> Engenharia de Computação — 5º Semestre (SENAC SP)<br>
                <strong>Empresa Parceira:</strong> Metalúrgica Amemiya &nbsp;|&nbsp; <strong>Subsistema:</strong> Monitoramento Preditivo e IoT 4.0 &nbsp;|&nbsp; <strong>Data:</strong> Outubro / 2026
            </div>
        </div>
        <div>
            <img class="header-logo" src="file:///{LOGO_PATH.replace(os.sep, '/')}" alt="SENAC Logo">
        </div>
    </div>

    {html_body}

    <div class="footer-note">
        Metalúrgica Amemiya & SENAC São Paulo • Plataforma IoT de Metrologia e Manutenção Preditiva • Documento Técnico de Engenharia
    </div>
</body>
</html>
"""

with open(HTML_TEMP, "w", encoding="utf-8") as f:
    f.write(html_template)

print(f"HTML temporário gerado em: {HTML_TEMP}")

# Run Microsoft Edge headless to print to PDF
edge_path = r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
cmd = [
    edge_path,
    "--headless",
    "--disable-gpu",
    "--no-pdf-header-footer",
    "--run-all-compositor-stages-before-draw",
    "--virtual-time-budget=6000",
    f"--print-to-pdf={PDF_PATH_2}",
    HTML_TEMP
]

print("Compilando PDF de alta definição com o Microsoft Edge...")
subprocess.run(cmd, check=True)

# Copy to SENAC OneDrive directory as well
import shutil
shutil.copy2(PDF_PATH_2, PDF_PATH_1)

print("PDF GERADO COM SUCESSO EM:")
print(f" -> {PDF_PATH_2} ({os.path.getsize(PDF_PATH_2)} bytes)")
print(f" -> {PDF_PATH_1} ({os.path.getsize(PDF_PATH_1)} bytes)")
