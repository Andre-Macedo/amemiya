#!/usr/bin/env python3
"""
Amemiya Main Node - Complete Modular Hierarchical Schematic Generator (KiCad 9)
Builds root schematic and 4 subsheets with strict 1.27mm (50-mil) grid, 100% ERC compliance,
and professional tier-1 visual aesthetics (zero text overlaps, balanced spacing, strictly 0 rot on text).

Architecture & Signal Flow:
  - Root: amemiya_main_node.kicad_sch (Architectural Block Diagram on A3)
  - Sheet 1: 01_Power_Supply.kicad_sch (BMS, 5V Step-Up, Decoupling, Battery Fuel Gauge)
  - Sheet 2: 02_Sensors_AFE.kicad_sch (SCT-013 Clamp AFE, Hall/Optical Tachometer, GX16-8 Industrial Probe)
  - Sheet 3: 03_MCU_ESP32S3.kicad_sch (ESP32-S3 DevKitC-1 Dual 1x22 Socket Interface & GPIO Map)
  - Sheet 4: 04_Peripherals_LoRa.kicad_sch (LoRa E32-TTL-100 UART, SSD1306 OLED, Panel LEDs, Buzzer, M3 Holes)
"""

import os
import sys
import uuid
import math
import re

KICAD_SYM_DIR = r"C:\Program Files\KiCad\9.0\share\kicad\symbols"
OUTPUT_DIR = r"hardware/kicad/amemiya_main_node"

G = 1.27

def gx(i):
    return round(i * G, 4)

def gy(j):
    return round(j * G, 4)

def u():
    return str(uuid.uuid4())

# Deterministic UUIDs for Sheets
ROOT_UUID = "620adc61-f0a7-4b23-bb32-12a58dd5caa9"
SHEET1_UUID = "e668d7da-402b-45e8-a61e-ea7f383d611c"
SHEET2_UUID = "b73bc9d4-1e86-4693-8263-aaeaf6ac7f20"
SHEET3_UUID = "ac9a9f32-0022-488e-9728-9af913d95ae7"
SHEET4_UUID = "318d76c3-4903-456e-b81c-7ac914c894e8"

S1_FILE_UUID = "901b8001-5a6a-42f1-a3c6-dacb1fbb8615"
S2_FILE_UUID = "46b10fa7-7a2e-4b68-b7b8-e2182098679e"
S3_FILE_UUID = "e4bb25aa-87d2-4309-8472-a9b31d871fa2"
S4_FILE_UUID = "47fb3177-3e6c-48be-a690-e54b6732ef52"

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

# Cache pin offsets and definitions
SYM_DEFS = {}
PIN_OFFSETS = {}

SYMS = [
    ("Connector_Generic", "Conn_01x02"),
    ("Connector_Generic", "Conn_01x03"),
    ("Connector_Generic", "Conn_01x04"),
    ("Connector_Generic", "Conn_01x07"),
    ("Connector_Generic", "Conn_01x08"),
    ("Connector_Generic", "Conn_01x22"),
    ("Device", "R"),
    ("Device", "C"),
    ("Device", "C_Polarized"),
    ("Device", "D_Schottky"),
    ("Diode", "BAT54S"),
    ("Mechanical", "MountingHole"),
    ("power", "+3V3"),
    ("power", "+5V"),
    ("power", "GND"),
    ("power", "PWR_FLAG"),
]

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

def sch_header(title, file_uuid, rev="v36.1", paper="A3"):
    return f"""(kicad_sch
  (version 20231120)
  (generator "kicad")
  (generator_version "9.0")
  (uuid "{file_uuid}")
  (paper "{paper}")
  (title_block
    (title "{title}")
    (date "2026-09-26")
    (rev "{rev}")
    (company "Lean Tech Metrology & Industrial IoT Solutions")
  )
"""

def make_lib_symbols(keys):
    s = "  (lib_symbols\n"
    for k in keys:
        raw = SYM_DEFS[k]
        lines = raw.split("\n")
        indented = "\n".join("    " + line if line.strip() else "" for line in lines)
        s += indented + "\n"
    s += "  )\n"
    return s

def wire(x1, y1, x2, y2):
    return f"""  (wire (pts (xy {x1} {y1}) (xy {x2} {y2}))
    (stroke (width 0) (type default))
    (uuid "{u()}")
  )\n"""

def junction(x, y):
    return f"""  (junction (at {x} {y}) (diameter 1.0) (color 0 0 0 0)
    (uuid "{u()}")
  )\n"""

def no_connect(x, y):
    return f"""  (no_connect (at {x} {y}) (uuid "{u()}"))\n"""

def sym_inst(lib_id, ref, val, fp, x, y, rot=0, desc="", dsheet="~", in_bom=True, on_board=True, ref_offset=None, val_offset=None, sheet_uuid=None, justify=None):
    b_str = "yes" if in_bom else "no"
    o_str = "yes" if on_board else "no"
    
    # In KiCad 9, properties inside rotated symbols inherit the symbol's rotation.
    # To keep text strictly 0 deg on canvas, if rot is 90 or 270, property angle must match rot.
    if rot in (90, 270):
        prop_rot = 90
    else:
        prop_rot = 0
    
    if ref_offset is not None:
        rx, ry = round(x + ref_offset[0], 4), round(y + ref_offset[1], 4)
    else:
        rx, ry = x, round(y - 3.81, 4)
        
    if val_offset is not None:
        vx, vy = round(x + val_offset[0], 4), round(y + val_offset[1], 4)
    else:
        vx, vy = x, round(y + 3.81, 4)
        
    just_str = f" (justify {justify})" if justify else ""
    
    inst_str = ""
    if sheet_uuid:
        inst_str = f"""    (instances
      (project "amemiya_main_node"
        (path "/{ROOT_UUID}/{sheet_uuid}"
          (reference "{ref}")
          (unit 1)
        )
      )
    )
"""
        
    ref_hide = " (hide yes)" if ref.startswith("#") else ""
    return f"""  (symbol (lib_id "{lib_id}") (at {x} {y} {rot}) (unit 1) (in_bom {b_str}) (on_board {o_str})
    (uuid "{u()}")
    (property "Reference" "{ref}" (at {rx} {ry} {prop_rot}) (effects (font (size 1.27 1.27)){just_str}{ref_hide}))
    (property "Value" "{val}" (at {vx} {vy} {prop_rot}) (effects (font (size 1.27 1.27)){just_str}))
    (property "Footprint" "{fp}" (at {x} {y} 0) (effects (font (size 1.27 1.27)) (hide yes)))
    (property "Datasheet" "{dsheet}" (at {x} {y} 0) (effects (font (size 1.27 1.27)) (hide yes)))
    (property "Description" "{desc}" (at {x} {y} 0) (effects (font (size 1.27 1.27)) (hide yes)))
{inst_str}  )
"""

def pwr_inst(lib_id, name, x, y, rot=0):
    if "GND" in name:
        vx, vy = x, round(y + 2.54, 4)
    else:
        vx, vy = x, round(y - 2.54, 4)
    return f"""  (symbol (lib_id "{lib_id}") (at {x} {y} {rot}) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "#PWR" (at {x} {y} 0) (effects (font (size 1.27 1.27)) (hide yes)))
    (property "Value" "{name}" (at {vx} {vy} 0) (effects (font (size 1.27 1.27))))
    (property "Footprint" "" (at {x} {y} 0) (effects (font (size 1.27 1.27)) (hide yes)))
    (property "Datasheet" "" (at {x} {y} 0) (effects (font (size 1.27 1.27)) (hide yes)))
    (property "Description" "" (at {x} {y} 0) (effects (font (size 1.27 1.27)) (hide yes)))
  )
"""

def h_label(name, shape, x, y, rot=0):
    just = "right" if rot == 180 else "left"
    return f"""  (hierarchical_label "{name}" (shape {shape}) (at {x} {y} {rot})
    (effects (font (size 1.27 1.27)) (justify {just}))
    (uuid "{u()}")
  )\n"""

def net_label(name, x, y, rot=0):
    return f"""  (label "{name}" (at {x} {y} {rot})
    (effects (font (size 1.27 1.27)) (justify {"right" if rot==180 else "left"}))
    (uuid "{u()}")
  )\n"""

def text_comment(text_str, x, y, size=1.5):
    return f"""  (text "{text_str}" (at {x} {y} 0)
    (effects (font (size {size} {size}) bold) (justify left bottom))
    (uuid "{u()}")
  )\n"""

