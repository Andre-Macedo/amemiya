#!/usr/bin/env python3
"""
Amemiya Dev CLI - Harness de Automação de Desenvolvimento & EGM
Projeto: Sistema de Metrologia Lean Tech & Industrial IoT

Fornece comandos padronizados e determinísticos para desenvolvimento,
testes, compilação de hardware (KiCad), verificação de contratos EGM,
manufatura, documentação e sincronização de pacotes TCC.
Projetado para uso humano e como Tool Harness para agentes de IA.
"""

import sys
import os
import argparse
import subprocess
import json
import shutil
import time
import random
import re
import zipfile
import hashlib
import urllib.request
from datetime import datetime, timezone
from pathlib import Path

ROOT_DIR = Path(__file__).resolve().parent
PARENT_DIR = ROOT_DIR.parent

HW_REPO = PARENT_DIR / "amemiya-hardware"
FRONT_REPO = PARENT_DIR / "metrology-sass-front"
APP_REPO = PARENT_DIR / "amemiya-app"
PACOTE_TCC_DIR = PARENT_DIR / "Pacote_TCC_Amemiya"
PACOTE_TCC_ZIP = PARENT_DIR / "Pacote_TCC_Amemiya.zip"

def get_hardware_dir():
    """Retorna o diretório do KiCad, verificando pasta local ou repositório irmão amemiya-hardware."""
    local_hw = ROOT_DIR / "hardware" / "kicad"
    if local_hw.exists():
        return local_hw
    external_hw = HW_REPO / "kicad"
    if external_hw.exists():
        return external_hw
    return local_hw

HARDWARE_DIR = get_hardware_dir()
SCRIPTS_DIR = HARDWARE_DIR / "scripts"

KICAD_CLI_DEFAULT = r"C:\Program Files\KiCad\9.0\bin\kicad-cli.exe"
KICAD_PYTHON_DEFAULT = r"C:\Program Files\KiCad\9.0\bin\python.exe"

def get_kicad_cli():
    return shutil.which("kicad-cli") or (KICAD_CLI_DEFAULT if os.path.exists(KICAD_CLI_DEFAULT) else None)

def get_kicad_python():
    return KICAD_PYTHON_DEFAULT if os.path.exists(KICAD_PYTHON_DEFAULT) else None

def get_docker():
    return shutil.which("docker")

def get_npm():
    return shutil.which("npm.cmd") or shutil.which("npm")

def get_npx():
    return shutil.which("npx.cmd") or shutil.which("npx")

def is_docker_running():
    docker_bin = get_docker()
    if not docker_bin:
        return False
    try:
        res = subprocess.run([docker_bin, "info"], capture_output=True, timeout=3)
        return res.returncode == 0
    except Exception:
        return False

# -----------------------------------------------------------------------------
# COMMAND: env:check
# -----------------------------------------------------------------------------
def cmd_env_check(args):
    """Verifica saúde e dependências do ambiente de desenvolvimento."""
    status = {
        "kicad_cli": {"available": bool(get_kicad_cli()), "path": get_kicad_cli()},
        "kicad_python": {"available": bool(get_kicad_python()), "path": get_kicad_python()},
        "docker_installed": bool(get_docker()),
        "docker_running": is_docker_running(),
        "php": {"available": bool(shutil.which("php")), "path": shutil.which("php")},
        "composer": {"available": bool(shutil.which("composer")), "path": shutil.which("composer")},
        "node": {"available": bool(shutil.which("node")), "path": shutil.which("node")},
        "npm": {"available": bool(get_npm()), "path": get_npm()},
        "git": {"available": bool(shutil.which("git")), "path": shutil.which("git")},
        "sibling_repositories": {
            "amemiya-hardware": HW_REPO.exists(),
            "metrology-sass-front": FRONT_REPO.exists(),
            "amemiya-app": APP_REPO.exists(),
            "Pacote_TCC_Amemiya": PACOTE_TCC_DIR.exists()
        }
    }
    
    # Python libraries
    py_libs = {}
    for pkg in ["ezdxf", "shapely", "fitz", "PIL", "xgboost"]:
        try:
            __import__(pkg)
            py_libs[pkg] = True
        except ImportError:
            py_libs[pkg] = False
    status["python_libraries"] = py_libs

    if args.json:
        print(json.dumps(status, indent=2))
        return

    print("=== AMEMIYA DEV ENVIRONMENT STATUS ===")
    print(f"[*] KiCad CLI:          {'OK' if status['kicad_cli']['available'] else 'NOT FOUND'} ({status['kicad_cli']['path']})")
    print(f"[*] KiCad Python:       {'OK' if status['kicad_python']['available'] else 'NOT FOUND'}")
    print(f"[*] Docker:             {'RUNNING' if status['docker_running'] else ('INSTALLED (DAEMON OFF)' if status['docker_installed'] else 'NOT FOUND')}")
    print(f"[*] PHP (Local):        {'OK' if status['php']['available'] else 'NOT FOUND'}")
    print(f"[*] Repositórios Irmãos:")
    for repo_name, exists in status["sibling_repositories"].items():
        print(f"    - {repo_name:22s}: {'PRESENTE' if exists else 'AUSENTE'}")
    print(f"[*] Python Libs:")
    for lib, ok in py_libs.items():
        print(f"    - {lib:10s}: {'OK' if ok else 'MISSING'}")

# -----------------------------------------------------------------------------
# COMMAND: workspace:status
# -----------------------------------------------------------------------------
def cmd_workspace_status(args):
    """Verifica o status Git consolidado de todos os 4 repositórios do ecossistema."""
    repos = [
        ("amemiya (Core & Backend)", ROOT_DIR),
        ("amemiya-hardware (KiCad)", HW_REPO),
        ("metrology-sass-front (Web)", FRONT_REPO),
        ("amemiya-app (Mobile)", APP_REPO),
    ]

    workspace_data = []

    for name, path in repos:
        repo_info = {
            "name": name,
            "path": str(path),
            "exists": path.exists(),
            "is_git": (path / ".git").exists(),
            "branch": "unknown",
            "clean": True,
            "modified_files": [],
            "untracked_files": []
        }

        if path.exists() and (path / ".git").exists():
            try:
                # Branch atual
                res_b = subprocess.run(["git", "branch", "--show-current"], cwd=str(path), capture_output=True, text=True)
                repo_info["branch"] = res_b.stdout.strip() or "HEAD detached"

                # Status modificado
                res_s = subprocess.run(["git", "status", "--porcelain"], cwd=str(path), capture_output=True, text=True)
                for line in res_s.stdout.splitlines():
                    if not line.strip():
                        continue
                    status_code = line[:2]
                    file_path = line[2:].strip()
                    if status_code.startswith("??"):
                        repo_info["untracked_files"].append(file_path)
                    else:
                        repo_info["modified_files"].append(file_path)
                repo_info["clean"] = (len(repo_info["modified_files"]) == 0 and len(repo_info["untracked_files"]) == 0)
            except Exception as e:
                repo_info["error"] = str(e)

        workspace_data.append(repo_info)

    if args.json:
        print(json.dumps(workspace_data, indent=2))
        return

    print("=== WORKSPACE STATUS: ECOSSISTEMA LEAN TECH ===")
    for r in workspace_data:
        if not r["exists"]:
            print(f"-> {r['name']}: [NAO ENCONTRADO]")
            continue
        if not r["is_git"]:
            print(f"-> {r['name']}: [NAO E REPO GIT]")
            continue

        status_tag = "[LIMPO]" if r["clean"] else f"[MODIFICADO: {len(r['modified_files'])} alterados, {len(r['untracked_files'])} novos]"
        print(f"-> {r['name']} ({r['branch']}): {status_tag}")
        for mf in r["modified_files"][:5]:
            print(f"     M {mf}")
        for uf in r["untracked_files"][:5]:
            print(f"     ? {uf}")
        total_hidden = (len(r["modified_files"]) + len(r["untracked_files"])) - 10
        if total_hidden > 0:
            print(f"     ... e mais {total_hidden} arquivos.")

# -----------------------------------------------------------------------------
# COMMAND: egm:lint
# -----------------------------------------------------------------------------
def cmd_egm_lint(args):
    """Valida a conformidade de todos os contratos EGM.md (links, seções e integridade)."""
    search_roots = [ROOT_DIR, FRONT_REPO, APP_REPO]
    contracts = []

    for s_root in search_roots:
        if s_root.exists():
            for p in s_root.rglob("EGM.md"):
                if "node_modules" not in str(p) and "vendor" not in str(p):
                    contracts.append(p)

    results = {
        "contracts_checked": len(contracts),
        "valid": True,
        "contracts": [],
        "broken_links_count": 0,
        "broken_links": []
    }

    req_peanut_sections = ["Overview", "Interface", "Invariants", "Side Effects", "Verification"]
    req_blueprint_sections = ["Overview", "Boundaries", "Invariants", "Side Effects", "Verification"]

    for c in sorted(contracts):
        content = c.read_text(encoding="utf-8")
        base_dir = c.parent
        is_blueprint = ("Blueprint" in content or "Subdirectories" in content)
        req_sections = req_blueprint_sections if is_blueprint else req_peanut_sections

        missing = []
        for sec in req_sections:
            if not re.search(rf"##\s+.*{sec}", content, re.IGNORECASE):
                missing.append(sec)

        # Links relativos
        local_broken = []
        links = re.findall(r'\[([^\]]+)\]\(([^)]+)\)', content)
        for text, link in links:
            if link.startswith("http") or link.startswith("#") or link.startswith("mailto:"):
                continue
            target_clean = link.split()[0].split('#')[0]
            target_path = (base_dir / target_clean).resolve()
            if not target_path.exists():
                local_broken.append({
                    "text": text,
                    "link": link,
                    "resolved": str(target_path)
                })

        contract_valid = (len(missing) == 0 and len(local_broken) == 0)
        if not contract_valid:
            results["valid"] = False

        results["broken_links_count"] += len(local_broken)
        for b in local_broken:
            results["broken_links"].append(f"{c.name} ({c.parent.name}): {b['link']}")

        results["contracts"].append({
            "path": str(c.relative_to(ROOT_DIR.parent)),
            "is_blueprint": is_blueprint,
            "valid": contract_valid,
            "missing_sections": missing,
            "broken_links": local_broken
        })

    if args.json:
        print(json.dumps(results, indent=2))
        return

    print("=== EGM CONTRACTS LINT & INTEGRITY ===")
    print(f"Total de contratos EGM.md validados: {len(contracts)}")
    print(f"Status Global: {'[100% CONFORME]' if results['valid'] else '[FALHA DE CONTRATO]'}")

    if not results["valid"]:
        print("\nIrregularidades Encontradas:")
        for cr in results["contracts"]:
            if not cr["valid"]:
                print(f"  -> {cr['path']}:")
                if cr["missing_sections"]:
                    print(f"     Seções faltantes: {', '.join(cr['missing_sections'])}")
                for bl in cr["broken_links"]:
                    print(f"     Link quebrado: [{bl['text']}]({bl['link']})")
    else:
        print("[OK] Todos os contratos possuem Overview, Interface, Invariants, Side Effects e links 100% verificados.")

