#!/usr/bin/env python3
"""
Testador de Integridade e Validade de Documentação (Docs Quality Gate)
Projeto: Sistema de Metrologia Lean Tech & Telemetria Industrial Amemiya

Valida:
1. Sintaxe Markdown (H1, blocos de código balanceados, matemática KaTeX).
2. Links internos e imagens locais.
3. Compilação estática do VitePress (Rollup + Dead Links).
4. Contrato OpenAPI 3.1 gerado pelo Dedoc Scramble.
"""

import sys
import os
import re
import json
import shutil
import subprocess
import urllib.request
from pathlib import Path

ROOT_DIR = Path(__file__).resolve().parent.parent
DOCS_DIR = ROOT_DIR / "docs"
PUBLIC_DIR = DOCS_DIR / "public"

ACTIVE_DIRS = ["tutoriais", "guias", "referencia", "explicacoes", "designs", "architecture"]

def check_markdown_files():
    results = {
        "files_checked": 0,
        "errors": []
    }
    
    # Coletar arquivos ativos
    files = [DOCS_DIR / "index.md"]
    for d in ACTIVE_DIRS:
        folder = DOCS_DIR / d
        if folder.exists():
            files.extend(list(folder.glob("*.md")))
            
    results["files_checked"] = len(files)
    
    for f in files:
        if not f.exists():
            continue
        rel = f.relative_to(DOCS_DIR)
        content = f.read_text(encoding="utf-8")
        
        # 1. H1 title (exceto index.md)
        if f.name != "index.md":
            if not re.search(r"^#\s+.+", content, re.MULTILINE):
                results["errors"].append(f"{rel}: Ausência de título principal H1 (# Título)")
                
        # 2. Code fences balance
        fence_lines = [l for l in content.splitlines() if l.strip().startswith("```")]
        if len(fence_lines) % 2 != 0:
            results["errors"].append(f"{rel}: Blocos de código ``` desbalanceados ({len(fence_lines)} linhas de abertura/fechamento)")
            
        # 3. KaTeX display math ($$) balance
        display_math_count = content.count("$$")
        if display_math_count % 2 != 0:
            results["errors"].append(f"{rel}: Blocos de fórmula matemática $$ desbalanceados ({display_math_count} ocorrências)")
            
        # 4. Local images existence
        for match in re.finditer(r'!\[.*?\]\((?!https?:\/\/)(.*?)\)', content):
            src = match.group(1).split()[0].split('#')[0]
            if not src:
                continue
            if src.startswith("/"):
                target = PUBLIC_DIR / src.lstrip("/")
            else:
                target = (f.parent / src).resolve()
            if not target.exists():
                results["errors"].append(f"{rel}: Imagem local inexistente: {src}")

        # 5. Internal links resolution
        for match in re.finditer(r'(?<!!)\[.*?\]\((?!https?:\/\/|#|mailto:)(.*?)\)', content):
            href = match.group(1).split()[0].split('#')[0]
            if not href or href.startswith("file:"):
                if href.startswith("file:"):
                    results["errors"].append(f"{rel}: Link com protocolo file:// não permitido em documentação web: {href}")
                continue
            if href.startswith("/"):
                cand_md = DOCS_DIR / (href.lstrip("/") + ".md")
                cand_dir = DOCS_DIR / href.lstrip("/") / "index.md"
                cand_raw = DOCS_DIR / href.lstrip("/")
                if not (cand_md.exists() or cand_dir.exists() or cand_raw.exists()):
                    results["errors"].append(f"{rel}: Link interno quebrado: {href}")
            else:
                cand = (f.parent / href).resolve()
                cand_md = (f.parent / (href + ".md")).resolve()
                # Links para repositórios irmãos (ex: ../../../amemiya-app/...) só são validados
                # quando o repositório irmão está presente localmente (ausente em CI isolado).
                if not cand.is_relative_to(ROOT_DIR):
                    if cand.is_relative_to(ROOT_DIR.parent):
                        sibling_parts = cand.relative_to(ROOT_DIR.parent).parts
                        if sibling_parts and not (ROOT_DIR.parent / sibling_parts[0]).exists():
                            continue
                if not (cand.exists() or cand_md.exists()):
                    results["errors"].append(f"{rel}: Link relativo quebrado: {href}")

    # 6. Garante que todo ADR em docs/explicacoes/ esteja registrado na sidebar do VitePress
    config_file = DOCS_DIR / ".vitepress" / "config.mts"
    if config_file.exists():
        config_text = config_file.read_text(encoding="utf-8")
        for adr in sorted((DOCS_DIR / "explicacoes").glob("ADR-*.md")):
            expected_link = f"/explicacoes/{adr.stem}"
            if expected_link not in config_text:
                results["errors"].append(
                    f"explicacoes/{adr.name}: ADR órfão não registrado na sidebar em .vitepress/config.mts ({expected_link})"
                )

    return results