# ==============================================================================
# 1. BUILD SHEET 1: 01_Power_Supply.kicad_sch
# ==============================================================================
def build_sheet_1():
    syms = ["Connector_Generic:Conn_01x02", "Device:R", "Device:C", "power:+3V3", "power:+5V", "power:GND", "power:PWR_FLAG"]
    sch = sch_header("01 - Power Supply, Decoupling & Battery Fuel Gauge", S1_FILE_UUID, "v36.1", "A3")
    sch += make_lib_symbols(syms)
    
    sch += text_comment("ENTRADA DE ENERGIA 5V DO MODULO STEP-UP (BOOST)", gx(20), gy(18), 2.0)
    sch += text_comment("Bateria 3.7V Li-Ion -> BMS TP4056 -> Chave ON/OFF -> Step-Up Boost 5V -> J_BAT", gx(20), gy(22), 1.2)
    
    # J_BAT at (gx(40), gy(40))
    jbat_x, jbat_y = gx(40), gy(40)
    sch += sym_inst("Connector_Generic:Conn_01x02", "J_BAT", "Bateria_BMS_2P",
                    "Connector_JST:JST_XH_B2B-XH-A_1x02_P2.50mm_Vertical", jbat_x, jbat_y, 0,
                    "Conector de alimentacao principal 5V DC proveniente do Step-Up",
                    ref_offset=(0, -6), val_offset=(0, 6), sheet_uuid=SHEET1_UUID)
    
    p1_x, p1_y = get_pin_canvas_coord(jbat_x, jbat_y, "Connector_Generic:Conn_01x02", 1)
    p2_x, p2_y = get_pin_canvas_coord(jbat_x, jbat_y, "Connector_Generic:Conn_01x02", 2)
    
    # Pin 1 to +5V (turn wire UP)
    sch += wire(p1_x, p1_y, gx(28), p1_y)
    sch += wire(gx(28), p1_y, gx(28), gy(32))
    sch += pwr_inst("power:+5V", "+5V", gx(28), gy(32), 0)
    
    # Pin 2 to GND (turn wire DOWN)
    sch += wire(p2_x, p2_y, gx(28), p2_y)
    sch += wire(gx(28), p2_y, gx(28), gy(48))
    sch += pwr_inst("power:GND", "GND", gx(28), gy(48), 0)
    
    # Decoupling Caps with generous breathing room
    sch += text_comment("DESACOPLAMENTO DA ALIMENTACAO (+5V & +3V3)", gx(55), gy(24), 1.3)
    
    caps = [
        ("C_IN1", "10uF", gx(60), "+5V"),
        ("C_IN2", "100nF", gx(76), "+5V"),
        ("C_3V3_1", "10uF", gx(96), "+3V3"),
        ("C_3V3_2", "100nF", gx(112), "+3V3")
    ]
    
    cap_y = gy(40)
    for c_ref, c_val, cx, pwr_net in caps:
        sch += sym_inst("Device:C", c_ref, c_val, "Capacitor_SMD:C_0805_2012Metric", cx, cap_y, 0,
                        ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET1_UUID, justify="left")
        cp1_x, cp1_y = get_pin_canvas_coord(cx, cap_y, "Device:C", 1) # gy(37)
        cp2_x, cp2_y = get_pin_canvas_coord(cx, cap_y, "Device:C", 2) # gy(43)
        sch += wire(cp1_x, cp1_y, cp1_x, gy(30))
        sch += pwr_inst(f"power:{pwr_net}", pwr_net, cp1_x, gy(30), 0)
        sch += wire(cp2_x, cp2_y, cp2_x, gy(50))
        sch += pwr_inst("power:GND", "GND", cp2_x, gy(50), 0)
    
    # PWR_FLAGS with clean vertical wire spacing to prevent text collision
    sch += text_comment("FONTES DE ALIMENTACAO DO SISTEMA (PWR_FLAG)", gx(135), gy(24), 1.3)
    
    # +5V PWR_FLAG
    sch += pwr_inst("power:+5V", "+5V", gx(140), gy(30), 0)
    sch += wire(gx(140), gy(30), gx(140), gy(36))
    sch += sym_inst("power:PWR_FLAG", "#FLG01", "PWR_FLAG", "", gx(140), gy(36), 0, in_bom=False, sheet_uuid=SHEET1_UUID)
    
    # +3V3 PWR_FLAG
    sch += pwr_inst("power:+3V3", "+3V3", gx(155), gy(30), 0)
    sch += wire(gx(155), gy(30), gx(155), gy(36))
    sch += sym_inst("power:PWR_FLAG", "#FLG02", "PWR_FLAG", "", gx(155), gy(36), 0, in_bom=False, sheet_uuid=SHEET1_UUID)
    
    # GND PWR_FLAG (Diamond at top, GND at bottom)
    sch += sym_inst("power:PWR_FLAG", "#FLG03", "PWR_FLAG", "", gx(170), gy(30), 0, in_bom=False, sheet_uuid=SHEET1_UUID)
    sch += wire(gx(170), gy(30), gx(170), gy(36))
    sch += pwr_inst("power:GND", "GND", gx(170), gy(36), 0)
    
    # Fuel Gauge Battery Sense
    sch += text_comment("CIRCUITO DE MONITORAMENTO DE BATERIA (FUEL GAUGE DIVIDER)", gx(20), gy(68), 2.0)
    sch += text_comment("Divisor 1:2 (100k / 100k) com filtro 100nF para leitura ADC de VBAT (0-4.2V -> 0-2.1V)", gx(20), gy(72), 1.2)
    
    jsense_x, jsense_y = gx(35), gy(88)
    sch += sym_inst("Connector_Generic:Conn_01x02", "J_BAT_SENSE", "Sense_VBAT_2P",
                    "Connector_JST:JST_XH_B2B-XH-A_1x02_P2.50mm_Vertical", jsense_x, jsense_y, 0,
                    "Leitura direta da tensao antes do Step-Up",
                    ref_offset=(0, -6), val_offset=(0, 6), sheet_uuid=SHEET1_UUID)
    sp1_x, sp1_y = get_pin_canvas_coord(jsense_x, jsense_y, "Connector_Generic:Conn_01x02", 1) # gy(88)
    sp2_x, sp2_y = get_pin_canvas_coord(jsense_x, jsense_y, "Connector_Generic:Conn_01x02", 2) # gy(90)
    
    sch += wire(sp1_x, sp1_y, gx(58), sp1_y)
    sch += net_label("VBAT_RAW", gx(45), sp1_y)
    sch += wire(sp2_x, sp2_y, gx(24), sp2_y)
    sch += wire(gx(24), sp2_y, gx(24), gy(96))
    sch += pwr_inst("power:GND", "GND", gx(24), gy(96), 0)
    
    # R_BAT1 at (gx(58), gy(98)) vertical
    rb1_x, rb1_y = gx(58), gy(98)
    sch += sym_inst("Device:R", "R_BAT1", "100k 1%", "Resistor_SMD:R_0805_2012Metric", rb1_x, rb1_y, 0,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET1_UUID, justify="left")
    rb1_p1_x, rb1_p1_y = get_pin_canvas_coord(rb1_x, rb1_y, "Device:R", 1) # gy(95)
    rb1_p2_x, rb1_p2_y = get_pin_canvas_coord(rb1_x, rb1_y, "Device:R", 2) # gy(101)
    sch += wire(gx(58), sp1_y, rb1_p1_x, rb1_p1_y) # sp1_y is gy(88)
    
    # Node at (gx(58), gy(106)): connects R_BAT1 bot, R_BAT2 top, C_BAT top, and VBAT_ADC
    node_y = gy(106)
    sch += wire(rb1_p2_x, rb1_p2_y, gx(58), node_y)
    
    # R_BAT2 at (gx(76), gy(116)) vertical
    rb2_x, rb2_y = gx(76), gy(116)
    sch += sym_inst("Device:R", "R_BAT2", "100k 1%", "Resistor_SMD:R_0805_2012Metric", rb2_x, rb2_y, 0,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET1_UUID, justify="left")
    rb2_p1_x, rb2_p1_y = get_pin_canvas_coord(rb2_x, rb2_y, "Device:R", 1) # gy(113)
    rb2_p2_x, rb2_p2_y = get_pin_canvas_coord(rb2_x, rb2_y, "Device:R", 2) # gy(119)
    sch += wire(gx(58), node_y, gx(76), node_y)
    sch += junction(gx(76), node_y)
    sch += wire(gx(76), node_y, rb2_p1_x, rb2_p1_y)
    sch += wire(rb2_p2_x, rb2_p2_y, rb2_p2_x, gy(126))
    sch += pwr_inst("power:GND", "GND", rb2_p2_x, gy(126), 0)
    
    # C_BAT at (gx(94), gy(116)) vertical
    cb_x, cb_y = gx(94), gy(116)
    sch += sym_inst("Device:C", "C_BAT", "100nF", "Capacitor_SMD:C_0805_2012Metric", cb_x, cb_y, 0,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET1_UUID, justify="left")
    cb_p1_x, cb_p1_y = get_pin_canvas_coord(cb_x, cb_y, "Device:C", 1) # gy(113)
    cb_p2_x, cb_p2_y = get_pin_canvas_coord(cb_x, cb_y, "Device:C", 2) # gy(119)
    sch += wire(gx(76), node_y, gx(94), node_y)
    sch += junction(gx(94), node_y)
    sch += wire(gx(94), node_y, cb_p1_x, cb_p1_y)
    sch += wire(cb_p2_x, cb_p2_y, cb_p2_x, gy(126))
    sch += pwr_inst("power:GND", "GND", cb_p2_x, gy(126), 0)
    
    # Hierarchical label VBAT_ADC output from node_y
    sch += wire(gx(94), node_y, gx(125), node_y)
    sch += h_label("VBAT_ADC", "output", gx(125), node_y, 0)
    
    # Informational Engineering Notes & Power Chain Diagram
    sch += text_comment("CADEIA DE ALIMENTACAO INDUSTRIAL EXTERNA (OFF-BOARD)", gx(135), gy(68), 1.8)
    sch += text_comment("1. BATERIA: 2x Celulas 18650 Li-Ion (3.7V nom / 4.2V max / ~5200mAh em paralelo)", gx(135), gy(76), 1.2)
    sch += text_comment("2. BMS/CARREGADOR: Modulo TP4056 com protecao contra sobrecarga e descarga profunda", gx(135), gy(82), 1.2)
    sch += text_comment("3. CHAVE GERAL: Chave alavanca liga/desliga de painel (interrompe o polo positivo)", gx(135), gy(88), 1.2)
    sch += text_comment("4. STEP-UP (BOOST): Modulo MT3608 ajustado para 5.00V DC regulado (eficiencia ~93%)", gx(135), gy(94), 1.2)
    sch += text_comment("5. ENTRADA NA PLACA: Conector JST-XH 2P (J_BAT) -> Pino 22 do DevKit (5V IN)", gx(135), gy(100), 1.2)
    sch += text_comment("6. SENSE DE BATERIA: Conector JST-XH 2P (J_BAT_SENSE) ligado antes do Step-Up", gx(135), gy(106), 1.2)
    sch += text_comment("7. FORMULA FUEL GAUGE: V_ADC = V_BAT * (100k / (100k + 100k)) = V_BAT / 2 (Max 2.10V)", gx(135), gy(114), 1.2)
    sch += text_comment("   -> Compativel com faixa linear do ADC1 (0 a 3.1V com atenuacao 11dB no ESP32-S3)", gx(135), gy(118), 1.1)

    sch += f"  (sheet_instances (path \"/{SHEET1_UUID}\" (page \"2\")))\n)\n"
    return sch

