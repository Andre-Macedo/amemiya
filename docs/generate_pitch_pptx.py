import os
import sys
import pptx
from pptx.util import Inches, Pt
from pptx.enum.text import PP_ALIGN, MSO_ANCHOR
from pptx.dml.color import RGBColor
from pptx.enum.shapes import MSO_SHAPE

# Paths
LOGO_PATH = r"C:\Users\andrl\OneDrive - SENAC - SP\Senac\Eng. Computação\SENAC 5º Semestre\Projeto\senac_logo.png"
OUTPUT_PATH_1 = r"C:\Users\andrl\OneDrive - SENAC - SP\Senac\Eng. Computação\SENAC 5º Semestre\Projeto\Pre_Banca_Pitch_Amemiya_IoT.pptx"
OUTPUT_PATH_2 = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\docs\Pre_Banca_Pitch_Amemiya_IoT.pptx"
BANCADA_GIF_PATH = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\docs\bancada_nova_rotacao_30_45.gif"
CARRIER_PCB_PATH = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\docs\carrier_pcb_3d_isometric.png"
PROBE_PCB_PATH = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\docs\probe_tower_3d_isometric.png"
GATEWAY_PCB_PATH = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\docs\gateway_pcb_3d_isometric.png"

# Color Palette (Senac & Industrial Engineering)
COLOR_NAVY_DARK = RGBColor(15, 34, 64)       # #0F2240 (Fundo escuro capa / cabeçalhos tabela)
COLOR_SENAC_BLUE = RGBColor(0, 74, 143)      # #004A8F (Azul oficial Senac)
COLOR_SENAC_ORANGE = RGBColor(243, 112, 33)  # #F37021 (Laranja oficial Senac)
COLOR_BG_LIGHT = RGBColor(248, 250, 252)     # #F8FAFC (Fundo claro dos slides)
COLOR_CARD_BG = RGBColor(255, 255, 255)      # #FFFFFF (Cartões brancos)
COLOR_CARD_BORDER = RGBColor(226, 232, 240)  # #E2E8F0 (Borda suave)
COLOR_TEXT_MAIN = RGBColor(30, 41, 59)       # #1E293B (Texto principal)
COLOR_TEXT_MUTED = RGBColor(100, 116, 139)   # #64748B (Texto secundário)
COLOR_TEXT_WHITE = RGBColor(255, 255, 255)   # #FFFFFF
COLOR_GREEN_KPI = RGBColor(16, 185, 129)     # #10B981 (Verde sucesso)
COLOR_RED_ALERT = RGBColor(239, 68, 68)      # #EF4444 (Vermelho gargalo)
COLOR_ROW_ALT = RGBColor(248, 250, 252)      # #F8FAFC (Linha alternada tabela)

prs = pptx.Presentation()
prs.slide_width = Inches(13.333)
prs.slide_height = Inches(7.5)
blank_layout = prs.slide_layouts[6]

def create_shape(slide, shape_type, left, top, width, height, fill_color=None, border_color=None, border_width=1):
    shape = slide.shapes.add_shape(shape_type, left, top, width, height)
    if fill_color:
        shape.fill.solid()
        shape.fill.fore_color.rgb = fill_color
    else:
        shape.fill.background()
    if border_color:
        shape.line.color.rgb = border_color
        shape.line.width = Pt(border_width)
    else:
        shape.line.fill.background()
    return shape

def add_header(slide, title_text, subtitle_text, slide_num):
    # Header background band
    create_shape(slide, MSO_SHAPE.RECTANGLE, Inches(0), Inches(0), Inches(13.333), Inches(1.15), fill_color=COLOR_CARD_BG, border_color=None)
    # Accent color line
    create_shape(slide, MSO_SHAPE.RECTANGLE, Inches(0), Inches(1.13), Inches(13.333), Inches(0.04), fill_color=COLOR_SENAC_BLUE, border_color=None)
    create_shape(slide, MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(1.13), Inches(2.5), Inches(0.04), fill_color=COLOR_SENAC_ORANGE, border_color=None)

    # Title & Subtitle text box
    tb = slide.shapes.add_textbox(Inches(0.8), Inches(0.12), Inches(10.2), Inches(0.95))
    tf = tb.text_frame
    tf.word_wrap = True
    tf.margin_left = tf.margin_top = tf.margin_right = tf.margin_bottom = 0

    p1 = tf.paragraphs[0]
    p1.text = title_text.upper()
    p1.font.name = "Segoe UI"
    p1.font.size = Pt(19)
    p1.font.bold = True
    p1.font.color.rgb = COLOR_SENAC_BLUE

    p2 = tf.add_paragraph()
    p2.space_before = Pt(2)
    p2.text = subtitle_text
    p2.font.name = "Segoe UI"
    p2.font.size = Pt(10.5)
    p2.font.color.rgb = COLOR_TEXT_MUTED

    # Logo in header - carefully dimensioned with proper clearance (height 0.72", stopping well before line at 1.13")
    if os.path.exists(LOGO_PATH):
        slide.shapes.add_picture(LOGO_PATH, Inches(11.45), Inches(0.20), height=Inches(0.72))

    # Footer
    add_footer(slide, slide_num)

def add_footer(slide, slide_num):
    # Left context text
    ft_box = slide.shapes.add_textbox(Inches(0.8), Inches(7.12), Inches(10.0), Inches(0.30))
    ft_tf = ft_box.text_frame
    ft_tf.margin_left = ft_tf.margin_top = ft_tf.margin_right = ft_tf.margin_bottom = 0
    p = ft_tf.paragraphs[0]
    p.text = "Engenharia de Computação — SENAC São Paulo (5º Semestre)  |  Projeto Integrador: Metalúrgica Amemiya"
    p.font.name = "Segoe UI"
    p.font.size = Pt(9.5)
    p.font.color.rgb = COLOR_TEXT_MUTED

    # Single right-aligned slide number box (fixes duplicate text bug)
    num_box = slide.shapes.add_textbox(Inches(11.2), Inches(7.12), Inches(1.333), Inches(0.30))
    num_tf = num_box.text_frame
    num_tf.margin_left = num_tf.margin_top = num_tf.margin_right = num_tf.margin_bottom = 0
    np = num_tf.paragraphs[0]
    np.text = f"Slide {slide_num:02d} / 05"
    np.alignment = PP_ALIGN.RIGHT
    np.font.name = "Segoe UI"
    np.font.size = Pt(9.5)
    np.font.bold = True
    np.font.color.rgb = COLOR_SENAC_BLUE

def set_speaker_notes(slide, notes_text):
    notes = slide.notes_slide.notes_text_frame
    notes.text = notes_text