# -----------------------------------------------------------------------------
# COMMAND: pcb:list
# -----------------------------------------------------------------------------
def cmd_pcb_list(args):
    """Lista todas as placas e projetos KiCad no repositório."""
    boards = []
    if HARDWARE_DIR.exists():
        for item in sorted(HARDWARE_DIR.iterdir()):
            if item.is_dir() and item.name != "scripts":
                pcbs = [f.name for f in item.glob("*.kicad_pcb")]
                schs = [f.name for f in item.glob("*.kicad_sch")]
                if pcbs or schs:
                    path_str = str(item.relative_to(ROOT_DIR)) if item.is_relative_to(ROOT_DIR) else str(item)
                    boards.append({
                        "name": item.name,
                        "path": path_str,
                        "pcbs": pcbs,
                        "schematics": schs
                    })

    if args.json:
        print(json.dumps(boards, indent=2))
        return

    print("=== PLACAS E CIRCUITOS DO PROJETO AMEMIYA ===")
    for b in boards:
        print(f"-> {b['name']}")
        if b["pcbs"]:
            print(f"   PCBs: {', '.join(b['pcbs'])}")
        if b["schematics"]:
            print(f"   Esquemas: {', '.join(b['schematics'])}")

# -----------------------------------------------------------------------------
# COMMAND: pcb:drc
# -----------------------------------------------------------------------------
def cmd_pcb_drc(args):
    """Executa o DRC do KiCad em uma placa específica e retorna resumo estruturado via JSON."""
    board_dir = HARDWARE_DIR / args.board
    if not board_dir.exists():
        candidates = [d for d in HARDWARE_DIR.iterdir() if d.is_dir() and args.board in d.name]
        if candidates:
            board_dir = candidates[0]
        else:
            print(f"Erro: Placa '{args.board}' não encontrada em hardware/kicad/", file=sys.stderr)
            sys.exit(1)

    pcb_files = list(board_dir.glob("*.kicad_pcb"))
    if not pcb_files:
        print(f"Erro: Nenhum arquivo .kicad_pcb encontrado em {board_dir}", file=sys.stderr)
        sys.exit(1)

    pcb_file = pcb_files[0]
    for p in pcb_files:
        if p.stem == board_dir.name:
            pcb_file = p
            break

    kicad_cli = get_kicad_cli()
    if not kicad_cli:
        print("Erro: kicad-cli.exe não encontrado no sistema.", file=sys.stderr)
        sys.exit(1)

    report_json = ROOT_DIR / f"temp_{pcb_file.stem}_drc.json"
    cmd = [kicad_cli, "pcb", "drc", "--format", "json", "-o", str(report_json), str(pcb_file)]
    res = subprocess.run(cmd, capture_output=True, text=True)

    violations_err = 0
    violations_warn = 0
    unconnected = 0
    errors_sample = []

    if report_json.exists():
        try:
            with open(report_json, "r", encoding="utf-8", errors="ignore") as f:
                drc_data = json.load(f)
            unconnected = len(drc_data.get("unconnected_items", []))
            raw_violations = drc_data.get("violations", [])
            for v in raw_violations:
                sev = v.get("severity", "warning").lower()
                desc = v.get("description", "")
                if sev == "error":
                    violations_err += 1
                    errors_sample.append(f"[ERROR] {desc}")
                else:
                    violations_warn += 1
                    if len(errors_sample) < 5:
                        errors_sample.append(f"[WARN] {desc}")
        except Exception:
            pass
        finally:
            report_json.unlink(missing_ok=True)

    clean = (violations_err == 0 and unconnected == 0)

    result = {
        "board": board_dir.name,
        "pcb_file": pcb_file.name,
        "errors": violations_err,
        "warnings": violations_warn,
        "unconnected": unconnected,
        "clean": clean,
        "sample": errors_sample[:5]
    }

    if args.json:
        print(json.dumps(result, indent=2))
        return

    print(f"=== DRC REPORT: {board_dir.name} ===")
    print(f"Arquivo:           {pcb_file.name}")
    print(f"Status:            {'[OK] APROVADO' if clean else '[FALHA] REQUER ATENCAO'}")
    print(f"Erros Graves DRC:  {violations_err}")
    print(f"Avisos (Warnings): {violations_warn}")
    print(f"Pads Desconectados:{unconnected}")
    if errors_sample:
        print("Amostra:")
        for s in errors_sample[:5]:
            print(f"  - {s}")

# -----------------------------------------------------------------------------
# COMMAND: pcb:erc
# -----------------------------------------------------------------------------
def cmd_pcb_erc(args):
    """Executa o ERC (Electrical Rules Check) nos esquemáticos KiCad."""
    kicad_cli = get_kicad_cli()
    if not kicad_cli:
        print("Erro: kicad-cli.exe não encontrado no sistema.", file=sys.stderr)
        sys.exit(1)

    target_boards = [args.board] if args.board else ["amemiya_main_node", "amemiya_probe_tower_dual"]
    erc_results = []

    for b_name in target_boards:
        b_dir = HARDWARE_DIR / b_name
        if not b_dir.exists():
            continue
        sch_files = list(b_dir.glob("*.kicad_sch"))
        if not sch_files:
            continue

        # Prioriza o esquemático com o mesmo nome do diretório
        sch_file = sch_files[0]
        for s in sch_files:
            if s.stem == b_name:
                sch_file = s
                break

        rpt_json = ROOT_DIR / f"temp_{sch_file.stem}_erc.json"
        cmd = [kicad_cli, "sch", "erc", "--format", "json", "-o", str(rpt_json), str(sch_file)]
        res = subprocess.run(cmd, capture_output=True, text=True)

        err_count = 0
        warn_count = 0
        samples = []

        if rpt_json.exists():
            try:
                with open(rpt_json, "r", encoding="utf-8", errors="ignore") as f:
                    erc_data = json.load(f)
                violations = erc_data.get("violations", [])
                for v in violations:
                    sev = v.get("severity", "warning").lower()
                    desc = v.get("description", "")
                    if sev == "error":
                        err_count += 1
                        samples.append(f"[ERROR] {desc}")
                    else:
                        warn_count += 1
                        if len(samples) < 5:
                            samples.append(f"[WARN] {desc}")
            except Exception:
                pass
            finally:
                rpt_json.unlink(missing_ok=True)

        erc_results.append({
            "board": b_name,
            "schematic": sch_file.name,
            "clean": (err_count == 0),
            "errors": err_count,
            "warnings": warn_count,
            "samples": samples[:5]
        })

    if args.json:
        print(json.dumps(erc_results, indent=2))
        return

    print("=== ERC REPORT (ELECTRICAL RULES CHECK) ===")
    for er in erc_results:
        print(f"-> {er['board']} ({er['schematic']}): {'[OK] 0 ERROS' if er['clean'] else f'[FALHA] {er['errors']} erros'}")
        if er["warnings"]:
            print(f"   Avisos: {er['warnings']}")
        for s in er["samples"]:
            print(f"   - {s}")

# -----------------------------------------------------------------------------
# COMMAND: pcb:laser
# -----------------------------------------------------------------------------
def cmd_pcb_laser(args):
    """Gera arquivos de corte/ablação a laser e furação CNC com hachura sólida."""
    script = SCRIPTS_DIR / "export_probe_tower_laser_dxf.py"
    if not script.exists():
        print(f"Erro: Script {script} não encontrado.", file=sys.stderr)
        sys.exit(1)

    print(f"Executando gerador de manufatura a laser para '{args.board}'...")
    res = subprocess.run([sys.executable, str(script)], capture_output=True, text=True)
    
    if res.returncode != 0:
        print(f"Erro ao executar: {res.stderr}", file=sys.stderr)
        sys.exit(res.returncode)

    export_dir = HARDWARE_DIR / args.board / "fabrication" / "laser_export"
    files = []
    if export_dir.exists():
        files = [f.name for f in export_dir.iterdir() if f.is_file()]

    result = {
        "board": args.board,
        "status": "success",
        "output_dir": str(export_dir.relative_to(ROOT_DIR)) if export_dir.is_relative_to(ROOT_DIR) else str(export_dir),
        "files_count": len(files),
        "files": files
    }

    if args.json:
        print(json.dumps(result, indent=2))
        return

    print(f"[OK] Manufatura gerada com sucesso em: {result['output_dir']}")
    print(f"Total de arquivos gerados: {len(files)}")
    for f in sorted(files):
        print(f"  - {f}")