# ==============================================================================
# 2. BUILD SHEET 2: 02_Sensors_AFE.kicad_sch
# ==============================================================================
def build_sheet_2():
    syms = ["Connector_Generic:Conn_01x02", "Connector_Generic:Conn_01x03", "Connector_Generic:Conn_01x08",
            "Device:R", "Device:C", "Device:D_Schottky", "power:+3V3", "power:GND"]
    sch = sch_header("02 - Analog Front-End (AFE) & Sensor Interfaces", S2_FILE_UUID, "v36.1", "A3")
    sch += make_lib_symbols(syms)
    
    # --------------------------------------------------------------------------
    # Band 1: SCT-013 AC Current Clamp AFE (0-100A AC -> 0-3.3V ADC)
    # --------------------------------------------------------------------------
    sch += text_comment("CONDICIONAMENTO DE SINAIS (AFE) E INTERFACES DE SENSORES INDUSTRIAIS", gx(15), gy(12), 2.0)
    sch += text_comment("AFE ALICATE AMPERIMETRICO SCT-013 (0-100A AC -> 0-3.3V ADC)", gx(15), gy(18), 1.5)
    sch += text_comment("Polarizacao virtual ground 1.65V DC + Resistor Burden 33R 1% + Filtro Passa-Baixa RC + Diodos Clamp", gx(15), gy(22), 1.2)
    
    # Virtual Ground Divider (1.65V DC) on the left
    # R_BIAS1 (100k 1%) vertical at (gx(22), gy(37))
    rb1_x, rb1_y = gx(22), gy(37)
    sch += sym_inst("Device:R", "R_BIAS1", "100k 1%", "Resistor_SMD:R_0805_2012Metric", rb1_x, rb1_y, 0,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET2_UUID, justify="left")
    b1_p1_x, b1_p1_y = get_pin_canvas_coord(rb1_x, rb1_y, "Device:R", 1) # gy(34)
    b1_p2_x, b1_p2_y = get_pin_canvas_coord(rb1_x, rb1_y, "Device:R", 2) # gy(40)
    sch += wire(b1_p1_x, b1_p1_y, b1_p1_x, gy(30))
    sch += pwr_inst("power:+3V3", "+3V3", b1_p1_x, gy(30), 0)
    
    # R_BIAS2 (100k 1%) vertical at (gx(22), gy(53))
    rb2_x, rb2_y = gx(22), gy(53)
    sch += sym_inst("Device:R", "R_BIAS2", "100k 1%", "Resistor_SMD:R_0805_2012Metric", rb2_x, rb2_y, 0,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET2_UUID, justify="left")
    b2_p1_x, b2_p1_y = get_pin_canvas_coord(rb2_x, rb2_y, "Device:R", 1) # gy(50)
    b2_p2_x, b2_p2_y = get_pin_canvas_coord(rb2_x, rb2_y, "Device:R", 2) # gy(56)
    sch += wire(b2_p2_x, b2_p2_y, b2_p2_x, gy(60))
    sch += pwr_inst("power:GND", "GND", b2_p2_x, gy(60), 0)
    
    # C_BIAS (10uF) vertical at (gx(32), gy(53))
    cbias_x, cbias_y = gx(32), gy(53)
    sch += sym_inst("Device:C", "C_BIAS", "10uF", "Capacitor_SMD:C_0805_2012Metric", cbias_x, cbias_y, 0,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET2_UUID, justify="left")
    cb_p1_x, cb_p1_y = get_pin_canvas_coord(cbias_x, cbias_y, "Device:C", 1) # gy(50)
    cb_p2_x, cb_p2_y = get_pin_canvas_coord(cbias_x, cbias_y, "Device:C", 2) # gy(56)
    sch += wire(cb_p2_x, cb_p2_y, cb_p2_x, gy(60))
    sch += pwr_inst("power:GND", "GND", cb_p2_x, gy(60), 0)
    
    # V_BIAS bus along gy(45)
    vbias_y = gy(45)
    sch += wire(b1_p2_x, b1_p2_y, b1_p2_x, vbias_y)
    sch += wire(b1_p2_x, vbias_y, b2_p1_x, b2_p1_y)
    sch += junction(b1_p2_x, vbias_y)
    sch += wire(b1_p2_x, vbias_y, cb_p1_x, vbias_y)
    sch += wire(cb_p1_x, vbias_y, cb_p1_x, cb_p1_y)
    sch += junction(cb_p1_x, vbias_y)
    
    # J_AMP Connector at (gx(46), gy(41)) vertical
    jamp_x, jamp_y = gx(46), gy(41)
    sch += sym_inst("Connector_Generic:Conn_01x02", "J_AMP", "Clamp_SCT013_2P",
                    "Connector_JST:JST_XH_B2B-XH-A_1x02_P2.50mm_Vertical", jamp_x, jamp_y, 0,
                    "Conector JST-XH ligado ao Jack P2 3.5mm de painel",
                    ref_offset=(0, -6), val_offset=(0, 6), sheet_uuid=SHEET2_UUID)
    jp1_x, jp1_y = get_pin_canvas_coord(jamp_x, jamp_y, "Connector_Generic:Conn_01x02", 1) # gy(41)
    jp2_x, jp2_y = get_pin_canvas_coord(jamp_x, jamp_y, "Connector_Generic:Conn_01x02", 2) # gy(43)
    
    # R_B (Burden 33R 1%) at (gx(58), gy(41)) vertical
    rb_x, rb_y = gx(58), gy(41)
    sch += sym_inst("Device:R", "R_B", "33R", "Resistor_THT:R_Axial_DIN0207_L6.3mm_D2.5mm_P7.62mm_Horizontal", rb_x, rb_y, 0,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET2_UUID, justify="left")
    rb_p1_x, rb_p1_y = get_pin_canvas_coord(rb_x, rb_y, "Device:R", 1) # gy(38)
    rb_p2_x, rb_p2_y = get_pin_canvas_coord(rb_x, rb_y, "Device:R", 2) # gy(44)
    
    # Connect V_BIAS rail to J_AMP Pin 2 and R_B Pin 2
    sch += wire(cb_p1_x, vbias_y, jp2_x, vbias_y)
    sch += wire(jp2_x, vbias_y, jp2_x, jp2_y)
    sch += junction(jp2_x, vbias_y)
    sch += wire(jp2_x, vbias_y, rb_p2_x, vbias_y)
    sch += wire(rb_p2_x, vbias_y, rb_p2_x, rb_p2_y)
    sch += junction(rb_p2_x, vbias_y)
    
    # Signal rail at gy(38): J_AMP Pin 1 -> R_B Pin 1 -> R_F Pin 1
    sig_y = gy(38)
    sch += wire(jp1_x, jp1_y, jp1_x, sig_y)
    sch += wire(jp1_x, sig_y, rb_p1_x, sig_y)
    sch += junction(rb_p1_x, sig_y)
    
    # Anti-Aliasing Low-Pass Filter: R_F (1k) horizontal (rot 90) at (gx(72), gy(38))
    rf_x, rf_y = gx(72), sig_y
    sch += sym_inst("Device:R", "R_F", "1k", "Resistor_SMD:R_0805_2012Metric", rf_x, rf_y, 90,
                    ref_offset=(0, -3.81), val_offset=(0, 3.81), sheet_uuid=SHEET2_UUID)
    rf_p1_x, rf_p1_y = get_pin_canvas_coord(rf_x, rf_y, "Device:R", 1, 90) # gx(69), gy(38)
    rf_p2_x, rf_p2_y = get_pin_canvas_coord(rf_x, rf_y, "Device:R", 2, 90) # gx(75), gy(38)
    sch += wire(rb_p1_x, sig_y, rf_p1_x, rf_p1_y)
    
    # C_F (10nF) at (gx(88), gy(46)) vertical
    cf_x, cf_y = gx(88), gy(46)
    sch += sym_inst("Device:C", "C_F", "10nF", "Capacitor_SMD:C_0805_2012Metric", cf_x, cf_y, 0,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET2_UUID, justify="left")
    cf_p1_x, cf_p1_y = get_pin_canvas_coord(cf_x, cf_y, "Device:C", 1) # gy(43)
    cf_p2_x, cf_p2_y = get_pin_canvas_coord(cf_x, cf_y, "Device:C", 2) # gy(49)
    sch += wire(rf_p2_x, sig_y, cf_p1_x, sig_y)
    sch += wire(cf_p1_x, sig_y, cf_p1_x, cf_p1_y)
    sch += junction(cf_p1_x, sig_y)
    sch += wire(cf_p2_x, cf_p2_y, cf_p2_x, gy(55))
    sch += pwr_inst("power:GND", "GND", cf_p2_x, gy(55), 0)
    
    # Dual Schottky Protection Clamp at gx(106): D_AMP_HI and D_AMP_LO (BAT54 / 1N5819)
    dclamp_x = gx(106)
    
    # D_AMP_HI: Upper clamp (rot=90: Cathode at top gy(27) -> +3V3; Anode at bottom gy(33) -> Signal)
    dhi_y = gy(30)
    sch += sym_inst("Device:D_Schottky", "D_AMP_HI", "BAT54", "Diode_SMD:D_SOD-323", dclamp_x, dhi_y, 90,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET2_UUID, justify="left")
    dhi_p1_x, dhi_p1_y = get_pin_canvas_coord(dclamp_x, dhi_y, "Device:D_Schottky", 1, 90) # Anode at gy(33)
    dhi_p2_x, dhi_p2_y = get_pin_canvas_coord(dclamp_x, dhi_y, "Device:D_Schottky", 2, 90) # Cathode at gy(27)
    sch += wire(dhi_p2_x, dhi_p2_y, dhi_p2_x, gy(22))
    sch += pwr_inst("power:+3V3", "+3V3", dhi_p2_x, gy(22), 0)
    sch += wire(dhi_p1_x, dhi_p1_y, dclamp_x, sig_y)
    
    # D_AMP_LO: Lower clamp (rot=90: Cathode at top gy(43) -> Signal; Anode at bottom gy(49) -> GND)
    dlo_y = gy(46)
    sch += sym_inst("Device:D_Schottky", "D_AMP_LO", "BAT54", "Diode_SMD:D_SOD-323", dclamp_x, dlo_y, 90,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET2_UUID, justify="left")
    dlo_p1_x, dlo_p1_y = get_pin_canvas_coord(dclamp_x, dlo_y, "Device:D_Schottky", 1, 90) # Anode at gy(49)
    dlo_p2_x, dlo_p2_y = get_pin_canvas_coord(dclamp_x, dlo_y, "Device:D_Schottky", 2, 90) # Cathode at gy(43)
    sch += wire(dlo_p2_x, dlo_p2_y, dclamp_x, sig_y)
    sch += wire(dlo_p1_x, dlo_p1_y, dlo_p1_x, gy(54))
    sch += pwr_inst("power:GND", "GND", dlo_p1_x, gy(54), 0)
    
    # Clean continuous signal wire from C_F through junction at dclamp_x to AMP_ADC
    sch += wire(cf_p1_x, sig_y, dclamp_x, sig_y)
    sch += junction(dclamp_x, sig_y)
    sch += wire(dclamp_x, sig_y, gx(125), sig_y)
    sch += h_label("AMP_ADC", "output", gx(125), sig_y, 0)
    
    # --------------------------------------------------------------------------
    # Band 2: Tachometer Interface (Optical / Hall Digital)
    # --------------------------------------------------------------------------
    sch += text_comment("INTERFACE TACOMETRO DIGITAL (SENSOR HALL / OPTICO)", gx(15), gy(74), 1.5)
    sch += text_comment("Pull-up 4.7k + Resistor Serie 1k + Filtro Passa-Baixa 100pF", gx(15), gy(78), 1.2)
    
    jtacho_x, jtacho_y = gx(26), gy(96)
    sch += sym_inst("Connector_Generic:Conn_01x03", "J_TACHO", "Tacometro_3P",
                    "Connector_JST:JST_XH_B3B-XH-A_1x03_P2.50mm_Vertical", jtacho_x, jtacho_y, 0,
                    "Conector ligado ao conector industrial GX12 3P de painel",
                    ref_offset=(0, -8), val_offset=(0, 8), sheet_uuid=SHEET2_UUID)
    tp1_x, tp1_y = get_pin_canvas_coord(jtacho_x, jtacho_y, "Connector_Generic:Conn_01x03", 1) # gy(94)
    tp2_x, tp2_y = get_pin_canvas_coord(jtacho_x, jtacho_y, "Connector_Generic:Conn_01x03", 2) # gy(96)
    tp3_x, tp3_y = get_pin_canvas_coord(jtacho_x, jtacho_y, "Connector_Generic:Conn_01x03", 3) # gy(98)
    
    # Pin 1: +3V3
    sch += wire(tp1_x, tp1_y, gx(18), tp1_y)
    sch += wire(gx(18), tp1_y, gx(18), gy(88))
    sch += pwr_inst("power:+3V3", "+3V3", gx(18), gy(88), 0)
    
    # Pin 2: GND
    sch += wire(tp2_x, tp2_y, gx(18), tp2_y)
    sch += wire(gx(18), tp2_y, gx(18), gy(104))
    sch += pwr_inst("power:GND", "GND", gx(18), gy(104), 0)
    
    # Pin 3: Signal along gy(98)
    tacho_sig_y = gy(98)
    
    # Pull-up R_PU_TACHO (4.7k) at (gx(42), gy(90)) vertical
    rpu_t_x, rpu_t_y = gx(42), gy(90)
    sch += sym_inst("Device:R", "R_PU_TACHO", "4.7k", "Resistor_SMD:R_0805_2012Metric", rpu_t_x, rpu_t_y, 0,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET2_UUID, justify="left")
    tpu_p1_x, tpu_p1_y = get_pin_canvas_coord(rpu_t_x, rpu_t_y, "Device:R", 1) # gy(87)
    tpu_p2_x, tpu_p2_y = get_pin_canvas_coord(rpu_t_x, rpu_t_y, "Device:R", 2) # gy(93)
    sch += wire(tpu_p1_x, tpu_p1_y, tpu_p1_x, gy(84))
    sch += pwr_inst("power:+3V3", "+3V3", tpu_p1_x, gy(84), 0)
    
    # Wire from Pin 3 to Pull-up node at (gx(42), gy(98))
    sch += wire(tp3_x, tp3_y, tpu_p2_x, tacho_sig_y)
    sch += wire(tpu_p2_x, tpu_p2_y, tpu_p2_x, tacho_sig_y)
    sch += junction(tpu_p2_x, tacho_sig_y)
    sch += net_label("TACHO_RAW", gx(32), tacho_sig_y)
    
    # Series Resistor R_S_TACHO (1k) horizontal (rot 90) at (gx(66), gy(98))
    rs_t_x, rs_t_y = gx(66), tacho_sig_y
    sch += sym_inst("Device:R", "R_S_TACHO", "1k", "Resistor_SMD:R_0805_2012Metric", rs_t_x, rs_t_y, 90,
                    ref_offset=(0, -3.81), val_offset=(0, 3.81), sheet_uuid=SHEET2_UUID)
    rst_p1_x, rst_p1_y = get_pin_canvas_coord(rs_t_x, rs_t_y, "Device:R", 1, 90) # gx(63), gy(98)
    rst_p2_x, rst_p2_y = get_pin_canvas_coord(rs_t_x, rs_t_y, "Device:R", 2, 90) # gx(69), gy(98)
    sch += wire(tpu_p2_x, tacho_sig_y, rst_p1_x, rst_p1_y)
    
    # Filter Capacitor C_F_TACHO (100pF) at (gx(88), gy(106)) vertical
    cft_x, cft_y = gx(88), gy(106)
    sch += sym_inst("Device:C", "C_F_TACHO", "100pF", "Capacitor_SMD:C_0805_2012Metric", cft_x, cft_y, 0,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET2_UUID, justify="left")
    cft_p1_x, cft_p1_y = get_pin_canvas_coord(cft_x, cft_y, "Device:C", 1) # gy(103)
    cft_p2_x, cft_p2_y = get_pin_canvas_coord(cft_x, cft_y, "Device:C", 2) # gy(109)
    sch += wire(rst_p2_x, rst_p2_y, cft_p1_x, tacho_sig_y)
    sch += wire(cft_p1_x, tacho_sig_y, cft_p1_x, cft_p1_y)
    sch += junction(cft_p1_x, tacho_sig_y)
    sch += wire(cft_p2_x, cft_p2_y, cft_p2_x, gy(115))
    sch += pwr_inst("power:GND", "GND", cft_p2_x, gy(115), 0)
    
    # Hierarchical label TACHO output
    sch += wire(cft_p1_x, tacho_sig_y, gx(125), tacho_sig_y)
    sch += h_label("TACHO", "output", gx(125), tacho_sig_y, 0)
    
    # --------------------------------------------------------------------------
    # Band 3: GX16-8 Test Tower Probe Cable Interface
    # --------------------------------------------------------------------------
    sch += text_comment("INTERFACE SONDA DA TORRE DE TESTES (CHICOTE INDUSTRIAL GX16-8 VIAS)", gx(15), gy(130), 1.5)
    sch += text_comment("Sinais: +3V3, GND, 1-Wire DS18B20, I2S Digital Mic / Vibrometro, Barramento I2C Sensores", gx(15), gy(134), 1.2)
    
    jprobe_x, jprobe_y = gx(26), gy(150)
    sch += sym_inst("Connector_Generic:Conn_01x08", "J_PROBE", "Sonda_Torre_GX16_8P",
                    "Connector_JST:JST_XH_B8B-XH-A_1x08_P2.50mm_Vertical", jprobe_x, jprobe_y, 0,
                    "Conector ligado ao GX16-8 Macho de painel para o chicote da torre",
                    ref_offset=(0, -14), val_offset=(0, 14), sheet_uuid=SHEET2_UUID)
    
    probe_pins = [get_pin_canvas_coord(jprobe_x, jprobe_y, "Connector_Generic:Conn_01x08", i) for i in range(1, 9)]
    
    # Pin 1: +3V3 Power Rail to Probe
    sch += wire(probe_pins[0][0], probe_pins[0][1], gx(18), probe_pins[0][1])
    sch += wire(gx(18), probe_pins[0][1], gx(18), gy(142))
    sch += pwr_inst("power:+3V3", "+3V3", gx(18), gy(142), 0)
    
    # Bypass Capacitor C_PROBE (100nF) for probe power rail at (gx(42), gy(147)) vertical
    cp_x, cp_y = gx(42), gy(147)
    sch += sym_inst("Device:C", "C_PROBE", "100nF", "Capacitor_SMD:C_0805_2012Metric", cp_x, cp_y, 0,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET2_UUID, justify="left")
    cp_p1_x, cp_p1_y = get_pin_canvas_coord(cp_x, cp_y, "Device:C", 1) # gy(144)
    cp_p2_x, cp_p2_y = get_pin_canvas_coord(cp_x, cp_y, "Device:C", 2) # gy(150)
    sch += wire(cp_p1_x, cp_p1_y, cp_p1_x, gy(142))
    sch += pwr_inst("power:+3V3", "+3V3", cp_p1_x, gy(142), 0)
    sch += wire(cp_p2_x, cp_p2_y, cp_p2_x, gy(155))
    sch += pwr_inst("power:GND", "GND", cp_p2_x, gy(155), 0)
    
    # Pin 2: GND
    sch += wire(probe_pins[1][0], probe_pins[1][1], gx(18), probe_pins[1][1])
    sch += wire(gx(18), probe_pins[1][1], gx(18), gy(162))
    sch += pwr_inst("power:GND", "GND", gx(18), gy(162), 0)
    
    # Probe Signal Lines:
    # Pin 3: 1W_DQ at probe_pins[2][1] (gy(148))
    # Pin 4: I2S_SD at probe_pins[3][1] (gy(150))
    # Pin 5: I2S_SCK at probe_pins[4][1] (gy(152))
    # Pin 6: I2S_WS at probe_pins[5][1] (gy(154))
    # Pin 7: I2C_SCL at probe_pins[6][1] (gy(156))
    # Pin 8: I2C_SDA at probe_pins[7][1] (gy(158))
    
    # Pull-ups with +3V3 power symbols at gy(142)
    # 1-Wire Pull-up R_PU_1W (4.7k) at (gx(58), gy(148))
    rpu1w_x, rpu1w_y = gx(58), gy(148)
    sch += sym_inst("Device:R", "R_PU_1W", "4.7k", "Resistor_SMD:R_0805_2012Metric", rpu1w_x, rpu1w_y, 0,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET2_UUID, justify="left")
    rw_p1_x, rw_p1_y = get_pin_canvas_coord(rpu1w_x, rpu1w_y, "Device:R", 1) # gy(145)
    rw_p2_x, rw_p2_y = get_pin_canvas_coord(rpu1w_x, rpu1w_y, "Device:R", 2) # gy(151)
    sch += wire(rw_p1_x, rw_p1_y, rw_p1_x, gy(142))
    sch += pwr_inst("power:+3V3", "+3V3", rw_p1_x, gy(142), 0)
    
    # I2C Pull-up R_PU_SCL (4.7k) at (gx(76), gy(152))
    rpuscl_x, rpuscl_y = gx(76), gy(152)
    sch += sym_inst("Device:R", "R_PU_SCL", "4.7k", "Resistor_SMD:R_0805_2012Metric", rpuscl_x, rpuscl_y, 0,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET2_UUID, justify="left")
    rscl_p1_x, rscl_p1_y = get_pin_canvas_coord(rpuscl_x, rpuscl_y, "Device:R", 1) # gy(149)
    rscl_p2_x, rscl_p2_y = get_pin_canvas_coord(rpuscl_x, rpuscl_y, "Device:R", 2) # gy(155)
    sch += wire(rscl_p1_x, rscl_p1_y, rscl_p1_x, gy(142))
    sch += pwr_inst("power:+3V3", "+3V3", rscl_p1_x, gy(142), 0)
    
    # I2C Pull-up R_PU_SDA (4.7k) at (gx(94), gy(154))
    rpusda_x, rpusda_y = gx(94), gy(154)
    sch += sym_inst("Device:R", "R_PU_SDA", "4.7k", "Resistor_SMD:R_0805_2012Metric", rpusda_x, rpusda_y, 0,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET2_UUID, justify="left")
    rsda_p1_x, rsda_p1_y = get_pin_canvas_coord(rpusda_x, rpusda_y, "Device:R", 1) # gy(151)
    rsda_p2_x, rsda_p2_y = get_pin_canvas_coord(rpusda_x, rpusda_y, "Device:R", 2) # gy(157)
    sch += wire(rsda_p1_x, rsda_p1_y, rsda_p1_x, gy(142))
    sch += pwr_inst("power:+3V3", "+3V3", rsda_p1_x, gy(142), 0)
    
    # 1W_DQ: Pin 3 -> junction at rw_p2_x -> h_label
    dq_y = probe_pins[2][1]
    sch += wire(probe_pins[2][0], dq_y, rw_p2_x, dq_y)
    sch += wire(rw_p2_x, rw_p2_y, rw_p2_x, dq_y)
    sch += junction(rw_p2_x, dq_y)
    sch += wire(rw_p2_x, dq_y, gx(115), dq_y)
    sch += h_label("1W_DQ", "bidirectional", gx(115), dq_y, 0)
    
    # I2S_SD: Pin 4 -> h_label
    sd_y = probe_pins[3][1]
    sch += wire(probe_pins[3][0], sd_y, gx(115), sd_y)
    sch += h_label("I2S_SD", "output", gx(115), sd_y, 0)
    
    # I2S_SCK: Pin 5 -> h_label
    sck_y = probe_pins[4][1]
    sch += wire(probe_pins[4][0], sck_y, gx(115), sck_y)
    sch += h_label("I2S_SCK", "input", gx(115), sck_y, 0)
    
    # I2S_WS: Pin 6 -> h_label
    ws_y = probe_pins[5][1]
    sch += wire(probe_pins[5][0], ws_y, gx(115), ws_y)
    sch += h_label("I2S_WS", "input", gx(115), ws_y, 0)
    
    # I2C_SCL: Pin 7 -> junction at rscl_p2_x -> h_label
    scl_y = probe_pins[6][1]
    sch += wire(probe_pins[6][0], scl_y, rscl_p2_x, scl_y)
    sch += wire(rscl_p2_x, rscl_p2_y, rscl_p2_x, scl_y)
    sch += junction(rscl_p2_x, scl_y)
    sch += wire(rscl_p2_x, scl_y, gx(115), scl_y)
    sch += h_label("I2C_SCL", "bidirectional", gx(115), scl_y, 0)
    
    # I2C_SDA: Pin 8 -> junction at rsda_p2_x -> h_label
    sda_y = probe_pins[7][1]
    sch += wire(probe_pins[7][0], sda_y, rsda_p2_x, sda_y)
    sch += wire(rsda_p2_x, rsda_p2_y, rsda_p2_x, sda_y)
    sch += junction(rsda_p2_x, sda_y)
    sch += wire(rsda_p2_x, sda_y, gx(115), sda_y)
    sch += h_label("I2C_SDA", "bidirectional", gx(115), sda_y, 0)
    
    sch += f"  (sheet_instances (path \"/{SHEET2_UUID}\" (page \"3\")))\n)\n"
    return sch

