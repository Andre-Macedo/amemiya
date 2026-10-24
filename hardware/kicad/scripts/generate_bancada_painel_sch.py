#!/usr/bin/env python3
"""
Amemiya - Bancada de Testes de Motores & Analise de Vibracao
Gerador de Esquematico do Painel Eletrico de Forca e Comando (KiCad 9)
Normas: ABNT NBR 5410, NR-10 e NR-12

Versao: v2.0 - Motor de Tanquinho 220V c/ Capacitor + Tacometro Digital NJK-5002C
              (Sem CLP ativo - CLP movido para notas de expansao futura)
"""

import os
import sys
import uuid
import subprocess

PROJECT_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "amemiya_bancada_painel"))
SCH_PATH = os.path.join(PROJECT_DIR, "amemiya_bancada_painel.kicad_sch")
PRO_PATH = os.path.join(PROJECT_DIR, "amemiya_bancada_painel.kicad_pro")
PDF_PATH = os.path.join(PROJECT_DIR, "amemiya_bancada_painel.pdf")
PNG_PATH = os.path.join(PROJECT_DIR, "amemiya_bancada_painel.png")
KICAD_CLI = r"C:\Program Files\KiCad\9.0\bin\kicad-cli.exe"

def u(): return str(uuid.uuid4())

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

def net_label(name, x, y, rot=0):
    just = "right" if rot == 180 else "left"
    return f"""  (label "{name}" (at {x} {y} {rot})
    (effects (font (size 1.27 1.27)) (justify {just}))
    (uuid "{u()}")
  )\n"""

def text_comment(text_str, x, y, size=1.5, bold=True):
    b_str = " bold" if bold else ""
    return f"""  (text "{text_str}" (at {x} {y} 0)
    (effects (font (size {size} {size}){b_str}) (justify left bottom))
    (uuid "{u()}")
  )\n"""

def text_box(text_str, x, y, size=1.1, line_height=3.8):
    s = ""
    lines = text_str.strip().split("\n")
    for i, l in enumerate(lines):
        if not l.strip():
            continue
        is_heading = l.startswith("*") or l.endswith(":") or "NORMAS" in l or "ESPECIFICACOES" in l or "INTERTRAVAMENTO" in l or "EXPANSAO" in l
        bold_flag = " bold" if is_heading else ""
        cur_y = round(y + i * line_height, 2)
        s += f"""  (text "{l}" (at {x} {cur_y} 0)
    (effects (font (size {size} {size}){bold_flag}) (justify left top))
    (uuid "{u()}")
  )\n"""
    return s

def draw_box(x1, y1, x2, y2, stroke_width=0.3):
    pts = f"(xy {x1} {y1}) (xy {x2} {y1}) (xy {x2} {y2}) (xy {x1} {y2}) (xy {x1} {y1})"
    return f"""  (polyline (pts {pts})
    (stroke (width {stroke_width}) (type default))
    (fill (type none))
    (uuid "{u()}")
  )\n"""

SHEET_UUID = "b7a24c10-5321-4d92-94b2-a4f5b8209301"

def sym_instance(ref, unit=1):
    return f"""    (instances
      (project "amemiya_bancada_painel"
        (path "/{SHEET_UUID}"
          (reference "{ref}")
          (unit {unit})
        )
      )
      (project ""
        (path "/{SHEET_UUID}"
          (reference "{ref}")
          (unit {unit})
        )
      )
    )"""