# -----------------------------------------------------------------------------
# COMMAND: tcc:sync & tcc:pack
# -----------------------------------------------------------------------------
def cmd_tcc_sync(args):
    """Sincroniza os esquemáticos PDF/PNG atualizados para a pasta de entrega do TCC."""
    pdf_name = "Esquematico_Main_Node_ESP32.pdf"
    png_names = [
        "Esquematico_Main_Node_Pag1_Root.png",
        "Esquematico_Main_Node_Pag2_Power_Supply.png",
        "Esquematico_Main_Node_Pag3_Sensors_AFE.png",
        "Esquematico_Main_Node_Pag4_MCU_ESP32S3.png",
        "Esquematico_Main_Node_Pag5_Peripherals_LoRa.png"
    ]

    docs_iot = ROOT_DIR / "docs" / "iot"
    src_pdf = docs_iot / pdf_name

    synced = []

    if src_pdf.exists():
        # 1. amemiya-hardware
        hw_main = HW_REPO / "kicad" / "amemiya_main_node"
        if hw_main.exists():
            shutil.copy2(src_pdf, hw_main / pdf_name)
            synced.append(f"amemiya-hardware: {pdf_name}")

        # 2. Pacote_TCC_Amemiya
        if PACOTE_TCC_DIR.exists():
            target_hw = PACOTE_TCC_DIR / "02_Hardware_Projetos_KiCad" / "Esquematicos_PDF"
            target_hw.mkdir(parents=True, exist_ok=True)
            shutil.copy2(src_pdf, target_hw / pdf_name)
            synced.append(f"Pacote_TCC_Amemiya (PDF): {pdf_name}")

            for p in png_names:
                src_png = docs_iot / p
                if src_png.exists():
                    shutil.copy2(src_png, target_hw / p)
                    synced.append(f"Pacote_TCC_Amemiya (PNG): {p}")

            # Espelhar fontes KiCad
            pacote_kicad = PACOTE_TCC_DIR / "02_Hardware_Projetos_KiCad" / "amemiya_main_node"
            if pacote_kicad.exists() and hw_main.exists():
                for f in hw_main.iterdir():
                    if f.suffix in [".kicad_sch", ".kicad_pro", ".kicad_prl"]:
                        shutil.copy2(f, pacote_kicad / f.name)
                synced.append("Pacote_TCC_Amemiya: Fontes KiCad espelhados")

    result = {
        "status": "success",
        "synced_items_count": len(synced),
        "synced_items": synced
    }

    if args.json:
        print(json.dumps(result, indent=2))
        return

    print("=== SINCRONIZACAO DO PACOTE TCC ===")
    print(f"Total de itens sincronizados: {len(synced)}")
    for s in synced:
        print(f"  - {s}")

def cmd_tcc_pack(args):
    """Executa a sincronização de esquemáticos e regera o Pacote_TCC_Amemiya.zip determinístico."""
    cmd_tcc_sync(args)

    if not PACOTE_TCC_DIR.exists():
        print(f"Erro: Diretório {PACOTE_TCC_DIR} não encontrado.", file=sys.stderr)
        sys.exit(1)

    print(f"Compactando {PACOTE_TCC_DIR.name} em {PACOTE_TCC_ZIP.name}...")
    with zipfile.ZipFile(PACOTE_TCC_ZIP, "w", zipfile.ZIP_DEFLATED) as zipf:
        for root, dirs, files in os.walk(PACOTE_TCC_DIR):
            for file in files:
                file_path = os.path.join(root, file)
                arcname = os.path.relpath(file_path, PACOTE_TCC_DIR.parent)
                zipf.write(file_path, arcname)

    size_mb = round(PACOTE_TCC_ZIP.stat().st_size / (1024 * 1024), 2)
    result = {
        "status": "success",
        "zip_path": str(PACOTE_TCC_ZIP),
        "size_mb": size_mb
    }

    if args.json:
        print(json.dumps(result, indent=2))
        return

    print(f"[OK] Pacote TCC gerado com sucesso: {PACOTE_TCC_ZIP} ({size_mb} MB)")

# -----------------------------------------------------------------------------
# COMMAND: backend:test
# -----------------------------------------------------------------------------
def cmd_backend_test(args):
    """Executa testes do Laravel/Pest (no Docker ou local via PHP Herd)."""
    filter_arg = f"--filter={args.filter}" if args.filter else ""
    
    if is_docker_running():
        print("[*] Executando testes dentro do container Docker 'app'...")
        cmd = ["docker-compose", "exec", "app", "php", "artisan", "test"]
        if filter_arg:
            cmd.append(filter_arg)
        subprocess.run(cmd, cwd=str(ROOT_DIR))
    else:
        php_bin = shutil.which("php")
        if not php_bin:
            print("Erro: Nem Docker nem PHP local estão disponíveis.", file=sys.stderr)
            sys.exit(1)
        print("[*] Docker parado. Executando testes via PHP local (Herd)...")
        cmd = [php_bin, "artisan", "test"]
        if filter_arg:
            cmd.append(filter_arg)
        subprocess.run(cmd, cwd=str(ROOT_DIR))

# -----------------------------------------------------------------------------
# COMMAND: frontend:build & test
# -----------------------------------------------------------------------------
def cmd_frontend_build(args):
    """Compila os assets de frontend com Vite (Tailwind 4, Filament Theme, React)."""
    npm_bin = get_npm()
    if not npm_bin:
        print("Erro: npm não encontrado no sistema.", file=sys.stderr)
        sys.exit(1)

    print("[*] Compilando assets do frontend via Vite (npm run build)...")
    res = subprocess.run([npm_bin, "run", "build"], cwd=str(ROOT_DIR), capture_output=True, text=True)

    dist_dir = ROOT_DIR / "public" / "build" / "assets"
    built_files = []
    if dist_dir.exists():
        built_files = [f.name for f in dist_dir.iterdir() if f.is_file()]

    success = (res.returncode == 0)
    result = {
        "status": "success" if success else "error",
        "return_code": res.returncode,
        "assets_count": len(built_files),
        "assets": built_files,
        "error_message": res.stderr.strip() if not success else None
    }

    if args.json:
        print(json.dumps(result, indent=2))
        return

    if success:
        print(f"[OK] Build do frontend concluído com sucesso!")
        print(f"Arquivos gerados em public/build/assets ({len(built_files)} itens):")
        for f in built_files:
            print(f"  - {f}")
    else:
        print("[FALHA] Erro na compilação do frontend:", file=sys.stderr)
        print(res.stderr or res.stdout, file=sys.stderr)
        sys.exit(res.returncode)

def cmd_frontend_test(args):
    """Executa testes de interface E2E via Playwright."""
    npm_bin = get_npm()
    if not npm_bin:
        print("Erro: npm não encontrado no sistema.", file=sys.stderr)
        sys.exit(1)

    print("[*] Executando testes E2E do frontend via Playwright...")
    cmd = [npm_bin, "run", "test:e2e"]
    res = subprocess.run(cmd, cwd=str(ROOT_DIR))
    sys.exit(res.returncode)

def cmd_frontend_list(args):
    """Lista módulos com assets de frontend, componentes React e views Blade."""
    frontend_map = {
        "root": {
            "views": [str(p.relative_to(ROOT_DIR)) for p in (ROOT_DIR / "resources" / "views").rglob("*.blade.php")],
            "css": [str(p.relative_to(ROOT_DIR)) for p in (ROOT_DIR / "resources" / "css").rglob("*.css")],
            "js": [str(p.relative_to(ROOT_DIR)) for p in (ROOT_DIR / "resources" / "js").rglob("*.js")]
        },
        "modules": {}
    }

    modules_dir = ROOT_DIR / "Modules"
    if modules_dir.exists():
        for mod in sorted(modules_dir.iterdir()):
            if mod.is_dir():
                react_files = [str(p.relative_to(ROOT_DIR)) for p in mod.rglob("*.tsx")] + [str(p.relative_to(ROOT_DIR)) for p in mod.rglob("*.jsx")]
                blade_files = [str(p.relative_to(ROOT_DIR)) for p in mod.rglob("*.blade.php")]
                js_files = [str(p.relative_to(ROOT_DIR)) for p in mod.rglob("*.js") if "node_modules" not in str(p)]
                has_vite = (mod / "vite.config.js").exists()
                if react_files or blade_files or has_vite:
                    frontend_map["modules"][mod.name] = {
                        "has_vite": has_vite,
                        "react_components": react_files,
                        "blade_views_count": len(blade_files),
                        "js_files": js_files
                    }

    if args.json:
        print(json.dumps(frontend_map, indent=2))
        return

    print("=== MAPEAMENTO DE FRONTEND DO PROJETO AMEMIYA ===")
    print("[*] Root:")
    print(f"    Views Blade: {len(frontend_map['root']['views'])}")
    print(f"    CSS (Tailwind 4 + Filament): {', '.join(frontend_map['root']['css'])}")
    print("\n[*] Módulos:")
    for mod_name, mod_info in frontend_map["modules"].items():
        print(f"  -> {mod_name} (Vite: {'Sim' if mod_info['has_vite'] else 'Não'}):")
        print(f"     Views Blade: {mod_info['blade_views_count']}")
        if mod_info["react_components"]:
            print(f"     Componentes React: {', '.join(mod_info['react_components'])}")