# ==========================================
# SLIDE 1: CAPA E CONTEXTO DO DESAFIO (REDESIGNED)
# ==========================================
s1 = prs.slides.add_slide(blank_layout)
# Dark elegant background
create_shape(s1, MSO_SHAPE.RECTANGLE, Inches(0), Inches(0), Inches(13.333), Inches(7.5), fill_color=COLOR_NAVY_DARK, border_color=None)
# Top Orange line
create_shape(s1, MSO_SHAPE.RECTANGLE, Inches(0), Inches(0), Inches(13.333), Inches(0.10), fill_color=COLOR_SENAC_ORANGE, border_color=None)

# Logo inside frosted white badge on top right for maximum brand clarity & contrast
if os.path.exists(LOGO_PATH):
    badge_logo = create_shape(s1, MSO_SHAPE.ROUNDED_RECTANGLE, Inches(10.75), Inches(0.45), Inches(1.78), Inches(1.05), fill_color=COLOR_TEXT_WHITE, border_color=None)
    s1.shapes.add_picture(LOGO_PATH, Inches(10.89), Inches(0.57), width=Inches(1.50))

# Category Pill Badge
badge = create_shape(s1, MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(0.55), Inches(6.0), Inches(0.38), fill_color=COLOR_SENAC_BLUE, border_color=None)
badge_tf = badge.text_frame
badge_tf.vertical_anchor = MSO_ANCHOR.MIDDLE
badge_tf.margin_left = badge_tf.margin_right = badge_tf.margin_top = badge_tf.margin_bottom = 0
bp = badge_tf.paragraphs[0]
bp.text = "PROJETO INTEGRADOR V2 • 5º SEMESTRE • ENG. COMPUTAÇÃO"
bp.font.name = "Segoe UI"
bp.font.size = Pt(9.5)
bp.font.bold = True
bp.font.color.rgb = COLOR_TEXT_WHITE
bp.alignment = PP_ALIGN.CENTER

# Main Title & Subtitle (Restricted to width=9.6" so it never collides with the Senac logo!)
tb_title = s1.shapes.add_textbox(Inches(0.8), Inches(1.15), Inches(9.6), Inches(1.45))
tf_title = tb_title.text_frame
tf_title.word_wrap = True
tf_title.margin_left = tf_title.margin_top = tf_title.margin_right = tf_title.margin_bottom = 0

p_t1 = tf_title.paragraphs[0]
p_t1.text = "Plataforma IoT de Manutenção Preditiva"
p_t1.font.name = "Segoe UI"
p_t1.font.size = Pt(27)
p_t1.font.bold = True
p_t1.font.color.rgb = COLOR_TEXT_WHITE

p_t2 = tf_title.add_paragraph()
p_t2.space_before = Pt(4)
p_t2.text = "Monitoramento de Condição em Máquinas Operatrizes — Metalúrgica Amemiya"
p_t2.font.name = "Segoe UI"
p_t2.font.size = Pt(14)
p_t2.font.bold = True
p_t2.font.color.rgb = COLOR_SENAC_ORANGE

# Authors & Guidance Box (Clean frosted bar)
meta_card = create_shape(s1, MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(2.68), Inches(11.733), Inches(0.58), fill_color=RGBColor(24, 49, 89), border_color=RGBColor(45, 78, 130))
mtf = meta_card.text_frame
mtf.word_wrap = True
mtf.vertical_anchor = MSO_ANCHOR.MIDDLE
mtf.margin_left = Inches(0.25)
mtf.margin_right = Inches(0.25)
mp = mtf.paragraphs[0]
mp.text = "Integrantes: "
mp.font.name = "Segoe UI"
mp.font.size = Pt(10)
mp.font.bold = True
mp.font.color.rgb = COLOR_TEXT_WHITE

r_int = mp.add_run()
r_int.text = "André Macedo [e equipe do grupo]       |       "
r_int.font.bold = False
r_int.font.color.rgb = RGBColor(226, 232, 240)

r_prof_lbl = mp.add_run()
r_prof_lbl.text = "Orientação: "
r_prof_lbl.font.bold = True
r_prof_lbl.font.color.rgb = COLOR_TEXT_WHITE

r_prof = mp.add_run()
r_prof.text = "Prof.ª Márcia Freire dos Reis Gorny       |       "
r_prof.font.bold = False
r_prof.font.color.rgb = RGBColor(226, 232, 240)

r_date_lbl = mp.add_run()
r_date_lbl.text = "Etapa: "
r_date_lbl.font.bold = True
r_date_lbl.font.color.rgb = COLOR_TEXT_WHITE

r_date = mp.add_run()
r_date.text = "Pré-Banca de Avaliação (Outubro / 2026)"
r_date.font.bold = False
r_date.font.color.rgb = COLOR_SENAC_ORANGE

# 3 Bottom Executive Cards
card_w = Inches(3.64)
card_h = Inches(3.40)
card_y = Inches(3.48)
gap_c = Inches(0.40)

# Card 1: Contexto
c1 = create_shape(s1, MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), card_y, card_w, card_h, fill_color=RGBColor(24, 49, 89), border_color=RGBColor(45, 78, 130), border_width=1.5)
create_shape(s1, MSO_SHAPE.RECTANGLE, Inches(0.8), card_y, card_w, Inches(0.06), fill_color=COLOR_SENAC_ORANGE, border_color=None)
tf1 = c1.text_frame
tf1.word_wrap = True
tf1.margin_left = tf1.margin_right = Inches(0.20)
tf1.margin_top = Inches(0.18)
p = tf1.paragraphs[0]
p.text = "1. DEMANDA INDUSTRIAL"
p.font.name = "Segoe UI"
p.font.size = Pt(11.5)
p.font.bold = True
p.font.color.rgb = COLOR_SENAC_ORANGE
items1 = [
    ("Empresa Parceira:", "Metalúrgica Amemiya"),
    ("Setor Crítico:", "Usinagem pesada e tornos mecânicos CNC"),
    ("Exigência:", "Tolerâncias centesimais (ISO 2768) onde qualquer folga mecânica condena lotes inteiros de peças usinadas.")
]
for k, v in items1:
    pk = tf1.add_paragraph()
    pk.space_before = Pt(6)
    pk.text = f"• {k} "
    pk.font.bold = True
    pk.font.size = Pt(9.5)
    pk.font.color.rgb = COLOR_TEXT_WHITE
    run = pk.add_run()
    run.text = v
    run.font.bold = False
    run.font.color.rgb = RGBColor(203, 213, 225)

