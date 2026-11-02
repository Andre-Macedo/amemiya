#!/usr/bin/env python3
"""
Amemiya - Bancada de Testes de Motores & Analise de Vibracao
Gerador de Diagrama Pictorico de Montagem e Fiacao (Estilo Fritzing v2 - Ordem Fisica Linear)
Componentes Reais: Steck SD 2P, Steck SC, Dimmer AC, KW11-3Z, LAY7, Fonte Colmeia, Tacometro CF5135B, Botoes Sibratec XB2, LEDs 22mm
"""

import os
import math
from PIL import Image, ImageDraw, ImageFont

PROJECT_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "amemiya_bancada_painel"))
OUTPUT_PNG = os.path.join(PROJECT_DIR, "diagrama_montagem_fritzing.png")
ARTIFACT_PNG = r"C:\Users\andrl\.gemini\antigravity-cli\brain\efd833bf-6ba1-4a1b-a7ce-aa9aa87aa86c\diagrama_montagem_fritzing.png"
USER_MEDIA_DIR = r"C:\Users\andrl\.gemini\antigravity-cli\brain\efd833bf-6ba1-4a1b-a7ce-aa9aa87aa86c\.user_uploaded"

MEDIA = {
    "disjuntor": os.path.join(USER_MEDIA_DIR, "uploaded_media_0_1792445722563.png"),
    "contator": os.path.join(USER_MEDIA_DIR, "uploaded_media_1_1792445722563.png"),
    "dimmer": os.path.join(USER_MEDIA_DIR, "uploaded_media_2_1792445722563.png"),
    "microswitch": os.path.join(USER_MEDIA_DIR, "uploaded_media_3_1792445722563.png"),
    "emergencia": os.path.join(USER_MEDIA_DIR, "uploaded_media_4_1792445722563.png"),
    "colmeia": os.path.join(USER_MEDIA_DIR, "uploaded_media_5_1792445722563.png"),
    "tacometro": os.path.join(USER_MEDIA_DIR, "uploaded_media_6_1792445722563.png"),
    "btn_vermelho": os.path.join(USER_MEDIA_DIR, "uploaded_media_0_1792446973912.png"),
    "btn_verde": os.path.join(USER_MEDIA_DIR, "uploaded_media_1_1792446973912.png"),
    "led_vermelho": os.path.join(USER_MEDIA_DIR, "uploaded_media_2_1792446973912.png"),
    "led_verde": os.path.join(USER_MEDIA_DIR, "uploaded_media_3_1792446973912.png"),
    "led_branco": os.path.join(USER_MEDIA_DIR, "uploaded_media_4_1792446973912.png"),
}

W, H = 3800, 2750

def get_font(size, bold=False):
    try:
        if bold:
            return ImageFont.truetype("arialbd.ttf", size)
        return ImageFont.truetype("arial.ttf", size)
    except:
        return ImageFont.load_default()

def draw_rounded_rect(draw, bbox, radius, fill, outline=None, width=1):
    draw.rounded_rectangle(bbox, radius=radius, fill=fill, outline=outline, width=width)

def draw_screw(draw, x, y, r=13, label="", sublabel=""):
    draw.ellipse([x - r, y - r, x + r, y + r], fill="#CFD8DC", outline="#455A64", width=2)
    draw.ellipse([x - r + 3, y - r + 3, x + r - 3, y + r - 3], fill="#ECEFF1")
    draw.line([x - r + 4, y, x + r - 4, y], fill="#263238", width=3)
    draw.line([x, y - r + 4, x, y + r - 4], fill="#263238", width=3)
    if label:
        font = get_font(13, bold=True)
        draw.text((x - 14, y + r + 3), label, fill="#1A202C", font=font)
    if sublabel:
        font_sub = get_font(11, bold=False)
        draw.text((x - 18, y + r + 18), sublabel, fill="#5A6A80", font=font_sub)