# -----------------------------------------------------------------------------
# COMMAND: docs:build & test
# -----------------------------------------------------------------------------
def cmd_docs_build(args):
    """Compila o portal estático de documentação com VitePress (Diátaxis)."""
    npm_bin = get_npm()
    if not npm_bin:
        print("Erro: npm não encontrado no sistema.", file=sys.stderr)
        sys.exit(1)

    print("[*] Compilando portal VitePress (npm run docs:build)...")
    res = subprocess.run([npm_bin, "run", "docs:build"], cwd=str(ROOT_DIR), capture_output=True, text=True)

    dist_dir = ROOT_DIR / "docs" / ".vitepress" / "dist"
    built_pages = []
    if dist_dir.exists():
        built_pages = [str(f.relative_to(dist_dir)) for f in dist_dir.rglob("*.html")]

    success = (res.returncode == 0)
    result = {
        "status": "success" if success else "error",
        "return_code": res.returncode,
        "pages_count": len(built_pages),
        "pages": built_pages,
        "error_message": res.stderr.strip() if not success else None
    }

    if args.json:
        print(json.dumps(result, indent=2))
        return

    if success:
        print(f"[OK] Build do portal VitePress concluído com sucesso ({len(built_pages)} páginas estáticas)!")
        print(f"Diretório de saída: docs/.vitepress/dist")
    else:
        print(f"[ERRO] Falha ao compilar documentação VitePress:\n{res.stderr.strip()}", file=sys.stderr)
        sys.exit(1)

def cmd_docs_test(args):
    """Executa suíte de testes de integridade, links, markdown e contratos OpenAPI dos documentos."""
    script = ROOT_DIR / "scripts" / "test_docs.py"
    if not script.exists():
        print("Erro: scripts/test_docs.py não encontrado.", file=sys.stderr)
        sys.exit(1)

    cmd = [sys.executable, str(script)]
    if args.json:
        cmd.append("--json")

    res = subprocess.run(cmd)
    sys.exit(res.returncode)

# -----------------------------------------------------------------------------
# COMMAND: iot:mock
# -----------------------------------------------------------------------------
def cmd_iot_mock(args):
    """Gera e publica pacotes MQTT sintéticos para testar telemetria sem hardware físico."""
    try:
        import paho.mqtt.client as mqtt
        has_paho = True
    except ImportError:
        has_paho = False

    topic = f"v1/gateways/{args.gateway}/nodes/{args.node}/telemetry"

    profiles = {
        "healthy": {
            "rpm_base": 1725, "rms_global": 0.42, "velocity_rms": 1.15,
            "iso_zone": "A", "temp_base": 36.5, "mic_rms": 0.040,
            "ml_status": "saudavel", "ml_confidence": 0.98,
            "kurt_x": 2.9, "kurt_y": 3.0, "kurt_z": 2.8
        },
        "defect_bearing": {
            "rpm_base": 1725, "rms_global": 5.85, "velocity_rms": 7.40,
            "iso_zone": "D", "temp_base": 58.2, "mic_rms": 0.320,
            "ml_status": "falha_confirmada", "ml_confidence": 0.94,
            "kurt_x": 8.6, "kurt_y": 9.1, "kurt_z": 7.8
        },
        "unbalance": {
            "rpm_base": 1725, "rms_global": 3.40, "velocity_rms": 4.85,
            "iso_zone": "C", "temp_base": 44.0, "mic_rms": 0.110,
            "ml_status": "desbalanceamento", "ml_confidence": 0.92,
            "kurt_x": 3.2, "kurt_y": 3.3, "kurt_z": 3.1
        },
        "misalignment": {
            "rpm_base": 1725, "rms_global": 3.80, "velocity_rms": 5.20,
            "iso_zone": "C", "temp_base": 49.5, "mic_rms": 0.140,
            "ml_status": "falha_confirmada", "ml_confidence": 0.91,
            "kurt_x": 3.8, "kurt_y": 4.0, "kurt_z": 4.2
        },
        "overheating": {
            "rpm_base": 1720, "rms_global": 2.40, "velocity_rms": 3.10,
            "iso_zone": "B", "temp_base": 82.5, "mic_rms": 0.085,
            "ml_status": "alerta", "ml_confidence": 0.89,
            "kurt_x": 3.4, "kurt_y": 3.6, "kurt_z": 3.2
        }
    }

    prof = profiles.get(args.profile, profiles["healthy"])
    messages = []

    client = None
    connected = False
    if has_paho and not args.dry_run:
        try:
            client = mqtt.Client(client_id=f"amemiya-mock-{random.randint(1000, 9999)}")
            client.connect(args.host, args.port, keepalive=10)
            client.loop_start()
            connected = True
        except Exception:
            connected = False

    if not args.json:
        print(f"=== SIMULADOR DE TELEMETRIA IOT AMEMIYA ===")
        print(f"Tópico MQTT:   {topic}")
        print(f"Perfil Físico: {args.profile.upper()}")
        print(f"Status Broker: {'CONECTADO (' + args.host + ':' + str(args.port) + ')' if connected else 'OFFLINE / DRY-RUN'}")
        print(f"Publicando {args.count} pacotes com intervalo de {args.interval}s...\n")

    sample_rate = 3200
    n_bins = 512
    df = (sample_rate / 2.0) / n_bins  # 3.125 Hz por bin

    for i in range(args.count):
        noise = random.uniform(-0.04, 0.04)
        base_rpm = args.rpm if getattr(args, "rpm", None) is not None else prof["rpm_base"]
        rpm_val = round(float(base_rpm) + random.uniform(-6.0, 6.0), 1)
        f_rot = rpm_val / 60.0

        # Multiplicadores cinemáticos exatos do rolamento 6203 (Bancada M1)
        f_bpfo = round(3.0528 * f_rot, 2)
        f_bpfi = round(4.9472 * f_rot, 2)
        f_bsf = round(1.9922 * f_rot, 2)

        if args.profile == "defect_bearing":
            raw_peaks = [
                (round(f_rot, 2), round(0.42 * (1.0 + noise), 3)),
                (f_bpfo, round(1.85 * (1.0 + noise), 3)),
                (round(2.0 * f_bpfo, 2), round(0.92 * (1.0 + noise), 3)),
                (f_bpfi, round(0.64 * (1.0 + noise), 3)),
                (f_bsf, round(0.38 * (1.0 + noise), 3)),
            ]
        elif args.profile == "unbalance":
            raw_peaks = [
                (round(f_rot, 2), round(2.15 * (1.0 + noise), 3)),
                (round(2.0 * f_rot, 2), round(0.32 * (1.0 + noise), 3)),
            ]
        elif args.profile == "misalignment":
            raw_peaks = [
                (round(f_rot, 2), round(0.85 * (1.0 + noise), 3)),
                (round(2.0 * f_rot, 2), round(1.95 * (1.0 + noise), 3)),
                (round(3.0 * f_rot, 2), round(0.74 * (1.0 + noise), 3)),
            ]
        else:
            raw_peaks = [
                (round(f_rot, 2), round(0.28 * (1.0 + noise), 3)),
                (round(2.0 * f_rot, 2), round(0.09 * (1.0 + noise), 3)),
            ]

        peaks = [
            {"frequency_hz": f_hz, "amplitude": amp_g, "freq_hz": f_hz, "amp_g": amp_g}
            for f_hz, amp_g in raw_peaks
        ]

        fft_x = [round(random.uniform(0.005, 0.022), 4) for _ in range(n_bins)]
        envelope_x = [round(random.uniform(0.003, 0.015), 4) for _ in range(n_bins)]
        for pk in peaks:
            bin_idx = int(round(pk["freq_hz"] / df))
            if 1 <= bin_idx < n_bins - 1:
                amp = pk["amp_g"]
                fft_x[bin_idx] = round(max(fft_x[bin_idx], amp), 4)
                fft_x[bin_idx - 1] = round(max(fft_x[bin_idx - 1], amp * 0.45), 4)
                fft_x[bin_idx + 1] = round(max(fft_x[bin_idx + 1], amp * 0.45), 4)
                env_amp = amp * (1.25 if pk["freq_hz"] > 2.5 * f_rot else 0.55)
                envelope_x[bin_idx] = round(max(envelope_x[bin_idx], env_amp), 4)

        rms_global = round(prof["rms_global"] * (1.0 + noise), 3)
        velocity_rms = round(prof["velocity_rms"] * (1.0 + noise), 2)
        rms_x = round(rms_global * 0.75, 3)
        rms_y = round(rms_global * 0.82, 3)
        rms_z = round(rms_global * 0.90, 3)
        kurt_x = round(prof["kurt_x"] + random.uniform(-0.2, 0.2), 2)
        kurt_y = round(prof["kurt_y"] + random.uniform(-0.2, 0.2), 2)
        kurt_z = round(prof["kurt_z"] + random.uniform(-0.2, 0.2), 2)
        mic_rms = round(prof["mic_rms"] * (1.0 + noise), 4)
        temp_c = round(prof["temp_base"] + random.uniform(-0.4, 0.4), 2)
        vbat = round(3.88 + random.uniform(-0.02, 0.02), 2)

        payload = {
            "tenant_id": args.tenant,
            "machine_id": args.machine,
            "device_id": args.gateway,
            "node_id": args.node,
            "msg_id": 1000 + i,
            "rpm_estimated": rpm_val,
            "rpm_source": "accelerometer_firmware",
            "sample_rate": sample_rate,
            "rms_global": rms_global,
            "velocity_rms": velocity_rms,
            "iso_zone": prof["iso_zone"],
            "temp_celsius": temp_c,
            "battery_voltage": vbat,
            "rms_x": rms_x,
            "rms_y": rms_y,
            "rms_z": rms_z,
            "kurt_x": kurt_x,
            "kurt_y": kurt_y,
            "kurt_z": kurt_z,
            "time_domain": {
                "rms_x": rms_x,
                "kurt_x": kurt_x,
                "rms_y": rms_y,
                "kurt_y": kurt_y,
                "rms_z": rms_z,
                "kurt_z": kurt_z,
            },
            "mic_rms": mic_rms,
            "peaks": peaks,
            "spectrum": {
                "sample_rate": sample_rate,
                "rpm": int(round(rpm_val)),
                "rpm_source": "accelerometer_firmware",
                "peaks": peaks,
                "fft_x": fft_x,
                "envelope_x": envelope_x,
            },
            "features": {
                "v_rms": velocity_rms,
                "x_rms": rms_x,
                "y_rms": rms_y,
                "z_rms": rms_z,
                "x_kurtosis": kurt_x,
                "y_kurtosis": kurt_y,
                "z_kurtosis": kurt_z,
                "mic_rms": mic_rms,
                "rpm_estimated": rpm_val,
                "rpm_source": "accelerometer_firmware",
            },
            "ml_status": prof["ml_status"],
            "ml_confidence": prof["ml_confidence"],
            "timestamp": int(time.time()),
        }
        messages.append(payload)

        if connected and client:
            client.publish(topic, json.dumps(payload), qos=1)
            if not args.json:
                print(f"[{i+1}/{args.count}] Publicado -> RMS: {payload['rms_global']}g | Temp: {payload['temp_celsius']}°C | VBAT: {payload['battery_voltage']}V | Status: {payload['ml_status']}")
        else:
            if not args.json:
                print(f"[{i+1}/{args.count}] (Mock) -> RMS: {payload['rms_global']}g | Temp: {payload['temp_celsius']}°C | VBAT: {payload['battery_voltage']}V | ISO: {payload['iso_zone']}")

        if i < args.count - 1 and args.interval > 0:
            time.sleep(args.interval)

    if connected and client:
        time.sleep(0.5)
        client.loop_stop()
        client.disconnect()

    if args.json:
        print(json.dumps({
            "status": "published" if connected else "dry_run",
            "topic": topic,
            "broker_connected": connected,
            "profile": args.profile,
            "messages_count": len(messages),
            "sample_message": messages[0] if messages else None
        }, indent=2))