# Card 2: Gargalos
c2 = create_shape(s1, MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8) + card_w + gap_c, card_y, card_w, card_h, fill_color=RGBColor(24, 49, 89), border_color=RGBColor(45, 78, 130), border_width=1.5)
create_shape(s1, MSO_SHAPE.RECTANGLE, Inches(0.8) + card_w + gap_c, card_y, card_w, Inches(0.06), fill_color=COLOR_RED_ALERT, border_color=None)
tf2 = c2.text_frame
tf2.word_wrap = True
tf2.margin_left = tf2.margin_right = Inches(0.20)
tf2.margin_top = Inches(0.18)
p = tf2.paragraphs[0]
p.text = "2. GARGALOS OPERACIONAIS"
p.font.name = "Segoe UI"
p.font.size = Pt(11.5)
p.font.bold = True
p.font.color.rgb = RGBColor(248, 113, 113) # Light red
items2 = [
    ("Paradas Não Planejadas:", "Quebras catastróficas de rolamentos e eixos de fusos"),
    ("Manutenção Reativa:", "Intervenção tardia somente após o colapso físico"),
    ("Impacto Financeiro:", "Quebra de ferramentas caras de corte, perda de prazos de entrega e ociosidade de operadores.")
]
for k, v in items2:
    pk = tf2.add_paragraph()
    pk.space_before = Pt(6)
    pk.text = f"• {k} "
    pk.font.bold = True
    pk.font.size = Pt(9.5)
    pk.font.color.rgb = COLOR_TEXT_WHITE
    run = pk.add_run()
    run.text = v
    run.font.bold = False
    run.font.color.rgb = RGBColor(203, 213, 225)

# Card 3: Proposta
c3 = create_shape(s1, MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8) + 2 * (card_w + gap_c), card_y, card_w, card_h, fill_color=RGBColor(24, 49, 89), border_color=RGBColor(45, 78, 130), border_width=1.5)
create_shape(s1, MSO_SHAPE.RECTANGLE, Inches(0.8) + 2 * (card_w + gap_c), card_y, card_w, Inches(0.06), fill_color=COLOR_GREEN_KPI, border_color=None)
tf3 = c3.text_frame
tf3.word_wrap = True
tf3.margin_left = tf3.margin_right = Inches(0.20)
tf3.margin_top = Inches(0.18)
p = tf3.paragraphs[0]
p.text = "3. PROPOSTA DE ENGENHARIA (V2)"
p.font.name = "Segoe UI"
p.font.size = Pt(11.5)
p.font.bold = True
p.font.color.rgb = COLOR_GREEN_KPI
items3 = [
    ("Sensoriamento Multimodal:", "Vibração Triaxial + Tacômetro + Ruído + Temperatura"),
    ("Validação em Bancada V2:", "Injeção física de desalinhamento (push-pull) e mancais (NR-12)"),
    ("Diagnóstico Preditivo V2:", "IA XGBoost Multiclasse (4 falhas) + Alertas Push no App Mobile.")
]
for k, v in items3:
    pk = tf3.add_paragraph()
    pk.space_before = Pt(6)
    pk.text = f"• {k} "
    pk.font.bold = True
    pk.font.size = Pt(9.5)
    pk.font.color.rgb = COLOR_TEXT_WHITE
    run = pk.add_run()
    run.text = v
    run.font.bold = False
    run.font.color.rgb = RGBColor(203, 213, 225)

set_speaker_notes(s1, """ROTEIRO DE FALA — SLIDE 1 (30 SEGUNDOS):
"Boa tarde a todos os professores e avaliadores da banca. O nosso projeto aborda a demanda real da Metalúrgica Amemiya no controle de uso e integridade do seu maquinário de usinagem pesada. Em tornos CNC e fresadoras industriais, qualquer folga mecânica ou desgaste prematuro de rolamento condena lotes inteiros de peças de alta precisão. Hoje, a empresa convive com quebras catastróficas imprevistas e manutenção reativa, gerando atrasos graves na produção. Apresentamos a evolução da nossa Plataforma IoT de Metrologia e Manutenção Preditiva na versão 2, expandindo o diagnóstico para múltiplos modos de falha e integrando uma bancada didática para homologação prática com segurança."
""")

# ==========================================
# SLIDE 2: DIAGNÓSTICO E GAP ANALYSIS
# ==========================================
s2 = prs.slides.add_slide(blank_layout)
create_shape(s2, MSO_SHAPE.RECTANGLE, Inches(0), Inches(0), Inches(13.333), Inches(7.5), fill_color=COLOR_BG_LIGHT, border_color=None)
add_header(s2, "Diagnóstico e Gap Analysis", "Auditoria Crítica do Protótipo Anterior vs. Nova Evolução Tecnológica", 2)

# Outer card wrapper for table
tbl_wrapper = create_shape(s2, MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(1.35), Inches(11.733), Inches(5.50), fill_color=COLOR_CARD_BG, border_color=COLOR_CARD_BORDER, border_width=1.5)

# Direct comparison table
table_shape = s2.shapes.add_table(6, 4, Inches(0.9), Inches(1.45), Inches(11.533), Inches(5.30))
table = table_shape.table
table.columns[0].width = Inches(2.00)
table.columns[1].width = Inches(2.40)
table.columns[2].width = Inches(3.30)
table.columns[3].width = Inches(3.833)

headers_s2 = ["Dimensão Técnica", "Projeto Anterior (Baseline V1)", "Gargalo Crítico Diagnosticado", "Nova Solução Adotada (Ciclo V2)"]
for col_idx, h_text in enumerate(headers_s2):
    cell = table.cell(0, col_idx)
    cell.fill.solid()
    cell.fill.fore_color.rgb = COLOR_SENAC_BLUE
    cell.vertical_anchor = MSO_ANCHOR.MIDDLE
    p = cell.text_frame.paragraphs[0]
    p.text = h_text
    p.font.name = "Segoe UI"
    p.font.size = Pt(10)
    p.font.bold = True
    p.font.color.rgb = COLOR_TEXT_WHITE

rows_data = [
    ("Escopo de Diagnóstico", "Modelo restrito a 1 falha (apenas desbalanceamento)", "Incapacidade de detectar desalinhamento de eixo, folga ou defeito de mancal", "Fusão Multissensorial: Tacômetro KY-003 + INMP441 + DS18B20 para 4 tipos de falha"),
    ("Bancada de Validação", "Bancada restrita a parafusos excêntricos", "Impossibilidade de simular desalinhamento angular/paralelo e ausência de proteção", "Nova Bancada Modular 40x80/2020: Injeção de desalinhamento por calços, mancais UCP204 e NR-12"),
    ("Integridade de Hardware", "Protoboard e fios jumpers soltos no motor", "Desconexão mecânica contínua por vibração; alto ruído EMI nos sinais", "Placa de Circuito Impresso (PCB dedicada): Planos de terra blindados e fixação sólida"),
    ("Alimentação & Enlace RF", "Cabos de tomada 110/220V + Wi-Fi 2.4 GHz", "Risco de acidentes em eixos girantes e alta atenuação do Wi-Fi na carcaça metálica", "Baterias Li-Ion 18650 internas (Deep Sleep) + Rádio LoRa (915 MHz) imune a EMI"),
    ("Ação da Manutenção", "Verificação manual em dashboard web passivo", "Falta de mobilidade; mecânico não fica parado em frente ao computador", "App Android + Alertas Push: Diagnóstico preditivo entregue no bolso do técnico em < 2s")
]