def check_vitepress_build():
    npm_bin = shutil.which("npm.cmd") or shutil.which("npm")
    if not npm_bin:
        return {"status": "SKIPPED", "reason": "npm não encontrado"}
        
    res = subprocess.run([npm_bin, "run", "docs:build"], cwd=str(ROOT_DIR), capture_output=True, text=True)
    success = (res.returncode == 0)
    
    dist_dir = DOCS_DIR / ".vitepress" / "dist"
    pages_count = len(list(dist_dir.rglob("*.html"))) if dist_dir.exists() else 0
    
    return {
        "status": "PASS" if success else "FAIL",
        "return_code": res.returncode,
        "pages_generated": pages_count,
        "error": res.stderr.strip() if not success else None
    }

def check_kicad_pinout_sync():
    """Valida a correspondência entre os GPIOs ligados no KiCad e docs/referencia/pinagem-hardware.md."""
    from collections import defaultdict, deque

    sch_path = ROOT_DIR.parent / "amemiya-hardware" / "kicad" / "amemiya_main_node" / "03_MCU_ESP32S3.kicad_sch"
    doc_path = DOCS_DIR / "referencia" / "pinagem-hardware.md"
    if not sch_path.exists() or not doc_path.exists():
        return {"status": "SKIPPED", "reason": "Esquemático KiCad (amemiya-hardware) ausente neste ambiente"}

    sch = sch_path.read_text(encoding="utf-8")
    m_sym = re.search(
        r'\(symbol\s+\(lib_id\s+"amemiya_carrier:ESP32-S3-DevKitC-1"\)\s+\(at\s+([0-9.\-]+)\s+([0-9.\-]+)',
        sch,
    )
    if not m_sym:
        return {"status": "FAIL", "issues": ["Símbolo ESP32-S3-DevKitC-1 não encontrado em 03_MCU_ESP32S3.kicad_sch"]}

    sx, sy = float(m_sym.group(1)), float(m_sym.group(2))

    def extract_sexpr_blocks(text, prefix):
        blocks = []
        idx = 0
        while True:
            pos = text.find(prefix, idx)
            if pos == -1:
                break
            depth = 0
            in_str = False
            esc = False
            end = pos
            while end < len(text):
                ch = text[end]
                if esc:
                    esc = False
                elif ch == "\\" and in_str:
                    esc = True
                elif ch == '"':
                    in_str = not in_str
                elif not in_str:
                    if ch == "(":
                        depth += 1
                    elif ch == ")":
                        depth -= 1
                        if depth == 0:
                            blocks.append(text[pos : end + 1])
                            break
                end += 1
            idx = end + 1
        return blocks

    lib_blocks = extract_sexpr_blocks(sch, '(symbol "amemiya_carrier:ESP32-S3-DevKitC-1"')
    if not lib_blocks:
        return {"status": "FAIL", "issues": ["Definição de símbolo ESP32-S3-DevKitC-1 ausente em lib_symbols"]}

    pin_coords = {}
    for pin_blk in extract_sexpr_blocks(lib_blocks[0], "(pin "):
        m_at = re.search(r'\(at\s+([0-9.\-]+)\s+([0-9.\-]+)', pin_blk)
        m_name = re.search(r'\(name\s+"([^"]+)"', pin_blk)
        if m_at and m_name:
            m_gpio = re.search(r'(GPIO\d+)', m_name.group(1))
            if m_gpio:
                px, py = float(m_at.group(1)), float(m_at.group(2))
                pin_coords[m_gpio.group(1)] = (round(sx + px, 2), round(sy - py, 2))

    adj = defaultdict(set)
    for wire_blk in extract_sexpr_blocks(sch, "(wire "):
        pts = [(round(float(x), 2), round(float(y), 2)) for x, y in re.findall(r'\(xy\s+([0-9.\-]+)\s+([0-9.\-]+)\)', wire_blk)]
        for a, b in zip(pts, pts[1:]):
            adj[a].add(b)
            adj[b].add(a)

    labels = {}
    for lbl_blk in extract_sexpr_blocks(sch, "(hierarchical_label "):
        m_lbl = re.search(r'\(hierarchical_label\s+"([^"]+)"', lbl_blk)
        m_at = re.search(r'\(at\s+([0-9.\-]+)\s+([0-9.\-]+)', lbl_blk)
        if m_lbl and m_at:
            labels[(round(float(m_at.group(1)), 2), round(float(m_at.group(2)), 2))] = m_lbl.group(1)

    no_connects = {
        (round(float(x), 2), round(float(y), 2))
        for x, y in re.findall(r'\(no_connect\s+\(at\s+([0-9.\-]+)\s+([0-9.\-]+)\)', sch)
    }

    kicad_signals = {}
    kicad_nc = set()
    for gpio, pt in pin_coords.items():
        q = deque([pt])
        seen = {pt}
        found = []
        while q:
            cur = q.popleft()
            if cur in labels:
                found.append(labels[cur])
            for nxt in adj[cur]:
                if nxt not in seen:
                    seen.add(nxt)
                    q.append(nxt)
        if found:
            kicad_signals[gpio] = found[0]
        elif pt in no_connects:
            kicad_nc.add(gpio)

    doc_text = doc_path.read_text(encoding="utf-8")
    sec1 = doc_text.split("## 1.")[1].split("## 2.")[0] if "## 1." in doc_text and "## 2." in doc_text else doc_text
    doc_rows = {}
    for line in sec1.splitlines():
        if not line.strip().startswith("|"):
            continue
        m_gpio = re.search(r'\|\s*`?(GPIO\d+)`?\s*\|', line)
        if m_gpio:
            doc_rows[m_gpio.group(1)] = line

    issues = []
    for gpio, sig in sorted(kicad_signals.items(), key=lambda kv: int(kv[0][4:])):
        row = doc_rows.get(gpio)
        if not row:
            issues.append(f"{gpio} ({sig}) conectado no KiCad, mas ausente na tabela de pinagem-hardware.md")
        elif f"`{sig}`" not in row:
            issues.append(f"{gpio}: KiCad usa '{sig}', mas pinagem-hardware.md diverge -> {row.strip()}")

    for gpio, row in sorted(doc_rows.items(), key=lambda kv: int(kv[0][4:])):
        if gpio in kicad_nc and "NC" not in row and "Livre" not in row and "Reserva" not in row:
            issues.append(f"{gpio}: desconectado (NC) no KiCad, mas documentado como ativo em pinagem-hardware.md")

    return {
        "status": "PASS" if not issues else "FAIL",
        "kicad_gpios_checked": len(kicad_signals),
        "issues": issues,
    }


