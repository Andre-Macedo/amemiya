import os
import shutil
from PIL import Image, ImageDraw, ImageFont

PROJECT_DIR = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_bancada_painel"
PINOUTS_DIR = os.path.join(PROJECT_DIR, "pinouts")
ARTIFACT_DIR = r"C:\Users\andrl\.gemini\antigravity-cli\brain\efd833bf-6ba1-4a1b-a7ce-aa9aa87aa86c"
USER_MEDIA_DIR = os.path.join(ARTIFACT_DIR, ".user_uploaded")

os.makedirs(PINOUTS_DIR, exist_ok=True)

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

def get_font(size, bold=False):
    try:
        if bold:
            return ImageFont.truetype("arialbd.ttf", size)
        return ImageFont.truetype("arial.ttf", size)
    except:
        return ImageFont.load_default()

def create_card_microswitch():
    W, H = 1200, 800
    card = Image.new("RGB", (W, H), "#FFFFFF")
    draw = ImageDraw.Draw(card)

    # Outer border
    draw.rounded_rectangle([15, 15, W - 15, H - 15], radius=16, outline="#CBD5E1", width=2)
    # Header box
    draw.rounded_rectangle([15, 15, W - 15, 100], radius=16, fill="#0F172A")
    draw.text((40, 26), "CHAVE DE SEGURANÇA DA TAMPA — KW11-3Z (PINOUT CORRETO)", fill="#FFFFFF", font=get_font(23, bold=True))
    draw.text((40, 60), "Microswitch com Rolete | Intertravamento NR-12 & Alarme Tampa Aberta", fill="#94A3B8", font=get_font(15))

    # Place image on the left side
    img_box = [40, 120, 480, 750]
    draw.rounded_rectangle(img_box, radius=12, fill="#F8FAFC", outline="#E2E8F0", width=2)

    img_path = MEDIA.get("microswitch")
    if img_path and os.path.exists(img_path):
        img = Image.open(img_path)
        avail_w = img_box[2] - img_box[0] - 20
        avail_h = img_box[3] - img_box[1] - 20
        img.thumbnail((avail_w, avail_h), Image.Resampling.LANCZOS)
        x_off = img_box[0] + (img_box[2] - img_box[0] - img.width) // 2
        y_off = img_box[1] + (img_box[3] - img_box[1] - img.height) // 2
        card.paste(img, (x_off, y_off))

    # Right side: Pinout details and Explanation Table
    rx = 510
    draw.rounded_rectangle([rx, 120, W - 40, 750], radius=12, fill="#F8FAFC", outline="#E2E8F0", width=2)
    draw.text((rx + 25, 140), "COMO LIGAR OS PINOS DO MICROSWITCH:", fill="#1E293B", font=get_font(17, bold=True))

    pins_info = [
        ("Pino COM", "#EA580C", "Comum (Sob o eixo traseiro da alavanca)",
         "Entrada de +24V vinda do Borne 2 do Botão de Emergência (S0)"),
        ("Pino NA / NO", "#16A34A", "Normal Aberto (Pino central inferior)",
         "Vai para o Pino C (11) do Botão Vermelho (Comando do Motor Contator K1)"),
        ("Pino NF / NC", "#DC2626", "Normal Fechado (Pino frontal sob rolete)",
         "Vai para o Borne X1 do LED Vermelho de Painel (Alarme Tampa Aberta)"),
    ]

    y = 180
    for pin, color, desc, where in pins_info:
        f_pin = get_font(13, bold=True)
        txt_w = draw.textlength(pin, font=f_pin)
        badge_w = max(125, int(txt_w + 20))
        draw.rounded_rectangle([rx + 25, y, rx + 25 + badge_w, y + 36], radius=6, fill=color)
        draw.text((rx + 33, y + 8), pin, fill="#FFFFFF", font=f_pin)

        draw.text((rx + 35 + badge_w, y + 2), desc, fill="#0F172A", font=get_font(14, bold=True))
        draw.text((rx + 35 + badge_w, y + 22), f"-> {where}", fill="#334155", font=get_font(12, bold=True))
        y += 66

    # Explanatory Table for Tampa Fechada vs Tampa Aberta
    y_table = 390
    draw.rounded_rectangle([rx + 25, y_table, W - 65, y_table + 325], radius=10, fill="#FFFFFF", outline="#CBD5E1", width=2)
    draw.rounded_rectangle([rx + 25, y_table, W - 65, y_table + 40], radius=10, fill="#1E293B")
    draw.text((rx + 40, y_table + 10), "POR QUE ESSA LIGAÇÃO ESTÁ CORRETA? (MECÂNICA DA TAMPA)", fill="#FFFFFF", font=get_font(13, bold=True))

    # Row 1: Tampa Fechada
    r1_y = y_table + 50
    draw.rounded_rectangle([rx + 35, r1_y, W - 75, r1_y + 120], radius=8, fill="#F0FDF4", outline="#86EFAC", width=1)
    draw.text((rx + 50, r1_y + 10), "TAMPA FECHADA (Bancada Pronta / Operação Segura):", fill="#166534", font=get_font(13, bold=True))
    draw.text((rx + 50, r1_y + 35), "• O peso/aba da tampa apoia na alavanca e mantém o switch PRESSIONADO (Clicado).", fill="#15803D", font=get_font(12))
    draw.text((rx + 50, r1_y + 55), "• Com o switch pressionado, o circuito fecha entre COM e NA (NO).", fill="#15803D", font=get_font(12))
    draw.text((rx + 50, r1_y + 75), "• O +24V passa pelo contato NA e alimenta a linha de partida do motor. O motor pode rodar!", fill="#166534", font=get_font(12, bold=True))
    draw.text((rx + 50, r1_y + 95), "• O contato NF fica aberto -> LED Vermelho 'TAMPA ABERTA' fica APAGADO.", fill="#475569", font=get_font(12))

    # Row 2: Tampa Aberta
    r2_y = y_table + 180
    draw.rounded_rectangle([rx + 35, r2_y, W - 75, r2_y + 120], radius=8, fill="#FEF2F2", outline="#FECACA", width=1)
    draw.text((rx + 50, r2_y + 10), "TAMPA ABERTA (Operador acessando o motor / Situação de Risco):", fill="#991B1B", font=get_font(13, bold=True))
    draw.text((rx + 50, r2_y + 35), "• Ao levantar a tampa, a alavanca é LIBERADA e volta para a posição de REPOUSO (Solta).", fill="#B91C1C", font=get_font(12))
    draw.text((rx + 50, r2_y + 55), "• Em repouso, o contato COM e NA se ABRE -> Corta a bobina do contator K1 na hora!", fill="#991B1B", font=get_font(12, bold=True))
    draw.text((rx + 50, r2_y + 75), "• Em repouso, o contato COM conecta ao NF (NC) -> Fecha o circuito do sinaleiro.", fill="#B91C1C", font=get_font(12))
    draw.text((rx + 50, r2_y + 95), "• O +24V passa pelo contato NF e ACENDE O LED VERMELHO 'TAMPA ABERTA'!", fill="#DC2626", font=get_font(12, bold=True))

    out_path = os.path.join(PINOUTS_DIR, "card_05_microswitch_tampa.png")
    card.save(out_path)
    print(f"Card 05 saved: {out_path}")
    shutil.copyfile(out_path, os.path.join(ARTIFACT_DIR, "card_05_microswitch_tampa.png"))

create_card_microswitch()
print("Card 05 regenerated successfully!")