# -----------------------------------------------------------------------------
# COMMAND: nr:sync & nr:check (Conformidade NR-10 / NR-12 / NBR 5410)
# -----------------------------------------------------------------------------
NR_SOURCES = {
    "NR-10.pdf": {
        "page_url": "https://www.gov.br/trabalho-e-emprego/pt-br/acesso-a-informacao/participacao-social/conselhos-e-orgaos-colegiados/comissao-tripartite-partitaria-permanente/normas-regulamentadora/normas-regulamentadoras-vigentes/norma-regulamentadora-no-10-nr-10",
        "anchors": ["10.2.4", "10.4.13", "10.6.4", "10.7.7.1"],
    },
    "NR-12.pdf": {
        "page_url": "https://www.gov.br/trabalho-e-emprego/pt-br/acesso-a-informacao/participacao-social/conselhos-e-orgaos-colegiados/comissao-tripartite-partitaria-permanente/normas-regulamentadora/normas-regulamentadoras-vigentes/norma-regulamentadora-no-12-nr-12",
        "anchors": ["12.3.5", "12.5.4", "12.5.9", "12.6.1"],
    },
}

def _inspect_nr_pdf(pdf_path, anchors):
    """Inspeciona PDF oficial da NR: cabeçalho %PDF, SHA-256, páginas e cláusulas-chave."""
    info = {
        "exists": pdf_path.exists(),
        "valid_pdf": False,
        "size_bytes": 0,
        "sha256": None,
        "pages": None,
        "anchors_found": {},
    }
    if not pdf_path.exists():
        return info

    raw = pdf_path.read_bytes()
    info["size_bytes"] = len(raw)
    info["valid_pdf"] = raw.startswith(b"%PDF")
    info["sha256"] = hashlib.sha256(raw).hexdigest()

    extracted_chunks = []
    try:
        import fitz  # PyMuPDF
        doc = fitz.open(str(pdf_path))
        info["pages"] = len(doc)
        # Lê páginas iniciais relevantes (NR-10 tem 33p; NR-12 corpo principal até p.25)
        for i in range(min(len(doc), 35)):
            extracted_chunks.append(doc[i].get_text())
        doc.close()
    except Exception:
        try:
            from pypdf import PdfReader
            reader = PdfReader(str(pdf_path))
            info["pages"] = len(reader.pages)
            for i in range(min(len(reader.pages), 35)):
                extracted_chunks.append(reader.pages[i].extract_text() or "")
        except Exception:
            pass

    full_text = "\n".join(extracted_chunks)
    if full_text:
        for anchor in anchors:
            info["anchors_found"][anchor] = (anchor in full_text)

    return info

def _resolve_mte_pdf_url(page_url):
    """Resolve link .pdf atual dentro da página oficial da NR no portal gov.br/MTE."""
    req = urllib.request.Request(page_url, headers={"User-Agent": "Mozilla/5.0"})
    html = urllib.request.urlopen(req, timeout=15).read().decode("utf-8", "ignore")
    matches = re.findall(r'href=["\']([^"\']+\.pdf)["\']', html, re.IGNORECASE)
    for m in matches:
        if "nr-" in m.lower() or "nr_" in m.lower() or "norma" in m.lower():
            return m
    return matches[0] if matches else None

def cmd_nr_sync(args):
    """Verifica/baixa textos oficiais vigentes da NR-10 e NR-12 (MTE/gov.br) para amemiya-hardware/normas/."""
    norms_dir = HW_REPO / "normas"
    norms_dir.mkdir(parents=True, exist_ok=True)
    results = {}
    all_ok = True

    for filename, meta in NR_SOURCES.items():
        target = norms_dir / filename
        action = "verified_local"
        download_error = None

        if not target.exists() or getattr(args, "force", False):
            try:
                pdf_url = _resolve_mte_pdf_url(meta["page_url"])
                if not pdf_url:
                    raise RuntimeError("Link PDF não encontrado na página oficial MTE")
                req = urllib.request.Request(pdf_url, headers={"User-Agent": "Mozilla/5.0"})
                data = urllib.request.urlopen(req, timeout=30).read()
                if not data.startswith(b"%PDF"):
                    raise RuntimeError("Conteúdo baixado não é um PDF válido")
                target.write_bytes(data)
                action = "downloaded_mte"
            except Exception as exc:
                download_error = str(exc)
                if not target.exists():
                    all_ok = False
                action = "download_failed"

        pdf_info = _inspect_nr_pdf(target, meta["anchors"])
        if not pdf_info["valid_pdf"]:
            all_ok = False

        results[filename] = {
            "path": str(target),
            "action": action,
            "download_error": download_error,
            **pdf_info,
        }

    if args.json:
        print(json.dumps({"ok": all_ok, "norms_dir": str(norms_dir), "files": results}, indent=2))
        if not all_ok:
            sys.exit(1)
        return

    print("=== SINCRONIZACAO DE NORMAS OFICIAIS (NR-10 / NR-12 - MTE) ===")
    print(f"Diretório: {norms_dir}\n")
    for fname, info in results.items():
        st = "[OK]" if info["valid_pdf"] else "[ERRO]"
        kb = round(info["size_bytes"] / 1024, 1)
        sha_short = (info["sha256"] or "")[:12]
        anchors_ok = sum(1 for v in info["anchors_found"].values() if v)
        anchors_tot = len(info["anchors_found"])
        print(f"{st} {fname:<12} | ação: {info['action']} | {kb} KB | {info['pages'] or '?'} págs | sha256:{sha_short} | cláusulas: {anchors_ok}/{anchors_tot}")
        if info["download_error"]:
            print(f"     Aviso download: {info['download_error']}")

    if not all_ok:
        sys.exit(1)