for row_idx, r_data in enumerate(rows_data, start=1):
    bg_color = COLOR_ROW_ALT if row_idx % 2 == 0 else COLOR_CARD_BG
    for col_idx, text in enumerate(r_data):
        cell = table.cell(row_idx, col_idx)
        cell.fill.solid()
        cell.fill.fore_color.rgb = bg_color
        cell.vertical_anchor = MSO_ANCHOR.MIDDLE
        p = cell.text_frame.paragraphs[0]
        p.text = text
        p.font.name = "Segoe UI"
        p.font.size = Pt(9.5)
        if col_idx == 0:
            p.font.bold = True
            p.font.color.rgb = COLOR_SENAC_BLUE
        elif col_idx == 2:
            p.font.color.rgb = COLOR_RED_ALERT
        elif col_idx == 3:
            p.font.bold = True
            p.font.color.rgb = RGBColor(5, 150, 105) # Green
        else:
            p.font.color.rgb = COLOR_TEXT_MAIN

set_speaker_notes(s2, """ROTEIRO DE FALA — SLIDE 2 (1 MINUTO):
"Ao auditarmos criticamente o projeto do ciclo anterior, identificamos os gargalos reais: nosso modelo de IA era preciso, mas só detectava desbalanceamento de massa, ignorando desalinhamentos de eixo, folgas estruturais e danos em mancais. Nossa bancada antiga só permitia fixar parafusos excêntricos, inviabilizando testes com outras falhas mecânicas. Além disso, o hardware em protoboard sofria com mau contato vibratório, fios de tomada ofereciam riscos perto de eixos girantes e o Wi-Fi sofria atenuação na estrutura dos tornos. Na V2, superamos todos esses pontos: criamos a nova bancada modular com injeção de desalinhamento por calços e mancais intercambiáveis, expandimos o sensoriamento para tacômetro, ruído acústico e temperatura, e projetamos uma PCB industrial com rádio LoRa e alimentação a baterias 18650."
""")

# ==========================================
# SLIDE 3: ARQUITETURA IOT E INDÚSTRIA 4.0 (REDESIGNED)
# ==========================================
s3 = prs.slides.add_slide(blank_layout)
create_shape(s3, MSO_SHAPE.RECTANGLE, Inches(0), Inches(0), Inches(13.333), Inches(7.5), fill_color=COLOR_BG_LIGHT, border_color=None)
add_header(s3, "Arquitetura IoT e Indústria 4.0", "Fluxo de Dados Unificado: Borda Sensorial ➔ LoRa ➔ Ingestão ➔ IA Preditiva ➔ Aplicação", 3)

# Top Pipeline Flow (5 Blocks)
pipe_w = Inches(2.18)
pipe_h = Inches(2.72)
pipe_y = Inches(1.28)
gap = Inches(0.20)

blocks = [
    ("1. SENSING (BORDA)", COLOR_SENAC_BLUE, [
        "• ADXL345: Vibração triaxial",
        "• KY-003: Tacômetro RPM",
        "• INMP441 & DS18B20",
        "➔ ESP32-S3 (DSP / FFT)",
    ]),
    ("2. ENLACE RF (LORA)", RGBColor(30, 64, 175), [
        "• Rádio LoRa (915 MHz)",
        "• Sem cabos no motor",
        "• Alcance 500m+ na oficina",
        "• Imune a ruídos EMI",
        "• Bateria dura 90-120 dias",
        "• Consumo 15µA Deep Sleep"
    ]),
    ("3. GATEWAY & INGESTÃO", RGBColor(14, 116, 144), [
        "• Gateway ESP32 de parede",
        "• Wi-Fi / Ethernet Industrial",
        "• Broker MQTT Mosquitto",
        "➔ QoS 1 + Bridge Laravel",
    ]),
    ("4. BACKEND & IA", RGBColor(67, 56, 202), [
        "• Core Laravel 12 API",
        "• Motor Normativo ISO 20816",
        "• Microsserviço FastAPI",
        "• Modelo XGBoost (4 Falhas)",
        "• Banco MySQL Otimizado",
        "• WebSockets (Laravel Reverb)"
    ]),
    ("5. APRESENTAÇÃO", COLOR_SENAC_ORANGE, [
        "• App Android (React Native)",
        "• IHM Digital de Bolso 3.5\"",
        "• Push Notifications < 2s",
        "• Painel Web Administrativo",
        "• API Pública RESTful v1",
        "• Geração automática de OS"
    ])
]