def check_openapi_contract():
    # Verifica via HTTP se o container estiver ativo (testa IPv4 127.0.0.1 e localhost)
    urls = ["http://127.0.0.1:8000/docs/api.json", "http://localhost:8000/docs/api.json"]
    schema = None
    
    for api_url in urls:
        try:
            req = urllib.request.Request(api_url, headers={"User-Agent": "LeanTech-DocValidator/1.0"})
            with urllib.request.urlopen(req, timeout=8) as response:
                if response.status == 200:
                    schema = json.loads(response.read().decode("utf-8"))
                    break
        except Exception:
            continue
        
    if not schema:
        # Tenta verificar se o cache do Scramble existe no storage
        return {
            "status": "SKIPPED",
            "reason": "Serviço HTTP localhost:8000 inacessível no momento do teste"
        }
        
    issues = []
    if "openapi" not in schema:
        issues.append("Propriedade 'openapi' ausente no contrato")
    if "info" not in schema or "title" not in schema["info"]:
        issues.append("Propriedade 'info.title' ausente")
    if "paths" not in schema or len(schema["paths"]) == 0:
        issues.append("Nenhuma rota documentada em 'paths'")
        
    sec_schemes = schema.get("components", {}).get("securitySchemes", {})
    if "apiKey" not in sec_schemes:
        issues.append("Esquema de segurança 'apiKey' (X-Tenant) ausente")
    if "http" not in sec_schemes:
        issues.append("Esquema de segurança 'http' (Bearer Sanctum) ausente")
        
    paths_count = len(schema.get("paths", {}))
    return {
        "status": "PASS" if not issues else "FAIL",
        "openapi_version": schema.get("openapi"),
        "title": schema.get("info", {}).get("title"),
        "paths_count": paths_count,
        "issues": issues
    }