# ==============================================================================
# 3. BUILD SHEET 3: 03_MCU_ESP32S3.kicad_sch
# ==============================================================================
def build_sheet_3():
    syms = ["Connector_Generic:Conn_01x22", "Device:C", "power:+3V3", "power:+5V", "power:GND"]
    sch = sch_header("03 - ESP32-S3 DevKit 44-Pin Dual Socket Interface", S3_FILE_UUID, "v36.1", "A3")
    sch += make_lib_symbols(syms)
    
    sch += text_comment("INTERFACES DE SOQUETE ESP32-S3 DEVKITC-1 (2x BARRA DE PINOS FEMEA 1x22, 2.54mm)", gx(20), gy(15), 2.0)
    sch += text_comment("O DevKit conecta-se modularmente a baseboard, preservando USB-C, LDO e botoes onboard.", gx(20), gy(19), 1.2)
    
    # U_ESP_L at (gx(75), gy(65))
    ul_x, ul_y = gx(75), gy(65)
    sch += sym_inst("Connector_Generic:Conn_01x22", "U_ESP_L", "ESP32-S3_Header_1x22",
                    "Connector_PinHeader_2.54mm:PinHeader_1x22_P2.54mm_Vertical", ul_x, ul_y, 0,
                    "Barra de pinos femea lado esquerdo (J1) ESP32-S3 DevKit",
                    ref_offset=(0, -32), val_offset=(0, 32), sheet_uuid=SHEET3_UUID)
    
    # Pin mappings for Left Header (DevKit J1)
    l_map = [
        (1, "pwr_3v3", "+3V3"),
        (2, "pwr_3v3", "+3V3"),
        (3, "nc", None),
        (4, "h_bi", "1W_DQ"),
        (5, "h_in", "I2S_SD"),
        (6, "h_out", "I2S_SCK"),
        (7, "h_out", "I2S_WS"),
        (8, "nc", None),
        (9, "nc", None),
        (10, "nc", None),
        (11, "nc", None),
        (12, "h_bi", "I2C_SCL"),
        (13, "h_bi", "I2C_SDA"),
        (14, "h_in", "TACHO"),
        (15, "h_in", "AMP_ADC"),
        (16, "h_out", "LORA_TX"),
        (17, "h_in", "LORA_RX"),
        (18, "h_in", "LORA_AUX"),
        (19, "h_out", "GPIO_LED_GRN"),
        (20, "h_out", "GPIO_LED_ORG"),
        (21, "h_out", "GPIO_LED_BLU"),
        (22, "pwr_gnd", "GND"),
    ]
    
    # Connect Pins 1 & 2 together (+3V3) with a single power symbol pointing UP
    p1_x, p1_y = get_pin_canvas_coord(ul_x, ul_y, "Connector_Generic:Conn_01x22", 1)
    p2_x, p2_y = get_pin_canvas_coord(ul_x, ul_y, "Connector_Generic:Conn_01x22", 2)
    bus_3v3_x = round(p1_x - 12.7, 4)
    sch += wire(p1_x, p1_y, bus_3v3_x, p1_y)
    sch += wire(p2_x, p2_y, bus_3v3_x, p2_y)
    sch += wire(bus_3v3_x, p1_y, bus_3v3_x, p2_y)
    sch += junction(bus_3v3_x, p1_y)
    sch += wire(bus_3v3_x, p1_y, bus_3v3_x, round(p1_y - 5.08, 4))
    sch += pwr_inst("power:+3V3", "+3V3", bus_3v3_x, round(p1_y - 5.08, 4), 0)
    
    # Connect Pin 22 (GND) cleanly with power symbol pointing DOWN
    p22_x, p22_y = get_pin_canvas_coord(ul_x, ul_y, "Connector_Generic:Conn_01x22", 22)
    bus_gnd_x = round(p22_x - 12.7, 4)
    sch += wire(p22_x, p22_y, bus_gnd_x, p22_y)
    sch += wire(bus_gnd_x, p22_y, bus_gnd_x, round(p22_y + 5.08, 4))
    sch += pwr_inst("power:GND", "GND", bus_gnd_x, round(p22_y + 5.08, 4), 0)
    
    for pnum, ptype, name in l_map:
        px, py = get_pin_canvas_coord(ul_x, ul_y, "Connector_Generic:Conn_01x22", pnum)
        if ptype == "nc":
            sch += no_connect(px, py)
        elif ptype in ["pwr_3v3", "pwr_gnd"]:
            pass # handled above
        elif ptype == "h_in":
            sch += wire(px, py, round(px - 22.86, 4), py)
            sch += h_label(name, "input", round(px - 22.86, 4), py, 180)
        elif ptype == "h_out":
            sch += wire(px, py, round(px - 22.86, 4), py)
            sch += h_label(name, "output", round(px - 22.86, 4), py, 180)
        elif ptype == "h_bi":
            sch += wire(px, py, round(px - 22.86, 4), py)
            sch += h_label(name, "bidirectional", round(px - 22.86, 4), py, 180)

    # U_ESP_R at (gx(140), gy(65))
    ur_x, ur_y = gx(140), gy(65)
    sch += sym_inst("Connector_Generic:Conn_01x22", "U_ESP_R", "ESP32-S3_Header_1x22",
                    "Connector_PinHeader_2.54mm:PinHeader_1x22_P2.54mm_Vertical", ur_x, ur_y, 0,
                    "Barra de pinos femea lado direito (J3) ESP32-S3 DevKit",
                    ref_offset=(0, -32), val_offset=(0, 32), sheet_uuid=SHEET3_UUID)
    
    # Pin 1: GND - Route left to gx(128), UP to gy(30), LEFT to gx(120), DOWN to gy(34) into GND (rot=0)
    rp1_x, rp1_y = get_pin_canvas_coord(ur_x, ur_y, "Connector_Generic:Conn_01x22", 1)
    sch += wire(rp1_x, rp1_y, gx(128), rp1_y)
    sch += wire(gx(128), rp1_y, gx(128), gy(30))
    sch += wire(gx(128), gy(30), gx(120), gy(30))
    sch += wire(gx(120), gy(30), gx(120), gy(34))
    sch += pwr_inst("power:GND", "GND", gx(120), gy(34), 0)
    
    # Pin 2: GPIO_BUZZER (output)
    rp2_x, rp2_y = get_pin_canvas_coord(ur_x, ur_y, "Connector_Generic:Conn_01x22", 2)
    sch += wire(rp2_x, rp2_y, round(rp2_x - 22.86, 4), rp2_y)
    sch += h_label("GPIO_BUZZER", "output", round(rp2_x - 22.86, 4), rp2_y, 180)
    
    # Pin 3: VBAT_ADC (input)
    rp3_x, rp3_y = get_pin_canvas_coord(ur_x, ur_y, "Connector_Generic:Conn_01x22", 3)
    sch += wire(rp3_x, rp3_y, round(rp3_x - 22.86, 4), rp3_y)
    sch += h_label("VBAT_ADC", "input", round(rp3_x - 22.86, 4), rp3_y, 180)
    
    # Pins 4 to 21: No Connect
    for p in range(4, 22):
        px, py = get_pin_canvas_coord(ur_x, ur_y, "Connector_Generic:Conn_01x22", p)
        sch += no_connect(px, py)
        
    # Pin 22: +5V - Route left to gx(128), DOWN to gy(96), LEFT to gx(120), UP to gy(92) into +5V (rot=0)
    rp22_x, rp22_y = get_pin_canvas_coord(ur_x, ur_y, "Connector_Generic:Conn_01x22", 22)
    sch += wire(rp22_x, rp22_y, gx(128), rp22_y)
    sch += wire(gx(128), rp22_y, gx(128), gy(96))
    sch += wire(gx(128), gy(96), gx(120), gy(96))
    sch += wire(gx(120), gy(96), gx(120), gy(92))
    sch += pwr_inst("power:+5V", "+5V", gx(120), gy(92), 0)

    # Local decoupling near DevKit sockets with plenty of vertical spacing
    sch += text_comment("DESACOPLAMENTO LOCAL PROXIMO AOS SOQUETES", gx(165), gy(24), 1.3)
    cesp1_x, cesp1_y = gx(170), gy(40)
    sch += sym_inst("Device:C", "C_ESP1", "10uF", "Capacitor_SMD:C_0805_2012Metric", cesp1_x, cesp1_y, 0,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET3_UUID, justify="left")
    cp1_x, cp1_y = get_pin_canvas_coord(cesp1_x, cesp1_y, "Device:C", 1)
    cp2_x, cp2_y = get_pin_canvas_coord(cesp1_x, cesp1_y, "Device:C", 2)
    sch += wire(cp1_x, cp1_y, cp1_x, gy(30))
    sch += pwr_inst("power:+3V3", "+3V3", cp1_x, gy(30), 0)
    sch += wire(cp2_x, cp2_y, cp2_x, gy(50))
    sch += pwr_inst("power:GND", "GND", cp2_x, gy(50), 0)
    
    cesp2_x, cesp2_y = gx(186), gy(40)
    sch += sym_inst("Device:C", "C_ESP2", "100nF", "Capacitor_SMD:C_0805_2012Metric", cesp2_x, cesp2_y, 0,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET3_UUID, justify="left")
    cp1_x, cp1_y = get_pin_canvas_coord(cesp2_x, cesp2_y, "Device:C", 1)
    cp2_x, cp2_y = get_pin_canvas_coord(cesp2_x, cesp2_y, "Device:C", 2)
    sch += wire(cp1_x, cp1_y, cp1_x, gy(30))
    sch += pwr_inst("power:+3V3", "+3V3", cp1_x, gy(30), 0)
    sch += wire(cp2_x, cp2_y, cp2_x, gy(50))
    sch += pwr_inst("power:GND", "GND", cp2_x, gy(50), 0)
    
    # Engineering Notes & GPIO Mapping Table
    sch += text_comment("MAPEAMENTO DE PINOS E PERIFERICOS DO ESP32-S3 (DEVKITC-1)", gx(165), gy(68), 1.8)
    sch += text_comment("GPIO 01 (ADC1_CH0): Leitura de Tensao da Bateria (VBAT_ADC via divisor 1:2)", gx(165), gy(76), 1.1)
    sch += text_comment("GPIO 02 (ADC1_CH1): Sinal AC do Alicate Amperimetrico (AMP_ADC com bias 1.65V)", gx(165), gy(82), 1.1)
    sch += text_comment("GPIO 04: Entrada de Pulsos do Tacometro Optico/Hall (TACHO com filtro RC)", gx(165), gy(88), 1.1)
    sch += text_comment("GPIO 05: Barramento 1-Wire Termometro DS18B20 da Torre (1W_DQ + pull-up 4.7k)", gx(165), gy(94), 1.1)
    sch += text_comment("GPIO 06 / 07 / 08: Barramento I2S do Microfone/Vibrometro (I2S_SD, I2S_SCK, I2S_WS)", gx(165), gy(100), 1.1)
    sch += text_comment("GPIO 15 / 16 / 17: LEDs de Painel (Verde=RUN, Laranja=STANDBY, Azul=TELEMETRIA)", gx(165), gy(106), 1.1)
    sch += text_comment("GPIO 18: Buzzer Ativo Industrial para Alarmes e Beeps de Operacao (GPIO_BUZZER)", gx(165), gy(112), 1.1)
    sch += text_comment("GPIO 41 / 42: Barramento I2C compartilhado (I2C_SDA, I2C_SCL para MPU6050 & OLED)", gx(165), gy(118), 1.1)
    sch += text_comment("GPIO 43 / 44 / 47: UART Transceptor LoRa E32-TTL-100 (LORA_RX, LORA_TX, LORA_AUX)", gx(165), gy(124), 1.1)
    sch += text_comment("ALIMENTACAO: 5V IN no Pino 22 (J3) -> LDO onboard do DevKit produz +3.3V nos Pinos 1 e 2 (J1)", gx(165), gy(132), 1.1)

    sch += f"  (sheet_instances (path \"/{SHEET3_UUID}\" (page \"4\")))\n)\n"
    return sch

