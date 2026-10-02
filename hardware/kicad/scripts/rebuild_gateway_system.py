#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Amemiya Industrial IoT - Gateway Concentrador v1.0 (amemiya_gateway)
Pipeline completo:
1. Geração de esquemático modular padrão IEEE 315 / IEC 61082-1
2. Verificação ERC formal (0 erros, 0 avisos)
3. Construção e roteamento da PCB (85.0 x 60.0 mm) via Freerouting
4. Planos de terra sólidos em F.Cu e B.Cu
5. Verificação DRC formal (0 erros, 0 avisos, 0 desconexões)
6. Exportação de fabricação (Gerbers, Drill, POS, 3D STEP e renders PNG)
"""

import os
import sys
import uuid
import math
import re
import subprocess
import shutil
import zipfile
import json

for p in [r"C:\Program Files\KiCad\9.0\bin", r"C:\Program Files\KiCad\8.0\bin"]:
    if os.path.exists(p) and p not in sys.path:
        sys.path.insert(0, p)
        os.environ["PATH"] = p + ";" + os.environ.get("PATH", "")

import pcbnew

JAVA_EXE = r"C:\Program Files\Eclipse Adoptium\jdk-17.0.13.11-hotspot\bin\java.exe"
KICAD_CLI = r"C:\Program Files\KiCad\9.0\bin\kicad-cli.exe"
FREEROUTING_JAR = r"C:\Users\andrl\.kicad-mcp\freerouting.jar"
KICAD_SYM_DIR = r"C:\Program Files\KiCad\9.0\share\kicad\symbols"
KICAD_FP_DIR = r"C:\Program Files\KiCad\9.0\share\kicad\footprints"

BASE_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
GATEWAY_DIR = os.path.join(BASE_DIR, "amemiya_gateway")
SCH_FILE = os.path.join(GATEWAY_DIR, "amemiya_gateway.kicad_sch")
PCB_FILE = os.path.join(GATEWAY_DIR, "amemiya_gateway.kicad_pcb")
DSN_FILE = os.path.join(GATEWAY_DIR, "amemiya_gateway.dsn")
SES_FILE = os.path.join(GATEWAY_DIR, "amemiya_gateway.ses")
FAB_DIR = os.path.join(GATEWAY_DIR, "fabrication")
GERBER_DIR = os.path.join(FAB_DIR, "gerbers")
POS_DIR = os.path.join(FAB_DIR, "pos")
ARTIFACT_DIR = r"C:\Users\andrl\.gemini\antigravity-cli\brain\efd833bf-6ba1-4a1b-a7ce-aa9aa87aa86c"

os.makedirs(GATEWAY_DIR, exist_ok=True)
os.makedirs(GERBER_DIR, exist_ok=True)
os.makedirs(POS_DIR, exist_ok=True)

# Grade padrão KiCad de 50 mil (1.27 mm)
G = 1.27
def mm(v): return pcbnew.FromMM(v)
def gx(i): return round(i * G, 4)
def gy(j): return round(j * G, 4)
def u(): return str(uuid.uuid4())

# ==============================================================================
# 1. EXTRAÇÃO DE SÍMBOLOS OFICIAIS KiCad 9
# ==============================================================================

def extract_symbol_definition(lib_name, sym_name):
    lib_path = os.path.join(KICAD_SYM_DIR, f"{lib_name}.kicad_sym")
    with open(lib_path, "r", encoding="utf-8", errors="ignore") as f:
        text = f.read()
    target = f'(symbol "{sym_name}"'
    pos = text.find(target)
    if pos == -1:
        raise ValueError(f"Symbol {sym_name} not found in {lib_name}")
    count = 0
    end_pos = -1
    for i in range(pos, len(text)):
        if text[i] == '(': count += 1
        elif text[i] == ')':
            count -= 1
            if count == 0:
                end_pos = i + 1
                break
    raw_def = text[pos:end_pos]
    return raw_def.replace(f'(symbol "{sym_name}"', f'(symbol "{lib_name}:{sym_name}"', 1)

def get_symbol_pin_offsets(lib_name, sym_name):
    s = extract_symbol_definition(lib_name, sym_name)
    pins = {}
    for m in re.finditer(r'\(pin\s+\w+\s+\w+\s+\(at\s+([0-9\.\-]+)\s+([0-9\.\-]+)\s+([0-9\.\-]+)\)[\s\S]*?\(number\s+"([^"]+)"', s):
        px = float(m.group(1))
        py = float(m.group(2))
        rot = float(m.group(3))
        pnum = m.group(4)
        pins[pnum] = (px, py, rot)
    return pins

SYMS = [
    ("Connector_Generic", "Conn_01x22"),
    ("Connector_Generic", "Conn_01x07"),
    ("Connector_Generic", "Conn_01x08"),
    ("Connector_Generic", "Conn_01x04"),
    ("Connector_Generic", "Conn_01x02"),
    ("Device", "R"),
    ("Device", "C"),
    ("Device", "C_Polarized"),
    ("Device", "D_Schottky"),
    ("Device", "LED"),
    ("Device", "Buzzer"),
    ("Switch", "SW_Push"),
    ("power", "+5V"),
    ("power", "+3V3"),
    ("power", "GND"),
    ("power", "PWR_FLAG"),
]

SYM_DEFS = {}
PIN_OFFSETS = {}
for lib, sym in SYMS:
    key = f"{lib}:{sym}"
    SYM_DEFS[key] = extract_symbol_definition(lib, sym)
    PIN_OFFSETS[key] = get_symbol_pin_offsets(lib, sym)

def get_pin_canvas_coord(sym_x, sym_y, lib_key, pin_num, angle_deg=0):
    dx, dy, _ = PIN_OFFSETS[lib_key][str(pin_num)]
    rad = math.radians(angle_deg)
    dx_rot = dx * math.cos(rad) - dy * math.sin(rad)
    dy_rot = dx * math.sin(rad) + dy * math.cos(rad)
    return round(sym_x + dx_rot, 4), round(sym_y - dy_rot, 4)

def r(v):
    return round(float(v), 4)

def wire(x1, y1, x2, y2):
    return f"""  (wire (pts (xy {r(x1)} {r(y1)}) (xy {r(x2)} {r(y2)}))
    (stroke (width 0) (type default))
    (uuid "{u()}")
  )\n"""

def junction(x, y):
    return f"""  (junction (at {r(x)} {r(y)}) (diameter 1.0) (color 0 0 0 0)
    (uuid "{u()}")
  )\n"""

def no_connect(x, y):
    return f"""  (no_connect (at {r(x)} {r(y)}) (uuid "{u()}"))\n"""

def label(text, x, y, rot=0):
    return f"""  (label "{text}"
    (at {r(x)} {r(y)} {rot})
    (effects (font (size 1.27 1.27)))
    (uuid "{u()}")
  )\n"""

def sym_inst(lib_id, ref, val, fp, x, y, rot=0, desc="", dsheet="~", in_bom=True, on_board=True, ref_offset=None, val_offset=None, sch_uuid="68a10000-0000-4000-8000-000000000001"):
    b_str = "yes" if in_bom else "no"
    o_str = "yes" if on_board else "no"
    prop_rot = 0
    rx = r(ref_offset[0]) if ref_offset else r(x)
    ry = r(ref_offset[1]) if ref_offset else r(y - gy(2))
    vx = r(val_offset[0]) if val_offset else r(x)
    vy = r(val_offset[1]) if val_offset else r(y + gy(2))

    inst_str = ""
    if sch_uuid and not ref.startswith("#"):
        inst_str = f"""    (instances
      (project "amemiya_gateway"
        (path "/{sch_uuid}"
          (reference "{ref}")
          (unit 1)
        )
      )
    )
"""
    return f"""  (symbol (lib_id "{lib_id}") (at {r(x)} {r(y)} {rot}) (unit 1) (in_bom {b_str}) (on_board {o_str})
    (uuid "{u()}")
    (property "Reference" "{ref}" (at {rx} {ry} {prop_rot}) (effects (font (size 1.27 1.27))))
    (property "Value" "{val}" (at {vx} {vy} {prop_rot}) (effects (font (size 1.27 1.27))))
    (property "Footprint" "{fp}" (at {r(x)} {r(y)} {prop_rot}) (effects (font (size 1.27 1.27)) (hide yes)))
    (property "Datasheet" "{dsheet}" (at {r(x)} {r(y)} {prop_rot}) (effects (font (size 1.27 1.27)) (hide yes)))
    (property "Description" "{desc}" (at {r(x)} {r(y)} {prop_rot}) (effects (font (size 1.27 1.27)) (hide yes)))
{inst_str}  )
"""

def pwr_inst(lib_id, name, x, y, rot=0):
    x_r, y_r = r(x), r(y)
    vx, vy = x_r, r(y_r + 2.54) if "GND" in name else r(y_r - 2.54)
    return f"""  (symbol (lib_id "{lib_id}") (at {x_r} {y_r} {rot}) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "#PWR" (at {x_r} {y_r} 0) (effects (font (size 1.27 1.27)) (hide yes)))
    (property "Value" "{name}" (at {vx} {vy} 0) (effects (font (size 1.27 1.27))))
    (property "Footprint" "" (at {x_r} {y_r} 0) (effects (font (size 1.27 1.27)) (hide yes)))
    (property "Datasheet" "" (at {x_r} {y_r} 0) (effects (font (size 1.27 1.27)) (hide yes)))
    (property "Description" "" (at {x_r} {y_r} 0) (effects (font (size 1.27 1.27)) (hide yes)))
  )
"""

def pwr_flag_inst(x, y):
    x_r, y_r = r(x), r(y)
    return f"""  (symbol (lib_id "power:PWR_FLAG") (at {x_r} {y_r} 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "#FLG" (at {x_r} {y_r} 0) (effects (font (size 1.27 1.27)) (hide yes)))
    (property "Value" "PWR_FLAG" (at {x_r} {r(y_r - 2.54)} 0) (effects (font (size 1.27 1.27))))
    (property "Footprint" "" (at {x_r} {y_r} 0) (effects (font (size 1.27 1.27)) (hide yes)))
    (property "Datasheet" "" (at {x_r} {y_r} 0) (effects (font (size 1.27 1.27)) (hide yes)))
    (property "Description" "" (at {x_r} {y_r} 0) (effects (font (size 1.27 1.27)) (hide yes)))
  )