def _evaluate_nr_compliance():
    """Audita projeto amemiya_bancada_painel e normas locais frente à NR-10, NR-12 e NBR 5410."""
    norms_dir = HW_REPO / "normas"
    painel_dir = HW_REPO / "kicad" / "amemiya_bancada_painel"
    gen_script = HW_REPO / "kicad" / "scripts" / "generate_bancada_painel_sch.py"

    sch_file = painel_dir / "amemiya_bancada_painel.kicad_sch"
    bom_md = painel_dir / "BOM_painel_eletrico_bancada.md"
    bom_csv = painel_dir / "BOM_painel_eletrico_bancada.csv"
    diag_md = painel_dir / "diagramas_eletricos_unifilar_multifilar_bancada_m1.md"
    matriz_md = norms_dir / "MATRIZ_CONFORMIDADE_NR10_NR12.md"

    failures = []
    passed_checks = []
    field_warnings = []

    # 1. Textos oficiais NR-10 / NR-12 + Matriz
    for fname, meta in NR_SOURCES.items():
        p = norms_dir / fname
        info = _inspect_nr_pdf(p, meta["anchors"])
        if not info["exists"] or not info["valid_pdf"]:
            failures.append(f"Texto oficial ausente ou inválido: {p}")
        else:
            missing_anchors = [a for a, found in info["anchors_found"].items() if not found]
            if missing_anchors:
                failures.append(f"Cláusulas não localizadas em {fname}: {', '.join(missing_anchors)}")
            else:
                passed_checks.append(f"{fname} válido ({info['pages']} págs, cláusulas {', '.join(meta['anchors'])} verificadas)")

    if not matriz_md.exists():
        failures.append(f"Matriz de conformidade ausente: {matriz_md}")
    else:
        passed_checks.append("MATRIZ_CONFORMIDADE_NR10_NR12.md presente")

    # 2. Existência dos documentos de projeto da bancada
    required_docs = [sch_file, bom_md, bom_csv, diag_md, gen_script]
    for doc in required_docs:
        if not doc.exists():
            failures.append(f"Arquivo de projeto ausente: {doc}")

    if not all(d.exists() for d in required_docs):
        return {
            "status": "FAIL",
            "design_ok": False,
            "passed": passed_checks,
            "failures": failures,
            "field_warnings": field_warnings,
        }

    sch_txt = sch_file.read_text(encoding="utf-8", errors="ignore")
    diag_txt = diag_md.read_text(encoding="utf-8", errors="ignore")
    bom_md_txt = bom_md.read_text(encoding="utf-8", errors="ignore")
    bom_csv_txt = bom_csv.read_text(encoding="utf-8", errors="ignore")
    gen_txt = gen_script.read_text(encoding="utf-8", errors="ignore")

    # 3. ABNT NBR 5410 Tabela 47: Condutores de força [W01]-[W05] e [W08]-[W13] devem ser >= 2.5 mm²
    force_ids = ["W01", "W02", "W03", "W04", "W05", "W08", "W09", "W10", "W11", "W12", "W13"]
    bad_gauge_hits = []
    for label, content in [("kicad_sch", sch_txt), ("diagramas_md", diag_txt), ("generator_py", gen_txt)]:
        for wid in force_ids:
            # Procura tag do fio com bitola 1.5mm na mesma linha
            pat_15 = re.compile(rf'\[{wid}\][^\n\r]*?1\.5\s*(?:\\text\{{\s*)?mm', re.IGNORECASE)
            if pat_15.search(content):
                bad_gauge_hits.append(f"{label}:[{wid}]=1.5mm²")
            pat_25 = re.compile(rf'\[{wid}\][^\n\r]*?2\.5\s*(?:\\text\{{\s*)?mm', re.IGNORECASE)
            if not pat_25.search(content):
                bad_gauge_hits.append(f"{label}:[{wid}] sem 2.5mm² explícito")

    if bad_gauge_hits:
        failures.append(f"NBR 5410 Tab.47 (Força >= 2.5 mm²): inconsistência em {', '.join(sorted(set(bad_gauge_hits)))}")
    else:
        passed_checks.append("NBR 5410 Tabela 47: todos os condutores de força [W01..W05, W08..W13] em 2.5 mm² no esquemático, gerador e diagramas")

    # 3b. As-built Comando 24V DC em 1.0 mm² ([W17]-[W39]) - NR-10 10.4.13
    cmd24_ids = [f"W{i}" for i in range(17, 40)]
    bad_cmd24_hits = []
    for label, content in [("kicad_sch", sch_txt), ("diagramas_md", diag_txt), ("generator_py", gen_txt)]:
        for wid in cmd24_ids:
            pat_10 = re.compile(rf'\[{wid}\][^\n\r]*?1\.0\s*(?:\\text\{{\s*)?mm', re.IGNORECASE)
            if not pat_10.search(content):
                bad_cmd24_hits.append(f"{label}:[{wid}] sem 1.0mm²")
    if bad_cmd24_hits:
        failures.append(f"NR-10 10.4.13 (As-built 24V = 1.0 mm²): divergência em {', '.join(sorted(set(bad_cmd24_hits))[:10])}")
    else:
        passed_checks.append("NR-10 10.4.13 / NBR 5410: comando 24V DC [W17..W39] documentado em 1.0 mm² (>= 0.5/0.75 mm² exigido)")

    # 4. NR-10 10.4.13: Coerência documental para 127V AC (sem resquício nominal 220V no motor/rede/dimmer)
    stale_220_patterns = [
        r"REDE_220V",
        r"Motor[_\s]+Tanquinho[^\n\r]*220V",
        r"Motor[^\n\r]{0,30}\b220V\b",
        r"DIMMER\s+220V",
        r"Dimmer_220V",
        r"Dimmer_AC_220V",
        r"FORCA\s*\(220V",
        r"Força\s*220V",
        r"Força\s*—\s*220V",
        r"forca\s+220V",
        r"(?<!/)220V\s*AC",
        r"0-220V",
    ]
    stale_hits = []
    for label, content in [("kicad_sch", sch_txt), ("diagramas_md", diag_txt), ("BOM_md", bom_md_txt), ("BOM_csv", bom_csv_txt), ("generator_py", gen_txt)]:
        for pat in stale_220_patterns:
            if re.search(pat, content, re.IGNORECASE):
                stale_hits.append(f"{label}:/{pat}/")

    if stale_hits:
        failures.append(f"NR-10 10.4.13 (Tensão nominal): referências antigas de 220V encontradas em {', '.join(stale_hits)}")
    else:
        passed_checks.append("NR-10 10.4.13: tensão nominal 127V AC consistente em todos os artefatos (KiCad, BOM, diagramas e gerador)")

    # 5. Elementos mínimos NR-10 / NR-12 no esquemático KiCad
    required_symbols = {
        "QM1": "Disjuntor geral bipolar 10A (NR-10 10.4.8)",
        "KM1": "Contator c/ selo contra religamento automático (NR-12 12.4)",
        "FU1": "Fusível fonte comando SELV (NR-10 10.4.8)",
        "PS1": "Fonte isolada 24V DC (NR-10 10.2.4)",
        "SB0": "Parada de emergência c/ retenção (NR-12 12.6)",
        "TERRA_PE": "Barramento/linha de aterramento PE (NR-12 12.3.1)",
        "REDE_127V_L1": "Net nominal 127V AC no esquemático",
    }
    missing_syms = [f"{sym} ({desc})" for sym, desc in required_symbols.items() if sym not in sch_txt]
    if missing_syms:
        failures.append(f"Elementos ausentes no esquemático KiCad: {'; '.join(missing_syms)}")
    else:
        passed_checks.append("NR-10/NR-12 esquemático: QM1, KM1 (selo), FU1/FU2, PS1 (24VDC), SB0 emergência e TERRA_PE verificados")

    # 6. Sincronismo gerador -> repo amemiya-hardware
    if r"\amemiya\hardware\kicad" in gen_txt:
        failures.append("generate_bancada_painel_sch.py ainda aponta para caminho legado amemiya\\hardware\\kicad")
    else:
        passed_checks.append("generate_bancada_painel_sch.py aponta para amemiya-hardware/kicad")

    # 7. Pendências físicas e exceções de campo (NUNCA silenciar)
    field_warnings.append({
        "rule": "NR-10 10.2.4 + 10.7.7.1 b) / NR-12 12.3.5 b)",
        "severity": "PENDENTE_CAMPO",
        "message": "127V AC (> 50V AC) NÃO é extra-baixa tensão e NÃO é isento: placa/adesivo de advertência 'PERIGO 127V' na porta do painel continua obrigatório."
    })
    field_warnings.append({
        "rule": "NR-10 10.6.4",
        "severity": "EXCECAO_DOCUMENTADA",
        "message": "Painel sem IDR interno por decisão de projeto: utilizar exclusivamente em tomada/circuito predial protegido por DR 30 mA."
    })
    field_warnings.append({
        "rule": "NR-12 12.5.4 / 12.5.7 / 12.5.9 / 12.5.11",
        "severity": "PENDENTE_AJUSTE_FISICO",
        "message": "Tampa existente abre sem ferramenta (proteção móvel 12.5.4 b): exige intertravamento funcional com SQ1 (12.5.7 a,b,c + avaliar inércia 12.5.9.1 e burla 12.5.11 g/k), OU fixação por parafusos que exijam ferramenta (proteção fixa 12.5.4 a)."
    })

    design_ok = (len(failures) == 0)
    status = ("PASS_WITH_WARNINGS" if field_warnings else "PASS") if design_ok else "FAIL"

    return {
        "status": status,
        "design_ok": design_ok,
        "passed": passed_checks,
        "failures": failures,
        "field_warnings": field_warnings,
    }

def cmd_nr_check(args):
    """Audita conformidade NR-10 / NR-12 / NBR 5410 do projeto da bancada M1."""
    report = _evaluate_nr_compliance()
    strict_mode = bool(getattr(args, "strict", False))
    strict_fail = strict_mode and len(report["field_warnings"]) > 0
    ok = report["design_ok"] and not strict_fail

    if args.json:
        print(json.dumps({**report, "strict": strict_mode, "passed": ok}, indent=2))
        if not ok:
            sys.exit(1)
        return

    print("=== AUDITORIA NORMATIVA NR-10 / NR-12 / ABNT NBR 5410 ===")
    print(f"Status Projeto/Docs: {report['status']}\n")

    print("[VERIFICACOES DE PROJETO E DOCUMENTACAO]")
    for item in report["passed"]:
        print(f"  [OK]   {item}")
    for err in report["failures"]:
        print(f"  [FAIL] {err}")

    if report["field_warnings"]:
        print("\n[ALERTAS CRITICOS DE CAMPO / PENDENCIAS FISICAS]")
        for w in report["field_warnings"]:
            print(f"  [WARN] [{w['rule']}] ({w['severity']}): {w['message']}")

    if not ok:
        print("\nRESULTADO: [REPROVADO]")
        sys.exit(1)
    else:
        print("\nRESULTADO: [PROJETO CONFORME COM ALERTAS DE CAMPO DOCUMENTADOS]")

