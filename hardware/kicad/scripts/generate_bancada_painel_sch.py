#!/usr/bin/env python3
"""
Amemiya - Teste de Geracao de Esquematico v2.4 (IEC 81346-2, Fiacao Opcao 1, NC/NO limpo)
"""
import os
import sys
import uuid
import subprocess

PROJECT_DIR = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_bancada_painel"
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

def wire_tag_h(tag_str, x, y, size=0.72):
    """Tag para fio horizontal: posicionado ligeiramente acima da linha"""
    return f"""  (text "{tag_str}" (at {x} {y} 0)
    (effects (font (size {size} {size}) bold) (justify bottom))
    (uuid "{u()}")
  )\n"""

def wire_tag_v(tag_str, x, y, size=0.72):
    """Tag para fio vertical: posicionado a direita da linha com alinhamento a esquerda"""
    return f"""  (text "{tag_str}" (at {x} {y} 0)
    (effects (font (size {size} {size}) bold) (justify left))
    (uuid "{u()}")
  )\n"""

def text_box(text_str, x, y, size=1.05, line_height=3.6):
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
    (date "2026-10-26")
    (rev "v2.4")
    (company "Lean Tech Metrology & Industrial IoT Solutions")
    (comment 1 "Motor 220V Tanquinho c/ Dimmer TRIAC TC1 e Cap. Permanente")
    (comment 2 "Fonte 24V PS1 + Tacometro Digital PA1 / Sensor Hall BG1")
    (comment 3 "Tags IEC 81346-2 + Anilhas [W01-W42] (Bitola/Cor) + Bornes XT0/XT1/XT2")
  )
  (lib_symbols
"""
    
    # 1. Terminal Block Single DIN Rail Through (Borne Sak Passagem)
    sch += """    (symbol "Industrial:Terminal_Passagem"
      (in_bom yes) (on_board yes)
      (property "Reference" "XT" (at 3.5 0 0) (effects (font (size 1.0 1.0)) (justify left)))
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

    # 2. CircuitBreaker_2P (QM1 - Spacing 10mm: -5.0 and +5.0)
    sch += """    (symbol "Industrial:CircuitBreaker_2P"
      (in_bom yes) (on_board yes)
      (property "Reference" "QM" (at -8.0 0 90) (effects (font (size 1.27 1.27))))
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

    # 3. Contactor_Power_2P (KM1 - Power Poles: -5.0 and +5.0)
    sch += """    (symbol "Industrial:Contactor_Power_2P"
      (in_bom yes) (on_board yes)
      (property "Reference" "KM" (at -8.0 0 90) (effects (font (size 1.27 1.27))))
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

    # 3.1 Dimmer AC 220V (TC1 - TRIAC Speed Controller)
    sch += """    (symbol "Industrial:Dimmer_AC_220V"
      (in_bom yes) (on_board yes)
      (property "Reference" "TC" (at -14.0 0 90) (effects (font (size 1.27 1.27))))
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

    # 4. Motor_Tanquinho_220V (M1 - 220V c/ Capacitor C_MOT)
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

    # 5. SMPS_24V (PS1 - Fonte Chaveada DIN 24V 2.5A)
    sch += """    (symbol "Industrial:SMPS_24V"
      (in_bom yes) (on_board yes)
      (property "Reference" "PS" (at 0 16.0 0) (effects (font (size 1.27 1.27))))
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

    # 6. Safety Switches (SB0, SQ1, SB1, SB2) com geometria limpa e pinos alongados sem colisao
    # 6.1 SB0 - Botao Emergencia Cogumelo 1NC + 1NO (Atuador à esquerda, pinagem limpa)
    sch += """    (symbol "Industrial:SW_Emergency_1NC_1NO"
      (in_bom yes) (on_board yes)
      (property "Reference" "SB" (at 9.0 2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Value" "E-STOP_1NC_1NO" (at 9.0 -2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "SW_Emergency_1NC_1NO_0_1"
        (arc (start -5.5 1.8) (mid -3.5 3.2) (end -1.5 1.8) (stroke (width 0.254) (type default)) (fill (type outline)))
        (polyline (pts (xy -3.5 1.8) (xy -3.5 0)) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy -3.5 0) (xy 5.0 0)) (stroke (width 0.15) (type dash)) (fill (type none)))
        (circle (center 0 1.5) (radius 0.4) (stroke (width 0.2) (type default)) (fill (type none)))
        (circle (center 0 -1.5) (radius 0.4) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy 0 2.08) (xy 0 1.5)) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy 0 -2.08) (xy 0 -1.5)) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy -1.2 0.3) (xy 0.8 0.3)) (stroke (width 0.3) (type default)) (fill (type none)))
        (circle (center 4.5 1.5) (radius 0.4) (stroke (width 0.2) (type default)) (fill (type none)))
        (circle (center 4.5 -1.5) (radius 0.4) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy 4.5 2.08) (xy 4.5 1.5)) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy 4.5 -1.5) (xy 4.5 -0.5) (xy 5.08 0)) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy 4.5 1.1) (xy 6.3 -0.2)) (stroke (width 0.3) (type default)) (fill (type none)))
      )
      (symbol "SW_Emergency_1NC_1NO_1_1"
        (pin passive line (at 0 5.08 270) (length 3.0) (name "11" (effects (font (size 0.7 0.7)) (hide yes))) (number "11NC" (effects (font (size 0.85 0.85)))))
        (pin passive line (at 0 -5.08 90) (length 3.0) (name "12" (effects (font (size 0.7 0.7)) (hide yes))) (number "12NC" (effects (font (size 0.85 0.85)))))
        (pin passive line (at 4.5 5.08 270) (length 3.0) (name "23" (effects (font (size 0.7 0.7)) (hide yes))) (number "23NO" (effects (font (size 0.85 0.85)))))
        (pin passive line (at 7.62 0 180) (length 2.54) (name "24" (effects (font (size 0.7 0.7)) (hide yes))) (number "24NO" (effects (font (size 0.85 0.85)))))
      )
    )
"""

    # 6.2 SQ1 - Chave Seguranca da Tampa (Microswitch SPDT c/ Rolete)
    sch += """    (symbol "Industrial:SW_Limit_Safety_SPDT"
      (in_bom yes) (on_board yes)
      (property "Reference" "SQ" (at 6.0 2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Value" "Microswitch_Tampa" (at 6.0 -2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "SW_Limit_Safety_SPDT_0_1"
        (circle (center -3.0 2.2) (radius 0.7) (stroke (width 0.254) (type default)) (fill (type none)))
        (polyline (pts (xy -3.0 1.5) (xy -1.2 0.8)) (stroke (width 0.2) (type default)) (fill (type none)))
        (circle (center 0 1.5) (radius 0.4) (stroke (width 0.2) (type default)) (fill (type none)))
        (circle (center 0 -1.5) (radius 0.4) (stroke (width 0.2) (type default)) (fill (type none)))
        (circle (center 4.5 0) (radius 0.4) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy 0 2.54) (xy 0 1.5)) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy 0 -2.54) (xy 0 -1.5)) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy 4.5 0) (xy 5.08 0)) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy 0 1.1) (xy 0 -1.1)) (stroke (width 0.254) (type default)) (fill (type none)))
        (polyline (pts (xy 0 1.1) (xy 4.1 0.2)) (stroke (width 0.18) (type dash)) (fill (type none)))
      )
      (symbol "SW_Limit_Safety_SPDT_1_1"
        (pin passive line (at 0 5.08 270) (length 2.54) (name "COM" (effects (font (size 0.7 0.7)) (hide yes))) (number "COM" (effects (font (size 0.85 0.85)))))
        (pin passive line (at 0 -5.08 90) (length 2.54) (name "NC" (effects (font (size 0.7 0.7)) (hide yes))) (number "NC" (effects (font (size 0.85 0.85)))))
        (pin passive line (at 7.62 0 180) (length 2.54) (name "NO" (effects (font (size 0.7 0.7)) (hide yes))) (number "NO" (effects (font (size 0.85 0.85)))))
      )
    )
"""

    # 6.3 SB1 - Botao Pulsador Desliga (NF)
    sch += """    (symbol "Industrial:SW_Push_NC"
      (in_bom yes) (on_board yes)
      (property "Reference" "SB" (at 4.5 2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Value" "SW_OFF" (at 4.5 -2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "SW_Push_NC_0_1"
        (polyline (pts (xy -4.5 1.5) (xy -2.5 1.5)) (stroke (width 0.3) (type default)) (fill (type none)))
        (polyline (pts (xy -3.5 1.5) (xy -3.5 0)) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy -3.5 0) (xy 0 0)) (stroke (width 0.15) (type dash)) (fill (type none)))
        (circle (center 0 1.5) (radius 0.4) (stroke (width 0.2) (type default)) (fill (type none)))
        (circle (center 0 -1.5) (radius 0.4) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy 0 2.08) (xy 0 1.5)) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy 0 -2.08) (xy 0 -1.5)) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy -1.2 0.3) (xy 0.8 0.3)) (stroke (width 0.3) (type default)) (fill (type none)))
      )
      (symbol "SW_Push_NC_1_1"
        (pin passive line (at 0 5.08 270) (length 3.0) (name "1" (effects (font (size 0.7 0.7)) (hide yes))) (number "1NC" (effects (font (size 0.85 0.85)))))
        (pin passive line (at 0 -5.08 90) (length 3.0) (name "2" (effects (font (size 0.7 0.7)) (hide yes))) (number "2NC" (effects (font (size 0.85 0.85)))))
      )
    )
"""

    # 6.4 SB2 - Botao Pulsador Liga (NA)
    sch += """    (symbol "Industrial:SW_Push_NO"
      (in_bom yes) (on_board yes)
      (property "Reference" "SB" (at 4.5 2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Value" "SW_ON" (at 4.5 -2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "SW_Push_NO_0_1"
        (polyline (pts (xy -4.5 1.5) (xy -2.5 1.5)) (stroke (width 0.3) (type default)) (fill (type none)))
        (polyline (pts (xy -3.5 1.5) (xy -3.5 0)) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy -3.5 0) (xy 0 0)) (stroke (width 0.15) (type dash)) (fill (type none)))
        (circle (center 0 1.5) (radius 0.4) (stroke (width 0.2) (type default)) (fill (type none)))
        (circle (center 0 -1.5) (radius 0.4) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy 0 2.08) (xy 0 1.5)) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy 0 -2.08) (xy 0 -1.5)) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy 0 1.1) (xy 1.8 -0.2)) (stroke (width 0.3) (type default)) (fill (type none)))
      )
      (symbol "SW_Push_NO_1_1"
        (pin passive line (at 0 5.08 270) (length 3.0) (name "3" (effects (font (size 0.7 0.7)) (hide yes))) (number "3NO" (effects (font (size 0.85 0.85)))))
        (pin passive line (at 0 -5.08 90) (length 3.0) (name "4" (effects (font (size 0.7 0.7)) (hide yes))) (number "4NO" (effects (font (size 0.85 0.85)))))
      )
    )
"""

    # 7. Contactor Aux Contacts (KM1 Selo 13-14) e Coil
    sch += """    (symbol "Industrial:Contactor_Aux_NO"
      (in_bom yes) (on_board yes)
      (property "Reference" "KM" (at 4.5 2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Value" "KM1_NA_13-14" (at 4.5 -2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "Contactor_Aux_NO_0_1"
        (circle (center 0 1.5) (radius 0.4) (stroke (width 0.2) (type default)) (fill (type none)))
        (circle (center 0 -1.5) (radius 0.4) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy 0 2.08) (xy 0 1.5)) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy 0 -2.08) (xy 0 -1.5)) (stroke (width 0.2) (type default)) (fill (type none)))
        (polyline (pts (xy 0 1.1) (xy 1.8 -0.2)) (stroke (width 0.3) (type default)) (fill (type none)))
      )
      (symbol "Contactor_Aux_NO_1_1"
        (pin passive line (at 0 5.08 270) (length 3.0) (name "13" (effects (font (size 0.7 0.7)) (hide yes))) (number "13NO" (effects (font (size 0.85 0.85)))))
        (pin passive line (at 0 -5.08 90) (length 3.0) (name "14" (effects (font (size 0.7 0.7)) (hide yes))) (number "14NO" (effects (font (size 0.85 0.85)))))
      )
    )
"""

    sch += """    (symbol "Industrial:Contactor_Coil_24V"
      (in_bom yes) (on_board yes)
      (property "Reference" "KM" (at 5.0 2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Value" "Bobina_24VDC" (at 5.0 -2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
      (property "Footprint" "" (at 0 0 0) (effects (font (size 1.27 1.27)) (hide yes)))
      (symbol "Contactor_Coil_24V_0_1"
        (rectangle (start -4.0 4.0) (end 4.0 -4.0) (stroke (width 0.254) (type default)) (fill (type background)))
        (polyline (pts (xy -4.0 -4.0) (xy 4.0 4.0)) (stroke (width 0.2) (type default)) (fill (type none)))
        (text "KM1" (at -1.2 1.2 0) (effects (font (size 1.3 1.3) bold)))
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

    # 8. Pilot Lamp (HL1, HL2, HL3, HL4)
    sch += """    (symbol "Industrial:Pilot_Lamp_24V"
      (in_bom yes) (on_board yes)
      (property "Reference" "HL" (at 5.0 2.54 0) (effects (font (size 1.27 1.27)) (justify left)))
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

    # 9. Fuses (FU1, FU2)
    sch += """    (symbol "Industrial:Fuse_Cartridge"
      (in_bom yes) (on_board yes)
      (property "Reference" "FU" (at 0 2.54 0) (effects (font (size 1.27 1.27))))
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

    # 10. Digital Tachometer Display Module (PA1 - CF5135B)
    sch += """    (symbol "Industrial:Display_Tacometro_RPM"
      (in_bom yes) (on_board yes)
      (property "Reference" "PA" (at 0 16.0 0) (effects (font (size 1.27 1.27))))
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

    # 11. Hall Effect Sensor NJK-5002C M12 (BG1)
    sch += """    (symbol "Industrial:Sensor_Hall_NJK5002C"
      (in_bom yes) (on_board yes)
      (property "Reference" "BG" (at 0 12.0 0) (effects (font (size 1.27 1.27))))
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

    # =========================================================================
    # SECTION FRAMES & HEADINGS (ESTRITAMENTE EM PORTUGUES)
    # =========================================================================
    # Secao 1: Forca (20 a 95, 20 a 195)
    sch += draw_box(20, 20, 95, 195, 0.4)
    sch += text_comment("SECAO 1: CIRCUITO DE FORCA (220V AC)", 25, 28, 2.0)
    sch += text_comment("Rede 220V -> QM1 -> KM1 -> Dimmer TC1 -> Motor M1", 25, 33, 1.0, False)

    # Secao 2: SMPS (98 a 175, 20 a 195)
    sch += draw_box(98, 20, 175, 195, 0.4)
    sch += text_comment("SECAO 2: FONTE CHAVEADA (24V DC)", 102, 28, 2.0)
    sch += text_comment("Derivacao 220V -> FU1/FU2 (2A) -> PS1 24V 2.5A (60W Trilho DIN)", 102, 33, 1.0, False)

    # Secao 3: Comando & Seguranca (178 a 325, 20 a 195)
    sch += draw_box(178, 20, 325, 195, 0.4)
    sch += text_comment("SECAO 3: CIRCUITO DE COMANDO & INTERTRAVAMENTO (24V DC)", 182, 28, 2.0)
    sch += text_comment("NR-12: Botao Emergencia (SB0) + Chave da Tampa (SQ1) + Retencao KM1 (13-14)", 182, 33, 1.15, False)

    # Secao 4: Tacometro (20 a 302, 200 a 280)
    sch += draw_box(20, 200, 302, 280, 0.4)
    sch += text_comment("SECAO 4: TACOMETRO DIGITAL DE PAINEL & SENSOR HALL (BG1 / PA1)", 25, 208, 1.8)
    sch += text_comment("Indicador Digital de RPM (12V-24V DC) + Sensor Magnetico M12 c/ Ima de Neodimio no Eixo", 25, 213, 1.15, False)

    # Secao 5: Notas Tecnicas, Bornes & BOM (328 a 410, 20 a 240)
    sch += draw_box(328, 20, 410, 240, 0.4)
    sch += text_comment("NOTAS TECNICAS, BORNES & BOM", 332, 27, 1.8)
    sch += text_box("""NORMAS APLICAVEIS:
- ABNT NBR 5410 (Instalacoes BT)
- NR-10 (Seguranca Eletrica)
- NR-12 (Seguranca em Maquinas)
- IEC 60204-1 (Fiacao Industrial)
- IEC 81346-2 (Padronizacao de Tags)

REGUAS DE BORNES 24V / 0V:
* REGUA XT1 (+24V DC - BORNES SAK):
  XT1:1 - Entrada +24V da Fonte PS1
  XT1:2 - Saida p/ Emergencia SB0:11 (NF)
  XT1:3 - Saida p/ Alarme Emerg. SB0:23 (NA)
  XT1:4 - Saida p/ Sinaleiro Branco HL1:X1
  (Interligados por Ponte Conectora FBS)

* REGUA XT2 (0V DC - BORNES SAK):
  XT2:1 - Entrada 0V da Fonte PS1
  XT2:2 - Retorno Bobina Contator KM1:A2
  XT2:3 - Retorno Sinaleiro Verde HL2:X2
  XT2:4 - Retorno Sinaleiro Amarelo HL3:X2
  XT2:5 - Retorno Sinaleiro Vermelho HL4:X2
  XT2:6 - Retorno Sinaleiro Branco HL1:X2
  (Interligados por Ponte Conectora FBS)

ANILHAS & CONDUTORES (IEC 60204-1):
* Fios [W01] a [W42] c/ Bitola e Cor (EN):
  BK (Preto), BU (Azul Claro), GNYE (Verde/Am)
  RD (Vermelho), DBU (Azul Escuro), YE (Amarelo)
  BN (Marrom)

COMPONENTES DE PAINEL (IEC 81346-2):
* QM1: Disjuntor Bipolar 2P C10A (DIN)
* KM1: Contator Tripolar Steck 24V DC c/ NA
* TC1: Dimmer AC TRIAC 2000W / 4000W
* M1: Motor Tanquinho 220V + Cap C_MOT 12uF
* PS1: Fonte Chaveada DIN 24V 2.5A (60W)
* FU1, FU2: Fusiveis Cerâmicos 2A 250V
* SB0: Emergencia Cogumelo 1NF+1NA c/ Trava
* SQ1: Chave Fim de Curso SPDT (Tampa)
* SB1: Botao Pulsador Desliga (NF)
* SB2: Botao Pulsador Liga (NA)
* HL1: LED Branco 24V (Painel Energizado)
* HL2: LED Verde 24V (Motor Operando)
* HL3: LED Amarelo 24V (Tampa Aberta)
* HL4: LED Vermelho 24V (Emergencia)
* PA1: Tacometro Digital LED CF5135B
* BG1: Sensor Efeito Hall M12 NPN NA""", 332, 33, 0.95, 3.0)

    # =========================================================================
    # PLACEMENT & WIRING
    # =========================================================================

    # 1. CIRCUITO DE FORCA (SECAO 1)
    # L1: X = 42 mm | L2: X = 52 mm | PE: X = 74 mm

    sch += net_label("TERRA_PE", 28, 35, 180)
    sch += wire(28, 35, 74, 35)
    sch += wire(74, 35, 74, 42.19)

    sch += net_label("REDE_220V_L2/N", 28, 38, 180)
    sch += wire(28, 38, 52, 38)
    sch += wire(52, 38, 52, 42.19)

    sch += net_label("REDE_220V_L1", 28, 41, 180)
    sch += wire(28, 41, 42, 41)
    sch += wire(42, 41, 42, 42.19)

    # XT0:1, XT0:2, XT0:3 - Bornes de Entrada at Y = 46 mm
    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 42 46 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "XT0.1" (at 45.5 46 0) (effects (font (size 1.0 1.0)) (justify left)))
    (property "Value" "L1_IN" (at 45.5 44 0) (effects (font (size 0.8 0.8)) (justify left)))
    (property "Footprint" "" (at 42 46 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("XT0.1")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 52 46 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "XT0.2" (at 55.5 46 0) (effects (font (size 1.0 1.0)) (justify left)))
    (property "Value" "L2_IN" (at 55.5 44 0) (effects (font (size 0.8 0.8)) (justify left)))
    (property "Footprint" "" (at 52 46 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("XT0.2")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 74 46 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "XT0.3" (at 77.5 46 0) (effects (font (size 1.0 1.0)) (justify left)))
    (property "Value" "PE_IN" (at 77.5 44 0) (effects (font (size 0.8 0.8)) (justify left)))
    (property "Footprint" "" (at 74 46 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("XT0.3")}
  )\n"""

    # Fios de Entrada para QM1:
    sch += wire(42, 49.81, 42, 56.38) # XT0:1 Out to QM1 Pin 1
    sch += wire_tag_v("[W01] 1.5mm² BK", 43.5, 53)
    sch += wire(52, 49.81, 52, 56.38) # XT0:2 Out to QM1 Pin 3
    sch += wire_tag_v("[W02] 1.5mm² BU", 53.5, 53)
    sch += wire_tag_v("[W03] 1.5mm² GNYE", 75.5, 38)

    # QM1 at (47, 64) - Disjuntor Bipolar 2P C10A
    sch += f"""  (symbol (lib_id "Industrial:CircuitBreaker_2P") (at 47 64 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "QM1" (at 35 64 90) (effects (font (size 1.27 1.27))))
    (property "Value" "Disjuntor_2P_C10A" (at 59 64 90) (effects (font (size 1.27 1.27))))
    (property "Footprint" "" (at 47 64 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("QM1")}
  )\n"""

    # QM1 Out to KM1 com derivacoes para FU1/FU2:
    sch += wire(42, 71.62, 42, 74)
    sch += junction(42, 74)
    sch += wire(42, 74, 42, 88.38)
    sch += wire_tag_v("[W04] 1.5mm² BK", 43.5, 80)
    sch += wire(42, 74, 105, 74) # Derivacao FU1
    sch += wire_tag_h("[W06] 0.75mm² BK", 68, 72.8)

    sch += wire(52, 71.62, 52, 86)
    sch += junction(52, 86)
    sch += wire(52, 86, 52, 88.38)
    sch += wire_tag_v("[W05] 1.5mm² BU", 53.5, 80)
    sch += wire(52, 86, 105, 86) # Derivacao FU2
    sch += wire_tag_h("[W07] 0.75mm² BU", 68, 84.8)

    # KM1 at (47, 96) - Contator de Potencia 2P
    sch += f"""  (symbol (lib_id "Industrial:Contactor_Power_2P") (at 47 96 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "KM1" (at 35 96 90) (effects (font (size 1.27 1.27))))
    (property "Value" "Contatos_Forca_KM1" (at 59 96 90) (effects (font (size 1.27 1.27))))
    (property "Footprint" "" (at 47 96 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("KM1")}
  )\n"""

    # KM1 Out to Dimmer TC1:
    sch += wire(42, 103.62, 42, 111.46)
    sch += wire_tag_v("[W08] 1.5mm² BK", 43.5, 107.5)
    sch += wire(52, 103.62, 52, 111.46)
    sch += wire_tag_v("[W09] 1.5mm² BU", 53.5, 107.5)

    # TC1 at (47, 124) - Dimmer AC 220V TRIAC:
    sch += f"""  (symbol (lib_id "Industrial:Dimmer_AC_220V") (at 47 124 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "TC1" (at 33 124 90) (effects (font (size 1.27 1.27))))
    (property "Value" "Dimmer_220V" (at 29 124 90) (effects (font (size 1.1 1.1))))
    (property "Footprint" "" (at 47 124 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("TC1")}
  )\n"""

    # TC1 Out to XT0:4, XT0:5 at Y = 148 mm:
    sch += wire(42, 136.54, 42, 144.19)
    sch += wire_tag_v("[W10] 1.5mm² BK", 43.5, 140)
    sch += wire(52, 136.54, 52, 144.19)
    sch += wire_tag_v("[W11] 1.5mm² BU", 53.5, 140)

    # XT0:4, XT0:5, XT0:6 - Bornes de Saida para Motor at Y = 148 mm:
    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 42 148 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "XT0.4" (at 45.5 148 0) (effects (font (size 1.0 1.0)) (justify left)))
    (property "Value" "L1_MOT" (at 45.5 146 0) (effects (font (size 0.8 0.8)) (justify left)))
    (property "Footprint" "" (at 42 148 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("XT0.4")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 52 148 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "XT0.5" (at 55.5 148 0) (effects (font (size 1.0 1.0)) (justify left)))
    (property "Value" "L2_MOT" (at 55.5 146 0) (effects (font (size 0.8 0.8)) (justify left)))
    (property "Footprint" "" (at 52 148 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("XT0.5")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 74 148 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "XT0.6" (at 77.5 148 0) (effects (font (size 1.0 1.0)) (justify left)))
    (property "Value" "PE_MOT" (at 77.5 146 0) (effects (font (size 0.8 0.8)) (justify left)))
    (property "Footprint" "" (at 74 148 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("XT0.6")}
  )\n"""

    # Linha PE Continua: de XT0:3 descendo ate XT0:6 (com derivacoes para PS1 e TC1)
    sch += wire(74, 49.81, 74, 93.08)
    sch += junction(74, 93.08)
    sch += wire(74, 93.08, 74, 124)
    sch += junction(74, 124)
    sch += wire(74, 124, 74, 144.19)
    sch += wire(74, 124, 57, 124) # Aterramento carcaca Dimmer TC1
    sch += wire_tag_h("[W12] 1.5mm² GNYE", 65, 122.8)
    sch += text_comment("PE Carcaca Dimmer", 59, 122, 0.85, False)

    # Fios de Saida de XT0:4/XT0:5/XT0:6 para Motor M1 at Y = 174 mm:
    sch += wire(42, 151.81, 42, 162.96)
    sch += wire_tag_v("[W10] 1.5mm² BK", 43.5, 157)
    sch += wire(52, 151.81, 52, 162.96)
    sch += wire_tag_v("[W11] 1.5mm² BU", 53.5, 157)
    sch += wire(74, 151.81, 74, 174)
    sch += wire_tag_v("[W13] 1.5mm² GNYE", 75.5, 160)
    sch += wire(74, 174, 69, 174) # Pino PE do Motor M1

    # M1 at (47, 174) - Motor Tanquinho 220V
    sch += f"""  (symbol (lib_id "Industrial:Motor_Tanquinho_220V") (at 47 174 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "M1" (at 47 159 0) (effects (font (size 1.27 1.27))))
    (property "Value" "Motor_Tanquinho_220V" (at 47 188 0) (effects (font (size 1.27 1.27))))
    (property "Footprint" "" (at 47 174 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("M1")}
  )\n"""

    # 2. SECAO DA FONTE CHAVEADA (SECAO 2)
    # Fusiveis FU1 e FU2:
    sch += f"""  (symbol (lib_id "Industrial:Fuse_Cartridge") (at 115 74 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "FU1" (at 115 70 0) (effects (font (size 1.27 1.27))))
    (property "Value" "2A_250V" (at 115 78 0) (effects (font (size 1.27 1.27))))
    (property "Footprint" "" (at 115 74 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("FU1")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:Fuse_Cartridge") (at 115 86 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "FU2" (at 115 82 0) (effects (font (size 1.27 1.27))))
    (property "Value" "2A_250V" (at 115 90 0) (effects (font (size 1.27 1.27))))
    (property "Footprint" "" (at 115 86 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("FU2")}
  )\n"""

    # PS1 - Fonte Chaveada 24V 2.5A
    sch += f"""  (symbol (lib_id "Industrial:SMPS_24V") (at 148 88 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "PS1" (at 148 70 0) (effects (font (size 1.27 1.27))))
    (property "Value" "SMPS_24V_2.5A" (at 148 106 0) (effects (font (size 1.27 1.27))))
    (property "Footprint" "" (at 148 88 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("PS1")}
  )\n"""

    # Fios para FU1/FU2 e PS1:
    sch += wire(105, 74, 108.65, 74)
    sch += wire(121.35, 74, 126, 74)
    sch += wire(126, 74, 126, 82.92)
    sch += wire(126, 82.92, 131.46, 82.92)
    sch += wire_tag_h("[W14] 0.75mm² BK", 126, 81.6)

    sch += wire(105, 86, 108.65, 86)
    sch += wire(121.35, 86, 126, 86)
    sch += wire(126, 86, 126, 88)
    sch += wire(126, 88, 131.46, 88)
    sch += wire_tag_h("[W15] 0.75mm² BU", 126, 86.8)

    # Aterramento PS1 Pino 3 (PE) conectado diretamente a Linha Principal de Terra PE (X=74):
    sch += wire(131.46, 93.08, 74, 93.08)
    sch += junction(74, 93.08)
    sch += wire_tag_h("[W16] 0.75mm² GNYE", 102, 91.8)

    # -------------------------------------------------------------------------
    # REGUA DE BORNES XT1 (+24V DC DISTRIBUTION)
    # -------------------------------------------------------------------------
    # XT1:1 (X=188, Y=44) - Alimentacao +24V da Fonte PS1
    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 188 44 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "XT1.1" (at 191.5 44 0) (effects (font (size 0.9 0.9)) (justify left)))
    (property "Value" "+24V_IN" (at 191.5 42 0) (effects (font (size 0.75 0.75)) (justify left)))
    (property "Footprint" "" (at 188 44 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("XT1.1")}
  )\n"""

    # XT1:2 (X=212, Y=44) - Saida +24V para SB0:11 (NF)
    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 212 44 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "XT1.2" (at 208 44 0) (effects (font (size 0.9 0.9)) (justify right)))
    (property "Value" "+24V_SB0" (at 208 42 0) (effects (font (size 0.75 0.75)) (justify right)))
    (property "Footprint" "" (at 212 44 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("XT1.2")}
  )\n"""

    # XT1:3 (X=224, Y=44) - Saida +24V para SB0:23 (NA)
    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 224 44 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "XT1.3" (at 227.5 44 0) (effects (font (size 0.9 0.9)) (justify left)))
    (property "Value" "+24V_EMG" (at 227.5 42 0) (effects (font (size 0.75 0.75)) (justify left)))
    (property "Footprint" "" (at 224 44 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("XT1.3")}
  )\n"""

    # XT1:4 (X=314, Y=44) - Saida +24V para HL1:X1
    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 314 44 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "XT1.4" (at 317.5 44 0) (effects (font (size 0.9 0.9)) (justify left)))
    (property "Value" "+24V_HL1" (at 317.5 42 0) (effects (font (size 0.75 0.75)) (justify left)))
    (property "Footprint" "" (at 314 44 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("XT1.4")}
  )\n"""

    # Ponte Conectora FBS (Jumper Bar XT1 +24V) at Y=40.19:
    sch += wire(188, 40.19, 212, 40.19)
    sch += junction(188, 40.19)
    sch += wire(212, 40.19, 224, 40.19)
    sch += junction(212, 40.19)
    sch += wire(224, 40.19, 314, 40.19)
    sch += junction(224, 40.19)
    sch += junction(314, 40.19)
    sch += text_comment("PONTE CONECTORA DIN (JUMPER XT1 +24V)", 195, 37.5, 1.0, True)
    sch += text_comment("REGUA DE BORNES XT1 (+24V DC)", 182, 42.5, 1.1, True)

    # W17: PS1 (+24V) para XT1:1 (Pin 1 em 188, 47.81):
    sch += wire(164.54, 82.92, 188, 82.92)
    sch += wire(188, 82.92, 188, 47.81)
    sch += wire_tag_v("[W17] 0.75mm² RD", 189.5, 65)

    # W19: XT1:2 Pin 1 para SB0:11 (em 212, 52.92):
    sch += wire(212, 47.81, 212, 52.92)
    sch += wire_tag_v("[W19] 0.75mm² RD", 213.5, 50.5)

    # W20: XT1:3 Pin 1 para SB0:23 (em 216.5, 52.92):
    sch += wire(224, 47.81, 224, 50)
    sch += wire(224, 50, 216.5, 50)
    sch += wire(216.5, 50, 216.5, 52.92)
    sch += wire_tag_h("[W20] 0.75mm² RD", 220, 48.8)

    # W21: XT1:4 Pin 1 para HL1:X1 (em 314, 108.65):
    sch += wire(314, 47.81, 314, 108.65)
    sch += wire_tag_v("[W21] 0.75mm² RD", 315.5, 75)

    # -------------------------------------------------------------------------
    # REGUA DE BORNES XT2 (0V / COM DISTRIBUTION)
    # -------------------------------------------------------------------------
    # XT2:1 a XT2:6 (Y = 181.19 mm):
    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 188 181.19 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "XT2.1" (at 191.5 181.19 0) (effects (font (size 0.9 0.9)) (justify left)))
    (property "Value" "0V_IN" (at 191.5 179 0) (effects (font (size 0.75 0.75)) (justify left)))
    (property "Footprint" "" (at 188 181.19 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("XT2.1")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 212 181.19 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "XT2.2" (at 215.5 181.19 0) (effects (font (size 0.9 0.9)) (justify left)))
    (property "Value" "0V_KM1" (at 215.5 179 0) (effects (font (size 0.75 0.75)) (justify left)))
    (property "Footprint" "" (at 212 181.19 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("XT2.2")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 245 181.19 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "XT2.3" (at 248.5 181.19 0) (effects (font (size 0.9 0.9)) (justify left)))
    (property "Value" "0V_HL2" (at 248.5 179 0) (effects (font (size 0.75 0.75)) (justify left)))
    (property "Footprint" "" (at 245 181.19 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("XT2.3")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 268 181.19 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "XT2.4" (at 271.5 181.19 0) (effects (font (size 0.9 0.9)) (justify left)))
    (property "Value" "0V_HL3" (at 271.5 179 0) (effects (font (size 0.75 0.75)) (justify left)))
    (property "Footprint" "" (at 268 181.19 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("XT2.4")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 291 181.19 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "XT2.5" (at 294.5 181.19 0) (effects (font (size 0.9 0.9)) (justify left)))
    (property "Value" "0V_HL4" (at 294.5 179 0) (effects (font (size 0.75 0.75)) (justify left)))
    (property "Footprint" "" (at 291 181.19 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("XT2.5")}
  )\n"""

    sch += f"""  (symbol (lib_id "Industrial:Terminal_Passagem") (at 314 181.19 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "XT2.6" (at 317.5 181.19 0) (effects (font (size 0.9 0.9)) (justify left)))
    (property "Value" "0V_HL1" (at 317.5 179 0) (effects (font (size 0.75 0.75)) (justify left)))
    (property "Footprint" "" (at 314 181.19 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("XT2.6")}
  )\n"""

    # Ponte Conectora FBS (Jumper Bar XT2 0V) at Y=185.0:
    sch += wire(188, 185, 212, 185)
    sch += junction(188, 185)
    sch += wire(212, 185, 245, 185)
    sch += junction(212, 185)
    sch += wire(245, 185, 268, 185)
    sch += junction(245, 185)
    sch += wire(268, 185, 291, 185)
    sch += junction(268, 185)
    sch += wire(291, 185, 314, 185)
    sch += junction(291, 185)
    sch += junction(314, 185)
    sch += text_comment("PONTE CONECTORA DIN (JUMPER XT2 0V)", 195, 189, 1.0, True)
    sch += text_comment("REGUA DE BORNES XT2 (0V / COM)", 182, 173.5, 1.1, True)

    # W18: PS1 (0V) para XT2:1 (Pin 1 em 188, 177.38):
    sch += wire(164.54, 93.08, 188, 93.08)
    sch += wire(188, 93.08, 188, 177.38)
    sch += wire_tag_v("[W18] 0.75mm² DBU", 189.5, 135)

    # 3. CIRCUITO DE COMANDO & INTERTRAVAMENTO NR-12 (SECAO 3)
    # Ramo 1 at X = 212 mm
    # SB0 at (212, 58)
    sch += f"""  (symbol (lib_id "Industrial:SW_Emergency_1NC_1NO") (at 212 58 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "SB0" (at 204 56 0) (effects (font (size 1.27 1.27)) (justify right)))
    (property "Value" "EMERGENCIA_1NF_1NA" (at 204 60 0) (effects (font (size 1.1 1.1)) (justify right)))
    (property "Footprint" "" (at 212 58 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("SB0")}
  )\n"""

    # SQ1 at (212, 78) - Microswitch Tampa
    sch += f"""  (symbol (lib_id "Industrial:SW_Limit_Safety_SPDT") (at 212 78 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "SQ1" (at 204 76 0) (effects (font (size 1.27 1.27)) (justify right)))
    (property "Value" "CHAVE_TAMPA_SPDT" (at 204 80 0) (effects (font (size 1.1 1.1)) (justify right)))
    (property "Footprint" "" (at 212 78 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("SQ1")}
  )\n"""

    # SB1 at (212, 98) - Botao Desliga (NF)
    sch += f"""  (symbol (lib_id "Industrial:SW_Push_NC") (at 212 98 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "SB1" (at 217 96 0) (effects (font (size 1.27 1.27)) (justify left)))
    (property "Value" "DESLIGA_NF" (at 217 100 0) (effects (font (size 1.27 1.27)) (justify left)))
    (property "Footprint" "" (at 212 98 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("SB1")}
  )\n"""

    # SB2 at (202, 122) - Botao Liga (NA)
    sch += f"""  (symbol (lib_id "Industrial:SW_Push_NO") (at 202 122 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "SB2" (at 193 122 0) (effects (font (size 1.27 1.27)) (justify right)))
    (property "Value" "LIGA_NA" (at 193 125 0) (effects (font (size 1.1 1.1)) (justify right)))
    (property "Footprint" "" (at 202 122 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("SB2")}
  )\n"""

    # KM1 Selo (13-14) at (222, 122)
    sch += f"""  (symbol (lib_id "Industrial:Contactor_Aux_NO") (at 222 122 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "KM1.1" (at 228 120 0) (effects (font (size 1.27 1.27)) (justify left)))
    (property "Value" "KM1_13-14_SELO" (at 228 124 0) (effects (font (size 1.1 1.1)) (justify left)))
    (property "Footprint" "" (at 222 122 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("KM1.1")}
  )\n"""

    # KM1 Bobina 24V DC at (212, 150)
    sch += f"""  (symbol (lib_id "Industrial:Contactor_Coil_24V") (at 212 150 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "KM1" (at 228 148 0) (effects (font (size 1.27 1.27)) (justify left)))
    (property "Value" "Bobina_24VDC" (at 228 152 0) (effects (font (size 1.27 1.27)) (justify left)))
    (property "Footprint" "" (at 212 150 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("KM1")}
  )\n"""

    # Fios Comando Ramo 1:
    # W22: SB0:12NC to SQ1:COM
    sch += wire(212, 63.08, 212, 72.92)
    sch += wire_tag_v("[W22] 0.75mm² RD", 213.5, 68)

    # W23: SQ1:NC to SB1:1NC
    sch += wire(212, 83.08, 212, 92.92)
    sch += wire_tag_v("[W23] 0.75mm² RD", 213.5, 88)

    # W24: SB1:2NC to No de Comando (212, 112)
    sch += wire(212, 103.08, 212, 112)
    sch += junction(212, 112)
    sch += wire_tag_v("[W24] 0.75mm² RD", 213.5, 107.5)

    # W25: No (212, 112) to SB2:3NO
    sch += wire(212, 112, 202, 112)
    sch += wire(202, 112, 202, 116.92)
    sch += wire_tag_v("[W25] 0.75mm² RD", 203.5, 114)

    # W26: No (212, 112) to KM1:13NO Selo
    sch += wire(212, 112, 222, 112)
    sch += wire(222, 112, 222, 116.92)
    sch += wire_tag_v("[W26] 0.75mm² RD", 223.5, 114)

    # W27: SB2:4NO to No Bobina (212, 136)
    sch += wire(202, 127.08, 202, 136)
    sch += wire(202, 136, 212, 136)
    sch += wire_tag_h("[W27] 0.75mm² RD", 205, 134.8)

    # W28: KM1:14NO Selo to No Bobina (212, 136)
    sch += wire(222, 127.08, 222, 136)
    sch += wire(222, 136, 212, 136)
    sch += junction(212, 136)
    sch += wire_tag_h("[W28] 0.75mm² RD", 219, 134.8)

    # W29: No (212, 136) to KM1:A1 (em 212, 143.46)
    sch += wire(212, 136, 212, 143.46)
    sch += wire_tag_v("[W29] 0.75mm² RD", 213.5, 140)

    # W31: KM1:A2 (em 212, 156.54) to XT2.2 (em 212, 177.38)
    sch += wire(212, 156.54, 212, 177.38)
    sch += wire_tag_v("[W31] 0.75mm² DBU", 213.5, 167)

    # Ramo 2: HL2 (Motor em Marcha - VERDE) em PARALELO com a Bobina KM1 (A1/A2) at (245, 150)
    sch += f"""  (symbol (lib_id "Industrial:Pilot_Lamp_24V") (at 245 150 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "HL2" (at 251 148 0) (effects (font (size 1.27 1.27)) (justify left)))
    (property "Value" "VERDE_24V" (at 251 152 0) (effects (font (size 1.1 1.1)) (justify left)))
    (property "Footprint" "" (at 245 150 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("HL2")}
  )\n"""
    # W30: Ligacao em paralelo do no +24V comutado (212, 136) ate HL2:X1:
    sch += wire(212, 136, 245, 136)
    sch += wire(245, 136, 245, 143.65)
    sch += wire_tag_h("[W30] 0.75mm² RD", 232, 134.8)
    # W32: HL2:X2 para XT2.3 (em 245, 177.38):
    sch += wire(245, 156.35, 245, 177.38)
    sch += wire_tag_v("[W32] 0.75mm² DBU", 246.5, 167)
    sch += text_comment("MOTOR EM MARCHA (HL2)", 232, 166, 0.95, False)
    sch += text_comment("PARALELO BOBINA KM1", 232, 170, 0.8, False)

    # Ramo 3: HL3 (Alarme: Tampa Aberta - AMARELO) via Contato NA do Microswitch SQ1 at (268, 115)
    sch += f"""  (symbol (lib_id "Industrial:Pilot_Lamp_24V") (at 268 115 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "HL3" (at 273 113 0) (effects (font (size 1.27 1.27)) (justify left)))
    (property "Value" "AMARELO_24V" (at 273 117 0) (effects (font (size 1.1 1.1)) (justify left)))
    (property "Footprint" "" (at 268 115 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("HL3")}
  )\n"""
    # W36: Fio do pino SQ1:NO (219.62, 78) ate (268, 78) e descendo ate HL3:X1:
    sch += wire(219.62, 78, 268, 78)
    sch += wire(268, 78, 268, 108.65)
    sch += wire_tag_h("[W36] 0.5mm² YE", 248, 76.8)
    # W33: HL3:X2 para XT2.4 (em 268, 177.38):
    sch += wire(268, 121.35, 268, 177.38)
    sch += wire_tag_v("[W33] 0.75mm² DBU", 269.5, 150)
    sch += text_comment("ALARME: TAMPA ABERTA (HL3)", 255, 133, 0.95, False)
    sch += text_comment("VIA SQ1 NA (AMARELO)", 255, 137, 0.8, False)

    # Ramo 4: HL4 (Alarme: Emergencia Acionada - VERMELHO) via Contato NA (Pin 24) de SB0 at (291, 115)
    sch += f"""  (symbol (lib_id "Industrial:Pilot_Lamp_24V") (at 291 115 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "HL4" (at 296 113 0) (effects (font (size 1.27 1.27)) (justify left)))
    (property "Value" "VERMELHO_24V" (at 296 117 0) (effects (font (size 1.1 1.1)) (justify left)))
    (property "Footprint" "" (at 291 115 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("HL4")}
  )\n"""
    # W37: Fio do pino SB0:24NO (219.62, 58) ate (291, 58) e descendo ate HL4:X1:
    sch += wire(219.62, 58, 291, 58)
    sch += wire(291, 58, 291, 108.65)
    sch += wire_tag_h("[W37] 0.5mm² RD", 268, 56.8)
    # W34: HL4:X2 para XT2.5 (em 291, 177.38):
    sch += wire(291, 121.35, 291, 177.38)
    sch += wire_tag_v("[W34] 0.75mm² DBU", 292.5, 150)
    sch += text_comment("EMERGENCIA ACIONADA (HL4)", 278, 133, 0.95, False)
    sch += text_comment("VIA SB0 BLOCO 2 NA (VERM)", 278, 137, 0.8, False)

    # Ramo 5: HL1 (Painel 24V Energizado - BRANCO) direto entre XT1:4 (+24V) e XT2:6 (0V) at (314, 115)
    sch += f"""  (symbol (lib_id "Industrial:Pilot_Lamp_24V") (at 314 115 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "HL1" (at 319 113 0) (effects (font (size 1.27 1.27)) (justify left)))
    (property "Value" "BRANCO_24V" (at 319 117 0) (effects (font (size 1.1 1.1)) (justify left)))
    (property "Footprint" "" (at 314 115 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("HL1")}
  )\n"""
    # W35: HL1:X2 para XT2.6 (em 314, 177.38):
    sch += wire(314, 121.35, 314, 177.38)
    sch += wire_tag_v("[W35] 0.75mm² DBU", 315.5, 150)
    sch += text_comment("PAINEL 24V ON (HL1)", 301, 133, 0.95, False)
    sch += text_comment("DIRETO DA FONTE 24V", 301, 137, 0.8, False)

    # 4. TACOMETRO DIGITAL DE PAINEL & SENSOR HALL (SECAO 4)
    # PA1 Display at X = 105, Y = 245
    sch += f"""  (symbol (lib_id "Industrial:Display_Tacometro_RPM") (at 105 245 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "PA1" (at 105 228 0) (effects (font (size 1.27 1.27))))
    (property "Value" "Tacometro_Digital_CF5135B" (at 105 262 0) (effects (font (size 1.27 1.27))))
    (property "Footprint" "" (at 105 245 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("PA1")}
  )\n"""

    # Alimentacao do PA1 no lado esquerdo:
    sch += wire(80.46, 241, 55, 241)
    sch += net_label("+24V", 55, 241, 180)
    sch += wire_tag_h("[W38] 0.5mm² RD", 68, 239.8)
    sch += text_comment("Alimentacao Display (12V-24V DC)", 56, 237, 1.0, False)
    sch += text_comment("(Derivado dos Bornes XT1/XT2)", 56, 233, 0.85, False)

    sch += wire(80.46, 249, 55, 249)
    sch += net_label("0V", 55, 249, 180)
    sch += wire_tag_h("[W39] 0.5mm² DBU", 68, 247.8)

    # Sensor Hall BG1 (NJK-5002C M12) at X = 230, Y = 245
    sch += f"""  (symbol (lib_id "Industrial:Sensor_Hall_NJK5002C") (at 230 245 0) (unit 1) (in_bom yes) (on_board yes)
    (uuid "{u()}")
    (property "Reference" "BG1" (at 230 233 0) (effects (font (size 1.27 1.27))))
    (property "Value" "NJK-5002C_M12" (at 230 257 0) (effects (font (size 1.27 1.27))))
    (property "Footprint" "" (at 230 245 0) (effects (font (size 1.27 1.27)) (hide yes)))
{sym_instance("BG1")}
  )\n"""

    # Fios entre Tacometro PA1 e Sensor BG1:
    # 1. Marrom (+24V):
    sch += wire(129.54, 239, 211.46, 239)
    sch += wire_tag_h("[W40] 0.5mm² BN", 170, 237.8)
    sch += text_comment("Fio Marrom (+24V)", 150, 235.5, 0.95, False)

    # 2. Preto (Sinal):
    sch += wire(129.54, 245, 211.46, 245)
    sch += wire_tag_h("[W41] 0.5mm² BK", 170, 243.8)
    sch += text_comment("Fio Preto (Sinal NPN Pulso)", 150, 241.5, 0.95, False)

    # 3. Azul (GND):
    sch += wire(129.54, 251, 211.46, 251)
    sch += wire_tag_h("[W42] 0.5mm² BU", 170, 249.8)
    sch += text_comment("Fio Azul (GND / 0V)", 150, 247.5, 0.95, False)

    # Nota do Ima:
    sch += text_comment("IMA DE NEODIMIO FIXADO NO EIXO / POLIA DO MOTOR", 195, 268, 1.1, True)
    sch += text_comment("Distancia de Deteccao: 1mm a 8mm | 1 Pulso por Rotacao (PPR = 1)", 195, 272, 1.0, False)

    sch += """  (sheet_instances
    (path "/" (page "1"))
  )
)
"""
    with open(SCH_PATH, "w", encoding="utf-8") as f:
        f.write(sch)

    print(f"Schematic v2.4 written successfully to {SCH_PATH}")

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