# ==============================================================================
# 4. BUILD SHEET 4: 04_Peripherals_LoRa.kicad_sch
# ==============================================================================
def build_sheet_4():
    syms = ["Connector_Generic:Conn_01x04", "Connector_Generic:Conn_01x07", "Connector_Generic:Conn_01x02",
            "Device:R", "Device:C", "Device:C_Polarized", "Mechanical:MountingHole", "power:+3V3", "power:GND"]
    sch = sch_header("04 - LoRa E32 RF, OLED Display, Status LEDs & Buzzer", S4_FILE_UUID, "v36.1", "A3")
    sch += make_lib_symbols(syms)
    
    # --------------------------------------------------------------------------
    # 1. LoRa E32-TTL-100 UART Transceiver Module (433MHz / 915MHz)
    # --------------------------------------------------------------------------
    sch += text_comment("COMUNICACAO RF LORA EBYTE E32-TTL-100 / ATUADORES DE INTERFACE HUMANO-MAQUINA", gx(15), gy(12), 2.0)
    sch += text_comment("TRANSCEPTOR LORA EBYTE E32-TTL-100 (433MHz / 915MHz)", gx(15), gy(18), 1.5)
    sch += text_comment("Capacitor bulk 470uF Low-ESR essencial para absorver surtos de transmissao RF sem brownout", gx(15), gy(22), 1.2)
    
    ulora_x, ulora_y = gx(35), gy(42)
    sch += sym_inst("Connector_Generic:Conn_01x07", "U_LORA", "E32-TTL-100",
                    "Connector_PinHeader_2.54mm:PinHeader_1x07_P2.54mm_Vertical", ulora_x, ulora_y, 0,
                    "Modulo LoRa EBYTE E32-TTL-100 1x07 pinos",
                    ref_offset=(10, -4), val_offset=(10, 4), sheet_uuid=SHEET4_UUID, justify="left")
    
    lp_pins = [get_pin_canvas_coord(ulora_x, ulora_y, "Connector_Generic:Conn_01x07", i) for i in range(1, 8)]
    
    # Pin 1: M0 -> GND (Normal mode: loop above to enter GND from top)
    sch += wire(lp_pins[0][0], lp_pins[0][1], gx(24), lp_pins[0][1])
    sch += wire(lp_pins[1][0], lp_pins[1][1], gx(24), lp_pins[1][1])
    sch += wire(gx(24), lp_pins[0][1], gx(24), lp_pins[1][1])
    sch += junction(gx(24), lp_pins[0][1])
    sch += junction(gx(24), lp_pins[1][1])
    sch += wire(gx(24), lp_pins[0][1], gx(24), gy(28))
    sch += wire(gx(24), gy(28), gx(28), gy(28))
    sch += wire(gx(28), gy(28), gx(28), gy(32))
    sch += pwr_inst("power:GND", "GND", gx(28), gy(32), 0)
    
    # Pin 3: RXD -> Connect to ESP32 LORA_TX (input to module)
    sch += wire(lp_pins[2][0], lp_pins[2][1], gx(15), lp_pins[2][1])
    sch += h_label("LORA_TX", "input", gx(15), lp_pins[2][1], 180)
    
    # Pin 4: TXD -> Connect to ESP32 LORA_RX (output from module)
    sch += wire(lp_pins[3][0], lp_pins[3][1], gx(15), lp_pins[3][1])
    sch += h_label("LORA_RX", "output", gx(15), lp_pins[3][1], 180)
    
    # Pin 5: AUX -> Connect to ESP32 LORA_AUX (output from module)
    sch += wire(lp_pins[4][0], lp_pins[4][1], gx(15), lp_pins[4][1])
    sch += h_label("LORA_AUX", "output", gx(15), lp_pins[4][1], 180)
    
    # Pin 6: VCC -> +3V3 (enter from below)
    sch += wire(lp_pins[5][0], lp_pins[5][1], gx(20), lp_pins[5][1])
    sch += wire(gx(20), lp_pins[5][1], gx(20), gy(56))
    sch += wire(gx(20), gy(56), gx(16), gy(56))
    sch += wire(gx(16), gy(56), gx(16), gy(50))
    sch += pwr_inst("power:+3V3", "+3V3", gx(16), gy(50), 0)
    
    # Pin 7: GND (enter from above)
    sch += wire(lp_pins[6][0], lp_pins[6][1], gx(28), lp_pins[6][1])
    sch += wire(gx(28), lp_pins[6][1], gx(28), gy(56))
    sch += pwr_inst("power:GND", "GND", gx(28), gy(56), 0)
    
    # LoRa Bulk & HF Decoupling Capacitors
    cbulk_x, cbulk_y = gx(60), gy(42)
    sch += sym_inst("Device:C_Polarized", "C_LORA_BULK", "470uF 16V", "Capacitor_THT:CP_Radial_D8.0mm_P3.50mm", cbulk_x, cbulk_y, 0,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET4_UUID, justify="left")
    cb1_x, cb1_y = get_pin_canvas_coord(cbulk_x, cbulk_y, "Device:C_Polarized", 1) # gy(39)
    cb2_x, cb2_y = get_pin_canvas_coord(cbulk_x, cbulk_y, "Device:C_Polarized", 2) # gy(45)
    sch += wire(cb1_x, cb1_y, cb1_x, gy(30))
    sch += pwr_inst("power:+3V3", "+3V3", cb1_x, gy(30), 0)
    sch += wire(cb2_x, cb2_y, cb2_x, gy(52))
    sch += pwr_inst("power:GND", "GND", cb2_x, gy(52), 0)
    
    clora_x, clora_y = gx(80), gy(42)
    sch += sym_inst("Device:C", "C_LORA_DEC", "100nF", "Capacitor_SMD:C_0805_2012Metric", clora_x, clora_y, 0,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET4_UUID, justify="left")
    cld1_x, cld1_y = get_pin_canvas_coord(clora_x, clora_y, "Device:C", 1)
    cld2_x, cld2_y = get_pin_canvas_coord(clora_x, clora_y, "Device:C", 2)
    sch += wire(cld1_x, cld1_y, cld1_x, gy(30))
    sch += pwr_inst("power:+3V3", "+3V3", cld1_x, gy(30), 0)
    sch += wire(cld2_x, cld2_y, cld2_x, gy(52))
    sch += pwr_inst("power:GND", "GND", cld2_x, gy(52), 0)
    
    # --------------------------------------------------------------------------
    # 2. SSD1306 0.96" I2C OLED Display
    # --------------------------------------------------------------------------
    sch += text_comment("DISPLAY OLED GRAFICO 0.96 POL SSD1306 I2C", gx(15), gy(68), 1.5)
    
    joled_x, joled_y = gx(35), gy(84)
    sch += sym_inst("Connector_Generic:Conn_01x04", "J_OLED", "Display_OLED_4P",
                    "Connector_PinHeader_2.54mm:PinHeader_1x04_P2.54mm_Vertical", joled_x, joled_y, 0,
                    "Conector 1x04 para display OLED 0.96 I2C",
                    ref_offset=(10, -3), val_offset=(10, 3), sheet_uuid=SHEET4_UUID, justify="left")
    op_pins = [get_pin_canvas_coord(joled_x, joled_y, "Connector_Generic:Conn_01x04", i) for i in range(1, 5)]
    
    # Pin 1: GND (enter from above)
    sch += wire(op_pins[0][0], op_pins[0][1], gx(28), op_pins[0][1])
    sch += wire(gx(28), op_pins[0][1], gx(28), gy(72))
    sch += wire(gx(28), gy(72), gx(31), gy(72))
    sch += wire(gx(31), gy(72), gx(31), gy(76))
    sch += pwr_inst("power:GND", "GND", gx(31), gy(76), 0)
    
    # Pin 2: VCC -> +3V3 (enter from below)
    sch += wire(op_pins[1][0], op_pins[1][1], gx(22), op_pins[1][1])
    sch += wire(gx(22), op_pins[1][1], gx(22), gy(74))
    sch += pwr_inst("power:+3V3", "+3V3", gx(22), gy(74), 0)
    
    # Pin 3: SCL
    sch += wire(op_pins[2][0], op_pins[2][1], gx(15), op_pins[2][1])
    sch += h_label("I2C_SCL", "bidirectional", gx(15), op_pins[2][1], 180)
    
    # Pin 4: SDA
    sch += wire(op_pins[3][0], op_pins[3][1], gx(15), op_pins[3][1])
    sch += h_label("I2C_SDA", "bidirectional", gx(15), op_pins[3][1], 180)
    
    # Decoupling Cap C_OLED (100nF)
    coled_x, coled_y = gx(60), gy(84)
    sch += sym_inst("Device:C", "C_OLED", "100nF", "Capacitor_SMD:C_0805_2012Metric", coled_x, coled_y, 0,
                    ref_offset=(3.0, -1.5), val_offset=(3.0, 1.5), sheet_uuid=SHEET4_UUID, justify="left")
    co1_x, co1_y = get_pin_canvas_coord(coled_x, coled_y, "Device:C", 1)
    co2_x, co2_y = get_pin_canvas_coord(coled_x, coled_y, "Device:C", 2)
    sch += wire(co1_x, co1_y, co1_x, gy(74))
    sch += pwr_inst("power:+3V3", "+3V3", co1_x, gy(74), 0)
    sch += wire(co2_x, co2_y, co2_x, gy(94))
    sch += pwr_inst("power:GND", "GND", co2_x, gy(94), 0)
    
    # --------------------------------------------------------------------------
    # 3. Status LEDs (Green, Orange, Blue)
    # --------------------------------------------------------------------------
    sch += text_comment("LEDS DE STATUS DO PAINEL (VERDE, LARANJA, AZUL)", gx(15), gy(108), 1.5)
    
    jleds_x, jleds_y = gx(26), gy(126)
    sch += sym_inst("Connector_Generic:Conn_01x04", "J_LEDS", "Status_LEDs_4P",
                    "Connector_JST:JST_XH_B4B-XH-A_1x04_P2.50mm_Vertical", jleds_x, jleds_y, 0,
                    "Conector 1x04 para chicote dos 3 LEDs de painel",
                    ref_offset=(0, -8), val_offset=(0, 8), sheet_uuid=SHEET4_UUID)
    lp_pins = [get_pin_canvas_coord(jleds_x, jleds_y, "Connector_Generic:Conn_01x04", i) for i in range(1, 5)]
    
    # Pin 4: Common Cathode -> GND (enter from above, routed to gx(12))
    sch += wire(lp_pins[3][0], lp_pins[3][1], gx(12), lp_pins[3][1])
    sch += wire(gx(12), lp_pins[3][1], gx(12), gy(134))
    sch += pwr_inst("power:GND", "GND", gx(12), gy(134), 0)
    
    # Current limit resistors R_LED1, R_LED2, R_LED3 (330R) horizontal (rot 90)
    led_info = [
        (0, "R_LED1", "GPIO_LED_GRN", gy(114)),
        (1, "R_LED2", "GPIO_LED_ORG", gy(126)),
        (2, "R_LED3", "GPIO_LED_BLU", gy(138))
    ]
    
    for idx, r_ref, sig_name, ry in led_info:
        rx = gx(55)
        sch += sym_inst("Device:R", r_ref, "330R", "Resistor_SMD:R_0805_2012Metric", rx, ry, 90,
                        ref_offset=(0, -3.81), val_offset=(0, 3.81), sheet_uuid=SHEET4_UUID)
        rp1_x, rp1_y = get_pin_canvas_coord(rx, ry, "Device:R", 1, 90)
        rp2_x, rp2_y = get_pin_canvas_coord(rx, ry, "Device:R", 2, 90)
        
        # Connect connector pin to resistor Pin 1
        sch += wire(lp_pins[idx][0], lp_pins[idx][1], gx(42), lp_pins[idx][1])
        sch += wire(gx(42), lp_pins[idx][1], gx(42), ry)
        sch += wire(gx(42), ry, rp1_x, rp1_y)
        
        # Connect resistor Pin 2 to hierarchical label
        sch += wire(rp2_x, rp2_y, gx(100), rp2_y)
        sch += h_label(sig_name, "input", gx(100), rp2_y, 0)
        
    # --------------------------------------------------------------------------
    # 4. Buzzer
    # --------------------------------------------------------------------------
    sch += text_comment("BUZZER SONORO ATIVO DE ALERTA INDUSTRIAL", gx(15), gy(152), 1.5)
    
    jbuz_x, jbuz_y = gx(26), gy(166)
    sch += sym_inst("Connector_Generic:Conn_01x02", "J_BUZ", "Buzzer_2P",
                    "Connector_JST:JST_XH_B2B-XH-A_1x02_P2.50mm_Vertical", jbuz_x, jbuz_y, 0,
                    "Conector 1x02 para buzzer piezoeletrico de painel",
                    ref_offset=(10, -5), val_offset=(10, 5), sheet_uuid=SHEET4_UUID, justify="left")
    bp1_x, bp1_y = get_pin_canvas_coord(jbuz_x, jbuz_y, "Connector_Generic:Conn_01x02", 1) # gy(166)
    bp2_x, bp2_y = get_pin_canvas_coord(jbuz_x, jbuz_y, "Connector_Generic:Conn_01x02", 2) # gy(168)
    
    # Pin 1: GND (loop above to enter GND from top)
    sch += wire(bp1_x, bp1_y, gx(16), bp1_y)
    sch += wire(gx(16), bp1_y, gx(16), gy(156))
    sch += wire(gx(16), gy(156), gx(19), gy(156))
    sch += wire(gx(19), gy(156), gx(19), gy(160))
    sch += pwr_inst("power:GND", "GND", gx(19), gy(160), 0)
    
    # Pin 2: Resistor R_BUZ (100R) horizontal (rot 90) at (gx(55), gy(168))
    rbz_x, rbz_y = gx(55), gy(168)
    sch += sym_inst("Device:R", "R_BUZ", "100R", "Resistor_SMD:R_0805_2012Metric", rbz_x, rbz_y, 90,
                    ref_offset=(0, -3.81), val_offset=(0, 3.81), sheet_uuid=SHEET4_UUID)
    rbz_p1_x, rbz_p1_y = get_pin_canvas_coord(rbz_x, rbz_y, "Device:R", 1, 90)
    rbz_p2_x, rbz_p2_y = get_pin_canvas_coord(rbz_x, rbz_y, "Device:R", 2, 90)
    sch += wire(bp2_x, bp2_y, rbz_p1_x, rbz_p1_y)
    sch += wire(rbz_p2_x, rbz_p2_y, gx(100), rbz_p2_y)
    sch += h_label("GPIO_BUZZER", "input", gx(100), rbz_p2_y, 0)
    
    # --------------------------------------------------------------------------
    # 5. Mounting Holes
    # --------------------------------------------------------------------------
    sch += text_comment("FUROS DE FIXACAO MECANICA (M3) NOS 4 CANTOS DA PLACA (90x65mm)", gx(15), gy(182), 1.3)
    holes = [("H1", gx(30)), ("H2", gx(55)), ("H3", gx(80)), ("H4", gx(105))]
    for href, hx in holes:
        sch += sym_inst("Mechanical:MountingHole", href, "M3", "MountingHole:MountingHole_3.2mm_M3",
                        hx, gy(192), 0, "Furo M3 metalizado", in_bom=False, sheet_uuid=SHEET4_UUID)
        
    sch += f"  (sheet_instances (path \"/{SHEET4_UUID}\" (page \"5\")))\n)\n"
    return sch