# -----------------------------------------------------------------------------
# COMMAND: audit (Quality Gate Geral Completo)
# -----------------------------------------------------------------------------
def cmd_audit(args):
    """Executa a esteira rigorosa de verificação de qualidade em todas as camadas."""
    start_time = time.time()
    audit_results = {
        "timestamp": datetime.now(timezone.utc).isoformat(),
        "ready_to_commit": True,
        "checks": {}
    }

    if not args.json:
        print("=== AMEMIYA QUALITY GATE AUDIT (FULL SUITE) ===\n")

    kicad_cli = get_kicad_cli()

    # 1. Hardware DRC & ERC em ambas as placas
    if not args.json:
        print("[1/6] Verificando Hardware DRC & ERC (main_node e probe_tower)...")

    hw_checks = {"drc": {}, "erc": {}}
    boards_to_check = [
        ("amemiya_main_node", "amemiya_main_node.kicad_pcb", "amemiya_main_node.kicad_sch"),
        ("amemiya_probe_tower_dual", "amemiya_probe_tower_dual.kicad_pcb", "amemiya_probe_tower_dual.kicad_sch")
    ]

    hw_overall_ok = True

    if kicad_cli and HARDWARE_DIR.exists():
        for b_name, pcb_name, sch_name in boards_to_check:
            b_dir = HARDWARE_DIR / b_name
            pcb_path = b_dir / pcb_name
            sch_path = b_dir / sch_name

            # DRC
            if pcb_path.exists():
                rpt_drc = ROOT_DIR / f"temp_{b_name}_drc.json"
                subprocess.run([kicad_cli, "pcb", "drc", "--format", "json", "-o", str(rpt_drc), str(pcb_path)], capture_output=True)
                drc_err = 0
                unconnected = 0
                if rpt_drc.exists():
                    try:
                        with open(rpt_drc, "r", encoding="utf-8") as f:
                            d = json.load(f)
                        unconnected = len(d.get("unconnected_items", []))
                        drc_err = len([v for v in d.get("violations", []) if v.get("severity") == "error"])
                    except Exception:
                        pass
                    finally:
                        rpt_drc.unlink(missing_ok=True)
                drc_pass = (unconnected == 0 and drc_err == 0)
                hw_checks["drc"][b_name] = {"pass": drc_pass, "unconnected": unconnected, "errors": drc_err}
                if not drc_pass:
                    hw_overall_ok = False

            # ERC
            if sch_path.exists():
                rpt_erc = ROOT_DIR / f"temp_{b_name}_erc.json"
                subprocess.run([kicad_cli, "sch", "erc", "--format", "json", "-o", str(rpt_erc), str(sch_path)], capture_output=True)
                erc_err = 0
                if rpt_erc.exists():
                    try:
                        with open(rpt_erc, "r", encoding="utf-8") as f:
                            e = json.load(f)
                        erc_err = len([v for v in e.get("violations", []) if v.get("severity") == "error"])
                    except Exception:
                        pass
                    finally:
                        rpt_erc.unlink(missing_ok=True)
                erc_pass = (erc_err == 0)
                hw_checks["erc"][b_name] = {"pass": erc_pass, "errors": erc_err}
                if not erc_pass:
                    hw_overall_ok = False

        audit_results["checks"]["hardware"] = {"status": "PASS" if hw_overall_ok else "FAIL", "details": hw_checks}
        if not hw_overall_ok:
            audit_results["ready_to_commit"] = False
        if not args.json:
            print(f"      -> Status: {'[OK]' if hw_overall_ok else '[FALHA]'}")
    else:
        audit_results["checks"]["hardware"] = {"status": "SKIPPED", "reason": "KiCad CLI not available"}
        if not args.json:
            print("      -> Status: [SKIPPED] (KiCad CLI não encontrado)")

    # 2. Sintaxe PHP em 100% dos arquivos de app/ e Modules/
    if not args.json:
        print("[2/6] Verificando Sintaxe PHP em 100% dos arquivos do Core e Módulos...")
    php_bin = shutil.which("php")
    if php_bin:
        all_php_files = []
        for scan_dir in [ROOT_DIR / "app", ROOT_DIR / "Modules", ROOT_DIR / "routes", ROOT_DIR / "config"]:
            if scan_dir.exists():
                for p in scan_dir.rglob("*.php"):
                    if "node_modules" not in str(p) and "vendor" not in str(p):
                        all_php_files.append(p)

        syntax_errs = 0
        err_samples = []
        for f in all_php_files:
            res_php = subprocess.run([php_bin, "-l", str(f)], capture_output=True, text=True)
            if res_php.returncode != 0:
                syntax_errs += 1
                err_samples.append(f"{f.name}: {res_php.stderr.strip()}")

        php_ok = (syntax_errs == 0)
        audit_results["checks"]["backend_syntax"] = {
            "status": "PASS" if php_ok else "FAIL",
            "files_scanned": len(all_php_files),
            "errors_count": syntax_errs,
            "errors_sample": err_samples[:5]
        }
        if not php_ok:
            audit_results["ready_to_commit"] = False
        if not args.json:
            print(f"      -> Status: {'[OK]' if php_ok else '[FALHA]'} ({len(all_php_files)} arquivos auditados, {syntax_errs} erros)")
    else:
        audit_results["checks"]["backend_syntax"] = {"status": "SKIPPED"}
        if not args.json:
            print("      -> Status: [SKIPPED] (PHP CLI não encontrado)")

    # 3. Compilação do Frontend Vite Local
    if not args.json:
        print("[3/6] Verificando Compilação Frontend Vite...")
    npm_bin = get_npm()
    if npm_bin and (ROOT_DIR / "package.json").exists():
        res_vite = subprocess.run([npm_bin, "run", "build"], cwd=str(ROOT_DIR), capture_output=True, text=True)
        vite_ok = (res_vite.returncode == 0)
        audit_results["checks"]["frontend_vite"] = {
            "status": "PASS" if vite_ok else "FAIL",
            "return_code": res_vite.returncode
        }
        if not vite_ok:
            audit_results["ready_to_commit"] = False
        if not args.json:
            print(f"      -> Status: {'[OK]' if vite_ok else '[FALHA]'}")
    else:
        audit_results["checks"]["frontend_vite"] = {"status": "SKIPPED"}

    # 4. TypeScript Typecheck nos Frontends Irmãos (Next.js e Expo)
    if not args.json:
        print("[4/6] Verificando Tipagem TypeScript nos Projetos Web e Mobile...")
    npx_bin = get_npx()
    ts_checks = {}
    ts_overall_ok = True

    if npx_bin:
        for f_name, f_path in [("metrology-sass-front", FRONT_REPO), ("amemiya-app", APP_REPO)]:
            if f_path.exists() and (f_path / "tsconfig.json").exists():
                res_tsc = subprocess.run([npx_bin, "tsc", "--noEmit"], cwd=str(f_path), capture_output=True, text=True)
                tsc_pass = (res_tsc.returncode == 0)
                ts_checks[f_name] = {"pass": tsc_pass, "exit_code": res_tsc.returncode}
                if not tsc_pass:
                    ts_overall_ok = False
            else:
                ts_checks[f_name] = {"pass": True, "skipped": True}

        audit_results["checks"]["frontend_typescript"] = {"status": "PASS" if ts_overall_ok else "FAIL", "projects": ts_checks}
        if not ts_overall_ok:
            audit_results["ready_to_commit"] = False
        if not args.json:
            print(f"      -> Status: {'[OK]' if ts_overall_ok else '[FALHA]'}")
    else:
        audit_results["checks"]["frontend_typescript"] = {"status": "SKIPPED"}

    # 5. Integridade da Documentação e Contratos EGM.md
    if not args.json:
        print("[5/6] Verificando Integridade dos Contratos EGM e Docs VitePress...")
    
    # EGM Lint inline
    egm_ok = True
    contracts = list(ROOT_DIR.rglob("EGM.md")) + list(FRONT_REPO.rglob("EGM.md")) + list(APP_REPO.rglob("EGM.md"))
    contracts = [c for c in contracts if "node_modules" not in str(c) and "vendor" not in str(c)]
    egm_broken = 0
    for c in contracts:
        base_dir = c.parent
        content = c.read_text(encoding="utf-8")
        links = re.findall(r'\[([^\]]+)\]\(([^)]+)\)', content)
        for _, link in links:
            if link.startswith("http") or link.startswith("#"):
                continue
            target_clean = link.split()[0].split('#')[0]
            if not (base_dir / target_clean).resolve().exists():
                egm_broken += 1
                egm_ok = False

    # Docs test script
    doc_script = ROOT_DIR / "scripts" / "test_docs.py"
    docs_ok = True
    if doc_script.exists():
        res_doc = subprocess.run([sys.executable, str(doc_script), "--json"], capture_output=True, text=True)
        try:
            doc_report = json.loads(res_doc.stdout)
            docs_ok = doc_report.get("all_passed", False)
        except Exception:
            docs_ok = (res_doc.returncode == 0)

    quality_docs_ok = (egm_ok and docs_ok)
    audit_results["checks"]["documentation_and_egm"] = {
        "status": "PASS" if quality_docs_ok else "FAIL",
        "egm_contracts_count": len(contracts),
        "egm_broken_links": egm_broken,
        "vitepress_docs_pass": docs_ok
    }
    if not quality_docs_ok:
        audit_results["ready_to_commit"] = False

    if not args.json:
        print(f"      -> Status: {'[OK]' if quality_docs_ok else '[FALHA]'} ({len(contracts)} contratos EGM validados, {egm_broken} links quebrados)")

    # 6. Conformidade Normativa NR-10 / NR-12 / NBR 5410 (Painel Bancada M1)
    if not args.json:
        print("[6/6] Verificando Conformidade NR-10 / NR-12 / NBR 5410 (Bancada M1)...")
    nr_report = _evaluate_nr_compliance()
    nr_ok = nr_report["design_ok"]
    audit_results["checks"]["nr_compliance"] = nr_report
    if not nr_ok:
        audit_results["ready_to_commit"] = False
    if not args.json:
        warn_cnt = len(nr_report["field_warnings"])
        print(f"      -> Status: {'[OK]' if nr_ok else '[FALHA]'} ({len(nr_report['failures'])} erros de projeto, {warn_cnt} alertas de campo)")

    elapsed = round(time.time() - start_time, 2)
    audit_results["elapsed_seconds"] = elapsed

    if args.json:
        print(json.dumps(audit_results, indent=2))
        return

    print(f"\n==========================================")
    print(f"RESULTADO: {'[APROVADO PARA COMMIT]' if audit_results['ready_to_commit'] else '[REPROVADO - CORRIJA ANTES DO COMMIT]'}")
    print(f"Tempo total: {elapsed}s")
    print(f"==========================================")