for idx, (title, color_top, bullets) in enumerate(blocks):
    bx = Inches(0.8) + idx * (pipe_w + gap)
    card = create_shape(s3, MSO_SHAPE.ROUNDED_RECTANGLE, bx, pipe_y, pipe_w, pipe_h, fill_color=COLOR_CARD_BG, border_color=color_top, border_width=1.5)
    
    # Text frame top portion
    tf_top_h = Inches(1.30) if (idx == 0 or idx == 2) else Inches(2.55)
    tb = s3.shapes.add_textbox(bx, pipe_y, pipe_w, tf_top_h)
    tf = tb.text_frame
    tf.word_wrap = True
    tf.margin_left = tf.margin_right = tf.margin_top = Inches(0.10)
    p = tf.paragraphs[0]
    p.text = title
    p.font.name = "Segoe UI"
    p.font.size = Pt(10)
    p.font.bold = True
    p.font.color.rgb = color_top

    for b in bullets:
        pb = tf.add_paragraph()
        pb.space_before = Pt(2)
        pb.text = b
        pb.font.name = "Segoe UI"
        pb.font.size = Pt(8.5)
        pb.font.color.rgb = COLOR_TEXT_MAIN

    # Connecting Flow Arrow Chevrons between cards
    if idx < 4:
        arrow_box = s3.shapes.add_textbox(bx + pipe_w - Inches(0.02), pipe_y + Inches(1.05), gap + Inches(0.04), Inches(0.40))
        atf = arrow_box.text_frame
        atf.margin_left = atf.margin_right = atf.margin_top = atf.margin_bottom = 0
        ap = atf.paragraphs[0]
        ap.text = "➔"
        ap.alignment = PP_ALIGN.CENTER
        ap.font.name = "Segoe UI"
        ap.font.size = Pt(13)
        ap.font.bold = True
        ap.font.color.rgb = COLOR_SENAC_ORANGE

    # Embed custom KiCad PCB 3D renders inside a recessed card container (fixes overlapping bug)
    if idx == 0 and os.path.exists(CARRIER_PCB_PATH):
        sub_card = create_shape(s3, MSO_SHAPE.ROUNDED_RECTANGLE, bx + Inches(0.10), pipe_y + Inches(1.48), Inches(1.98), Inches(1.14), fill_color=COLOR_ROW_ALT, border_color=COLOR_CARD_BORDER, border_width=1)
        s3.shapes.add_picture(CARRIER_PCB_PATH, bx + Inches(0.18), pipe_y + Inches(1.52), width=Inches(1.82), height=Inches(0.85))
        lbl = s3.shapes.add_textbox(bx + Inches(0.10), pipe_y + Inches(2.40), Inches(1.98), Inches(0.20))
        lbl_p = lbl.text_frame.paragraphs[0]
        lbl_p.text = "Nó ESP32-S3 (KiCad)"
        lbl_p.alignment = PP_ALIGN.CENTER
        lbl_p.font.name = "Segoe UI"
        lbl_p.font.size = Pt(7.5)
        lbl_p.font.bold = True
        lbl_p.font.color.rgb = COLOR_SENAC_BLUE
    elif idx == 2 and os.path.exists(GATEWAY_PCB_PATH):
        sub_card = create_shape(s3, MSO_SHAPE.ROUNDED_RECTANGLE, bx + Inches(0.10), pipe_y + Inches(1.48), Inches(1.98), Inches(1.14), fill_color=COLOR_ROW_ALT, border_color=COLOR_CARD_BORDER, border_width=1)
        s3.shapes.add_picture(GATEWAY_PCB_PATH, bx + Inches(0.18), pipe_y + Inches(1.52), width=Inches(1.82), height=Inches(0.85))
        lbl = s3.shapes.add_textbox(bx + Inches(0.10), pipe_y + Inches(2.40), Inches(1.98), Inches(0.20))
        lbl_p = lbl.text_frame.paragraphs[0]
        lbl_p.text = "Gateway LoRa (KiCad)"
        lbl_p.alignment = PP_ALIGN.CENTER
        lbl_p.font.name = "Segoe UI"
        lbl_p.font.size = Pt(7.5)
        lbl_p.font.bold = True
        lbl_p.font.color.rgb = RGBColor(14, 116, 144)

# Bottom Justification Table
t3_top = Inches(4.18)
t3_w = Inches(11.733)
t3_h = Inches(2.65)
table3_shape = s3.shapes.add_table(5, 3, Inches(0.8), t3_top, t3_w, t3_h)
t3 = table3_shape.table
t3.columns[0].width = Inches(2.5)
t3.columns[1].width = Inches(3.2)
t3.columns[2].width = Inches(6.033)

h3 = ["Camada Arquitetural", "Tecnologia Selecionada", "Justificativa Técnica Direta para a Metalúrgica Amemiya"]
for col_idx, h_text in enumerate(h3):
    cell = t3.cell(0, col_idx)
    cell.fill.solid()
    cell.fill.fore_color.rgb = COLOR_NAVY_DARK
    cell.vertical_anchor = MSO_ANCHOR.MIDDLE
    p = cell.text_frame.paragraphs[0]
    p.text = h_text
    p.font.name = "Segoe UI"
    p.font.size = Pt(10)
    p.font.bold = True
    p.font.color.rgb = COLOR_TEXT_WHITE

rows_arch = [
    ("Fusão Sensorial de Borda", "ADXL345 + KY-003 + INMP441 + DS18B20", "Cruza vibração, velocidade angular, ruído e temperatura para separar com precisão desbalanceamento mecânico de defeitos internos de rolamento."),
    ("Transmissão Nó ➔ Gateway", "Rádio LoRa Sub-GHz (915 MHz)", "Imune às interferências eletromagnéticas de motores/inversores e com alta penetração em estruturas metálicas, sem disputar banda com o Wi-Fi corporativo."),
    ("Ingestão de Mensageria", "Broker MQTT + Buffer em Memória Redis", "Protocolo Pub/Sub ultraleve com QoS 1; o Redis absorve picos instantâneos de telemetria sem engargalar as conexões do banco MySQL."),
    ("Decisão Preditiva & Ação", "XGBoost + App Mobile + API Pública", "A IA classifica a falha e dispara notificação Push para o mecânico em campo, enquanto a API Pública permite abrir Ordem de Serviço no ERP.")
]

for row_idx, r_data in enumerate(rows_arch, start=1):
    bg_color = COLOR_ROW_ALT if row_idx % 2 == 0 else COLOR_CARD_BG
    for col_idx, text in enumerate(r_data):
        cell = t3.cell(row_idx, col_idx)
        cell.fill.solid()
        cell.fill.fore_color.rgb = bg_color
        cell.vertical_anchor = MSO_ANCHOR.MIDDLE
        p = cell.text_frame.paragraphs[0]
        p.text = text
        p.font.name = "Segoe UI"
        p.font.size = Pt(9.5)
        if col_idx == 0:
            p.font.bold = True
            p.font.color.rgb = COLOR_SENAC_BLUE
        elif col_idx == 1:
            p.font.bold = True
            p.font.color.rgb = COLOR_TEXT_MAIN
        else:
            p.font.color.rgb = COLOR_TEXT_MAIN

set_speaker_notes(s3, """ROTEIRO DE FALA — SLIDE 3 (1 MINUTO E 30 SEGUNDOS):
"Este é o diagrama integrado da nossa arquitetura. No motor da máquina, a placa ESP32-S3 executa a amostragem síncrona do acelerômetro, tacômetro, microfone e temperatura. Ela calcula a FFT de 1024 pontos e os descritores no tempo e envia o pacote via rádio LoRa para o Gateway fixado na parede da oficina. O Gateway repassa o payload via MQTT para o broker Mosquitto. No servidor, um daemon em Laravel consome as mensagens através de um buffer em memória no Redis, avalia os limites da norma internacional ISO 20816 e consulta o modelo XGBoost em Python. Se uma anomalia for confirmada, o Laravel emite instantaneamente um alerta Push para o aplicativo Android do mecânico de manutenção e disponibiliza o evento através de uma API Pública RESTful, permitindo que o ERP da metalúrgica abra automaticamente uma Ordem de Serviço e reserve peças de reposição no almoxarifado."
""")

