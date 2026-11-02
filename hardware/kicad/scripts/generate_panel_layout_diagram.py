#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
generate_panel_layout_diagram.py
Gera diagrama técnico visual e dimensional da Caixa de Comando Montada (300x200x150 mm)
Mostrando:
1. Layout da Porta Frontal (Vista Externa e furações)
2. Layout da Placa de Montagem Interna (Trilho DIN, canaletas e componentes)
"""

import os
import matplotlib.pyplot as plt
import matplotlib.patches as patches
from matplotlib.patches import FancyBboxPatch, Circle, Rectangle

def generate_layout():
    fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(18, 12), dpi=180)
    fig.patch.set_facecolor('#1E1E24')

    # Configurações de estilo
    for ax in [ax1, ax2]:
        ax.set_facecolor('#262730')
        ax.set_xlim(-20, 220)
        ax.set_ylim(-30, 330)
        ax.set_aspect('equal')
        ax.axis('off')

    # =========================================================================
    # 1. PAINEL FRONTAL / PORTA (ax1)
    # =========================================================================
    ax1.set_title("VISTA FRONTAL DA PORTA (FURAÇÕES & OPERAÇÃO)\nDimensões: 300 x 200 mm", 
                  color='#FFFFFF', fontsize=14, fontweight='bold', pad=15)

    # Gabinete Externo (Chapa Cinza)
    door_outer = FancyBboxPatch((0, 0), 200, 300, boxstyle="round,pad=2,rounding_size=6",
                                facecolor='#D8DCE0', edgecolor='#555A60', linewidth=3)
    ax1.add_patch(door_outer)

    # Borda interna da porta
    door_inner = FancyBboxPatch((5, 5), 190, 290, boxstyle="round,pad=1,rounding_size=4",
                                facecolor='#ECEFF1', edgecolor='#B0BEC5', linewidth=1.5)
    ax1.add_patch(door_inner)

    # Dobradiças na esquerda
    hinge1 = FancyBboxPatch((-6, 240), 8, 30, boxstyle="round,pad=0.5,rounding_size=2",
                            facecolor='#78909C', edgecolor='#37474F', linewidth=1.5)
    hinge2 = FancyBboxPatch((-6, 30), 8, 30, boxstyle="round,pad=0.5,rounding_size=2",
                            facecolor='#78909C', edgecolor='#37474F', linewidth=1.5)
    ax1.add_patch(hinge1)
    ax1.add_patch(hinge2)
    ax1.text(-12, 150, "DOBRADIÇAS", color='#B0BEC5', fontsize=8, rotation=90, ha='center', va='center')

    # Fecho / Fechadura na direita
    lock = FancyBboxPatch((190, 140), 7, 20, boxstyle="round,pad=0.5,rounding_size=2",
                          facecolor='#455A64', edgecolor='#263238', linewidth=1.5)
    ax1.add_patch(lock)
    ax1.add_patch(Circle((193.5, 150), 2.5, facecolor='#263238', edgecolor='#ECEFF1', linewidth=1))

    # --- ITEM 1: Tacômetro Digital LED CF5135B (Topo) ---
    tacho_box = FancyBboxPatch((40, 225), 120, 50, boxstyle="round,pad=1,rounding_size=3",
                               facecolor='#1B1E23', edgecolor='#455A64', linewidth=2)
    ax1.add_patch(tacho_box)
    tacho_screen = Rectangle((50, 235), 70, 30, facecolor='#000000', edgecolor='#333333', linewidth=1.5)
    ax1.add_patch(tacho_screen)
    ax1.text(85, 250, "1750", color='#FF1744', fontsize=22, fontweight='bold', fontfamily='monospace', ha='center', va='center')
    ax1.text(140, 252, "RPM", color='#00E676', fontsize=10, fontweight='bold', ha='center', va='center')
    ax1.text(140, 240, "CF5135B", color='#90A4AE', fontsize=8, ha='center', va='center')
    ax1.text(100, 218, "TACÔMETRO DIGITAL (Rasgo 76x39 mm)", color='#37474F', fontsize=8, fontweight='bold', ha='center')

    # --- ITEM 2: Potenciômetro do Dimmer c/ Dial (Centro-Superior) ---
    pot_dial = Circle((100, 185), 15, facecolor='#263238', edgecolor='#78909C', linewidth=2)
    ax1.add_patch(pot_dial)
    pot_knob = Circle((100, 185), 9, facecolor='#B0BEC5', edgecolor='#37474F', linewidth=1.5)
    ax1.add_patch(pot_knob)
    ax1.plot([100, 107], [185, 192], color='#D50000', linewidth=2) # Ponteiro indicador
    ax1.text(100, 163, "POTENCIÔMETRO VELOCIDADE (0-100%)\nFuro ø10 mm", color='#37474F', fontsize=8, fontweight='bold', ha='center')

    # --- ITEM 3: Sinaleiros LED 22mm (Centro) ---
    # Sinaleiro Verde (Motor Operando)
    led_green = Circle((65, 125), 11, facecolor='#00E676', edgecolor='#1B5E20', linewidth=2)
    ax1.add_patch(led_green)
    ax1.add_patch(Circle((65, 125), 6, facecolor='#B9F6CA', edgecolor='none'))
    ax1.text(65, 107, "MOTOR LIGADO\nLED Verde 24V (DS1)\nFuro ø22 mm", color='#1B5E20', fontsize=8, fontweight='bold', ha='center')

    # Sinaleiro Vermelho (Tampa Aberta)
    led_red = Circle((135, 125), 11, facecolor='#FF1744', edgecolor='#B71C1C', linewidth=2)
    ax1.add_patch(led_red)
    ax1.add_patch(Circle((135, 125), 6, facecolor='#FF8A80', edgecolor='none'))
    ax1.text(135, 107, "TAMPA ABERTA\nLED Vermelho 24V (DS2)\nFuro ø22 mm", color='#B71C1C', fontsize=8, fontweight='bold', ha='center')

    # --- ITEM 4: Abotoadeiras Inferiores (Emergência, Liga, Desliga) ---
    # Botão de Emergência Cogumelo 40mm
    btn_emerg = Circle((45, 55), 18, facecolor='#D50000', edgecolor='#850000', linewidth=3)
    ax1.add_patch(btn_emerg)
    ax1.add_patch(Circle((45, 55), 7, facecolor='#FF5252', edgecolor='none'))
    ax1.text(45, 27, "EMERGÊNCIA\nCogumelo c/ Trava\nFuro ø22 mm", color='#850000', fontsize=8, fontweight='bold', ha='center')

    # Botão Desliga Vermelho (Faceado)
    btn_off_ring = Circle((105, 55), 11, facecolor='#37474F', edgecolor='#212121', linewidth=1.5)
    btn_off = Circle((105, 55), 9, facecolor='#D50000', edgecolor='#850000', linewidth=1)
    ax1.add_patch(btn_off_ring)
    ax1.add_patch(btn_off)
    ax1.text(105, 55, "0", color='#FFFFFF', fontsize=12, fontweight='bold', ha='center', va='center')
    ax1.text(105, 33, "DESLIGA (S0)\nBloco 1NF\nFuro ø22 mm", color='#37474F', fontsize=8, fontweight='bold', ha='center')

    # Botão Liga Verde (Faceado)
    btn_on_ring = Circle((155, 55), 11, facecolor='#37474F', edgecolor='#212121', linewidth=1.5)
    btn_on = Circle((155, 55), 9, facecolor='#00C853', edgecolor='#007E33', linewidth=1)
    ax1.add_patch(btn_on_ring)
    ax1.add_patch(btn_on)
    ax1.text(155, 55, "I", color='#FFFFFF', fontsize=12, fontweight='bold', ha='center', va='center')
    ax1.text(155, 33, "LIGA (S1)\nBloco 1NA\nFuro ø22 mm", color='#37474F', fontsize=8, fontweight='bold', ha='center')


    # =========================================================================
    # 2. PLACA DE MONTAGEM INTERNA (ax2)
    # =========================================================================
    ax2.set_title("VISTA INTERNA (CHASSI & TRILHO DIN)\nPlaca de Montagem Fundo Laranja", 
                  color='#FFFFFF', fontsize=14, fontweight='bold', pad=15)

    # Gabinete Externo Aberto (Borda interna)
    cab_box = FancyBboxPatch((0, 0), 200, 300, boxstyle="round,pad=2,rounding_size=6",
                             facecolor='#ECEFF1', edgecolor='#555A60', linewidth=3)
    ax2.add_patch(cab_box)

    # Placa de montagem Laranja Industrial
    plate = FancyBboxPatch((10, 10), 180, 280, boxstyle="round,pad=1,rounding_size=3",
                           facecolor='#FF9800', edgecolor='#E65100', linewidth=2)
    ax2.add_patch(plate)

    # Canaletas Ranhuradas Cinzas (Wire Ducts)
    # Topo
    ax2.add_patch(Rectangle((15, 255), 170, 25, facecolor='#9E9E9E', edgecolor='#616161', linewidth=1))
    for x in range(20, 180, 10):
        ax2.plot([x, x], [255, 280], color='#616161', linewidth=1)
    ax2.text(100, 267, "CANALETA SUPERIOR (30x30 mm)", color='#FFFFFF', fontsize=8, fontweight='bold', ha='center')

    # Meio
    ax2.add_patch(Rectangle((15, 100), 170, 25, facecolor='#9E9E9E', edgecolor='#616161', linewidth=1))
    for x in range(20, 180, 10):
        ax2.plot([x, x], [100, 125], color='#616161', linewidth=1)
    ax2.text(100, 112, "CANALETA INTERMEDIÁRIA (30x30 mm)", color='#FFFFFF', fontsize=8, fontweight='bold', ha='center')

    # Fundo
    ax2.add_patch(Rectangle((15, 15), 170, 25, facecolor='#9E9E9E', edgecolor='#616161', linewidth=1))
    for x in range(20, 180, 10):
        ax2.plot([x, x], [15, 40], color='#616161', linewidth=1)
    ax2.text(100, 27, "CANALETA INFERIOR (30x30 mm)", color='#FFFFFF', fontsize=8, fontweight='bold', ha='center')

    # Laterais
    ax2.add_patch(Rectangle((15, 40), 20, 215, facecolor='#757575', edgecolor='#424242', linewidth=1))
    ax2.add_patch(Rectangle((165, 40), 20, 215, facecolor='#757575', edgecolor='#424242', linewidth=1))

    # --- TRILHO DIN 1 (SUPERIOR: 190 mm) ---
    ax2.add_patch(Rectangle((35, 195), 130, 15, facecolor='#CFD8DC', edgecolor='#90A4AE', linewidth=1))
    ax2.text(100, 182, "TRILHO DIN TS-35 (NÍVEL FORÇA & CONTROLE)", color='#37474F', fontsize=7, fontweight='bold', ha='center')

    # Q1: Disjuntor Bipolar 2P (35 a 60)
    ax2.add_patch(FancyBboxPatch((38, 175), 22, 55, boxstyle="round,pad=0.5,rounding_size=1",
                                 facecolor='#FAFAFA', edgecolor='#212121', linewidth=1.5))
    ax2.add_patch(Rectangle((43, 195), 12, 14, facecolor='#212121', edgecolor='none'))
    ax2.text(49, 218, "Q1", color='#212121', fontsize=9, fontweight='bold', ha='center')
    ax2.text(49, 180, "C10A", color='#D50000', fontsize=8, fontweight='bold', ha='center')

    # F1/F2: Porta-Fusíveis 10x38 (63 a 83)
    ax2.add_patch(FancyBboxPatch((63, 177), 18, 51, boxstyle="round,pad=0.5,rounding_size=1",
                                 facecolor='#EEEEEE', edgecolor='#424242', linewidth=1.2))
    ax2.text(72, 218, "F1/F2", color='#424242', fontsize=8, fontweight='bold', ha='center')
    ax2.text(72, 182, "2A", color='#FF6D00', fontsize=8, fontweight='bold', ha='center')

    # T1: Fonte Chaveada 24V DIN (84 a 114)
    ax2.add_patch(FancyBboxPatch((84, 172), 30, 61, boxstyle="round,pad=0.5,rounding_size=1",
                                 facecolor='#37474F', edgecolor='#212121', linewidth=1.5))
    ax2.add_patch(Circle((99, 222), 2, facecolor='#00E676', edgecolor='none')) # LED DC OK
    ax2.text(99, 205, "FONTE\n24V 2.5A\n(T1)", color='#FFFFFF', fontsize=7, fontweight='bold', ha='center')

    # K1: Contator Steck SK1 Bobina 24VCC (117 a 155)
    ax2.add_patch(FancyBboxPatch((117, 170), 38, 65, boxstyle="round,pad=0.5,rounding_size=1",
                                 facecolor='#ECEFF1', edgecolor='#263238', linewidth=1.8))
    ax2.add_patch(Rectangle((126, 192), 20, 20, facecolor='#0277BD', edgecolor='#01579B', linewidth=1))
    ax2.text(136, 202, "K1", color='#FFFFFF', fontsize=11, fontweight='bold', ha='center', va='center')
    ax2.text(136, 222, "STECK 24Vcc", color='#01579B', fontsize=7, fontweight='bold', ha='center')
    ax2.text(136, 175, "13-14 NA", color='#D50000', fontsize=7, fontweight='bold', ha='center')

    # --- TRILHO DIN 2 (INFERIOR: 60 mm) ---
    ax2.add_patch(Rectangle((35, 65), 130, 15, facecolor='#CFD8DC', edgecolor='#90A4AE', linewidth=1))

    # DIM1: Módulo Dimmer 220V (40 a 75)
    ax2.add_patch(FancyBboxPatch((40, 48), 35, 48, boxstyle="round,pad=0.5,rounding_size=1",
                                 facecolor='#455A64', edgecolor='#263238', linewidth=1.5))
    # Aletas do dissipador
    for x in range(43, 73, 5):
        ax2.plot([x, x], [68, 92], color='#90A4AE', linewidth=1.5)
    ax2.text(57, 56, "DIMMER\n220V 2kW", color='#FFFFFF', fontsize=7, fontweight='bold', ha='center')

    # RÉGUA DE BORNES SAK (80 a 158)
    # Bornes Entrada Rede (TB1 L1, TB2 L2/N, TB3 Terra)
    ax2.add_patch(Rectangle((80, 50), 7, 44, facecolor='#B0BEC5', edgecolor='#37474F', linewidth=1))
    ax2.add_patch(Rectangle((88, 50), 7, 44, facecolor='#B0BEC5', edgecolor='#37474F', linewidth=1))
    ax2.add_patch(Rectangle((96, 50), 7, 44, facecolor='#7CB342', edgecolor='#33691E', linewidth=1)) # Terra
    ax2.text(83, 72, "L1", color='#212121', fontsize=6, rotation=90, ha='center', va='center')
    ax2.text(91, 72, "L2", color='#212121', fontsize=6, rotation=90, ha='center', va='center')
    ax2.text(99, 72, "PE", color='#FFFFFF', fontsize=6, rotation=90, ha='center', va='center')

    # Bornes Saída Motor (TB4 L1_M, TB5 L2_M, TB6 PE_M)
    ax2.add_patch(Rectangle((106, 50), 7, 44, facecolor='#90A4AE', edgecolor='#37474F', linewidth=1))
    ax2.add_patch(Rectangle((114, 50), 7, 44, facecolor='#90A4AE', edgecolor='#37474F', linewidth=1))
    ax2.add_patch(Rectangle((122, 50), 7, 44, facecolor='#7CB342', edgecolor='#33691E', linewidth=1)) # Terra Motor
    ax2.text(109, 72, "MOT1", color='#212121', fontsize=5.5, rotation=90, ha='center', va='center')
    ax2.text(117, 72, "MOT2", color='#212121', fontsize=5.5, rotation=90, ha='center', va='center')
    ax2.text(125, 72, "PE", color='#FFFFFF', fontsize=6, rotation=90, ha='center', va='center')

    # Bornes Tampa e Sensor Hall (130 a 155)
    for i, name in enumerate(["COM", "NC", "NO", "+V", "SIG", "0V"]):
        bx = 131 + i * 5
        ax2.add_patch(Rectangle((bx, 50), 4.5, 44, facecolor='#CFD8DC', edgecolor='#455A64', linewidth=0.8))
        ax2.text(bx + 2.2, 72, name, color='#212121', fontsize=5, rotation=90, ha='center', va='center')

    ax2.text(118, 42, "RÉGUA DE BORNES SAK / MOLA (TB1 a TB6 + COMANDO)", color='#37474F', fontsize=7, fontweight='bold', ha='center')

    # Prensa-cabos na Base Inferior (Fundo Externo da Caixa)
    ax2.text(100, -18, "PRENSA-CABOS DE ENTRADA / SAÍDA (BASE INFERIOR)", color='#B0BEC5', fontsize=9, fontweight='bold', ha='center')
    glands = [
        (40, "REDE 220V\n(M20)"),
        (80, "MOTOR M1\n(M20)"),
        (120, "TAMPA SQ1\n(M16)"),
        (160, "SENSOR HALL\n(M16)")
    ]
    for gx, glabel in glands:
        ax2.add_patch(FancyBboxPatch((gx - 8, -10), 16, 12, boxstyle="round,pad=0.5,rounding_size=1",
                                     facecolor='#263238', edgecolor='#37474F', linewidth=1.5))
        ax2.add_patch(Circle((gx, -10), 4, facecolor='#ECEFF1', edgecolor='#263238', linewidth=1))
        ax2.text(gx, -27, glabel, color='#90A4AE', fontsize=7, ha='center')

    # Espiral / Chicote articulado para a Porta
    ax2.plot([25, 15, 10, 5, 0], [150, 155, 160, 165, 170], color='#29B6F6', linewidth=4, linestyle=':')
    ax2.text(15, 142, "CHICOTE FLEXÍVEL\nC/ ESPIRAL P/ PORTA", color='#0288D1', fontsize=7, fontweight='bold')

    plt.tight_layout()
    
    out_dir = r"C:\Users\andrl\.gemini\antigravity-cli\brain\efd833bf-6ba1-4a1b-a7ce-aa9aa87aa86c"
    out_path = os.path.join(out_dir, "layout_caixa_comando_bancada.png")
    plt.savefig(out_path, dpi=200, facecolor=fig.get_facecolor(), bbox_inches='tight')
    plt.close()
    print(f"Diagram successfully generated at: {out_path}")

if __name__ == "__main__":
    generate_layout()