# ==============================================================================
# 5. BUILD ROOT SCHEMATIC: amemiya_main_node.kicad_sch
# ==============================================================================
def build_root_sheet():
    sch = sch_header("Amemiya Metrology - Main Node Carrier ESP32-S3", ROOT_UUID, "v36.1", "A3")
    sch += "  (lib_symbols\n  )\n"
    
    sch += text_comment("SISTEMA DE METROLOGIA LEAN TECH - ARQUITETURA MODULAR DO NO PRINCIPAL (CARRIER BOARD)", gx(18), gy(12), 2.2)
    sch += text_comment("Diagrama de Blocos Hierarquicos com Fluxo Sinalizacao Inputs -> CPU -> Outputs (Zero DRC / 0 ERC)", gx(18), gy(16), 1.3)
    
    # Geometric Coordinates setup for A3 Page (Width: 330 grid units, Height: 234 grid units)
    # Column 1 (Left): Inputs
    s1_x, s1_y = gx(18), gy(20)
    s1_w, s1_h = gx(48), gy(20)
    vbat_pin_y = gy(30)
    
    s2_x, s2_y = gx(18), gy(58)
    s2_w, s2_h = gx(48), gy(98)
    amp_pin_y = gy(70)
    tacho_pin_y = gy(80)
    dq_pin_y = gy(90)
    sd_pin_y = gy(100)
    sck_pin_y = gy(110)
    ws_pin_y = gy(120)
    scl_pin_y = gy(132)
    sda_pin_y = gy(142)
    
    # Column 2 (Center): Brain (ESP32-S3 MCU)
    s3_x, s3_y = gx(95), gy(20)
    s3_w, s3_h = gx(70), gy(136)
    lora_tx_y = gy(36)
    lora_rx_y = gy(46)
    lora_aux_y = gy(56)
    led_grn_y = gy(72)
    led_org_y = gy(82)
    led_blu_y = gy(92)
    buz_y = gy(106)
    
    # Column 3 (Right): Outputs & RF
    s4_x, s4_y = gx(195), gy(20)
    s4_w, s4_h = gx(55), gy(136)
    scl_out_y = gy(126)
    sda_out_y = gy(136)

    # Sheet 1: Power Supply at (gx(18), gy(22))
    sch += f"""  (sheet (at {s1_x} {s1_y}) (size {s1_w} {s1_h}) (fields_autoplaced yes)
    (stroke (width 0.1524) (type solid))
    (fill (color 245 245 245 0.2))
    (uuid "{SHEET1_UUID}")
    (property "Sheetname" "Power_Supply" (at {s1_x} {s1_y - 2.54} 0) (effects (font (size 1.5 1.5)) (justify left bottom)))
    (property "Sheetfile" "01_Power_Supply.kicad_sch" (at {s1_x} {s1_y + s1_h + 2.54} 0) (effects (font (size 1.27 1.27)) (justify left top)))
    (pin "VBAT_ADC" output (at {s1_x + s1_w} {vbat_pin_y} 0) (effects (font (size 1.27 1.27)) (justify right)))
  )
"""

    # Sheet 2: Sensors & AFE at (gx(18), gy(58))
    sch += f"""  (sheet (at {s2_x} {s2_y}) (size {s2_w} {s2_h}) (fields_autoplaced yes)
    (stroke (width 0.1524) (type solid))
    (fill (color 245 245 245 0.2))
    (uuid "{SHEET2_UUID}")
    (property "Sheetname" "Sensors_AFE" (at {s2_x} {s2_y - 2.54} 0) (effects (font (size 1.5 1.5)) (justify left bottom)))
    (property "Sheetfile" "02_Sensors_AFE.kicad_sch" (at {s2_x} {s2_y + s2_h + 2.54} 0) (effects (font (size 1.27 1.27)) (justify left top)))
    (pin "AMP_ADC" output (at {s2_x + s2_w} {amp_pin_y} 0) (effects (font (size 1.27 1.27)) (justify right)))
    (pin "TACHO" output (at {s2_x + s2_w} {tacho_pin_y} 0) (effects (font (size 1.27 1.27)) (justify right)))
    (pin "1W_DQ" bidirectional (at {s2_x + s2_w} {dq_pin_y} 0) (effects (font (size 1.27 1.27)) (justify right)))
    (pin "I2S_SD" output (at {s2_x + s2_w} {sd_pin_y} 0) (effects (font (size 1.27 1.27)) (justify right)))
    (pin "I2S_SCK" input (at {s2_x + s2_w} {sck_pin_y} 0) (effects (font (size 1.27 1.27)) (justify right)))
    (pin "I2S_WS" input (at {s2_x + s2_w} {ws_pin_y} 0) (effects (font (size 1.27 1.27)) (justify right)))
    (pin "I2C_SCL" bidirectional (at {s2_x + s2_w} {scl_pin_y} 0) (effects (font (size 1.27 1.27)) (justify right)))
    (pin "I2C_SDA" bidirectional (at {s2_x + s2_w} {sda_pin_y} 0) (effects (font (size 1.27 1.27)) (justify right)))
  )
"""

    # Sheet 3: MCU ESP32-S3 at (gx(95), gy(22))
    sch += f"""  (sheet (at {s3_x} {s3_y}) (size {s3_w} {s3_h}) (fields_autoplaced yes)
    (stroke (width 0.1524) (type solid))
    (fill (color 245 245 245 0.2))
    (uuid "{SHEET3_UUID}")
    (property "Sheetname" "MCU_ESP32S3" (at {s3_x} {s3_y - 2.54} 0) (effects (font (size 1.5 1.5)) (justify left bottom)))
    (property "Sheetfile" "03_MCU_ESP32S3.kicad_sch" (at {s3_x} {s3_y + s3_h + 2.54} 0) (effects (font (size 1.27 1.27)) (justify left top)))
    (pin "VBAT_ADC" input (at {s3_x} {vbat_pin_y} 180) (effects (font (size 1.27 1.27)) (justify left)))
    (pin "AMP_ADC" input (at {s3_x} {amp_pin_y} 180) (effects (font (size 1.27 1.27)) (justify left)))
    (pin "TACHO" input (at {s3_x} {tacho_pin_y} 180) (effects (font (size 1.27 1.27)) (justify left)))
    (pin "1W_DQ" bidirectional (at {s3_x} {dq_pin_y} 180) (effects (font (size 1.27 1.27)) (justify left)))
    (pin "I2S_SD" input (at {s3_x} {sd_pin_y} 180) (effects (font (size 1.27 1.27)) (justify left)))
    (pin "I2S_SCK" output (at {s3_x} {sck_pin_y} 180) (effects (font (size 1.27 1.27)) (justify left)))
    (pin "I2S_WS" output (at {s3_x} {ws_pin_y} 180) (effects (font (size 1.27 1.27)) (justify left)))
    (pin "I2C_SCL" bidirectional (at {s3_x} {scl_pin_y} 180) (effects (font (size 1.27 1.27)) (justify left)))
    (pin "I2C_SDA" bidirectional (at {s3_x} {sda_pin_y} 180) (effects (font (size 1.27 1.27)) (justify left)))
    (pin "LORA_TX" output (at {s3_x + s3_w} {lora_tx_y} 0) (effects (font (size 1.27 1.27)) (justify right)))
    (pin "LORA_RX" input (at {s3_x + s3_w} {lora_rx_y} 0) (effects (font (size 1.27 1.27)) (justify right)))
    (pin "LORA_AUX" input (at {s3_x + s3_w} {lora_aux_y} 0) (effects (font (size 1.27 1.27)) (justify right)))
    (pin "GPIO_LED_GRN" output (at {s3_x + s3_w} {led_grn_y} 0) (effects (font (size 1.27 1.27)) (justify right)))
    (pin "GPIO_LED_ORG" output (at {s3_x + s3_w} {led_org_y} 0) (effects (font (size 1.27 1.27)) (justify right)))
    (pin "GPIO_LED_BLU" output (at {s3_x + s3_w} {led_blu_y} 0) (effects (font (size 1.27 1.27)) (justify right)))
    (pin "GPIO_BUZZER" output (at {s3_x + s3_w} {buz_y} 0) (effects (font (size 1.27 1.27)) (justify right)))
  )
"""

    # Sheet 4: Peripherals & LoRa at (gx(195), gy(22))
    sch += f"""  (sheet (at {s4_x} {s4_y}) (size {s4_w} {s4_h}) (fields_autoplaced yes)
    (stroke (width 0.1524) (type solid))
    (fill (color 245 245 245 0.2))
    (uuid "{SHEET4_UUID}")
    (property "Sheetname" "Peripherals_LoRa" (at {s4_x} {s4_y - 2.54} 0) (effects (font (size 1.5 1.5)) (justify left bottom)))
    (property "Sheetfile" "04_Peripherals_LoRa.kicad_sch" (at {s4_x} {s4_y + s4_h + 2.54} 0) (effects (font (size 1.27 1.27)) (justify left top)))
    (pin "LORA_TX" input (at {s4_x} {lora_tx_y} 180) (effects (font (size 1.27 1.27)) (justify left)))
    (pin "LORA_RX" output (at {s4_x} {lora_rx_y} 180) (effects (font (size 1.27 1.27)) (justify left)))
    (pin "LORA_AUX" output (at {s4_x} {lora_aux_y} 180) (effects (font (size 1.27 1.27)) (justify left)))
    (pin "GPIO_LED_GRN" input (at {s4_x} {led_grn_y} 180) (effects (font (size 1.27 1.27)) (justify left)))
    (pin "GPIO_LED_ORG" input (at {s4_x} {led_org_y} 180) (effects (font (size 1.27 1.27)) (justify left)))
    (pin "GPIO_LED_BLU" input (at {s4_x} {led_blu_y} 180) (effects (font (size 1.27 1.27)) (justify left)))
    (pin "GPIO_BUZZER" input (at {s4_x} {buz_y} 180) (effects (font (size 1.27 1.27)) (justify left)))
    (pin "I2C_SCL" bidirectional (at {s4_x} {scl_out_y} 180) (effects (font (size 1.27 1.27)) (justify left)))
    (pin "I2C_SDA" bidirectional (at {s4_x} {sda_out_y} 180) (effects (font (size 1.27 1.27)) (justify left)))
  )
"""
    
    # Connecting Wires:
    # 1. Power_Supply to MCU (VBAT_ADC)
    sch += wire(s1_x + s1_w, vbat_pin_y, s3_x, vbat_pin_y)
    
    # 2. Sensors_AFE to MCU (Analog & Digital Inputs)
    sch += wire(s2_x + s2_w, amp_pin_y, s3_x, amp_pin_y)
    sch += wire(s2_x + s2_w, tacho_pin_y, s3_x, tacho_pin_y)
    sch += wire(s2_x + s2_w, dq_pin_y, s3_x, dq_pin_y)
    sch += wire(s2_x + s2_w, sd_pin_y, s3_x, sd_pin_y)
    sch += wire(s2_x + s2_w, sck_pin_y, s3_x, sck_pin_y)
    sch += wire(s2_x + s2_w, ws_pin_y, s3_x, ws_pin_y)
    
    # I2C Bus wires (Sensors_AFE -> MCU and Sensors_AFE -> Peripherals_LoRa routed cleanly below MCU sheet)
    # SCL routing:
    sch += wire(s2_x + s2_w, scl_pin_y, gx(78), scl_pin_y)
    sch += wire(gx(78), scl_pin_y, s3_x, scl_pin_y)
    sch += junction(gx(78), scl_pin_y)
    sch += wire(gx(78), scl_pin_y, gx(78), gy(162))
    sch += wire(gx(78), gy(162), gx(180), gy(162))
    sch += wire(gx(180), gy(162), gx(180), scl_out_y)
    sch += wire(gx(180), scl_out_y, s4_x, scl_out_y)
    
    # SDA routing:
    sch += wire(s2_x + s2_w, sda_pin_y, gx(82), sda_pin_y)
    sch += wire(gx(82), sda_pin_y, s3_x, sda_pin_y)
    sch += junction(gx(82), sda_pin_y)
    sch += wire(gx(82), sda_pin_y, gx(82), gy(166))
    sch += wire(gx(82), gy(166), gx(184), gy(166))
    sch += wire(gx(184), gy(166), gx(184), sda_out_y)
    sch += wire(gx(184), sda_out_y, s4_x, sda_out_y)
    
    # 3. MCU to Peripherals_LoRa (UART, LEDs, Buzzer)
    sch += wire(s3_x + s3_w, lora_tx_y, s4_x, lora_tx_y)
    sch += wire(s3_x + s3_w, lora_rx_y, s4_x, lora_rx_y)
    sch += wire(s3_x + s3_w, lora_aux_y, s4_x, lora_aux_y)
    sch += wire(s3_x + s3_w, led_grn_y, s4_x, led_grn_y)
    sch += wire(s3_x + s3_w, led_org_y, s4_x, led_org_y)
    sch += wire(s3_x + s3_w, led_blu_y, s4_x, led_blu_y)
    sch += wire(s3_x + s3_w, buz_y, s4_x, buz_y)
    
    sch += f"""  (sheet_instances
    (path "/" (page "1"))
  )
)
"""
    return sch

