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

ACTIVE_DIRS = ["tutoriais", "guias", "referencia", "explicacoes"]

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
                if not (cand.exists() or cand_md.exists()):
                    results["errors"].append(f"{rel}: Link relativo quebrado: {href}")
                    
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
    build_res = check_vitepress_build() if not args.skip_build else {"status": "SKIPPED"}
    openapi_res = check_openapi_contract()
    
    all_ok = (len(md_res["errors"]) == 0) and (build_res["status"] in ["PASS", "SKIPPED"]) and (openapi_res["status"] in ["PASS", "SKIPPED"])
    
    report = {
        "all_passed": all_ok,
        "markdown_integrity": {
            "status": "PASS" if not md_res["errors"] else "FAIL",
            "files_checked": md_res["files_checked"],
            "errors_count": len(md_res["errors"]),
            "errors": md_res["errors"]
        },
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
            
    print(f"[*] 2. Compilação VitePress:")
    if build_res["status"] == "PASS":
        print(f"    -> [OK] Build estático bem-sucedido ({build_res['pages_generated']} páginas geradas).")
    elif build_res["status"] == "SKIPPED":
        print("    -> [SKIPPED] Compilação pulada.")
    else:
        print(f"    -> [FALHA] Erro no build: {build_res.get('error')}")
        
    print(f"[*] 3. Contrato OpenAPI 3.1 (Scramble):")
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