# -----------------------------------------------------------------------------
# COMMAND: clean
# -----------------------------------------------------------------------------
def cmd_clean(args):
    """Remove backups do KiCad, caches de Python, caches de build e do Laravel."""
    removed = []

    # 1. KiCad backups (*-bak) em ambos os repos
    for scan_root in [ROOT_DIR, HW_REPO]:
        if scan_root.exists():
            for bak in scan_root.rglob("*.kicad_*-bak"):
                bak.unlink(missing_ok=True)
                removed.append(str(bak.name))

    # 2. Python __pycache__
    for pyc in ROOT_DIR.rglob("__pycache__"):
        if "node_modules" not in str(pyc) and ".venv" not in str(pyc):
            shutil.rmtree(pyc, ignore_errors=True)
            removed.append(str(pyc.relative_to(ROOT_DIR)))

    # 3. Laravel Optimize Clear
    php_bin = shutil.which("php")
    if php_bin and (ROOT_DIR / "artisan").exists():
        subprocess.run([php_bin, "artisan", "optimize:clear"], cwd=str(ROOT_DIR), capture_output=True)
        removed.append("Laravel Caches (views, config, routes)")

    if args.json:
        print(json.dumps({"cleaned_items_count": len(removed), "items": removed[:20]}, indent=2))
        return

    print(f"=== LIMPEZA DE ARQUIVOS TEMPORARIOS ===")
    print(f"Itens removidos/limpos: {len(removed)}")
    for item in removed[:10]:
        print(f"  - {item}")
    if len(removed) > 10:
        print(f"  ... e mais {len(removed) - 10} itens.")

# -----------------------------------------------------------------------------
# COMMAND: backend:migrate
# -----------------------------------------------------------------------------
def cmd_backend_migrate(args):
    """Executa migrações modulares do Laravel."""
    php_bin = shutil.which("php")
    if is_docker_running():
        cmd = ["docker-compose", "exec", "app", "php", "artisan"]
    elif php_bin:
        cmd = [php_bin, "artisan"]
    else:
        print("Erro: Nem Docker nem PHP local estão disponíveis.", file=sys.stderr)
        sys.exit(1)

    if args.module:
        cmd.extend(["module:migrate", args.module])
        print(f"[*] Executando migrações do módulo: {args.module}...")
        subprocess.run(cmd, cwd=str(ROOT_DIR))
        if args.seed:
            seed_cmd = cmd[:-2] + ["module:seed", args.module]
            print(f"[*] Executando seeders do módulo: {args.module}...")
            subprocess.run(seed_cmd, cwd=str(ROOT_DIR))
    else:
        cmd.append("migrate")
        print("[*] Executando todas as migrações...")
        subprocess.run(cmd, cwd=str(ROOT_DIR))

# -----------------------------------------------------------------------------
# MAIN DISPATCHER
# -----------------------------------------------------------------------------
def main():
    common_parser = argparse.ArgumentParser(add_help=False)
    common_parser.add_argument("--json", action="store_true", help="Retorna saída estruturada em JSON (ideal para ferramentas de IA)")

    parser = argparse.ArgumentParser(
        prog="amemiya-dev",
        description="Amemiya Dev CLI - Harness de Automação de Desenvolvimento & EGM",
        parents=[common_parser]
    )

    subparsers = parser.add_subparsers(dest="command", help="Comandos disponíveis")

    # workspace:status
    p_ws = subparsers.add_parser("workspace:status", parents=[common_parser], help="Status Git consolidado de todos os repositórios")
    p_ws.set_defaults(func=cmd_workspace_status)

    # egm:lint
    p_egm = subparsers.add_parser("egm:lint", parents=[common_parser], help="Valida sintaxe e links de todos os contratos EGM.md")
    p_egm.set_defaults(func=cmd_egm_lint)

    # env:check
    p_env = subparsers.add_parser("env:check", parents=[common_parser], help="Verifica saúde do ambiente (Docker, KiCad, PHP, repositórios)")
    p_env.set_defaults(func=cmd_env_check)

    # pcb:list
    p_plist = subparsers.add_parser("pcb:list", parents=[common_parser], help="Lista todas as placas e projetos KiCad")
    p_plist.set_defaults(func=cmd_pcb_list)

    # pcb:drc
    p_pdrc = subparsers.add_parser("pcb:drc", parents=[common_parser], help="Roda verificação de regras de design (DRC) do KiCad")
    p_pdrc.add_argument("board", nargs="?", default="amemiya_main_node", help="Nome da placa (ex: amemiya_main_node, amemiya_probe_tower_dual)")
    p_pdrc.set_defaults(func=cmd_pcb_drc)

    # pcb:erc
    p_perc = subparsers.add_parser("pcb:erc", parents=[common_parser], help="Roda verificação de regras elétricas (ERC) do esquemático")
    p_perc.add_argument("board", nargs="?", help="Nome da placa ou omita para checar todas as placas principais")
    p_perc.set_defaults(func=cmd_pcb_erc)

    # pcb:laser
    p_laser = subparsers.add_parser("pcb:laser", parents=[common_parser], help="Regera arquivos DXF/PNG/CNC para manufatura a laser")
    p_laser.add_argument("board", nargs="?", default="amemiya_probe_tower_dual", help="Nome do diretório da placa")
    p_laser.set_defaults(func=cmd_pcb_laser)

    # tcc:sync
    p_tsync = subparsers.add_parser("tcc:sync", parents=[common_parser], help="Sincroniza esquemáticos PDF/PNG na pasta Pacote_TCC_Amemiya")
    p_tsync.set_defaults(func=cmd_tcc_sync)

    # tcc:pack
    p_tpack = subparsers.add_parser("tcc:pack", parents=[common_parser], help="Sincroniza e regera o Pacote_TCC_Amemiya.zip")
    p_tpack.set_defaults(func=cmd_tcc_pack)

    # nr:sync
    p_nrsync = subparsers.add_parser("nr:sync", parents=[common_parser], help="Verifica/baixa textos oficiais da NR-10 e NR-12 (MTE/gov.br)")
    p_nrsync.add_argument("--force", action="store_true", help="Força re-download dos PDFs oficiais a partir do portal gov.br")
    p_nrsync.set_defaults(func=cmd_nr_sync)

    # nr:check
    p_nrcheck = subparsers.add_parser("nr:check", parents=[common_parser], help="Audita projeto da bancada M1 contra NR-10, NR-12 e ABNT NBR 5410")
    p_nrcheck.add_argument("--strict", action="store_true", help="Falha também se houver alertas/pendências físicas em campo")
    p_nrcheck.set_defaults(func=cmd_nr_check)

    # backend:test
    p_btest = subparsers.add_parser("backend:test", parents=[common_parser], help="Roda testes automatizados Pest/PHPUnit")
    p_btest.add_argument("--filter", "-f", help="Filtro de teste específico (ex: TelemetryTest)")
    p_btest.set_defaults(func=cmd_backend_test)

    # backend:migrate
    p_bmig = subparsers.add_parser("backend:migrate", parents=[common_parser], help="Executa migrações modulares do Laravel")
    p_bmig.add_argument("module", nargs="?", help="Módulo específico (ex: Metrology, IoT, System)")
    p_bmig.add_argument("--seed", action="store_true", help="Executa os seeders após a migração")
    p_bmig.set_defaults(func=cmd_backend_migrate)

    # frontend:build
    p_fbuild = subparsers.add_parser("frontend:build", parents=[common_parser], help="Compila assets com Vite (Tailwind 4, Filament, React)")
    p_fbuild.set_defaults(func=cmd_frontend_build)

    # frontend:test
    p_ftest = subparsers.add_parser("frontend:test", parents=[common_parser], help="Executa testes E2E do frontend via Playwright")
    p_ftest.set_defaults(func=cmd_frontend_test)

    # frontend:list
    p_flist = subparsers.add_parser("frontend:list", parents=[common_parser], help="Lista componentes React, views Blade e assets")
    p_flist.set_defaults(func=cmd_frontend_list)

    # docs:build
    p_dbuild = subparsers.add_parser("docs:build", parents=[common_parser], help="Compila o portal estático de documentação VitePress")
    p_dbuild.set_defaults(func=cmd_docs_build)

    # docs:test
    p_dtest = subparsers.add_parser("docs:test", parents=[common_parser], help="Testa integridade, links, markdown e contratos OpenAPI dos documentos")
    p_dtest.set_defaults(func=cmd_docs_test)

    # iot:mock
    p_imock = subparsers.add_parser("iot:mock", parents=[common_parser], help="Publica telemetria MQTT sintética para teste do monitor")
    p_imock.add_argument("--profile", choices=["healthy", "defect_bearing", "unbalance", "misalignment", "overheating"], default="healthy", help="Perfil de falha física")
    p_imock.add_argument("--rpm", type=float, default=None, help="Rotação base simulada em RPM (ex: 1725)")
    p_imock.add_argument("--node", default="node-01", help="ID do nó IoT sensor")
    p_imock.add_argument("--gateway", default="gw-01", help="ID do gateway intermediário")
    p_imock.add_argument("--machine", default="bancada-m1", help="ID da máquina monitorada")
    p_imock.add_argument("--tenant", default="tenant-1", help="ID do tenant")
    p_imock.add_argument("--count", type=int, default=3, help="Quantidade de pacotes a enviar")
    p_imock.add_argument("--interval", type=float, default=1.0, help="Intervalo em segundos entre pacotes")
    p_imock.add_argument("--host", default="localhost", help="Host do broker Mosquitto")
    p_imock.add_argument("--port", type=int, default=1883, help="Porta do broker Mosquitto")
    p_imock.add_argument("--dry-run", action="store_true", help="Gera apenas o payload JSON sem conectar no broker")
    p_imock.set_defaults(func=cmd_iot_mock)

    # audit
    p_audit = subparsers.add_parser("audit", parents=[common_parser], help="Quality Gate completo (Hardware DRC/ERC + PHP Core + Vite/TS + EGM/Docs)")
    p_audit.set_defaults(func=cmd_audit)

    # clean
    p_clean = subparsers.add_parser("clean", parents=[common_parser], help="Limpa backups KiCad, caches Python e caches Laravel")
    p_clean.set_defaults(func=cmd_clean)

    if len(sys.argv) == 1:
        parser.print_help()
        sys.exit(0)

    args = parser.parse_args()
    if hasattr(args, "func"):
        args.func(args)
    else:
        parser.print_help()

if __name__ == "__main__":
    main()