"""

def rect_box(x1, y1, x2, y2):
    return f"""  (rectangle (start {r(x1)} {r(y1)}) (end {r(x2)} {r(y2)})
    (stroke (width 0.254) (type dash))
    (fill (type none))
    (uuid "{u()}")
  )\n"""

def text_block(txt, x, y, size=1.5, bold=True, italic=False):
    style = "bold" if bold else ("italic" if italic else "")
    return f"""  (text "{txt}" (at {r(x)} {r(y)} 0)
    (effects (font (size {size} {size}) {style}) (justify left bottom))
    (uuid "{u()}")
  )\n"""

# ==============================================================================
# 2. GERADOR DO ESQUEMÁTICO (amemiya_gateway.kicad_sch)
# ==============================================================================

def generate_schematic():
    print("--> [1/6] Gerando amemiya_gateway.kicad_sch na grade exata de 50 mil...")
    sch_uuid = "68a10000-0000-4000-8000-000000000001"
    
    out = f"""(kicad_sch
  (version 20231120)
  (generator "kicad")
  (generator_version "9.0")
  (uuid "{sch_uuid}")
  (paper "A3")
  (title_block
    (title "Amemiya Industrial IoT - Gateway Concentrador LoRa/MQTT v1.0")
    (date "2026-09-28")
    (rev "v1.0")
    (company "Lean Tech Metrology & Industrial IoT Solutions")
    (comment 1 "Certificacao: 0 Violacoes ERC / 0 Violacoes DRC")
    (comment 2 "Padrao de Projeto: IEEE 315 / IEC 61082-1 / IPC-2221B")
    (comment 3 "Transceptor: CDEBYTE E220-900T22D (LLCC68 915 MHz 22dBm)")
  )
  (lib_symbols
"""
    for k in SYM_DEFS:
        raw = SYM_DEFS[k]
        lines = raw.split("\n")
        indented = "\n".join("    " + line if line.strip() else "" for line in lines)
        out += indented + "\n"
    out += "  )\n\n"

    # Banner superior
    out += text_block("SISTEMA DE METROLOGIA LEAN TECH — GATEWAY CONCENTRADOR INDUSTRIAL (LORA TO MQTT)", gx(16), gy(12), size=2.4, bold=True)
    out += text_block("Ponte de Borda LoRa 915 MHz (LLCC68) para Wi-Fi / Ethernet W5500 com Ingestao MQTT para o Backend Laravel", gx(16), gy(16), size=1.3, italic=True)

    # =========================================================================
    # BLOCO 1: ALIMENTAÇÃO INDUSTRIAL & CONVERSÃO STEP-DOWN BUCK
    # =========================================================================
    b1_x1, b1_y1 = gx(16), gy(20)
    b1_x2, b1_y2 = gx(150), gy(74)
    out += rect_box(b1_x1, b1_y1, b1_x2, b1_y2)
    out += text_block("BLOCO 1: ALIMENTACAO INDUSTRIAL & GERENCIAMENTO DE ENERGIA (9V-24V DC / USB 5V)", b1_x1 + gx(3), b1_y1 + gy(4), size=1.6, bold=True)
    out += text_block("Entrada por Borne 9-24V com protecao reversa e Step-Down MP1584EN + Entrada auxiliar USB-C 5V", b1_x1 + gx(3), b1_y1 + gy(7), size=1.05, italic=True)

    # Borne 2P J_PWR_IN
    pwr_x, pwr_y = gx(30), gy(36)
    out += sym_inst(
        "Connector_Generic:Conn_01x02", "J_PWR_IN", "Borne_Industrial_9-24V",
        "TerminalBlock_Phoenix:TerminalBlock_Phoenix_MKDS-1,5-2-5.08_1x02_P5.08mm_Horizontal",
        pwr_x, pwr_y, rot=0, desc="Borne parafuso para entrada de energia industrial 9V-24V",
        ref_offset=(pwr_x, pwr_y - gy(4)), val_offset=(pwr_x, pwr_y + gy(4))
    )
    p_p1 = get_pin_canvas_coord(pwr_x, pwr_y, "Connector_Generic:Conn_01x02", 1)
    p_p2 = get_pin_canvas_coord(pwr_x, pwr_y, "Connector_Generic:Conn_01x02", 2)

    # D1 (1N5819)
    d1_x, d1_y = gx(46), p_p1[1]
    out += sym_inst(
        "Device:D_Schottky", "D1", "1N5819",
        "Diode_THT:D_DO-41_SOD81_P7.62mm_Horizontal",
        d1_x, d1_y, rot=0, desc="Diodo Schottky de protecao reversa 1A 40V",
        ref_offset=(d1_x, d1_y - gy(3)), val_offset=(d1_x, d1_y + gy(3))
    )
    d1_p1 = get_pin_canvas_coord(d1_x, d1_y, "Device:D_Schottky", 1)
    d1_p2 = get_pin_canvas_coord(d1_x, d1_y, "Device:D_Schottky", 2)
    out += wire(p_p1[0], p_p1[1], d1_p1[0], d1_p1[1])

    # Borne Pin 2: GND
    out += wire(p_p2[0], p_p2[1], p_p2[0] - gx(4), p_p2[1])
    out += pwr_inst("power:GND", "GND", p_p2[0] - gx(4), p_p2[1], rot=0)

    # Bulk Cap C1 (470uF / 25V)
    c1_x, c1_y = gx(56), gy(44)
    out += sym_inst(
        "Device:C_Polarized", "C1", "470uF_25V",
        "Capacitor_THT:CP_Radial_D6.3mm_P2.50mm",
        c1_x, c1_y, rot=0, desc="Capacitor eletrolitico de desacoplamento bulk",
        ref_offset=(c1_x + gx(4), c1_y - gy(2)), val_offset=(c1_x + gx(4), c1_y + gy(2))
    )
    c1_p1 = get_pin_canvas_coord(c1_x, c1_y, "Device:C_Polarized", 1)
    c1_p2 = get_pin_canvas_coord(c1_x, c1_y, "Device:C_Polarized", 2)

    out += wire(d1_p2[0], d1_p2[1], c1_p1[0], d1_p2[1])
    out += wire(c1_p1[0], d1_p2[1], c1_p1[0], c1_p1[1])
    out += junction(c1_p1[0], d1_p2[1])
    out += wire(c1_p2[0], c1_p2[1], c1_p2[0], c1_p2[1] + gy(3))
    out += pwr_inst("power:GND", "GND", c1_p2[0], c1_p2[1] + gy(3), rot=0)

    # U_BUCK (MP1584EN 1x04)
    buck_x, buck_y = gx(78), gy(40)
    out += sym_inst(
        "Connector_Generic:Conn_01x04", "U_BUCK", "MP1584EN_Buck_5V",
        "Connector_PinHeader_2.54mm:PinHeader_1x04_P2.54mm_Vertical",
        buck_x, buck_y, rot=0, desc="Modulo Step-Down MP1584EN (Saida regulada 5.0V / 3A)",
        ref_offset=(buck_x, buck_y - gy(6)), val_offset=(buck_x, buck_y + gy(6))
    )
    bk_p1 = get_pin_canvas_coord(buck_x, buck_y, "Connector_Generic:Conn_01x04", 1)
    bk_p2 = get_pin_canvas_coord(buck_x, buck_y, "Connector_Generic:Conn_01x04", 2)
    bk_p3 = get_pin_canvas_coord(buck_x, buck_y, "Connector_Generic:Conn_01x04", 3)
    bk_p4 = get_pin_canvas_coord(buck_x, buck_y, "Connector_Generic:Conn_01x04", 4)

    # Buck Pin 1: IN+
    out += wire(c1_p1[0], d1_p2[1], bk_p1[0], d1_p2[1])
    out += wire(bk_p1[0], d1_p2[1], bk_p1[0], bk_p1[1])

    # Buck Pin 2: IN- (GND)
    out += wire(bk_p2[0], bk_p2[1], bk_p2[0] - gx(4), bk_p2[1])
    out += pwr_inst("power:GND", "GND", bk_p2[0] - gx(4), bk_p2[1], rot=0)

    # Buck Pin 3: OUT- (GND)
    out += wire(bk_p3[0], bk_p3[1], bk_p3[0] - gx(4), bk_p3[1])
    out += pwr_inst("power:GND", "GND", bk_p3[0] - gx(4), bk_p3[1], rot=0)

    # Buck Pin 4: OUT+ (+5V)
    out += wire(bk_p4[0], bk_p4[1], bk_p4[0] - gx(4), bk_p4[1])
    out += wire(bk_p4[0] - gx(4), bk_p4[1], bk_p4[0] - gx(4), bk_p4[1] + gy(4))
    out += pwr_inst("power:+5V", "+5V", bk_p4[0] - gx(4), bk_p4[1] + gy(4), rot=0)

    # Entrada USB Auxiliar 5V
    usb_x, usb_y = gx(100), gy(36)
    out += sym_inst(
        "Connector_Generic:Conn_01x02", "J_USB_PWR", "Entrada_USB_5V",
        "Connector_PinHeader_2.54mm:PinHeader_1x02_P2.54mm_Vertical",
        usb_x, usb_y, rot=0, desc="Header auxiliar de alimentacao 5V USB",
        ref_offset=(usb_x, usb_y - gy(4)), val_offset=(usb_x, usb_y + gy(4))
    )
    u_p1 = get_pin_canvas_coord(usb_x, usb_y, "Connector_Generic:Conn_01x02", 1)
    u_p2 = get_pin_canvas_coord(usb_x, usb_y, "Connector_Generic:Conn_01x02", 2)

    # D2 (1N5819)
    d2_x, d2_y = gx(116), u_p1[1]
    out += sym_inst(
        "Device:D_Schottky", "D2", "1N5819",
        "Diode_THT:D_DO-41_SOD81_P7.62mm_Horizontal",
        d2_x, d2_y, rot=0, desc="Diodo de protecao ORing USB 5V",
        ref_offset=(d2_x, d2_y - gy(3)), val_offset=(d2_x, d2_y + gy(3))
    )
    d2_p1 = get_pin_canvas_coord(d2_x, d2_y, "Device:D_Schottky", 1)
    d2_p2 = get_pin_canvas_coord(d2_x, d2_y, "Device:D_Schottky", 2)

    out += wire(u_p1[0], u_p1[1], d2_p1[0], d2_p1[1])
    out += wire(d2_p2[0], d2_p2[1], d2_p2[0] + gx(4), d2_p2[1])
    out += pwr_inst("power:+5V", "+5V", d2_p2[0] + gx(4), d2_p2[1], rot=0)

    # USB Pin 2: GND
    out += wire(u_p2[0], u_p2[1], u_p2[0] - gx(4), u_p2[1])
    out += pwr_inst("power:GND", "GND", u_p2[0] - gx(4), u_p2[1], rot=0)

    # Capacitores C2 (5V) e C3 (3V3)
    c2_x, c2_y = gx(132), gy(40)
    out += sym_inst(
        "Device:C", "C2", "100nF", "Capacitor_THT:C_Disc_D3.8mm_W2.6mm_P2.50mm",
        c2_x, c2_y, rot=0, desc="Filtro de alta frequencia 5V",
        ref_offset=(c2_x + gx(4), c2_y - gy(2)), val_offset=(c2_x + gx(4), c2_y + gy(2))
    )
    c2_p1 = get_pin_canvas_coord(c2_x, c2_y, "Device:C", 1)
    c2_p2 = get_pin_canvas_coord(c2_x, c2_y, "Device:C", 2)
    out += wire(c2_p1[0], c2_p1[1], c2_p1[0], c2_p1[1] - gy(3))
    out += pwr_inst("power:+5V", "+5V", c2_p1[0], c2_p1[1] - gy(3), rot=0)
    out += wire(c2_p2[0], c2_p2[1], c2_p2[0], c2_p2[1] + gy(3))
    out += pwr_inst("power:GND", "GND", c2_p2[0], c2_p2[1] + gy(3), rot=0)

    c3_x, c3_y = gx(142), gy(40)
    out += sym_inst(
        "Device:C", "C3", "100nF", "Capacitor_THT:C_Disc_D3.8mm_W2.6mm_P2.50mm",
        c3_x, c3_y, rot=0, desc="Filtro de alta frequencia 3.3V",
        ref_offset=(c3_x + gx(4), c3_y - gy(2)), val_offset=(c3_x + gx(4), c3_y + gy(2))
    )
    c3_p1 = get_pin_canvas_coord(c3_x, c3_y, "Device:C", 1)
    c3_p2 = get_pin_canvas_coord(c3_x, c3_y, "Device:C", 2)
    out += wire(c3_p1[0], c3_p1[1], c3_p1[0], c3_p1[1] - gy(3))
    out += pwr_inst("power:+3V3", "+3V3", c3_p1[0], c3_p1[1] - gy(3), rot=0)
    out += wire(c3_p2[0], c3_p2[1], c3_p2[0], c3_p2[1] + gy(3))
    out += pwr_inst("power:GND", "GND", c3_p2[0], c3_p2[1] + gy(3), rot=0)

    # LED PWR
    led_pwr_x, led_pwr_y = gx(30), gy(62)
    out += sym_inst(
        "Device:LED", "D_PWR", "Verde_3mm", "LED_THT:LED_D3.0mm",
        led_pwr_x, led_pwr_y, rot=0, desc="LED indicador de alimentacao 3.3V",
        ref_offset=(led_pwr_x, led_pwr_y - gy(3)), val_offset=(led_pwr_x, led_pwr_y + gy(3))
    )
    lp_p1 = get_pin_canvas_coord(led_pwr_x, led_pwr_y, "Device:LED", 1)
    lp_p2 = get_pin_canvas_coord(led_pwr_x, led_pwr_y, "Device:LED", 2)

    r_pwr_x, r_pwr_y = gx(44), led_pwr_y
    out += sym_inst(
        "Device:R", "R_PWR", "1k", "Resistor_THT:R_Axial_DIN0207_L6.3mm_D2.5mm_P7.62mm_Horizontal",
        r_pwr_x, r_pwr_y, rot=90, desc="Resistor limitador de corrente LED PWR",
        ref_offset=(r_pwr_x, r_pwr_y - gy(3)), val_offset=(r_pwr_x, r_pwr_y + gy(3))
    )
    rp_p1 = get_pin_canvas_coord(r_pwr_x, r_pwr_y, "Device:R", 1, angle_deg=90)
    rp_p2 = get_pin_canvas_coord(r_pwr_x, r_pwr_y, "Device:R", 2, angle_deg=90)

    out += wire(lp_p1[0], lp_p1[1], lp_p1[0] - gx(4), lp_p1[1])
    out += pwr_inst("power:+3V3", "+3V3", lp_p1[0] - gx(4), lp_p1[1], rot=0)
    out += wire(lp_p2[0], lp_p2[1], rp_p1[0], rp_p1[1])
    out += wire(rp_p2[0], rp_p2[1], rp_p2[0] + gx(4), rp_p2[1])
    out += pwr_inst("power:GND", "GND", rp_p2[0] + gx(4), rp_p2[1], rot=0)

    # PWR_FLAGS
    flg_x = gx(70)
    out += text_block("DEFINICAO DE FONTES DE ALIMENTACAO (PWR_FLAG ERC):", flg_x, gy(57), size=1.1, bold=True)
    out += pwr_flag_inst(flg_x + gx(8), gy(62))
    out += wire(flg_x + gx(8), gy(62), flg_x + gx(8), gy(66))
    out += pwr_inst("power:+5V", "+5V", flg_x + gx(8), gy(66), rot=0)

    out += pwr_flag_inst(flg_x + gx(28), gy(62))
    out += wire(flg_x + gx(28), gy(62), flg_x + gx(28), gy(66))
    out += pwr_inst("power:+3V3", "+3V3", flg_x + gx(28), gy(66), rot=0)

    out += pwr_flag_inst(flg_x + gx(48), gy(62))
    out += wire(flg_x + gx(48), gy(62), flg_x + gx(48), gy(66))
    out += pwr_inst("power:GND", "GND", flg_x + gx(48), gy(66), rot=0)

    # =========================================================================
    # BLOCO 2: MICROCONTROLADOR PRINCIPAL (ESP32-S3 DEVKIT 44P)
    # =========================================================================
    b2_x1, b2_y1 = gx(158), gy(20)
    b2_x2, b2_y2 = gx(310), gy(124)
    out += rect_box(b2_x1, b2_y1, b2_x2, b2_y2)
    out += text_block("BLOCO 2: MICROCONTROLADOR PRINCIPAL (ESP32-S3 DEVKITC-1 44 PINOS DUAL ROW)", b2_x1 + gx(3), b2_y1 + gy(4), size=1.6, bold=True)
    out += text_block("Soquetes femea duplos 1x22 pinos | Wi-Fi 802.11 b/g/n + BLE 5.0 | Dual Core 240 MHz LX7", b2_x1 + gx(3), b2_y1 + gy(7), size=1.05, italic=True)

    esp_l_x, esp_l_y = gx(195), gy(74)
    out += sym_inst(
        "Connector_Generic:Conn_01x22", "U_ESP_L", "ESP32-S3_DevKit_Left",
        "Connector_PinHeader_2.54mm:PinHeader_1x22_P2.54mm_Vertical",
        esp_l_x, esp_l_y, rot=0, desc="Barra de pinos esquerda do ESP32-S3 DevKit",
        ref_offset=(esp_l_x, esp_l_y - gy(25)), val_offset=(esp_l_x, esp_l_y + gy(25))
    )
    el_pins = {p: get_pin_canvas_coord(esp_l_x, esp_l_y, "Connector_Generic:Conn_01x22", p) for p in range(1, 23)}

    esp_r_x, esp_r_y = gx(270), gy(74)
    out += sym_inst(
        "Connector_Generic:Conn_01x22", "U_ESP_R", "ESP32-S3_DevKit_Right",
        "Connector_PinHeader_2.54mm:PinHeader_1x22_P2.54mm_Vertical",
        esp_r_x, esp_r_y, rot=0, desc="Barra de pinos direita do ESP32-S3 DevKit",
        ref_offset=(esp_r_x, esp_r_y - gy(25)), val_offset=(esp_r_x, esp_r_y + gy(25))
    )
    er_pins = {p: get_pin_canvas_coord(esp_r_x, esp_r_y, "Connector_Generic:Conn_01x22", p) for p in range(1, 23)}

    # Barra esquerda (U_ESP_L):
    # Pin 1: 3V3
    out += wire(el_pins[1][0], el_pins[1][1], el_pins[1][0] - gx(8), el_pins[1][1])
    out += pwr_inst("power:+3V3", "+3V3", el_pins[1][0] - gx(8), el_pins[1][1], rot=0)

    # Pin 2: 3V3
    out += wire(el_pins[2][0], el_pins[2][1], el_pins[2][0] - gx(8), el_pins[2][1])
    out += pwr_inst("power:+3V3", "+3V3", el_pins[2][0] - gx(8), el_pins[2][1], rot=0)

    # Pin 3: EN (NC)
    out += wire(el_pins[3][0], el_pins[3][1], el_pins[3][0] - gx(4), el_pins[3][1])
    out += no_connect(el_pins[3][0] - gx(4), el_pins[3][1])

    # Pin 4: GPIO 04 (LED_LORA_RX)
    out += wire(el_pins[4][0], el_pins[4][1], el_pins[4][0] - gx(10), el_pins[4][1])
    out += label("LED_LORA_RX", el_pins[4][0] - gx(10), el_pins[4][1], rot=0)

    # Pin 5: GPIO 05 (LED_MQTT_OK)
    out += wire(el_pins[5][0], el_pins[5][1], el_pins[5][0] - gx(10), el_pins[5][1])
    out += label("LED_MQTT_OK", el_pins[5][0] - gx(10), el_pins[5][1], rot=0)

    # Pin 6: GPIO 06 (LED_NET_ACT)
    out += wire(el_pins[6][0], el_pins[6][1], el_pins[6][0] - gx(10), el_pins[6][1])
    out += label("LED_NET_ACT", el_pins[6][0] - gx(10), el_pins[6][1], rot=0)

    # Pin 7: GPIO 07 (BUZZER)
    out += wire(el_pins[7][0], el_pins[7][1], el_pins[7][0] - gx(10), el_pins[7][1])
    out += label("BUZZER", el_pins[7][0] - gx(10), el_pins[7][1], rot=0)

    # Pin 8: GPIO 15 (NC)
    out += wire(el_pins[8][0], el_pins[8][1], el_pins[8][0] - gx(4), el_pins[8][1])
    out += no_connect(el_pins[8][0] - gx(4), el_pins[8][1])

    # Pin 9: GPIO 16 (LORA_RX)
    out += wire(el_pins[9][0], el_pins[9][1], el_pins[9][0] - gx(10), el_pins[9][1])
    out += label("LORA_RX", el_pins[9][0] - gx(10), el_pins[9][1], rot=0)

    # Pin 10: GPIO 17 (LORA_TX)
    out += wire(el_pins[10][0], el_pins[10][1], el_pins[10][0] - gx(10), el_pins[10][1])
    out += label("LORA_TX", el_pins[10][0] - gx(10), el_pins[10][1], rot=0)

    # Pin 11: GPIO 18 (LORA_AUX)
    out += wire(el_pins[11][0], el_pins[11][1], el_pins[11][0] - gx(10), el_pins[11][1])
    out += label("LORA_AUX", el_pins[11][0] - gx(10), el_pins[11][1], rot=0)

    # Pin 12: GPIO 08 (NC)
    out += wire(el_pins[12][0], el_pins[12][1], el_pins[12][0] - gx(4), el_pins[12][1])
    out += no_connect(el_pins[12][0] - gx(4), el_pins[12][1])

    # Pin 13: GPIO 03 (NC)
    out += wire(el_pins[13][0], el_pins[13][1], el_pins[13][0] - gx(4), el_pins[13][1])
    out += no_connect(el_pins[13][0] - gx(4), el_pins[13][1])

    # Pin 14: GPIO 46 (NC)
    out += wire(el_pins[14][0], el_pins[14][1], el_pins[14][0] - gx(4), el_pins[14][1])
    out += no_connect(el_pins[14][0] - gx(4), el_pins[14][1])

    # Pin 15: GPIO 09 (ETH_RST)
    out += wire(el_pins[15][0], el_pins[15][1], el_pins[15][0] - gx(10), el_pins[15][1])
    out += label("ETH_RST", el_pins[15][0] - gx(10), el_pins[15][1], rot=0)

    # Pin 16: GPIO 10 (ETH_CS)
    out += wire(el_pins[16][0], el_pins[16][1], el_pins[16][0] - gx(10), el_pins[16][1])
    out += label("ETH_CS", el_pins[16][0] - gx(10), el_pins[16][1], rot=0)

    # Pin 17: GPIO 11 (ETH_MOSI)
    out += wire(el_pins[17][0], el_pins[17][1], el_pins[17][0] - gx(10), el_pins[17][1])
    out += label("ETH_MOSI", el_pins[17][0] - gx(10), el_pins[17][1], rot=0)

    # Pin 18: GPIO 12 (ETH_SCK)
    out += wire(el_pins[18][0], el_pins[18][1], el_pins[18][0] - gx(10), el_pins[18][1])
    out += label("ETH_SCK", el_pins[18][0] - gx(10), el_pins[18][1], rot=0)

    # Pin 19: GPIO 13 (ETH_MISO)
    out += wire(el_pins[19][0], el_pins[19][1], el_pins[19][0] - gx(10), el_pins[19][1])
    out += label("ETH_MISO", el_pins[19][0] - gx(10), el_pins[19][1], rot=0)

    # Pin 20: GPIO 14 (ETH_INT)
    out += wire(el_pins[20][0], el_pins[20][1], el_pins[20][0] - gx(10), el_pins[20][1])
    out += label("ETH_INT", el_pins[20][0] - gx(10), el_pins[20][1], rot=0)

    # Pin 21: 5V IN
    out += wire(el_pins[21][0], el_pins[21][1], el_pins[21][0] - gx(8), el_pins[21][1])
    out += pwr_inst("power:+5V", "+5V", el_pins[21][0] - gx(8), el_pins[21][1], rot=0)

    # Pin 22: GND
    out += wire(el_pins[22][0], el_pins[22][1], el_pins[22][0] - gx(8), el_pins[22][1])
    out += pwr_inst("power:GND", "GND", el_pins[22][0] - gx(8), el_pins[22][1], rot=0)

    # Barra direita (U_ESP_R):
    # Pin 1: GND
    out += wire(er_pins[1][0], er_pins[1][1], er_pins[1][0] - gx(8), er_pins[1][1])
    out += pwr_inst("power:GND", "GND", er_pins[1][0] - gx(8), er_pins[1][1], rot=0)

    # Pin 2: GPIO 43 (NC)
    out += wire(er_pins[2][0], er_pins[2][1], er_pins[2][0] - gx(4), er_pins[2][1])
    out += no_connect(er_pins[2][0] - gx(4), er_pins[2][1])

    # Pin 3: GPIO 44 (NC)
    out += wire(er_pins[3][0], er_pins[3][1], er_pins[3][0] - gx(4), er_pins[3][1])
    out += no_connect(er_pins[3][0] - gx(4), er_pins[3][1])

    # Pin 4: GPIO 01 (BTN_CONFIG)
    out += wire(er_pins[4][0], er_pins[4][1], er_pins[4][0] - gx(10), er_pins[4][1])
    out += label("BTN_CONFIG", er_pins[4][0] - gx(10), er_pins[4][1], rot=0)

    # Pin 5: GPIO 02 (NC)
    out += wire(er_pins[5][0], er_pins[5][1], er_pins[5][0] - gx(4), er_pins[5][1])
    out += no_connect(er_pins[5][0] - gx(4), er_pins[5][1])

    # Pin 6: GPIO 42 (I2C_SCL)
    out += wire(er_pins[6][0], er_pins[6][1], er_pins[6][0] - gx(10), er_pins[6][1])
    out += label("I2C_SCL", er_pins[6][0] - gx(10), er_pins[6][1], rot=0)

    # Pin 7: GPIO 41 (I2C_SDA)
    out += wire(er_pins[7][0], er_pins[7][1], er_pins[7][0] - gx(10), er_pins[7][1])
    out += label("I2C_SDA", er_pins[7][0] - gx(10), er_pins[7][1], rot=0)

    # Pin 8..18: GPIOs livres (NC)
    for p in range(8, 19):
        out += wire(er_pins[p][0], er_pins[p][1], er_pins[p][0] - gx(4), er_pins[p][1])
        out += no_connect(er_pins[p][0] - gx(4), er_pins[p][1])

    # Pin 19: GPIO 20 (LORA_M1)
    out += wire(er_pins[19][0], er_pins[19][1], er_pins[19][0] - gx(10), er_pins[19][1])
    out += label("LORA_M1", er_pins[19][0] - gx(10), er_pins[19][1], rot=0)

    # Pin 20: GPIO 19 (LORA_M0)
    out += wire(er_pins[20][0], er_pins[20][1], er_pins[20][0] - gx(10), er_pins[20][1])
    out += label("LORA_M0", er_pins[20][0] - gx(10), er_pins[20][1], rot=0)

    # Pin 21: GND
    out += wire(er_pins[21][0], er_pins[21][1], er_pins[21][0] - gx(8), er_pins[21][1])
    out += pwr_inst("power:GND", "GND", er_pins[21][0] - gx(8), er_pins[21][1], rot=0)

    # Pin 22: GND
    out += wire(er_pins[22][0], er_pins[22][1], er_pins[22][0] - gx(8), er_pins[22][1])
    out += pwr_inst("power:GND", "GND", er_pins[22][0] - gx(8), er_pins[22][1], rot=0)

    # =========================================================================
    # BLOCO 3: TRANSCEPTOR LORA LONGO ALCANCE (EBYTE E220-900T22D / LLCC68 915 MHz)
    # =========================================================================
    b3_x1, b3_y1 = gx(16), gy(80)
    b3_x2, b3_y2 = gx(150), gy(144)
    out += rect_box(b3_x1, b3_y1, b3_x2, b3_y2)
    out += text_block("BLOCO 3: COMUNICACAO SEM FIO LORA LONGO ALCANCE (CDEBYTE E220-900T22D)", b3_x1 + gx(3), b3_y1 + gy(4), size=1.6, bold=True)
    out += text_block("Chip Semtech LLCC68 | Frequencia 915 MHz | Potencia 22 dBm (160 mW) | Alcance ate 5 km | Interface UART", b3_x1 + gx(3), b3_y1 + gy(7), size=1.05, italic=True)

    lora_x, lora_y = gx(70), gy(114)
    out += sym_inst(
        "Connector_Generic:Conn_01x07", "U_LORA", "E220-900T22D",
        "Connector_PinHeader_2.54mm:PinHeader_1x07_P2.54mm_Vertical",
        lora_x, lora_y, rot=0, desc="Modulo Transceptor LoRa EBYTE E220-900T22D (DIP 7 pinos)",
        ref_offset=(lora_x, lora_y - gy(9)), val_offset=(lora_x, lora_y + gy(9))
    )
    lo_pins = {p: get_pin_canvas_coord(lora_x, lora_y, "Connector_Generic:Conn_01x07", p) for p in range(1, 8)}

    # Resistor pull-down R_M0 (10k)
    rm0_x, rm0_y = lo_pins[1][0] - gx(8), lo_pins[1][1] + gy(6)
    rm0_p1 = get_pin_canvas_coord(rm0_x, rm0_y, "Device:R", 1)
    rm0_p2 = get_pin_canvas_coord(rm0_x, rm0_y, "Device:R", 2)

    # Pin 1: M0
    out += wire(lo_pins[1][0], lo_pins[1][1], rm0_p1[0], lo_pins[1][1])
    out += wire(rm0_p1[0], lo_pins[1][1], lo_pins[1][0] - gx(14), lo_pins[1][1])
    out += label("LORA_M0", lo_pins[1][0] - gx(14), lo_pins[1][1], rot=0)

    out += sym_inst(
        "Device:R", "R_M0", "10k",
        "Resistor_THT:R_Axial_DIN0207_L6.3mm_D2.5mm_P7.62mm_Horizontal",
        rm0_x, rm0_y, rot=0, desc="Pull-down para modo normal padrao",
        ref_offset=(rm0_x + gx(4), rm0_y - gy(2)), val_offset=(rm0_x + gx(4), rm0_y + gy(2))
    )
    out += wire(rm0_p1[0], rm0_p1[1], rm0_p1[0], lo_pins[1][1])
    out += junction(rm0_p1[0], lo_pins[1][1])
    out += wire(rm0_p2[0], rm0_p2[1], rm0_p2[0], rm0_p2[1] + gy(3))
    out += pwr_inst("power:GND", "GND", rm0_p2[0], rm0_p2[1] + gy(3), rot=0)

    # Pin 2: M1
    out += wire(lo_pins[2][0], lo_pins[2][1], lo_pins[2][0] - gx(14), lo_pins[2][1])
    out += label("LORA_M1", lo_pins[2][0] - gx(14), lo_pins[2][1], rot=0)

    # Pin 3: RXD (LoRa RX <- ESP32 TX)
    out += wire(lo_pins[3][0], lo_pins[3][1], lo_pins[3][0] - gx(14), lo_pins[3][1])
    out += label("LORA_TX", lo_pins[3][0] - gx(14), lo_pins[3][1], rot=0)

    # Pin 4: TXD (LoRa TX -> ESP32 RX)
    out += wire(lo_pins[4][0], lo_pins[4][1], lo_pins[4][0] - gx(14), lo_pins[4][1])
    out += label("LORA_RX", lo_pins[4][0] - gx(14), lo_pins[4][1], rot=0)

    # Pin 5: AUX
    out += wire(lo_pins[5][0], lo_pins[5][1], lo_pins[5][0] - gx(14), lo_pins[5][1])
    out += label("LORA_AUX", lo_pins[5][0] - gx(14), lo_pins[5][1], rot=0)

    # Pin 6: VCC (5V para maxima potencia RF)
    out += wire(lo_pins[6][0], lo_pins[6][1], lo_pins[6][0] - gx(8), lo_pins[6][1])
    out += pwr_inst("power:+5V", "+5V", lo_pins[6][0] - gx(8), lo_pins[6][1], rot=0)

    # Pin 7: GND
    out += wire(lo_pins[7][0], lo_pins[7][1], lo_pins[7][0] - gx(8), lo_pins[7][1])
    out += pwr_inst("power:GND", "GND", lo_pins[7][0] - gx(8), lo_pins[7][1], rot=0)

    # Capacitor local C4 (100nF)
    c4_x, c4_y = gx(100), gy(118)
    out += sym_inst(
        "Device:C", "C4", "100nF", "Capacitor_THT:C_Disc_D3.8mm_W2.6mm_P2.50mm",
        c4_x, c4_y, rot=0, desc="Capacitor de desacoplamento do modulo LoRa",
        ref_offset=(c4_x + gx(4), c4_y - gy(2)), val_offset=(c4_x + gx(4), c4_y + gy(2))
    )
    c4_p1 = get_pin_canvas_coord(c4_x, c4_y, "Device:C", 1)
    c4_p2 = get_pin_canvas_coord(c4_x, c4_y, "Device:C", 2)
    out += wire(c4_p1[0], c4_p1[1], c4_p1[0], c4_p1[1] - gy(3))
    out += pwr_inst("power:+5V", "+5V", c4_p1[0], c4_p1[1] - gy(3), rot=0)
    out += wire(c4_p2[0], c4_p2[1], c4_p2[0], c4_p2[1] + gy(3))
    out += pwr_inst("power:GND", "GND", c4_p2[0], c4_p2[1] + gy(3), rot=0)

    out += text_block("PINAGEM EBYTE E220-900T22D (PADRAO DIP 7 PINOS):", gx(92), gy(96), size=1.1, bold=True)
    out += text_block("1: M0 | 2: M1 | 3: RXD | 4: TXD | 5: AUX | 6: VCC (5V) | 7: GND", gx(92), gy(100), size=0.9)
    out += text_block("Modo Normal: M0=0, M1=0 (Transmissao transparente serial automatica)", gx(92), gy(104), size=0.9, italic=True)

    # =========================================================================
    # BLOCO 4: SOCKET DE EXPANSÃO ETHERNET SPI (W5500 INDUSTRIAL RJ45)
    # =========================================================================
    b4_x1, b4_y1 = gx(16), gy(150)
    b4_x2, b4_y2 = gx(150), gy(210)
    out += rect_box(b4_x1, b4_y1, b4_x2, b4_y2)
    out += text_block("BLOCO 4: INTERFACE ETHERNET CABEADA (SOCKET MODULO W5500 SPI)", b4_x1 + gx(3), b4_y1 + gy(4), size=1.6, bold=True)
    out += text_block("Conexao cabeada RJ45 10/100 Mbps com protocolo TCP/IP embarcado em hardware | Ideal para chão de fábrica", b4_x1 + gx(3), b4_y1 + gy(7), size=1.05, italic=True)

    eth_x, eth_y = gx(70), gy(184)
    out += sym_inst(
        "Connector_Generic:Conn_01x08", "J_ETH", "W5500_Ethernet_SPI",
        "Connector_PinHeader_2.54mm:PinHeader_1x08_P2.54mm_Vertical",
        eth_x, eth_y, rot=0, desc="Socket para modulo Ethernet W5500 SPI (1x08 pinos)",
        ref_offset=(eth_x, eth_y - gy(9)), val_offset=(eth_x, eth_y + gy(9))
    )
    et_pins = {p: get_pin_canvas_coord(eth_x, eth_y, "Connector_Generic:Conn_01x08", p) for p in range(1, 9)}

    # Pin 1: 3V3
    out += wire(et_pins[1][0], et_pins[1][1], et_pins[1][0] - gx(8), et_pins[1][1])
    out += pwr_inst("power:+3V3", "+3V3", et_pins[1][0] - gx(8), et_pins[1][1], rot=0)

    # Pin 2: GND
    out += wire(et_pins[2][0], et_pins[2][1], et_pins[2][0] - gx(8), et_pins[2][1])
    out += pwr_inst("power:GND", "GND", et_pins[2][0] - gx(8), et_pins[2][1], rot=0)

    # Pin 3: SCK
    out += wire(et_pins[3][0], et_pins[3][1], et_pins[3][0] - gx(14), et_pins[3][1])
    out += label("ETH_SCK", et_pins[3][0] - gx(14), et_pins[3][1], rot=0)

    # Pin 4: MOSI
    out += wire(et_pins[4][0], et_pins[4][1], et_pins[4][0] - gx(14), et_pins[4][1])
    out += label("ETH_MOSI", et_pins[4][0] - gx(14), et_pins[4][1], rot=0)

    # Pin 5: MISO
    out += wire(et_pins[5][0], et_pins[5][1], et_pins[5][0] - gx(14), et_pins[5][1])
    out += label("ETH_MISO", et_pins[5][0] - gx(14), et_pins[5][1], rot=0)

    # Pin 6: CS
    out += wire(et_pins[6][0], et_pins[6][1], et_pins[6][0] - gx(14), et_pins[6][1])
    out += label("ETH_CS", et_pins[6][0] - gx(14), et_pins[6][1], rot=0)

    # Pin 7: INT
    out += wire(et_pins[7][0], et_pins[7][1], et_pins[7][0] - gx(14), et_pins[7][1])
    out += label("ETH_INT", et_pins[7][0] - gx(14), et_pins[7][1], rot=0)

    # Pin 8: RST
    out += wire(et_pins[8][0], et_pins[8][1], et_pins[8][0] - gx(14), et_pins[8][1])
    out += label("ETH_RST", et_pins[8][0] - gx(14), et_pins[8][1], rot=0)

    # =========================================================================
    # BLOCO 5: IHM LOCAL & DIAGNÓSTICO VISUAL (OLED 0.96", LEDS & BUZZER)
    # =========================================================================
    b5_x1, b5_y1 = gx(158), gy(130)
    b5_x2, b5_y2 = gx(310), gy(210)
    out += rect_box(b5_x1, b5_y1, b5_x2, b5_y2)
    out += text_block("BLOCO 5: DIAGNOSTICO VISUAL & IHM LOCAL (DISPLAY OLED 0.96, LEDS DE STATUS E BUZZER)", b5_x1 + gx(3), b5_y1 + gy(4), size=1.6, bold=True)
    out += text_block("Display I2C para IP, RSSI LoRa e status MQTT | LEDs de atividade em tempo real | Botao de Modo AP", b5_x1 + gx(3), b5_y1 + gy(7), size=1.05, italic=True)

    # Conector OLED 1x04 (GND, VCC, SCL, SDA)
    oled_x, oled_y = gx(185), gy(155)
    out += sym_inst(
        "Connector_Generic:Conn_01x04", "J_OLED", "OLED_SSD1306_I2C",
        "Connector_PinHeader_2.54mm:PinHeader_1x04_P2.54mm_Vertical",
        oled_x, oled_y, rot=0, desc="Conector 1x04 para Display OLED 0.96 I2C",
        ref_offset=(oled_x, oled_y - gy(6)), val_offset=(oled_x, oled_y + gy(6))
    )
    ol_pins = {p: get_pin_canvas_coord(oled_x, oled_y, "Connector_Generic:Conn_01x04", p) for p in range(1, 5)}

    # OLED Pin 1: GND
    out += wire(ol_pins[1][0], ol_pins[1][1], ol_pins[1][0] - gx(8), ol_pins[1][1])
    out += pwr_inst("power:GND", "GND", ol_pins[1][0] - gx(8), ol_pins[1][1], rot=0)

    # OLED Pin 2: 3V3
    out += wire(ol_pins[2][0], ol_pins[2][1], ol_pins[2][0] - gx(8), ol_pins[2][1])
    out += pwr_inst("power:+3V3", "+3V3", ol_pins[2][0] - gx(8), ol_pins[2][1], rot=0)

    # OLED Pin 3: SCL
    out += wire(ol_pins[3][0], ol_pins[3][1], ol_pins[3][0] - gx(14), ol_pins[3][1])
    out += label("I2C_SCL", ol_pins[3][0] - gx(14), ol_pins[3][1], rot=0)

    # OLED Pin 4: SDA
    out += wire(ol_pins[4][0], ol_pins[4][1], ol_pins[4][0] - gx(14), ol_pins[4][1])
    out += label("I2C_SDA", ol_pins[4][0] - gx(14), ol_pins[4][1], rot=0)

    # Pull-ups I2C
    rscl_x, rscl_y = gx(205), gy(150)
    out += sym_inst(
        "Device:R", "R_SCL", "4.7k", "Resistor_THT:R_Axial_DIN0207_L6.3mm_D2.5mm_P7.62mm_Horizontal",
        rscl_x, rscl_y, rot=0, desc="Pull-up I2C SCL",
        ref_offset=(rscl_x + gx(4), rscl_y - gy(2)), val_offset=(rscl_x + gx(4), rscl_y + gy(2))
    )
    rscl_p1 = get_pin_canvas_coord(rscl_x, rscl_y, "Device:R", 1)
    rscl_p2 = get_pin_canvas_coord(rscl_x, rscl_y, "Device:R", 2)
    out += wire(rscl_p1[0], rscl_p1[1], rscl_p1[0], rscl_p1[1] - gy(3))
    out += pwr_inst("power:+3V3", "+3V3", rscl_p1[0], rscl_p1[1] - gy(3), rot=0)
    out += wire(rscl_p2[0], rscl_p2[1], rscl_p2[0], ol_pins[3][1])
    out += wire(rscl_p2[0], ol_pins[3][1], ol_pins[3][0] - gx(14), ol_pins[3][1])
    out += junction(rscl_p2[0], ol_pins[3][1])

    rsda_x, rsda_y = gx(215), gy(150)
    out += sym_inst(
        "Device:R", "R_SDA", "4.7k", "Resistor_THT:R_Axial_DIN0207_L6.3mm_D2.5mm_P7.62mm_Horizontal",
        rsda_x, rsda_y, rot=0, desc="Pull-up I2C SDA",
        ref_offset=(rsda_x + gx(4), rsda_y - gy(2)), val_offset=(rsda_x + gx(4), rsda_y + gy(2))
    )
    rsda_p1 = get_pin_canvas_coord(rsda_x, rsda_y, "Device:R", 1)
    rsda_p2 = get_pin_canvas_coord(rsda_x, rsda_y, "Device:R", 2)
    out += wire(rsda_p1[0], rsda_p1[1], rsda_p1[0], rsda_p1[1] - gy(3))
    out += pwr_inst("power:+3V3", "+3V3", rsda_p1[0], rsda_p1[1] - gy(3), rot=0)
    out += wire(rsda_p2[0], rsda_p2[1], rsda_p2[0], ol_pins[4][1])
    out += wire(rsda_p2[0], ol_pins[4][1], ol_pins[4][0] - gx(14), ol_pins[4][1])
    out += junction(rsda_p2[0], ol_pins[4][1])

    # 3x LEDs de Status
    leds_data = [
        ("D_LORA", "Azul_3mm", "LED_LORA_RX", "R_LORA", gx(185), gy(180)),
        ("D_MQTT", "Amarelo_3mm", "LED_MQTT_OK", "R_MQTT", gx(215), gy(180)),
        ("D_NET",  "Verde_3mm", "LED_NET_ACT", "R_NET",  gx(245), gy(180)),
    ]
    for d_ref, d_val, sig_name, r_ref, lx, ly in leds_data:
        out += sym_inst(
            "Device:LED", d_ref, d_val, "LED_THT:LED_D3.0mm",
            lx, ly, rot=0, desc=f"LED indicador {sig_name}",
            ref_offset=(lx, ly - gy(3)), val_offset=(lx, ly + gy(3))
        )
        l_p1 = get_pin_canvas_coord(lx, ly, "Device:LED", 1)
        l_p2 = get_pin_canvas_coord(lx, ly, "Device:LED", 2)

        rx = lx + gx(12)
        out += sym_inst(
            "Device:R", r_ref, "330", "Resistor_THT:R_Axial_DIN0207_L6.3mm_D2.5mm_P7.62mm_Horizontal",
            rx, ly, rot=90, desc="Resistor limitador de corrente do LED",
            ref_offset=(rx, ly - gy(3)), val_offset=(rx, ly + gy(3))
        )
        r_p1 = get_pin_canvas_coord(rx, ly, "Device:R", 1, angle_deg=90)
        r_p2 = get_pin_canvas_coord(rx, ly, "Device:R", 2, angle_deg=90)

        out += wire(l_p1[0], l_p1[1], l_p1[0] - gx(6), l_p1[1])
        out += label(sig_name, l_p1[0] - gx(6), l_p1[1], rot=0)
        out += wire(l_p2[0], l_p2[1], r_p1[0], r_p1[1])
        out += wire(r_p2[0], r_p2[1], r_p2[0] + gx(4), r_p2[1])
        out += pwr_inst("power:GND", "GND", r_p2[0] + gx(4), r_p2[1], rot=0)

    # Botao SW_CFG
    btn_x, btn_y = gx(280), gy(155)
    out += sym_inst(
        "Switch:SW_Push", "SW_CFG", "Botao_Config_AP",
        "Button_Switch_THT:SW_PUSH_6mm_H5mm",
        btn_x, btn_y, rot=0, desc="Botao tactil para provisionamento Wi-Fi e modo AP",
        ref_offset=(btn_x, btn_y - gy(4)), val_offset=(btn_x, btn_y + gy(4))
    )
    sw_p1 = get_pin_canvas_coord(btn_x, btn_y, "Switch:SW_Push", 1)
    sw_p2 = get_pin_canvas_coord(btn_x, btn_y, "Switch:SW_Push", 2)

    out += wire(sw_p1[0], sw_p1[1], sw_p1[0] - gx(6), sw_p1[1])
    out += label("BTN_CONFIG", sw_p1[0] - gx(6), sw_p1[1], rot=0)

    out += wire(sw_p2[0], sw_p2[1], sw_p2[0] + gx(4), sw_p2[1])
    out += pwr_inst("power:GND", "GND", sw_p2[0] + gx(4), sw_p2[1], rot=0)

    # Buzzer BZ1
    bz_x, bz_y = gx(280), gy(185)
    out += sym_inst(
        "Device:Buzzer", "BZ1", "Buzzer_Ativo_5V",
        "Buzzer_Beeper:Buzzer_12x9.5RM7.6",
        bz_x, bz_y, rot=0, desc="Buzzer piezo ativo 5V para alertas sonoros",
        ref_offset=(bz_x + gx(4), bz_y - gy(3)), val_offset=(bz_x + gx(4), bz_y + gy(3))
    )
    bz_p1 = get_pin_canvas_coord(bz_x, bz_y, "Device:Buzzer", 1)
    bz_p2 = get_pin_canvas_coord(bz_x, bz_y, "Device:Buzzer", 2)

    out += wire(bz_p1[0], bz_p1[1], bz_p1[0] - gx(6), bz_p1[1])
    out += label("BUZZER", bz_p1[0] - gx(6), bz_p1[1], rot=0)
    out += wire(bz_p2[0], bz_p2[1], bz_p2[0] - gx(6), bz_p2[1])
    out += pwr_inst("power:GND", "GND", bz_p2[0] - gx(6), bz_p2[1], rot=0)

    out += ")\n"

    with open(SCH_FILE, "w", encoding="utf-8") as f:
        f.write(out)
    print(f"[OK] Esquemático salvo: {SCH_FILE}")

def run_erc():
    print("--> [2/7] Executando kicad-cli sch erc...")
    erc_json = os.path.join(GATEWAY_DIR, "erc_report.json")
    cmd = [KICAD_CLI, "sch", "erc", "--output", erc_json, "--format", "json", SCH_FILE]
    res = subprocess.run(cmd, capture_output=True, text=True)
    if os.path.exists(erc_json):
        with open(erc_json, "r", encoding="utf-8") as f:
            d = json.load(f)
        violations = []
        for s in d.get("sheets", []):
            violations.extend(s.get("violations", []))
        if len(violations) > 0:
            print(f"ATENÇÃO: {len(violations)} violações ERC encontradas:")
            for v in violations:
                print(f"  [{v.get('severity')}] {v.get('type')}: {v.get('description')}")
            return False
        else:
            print("[PASS] ERC 100% Limpo (0 Erros, 0 Avisos)!")
            return True
    return False

def export_netlist():
    print("--> [3/7] Exportando netlist do esquemático...")
    net_file = os.path.join(GATEWAY_DIR, "amemiya_gateway.net")
    cmd = [KICAD_CLI, "sch", "export", "netlist", "--format", "kicadsexpr", "--output", net_file, SCH_FILE]
    subprocess.run(cmd, check=True)
    print(f"[OK] Netlist exportado: {net_file}")
    return net_file

def parse_netlist(net_file):
    with open(net_file, encoding='utf-8') as f:
        lines = f.readlines()

    in_nets = False
    current_net = None
    pad_nets = {}
    all_nets = set(["GND"])

    for line in lines:
        line_s = line.strip()
        if line_s.startswith('(nets'):
            in_nets = True
            continue
        if not in_nets:
            continue
        if line_s.startswith('(net '):
            parts = line_s.split('"')
            if len(parts) >= 4:
                current_net = parts[3]
                all_nets.add(current_net)
        elif line_s.startswith('(node '):
            parts = line_s.split('"')
            if len(parts) >= 4:
                ref = parts[1]
                pin = parts[3]
                if ref not in pad_nets:
                    pad_nets[ref] = {}
                pad_nets[ref][pin] = current_net

    for h in ["H1", "H2", "H3", "H4"]:
        pad_nets[h] = {"1": "GND"}

    return pad_nets, all_nets

def build_board(pad_nets, all_nets):
    print("--> [4/7] Construindo placa PCB (85.0 x 60.0 mm) e posicionando footprints...")
    b = pcbnew.BOARD()
    b.GetDesignSettings().SetBoardThickness(pcbnew.FromMM(1.6))

    # Edge.Cuts (85.0 x 60.0 mm)
    pts = [(0.0, 0.0), (85.0, 0.0), (85.0, 60.0), (0.0, 60.0)]
    for i in range(4):
        p1, p2 = pts[i], pts[(i+1)%4]
        seg = pcbnew.PCB_SHAPE(b)
        seg.SetShape(pcbnew.SHAPE_T_SEGMENT)
        seg.SetLayer(pcbnew.Edge_Cuts)
        seg.SetWidth(pcbnew.FromMM(0.15))
        seg.SetStart(pcbnew.VECTOR2I(pcbnew.FromMM(p1[0]), pcbnew.FromMM(p1[1])))
        seg.SetEnd(pcbnew.VECTOR2I(pcbnew.FromMM(p2[0]), pcbnew.FromMM(p2[1])))
        b.Add(seg)

    # Netclasses e regras de projeto (IPC-2221)
    net_settings = b.GetDesignSettings().m_NetSettings
    default_nc = net_settings.GetDefaultNetclass()
    default_nc.SetTrackWidth(pcbnew.FromMM(0.254))  # 10 mil
    default_nc.SetClearance(pcbnew.FromMM(0.20))    # 8 mil
    default_nc.SetViaDiameter(pcbnew.FromMM(0.80))
    default_nc.SetViaDrill(pcbnew.FromMM(0.40))

    pnc = pcbnew.NETCLASS("Power")
    pnc.SetTrackWidth(pcbnew.FromMM(0.50))          # 20 mil
    pnc.SetClearance(pcbnew.FromMM(0.25))           # 10 mil
    pnc.SetViaDiameter(pcbnew.FromMM(0.80))
    pnc.SetViaDrill(pcbnew.FromMM(0.40))
    net_settings.SetNetclass("Power", pnc)

    for pnet in ["+5V", "+3V3", "GND", "Net-(D1-A)", "Net-(D1-K)", "Net-(D2-K)"]:
        net_settings.SetNetclassPatternAssignment(pnet, "Power")

    # Registrar redes
    net_obj_map = {}
    for nname in sorted(all_nets):
        net_item = pcbnew.NETINFO_ITEM(b, nname)
        b.Add(net_item)
        net_obj_map[nname] = net_item

    # Layout com clearance formal verificado (0 DRC)
    fp_layout = [
        # Furos de montagem M3
        {"ref": "H1", "val": "MountingHole_M3", "lib": "MountingHole", "fp": "MountingHole_3.2mm_M3_Pad", "x": 4.0, "y": 4.0, "rot": 0},
        {"ref": "H2", "val": "MountingHole_M3", "lib": "MountingHole", "fp": "MountingHole_3.2mm_M3_Pad", "x": 81.0, "y": 4.0, "rot": 0},
        {"ref": "H3", "val": "MountingHole_M3", "lib": "MountingHole", "fp": "MountingHole_3.2mm_M3_Pad", "x": 81.0, "y": 56.0, "rot": 0},
        {"ref": "H4", "val": "MountingHole_M3", "lib": "MountingHole", "fp": "MountingHole_3.2mm_M3_Pad", "x": 4.0, "y": 56.0, "rot": 0},

        # Soquetes ESP32-S3 (Horizontal, passo 25.4 mm entre barras)
        {"ref": "U_ESP_L", "val": "ESP32-S3_Left", "lib": "Connector_PinHeader_2.54mm", "fp": "PinHeader_1x22_P2.54mm_Vertical", "x": 14.0, "y": 20.0, "rot": 90},
        {"ref": "U_ESP_R", "val": "ESP32-S3_Right", "lib": "Connector_PinHeader_2.54mm", "fp": "PinHeader_1x22_P2.54mm_Vertical", "x": 14.0, "y": 45.4, "rot": 90},

        # Setor de Alimentação (Superior Esquerdo)
        {"ref": "J_PWR_IN", "val": "TerminalBlock_9-24V", "lib": "TerminalBlock_Phoenix", "fp": "TerminalBlock_Phoenix_MKDS-1,5-2-5.08_1x02_P5.08mm_Horizontal", "x": 13.0, "y": 8.5, "rot": 270},
        {"ref": "D1", "val": "1N5819", "lib": "Diode_THT", "fp": "D_DO-41_SOD81_P10.16mm_Horizontal", "x": 20.5, "y": 5.0, "rot": 0},
        {"ref": "C1", "val": "470uF_25V", "lib": "Capacitor_THT", "fp": "CP_Radial_D6.3mm_P2.50mm", "x": 22.0, "y": 12.5, "rot": 0},
        {"ref": "J_USB_PWR", "val": "Conn_USB_5V_AUX", "lib": "Connector_PinHeader_2.54mm", "fp": "PinHeader_1x02_P2.54mm_Vertical", "x": 5.0, "y": 15.0, "rot": 0},
        {"ref": "U_BUCK", "val": "MP1584EN_Buck", "lib": "Connector_PinHeader_2.54mm", "fp": "PinHeader_1x04_P2.54mm_Vertical", "x": 35.0, "y": 5.0, "rot": 90},
        {"ref": "D2", "val": "1N5819", "lib": "Diode_THT", "fp": "D_DO-41_SOD81_P10.16mm_Horizontal", "x": 32.5, "y": 12.5, "rot": 0},
        {"ref": "C2", "val": "100nF", "lib": "Capacitor_THT", "fp": "C_Disc_D3.8mm_W2.6mm_P2.50mm", "x": 46.0, "y": 12.5, "rot": 0},
        {"ref": "C3", "val": "100nF", "lib": "Capacitor_THT", "fp": "C_Disc_D3.8mm_W2.6mm_P2.50mm", "x": 22.0, "y": 25.0, "rot": 0},
        {"ref": "D_PWR", "val": "LED_PWR_Verde", "lib": "LED_THT", "fp": "LED_D3.0mm", "x": 5.0, "y": 24.0, "rot": 0},
        {"ref": "R_PWR", "val": "1k", "lib": "Resistor_THT", "fp": "R_Axial_DIN0207_L6.3mm_D2.5mm_P7.62mm_Horizontal", "x": 5.0, "y": 33.0, "rot": 0},
        {"ref": "R_M0", "val": "10k", "lib": "Resistor_THT", "fp": "R_Axial_DIN0207_L6.3mm_D2.5mm_P7.62mm_Horizontal", "x": 5.0, "y": 42.0, "rot": 0},

        # Setor LoRa CDEBYTE E220-900T22D (Superior Direito)
        {"ref": "U_LORA", "val": "E220-900T22D", "lib": "Connector_PinHeader_2.54mm", "fp": "PinHeader_1x07_P2.54mm_Vertical", "x": 46.5, "y": 5.0, "rot": 90},
        {"ref": "C4", "val": "100nF", "lib": "Capacitor_THT", "fp": "C_Disc_D3.8mm_W2.6mm_P2.50mm", "x": 56.0, "y": 12.5, "rot": 0},

        # Setor Ethernet W5500 & Buzzer
        {"ref": "BZ1", "val": "Buzzer_5V", "lib": "Buzzer_Beeper", "fp": "Buzzer_12x9.5RM7.6", "x": 74.0, "y": 10.0, "rot": 180},
        {"ref": "J_ETH", "val": "W5500_Ethernet", "lib": "Connector_PinHeader_2.54mm", "fp": "PinHeader_1x08_P2.54mm_Vertical", "x": 78.0, "y": 24.0, "rot": 0},

        # Painel Frontal (Inferior: LEDs, OLED, Botão)
        {"ref": "D_LORA", "val": "Azul_3mm", "lib": "LED_THT", "fp": "LED_D3.0mm", "x": 11.0, "y": 55.0, "rot": 0},
        {"ref": "R_LORA", "val": "330", "lib": "Resistor_THT", "fp": "R_Axial_DIN0207_L6.3mm_D2.5mm_P7.62mm_Horizontal", "x": 11.0, "y": 50.0, "rot": 0},
        {"ref": "D_MQTT", "val": "Amarelo_3mm", "lib": "LED_THT", "fp": "LED_D3.0mm", "x": 21.0, "y": 55.0, "rot": 0},
        {"ref": "R_MQTT", "val": "330", "lib": "Resistor_THT", "fp": "R_Axial_DIN0207_L6.3mm_D2.5mm_P7.62mm_Horizontal", "x": 21.0, "y": 50.0, "rot": 0},
        {"ref": "D_NET", "val": "Verde_3mm", "lib": "LED_THT", "fp": "LED_D3.0mm", "x": 31.0, "y": 55.0, "rot": 0},
        {"ref": "R_NET", "val": "330", "lib": "Resistor_THT", "fp": "R_Axial_DIN0207_L6.3mm_D2.5mm_P7.62mm_Horizontal", "x": 31.0, "y": 50.0, "rot": 0},
        {"ref": "R_SCL", "val": "4.7k", "lib": "Resistor_THT", "fp": "R_Axial_DIN0207_L6.3mm_D2.5mm_P7.62mm_Horizontal", "x": 41.0, "y": 50.0, "rot": 0},
        {"ref": "R_SDA", "val": "4.7k", "lib": "Resistor_THT", "fp": "R_Axial_DIN0207_L6.3mm_D2.5mm_P7.62mm_Horizontal", "x": 41.0, "y": 55.0, "rot": 0},
        {"ref": "J_OLED", "val": "OLED_SSD1306", "lib": "Connector_PinHeader_2.54mm", "fp": "PinHeader_1x04_P2.54mm_Vertical", "x": 53.0, "y": 52.5, "rot": 90},
        {"ref": "SW_CFG", "val": "Botao_Config", "lib": "Button_Switch_THT", "fp": "SW_PUSH_6mm_H5mm", "x": 66.0, "y": 52.0, "rot": 0},
    ]

    for cfg in fp_layout:
        lib_path = os.path.join(KICAD_FP_DIR, f"{cfg['lib']}.pretty")
        fp = pcbnew.FootprintLoad(lib_path, cfg["fp"])
        fp.SetReference(cfg["ref"])
        fp.Reference().SetVisible(False)
        fp.SetValue(cfg["val"])
        fp.Value().SetVisible(False)
        fp.SetPosition(pcbnew.VECTOR2I(pcbnew.FromMM(cfg["x"]), pcbnew.FromMM(cfg["y"])))
        fp.SetOrientationDegrees(cfg["rot"])

        ref = cfg["ref"]
        if ref in pad_nets:
            ref_nets = pad_nets[ref]
            for pad in fp.Pads():
                pnum = pad.GetNumber()
                if pnum in ref_nets:
                    nname = ref_nets[pnum]
                    if nname and nname in net_obj_map:
                        pad.SetNet(net_obj_map[nname])

        b.Add(fp)

    pcbnew.SaveBoard(PCB_FILE, b)
    print(f"[OK] Placa inicial criada com {len(b.GetFootprints())} footprints.")
    return b

def autoroute(b):
    print("--> [5/7] Executando Freerouting (Autorouter)...")
    dsn_abs = os.path.abspath(DSN_FILE)
    ses_abs = os.path.abspath(SES_FILE)

    if os.path.exists(ses_abs):
        os.remove(ses_abs)

    pcbnew.ExportSpecctraDSN(b, dsn_abs)
    print(f"[OK] Specctra DSN exportado: {dsn_abs}")

    cmd = [JAVA_EXE, "-jar", FREEROUTING_JAR, "-de", dsn_abs, "-do", ses_abs, "-mp", "60", "-l", "1"]
    print("Freerouting CMD:", " ".join(cmd))
    res = subprocess.run(cmd, capture_output=True, text=True)
    if not os.path.exists(ses_abs):
        print(res.stderr)
        raise RuntimeError("Freerouting não gerou o arquivo SES")

    print("Importando rotas SES...")
    ret_ses = pcbnew.ImportSpecctraSES(b, ses_abs)
    if not ret_ses:
        raise RuntimeError("Falha ao importar Specctra SES na placa")
    print(f"[OK] Rotas importadas: {len(b.GetTracks())} trilhas e vias criadas.")

def add_ground_planes(b):
    print("--> [6/7] Injetando planos de terra sólidos (GND) em F.Cu e B.Cu...")
    gnd_net = b.FindNet("GND")
    if not gnd_net:
        raise ValueError("Rede GND não encontrada na placa")

    for layer in [pcbnew.B_Cu, pcbnew.F_Cu]:
        z = pcbnew.ZONE(b)
        z.SetLayer(layer)
        z.SetNet(gnd_net)
        z.SetAssignedPriority(0)
        z.SetPadConnection(pcbnew.ZONE_CONNECTION_THERMAL)
        z.SetThermalReliefSpokeWidth(pcbnew.FromMM(0.30))
        z.SetThermalReliefGap(pcbnew.FromMM(0.20))
        z.SetLocalClearance(pcbnew.FromMM(0.25))
        z.SetMinThickness(pcbnew.FromMM(0.20))
        ol = z.Outline()
        ol.NewOutline()
        ol.Append(pcbnew.FromMM(0.4), pcbnew.FromMM(0.4))
        ol.Append(pcbnew.FromMM(84.6), pcbnew.FromMM(0.4))
        ol.Append(pcbnew.FromMM(84.6), pcbnew.FromMM(59.6))
        ol.Append(pcbnew.FromMM(0.4), pcbnew.FromMM(59.6))
        b.Add(z)

    pcbnew.SaveBoard(PCB_FILE, b)
    b_loaded = pcbnew.LoadBoard(PCB_FILE)
    zf = pcbnew.ZONE_FILLER(b_loaded)
    zf.Fill(b_loaded.Zones())
    pcbnew.SaveBoard(PCB_FILE, b_loaded)
    print("[OK] Planos de terra preenchidos com sucesso.")
    return b_loaded

def run_drc():
    print("--> [7/7] Executando KiCad Design Rules Check (DRC)...")
    drc_json = os.path.join(GATEWAY_DIR, "pcb_drc_report.json")
    cmd = [KICAD_CLI, "pcb", "drc", "--output", drc_json, "--format", "json", PCB_FILE]
    res = subprocess.run(cmd, capture_output=True, text=True)
    if os.path.exists(drc_json):
        with open(drc_json, "r", encoding="utf-8") as f:
            d = json.load(f)
        violations = d.get("violations", [])
        unconnected = d.get("unconnected_items", [])
        print(f"Total de violações DRC: {len(violations)}")
        print(f"Total de itens desconectados: {len(unconnected)}")
        for v in violations:
            print(f"  [{v.get('type')}] {v.get('description')}")
        for u in unconnected:
            print(f"  [unconnected] {u.get('description')}")
        if len(violations) == 0 and len(unconnected) == 0:
            print("[PASS] DRC 100% LIMPO (0 Erros, 0 Avisos, 0 Desconexões)!")
            return True
        else:
            return False
    return False

def export_fabrication():
    print("--> Exportando pacote de fabricação completo...")
    os.makedirs(GERBER_DIR, exist_ok=True)
    os.makedirs(POS_DIR, exist_ok=True)

    # 1. Gerbers
    layers = "F.Cu,B.Cu,F.Paste,B.Paste,F.SilkS,B.SilkS,F.Mask,B.Mask,Edge.Cuts"
    cmd_gerbers = [
        KICAD_CLI, "pcb", "export", "gerbers",
        "--output", GERBER_DIR,
        "--layers", layers,
        "--subtract-soldermask",
        "--precision", "6",
        PCB_FILE
    ]
    subprocess.run(cmd_gerbers, check=True)

    # 2. Drill Files
    cmd_drill = [
        KICAD_CLI, "pcb", "export", "drill",
        "--output", GERBER_DIR,
        "--format", "excellon",
        "--excellon-separate-th",
        "--generate-map",
        "--map-format", "pdf",
        PCB_FILE
    ]
    subprocess.run(cmd_drill, check=True)

    # 3. Gerbers ZIP
    zip_path = os.path.join(FAB_DIR, "amemiya_gateway_gerbers.zip")
    with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, _, files in os.walk(GERBER_DIR):
            for f in files:
                fp = os.path.join(root, f)
                arcname = os.path.relpath(fp, GERBER_DIR)
                zipf.write(fp, arcname)
    print(f"Criado {zip_path} ({os.path.getsize(zip_path)} bytes)")

    # 4. Pick & Place CSV
    cmd_pos = [
        KICAD_CLI, "pcb", "export", "pos",
        "--output", os.path.join(POS_DIR, "amemiya_gateway_positions.csv"),
        "--format", "csv",
        "--units", "mm",
        "--side", "both",
        PCB_FILE
    ]
    subprocess.run(cmd_pos, check=True)

    # 5. STEP 3D
    step_out = os.path.join(GATEWAY_DIR, "amemiya_gateway.step")
    cmd_step = [
        KICAD_CLI, "pcb", "export", "step",
        "--output", step_out,
        "--force",
        "--subst-models",
        "--include-tracks",
        "--include-pads",
        "--include-zones",
        PCB_FILE
    ]
    subprocess.run(cmd_step, check=True)
    print(f"Exportado modelo 3D STEP: {step_out}")

    # Copiar STEP para hardware/cad
    cad_dir = os.path.abspath(os.path.join(BASE_DIR, "..", "cad"))
    os.makedirs(cad_dir, exist_ok=True)
    target_step = os.path.join(cad_dir, "amemiya_gateway_pcb.step")
    shutil.copy2(step_out, target_step)
    print(f"Copiado STEP para CAD: {target_step}")

    # 6. Renders 3D Raytraced
    render_iso = os.path.join(ARTIFACT_DIR, "gateway_pcb_3d_isometric.png")
    render_top = os.path.join(ARTIFACT_DIR, "gateway_pcb_3d_top.png")
    render_bot = os.path.join(ARTIFACT_DIR, "gateway_pcb_3d_bottom.png")

    subprocess.run([
        KICAD_CLI, "pcb", "render",
        "--output", render_iso,
        "--width", "1920", "--height", "1080",
        "--quality", "high", "--perspective", "--floor",
        "--rotate", "45,0,45", "--zoom", "1.1",
        PCB_FILE
    ], check=True)

    subprocess.run([
        KICAD_CLI, "pcb", "render",
        "--output", render_top,
        "--width", "1920", "--height", "1080",
        "--quality", "high", "--side", "top", "--zoom", "1.1",
        PCB_FILE
    ], check=True)

    subprocess.run([
        KICAD_CLI, "pcb", "render",
        "--output", render_bot,
        "--width", "1920", "--height", "1080",
        "--quality", "high", "--side", "bottom", "--zoom", "1.1",
        PCB_FILE
    ], check=True)
    print("[OK] Renderizações 3D salvas no diretório de artefatos.")

    # 7. Renderizar PNG do Esquemático
    try:
        import fitz
        pdf_sch = os.path.join(GATEWAY_DIR, "amemiya_gateway_schematic.pdf")
        subprocess.run([KICAD_CLI, "sch", "export", "pdf", "--output", pdf_sch, SCH_FILE], check=True)
        doc = fitz.open(pdf_sch)
        page = doc[0]
        pix = page.get_pixmap(dpi=200)
        pix.save(os.path.join(ARTIFACT_DIR, "gateway_schematic.png"))
        print("[OK] Renderização do esquemático salva no diretório de artefatos.")
    except Exception as e:
        print(f"[WARN] Falha ao renderizar PNG do esquemático: {e}")

if __name__ == "__main__":
    generate_schematic()
    erc_ok = run_erc()
    if not erc_ok:
        sys.exit(1)
    net_file = export_netlist()
    pad_nets, all_nets = parse_netlist(net_file)
    board = build_board(pad_nets, all_nets)
    autoroute(board)
    board = add_ground_planes(board)
    drc_ok = run_drc()
    if drc_ok:
        export_fabrication()
    else:
        print("[ERRO] DRC falhou! Pacote de fabricação não exportado.")
        sys.exit(1)
    print("=== Pipeline Completo do Gateway Concluído com Sucesso ===")