# ==========================================
# SLIDE 4: VIABILIDADE TÉCNICA E KPIS (REDESIGNED)
# ==========================================
s4 = prs.slides.add_slide(blank_layout)
create_shape(s4, MSO_SHAPE.RECTANGLE, Inches(0), Inches(0), Inches(13.333), Inches(7.5), fill_color=COLOR_BG_LIGHT, border_color=None)
add_header(s4, "Viabilidade Técnica e Metas Operacionais", "Projeção de Indicadores Antes x Depois e Matriz de Mitigação de Riscos de Planta", 4)

# Left Column: KPIs Table
kpi_w = Inches(6.15)
kpi_h = Inches(5.50)
table4_shape = s4.shapes.add_table(6, 3, Inches(0.8), Inches(1.35), kpi_w, kpi_h)
t4 = table4_shape.table
t4.columns[0].width = Inches(2.45)
t4.columns[1].width = Inches(1.8)
t4.columns[2].width = Inches(1.9)

h4 = ["Critério de Engenharia / Meta", "Projeto Anterior (V1)", "Nova Evolução (V2)"]
for col_idx, h_text in enumerate(h4):
    cell = t4.cell(0, col_idx)
    cell.fill.solid()
    cell.fill.fore_color.rgb = COLOR_SENAC_BLUE
    cell.vertical_anchor = MSO_ANCHOR.MIDDLE
    p = cell.text_frame.paragraphs[0]
    p.text = h_text
    p.font.name = "Segoe UI"
    p.font.size = Pt(10)
    p.font.bold = True
    p.font.color.rgb = COLOR_TEXT_WHITE

rows_kpi = [
    ("Espectro de Falhas Cobertas", "1 modo (apenas desbalanceamento)", "4 modos (desbalanceamento, desalinhamento, folga e rolamento)"),
    ("Fusão Sensorial de Borda", "Mono-sensorial (apenas acelerômetro)", "Multimodal (vibração + tacômetro + microfone + temperatura)"),
    ("Validação Prática na Bancada", "Injeção restrita a peso excêntrico", "Injeção física de desalinhamento (push-pull), folga e mancais + NR-12"),
    ("Classificação do Modelo IA", "Binário preciso (~96% em desbalanceamento)", "Multiclasse com acurácia projetada > 93% em 4 classes de falha"),
    ("Autonomia & Enlace de Dados", "Cabo contínuo + Wi-Fi fabril instável", "Bateria Li-Ion (90-120 dias) + Rádio LoRa 915 MHz imune a EMI")
]

for row_idx, r_data in enumerate(rows_kpi, start=1):
    bg_color = COLOR_ROW_ALT if row_idx % 2 == 0 else COLOR_CARD_BG
    for col_idx, text in enumerate(r_data):
        cell = t4.cell(row_idx, col_idx)
        cell.fill.solid()
        cell.fill.fore_color.rgb = bg_color
        cell.vertical_anchor = MSO_ANCHOR.MIDDLE
        p = cell.text_frame.paragraphs[0]
        p.text = text
        p.font.name = "Segoe UI"
        p.font.size = Pt(9.5)
        if col_idx == 0:
            p.font.bold = True
            p.font.color.rgb = COLOR_TEXT_MAIN
        elif col_idx == 1:
            p.font.color.rgb = COLOR_TEXT_MUTED
        else:
            p.font.bold = True
            p.font.color.rgb = RGBColor(5, 150, 105)

# Right Column: 3D Bancada Animated GIF + Risk Mitigation Matrix
r_left = Inches(7.3)
r_w = Inches(5.233)

# Card 1 (Top): Bancada Didática Portátil 3D GIF
b_card_h = Inches(3.40)
bancada_card = create_shape(s4, MSO_SHAPE.ROUNDED_RECTANGLE, r_left, Inches(1.35), r_w, b_card_h, fill_color=COLOR_CARD_BG, border_color=COLOR_CARD_BORDER)
create_shape(s4, MSO_SHAPE.RECTANGLE, r_left, Inches(1.35), r_w, Inches(0.06), fill_color=COLOR_SENAC_BLUE, border_color=None)

tb_b = s4.shapes.add_textbox(r_left + Inches(0.18), Inches(1.43), r_w - Inches(0.36), Inches(0.50))
tf_b = tb_b.text_frame
tf_b.margin_left = tf_b.margin_top = tf_b.margin_right = tf_b.margin_bottom = 0
p_b = tf_b.paragraphs[0]
p_b.text = "PROTÓTIPO DE VALIDAÇÃO: BANCADA DIDÁTICA (NR-12)"
p_b.font.name = "Segoe UI"
p_b.font.size = Pt(9.5)
p_b.font.bold = True
p_b.font.color.rgb = COLOR_SENAC_BLUE

p_sub = tf_b.add_paragraph()
p_sub.text = "Perfis 40x80/2020 • Cúpula Articulada NR-12 • Sondas Verticais • Motor Push-Pull"
p_sub.font.name = "Segoe UI"
p_sub.font.size = Pt(8)
p_sub.font.color.rgb = COLOR_TEXT_MUTED

# Embed 3D Rotating GIF cleanly below the title text
gif_w = Inches(4.65)
gif_h = Inches(2.61)
gif_x = r_left + (r_w - gif_w) / 2
gif_y = Inches(2.05)
if os.path.exists(BANCADA_GIF_PATH):
    s4.shapes.add_picture(BANCADA_GIF_PATH, gif_x, gif_y, width=gif_w, height=gif_h)

# Card 2 (Bottom): Plant Risks & Technical Viability Matrix
r_bottom_y = Inches(4.90)
r_bottom_h = Inches(1.95)
rcard = create_shape(s4, MSO_SHAPE.ROUNDED_RECTANGLE, r_left, r_bottom_y, r_w, r_bottom_h, fill_color=COLOR_CARD_BG, border_color=COLOR_CARD_BORDER)
create_shape(s4, MSO_SHAPE.RECTANGLE, r_left, r_bottom_y, Inches(0.1), r_bottom_h, fill_color=COLOR_SENAC_ORANGE, border_color=None)

# Embed Probe Tower PCB on right of Risk card inside its own recessed badge
if os.path.exists(PROBE_PCB_PATH):
    probe_badge = create_shape(s4, MSO_SHAPE.ROUNDED_RECTANGLE, r_left + Inches(3.68), r_bottom_y + Inches(0.12), Inches(1.42), Inches(1.70), fill_color=COLOR_ROW_ALT, border_color=COLOR_CARD_BORDER, border_width=1)
    s4.shapes.add_picture(PROBE_PCB_PATH, r_left + Inches(3.74), r_bottom_y + Inches(0.16), width=Inches(1.30), height=Inches(1.35))
    p_lbl = s4.shapes.add_textbox(r_left + Inches(3.68), r_bottom_y + Inches(1.53), Inches(1.42), Inches(0.24))
    p_lp = p_lbl.text_frame.paragraphs[0]
    p_lp.text = "Sonda Mancal (KiCad)"
    p_lp.alignment = PP_ALIGN.CENTER
    p_lp.font.name = "Segoe UI"
    p_lp.font.size = Pt(7.5)
    p_lp.font.bold = True
    p_lp.font.color.rgb = COLOR_SENAC_ORANGE