def main():
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    
    s1_content = build_sheet_1()
    s1_path = os.path.join(OUTPUT_DIR, "01_Power_Supply.kicad_sch")
    with open(s1_path, "w", encoding="utf-8") as f:
        f.write(s1_content)
    print(f"Generated Sheet 1: {s1_path}")
    
    s2_content = build_sheet_2()
    s2_path = os.path.join(OUTPUT_DIR, "02_Sensors_AFE.kicad_sch")
    with open(s2_path, "w", encoding="utf-8") as f:
        f.write(s2_content)
    print(f"Generated Sheet 2: {s2_path}")
    
    s3_content = build_sheet_3()
    s3_path = os.path.join(OUTPUT_DIR, "03_MCU_ESP32S3.kicad_sch")
    with open(s3_path, "w", encoding="utf-8") as f:
        f.write(s3_content)
    print(f"Generated Sheet 3: {s3_path}")
    
    s4_content = build_sheet_4()
    s4_path = os.path.join(OUTPUT_DIR, "04_Peripherals_LoRa.kicad_sch")
    with open(s4_path, "w", encoding="utf-8") as f:
        f.write(s4_content)
    print(f"Generated Sheet 4: {s4_path}")
    
    root_content = build_root_sheet()
    root_path = os.path.join(OUTPUT_DIR, "amemiya_main_node.kicad_sch")
    with open(root_path, "w", encoding="utf-8") as f:
        f.write(root_content)
    print(f"Generated Root Sheet: {root_path}")

if __name__ == "__main__":
    main()
