#!/usr/bin/env python3
"""
Amemiya Dev CLI - Harness de Automação de Desenvolvimento
Projeto: Sistema de Metrologia Lean Tech & Industrial IoT

Fornece comandos padronizados e determinísticos para desenvolvimento,
testes, compilação de hardware (KiCad) e verificação de ambiente.
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
from datetime import datetime, timezone
from pathlib import Path

ROOT_DIR = Path(__file__).resolve().parent
HARDWARE_DIR = ROOT_DIR / "hardware" / "kicad"
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
        "git": {"available": bool(shutil.which("git")), "path": shutil.which("git")}
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
    print(f"[*] KiCad CLI:      {'OK' if status['kicad_cli']['available'] else 'NOT FOUND'} ({status['kicad_cli']['path']})")
    print(f"[*] KiCad Python:   {'OK' if status['kicad_python']['available'] else 'NOT FOUND'}")
    print(f"[*] Docker:         {'RUNNING' if status['docker_running'] else ('INSTALLED (DAEMON OFF)' if status['docker_installed'] else 'NOT FOUND')}")
    print(f"[*] PHP (Local):    {'OK' if status['php']['available'] else 'NOT FOUND'}")
    print(f"[*] Python Libs:")
    for lib, ok in py_libs.items():
        print(f"    - {lib:10s}: {'OK' if ok else 'MISSING'}")

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
                    boards.append({
                        "name": item.name,
                        "path": str(item.relative_to(ROOT_DIR)),
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
    """Executa o DRC do KiCad em uma placa específica e retorna resumo estruturado."""
    board_dir = HARDWARE_DIR / args.board
    if not board_dir.exists():
        # Tenta achar por match parcial
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

    # Prioriza o pcb com o mesmo nome do diretório
    pcb_file = pcb_files[0]
    for p in pcb_files:
        if p.stem == board_dir.name:
            pcb_file = p
            break

    kicad_cli = get_kicad_cli()
    if not kicad_cli:
        print("Erro: kicad-cli.exe não encontrado no sistema.", file=sys.stderr)
        sys.exit(1)

    report_file = ROOT_DIR / f"{pcb_file.stem}-drc.rpt"
    cmd = [kicad_cli, "pcb", "drc", "-o", str(report_file), str(pcb_file)]
    res = subprocess.run(cmd, capture_output=True, text=True)

    violations = 0
    unconnected = 0
    errors_list = []

    if report_file.exists():
        with open(report_file, "r", encoding="utf-8", errors="ignore") as f:
            lines = f.readlines()
        for line in lines:
            if "violações" in line.lower() or "violations" in line.lower():
                try:
                    violations = int("".join(filter(str.isdigit, line)))
                except ValueError:
                    pass
            elif "desconectados" in line.lower() or "unconnected" in line.lower():
                try:
                    unconnected = int("".join(filter(str.isdigit, line)))
                except ValueError:
                    pass
            elif line.startswith("[") and "]" in line:
                errors_list.append(line.strip())
        report_file.unlink(missing_ok=True)

    result = {
        "board": board_dir.name,
        "pcb_file": pcb_file.name,
        "violations": violations,
        "unconnected": unconnected,
        "clean": (violations == 0 and unconnected == 0),
        "errors_sample": errors_list[:10]
    }

    if args.json:
        print(json.dumps(result, indent=2))
        return

    print(f"=== DRC REPORT: {board_dir.name} ===")
    print(f"Arquivo: {pcb_file.name}")
    print(f"Status:  {'[OK] PERFEITO' if result['clean'] else '[FALHA] REQUER ATENCAO'}")
    print(f"Violações DRC:      {violations}")
    print(f"Pads Desconectados: {unconnected}")
    if errors_list:
        print("Amostra de Erros:")
        for err in errors_list[:5]:
            print(f"  - {err}")

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
        "output_dir": str(export_dir.relative_to(ROOT_DIR)),
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
# COMMAND: frontend:build
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

# -----------------------------------------------------------------------------
# COMMAND: docs:build
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

# -----------------------------------------------------------------------------
# COMMAND: docs:test
# -----------------------------------------------------------------------------
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
# COMMAND: frontend:test
# -----------------------------------------------------------------------------
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

# -----------------------------------------------------------------------------
# COMMAND: frontend:list
# -----------------------------------------------------------------------------
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
            "rpm_base": 1750, "rms_global": 1.20, "velocity_rms": 1.8,
            "iso_zone": "A", "temp_base": 36.5, "mic_rms": 0.040,
            "ml_status": "saudavel", "ml_confidence": 0.98,
            "kurt_x": 2.9, "kurt_y": 3.0, "kurt_z": 2.8
        },
        "defect_bearing": {
            "rpm_base": 1740, "rms_global": 5.85, "velocity_rms": 7.4,
            "iso_zone": "D", "temp_base": 58.2, "mic_rms": 0.320,
            "ml_status": "falha", "ml_confidence": 0.94,
            "kurt_x": 8.6, "kurt_y": 9.1, "kurt_z": 7.8
        },
        "overheating": {
            "rpm_base": 1720, "rms_global": 2.40, "velocity_rms": 3.1,
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
        except Exception as e:
            connected = False

    if not args.json:
        print(f"=== SIMULADOR DE TELEMETRIA IOT AMEMIYA ===")
        print(f"Tópico MQTT:   {topic}")
        print(f"Perfil Físico: {args.profile.upper()}")
        print(f"Status Broker: {'CONECTADO (' + args.host + ':' + str(args.port) + ')' if connected else 'OFFLINE / DRY-RUN'}")
        print(f"Publicando {args.count} pacotes com intervalo de {args.interval}s...\n")

    for i in range(args.count):
        noise = random.uniform(-0.05, 0.05)
        now_iso = datetime.now(timezone.utc).isoformat()
        payload = {
            "tenant_id": args.tenant,
            "machine_id": args.machine,
            "device_id": args.gateway,
            "node_id": args.node,
            "msg_id": 1000 + i,
            "rpm": int(prof["rpm_base"] + random.randint(-15, 15)),
            "rms_global": round(prof["rms_global"] * (1.0 + noise), 3),
            "velocity_rms": round(prof["velocity_rms"] * (1.0 + noise), 2),
            "iso_zone": prof["iso_zone"],
            "temp_celsius": round(prof["temp_base"] + random.uniform(-0.4, 0.4), 2),
            "battery_voltage": round(3.68 + random.uniform(-0.02, 0.02), 2),
            "time_domain": {
                "rms_x": round(prof["rms_global"] * 0.7 * (1.0 + noise), 3),
                "kurt_x": round(prof["kurt_x"] + random.uniform(-0.2, 0.2), 2),
                "rms_y": round(prof["rms_global"] * 0.8 * (1.0 + noise), 3),
                "kurt_y": round(prof["kurt_y"] + random.uniform(-0.2, 0.2), 2),
                "rms_z": round(prof["rms_global"] * 0.9 * (1.0 + noise), 3),
                "kurt_z": round(prof["kurt_z"] + random.uniform(-0.2, 0.2), 2)
            },
            "mic_rms": round(prof["mic_rms"] * (1.0 + noise), 4),
            "features": [round(prof["rms_global"], 2), round(prof["kurt_x"], 2), round(prof["mic_rms"], 3)],
            "ml_status": prof["ml_status"],
            "ml_confidence": prof["ml_confidence"],
            "timestamp": now_iso
        }
        messages.append(payload)

        if connected and client:
            client.publish(topic, json.dumps(payload), qos=1)
            if not args.json:
                print(f"[{i+1}/{args.count}] Publicado -> RMS: {payload['rms_global']}g | Temp: {payload['temp_celsius']}°C | Status: {payload['ml_status']}")
        else:
            if not args.json:
                print(f"[{i+1}/{args.count}] (Mock) -> RMS: {payload['rms_global']}g | Temp: {payload['temp_celsius']}°C | ISO: {payload['iso_zone']}")

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
# COMMAND: audit (Quality Gate Geral)
# -----------------------------------------------------------------------------
def cmd_audit(args):
    """Executa a esteira completa de verificação de qualidade (Hardware DRC + Frontend Build)."""
    start_time = time.time()
    audit_results = {
        "timestamp": datetime.now(timezone.utc).isoformat(),
        "ready_to_commit": True,
        "checks": {}
    }

    if not args.json:
        print("=== AMEMIYA QUALITY GATE AUDIT ===\n")

    # 1. Hardware DRC (Probe Tower Dual)
    if not args.json:
        print("[1/3] Verificando Hardware DRC (amemiya_probe_tower_dual)...")
    kicad_cli = get_kicad_cli()
    pcb_file = HARDWARE_DIR / "amemiya_probe_tower_dual" / "amemiya_probe_tower_dual.kicad_pcb"
    if kicad_cli and pcb_file.exists():
        rpt = ROOT_DIR / "temp_audit_drc.rpt"
        res_drc = subprocess.run([kicad_cli, "pcb", "drc", "-o", str(rpt), str(pcb_file)], capture_output=True, text=True)
        unconnected = 0
        violations = 0
        if rpt.exists():
            with open(rpt, "r", encoding="utf-8", errors="ignore") as f:
                for line in f:
                    if "desconectados" in line.lower() or "unconnected" in line.lower():
                        unconnected = int("".join(filter(str.isdigit, line)) or 0)
                    elif "violações" in line.lower() or "violations" in line.lower():
                        violations = int("".join(filter(str.isdigit, line)) or 0)
            rpt.unlink(missing_ok=True)
        
        drc_ok = (unconnected == 0)
        audit_results["checks"]["hardware_drc"] = {
            "status": "PASS" if drc_ok else "FAIL",
            "unconnected_pads": unconnected,
            "violations": violations
        }
        if not drc_ok:
            audit_results["ready_to_commit"] = False
        if not args.json:
            print(f"      -> Status: {'[OK]' if drc_ok else '[FALHA]'} (Unconnected: {unconnected}, Violations: {violations})")
    else:
        audit_results["checks"]["hardware_drc"] = {"status": "SKIPPED", "reason": "KiCad CLI not available"}

    # 2. Frontend Vite Build
    if not args.json:
        print("[2/4] Verificando Compilação Frontend Vite...")
    npm_bin = get_npm()
    if npm_bin and (ROOT_DIR / "package.json").exists():
        res_vite = subprocess.run([npm_bin, "run", "build"], cwd=str(ROOT_DIR), capture_output=True, text=True)
        vite_ok = (res_vite.returncode == 0)
        audit_results["checks"]["frontend_build"] = {
            "status": "PASS" if vite_ok else "FAIL",
            "return_code": res_vite.returncode
        }
        if not vite_ok:
            audit_results["ready_to_commit"] = False
        if not args.json:
            print(f"      -> Status: {'[OK]' if vite_ok else '[FALHA]'} (Vite exit code: {res_vite.returncode})")
    else:
        audit_results["checks"]["frontend_build"] = {"status": "SKIPPED", "reason": "npm or package.json not found"}

    # 3. Backend PHP Syntax Check
    if not args.json:
        print("[3/4] Verificando Sintaxe PHP do Core...")
    php_bin = shutil.which("php")
    if php_bin:
        syntax_errs = 0
        sample_files = list((ROOT_DIR / "app").rglob("*.php"))[:10]
        for f in sample_files:
            res_php = subprocess.run([php_bin, "-l", str(f)], capture_output=True, text=True)
            if res_php.returncode != 0:
                syntax_errs += 1
        backend_ok = (syntax_errs == 0)
        audit_results["checks"]["backend_syntax"] = {
            "status": "PASS" if backend_ok else "FAIL",
            "syntax_errors": syntax_errs
        }
        if not backend_ok:
            audit_results["ready_to_commit"] = False
        if not args.json:
            print(f"      -> Status: {'[OK]' if backend_ok else '[FALHA]'} (Erros: {syntax_errs})")
    else:
        audit_results["checks"]["backend_syntax"] = {"status": "SKIPPED"}

    # 4. Documentation Quality & Integrity Check (VitePress + Scramble)
    if not args.json:
        print("[4/4] Verificando Integridade da Documentação (VitePress + Scramble)...")
    doc_script = ROOT_DIR / "scripts" / "test_docs.py"
    if doc_script.exists():
        res_doc = subprocess.run([sys.executable, str(doc_script), "--json"], capture_output=True, text=True)
        try:
            doc_report = json.loads(res_doc.stdout)
            doc_ok = doc_report.get("all_passed", False)
            md_errs = doc_report.get("markdown_integrity", {}).get("errors_count", 0)
        except Exception:
            doc_ok = (res_doc.returncode == 0)
            md_errs = 0
            doc_report = {}

        audit_results["checks"]["documentation"] = {
            "status": "PASS" if doc_ok else "FAIL",
            "markdown_errors": md_errs,
            "vitepress_build": doc_report.get("vitepress_build", {}).get("status", "UNKNOWN"),
            "openapi_contract": doc_report.get("openapi_contract", {}).get("status", "UNKNOWN")
        }
        if not doc_ok:
            audit_results["ready_to_commit"] = False
        if not args.json:
            print(f"      -> Status: {'[OK]' if doc_ok else '[FALHA]'}")
    else:
        audit_results["checks"]["documentation"] = {"status": "SKIPPED"}

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
# COMMAND: clean (Limpeza de Arquivos Temporários)
# -----------------------------------------------------------------------------
def cmd_clean(args):
    """Remove backups do KiCad, caches de Python, caches de build e do Laravel."""
    removed = []

    # 1. KiCad backups (*-bak)
    for bak in ROOT_DIR.rglob("*.kicad_*-bak"):
        bak.unlink(missing_ok=True)
        removed.append(str(bak.relative_to(ROOT_DIR)))

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
    # Parent parser para argumentos comuns (como --json)
    common_parser = argparse.ArgumentParser(add_help=False)
    common_parser.add_argument("--json", action="store_true", help="Retorna saída estruturada em JSON (ideal para ferramentas de IA)")

    parser = argparse.ArgumentParser(
        prog="amemiya-dev",
        description="Amemiya Dev CLI - Harness de Automação de Desenvolvimento & IA",
        parents=[common_parser]
    )

    subparsers = parser.add_subparsers(dest="command", help="Comandos disponíveis")

    # env:check
    p_env = subparsers.add_parser("env:check", parents=[common_parser], help="Verifica saúde do ambiente (Docker, KiCad, PHP, bibliotecas)")
    p_env.set_defaults(func=cmd_env_check)

    # pcb:list
    p_plist = subparsers.add_parser("pcb:list", parents=[common_parser], help="Lista todas as placas e projetos KiCad")
    p_plist.set_defaults(func=cmd_pcb_list)

    # pcb:drc
    p_pdrc = subparsers.add_parser("pcb:drc", parents=[common_parser], help="Roda verificação de regras de design (DRC) do KiCad")
    p_pdrc.add_argument("board", nargs="?", default="amemiya_probe_tower_dual", help="Nome do diretório da placa (ex: amemiya_probe_tower_dual)")
    p_pdrc.set_defaults(func=cmd_pcb_drc)

    # pcb:laser
    p_laser = subparsers.add_parser("pcb:laser", parents=[common_parser], help="Regera arquivos DXF/PNG/CNC para manufatura a laser")
    p_laser.add_argument("board", nargs="?", default="amemiya_probe_tower_dual", help="Nome do diretório da placa")
    p_laser.set_defaults(func=cmd_pcb_laser)

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
    p_dbuild = subparsers.add_parser("docs:build", parents=[common_parser], help="Compila o portal estático de documentação VitePress (Diátaxis)")
    p_dbuild.set_defaults(func=cmd_docs_build)

    # docs:test
    p_dtest = subparsers.add_parser("docs:test", parents=[common_parser], help="Testa integridade, links, markdown e contratos OpenAPI dos documentos")
    p_dtest.set_defaults(func=cmd_docs_test)

    # iot:mock
    p_imock = subparsers.add_parser("iot:mock", parents=[common_parser], help="Publica telemetria MQTT sintética para teste do monitor sem hardware")
    p_imock.add_argument("--profile", choices=["healthy", "defect_bearing", "overheating"], default="healthy", help="Perfil de falha física")
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
    p_audit = subparsers.add_parser("audit", parents=[common_parser], help="Quality Gate completo (Hardware DRC + Frontend Build + Backend Syntax)")
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