rtf = rcard.text_frame
rtf.word_wrap = True
rtf.margin_left = Inches(0.20)
rtf.margin_right = Inches(1.65) # Leave room for Probe PCB
rtf.margin_top = Inches(0.10)
rp = rtf.paragraphs[0]
rp.text = "MATRIZ DE RISCOS & VIABILIDADE TÉCNICA"
rp.font.name = "Segoe UI"
rp.font.size = Pt(9.5)
rp.font.bold = True
rp.font.color.rgb = COLOR_SENAC_ORANGE

p1 = rtf.add_paragraph()
p1.space_before = Pt(3)
p1.text = "• Ambiente (Óleo/Cavacos): "
p1.font.bold = True
p1.font.size = Pt(8)
p1.font.color.rgb = COLOR_TEXT_MAIN
run1 = p1.add_run()
run1.text = "Gabinete magnético IP65 e sonda direta de mancal."
run1.font.bold = False

p2 = rtf.add_paragraph()
p2.space_before = Pt(2)
p2.text = "• Ruído Eletromagnético: "
p2.font.bold = True
p2.font.size = Pt(8)
p2.font.color.rgb = COLOR_TEXT_MAIN
run2 = p2.add_run()
run2.text = "Rádio LoRa 915 MHz imune a inversores + PCB aterrada."
run2.font.bold = False

p3 = rtf.add_paragraph()
p3.space_before = Pt(2)
p3.text = "• Validação Prática: "
p3.font.bold = True
p3.font.size = Pt(8)
p3.font.color.rgb = COLOR_TEXT_MAIN
run3 = p3.add_run()
run3.text = "Bancada injeta desalinhamento e folgas para validar IA."
run3.font.bold = False

set_speaker_notes(s4, """ROTEIRO DE FALA — SLIDE 4 (1 MINUTO):
"A viabilidade da nossa proposta na V2 se apoia na expansão do espectro de diagnóstico e na nova bancada de ensaios: no projeto anterior, nosso modelo de IA já era bastante preciso (~96%), porém estava restrito exclusivamente ao desbalanceamento de massa. Com a nova fusão de sensores — unindo aceleração triaxial, tacômetro síncrono, microfone acústico e temperatura —, passamos a cobrir 4 modalidades críticas de falha: desbalanceamento, desalinhamento angular e paralelo, folgas mecânicas e defeitos em mancais. Para validar isso perante a banca sem riscos operacionais na fábrica, projetamos a nova bancada didática em perfis 40x80 e 2020 que vocês veem em rotação 3D na tela. Com mancais industriais UCP204, placas de desalinhamento ajustadas por calços e parafusos push-pull, e enclausuramento NR-12, conseguimos injetar falhas mecânicas reais e comprovar a resposta da IA e dos alertas ao vivo perante os professores."
""")

# ==========================================
# SLIDE 5: CRONOGRAMA DO BACK-END (REDESIGNED)
# ==========================================
s5 = prs.slides.add_slide(blank_layout)
create_shape(s5, MSO_SHAPE.RECTANGLE, Inches(0), Inches(0), Inches(13.333), Inches(7.5), fill_color=COLOR_BG_LIGHT, border_color=None)
add_header(s5, "Evolução do Back-end V2: Cronograma", "Metas de Desenvolvimento das Próximas 2 Semanas (APIs, Banco e IA)", 5)

# Banner: Arquitetura Base Consolidada
top_banner = create_shape(s5, MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(1.32), Inches(11.733), Inches(0.55), fill_color=RGBColor(240, 253, 244), border_color=RGBColor(187, 247, 208), border_width=1.5)
b_tf = top_banner.text_frame
b_tf.word_wrap = True
b_tf.vertical_anchor = MSO_ANCHOR.MIDDLE
b_tf.margin_left = Inches(0.20)
b_tf.margin_right = Inches(0.20)
bp = b_tf.paragraphs[0]
bp.text = "✓ ARQUITETURA BASE JÁ CONSOLIDADA & OPERACIONAL: "
bp.font.name = "Segoe UI"
bp.font.size = Pt(9.5)
bp.font.bold = True
bp.font.color.rgb = RGBColor(22, 101, 52) # Dark green
brun = bp.add_run()
brun.text = "MySQL Relacional, Broker Mosquitto MQTT, Cache/Filas Redis e WebSockets Reverb já implementados."
brun.font.bold = False
brun.font.color.rgb = RGBColor(21, 128, 61)

# Two Sprint Columns
col_w = Inches(5.72)
col_h = Inches(4.30)
col_y = Inches(2.00)

# Card Semana 1
c_s1 = create_shape(s5, MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), col_y, col_w, col_h, fill_color=COLOR_CARD_BG, border_color=COLOR_CARD_BORDER, border_width=1.5)
create_shape(s5, MSO_SHAPE.RECTANGLE, Inches(0.8), col_y, col_w, Inches(0.06), fill_color=COLOR_SENAC_BLUE, border_color=None)

t_s1 = s5.shapes.add_textbox(Inches(1.00), col_y + Inches(0.14), col_w - Inches(0.4), Inches(0.40))
t_tf1 = t_s1.text_frame
t_tf1.word_wrap = True
t_tf1.margin_left = t_tf1.margin_top = t_tf1.margin_right = t_tf1.margin_bottom = 0
t_p1 = t_tf1.paragraphs[0]
t_p1.text = "SEMANA 1 (DIAS 1 A 7): DTOs MULTISSENSORIAIS & DATASET"
t_p1.font.name = "Segoe UI"
t_p1.font.size = Pt(11)
t_p1.font.bold = True
t_p1.font.color.rgb = COLOR_SENAC_BLUE

create_shape(s5, MSO_SHAPE.RECTANGLE, Inches(1.00), col_y + Inches(0.56), col_w - Inches(0.4), Inches(0.02), fill_color=COLOR_CARD_BORDER, border_color=None)

tb_c1 = s5.shapes.add_textbox(Inches(1.00), col_y + Inches(0.70), col_w - Inches(0.4), Inches(3.45))
tf_c1 = tb_c1.text_frame
tf_c1.word_wrap = True
tf_c1.margin_left = tf_c1.margin_right = tf_c1.margin_top = tf_c1.margin_bottom = 0