def main():
    import argparse
    parser = argparse.ArgumentParser(description="Validador de Integridade da Documentação Técnica")
    parser.add_argument("--json", action="store_true", help="Saída em formato JSON estruturado")
    parser.add_argument("--skip-build", action="store_true", help="Pular compilação do VitePress")
    args = parser.parse_args()
    
    md_res = check_markdown_files()
    kicad_res = check_kicad_pinout_sync()
    build_res = check_vitepress_build() if not args.skip_build else {"status": "SKIPPED"}
    openapi_res = check_openapi_contract()
    
    all_ok = (
        (len(md_res["errors"]) == 0)
        and (kicad_res["status"] in ["PASS", "SKIPPED"])
        and (build_res["status"] in ["PASS", "SKIPPED"])
        and (openapi_res["status"] in ["PASS", "SKIPPED"])
    )
    
    report = {
        "all_passed": all_ok,
        "markdown_integrity": {
            "status": "PASS" if not md_res["errors"] else "FAIL",
            "files_checked": md_res["files_checked"],
            "errors_count": len(md_res["errors"]),
            "errors": md_res["errors"]
        },
        "kicad_pinout_sync": kicad_res,
        "vitepress_build": build_res,
        "openapi_contract": openapi_res
    }
    
    if args.json:
        print(json.dumps(report, indent=2))
        sys.exit(0 if all_ok else 1)
        
    print("==================================================")
    print("      LEAN TECH - AUDITORIA DE DOCUMENTAÇÃO       ")
    print("==================================================")
    print(f"[*] 1. Integridade Markdown ({md_res['files_checked']} arquivos):")
    if not md_res["errors"]:
        print("    -> [OK] Nenhum link quebrado, H1 ausente ou cerca desbalanceada.")
    else:
        print(f"    -> [FALHA] {len(md_res['errors'])} inconsistência(s) encontrada(s):")
        for err in md_res["errors"]:
            print(f"       - {err}")
            
    print(f"[*] 2. Sincronismo KiCad <-> Pinagem Oficial:")
    if kicad_res["status"] == "PASS":
        print(f"    -> [OK] {kicad_res['kicad_gpios_checked']} GPIOs do esquemático batem 100% com pinagem-hardware.md.")
    elif kicad_res["status"] == "SKIPPED":
        print(f"    -> [SKIPPED] {kicad_res['reason']}")
    else:
        print(f"    -> [FALHA] Divergência(s) entre KiCad e pinagem-hardware.md:")
        for iss in kicad_res["issues"]:
            print(f"       - {iss}")

    print(f"[*] 3. Compilação VitePress:")
    if build_res["status"] == "PASS":
        print(f"    -> [OK] Build estático bem-sucedido ({build_res['pages_generated']} páginas geradas).")
    elif build_res["status"] == "SKIPPED":
        print("    -> [SKIPPED] Compilação pulada.")
    else:
        print(f"    -> [FALHA] Erro no build: {build_res.get('error')}")
        
    print(f"[*] 4. Contrato OpenAPI 3.1 (Scramble):")
    if openapi_res["status"] == "PASS":
        print(f"    -> [OK] OpenAPI {openapi_res['openapi_version']} válido: {openapi_res['title']} ({openapi_res['paths_count']} rotas).")
        print("       Esquemas validados: X-Tenant e Bearer Sanctum.")
    elif openapi_res["status"] == "SKIPPED":
        print(f"    -> [SKIPPED] {openapi_res['reason']}")
    else:
        print(f"    -> [FALHA] Inconsistências no OpenAPI: {', '.join(openapi_res['issues'])}")
        
    print("==================================================")
    print(f"RESULTADO FINAL: {'[DOCUMENTAÇÃO APROVADA]' if all_ok else '[REPROVADA - REQUER CORREÇÃO]'}")
    print("==================================================")
    
    sys.exit(0 if all_ok else 1)

if __name__ == "__main__":
    main()