def draw_wire(draw, pts, color, width=7, border_color="#111827", border_width=2):
    for i in range(len(pts) - 1):
        draw.line([pts[i], pts[i+1]], fill=border_color, width=width + border_width * 2)
    for i in range(len(pts) - 1):
        draw.line([pts[i], pts[i+1]], fill=color, width=width)
    for p in pts:
        draw.ellipse([p[0] - width//2, p[1] - width//2, p[0] + width//2, p[1] + width//2], fill=color)

def draw_striped_wire(draw, pts, color1="#16A34A", color2="#FACC15", width=7):
    for i in range(len(pts) - 1):
        draw.line([pts[i], pts[i+1]], fill="#064E3B", width=width + 4)
    for i in range(len(pts) - 1):
        draw.line([pts[i], pts[i+1]], fill=color1, width=width)
    for i in range(len(pts) - 1):
        p1, p2 = pts[i], pts[i+1]
        dist = math.hypot(p2[0] - p1[0], p2[1] - p1[1])
        if dist > 0:
            steps = int(dist // 18)
            for s in range(0, steps, 2):
                t1 = s / max(steps, 1)
                t2 = min((s + 1) / max(steps, 1), 1.0)
                x1 = p1[0] + (p2[0] - p1[0]) * t1
                y1 = p1[1] + (p2[1] - p1[1]) * t1
                x2 = p1[0] + (p2[0] - p1[0]) * t2
                y2 = p1[1] + (p2[1] - p1[1]) * t2
                draw.line([(x1, y1), (x2, y2)], fill=color2, width=width - 2)

def draw_wire_tag(draw, x, y, text, bg_color="#1E293B", text_color="#FFFFFF"):
    font = get_font(12, bold=True)
    bbox = font.getbbox(text)
    tw = bbox[2] - bbox[0]
    th = bbox[3] - bbox[1]
    draw.rounded_rectangle([x - 6, y - 3, x + tw + 6, y + th + 3], radius=4, fill=bg_color, outline="#F8FAFC", width=1)
    draw.text((x, y), text, fill=text_color, font=font)

def place_image(canvas, path, target_box):
    if os.path.exists(path):
        img = Image.open(path)
        img.thumbnail((target_box[2] - target_box[0], target_box[3] - target_box[1]), Image.Resampling.LANCZOS)
        x_off = target_box[0] + (target_box[2] - target_box[0] - img.width) // 2
        y_off = target_box[1] + (target_box[3] - target_box[1] - img.height) // 2
        canvas.paste(img, (x_off, y_off))
        return (x_off, y_off, img.width, img.height)
    return None

def main():
    os.makedirs(PROJECT_DIR, exist_ok=True)
    canvas = Image.new("RGB", (W, H), "#F1F5F9")
    draw = ImageDraw.Draw(canvas)

    # Background grid
    for y in range(0, H, 50):
        draw.line([(0, y), (W, y)], fill="#E2E8F0", width=1)
    for x in range(0, W, 50):
        draw.line([(x, 0), (x, H)], fill="#E2E8F0", width=1)

    # 1. Main Header Banner
    draw_rounded_rect(draw, [40, 25, W - 40, 135], radius=10, fill="#0F172A", outline="#1E293B", width=2)
    draw.text((70, 42), "GUIA DE MONTAGEM E FIAÇÃO POR ETAPAS FÍSICAS (ORDEM DE LIGAÇÃO)", fill="#F8FAFC", font=get_font(28, bold=True))
    draw.text((70, 82), "Diagrama Pictórico Linear com Fotos Reais | Sem Fios Cruzados | Bancada de Testes de Motores Amemiya", fill="#94A3B8", font=get_font(18))
    draw.text((70, 108), "Padrão de Cores: Vermelho (L1 / +24V), Azul (L2 / 0V), Verde/Amarelo (Terra PE), Roxo (Selo 13-14), Laranja (Emergência)", fill="#38BDF8", font=get_font(13, bold=True))

    draw_rounded_rect(draw, [W - 460, 40, W - 60, 120], radius=8, fill="#10B981")
    draw.text((W - 440, 52), "ORDEM FÍSICA REAL", fill="#FFFFFF", font=get_font(18, bold=True))
    draw.text((W - 440, 80), "Do parafuso ao borne sem confusão", fill="#ECFDF5", font=get_font(13))

    # =========================================================================
    # SECTION 1: FORÇA 220V & FONTE (ALTA TENSÃO) - Y: 155 to 940
    # =========================================================================
    sec1_box = [50, 155, 2550, 940]
    draw_rounded_rect(draw, sec1_box, radius=12, fill="#FFFFFF", outline="#DC2626", width=2)
    draw_rounded_rect(draw, [sec1_box[0], sec1_box[1], sec1_box[0] + 700, sec1_box[1] + 40], radius=8, fill="#DC2626")
    draw.text((sec1_box[0] + 15, sec1_box[1] + 8), "ETAPA 1: CIRCUITO DE FORÇA 220V & FONTE (FLUXO DIRETO DE POTÊNCIA)", fill="#FFFFFF", font=get_font(16, bold=True))

    # 1.1 Entrada de Rede (Bornes)
    draw_rounded_rect(draw, [80, 220, 300, 890], radius=8, fill="#F8FAFC", outline="#CBD5E1", width=2)
    draw.text((95, 235), "1. REDE 220V", fill="#0F172A", font=get_font(15, bold=True))
    draw.text((95, 255), "Entrada da Parede / Plugue", fill="#64748B", font=get_font(12))

    tb_l1 = (240, 360)
    tb_l2 = (240, 520)
    tb_pe = (240, 680)
    draw_screw(draw, tb_l1[0], tb_l1[1], 13, "L1", "Fase 1 (220V)")
    draw_screw(draw, tb_l2[0], tb_l2[1], 13, "L2/N", "Fase 2 / Neutro")
    draw_screw(draw, tb_pe[0], tb_pe[1], 13, "PE", "Terra Bancada")

    # 1.2 Disjuntor Steck SD 2P (Q1)
    draw_rounded_rect(draw, [350, 220, 660, 890], radius=8, fill="#F8FAFC", outline="#CBD5E1", width=2)
    draw.text((365, 235), "2. DISJUNTOR Q1", fill="#0F172A", font=get_font(15, bold=True))
    draw.text((365, 255), "Steck SD 2P Curva C 10A", fill="#64748B", font=get_font(12))
    place_image(canvas, MEDIA["disjuntor"], [390, 310, 620, 770])
    q1_in1  = (445, 300)
    q1_in2  = (565, 300)
    q1_out1 = (445, 800)
    q1_out2 = (565, 800)
    draw_screw(draw, q1_in1[0], q1_in1[1], 12, "1", "Entrada L1")
    draw_screw(draw, q1_in2[0], q1_in2[1], 12, "3", "Entrada L2")
    draw_screw(draw, q1_out1[0], q1_out1[1], 12, "2", "Saída L1")
    draw_screw(draw, q1_out2[0], q1_out2[1], 12, "4", "Saída L2")

    # 1.3 Contator Steck SC (K1) - Força
    draw_rounded_rect(draw, [710, 220, 1100, 890], radius=8, fill="#F8FAFC", outline="#CBD5E1", width=2)
    draw.text((725, 235), "3. CONTATOR K1", fill="#0F172A", font=get_font(15, bold=True))
    draw.text((725, 255), "Steck SC (Contatos de Força)", fill="#64748B", font=get_font(12))
    place_image(canvas, MEDIA["contator"], [750, 310, 1060, 770])
    k1_1L1 = (790, 300)
    k1_3L2 = (880, 300)
    k1_2T1 = (790, 800)
    k1_4T2 = (880, 800)
    draw_screw(draw, k1_1L1[0], k1_1L1[1], 12, "1L1", "Entrada L1")
    draw_screw(draw, k1_3L2[0], k1_3L2[1], 12, "3L2", "Entrada L2")
    draw_screw(draw, k1_2T1[0], k1_2T1[1], 12, "2T1", "Saída L1")
    draw_screw(draw, k1_4T2[0], k1_4T2[1], 12, "4T2", "Saída L2")

    # 1.4 Dimmer AC 220V Externo
    draw_rounded_rect(draw, [1150, 220, 1600, 890], radius=8, fill="#F8FAFC", outline="#CBD5E1", width=2)
    draw.text((1165, 235), "4. DIMMER AC 220V", fill="#0F172A", font=get_font(15, bold=True))
    draw.text((1165, 255), "Controle Velocidade c/ Knob", fill="#64748B", font=get_font(12))
    place_image(canvas, MEDIA["dimmer"], [1180, 340, 1570, 680])
    dim_in1  = (1260, 800)
    dim_in2  = (1350, 800)
    dim_out1 = (1440, 800)
    dim_out2 = (1530, 800)
    draw_screw(draw, dim_in1[0], dim_in1[1], 12, "IN L", "Plugue Macho")
    draw_screw(draw, dim_in2[0], dim_in2[1], 12, "IN N", "Plugue Macho")
    draw_screw(draw, dim_out1[0], dim_out1[1], 12, "OUT 1", "Tomada Fêmea")
    draw_screw(draw, dim_out2[0], dim_out2[1], 12, "OUT 2", "Tomada Fêmea")

    # 1.5 Motor de Tanquinho 220V + Capacitor
    draw_rounded_rect(draw, [1650, 220, 2080, 890], radius=8, fill="#F8FAFC", outline="#CBD5E1", width=2)
    draw.text((1665, 235), "5. MOTOR TANQUINHO", fill="#0F172A", font=get_font(15, bold=True))
    draw.text((1665, 255), "220V ~1/4 CV + Cap. 12-15uF", fill="#64748B", font=get_font(12))

    # Motor circle graphic
    draw.ellipse([1730, 330, 2000, 600], fill="#475569", outline="#0F172A", width=3)
    draw.ellipse([1760, 360, 1970, 570], fill="#64748B")
    draw.text((1815, 445), "MOTOR", fill="#F8FAFC", font=get_font(18, bold=True))
    draw.text((1805, 475), "TANQUINHO", fill="#E2E8F0", font=get_font(14))

    # Capacitor block
    draw_rounded_rect(draw, [1730, 630, 1870, 720], radius=6, fill="#E2E8F0", outline="#475569", width=2)
    draw.text((1745, 645), "CAPACITOR", fill="#0F172A", font=get_font(11, bold=True))
    draw.text((1750, 665), "12 a 15 uF", fill="#0284C7", font=get_font(13, bold=True))
    draw.text((1750, 685), "250V AC", fill="#64748B", font=get_font(10))

    mot_com = (1750, 800)
    mot_run = (1880, 800)
    mot_pe  = (2000, 800)
    draw_screw(draw, mot_com[0], mot_com[1], 12, "COMUM", "Fio Preto/Verm")
    draw_screw(draw, mot_run[0], mot_run[1], 12, "MARCHA", "Fio Azul/Amarelo")
    draw_screw(draw, mot_pe[0], mot_pe[1], 12, "CARCAÇA", "Parafuso Terra")

    # 1.6 Fonte Chaveada Colmeia (T1)
    draw_rounded_rect(draw, [2120, 220, 2510, 890], radius=8, fill="#F8FAFC", outline="#CBD5E1", width=2)
    draw.text((2135, 235), "FONTE COLMEIA 24V", fill="#0F172A", font=get_font(15, bold=True))
    draw.text((2135, 255), "Alimenta Comando & Tacômetro", fill="#64748B", font=get_font(12))
    place_image(canvas, MEDIA["colmeia"], [2150, 310, 2480, 680])
    smps_L   = (2180, 800)
    smps_N   = (2250, 800)
    smps_PE  = (2320, 800)
    smps_GND = (2390, 800)
    smps_VCC = (2460, 800)
    draw_screw(draw, smps_L[0], smps_L[1], 11, "L", "Fase")
    draw_screw(draw, smps_N[0], smps_N[1], 11, "N", "Neutro")
    draw_screw(draw, smps_PE[0], smps_PE[1], 11, "PE", "Terra")
    draw_screw(draw, smps_GND[0], smps_GND[1], 11, "-V", "0V DC")
    draw_screw(draw, smps_VCC[0], smps_VCC[1], 11, "+V", "+24V DC")

    # --- FIAÇÃO DE FORÇA LINEAR (SEM CRUZAMENTO) ---
    # L1: TB -> Q1 (pino 1)
    draw_wire(draw, [tb_l1, (320, 360), (320, 280), (445, 280), q1_in1], color="#DC2626", width=7)
    draw_wire_tag(draw, 325, 290, "Fase L1 (220V)", bg_color="#DC2626")

    # L2: TB -> Q1 (pino 3)
    draw_wire(draw, [tb_l2, (335, 520), (335, 270), (565, 270), q1_in2], color="#2563EB", width=7)
    draw_wire_tag(draw, 325, 500, "Fase L2 / N", bg_color="#2563EB")

    # Q1 Out 2 (L1) -> K1 1L1 (com derivação p/ Fonte L)
    draw_wire(draw, [q1_out1, (445, 835), (790, 835), (790, 300), k1_1L1], color="#DC2626", width=7)
    draw_wire(draw, [(790, 835), (2180, 835), smps_L], color="#DC2626", width=5)
    draw_wire_tag(draw, 490, 820, "Fase L1 Protegida", bg_color="#DC2626")

    # Q1 Out 4 (L2) -> K1 3L2 (com derivação p/ Fonte N)
    draw_wire(draw, [q1_out2, (565, 850), (880, 850), (880, 300), k1_3L2], color="#2563EB", width=7)
    draw_wire(draw, [(880, 850), (2250, 850), smps_N], color="#2563EB", width=5)
    draw_wire_tag(draw, 600, 835, "Fase L2 Protegida", bg_color="#2563EB")

    # K1 Out 2T1 -> Dimmer IN L
    draw_wire(draw, [k1_2T1, (790, 865), (1260, 865), dim_in1], color="#B91C1C", width=7)
    draw_wire_tag(draw, 960, 850, "L1 Comutada pelo Contator", bg_color="#B91C1C")

    # K1 Out 4T2 -> Dimmer IN N
    draw_wire(draw, [k1_4T2, (880, 880), (1350, 880), dim_in2], color="#1D4ED8", width=7)
    draw_wire_tag(draw, 960, 885, "L2 Comutada pelo Contator", bg_color="#1D4ED8")

    # Dimmer OUT 1 -> Motor Comum
    draw_wire(draw, [dim_out1, (1440, 835), (1750, 835), mot_com], color="#B91C1C", width=7)
    draw_wire_tag(draw, 1550, 820, "Velocidade Regulada", bg_color="#B91C1C")

    # Dimmer OUT 2 -> Motor Marcha
    draw_wire(draw, [dim_out2, (1530, 850), (1880, 850), mot_run], color="#1D4ED8", width=7)

    # Terra PE: TB -> Fonte PE e Carcaça Motor
    draw_striped_wire(draw, [tb_pe, (310, 680), (310, 895), (2320, 895), smps_PE], width=7)
    draw_striped_wire(draw, [(2000, 800), (2000, 895)], width=7)
    draw_wire_tag(draw, 1000, 905, "Barramento de Terra PE (Carcaça & Fonte)", bg_color="#15803D")

    # =========================================================================
    # SECTION 2: COMANDO EM SÉRIE (SEGURANÇA NR-12) - Y: 960 to 1750
    # =========================================================================
    sec2_box = [50, 960, 2550, 1750]
    draw_rounded_rect(draw, sec2_box, radius=12, fill="#FFFFFF", outline="#7C3AED", width=2)
    draw_rounded_rect(draw, [sec2_box[0], sec2_box[1], sec2_box[0] + 700, sec2_box[1] + 40], radius=8, fill="#7C3AED")
    draw.text((sec2_box[0] + 15, sec2_box[1] + 8), "ETAPA 2: CADEIA DE SEGURANÇA E COMANDO EM SÉRIE (24V DC)", fill="#FFFFFF", font=get_font(16, bold=True))

    # 2.1 Ponto de Alimentação (+24V da Fonte Colmeia)
    draw_rounded_rect(draw, [80, 1020, 310, 1690], radius=8, fill="#F8FAFC", outline="#CBD5E1", width=2)
    draw.text((95, 1035), "1. FONTE DC", fill="#0F172A", font=get_font(15, bold=True))
    draw.text((95, 1055), "Início do Laço 24V", fill="#64748B", font=get_font(12))
    pwr_24v = (200, 1200)
    pwr_0v  = (200, 1500)
    draw_screw(draw, pwr_24v[0], pwr_24v[1], 13, "+24V", "Ponto de Partida")
    draw_screw(draw, pwr_0v[0], pwr_0v[1], 13, "0V / COM", "Ponto de Retorno")

    # 2.2 Botão de Emergência LAY7
    draw_rounded_rect(draw, [360, 1020, 710, 1690], radius=8, fill="#F8FAFC", outline="#CBD5E1", width=2)
    draw.text((375, 1035), "2. BOTÃO EMERGÊNCIA", fill="#0F172A", font=get_font(15, bold=True))
    draw.text((375, 1055), "LAY7 Gira-Puxa (NF 1-2)", fill="#DC2626", font=get_font(12, bold=True))
    place_image(canvas, MEDIA["emergencia"], [400, 1100, 670, 1500])
    emg_in  = (450, 1580)
    emg_out = (620, 1580)
    draw_screw(draw, emg_in[0], emg_in[1], 12, "1 (NF)", "Entrada 24V")
    draw_screw(draw, emg_out[0], emg_out[1], 12, "2 (NF)", "Saída p/ Tampa")

    # 2.3 Microswitch da Tampa KW11-3Z
    draw_rounded_rect(draw, [760, 1020, 1150, 1690], radius=8, fill="#F8FAFC", outline="#CBD5E1", width=2)
    draw.text((775, 1035), "3. CHAVE DA TAMPA", fill="#0F172A", font=get_font(15, bold=True))
    draw.text((775, 1055), "Microswitch KW11-3Z (COM-NC)", fill="#16A34A", font=get_font(12, bold=True))
    place_image(canvas, MEDIA["microswitch"], [800, 1100, 1110, 1500])
    msw_in  = (860, 1580)
    msw_out = (1050, 1580)
    draw_screw(draw, msw_in[0], msw_in[1], 12, "COM", "Entrada da Emg.")
    draw_screw(draw, msw_out[0], msw_out[1], 12, "NC", "Saída p/ Desliga")

    # 2.4 Botão Vermelho Sibratec XB2 (Desliga)
    draw_rounded_rect(draw, [1200, 1020, 1550, 1690], radius=8, fill="#F8FAFC", outline="#CBD5E1", width=2)
    draw.text((1215, 1035), "4. BOTÃO DESLIGA", fill="#0F172A", font=get_font(15, bold=True))
    draw.text((1215, 1055), "Sibratec XB2-EA145 (NF)", fill="#DC2626", font=get_font(12, bold=True))
    place_image(canvas, MEDIA["btn_vermelho"], [1250, 1100, 1500, 1500])
    btn_off_in  = (1280, 1580)
    btn_off_out = (1470, 1580)
    draw_screw(draw, btn_off_in[0], btn_off_in[1], 12, "C (11)", "Entrada da Tampa")
    draw_screw(draw, btn_off_out[0], btn_off_out[1], 12, "NC (12)", "Saída p/ Liga")

    # 2.5 Botão Verde Sibratec XB2 (Liga)
    draw_rounded_rect(draw, [1600, 1020, 1950, 1690], radius=8, fill="#F8FAFC", outline="#CBD5E1", width=2)
    draw.text((1615, 1035), "5. BOTÃO LIGA", fill="#0F172A", font=get_font(15, bold=True))
    draw.text((1615, 1055), "Sibratec XB2-EA135 (NA)", fill="#16A34A", font=get_font(12, bold=True))
    place_image(canvas, MEDIA["btn_verde"], [1650, 1100, 1900, 1500])
    btn_on_in  = (1680, 1580)
    btn_on_out = (1870, 1580)
    draw_screw(draw, btn_on_in[0], btn_on_in[1], 12, "C (11)", "Entrada / Selo 13")
    draw_screw(draw, btn_on_out[0], btn_on_out[1], 12, "NO (14)", "Saída p/ Bobina A1")

    # 2.6 Bobina K1 (A1 - A2) & Contato de Selo (13 - 14)
    draw_rounded_rect(draw, [2000, 1020, 2510, 1690], radius=8, fill="#F8FAFC", outline="#CBD5E1", width=2)
    draw.text((2015, 1035), "6. BOBINA & SELO K1", fill="#0F172A", font=get_font(15, bold=True))
    draw.text((2015, 1055), "Acionamento do Contator Steck", fill="#64748B", font=get_font(12))
    place_image(canvas, MEDIA["contator"], [2050, 1100, 2460, 1460])
    k1_selo13 = (2080, 1550)
    k1_selo14 = (2200, 1550)
    k1_coilA1 = (2330, 1550)
    k1_coilA2 = (2440, 1550)
    draw_screw(draw, k1_selo13[0], k1_selo13[1], 12, "13", "Selo NA")
    draw_screw(draw, k1_selo14[0], k1_selo14[1], 12, "14", "Selo NA")
    draw_screw(draw, k1_coilA1[0], k1_coilA1[1], 12, "A1", "Bobina (+24V)")
    draw_screw(draw, k1_coilA2[0], k1_coilA2[1], 12, "A2", "Bobina (0V Retorno)")

    # --- FIAÇÃO DE COMANDO EM CADEIA EM SÉRIE (ZERO CRUZAMENTO) ---
    # Passo 1: +24V -> Entrada da Emergência 1
    draw_wire(draw, [pwr_24v, (320, 1200), (320, 1580), emg_in], color="#EA580C", width=6)
    draw_wire_tag(draw, 220, 1180, "+24V DC da Fonte", bg_color="#EA580C")

    # Passo 2: Saída Emergência 2 -> Entrada Microswitch COM
    draw_wire(draw, [emg_out, (690, 1580), (690, 1620), (860, 1620), msw_in], color="#EA580C", width=6)
    draw_wire_tag(draw, 640, 1635, "Retorno Emergência", bg_color="#EA580C")

    # Passo 3: Saída Microswitch NC -> Entrada Botão Desliga C (11)
    draw_wire(draw, [msw_out, (1120, 1580), (1120, 1620), (1280, 1620), btn_off_in], color="#EA580C", width=6)
    draw_wire_tag(draw, 1070, 1635, "Retorno Tampa Fechada", bg_color="#EA580C")

    # Passo 4: Saída Botão Desliga NC (12) -> Entrada Botão Liga C (11) & Selo 13
    draw_wire(draw, [btn_off_out, (1530, 1580), (1530, 1620), (1680, 1620), btn_on_in], color="#9333EA", width=6)
    # Derivação para o Selo 13
    draw_wire(draw, [(1680, 1620), (1680, 1660), (2080, 1660), k1_selo13], color="#9333EA", width=5)
    draw_wire_tag(draw, 1540, 1600, "Nó de Partida (Liga + Selo 13)", bg_color="#9333EA")

    # Passo 5: Saída Botão Liga NO (14) -> Bobina A1 & Selo 14
    draw_wire(draw, [btn_on_out, (1930, 1580), (1930, 1620), (2330, 1620), k1_coilA1], color="#16A34A", width=6)
    # Derivação para o Selo 14
    draw_wire(draw, [(2200, 1550), (2200, 1620)], color="#16A34A", width=5)
    draw_wire_tag(draw, 1950, 1600, "Pulso Liga -> Bobina A1 & Selo 14", bg_color="#16A34A")

    # Passo 6: Bobina A2 -> Retorno 0V
    draw_wire(draw, [k1_coilA2, (2440, 1710), (200, 1710), pwr_0v], color="#334155", width=6)
    draw_wire_tag(draw, 1200, 1695, "Retorno da Bobina (0V / COM)", bg_color="#334155")

    # =========================================================================
    # SECTION 3: INSTRUMENTAÇÃO & SINALIZAÇÃO LED - Y: 1775 to 2670
    # =========================================================================
    sec3_box = [50, 1775, 2550, 2670]
    draw_rounded_rect(draw, sec3_box, radius=12, fill="#FFFFFF", outline="#0284C7", width=2)
    draw_rounded_rect(draw, [sec3_box[0], sec3_box[1], sec3_box[0] + 700, sec3_box[1] + 40], radius=8, fill="#0284C7")
    draw.text((sec3_box[0] + 15, sec3_box[1] + 8), "ETAPA 3: TACÔMETRO DIGITAL CF5135B & SINALIZAÇÃO VISUAL LED", fill="#FFFFFF", font=get_font(16, bold=True))

    # 3.1 Tacômetro Digital & Sensor Hall M12
    draw_rounded_rect(draw, [80, 1835, 1150, 2620], radius=8, fill="#F8FAFC", outline="#CBD5E1", width=2)
    draw.text((95, 1850), "1. TACÔMETRO DIGITAL CF5135B & SENSOR HALL NJK-5002C", fill="#0F172A", font=get_font(15, bold=True))
    draw.text((95, 1870), "Display 4 Dígitos LED Azul + Sensor M12 c/ Ímã de Neodímio no Eixo", fill="#64748B", font=get_font(12))
    place_image(canvas, MEDIA["tacometro"], [100, 1910, 750, 2380])

    tacho_vcc   = (820, 2000)
    tacho_gnd   = (820, 2100)
    tacho_sen_v = (820, 2220)
    tacho_sen_s = (820, 2320)
    tacho_sen_g = (820, 2420)
    draw_screw(draw, tacho_vcc[0], tacho_vcc[1], 12, "+24V", "Alimentação Display")
    draw_screw(draw, tacho_gnd[0], tacho_gnd[1], 12, "0V", "Alimentação Display")
    draw_screw(draw, tacho_sen_v[0], tacho_sen_v[1], 12, "MARROM", "Sensor (+V)")
    draw_screw(draw, tacho_sen_s[0], tacho_sen_s[1], 12, "PRETO", "Sensor (Sinal NPN)")
    draw_screw(draw, tacho_sen_g[0], tacho_sen_g[1], 12, "AZUL", "Sensor (GND)")

    # Hall Sensor callout note
    draw_rounded_rect(draw, [960, 2200, 1130, 2460], radius=6, fill="#EFF6FF", outline="#93C5FD", width=1)
    draw.text((975, 2220), "SENSOR HALL M12", fill="#1E40AF", font=get_font(11, bold=True))
    draw.text((975, 2245), "• Fio Marrom: +V", fill="#78350F", font=get_font(10, bold=True))
    draw.text((975, 2265), "• Fio Preto: Sinal", fill="#0F172A", font=get_font(10, bold=True))
    draw.text((975, 2285), "• Fio Azul: GND", fill="#1D4ED8", font=get_font(10, bold=True))
    draw.text((975, 2315), "Distância até o ímã:", fill="#64748B", font=get_font(10))
    draw.text((975, 2335), "1 mm a 5 mm", fill="#DC2626", font=get_font(11, bold=True))

    # Wires to sensor
    draw_wire(draw, [tacho_sen_v, (960, 2220)], color="#78350F", width=5)
    draw_wire(draw, [tacho_sen_s, (960, 2265)], color="#0F172A", width=5)
    draw_wire(draw, [tacho_sen_g, (960, 2285)], color="#2563EB", width=5)

    # 3.2 Sinaleiros LED de Painel (Branco, Verde, Vermelho)
    draw_rounded_rect(draw, [1200, 1835, 2510, 2620], radius=8, fill="#F8FAFC", outline="#CBD5E1", width=2)
    draw.text((1215, 1850), "2. SINALIZADORES VISUAIS LED 22MM (PORTA DO PAINEL)", fill="#0F172A", font=get_font(15, bold=True))
    draw.text((1215, 1870), "Fotos Reais dos LEDs Sibratec/WEG: Branco (Painel Ligado) | Verde (Motor Ligado) | Vermelho (Alarme)", fill="#64748B", font=get_font(12))

    # LED Branco
    draw_rounded_rect(draw, [1230, 1920, 1610, 2580], radius=8, fill="#FFFFFF", outline="#E2E8F0", width=2)
    draw.text((1250, 1940), "LED BRANCO", fill="#0F172A", font=get_font(14, bold=True))
    draw.text((1250, 1960), "Painel 24V Ligado", fill="#64748B", font=get_font(12))
    place_image(canvas, MEDIA["led_branco"], [1270, 2010, 1570, 2350])
    led_w_x1 = (1340, 2480)
    led_w_x2 = (1500, 2480)
    draw_screw(draw, led_w_x1[0], led_w_x1[1], 12, "X1 (+)", "Ligar no +24V")
    draw_screw(draw, led_w_x2[0], led_w_x2[1], 12, "X2 (-)", "Ligar no 0V")

    # LED Verde
    draw_rounded_rect(draw, [1660, 1920, 2040, 2580], radius=8, fill="#FFFFFF", outline="#E2E8F0", width=2)
    draw.text((1680, 1940), "LED VERDE", fill="#16A34A", font=get_font(14, bold=True))
    draw.text((1680, 1960), "Motor Operando", fill="#64748B", font=get_font(12))
    place_image(canvas, MEDIA["led_verde"], [1700, 2010, 2000, 2350])
    led_g_x1 = (1770, 2480)
    led_g_x2 = (1930, 2480)
    draw_screw(draw, led_g_x1[0], led_g_x1[1], 12, "X1 (+)", "Ligar no A1 de K1")
    draw_screw(draw, led_g_x2[0], led_g_x2[1], 12, "X2 (-)", "Ligar no 0V")

    # LED Vermelho
    draw_rounded_rect(draw, [2090, 1920, 2470, 2580], radius=8, fill="#FFFFFF", outline="#E2E8F0", width=2)
    draw.text((2110, 1940), "LED VERMELHO", fill="#DC2626", font=get_font(14, bold=True))
    draw.text((2110, 1960), "Motor Parado / Emerg.", fill="#64748B", font=get_font(12))
    place_image(canvas, MEDIA["led_vermelho"], [2130, 2010, 2430, 2350])
    led_r_x1 = (2200, 2480)
    led_r_x2 = (2360, 2480)
    draw_screw(draw, led_r_x1[0], led_r_x1[1], 12, "X1 (+)", "Alarme / Parado")
    draw_screw(draw, led_r_x2[0], led_r_x2[1], 12, "X2 (-)", "Ligar no 0V")

    # Tacometer and LED Power Wires
    draw_wire(draw, [tacho_vcc, (880, 2000), (880, 2540), (1340, 2540), led_w_x1], color="#DC2626", width=5)
    draw_wire(draw, [tacho_gnd, (900, 2100), (900, 2560), (1500, 2560), led_w_x2], color="#334155", width=5)
    draw_wire_tag(draw, 980, 2525, "Barramento 24V / 0V Iluminação & Display", bg_color="#0F172A")

    # =========================================================================
    # RIGHT SIDEBAR: ROTEIRO PRÁTICO DE MONTAGEM PASSO A PASSO
    # =========================================================================
    rx = 2600
    draw_rounded_rect(draw, [rx, 155, W - 40, H - 80], radius=12, fill="#0F172A", outline="#1E293B", width=2)
    draw.text((rx + 30, 185), "ROTEIRO PRÁTICO DE MONTAGEM NA BANCADA", fill="#FACC15", font=get_font(22, bold=True))
    draw.text((rx + 30, 220), "Siga rigorosamente esta ordem física para montar sem dúvidas:", fill="#94A3B8", font=get_font(14))

    # Box 1: Ordem de Montagem Força
    draw_rounded_rect(draw, [rx + 25, 260, W - 65, 780], radius=8, fill="#1E293B", outline="#DC2626", width=2)
    draw.text((rx + 45, 275), "PASSO 1: FIAÇÃO DE FORÇA 220V (ALTA TENSÃO)", fill="#F87171", font=get_font(16, bold=True))
    txt_step1 = """1. Fixe no trilho DIN: Disjuntor Steck, Contator Steck e a Fonte Colmeia.
2. Entrada: Ligue os fios da tomada 220V no topo do Disjuntor:
   • Fase 1 no borne 1
   • Fase 2 (ou Neutro) no borne 3
   • Terra no parafuso de carcaça PE
3. Saída do Disjuntor para o Contator e Fonte:
   • Borne 2 do disjuntor -> Borne 1L1 do contator E parafuso L da fonte.
   • Borne 4 do disjuntor -> Borne 3L2 do contator E parafuso N da fonte.
4. Saída do Contator para o Dimmer:
   • Borne 2T1 do contator -> Fio L do plugue macho do Dimmer.
   • Borne 4T2 do contator -> Fio N do plugue macho do Dimmer.
5. Saída do Dimmer para o Motor:
   • Fio 1 da tomada fêmea do Dimmer -> Fio Comum do motor.
   • Fio 2 da tomada fêmea do Dimmer -> Fio de Marcha do motor.
   • O capacitor (12-15uF) fica entre o Fio Auxiliar e o Fio de Marcha.
6. Aterramento: Conecte o fio Terra na carcaça do motor e no PE da fonte."""
    yy = 310
    for l in txt_step1.split("\n"):
        draw.text((rx + 45, yy), l, fill="#F1F5F9", font=get_font(13))
        yy += 27

    # Box 2: Ordem de Montagem Comando
    draw_rounded_rect(draw, [rx + 25, 805, W - 65, 1420], radius=8, fill="#1E293B", outline="#A855F7", width=2)
    draw.text((rx + 45, 820), "PASSO 2: CADEIA DE SEGURANÇA EM SÉRIE (24V DC)", fill="#C084FC", font=get_font(16, bold=True))
    txt_step2 = """A segurança NR-12 é um único laço contínuo em série (como um colar):
1. Saia do parafuso +V (+24V) da Fonte Colmeia -> Entre no borne 1 da Emergência.
2. Saia do borne 2 da Emergência -> Entre no terminal COM do microswitch.
3. Saia do terminal NC do microswitch -> Entre no borne C (11) do botão vermelho.
4. Saia do borne NC (12) do botão vermelho -> Entre no borne C (11) do botão verde.
5. NO MESMO BORNE C (11) DO BOTÃO VERDE, aperte um fio que vai para o borne 13
   (contato NA) do contator Steck. (Esse é o nó de partida do selo).
6. Saia do borne NO (14) do botão verde -> Ligue direto no borne A1 da bobina
   do contator.
7. NO MESMO BORNE A1 DO CONTATOR, aperte um fio que vai para o borne 14
   (contato NA) do contator Steck. (Esse fecha o selo de retenção!).
8. Borne A2 da bobina do contator: Ligue direto no parafuso -V (0V) da Fonte.
TESTE DO SELO: Ao apertar o botão verde, o contator atraca e fica retido.
Ao abrir a tampa ou bater na emergência, o contator desliga imediatamente!"""
    yy = 855
    for l in txt_step2.split("\n"):
        draw.text((rx + 45, yy), l, fill="#F1F5F9", font=get_font(13))
        yy += 27

    # Box 3: Tacômetro e Sinaleiros
    draw_rounded_rect(draw, [rx + 25, 1445, W - 65, 2040], radius=8, fill="#1E293B", outline="#38BDF8", width=2)
    draw.text((rx + 45, 1460), "PASSO 3: TACÔMETRO DIGITAL & SINALIZAÇÃO LED", fill="#38BDF8", font=get_font(16, bold=True))
    txt_step3 = """1. Alimentação do Display CF5135B:
   • Pino VCC (+) -> Ligue no parafuso +V da fonte (+24V).
   • Pino GND (-) -> Ligue no parafuso -V da fonte (0V).
2. Sensor Hall M12 NJK-5002C:
   • Fio Marrom -> Ligue no terminal MARROM (+V) do tacômetro.
   • Fio Preto  -> Ligue no terminal PRETO (Sinal NPN) do tacômetro.
   • Fio Azul   -> Ligue no terminal AZUL (GND / 0V) do tacômetro.
3. Fixação do Ímã: Cole o ímã de neodímio na polia ou no eixo do motor.
   Ajuste o sensor a uma distância de 1 mm a 5 mm do ímã.
4. Sinaleiros LED de Painel (Opcionais):
   • LED Branco (Painel ON): Ligue X1 no +24V e X2 no 0V.
   • LED Verde (Motor ON): Ligue X1 no A1 do contator e X2 no 0V.
   • LED Vermelho (Alarme): Ligue no contato auxiliar NF ou emergência."""
    yy = 1495
    for l in txt_step3.split("\n"):
        draw.text((rx + 45, yy), l, fill="#F1F5F9", font=get_font(13))
        yy += 27

    # Box 4: Dicas Finais
    draw_rounded_rect(draw, [rx + 25, 2065, W - 65, 2670], radius=8, fill="#1E293B", outline="#EAB308", width=2)
    draw.text((rx + 45, 2080), "DICAS DE OURO PARA NÃO ERRAR NA BANCADA", fill="#FDE047", font=get_font(16, bold=True))
    txt_step4 = """• Tensão da Bobina do Contator Steck SC:
  Verifique a lateral do seu contator. Se for bobina 24V CC, use a fiação acima.
  Se for bobina 220V AC, a cadeia dos botões é alimentada direto pela Fase 220V,
  e a fonte colmeia servirá exclusivamente para o Tacômetro Digital!
• Terminais dos Botões Sibratec XB2:
  O botão vermelho usa C (11) e NC (12) - contato que abre ao apertar.
  O botão verde usa C (11) e NO (14) - contato que fecha ao apertar.
• Supressor de Bobina: Para os botões manuais mecânicos, não precisa de
  supressor nem diodo agora. Pode montar sem medo!
• Bitola dos Fios:
  Use fio 1,5 mm² ou 2,5 mm² para o circuito de Força (Rede, Contator, Dimmer).
  Use fio fino 0,5 mm² ou 0,75 mm² para o circuito de comando 24V e botões."""
    yy = 2115
    for l in txt_step4.split("\n"):
        draw.text((rx + 45, yy), l, fill="#F1F5F9", font=get_font(13))
        yy += 27

    # Save image
    canvas.save(OUTPUT_PNG)
    print(f"Diagram v2 saved to {OUTPUT_PNG}")

    try:
        import shutil
        shutil.copyfile(OUTPUT_PNG, ARTIFACT_PNG)
        print(f"Copied to artifact at {ARTIFACT_PNG}")
    except Exception as e:
        print("Error copying to artifact:", e)

if __name__ == "__main__":
    main()