def build_schematic():
    os.makedirs(PROJECT_DIR, exist_ok=True)
    
    pro_content = """{
  "meta": {
    "filename": "amemiya_bancada_painel.kicad_pro",
    "version": 1
  },
  "schematic": {
    "annotate_start_num": 1,
    "drawing": {
      "default_line_thickness": 0.1524,
      "default_text_size": 1.27
    }
  }
}
"""
    with open(PRO_PATH, "w", encoding="utf-8") as f:
        f.write(pro_content)
        
    sch = """(kicad_sch
  (version 20231120)
  (generator "kicad")
  (generator_version "9.0")
  (uuid "b7a24c10-5321-4d92-94b2-a4f5b8209301")
  (paper "A3")
  (title_block
    (title "Painel Eletrico Bancada de Testes (NR-10 / NR-12)")
    (date "2026-10-24")
    (rev "v2.2")
    (company "Lean Tech Metrology & Industrial IoT Solutions")
    (comment 1 "Motor 220V Tanquinho c/ Dimmer TRIAC e Cap. Permanente")
    (comment 2 "Fonte 24V DC + Tacometro Digital CF5135B / NJK-5002C")
    (comment 3 "Seguranca NR-12: Emergencia (LED Vermelho) + Tampa (LED Amarelo)")
  )
  (lib_symbols
"""
    
    # 1. Terminal Block Single DIN Rail Through (Borne Sak Passagem)
    sch += """    (symbol "Industrial:Terminal_Passagem"
      (in_bom yes) (on_board yes)
      (property "Reference" "TB" (at 3.5 0 0) (effects (font (size 1.0 1.0)) (justify left)))
      (property "Value" "Borne_DIN" (at 3.5 -2.2 0) (effects (font (size 0.8 0.8)) (justify left)))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "Terminal_Passagem_0_1"
        (circle (center 0 0) (radius 1.8) (stroke (width 0.254) (type default)) (fill (type background)))
        (circle (center 0 0) (radius 0.6) (stroke (width 0.254) (type default)) (fill (type none)))
      )
      (symbol "Terminal_Passagem_1_1"
        (pin passive line (at 0 3.81 270) (length 2.01) (name "IN" (effects (font (size 0.7 0.7)) (hide yes))) (number "1" (effects (font (size 0.7 0.7)) (hide yes))))
        (pin passive line (at 0 -3.81 90) (length 2.01) (name "OUT" (effects (font (size 0.7 0.7)) (hide yes))) (number "2" (effects (font (size 0.7 0.7)) (hide yes))))
      )
    )
"""

    # 2. CircuitBreaker_2P (Pole spacing 10mm: -5.0 and +5.0)
    sch += """    (symbol "Industrial:CircuitBreaker_2P"
      (in_bom yes) (on_board yes)
      (property "Reference" "Q" (at -8.0 0 90) (effects (font (size 1.27 1.27))))
      (property "Value" "Disjuntor_2P" (at 8.0 0 90) (effects (font (size 1.27 1.27))))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "CircuitBreaker_2P_0_1"
        (rectangle (start -8.0 6.0) (end 8.0 -6.0) (stroke (width 0.254) (type default)) (fill (type background)))
        (polyline (pts (xy -5.0 5.0) (xy -5.0 1.5) (xy -6.5 -1.5) (xy -5.0 -5.0)) (stroke (width 0.254) (type default)) (fill (type none)))
        (polyline (pts (xy 5.0 5.0) (xy 5.0 1.5) (xy 3.5 -1.5) (xy 5.0 -5.0)) (stroke (width 0.254) (type default)) (fill (type none)))
        (polyline (pts (xy -6.5 -0.5) (xy 3.5 -0.5)) (stroke (width 0.15) (type dash)) (fill (type none)))
      )
      (symbol "CircuitBreaker_2P_1_1"
        (pin passive line (at -5.0 7.62 270) (length 2.54) (name "1" (effects (font (size 0.8 0.8)))) (number "1" (effects (font (size 0.8 0.8)))))
        (pin passive line (at -5.0 -7.62 90) (length 2.54) (name "2" (effects (font (size 0.8 0.8)))) (number "2" (effects (font (size 0.8 0.8)))))
        (pin passive line (at 5.0 7.62 270) (length 2.54) (name "3" (effects (font (size 0.8 0.8)))) (number "3" (effects (font (size 0.8 0.8)))))
        (pin passive line (at 5.0 -7.62 90) (length 2.54) (name "4" (effects (font (size 0.8 0.8)))) (number "4" (effects (font (size 0.8 0.8)))))
      )
    )
"""

    # 3. Contactor_Power_2P (Strictly 2 Poles: -5.0 and +5.0 - No loose terminals!)
    sch += """    (symbol "Industrial:Contactor_Power_2P"
      (in_bom yes) (on_board yes)
      (property "Reference" "K" (at -8.0 0 90) (effects (font (size 1.27 1.27))))
      (property "Value" "Contator_2P" (at 8.0 0 90) (effects (font (size 1.27 1.27))))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "Contactor_Power_2P_0_1"
        (rectangle (start -8.0 6.0) (end 8.0 -6.0) (stroke (width 0.254) (type default)) (fill (type background)))
        (polyline (pts (xy -5.0 5.0) (xy -5.0 1.5) (xy -6.9 -2.0)) (stroke (width 0.254) (type default)) (fill (type none)))
        (circle (center -5.0 -4.5) (radius 0.6) (stroke (width 0.254) (type default)) (fill (type none)))
        (polyline (pts (xy 5.0 5.0) (xy 5.0 1.5) (xy 3.1 -2.0)) (stroke (width 0.254) (type default)) (fill (type none)))
        (circle (center 5.0 -4.5) (radius 0.6) (stroke (width 0.254) (type default)) (fill (type none)))
        (polyline (pts (xy -6.0 -0.5) (xy 4.0 -0.5)) (stroke (width 0.15) (type dash)) (fill (type none)))
      )
      (symbol "Contactor_Power_2P_1_1"
        (pin passive line (at -5.0 7.62 270) (length 2.54) (name "1" (effects (font (size 0.7 0.7)) (hide yes))) (number "1L1" (effects (font (size 0.85 0.85)))))
        (pin passive line (at -5.0 -7.62 90) (length 2.54) (name "2" (effects (font (size 0.7 0.7)) (hide yes))) (number "2T1" (effects (font (size 0.85 0.85)))))
        (pin passive line (at 5.0 7.62 270) (length 2.54) (name "3" (effects (font (size 0.7 0.7)) (hide yes))) (number "3L2" (effects (font (size 0.85 0.85)))))
        (pin passive line (at 5.0 -7.62 90) (length 2.54) (name "4" (effects (font (size 0.7 0.7)) (hide yes))) (number "4T2" (effects (font (size 0.85 0.85)))))
      )
    )
"""

    # 3.1 Dimmer AC 220V (TRIAC Speed Controller with Potentiometer)
    sch += """    (symbol "Industrial:Dimmer_AC_220V"
      (in_bom yes) (on_board yes)
      (property "Reference" "DIM" (at -14.0 0 90) (effects (font (size 1.27 1.27))))
      (property "Value" "Dimmer_220V" (at 14.0 0 90) (effects (font (size 1.27 1.27))))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "Dimmer_AC_220V_0_1"
        (rectangle (start -10.0 10.0) (end 10.0 -10.0) (stroke (width 0.3) (type default)) (fill (type background)))
        (circle (center 0 1.0) (radius 3.5) (stroke (width 0.254) (type default)) (fill (type outline)))
        (polyline (pts (xy 0 1.0) (xy 2.5 3.5)) (stroke (width 0.3) (type default)) (fill (type none)))
        (polyline (pts (xy -5.0 -2.5) (xy 5.0 4.5)) (stroke (width 0.254) (type default)) (fill (type none)))
        (polyline (pts (xy 3.8 4.5) (xy 5.0 4.5) (xy 5.0 3.3)) (stroke (width 0.254) (type default)) (fill (type none)))
        (text "DIMMER 220V" (at 0 7.5 0) (effects (font (size 1.1 1.1) bold)))
        (text "POT. VELOCIDADE" (at 0 -6.5 0) (effects (font (size 0.9 0.9))))
        (text "TRIAC 0-100%" (at 0 -8.5 0) (effects (font (size 0.8 0.8))))
      )
      (symbol "Dimmer_AC_220V_1_1"
        (pin power_in line (at -5.0 12.54 270) (length 2.54) (name "L_IN" (effects (font (size 0.8 0.8)))) (number "1" (effects (font (size 0.8 0.8)))))
        (pin power_in line (at 5.0 12.54 270) (length 2.54) (name "N_IN" (effects (font (size 0.8 0.8)))) (number "2" (effects (font (size 0.8 0.8)))))
        (pin power_out line (at -5.0 -12.54 90) (length 2.54) (name "L_OUT" (effects (font (size 0.8 0.8)))) (number "3" (effects (font (size 0.8 0.8)))))
        (pin power_out line (at 5.0 -12.54 90) (length 2.54) (name "N_OUT" (effects (font (size 0.8 0.8)))) (number "4" (effects (font (size 0.8 0.8)))))
      )
    )
"""

    # 4. Motor_Tanquinho_220V (Single-phase/Biphase 220V with integrated Run Capacitor)
    sch += """    (symbol "Industrial:Motor_Tanquinho_220V"
      (in_bom yes) (on_board yes)
      (property "Reference" "M" (at 0 14.0 0) (effects (font (size 1.27 1.27))))
      (property "Value" "Motor_Tanquinho" (at 0 -14.0 0) (effects (font (size 1.27 1.27))))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "Motor_Tanquinho_220V_0_1"
        (circle (center 0 0) (radius 8.5) (stroke (width 0.3) (type default)) (fill (type background)))
        (text "M" (at 0 2.5 0) (effects (font (size 3.8 3.8) bold)))
        (text "1~ 220V" (at 0 -1.5 0) (effects (font (size 1.5 1.5) bold)))
        (text "1/4 CV" (at 0 -4.5 0) (effects (font (size 1.2 1.2))))
        (rectangle (start 12.0 4.0) (end 18.0 -4.0) (stroke (width 0.254) (type default)) (fill (type background)))
        (polyline (pts (xy 13.5 2.5) (xy 16.5 2.5)) (stroke (width 0.3) (type default)) (fill (type none)))
        (polyline (pts (xy 13.5 -2.5) (xy 16.5 -2.5)) (stroke (width 0.3) (type default)) (fill (type none)))
        (text "C_MOT" (at 15.0 6.0 0) (effects (font (size 1.0 1.0) bold)))
        (text "12uF" (at 15.0 -6.0 0) (effects (font (size 1.0 1.0))))
        (polyline (pts (xy 8.5 2.0) (xy 12.0 2.0)) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy 8.5 -2.0) (xy 12.0 -2.0)) (stroke (width 0.2) (type default)) (fill (type none)))
      )
      (symbol "Motor_Tanquinho_220V_1_1"
        (pin passive line (at -5.0 11.04 270) (length 2.54) (name "L1" (effects (font (size 0.8 0.8)))) (number "1" (effects (font (size 0.8 0.8)))))
        (pin passive line (at 5.0 11.04 270) (length 2.54) (name "L2/N" (effects (font (size 0.8 0.8)))) (number "2" (effects (font (size 0.8 0.8)))))
        (pin passive line (at 22.0 0 180) (length 4.0) (name "PE" (effects (font (size 0.8 0.8)))) (number "3" (effects (font (size 0.8 0.8)))))
      )
    )
"""

    # 5. SMPS_24V (Clean, self-contained DIN Rail SMPS)
    sch += """    (symbol "Industrial:SMPS_24V"
      (in_bom yes) (on_board yes)
      (property "Reference" "T" (at 0 16.0 0) (effects (font (size 1.27 1.27))))
      (property "Value" "SMPS_24V_2.5A" (at 0 -16.0 0) (effects (font (size 1.27 1.27))))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "SMPS_24V_0_1"
        (rectangle (start -14.0 14.0) (end 14.0 -14.0) (stroke (width 0.3) (type default)) (fill (type background)))
        (polyline (pts (xy 0 14.0) (xy 0 -14.0)) (stroke (width 0.15) (type dash)) (fill (type none)))
        (text "AC IN" (at -7.0 8.5 0) (effects (font (size 1.5 1.5) bold)))
        (text "220V" (at -7.0 5.5 0) (effects (font (size 1.2 1.2))))
        (text "DC OUT" (at 7.0 8.5 0) (effects (font (size 1.5 1.5) bold)))
        (text "24V 2.5A" (at 5.0 5.5 0) (effects (font (size 1.1 1.1))))
        (text "FONTE CHAVEADA" (at 0 -10.5 0) (effects (font (size 1.2 1.2) bold)))
      )
      (symbol "SMPS_24V_1_1"
        (pin power_in line (at -16.54 5.08 0) (length 2.54) (name "L" (effects (font (size 1.0 1.0)))) (number "1" (effects (font (size 1.0 1.0)))))
        (pin power_in line (at -16.54 0 0) (length 2.54) (name "N" (effects (font (size 1.0 1.0)))) (number "2" (effects (font (size 1.0 1.0)))))
        (pin power_in line (at -16.54 -5.08 0) (length 2.54) (name "PE" (effects (font (size 1.0 1.0)))) (number "3" (effects (font (size 1.0 1.0)))))
        (pin power_out line (at 16.54 5.08 180) (length 2.54) (name "+24V" (effects (font (size 1.0 1.0)))) (number "4" (effects (font (size 1.0 1.0)))))
        (pin power_out line (at 16.54 -5.08 180) (length 2.54) (name "0V" (effects (font (size 1.0 1.0)))) (number "5" (effects (font (size 1.0 1.0)))))
      )
    )
"""

    # 6. Safety Switches (S0 Emergência 1NC+1NO, S_TAMPA Microswitch, S_OFF, S_ON)
    sch += """    (symbol "Industrial:SW_Emergency_1NC_1NO"
      (in_bom yes) (on_board yes)
      (property "Reference" "S" (at 4.0 2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Value" "E-STOP_1NC_1NO" (at 4.0 -2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "SW_Emergency_1NC_1NO_0_1"
        (arc (start -2.54 2.54) (mid 0 4.0) (end 2.54 2.54) (stroke (width 0.254) (type default)) (fill (type outline)))
        (polyline (pts (xy 0 3.0) (xy 0 1.0)) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy -1.5 1.0) (xy 1.5 1.0)) (stroke (width 0.2) (type default)) (fill (type none)))
        (circle (center -2.0 0) (radius 0.5) (stroke (width 0.2) (type default)) (fill (type none)))
        (circle (center 2.0 0) (radius 0.5) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy -2.0 -0.5) (xy 1.5 -0.5)) (stroke (width 0.254) (type default)) (fill (type none)))
        (polyline (pts (xy 0 -0.5) (xy 4.5 -0.5)) (stroke (width 0.15) (type dash)) (fill (type none)))
        (circle (center 4.5 1.8) (radius 0.4) (stroke (width 0.2) (type default)) (fill (type none)))
        (circle (center 4.5 -1.8) (radius 0.4) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy 4.5 1.4) (xy 6.5 -0.5)) (stroke (width 0.254) (type default)) (fill (type none)))
      )
      (symbol "SW_Emergency_1NC_1NO_1_1"
        (pin passive line (at 0 5.08 270) (length 2.54) (name "11" (effects (font (size 0.7 0.7)) (hide yes))) (number "11NF" (effects (font (size 0.85 0.85)))))
        (pin passive line (at 0 -5.08 90) (length 2.54) (name "12" (effects (font (size 0.7 0.7)) (hide yes))) (number "12NF" (effects (font (size 0.85 0.85)))))
        (pin passive line (at 4.5 5.08 270) (length 2.54) (name "23" (effects (font (size 0.7 0.7)) (hide yes))) (number "23NA" (effects (font (size 0.85 0.85)))))
        (pin passive line (at 7.62 0 180) (length 2.54) (name "24" (effects (font (size 0.7 0.7)) (hide yes))) (number "24NA" (effects (font (size 0.85 0.85)))))
      )
    )
"""

    sch += """    (symbol "Industrial:SW_Limit_Safety_SPDT"
      (in_bom yes) (on_board yes)
      (property "Reference" "S" (at 4.0 2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Value" "Microswitch_Tampa" (at 4.0 -2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "SW_Limit_Safety_SPDT_0_1"
        (circle (center -2.5 2.5) (radius 0.8) (stroke (width 0.254) (type default)) (fill (type none)))
        (polyline (pts (xy -2.5 1.7) (xy -1.0 0.8)) (stroke (width 0.2) (type default)) (fill (type none)))
        (circle (center 0 1.8) (radius 0.4) (stroke (width 0.2) (type default)) (fill (type none)))
        (circle (center 0 -1.8) (radius 0.4) (stroke (width 0.2) (type default)) (fill (type none)))
        (circle (center 4.5 0) (radius 0.4) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy 0 1.4) (xy 0 -1.4)) (stroke (width 0.254) (type default)) (fill (type none)))
        (polyline (pts (xy 0 1.4) (xy 4.1 0.2)) (stroke (width 0.2) (type dash)) (fill (type none)))
      )
      (symbol "SW_Limit_Safety_SPDT_1_1"
        (pin passive line (at 0 5.08 270) (length 2.54) (name "COM" (effects (font (size 0.7 0.7)) (hide yes))) (number "COM" (effects (font (size 0.8 0.8)))))
        (pin passive line (at 0 -5.08 90) (length 2.54) (name "NC" (effects (font (size 0.7 0.7)) (hide yes))) (number "NC" (effects (font (size 0.8 0.8)))))
        (pin passive line (at 7.62 0 180) (length 2.54) (name "NO" (effects (font (size 0.7 0.7)) (hide yes))) (number "NO" (effects (font (size 0.8 0.8)))))
      )
    )
"""

    sch += """    (symbol "Industrial:SW_Push_NC"
      (in_bom yes) (on_board yes)
      (property "Reference" "S" (at 4.0 2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Value" "SW_OFF" (at 4.0 -2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "SW_Push_NC_0_1"
        (polyline (pts (xy -1.5 2.5) (xy 1.5 2.5)) (stroke (width 0.3) (type default)) (fill (type none)))
        (polyline (pts (xy 0 2.5) (xy 0 0.8)) (stroke (width 0.2) (type default)) (fill (type none)))
        (circle (center -2.0 0) (radius 0.5) (stroke (width 0.2) (type default)) (fill (type none)))
        (circle (center 2.0 0) (radius 0.5) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy -2.0 -0.5) (xy 1.5 -0.5)) (stroke (width 0.254) (type default)) (fill (type none)))
      )
      (symbol "SW_Push_NC_1_1"
        (pin passive line (at 0 5.08 270) (length 2.54) (name "1" (effects (font (size 0.7 0.7)) (hide yes))) (number "1NF" (effects (font (size 0.85 0.85)))))
        (pin passive line (at 0 -5.08 90) (length 2.54) (name "2" (effects (font (size 0.7 0.7)) (hide yes))) (number "2NF" (effects (font (size 0.85 0.85)))))
      )
    )
"""

    sch += """    (symbol "Industrial:SW_Push_NO"
      (in_bom yes) (on_board yes)
      (property "Reference" "S" (at 4.0 2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Value" "SW_ON" (at 4.0 -2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "SW_Push_NO_0_1"
        (polyline (pts (xy -1.5 2.5) (xy 1.5 2.5)) (stroke (width 0.3) (type default)) (fill (type none)))
        (polyline (pts (xy 0 2.5) (xy 0 1.0)) (stroke (width 0.2) (type default)) (fill (type none)))
        (circle (center -2.0 0) (radius 0.5) (stroke (width 0.2) (type default)) (fill (type none)))
        (circle (center 2.0 0) (radius 0.5) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy -2.0 0.5) (xy 1.5 2.0)) (stroke (width 0.254) (type default)) (fill (type none)))
      )
      (symbol "SW_Push_NO_1_1"
        (pin passive line (at 0 5.08 270) (length 2.54) (name "3" (effects (font (size 0.7 0.7)) (hide yes))) (number "3NA" (effects (font (size 0.85 0.85)))))
        (pin passive line (at 0 -5.08 90) (length 2.54) (name "4" (effects (font (size 0.7 0.7)) (hide yes))) (number "4NA" (effects (font (size 0.85 0.85)))))
      )
    )
"""

    # 7. Contactor Aux Contacts and Coil
    sch += """    (symbol "Industrial:Contactor_Aux_NO"
      (in_bom yes) (on_board yes)
      (property "Reference" "K" (at 4.0 2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Value" "K1_NA_13-14" (at 4.0 -2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "Contactor_Aux_NO_0_1"
        (circle (center -2.0 0) (radius 0.5) (stroke (width 0.2) (type default)) (fill (type none)))
        (circle (center 2.0 0) (radius 0.5) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy -2.0 0.5) (xy 1.5 2.0)) (stroke (width 0.254) (type default)) (fill (type none)))
      )
      (symbol "Contactor_Aux_NO_1_1"
        (pin passive line (at 0 5.08 270) (length 2.54) (name "13" (effects (font (size 0.7 0.7)) (hide yes))) (number "13NA" (effects (font (size 0.85 0.85)))))
        (pin passive line (at 0 -5.08 90) (length 2.54) (name "14" (effects (font (size 0.7 0.7)) (hide yes))) (number "14NA" (effects (font (size 0.85 0.85)))))
      )
    )
"""

    sch += """    (symbol "Industrial:Contactor_Aux_NC"
      (in_bom yes) (on_board yes)
      (property "Reference" "K" (at 4.0 2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Value" "K1_NF_21-22" (at 4.0 -2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "Contactor_Aux_NC_0_1"
        (circle (center -2.0 0) (radius 0.5) (stroke (width 0.2) (type default)) (fill (type none)))
        (circle (center 2.0 0) (radius 0.5) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy -2.0 -0.5) (xy 1.5 -0.5)) (stroke (width 0.254) (type default)) (fill (type none)))
      )
      (symbol "Contactor_Aux_NC_1_1"
        (pin passive line (at 0 5.08 270) (length 2.54) (name "21" (effects (font (size 0.8 0.8)) (hide yes))) (number "21NF" (effects (font (size 0.85 0.85)))))
        (pin passive line (at 0 -5.08 90) (length 2.54) (name "22" (effects (font (size 0.8 0.8)) (hide yes))) (number "22NF" (effects (font (size 0.85 0.85)))))
      )
    )
"""

    sch += """    (symbol "Industrial:Contactor_Coil_24V"
      (in_bom yes) (on_board yes)
      (property "Reference" "K" (at 5.0 2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Value" "Bobina_24VDC" (at 5.0 -2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "Contactor_Coil_24V_0_1"
        (rectangle (start -4.0 4.0) (end 4.0 -4.0) (stroke (width 0.254) (type default)) (fill (type background)))
        (polyline (pts (xy -4.0 -4.0) (xy 4.0 4.0)) (stroke (width 0.2) (type default)) (fill (type none)))
        (text "K1" (at -1.2 1.2 0) (effects (font (size 1.5 1.5) bold)))
        (rectangle (start 4.0 3.0) (end 14.0 -3.0) (stroke (width 0.2) (type dash)) (fill (type none)))
        (text "SUPRESSOR" (at 9.0 1.0 0) (effects (font (size 0.7 0.7))))
        (text "WEG DI-D" (at 9.0 -1.2 0) (effects (font (size 0.7 0.7) bold)))
      )
      (symbol "Contactor_Coil_24V_1_1"
        (pin passive line (at 0 6.54 270) (length 2.54) (name "A1" (effects (font (size 0.8 0.8)) (hide yes))) (number "A1" (effects (font (size 0.9 0.9)))))
        (pin passive line (at 0 -6.54 90) (length 2.54) (name "A2" (effects (font (size 0.8 0.8)) (hide yes))) (number "A2" (effects (font (size 0.9 0.9)))))
      )
    )
"""

    # 8. Pilot Lamp
    sch += """    (symbol "Industrial:Pilot_Lamp_24V"
      (in_bom yes) (on_board yes)
      (property "Reference" "H" (at 5.0 2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Value" "Lamp_24V" (at 5.0 -2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "Pilot_Lamp_24V_0_1"
        (circle (center 0 0) (radius 3.81) (stroke (width 0.254) (type default)) (fill (type background)))
        (polyline (pts (xy -2.69 -2.69) (xy 2.69 2.69)) (stroke (width 0.254) (type default)) (fill (type none)))
        (polyline (pts (xy -2.69 2.69) (xy 2.69 -2.69)) (stroke (width 0.254) (type default)) (fill (type none)))
      )
      (symbol "Pilot_Lamp_24V_1_1"
        (pin passive line (at 0 6.35 270) (length 2.54) (name "X1" (effects (font (size 0.8 0.8)) (hide yes))) (number "X1" (effects (font (size 0.9 0.9)))))
        (pin passive line (at 0 -6.35 90) (length 2.54) (name "X2" (effects (font (size 0.8 0.8)) (hide yes))) (number "X2" (effects (font (size 0.9 0.9)))))
      )
    )
"""

    # 9. Fuses
    sch += """    (symbol "Industrial:Fuse_Cartridge"
      (in_bom yes) (on_board yes)
      (property "Reference" "F" (at 0 2.54 0) (effects (font (size 1.27 1.27))))
      (property "Value" "Fuse_2A" (at 0 -2.54 0) (effects (font (size 1.27 1.27))))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "Fuse_Cartridge_0_1"
        (rectangle (start -3.81 1.27) (end 3.81 -1.27) (stroke (width 0.254) (type default)) (fill (type background)))
        (polyline (pts (xy -3.81 0) (xy 3.81 0)) (stroke (width 0.2) (type default)) (fill (type none)))
      )
      (symbol "Fuse_Cartridge_1_1"
        (pin passive line (at -6.35 0 0) (length 2.54) (name "1" (effects (font (size 0.8 0.8)))) (number "1" (effects (font (size 0.8 0.8)))))
        (pin passive line (at 6.35 0 180) (length 2.54) (name "2" (effects (font (size 0.8 0.8)))) (number "2" (effects (font (size 0.8 0.8)))))
      )
    )
"""

    # 10. Digital Tachometer Display Module (CF5135B / ML standard)
    sch += """    (symbol "Industrial:Display_Tacometro_RPM"
      (in_bom yes) (on_board yes)
      (property "Reference" "DISP" (at 0 16.0 0) (effects (font (size 1.27 1.27))))
      (property "Value" "Tacometro_CF5135B" (at 0 -16.0 0) (effects (font (size 1.27 1.27))))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "Display_Tacometro_RPM_0_1"
        (rectangle (start -22.0 14.0) (end 22.0 -14.0) (stroke (width 0.3) (type default)) (fill (type background)))
        (rectangle (start -18.0 10.0) (end 18.0 0) (stroke (width 0.254) (type default)) (fill (type none)))
        (text "1750" (at -4.0 3.0 0) (effects (font (size 5.0 5.0) bold)))
        (text "RPM" (at 12.0 3.0 0) (effects (font (size 2.0 2.0) bold)))
        (text "TACOMETRO DIGITAL LED (12V-24V DC)" (at 0 -10.5 0) (effects (font (size 1.2 1.2) bold)))
      )
      (symbol "Display_Tacometro_RPM_1_1"
        (pin power_in line (at -24.54 4.0 0) (length 2.54) (name "VCC (+)" (effects (font (size 1.0 1.0)))) (number "1" (effects (font (size 1.0 1.0)))))
        (pin power_in line (at -24.54 -4.0 0) (length 2.54) (name "GND (-)" (effects (font (size 1.0 1.0)))) (number "2" (effects (font (size 1.0 1.0)))))
        (pin power_out line (at 24.54 6.0 180) (length 2.54) (name "+V SENSOR" (effects (font (size 1.0 1.0)))) (number "4" (effects (font (size 1.0 1.0)))))
        (pin input line (at 24.54 0 180) (length 2.54) (name "SINAL (IN)" (effects (font (size 1.0 1.0)))) (number "3" (effects (font (size 1.0 1.0)))))
        (pin power_out line (at 24.54 -6.0 180) (length 2.54) (name "GND SENSOR" (effects (font (size 1.0 1.0)))) (number "5" (effects (font (size 1.0 1.0)))))
      )
    )
"""

    # 11. Hall Effect Sensor NJK-5002C M12
    sch += """    (symbol "Industrial:Sensor_Hall_NJK5002C"
      (in_bom yes) (on_board yes)
      (property "Reference" "SEN" (at 0 12.0 0) (effects (font (size 1.27 1.27))))
      (property "Value" "NJK-5002C" (at 0 -12.0 0) (effects (font (size 1.27 1.27))))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "Sensor_Hall_NJK5002C_0_1"
        (rectangle (start -16.0 9.0) (end 16.0 -9.0) (stroke (width 0.3) (type default)) (fill (type background)))
        (polyline (pts (xy 16.0 8.0) (xy 16.0 -8.0)) (stroke (width 0.6) (type default)) (fill (type none)))
        (circle (center 10.0 0) (radius 2.5) (stroke (width 0.254) (type default)) (fill (type outline)))
        (text "HALL" (at -2.0 2.5 0) (effects (font (size 2.0 2.0) bold)))
        (text "NJK-5002C (NPN NA)" (at -2.0 -2.5 0) (effects (font (size 1.1 1.1))))
        (text "LED" (at 10.0 -4.5 0) (effects (font (size 0.9 0.9))))
        (rectangle (start 21.0 3.5) (end 25.0 -3.5) (stroke (width 0.3) (type default)) (fill (type background)))
        (text "IMA" (at 23.0 5.5 0) (effects (font (size 0.9 0.9) bold)))
        (polyline (pts (xy 29.0 8.0) (xy 29.0 -8.0)) (stroke (width 0.5) (type default)) (fill (type none)))
        (text "EIXO" (at 32.0 0 90) (effects (font (size 0.8 0.8))))
      )
      (symbol "Sensor_Hall_NJK5002C_1_1"
        (pin power_in line (at -18.54 6.0 0) (length 2.54) (name "MARROM (+)" (effects (font (size 0.9 0.9)))) (number "1" (effects (font (size 0.9 0.9)))))
        (pin output line (at -18.54 0 0) (length 2.54) (name "PRETO (SIG)" (effects (font (size 0.9 0.9)))) (number "2" (effects (font (size 0.9 0.9)))))
        (pin power_in line (at -18.54 -6.0 0) (length 2.54) (name "AZUL (GND)" (effects (font (size 0.9 0.9)))) (number "3" (effects (font (size 0.9 0.9)))))
      )
    )
"""

    sch += "  )\n" # End of lib_symbols

    # Section Frames & Titles
    # Section 1: Força (20 to 95, 20 to 195)
    sch += draw_box(20, 20, 95, 195, 0.4)
    sch += text_comment("SECAO 1: CIRCUITO DE FORCA (220V AC)", 25, 28, 2.0)
    sch += text_comment("Rede 220V -> Q1 -> K1 -> Dimmer -> Motor M1", 25, 33, 1.0, False)

    # Section 2: SMPS (98 to 175, 20 to 195)
    sch += draw_box(98, 20, 175, 195, 0.4)
    sch += text_comment("SECAO 2: FONTE CHAVEADA (24V DC)", 102, 28, 2.0)
    sch += text_comment("Derivacao 220V -> F1/F2 (2A) -> SMPS 24V 2.5A (60W Trilho DIN)", 102, 33, 1.0, False)

    # Section 3: Comando & Seguranca (178 to 325, 20 to 195)
    sch += draw_box(178, 20, 325, 195, 0.4)
    sch += text_comment("SECAO 3: CIRCUITO DE COMANDO & INTERTRAVAMENTO (24V DC)", 182, 28, 2.0)
    sch += text_comment("NR-12: Botao Emergencia (S0) + Microswitch Tampa SPDT + Retencao K1 (13-14)", 182, 33, 1.2, False)

    # Section 4: Tacometro (20 to 302, 200 to 280) - Cleared from Title Block at bottom right!
    sch += draw_box(20, 200, 302, 280, 0.4)
    sch += text_comment("SECAO 4: TACOMETRO DIGITAL DE PAINEL & SENSOR HALL NJK-5002C", 25, 208, 1.8)
    sch += text_comment("Display Digital de RPM (12V-24V DC) + Sensor Magnetico M12 c/ Ima de Neodimio no Eixo", 25, 213, 1.2, False)

    # Section 5: Notas Tecnicas (328 to 410, 20 to 238) - Cleared from Title Block!
    sch += draw_box(328, 20, 410, 238, 0.4)
    sch += text_comment("NOTAS TECNICAS & BOM", 332, 28, 2.0)
    sch += text_box("""NORMAS APLICAVEIS:
- ABNT NBR 5410 (Instalacoes BT)
- NR-10 (Seguranca Eletrica)
- NR-12 (Seguranca em Maquinas)

ESPECIFICACOES COMPONENTES:
* Q1: Disjuntor Bipolar 2P Curva C 10A
* K1: Contator Steck SC / WEG CWB (24V CC)
  c/ Contato Auxiliar 13-14 (NA) p/ Selo
  (Sem blocos de contato adicionais)
* DIM1: Dimmer AC 220V 2000W / 4000W
  Controle TRIAC c/ Potenciometro de Painel
* M1: Motor de Tanquinho 220V
  Monofasico/Bifasico ~1/4 CV (180W)
  Capacitor Permanente C_MOT (12-15uF)
* T1: Fonte Chaveada Trilho DIN 24V 2.5A
* S0: Botao Emergencia Cogumelo c/ Trava (1NF + 1NA)
* S_TAMPA: Microswitch Seguranca SPDT (COM, NF, NA)
  NF: Intertravamento Serie Comando
  NA: Aciona Sinaleiro H3 Tampa Aberta
* S_OFF: Botao Desliga Vermelho (NF)
* S_ON: Botao Liga Verde (NA)
* H1: Sinaleiro LED Branco 24V (Painel ON / Standby)
* H2: Sinaleiro LED Verde 24V (Motor ON - Paralelo K1 A1/A2)
* H3: Sinaleiro LED Amarelo 24V (Alarme Tampa Aberta)
* H4: Sinaleiro LED Vermelho 24V (Emergencia Acionada S0)
* DISP: Tacometro Digital CF5135B 4 Digitos
* SEN: Sensor Hall NJK-5002C M12 NPN NA

ATERRAMENTO DE PROTECAO (PE):
Carcaca do motor, chassi do dimmer,
fonte T1 e painel sao interligados a
barra de equipotencializacao PE da bancada.

INTERTRAVAMENTO NR-12:
Se a tampa for aberta ou a emergencia
for acionada, K1 desliga imediatamente.
O selo 13-14 abre, impedindo religamento
automatico. Exige REARME MANUAL via S_ON.
Abertura da tampa acende LED Amarelo H3.
Acionamento da emergencia acende LED Vermelho H4.

EXPANSAO FUTURA PARA CLP:
1. DI: Status S0, S_TAMPA, K1, Pulso Tacho
2. DO: Partida/Parada Remota via reles""", 332, 35, 1.05, 3.4)

    # =========================================================================
    # PLACEMENT & WIRING
    # =========================================================================

    # 1. CIRCUITO DE FORCA (SECAO 1)
    # L1: X = 42 mm
    # L2: X = 52 mm
    # PE: X = 74 mm

    # Mains Supply Labels on Left side (clean routing, no crossing over terminals):
    sch += net_label("TERRA_PE", 28, 35, 180)
    sch += wire(28, 35, 74, 35)
    sch += wire(74, 35, 74, 42.19)

    sch += net_label("REDE_220V_L2/N", 28, 38, 180)
    sch += wire(28, 38, 52, 38)
    sch += wire(52, 38, 52, 42.19)

    sch += net_label("REDE_220V_L1", 28, 41, 180)
    sch += wire(28, 41, 42, 41)
    sch += wire(42, 41, 42, 42.19)

    # TB1, TB2, TB3: Bornes Sak de Entrada at Y = 46 mm
    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 42 46 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "TB1" (at 45.5 46 0) (effects (font (size 1.0 1.0)) (justify left)))
    (property "Value" "L1_IN" (at 45.5 44 0) (effects (font (size 0.8 0.8)) (justify left)))
    (property "Footprint" "" (at 42 46 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("TB1")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 52 46 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "TB2" (at 55.5 46 0) (effects (font (size 1.0 1.0)) (justify left)))
    (property "Value" "L2_IN" (at 55.5 44 0) (effects (font (size 0.8 0.8)) (justify left)))
    (property "Footprint" "" (at 52 46 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("TB2")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 74 46 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "TB3" (at 77.5 46 0) (effects (font (size 1.0 1.0)) (justify left)))
    (property "Value" "PE_IN" (at 77.5 44 0) (effects (font (size 0.8 0.8)) (justify left)))
    (property "Footprint" "" (at 74 46 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("TB3")}
  )\n"""

    # Wires from TB1/TB2 to Q1:
    sch += wire(42, 49.81, 42, 56.38) # TB1 Out to Q1 Pin 1
    sch += wire(52, 49.81, 52, 56.38) # TB2 Out to Q1 Pin 3

    # Q1 at (47, 64):
    sch += f"""  (symbol (lib_id "Industrial:CircuitBreaker_2P") (at 47 64 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "Q1" (at 35 64 90) (effects (font (size 1.27 1.27))))
    (property "Value" "Disjuntor_2P_C10A" (at 59 64 90) (effects (font (size 1.27 1.27))))
    (property "Footprint" "" (at 47 64 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("Q1")}
  )\n"""

    # Q1 Out to K1 with derivations to F1/F2:
    sch += wire(42, 71.62, 42, 88.38)
    sch += junction(42, 74)
    sch += wire(42, 74, 105, 74) # Derivation to F1

    sch += wire(52, 71.62, 52, 88.38)
    sch += junction(52, 86)
    sch += wire(52, 86, 105, 86) # Derivation to F2

    # K1 at (47, 96) - 2 POLES ONLY (1-2 and 3-4)!
    sch += f"""  (symbol (lib_id "Industrial:Contactor_Power_2P") (at 47 96 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "K1" (at 35 96 90) (effects (font (size 1.27 1.27))))
    (property "Value" "Contatos_Forca_K1" (at 59 96 90) (effects (font (size 1.27 1.27))))
    (property "Footprint" "" (at 47 96 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("K1")}
  )\n"""

    # K1 Out straight down to Dimmer DIM1:
    sch += wire(42, 103.62, 42, 111.46)
    sch += wire(52, 103.62, 52, 111.46)

    # DIM1 at (47, 124) - Dimmer AC 220V com Controle TRIAC:
    sch += f"""  (symbol (lib_id "Industrial:Dimmer_AC_220V") (at 47 124 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "DIM1" (at 33 124 90) (effects (font (size 1.27 1.27))))
    (property "Value" "Dimmer_220V" (at 29 124 90) (effects (font (size 1.1 1.1))))
    (property "Footprint" "" (at 47 124 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("DIM1")}
  )\n"""

    # DIM1 Out straight down to TB4, TB5 at Y = 148 mm:
    sch += wire(42, 136.54, 42, 144.19)
    sch += wire(52, 136.54, 52, 144.19)

    # TB4, TB5, TB6: Bornes Sak de Saida para o Motor at Y = 148 mm
    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 42 148 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "TB4" (at 45.5 148 0) (effects (font (size 1.0 1.0)) (justify left)))
    (property "Value" "L1_MOT" (at 45.5 146 0) (effects (font (size 0.8 0.8)) (justify left)))
    (property "Footprint" "" (at 42 148 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("TB4")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 52 148 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "TB5" (at 55.5 148 0) (effects (font (size 1.0 1.0)) (justify left)))
    (property "Value" "L2_MOT" (at 55.5 146 0) (effects (font (size 0.8 0.8)) (justify left)))
    (property "Footprint" "" (at 52 148 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("TB5")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 74 148 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "TB6" (at 77.5 148 0) (effects (font (size 1.0 1.0)) (justify left)))
    (property "Value" "PE_MOT" (at 77.5 146 0) (effects (font (size 0.8 0.8)) (justify left)))
    (property "Footprint" "" (at 74 148 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("TB6")}
  )\n"""

    # Continuous PE Line: From TB3 straight down to TB6:
    sch += wire(74, 49.81, 74, 144.19)
    sch += junction(74, 124)
    sch += wire(74, 124, 57, 124) # Tap to Dimmer chassis earth
    sch += text_comment("PE Carcaca Dimmer", 59, 122, 0.85, False)

    # Straight vertical wires down from TB4/TB5/TB6 to Motor M1 at Y = 174 mm:
    sch += wire(42, 151.81, 42, 162.96)
    sch += wire(52, 151.81, 52, 162.96)
    sch += wire(74, 151.81, 74, 174)
    sch += wire(74, 174, 69, 174) # Into Motor PE pin (47 + 22 = 69 mm)

    # Motor M1 at (47, 174):
    sch += f"""  (symbol (lib_id "Industrial:Motor_Tanquinho_220V") (at 47 174 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "M1" (at 47 159 0) (effects (font (size 1.27 1.27))))
    (property "Value" "Motor_Tanquinho_220V" (at 47 188 0) (effects (font (size 1.27 1.27))))
    (property "Footprint" "" (at 47 174 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("M1")}
  )\n"""

    # 2. SEÇÃO DA FONTE CHAVEADA (SECAO 2)
    sch += f"""  (symbol (lib_id "Industrial:Fuse_Cartridge") (at 115 74 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "F1" (at 115 70 0) (effects (font (size 1.27 1.27))))
    (property "Value" "2A_250V" (at 115 78 0) (effects (font (size 1.27 1.27))))
    (property "Footprint" "" (at 115 74 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("F1")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:Fuse_Cartridge") (at 115 86 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "F2" (at 115 82 0) (effects (font (size 1.27 1.27))))
    (property "Value" "2A_250V" (at 115 90 0) (effects (font (size 1.27 1.27))))
    (property "Footprint" "" (at 115 86 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("F2")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:SMPS_24V") (at 148 88 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "T1" (at 148 70 0) (effects (font (size 1.27 1.27))))
    (property "Value" "SMPS_24V_2.5A" (at 148 106 0) (effects (font (size 1.27 1.27))))
    (property "Footprint" "" (at 148 88 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("T1")}
  )\n"""

    # Wires to F1/F2 and SMPS:
    sch += wire(105, 74, 108.65, 74)
    sch += wire(121.35, 74, 126, 74)
    sch += wire(126, 74, 126, 82.92)
    sch += wire(126, 82.92, 131.46, 82.92)

    sch += wire(105, 86, 108.65, 86)
    sch += wire(121.35, 86, 126, 86)
    sch += wire(126, 86, 126, 88)
    sch += wire(126, 88, 131.46, 88)

    # Local Earth Ground Symbol for T1 Pin 3 (PE):
    sch += wire(131.46, 93.08, 131.46, 96.5)
    sch += """  (polyline (pts (xy 129.5 96.5) (xy 133.5 96.5)) (stroke (width 0.4) (type default)) (fill (type none)))\n"""
    sch += """  (polyline (pts (xy 130.5 97.5) (xy 132.5 97.5)) (stroke (width 0.4) (type default)) (fill (type none)))\n"""
    sch += """  (polyline (pts (xy 131.0 98.5) (xy 132.0 98.5)) (stroke (width 0.4) (type default)) (fill (type none)))\n"""
    sch += text_comment("PE", 134, 98, 0.9, False)

    # SMPS Outputs & Continuous Barramentos:
    sch += wire(164.54, 82.92, 172, 82.92)
    sch += wire(172, 82.92, 172, 45)
    sch += junction(172, 45)
    # +24V Rail: explicit contiguous segments
    sch += wire(172, 45, 212, 45)
    sch += wire(212, 45, 216.5, 45)
    sch += wire(216.5, 45, 314, 45)
    sch += text_comment("+24V DC BARRAMENTO GERAL", 182, 42, 1.3)

    sch += wire(164.54, 93.08, 172, 93.08)
    sch += wire(172, 93.08, 172, 185)
    sch += junction(172, 185)
    # 0V / COM Rail: explicit contiguous segments between all branches
    sch += wire(172, 185, 212, 185)
    sch += wire(212, 185, 245, 185)
    sch += wire(245, 185, 268, 185)
    sch += wire(268, 185, 291, 185)
    sch += wire(291, 185, 314, 185)
    sch += text_comment("0V / COM BARRAMENTO GERAL", 182, 182, 1.3)

    # 3. CIRCUITO DE COMANDO & INTERTRAVAMENTO NR-12 (SECAO 3)
    # Branch 1 at X = 212 mm
    sch += f"""  (symbol (lib_id "Industrial:SW_Emergency_1NC_1NO") (at 212 58 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "S0" (at 204 56 0) (effects (font (size 1.27 1.27)) (justify right)))
    (property "Value" "EMERGENCIA_1NF_1NA" (at 204 60 0) (effects (font (size 1.1 1.1)) (justify right)))
    (property "Footprint" "" (at 212 58 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("S0")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:SW_Limit_Safety_SPDT") (at 212 78 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "S_TAMPA" (at 204 76 0) (effects (font (size 1.27 1.27)) (justify right)))
    (property "Value" "CHAVE_TAMPA_SPDT" (at 204 80 0) (effects (font (size 1.1 1.1)) (justify right)))
    (property "Footprint" "" (at 212 78 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("S_TAMPA")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:SW_Push_NC") (at 212 98 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "S_OFF" (at 217 96 0) (effects (font (size 1.27 1.27)) (justify left)))
    (property "Value" "DESLIGA_NF" (at 217 100 0) (effects (font (size 1.27 1.27)) (justify left)))
    (property "Footprint" "" (at 212 98 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("S_OFF")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:SW_Push_NO") (at 202 122 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "S_ON" (at 193 122 0) (effects (font (size 1.27 1.27)) (justify right)))
    (property "Value" "LIGA_NA" (at 193 125 0) (effects (font (size 1.1 1.1)) (justify right)))
    (property "Footprint" "" (at 202 122 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("S_ON")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:Contactor_Aux_NO") (at 222 122 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "K1_SELO" (at 228 120 0) (effects (font (size 1.27 1.27)) (justify left)))
    (property "Value" "13-14_SELO" (at 228 124 0) (effects (font (size 1.1 1.1)) (justify left)))
    (property "Footprint" "" (at 222 122 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("K1_SELO")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:Contactor_Coil_24V") (at 212 150 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "K1" (at 228 148 0) (effects (font (size 1.27 1.27)) (justify left)))
    (property "Value" "Bobina_24VDC" (at 228 152 0) (effects (font (size 1.27 1.27)) (justify left)))
    (property "Footprint" "" (at 212 150 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("K1")}
  )\n"""

    # Wires Comando Ramo 1:
    sch += junction(212, 45)
    sch += wire(212, 45, 212, 52.92) # +24V to S0 Pin 11

    sch += junction(216.5, 45)
    sch += wire(216.5, 45, 216.5, 52.92) # +24V to S0 Pin 23 (NA in)

    sch += wire(212, 63.08, 212, 72.92) # S0 Pin 12 to S_TAMPA COM
    sch += wire(212, 83.08, 212, 92.92) # S_TAMPA NC to S_OFF Pin 1

    sch += wire(212, 103.08, 212, 112)
    sch += junction(212, 112)
    sch += wire(212, 112, 202, 112)
    sch += wire(202, 112, 202, 116.92)
    sch += wire(212, 112, 222, 112)
    sch += wire(222, 112, 222, 116.92)

    sch += wire(202, 127.08, 202, 136)
    sch += wire(202, 136, 212, 136)
    sch += wire(222, 127.08, 222, 136)
    sch += wire(222, 136, 212, 136)
    sch += junction(212, 136)

    # Clean straight wire into Coil Pin A1 (at 212, 143.46):
    sch += wire(212, 136, 212, 143.46)

    # Clean straight wire out of Coil Pin A2 (at 212, 156.54) to 0V Rail at Y = 185:
    sch += wire(212, 156.54, 212, 185)
    sch += junction(212, 185)

    # Ramo 2: H2 (Motor Operando - VERDE) em PARALELO com a Bobina K1 (A1/A2) at (245, 150)
    sch += f"""  (symbol (lib_id "Industrial:Pilot_Lamp_24V") (at 245 150 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "H2" (at 251 148 0) (effects (font (size 1.27 1.27)) (justify left)))
    (property "Value" "VERDE_24V" (at 251 152 0) (effects (font (size 1.1 1.1)) (justify left)))
    (property "Footprint" "" (at 245 150 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("H2")}
  )\n"""
    # Parallel connection from switched +24V node (212, 136) to H2 pin X1:
    sch += wire(212, 136, 245, 136)
    sch += wire(245, 136, 245, 143.65)
    # H2 pin X2 to 0V rail:
    sch += wire(245, 156.35, 245, 185)
    sch += junction(245, 185)
    sch += text_comment("MOTOR OPERANDO (H2)", 232, 166, 0.95, False)
    sch += text_comment("PARALELO BOBINA K1", 232, 170, 0.8, False)

    # Ramo 3: H3 (Alarme: Tampa Aberta - AMARELO) via Contato NA do Microswitch S_TAMPA at (268, 115)
    sch += f"""  (symbol (lib_id "Industrial:Pilot_Lamp_24V") (at 268 115 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "H3" (at 273 113 0) (effects (font (size 1.27 1.27)) (justify left)))
    (property "Value" "AMARELO_24V" (at 273 117 0) (effects (font (size 1.1 1.1)) (justify left)))
    (property "Footprint" "" (at 268 115 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("H3")}
  )\n"""
    # Wire from S_TAMPA pin NO (219.62, 78) across to (268, 78) and down to H3 pin X1:
    sch += wire(219.62, 78, 268, 78)
    sch += wire(268, 78, 268, 108.65)
    # H3 pin X2 to 0V rail:
    sch += wire(268, 121.35, 268, 185)
    sch += junction(268, 185)
    sch += text_comment("ALARME: TAMPA ABERTA (H3)", 255, 133, 0.95, False)
    sch += text_comment("VIA S_TAMPA NA (AMARELO)", 255, 137, 0.8, False)

    # Ramo 4: H4 (Alarme: Emergencia Acionada - VERMELHO) via Contato NA (Pin 24) de S0 at (291, 115)
    sch += f"""  (symbol (lib_id "Industrial:Pilot_Lamp_24V") (at 291 115 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "H4" (at 296 113 0) (effects (font (size 1.27 1.27)) (justify left)))
    (property "Value" "VERMELHO_24V" (at 296 117 0) (effects (font (size 1.1 1.1)) (justify left)))
    (property "Footprint" "" (at 291 115 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("H4")}
  )\n"""
    # Wire from S0 pin 24 (219.62, 58) across to (291, 58) and down to H4 pin X1:
    sch += wire(219.62, 58, 291, 58)
    sch += wire(291, 58, 291, 108.65)
    # H4 pin X2 to 0V rail:
    sch += wire(291, 121.35, 291, 185)
    sch += junction(291, 185)
    sch += text_comment("EMERGENCIA ACIONADA (H4)", 278, 133, 0.95, False)
    sch += text_comment("VIA S0 BLOCO 2 NA (VERM)", 278, 137, 0.8, False)

    # Ramo 5: H1 (Painel 24V Energizado - BRANCO) direto entre +24V e 0V at (314, 115)
    sch += f"""  (symbol (lib_id "Industrial:Pilot_Lamp_24V") (at 314 115 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "H1" (at 319 113 0) (effects (font (size 1.27 1.27)) (justify left)))
    (property "Value" "BRANCO_24V" (at 319 117 0) (effects (font (size 1.1 1.1)) (justify left)))
    (property "Footprint" "" (at 314 115 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("H1")}
  )\n"""
    sch += junction(314, 45)
    sch += wire(314, 45, 314, 108.65)
    sch += wire(314, 121.35, 314, 185)
    sch += junction(314, 185)
    sch += text_comment("PAINEL 24V ON (H1)", 301, 133, 0.95, False)
    sch += text_comment("DIRETO DA FONTE 24V", 301, 137, 0.8, False)

    # 4. TACOMETRO DIGITAL DE PAINEL & SENSOR HALL NJK-5002C (SECAO 4)
    # Display at X = 105, Y = 245
    sch += f"""  (symbol (lib_id "Industrial:Display_Tacometro_RPM") (at 105 245 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "DISP1" (at 105 228 0) (effects (font (size 1.27 1.27))))
    (property "Value" "Tacometro_Digital_CF5135B" (at 105 262 0) (effects (font (size 1.27 1.27))))
    (property "Footprint" "" (at 105 245 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("DISP1")}
  )\n"""

    # Display Power Input on left side:
    # Pin 1 (VCC +24V): at (105 - 24.54, 245 - 4.0) = (80.46, 241)
    # Pin 2 (GND 0V): at (105 - 24.54, 245 + 4.0) = (80.46, 249)
    sch += wire(80.46, 241, 55, 241)
    sch += net_label("+24V", 55, 241, 180)
    sch += text_comment("Alimentacao Display (12V-24V DC)", 56, 237, 1.0, False)
    sch += text_comment("(Derivado do Barramento Geral 24V/0V)", 56, 233, 0.85, False)

    sch += wire(80.46, 249, 55, 249)
    sch += net_label("0V", 55, 249, 180)

    # Sensor Hall NJK-5002C at X = 230, Y = 245
    sch += f"""  (symbol (lib_id "Industrial:Sensor_Hall_NJK5002C") (at 230 245 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "SEN_HALL" (at 230 233 0) (effects (font (size 1.27 1.27))))
    (property "Value" "NJK-5002C_M12" (at 230 257 0) (effects (font (size 1.27 1.27))))
    (property "Footprint" "" (at 230 245 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("SEN_HALL")}
  )\n"""

    # Display Sensor Terminals (Right side):
    # Pin 4 (+V SENSOR): at (105 + 24.54, 245 - 6.0) = (129.54, 239)
    # Pin 3 (SINAL IN): at (105 + 24.54, 245 - 0.0) = (129.54, 245)
    # Pin 5 (GND SENSOR): at (105 + 24.54, 245 + 6.0) = (129.54, 251)

    # Sensor Terminals (Left side):
    # Pin 1: Marrom (+Vcc) at (230 - 18.54, 245 - 6.0) = (211.46, 239)
    # Pin 2: Preto (Sinal NPN) at (230 - 18.54, 245 - 0.0) = (211.46, 245)
    # Pin 3: Azul (GND) at (230 - 18.54, 245 + 6.0) = (211.46, 251)

    # Completely straight, clean parallel wires:
    # 1. Marrom (+24V):
    sch += wire(129.54, 239, 211.46, 239)
    sch += text_comment("Fio Marrom (+24V)", 150, 237, 1.0, False)

    # 2. Preto (Sinal):
    sch += wire(129.54, 245, 211.46, 245)
    sch += text_comment("Fio Preto (Sinal NPN Pulso)", 150, 243, 1.0, False)

    # 3. Azul (GND):
    sch += wire(129.54, 251, 211.46, 251)
    sch += text_comment("Fio Azul (GND / 0V)", 150, 249, 1.0, False)

    # Visual note for Neodymium Magnet:
    sch += text_comment("IMA DE NEODIMIO FIXADO NO EIXO / POLIA DO MOTOR", 195, 268, 1.1, True)
    sch += text_comment("Distancia de Deteccao: 1mm a 8mm | 1 Pulso por Rotacao (PPR = 1)", 195, 272, 1.0, False)

    sch += """  (sheet_instances
    (path "/" (page "1"))
  )
)
"""
    with open(SCH_PATH, "w", encoding="utf-8") as f:
        f.write(sch)

    print(f"Schematic v2.0 generated successfully at {SCH_PATH}")

    # Export PDF via kicad-cli
    if os.path.exists(KICAD_CLI):
        cmd_pdf = [KICAD_CLI, "sch", "export", "pdf", "-o", PDF_PATH, SCH_PATH]
        res = subprocess.run(cmd_pdf, capture_output=True, text=True)
        print("Export PDF status:", res.returncode)
        if res.returncode == 0:
            print("PDF generated successfully at", PDF_PATH)
            try:
                import fitz
                doc = fitz.open(PDF_PATH)
                page = doc.load_page(0)
                pix = page.get_pixmap(dpi=200)
                pix.save(PNG_PATH)
                print("PNG rendered successfully at", PNG_PATH)
            except Exception as e:
                print("Error rendering PNG:", e)
        else:
            print("PDF Error:", res.stderr)

if __name__ == "__main__":
    build_schematic()