s1_tasks = [
    ("• Sprint 1.1: Adaptação de Esquema & DTOs", "Expansão do pipeline de telemetria no Laravel para persistir e processar os novos campos (temp_celsius, battery_voltage, features acústicas e tacômetro)."),
    ("• Sprint 1.2: Campanha de Coleta na Nova Bancada", "Injeção física de falhas na nova bancada (calços de desalinhamento, folga e mancais) para gravação do dataset multiclasse."),
    ("• Sprint 1.3: Retreinamento do Modelo XGBoost", "Treinamento supervisionado no microsserviço Python com as features temporais e espectrais extraídas para 4 classes mecânicas.")
]
for idx_t, (stitle, sdesc) in enumerate(s1_tasks):
    pt = tf_c1.paragraphs[0] if idx_t == 0 else tf_c1.add_paragraph()
    pt.space_before = Pt(8) if idx_t > 0 else Pt(0)
    pt.text = stitle
    pt.font.name = "Segoe UI"
    pt.font.size = Pt(9.5)
    pt.font.bold = True
    pt.font.color.rgb = COLOR_SENAC_BLUE
    pd = tf_c1.add_paragraph()
    pd.space_before = Pt(1)
    pd.text = sdesc
    pd.font.name = "Segoe UI"
    pd.font.size = Pt(8.5)
    pd.font.color.rgb = COLOR_TEXT_MAIN

# Card Semana 2
c_s2 = create_shape(s5, MSO_SHAPE.ROUNDED_RECTANGLE, Inches(6.813), col_y, col_w, col_h, fill_color=COLOR_CARD_BG, border_color=COLOR_CARD_BORDER, border_width=1.5)
create_shape(s5, MSO_SHAPE.RECTANGLE, Inches(6.813), col_y, col_w, Inches(0.06), fill_color=COLOR_SENAC_ORANGE, border_color=None)

t_s2 = s5.shapes.add_textbox(Inches(7.013), col_y + Inches(0.14), col_w - Inches(0.4), Inches(0.40))
t_tf2 = t_s2.text_frame
t_tf2.word_wrap = True
t_tf2.margin_left = t_tf2.margin_top = t_tf2.margin_right = t_tf2.margin_bottom = 0
t_p2 = t_tf2.paragraphs[0]
t_p2.text = "SEMANA 2 (DIAS 8 A 14): REGRAS MULTI-FALHA & APIS"
t_p2.font.name = "Segoe UI"
t_p2.font.size = Pt(11)
t_p2.font.bold = True
t_p2.font.color.rgb = COLOR_SENAC_ORANGE

create_shape(s5, MSO_SHAPE.RECTANGLE, Inches(7.013), col_y + Inches(0.56), col_w - Inches(0.4), Inches(0.02), fill_color=COLOR_CARD_BORDER, border_color=None)

tb_c2 = s5.shapes.add_textbox(Inches(7.013), col_y + Inches(0.70), col_w - Inches(0.4), Inches(3.45))
tf_c2 = tb_c2.text_frame
tf_c2.word_wrap = True
tf_c2.margin_left = tf_c2.margin_right = tf_c2.margin_top = tf_c2.margin_bottom = 0

s2_tasks = [
    ("• Sprint 2.1: Expansão do Motor ISO 20816", "Parametrização dos limites de alarme de vibração global cruzados com curva de aquecimento do mancal e energia acústica."),
    ("• Sprint 2.2: Contratos & Endpoints da API RESTful v1", "Ajuste das rotas autenticadas (Sanctum) para disponibilizar diagnósticos multiclasse, FFTs e telemetria térmica para ERP e Mobile."),
    ("• Sprint 2.3: Ensaio Integrado de Ponta a Ponta", "Validação completa: Nó físico na bancada -> Gateway LoRa -> Mosquitto -> Laravel -> IA FastAPI -> Reverb -> API.")
]
for idx_t, (stitle, sdesc) in enumerate(s2_tasks):
    pt = tf_c2.paragraphs[0] if idx_t == 0 else tf_c2.add_paragraph()
    pt.space_before = Pt(8) if idx_t > 0 else Pt(0)
    pt.text = stitle
    pt.font.name = "Segoe UI"
    pt.font.size = Pt(9.5)
    pt.font.bold = True
    pt.font.color.rgb = COLOR_SENAC_ORANGE
    pd = tf_c2.add_paragraph()
    pd.space_before = Pt(1)
    pd.text = sdesc
    pd.font.name = "Segoe UI"
    pd.font.size = Pt(8.5)
    pd.font.color.rgb = COLOR_TEXT_MAIN

# Bottom Acceptance Criteria Card
crit_box = create_shape(s5, MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(6.42), Inches(11.733), Inches(0.55), fill_color=COLOR_NAVY_DARK, border_color=None)
ctf = crit_box.text_frame
ctf.word_wrap = True
ctf.vertical_anchor = MSO_ANCHOR.MIDDLE
ctf.margin_left = ctf.margin_right = Inches(0.20)
cp = ctf.paragraphs[0]
cp.text = "CRITÉRIO DE ACEITE: BACK-END HOMOLOGADO PARA INFERÊNCIA MULTICLASSE"
cp.alignment = PP_ALIGN.CENTER
cp.font.name = "Segoe UI"
cp.font.size = Pt(10)
cp.font.bold = True
cp.font.color.rgb = COLOR_SENAC_ORANGE
cp2 = ctf.add_paragraph()
cp2.text = "Pipeline completo validado com a nova bancada física para suporte direto ao App Mobile e ERP."
cp2.alignment = PP_ALIGN.CENTER
cp2.font.name = "Segoe UI"
cp2.font.size = Pt(8.5)
cp2.font.color.rgb = COLOR_TEXT_WHITE

set_speaker_notes(s5, """ROTEIRO DE FALA — SLIDE 5 (30 SEGUNDOS):
"Para fecharmos com o planejamento: a nossa arquitetura base de back-end — incluindo o banco MySQL, a mensageria MQTT Mosquitto, o buffer Redis e os WebSockets do Reverb — já está totalmente implementada e funcional no repositório. Por isso, as próximas duas semanas serão focadas na evolução: na Semana 1, ajustamos os DTOs do Laravel para receber temperatura e tacômetro, gravamos as 4 classes mecânicas na nova bancada e retreinamos o XGBoost. Na Semana 2, refinamos as regras da ISO 20816, fechamos os endpoints da API RESTful e realizamos o teste de ponta a ponta até a emissão de ordens de serviço. Muito obrigado a todos, ficamos agora à disposição para a arguição da banca!"
""")

# Save Presentations
prs.save(OUTPUT_PATH_1)
prs.save(OUTPUT_PATH_2)
print("SUCCESSFULLY GENERATED PRESENTATIONS AT:")
print(f" -> {OUTPUT_PATH_1}")
print(f" -> {OUTPUT_PATH_2}")
