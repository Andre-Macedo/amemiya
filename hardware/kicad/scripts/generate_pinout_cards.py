#!/usr/bin/env python3
"""
Amemiya - Bancada de Testes de Motores
Gerador de Cartões Individuais de Pinout / Contatos Físicos
Gera imagens individuais limpas com as fotos dos componentes e indicação clara de cada parafuso/contato.
"""

import os
from PIL import Image, ImageDraw, ImageFont

PROJECT_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "amemiya_bancada_painel"))
PINOUTS_DIR = os.path.join(PROJECT_DIR, "pinouts")
ARTIFACT_DIR = r"C:\Users\andrl\.gemini\antigravity-cli\brain\efd833bf-6ba1-4a1b-a7ce-aa9aa87aa86c"
USER_MEDIA_DIR = r"C:\Users\andrl\.gemini\antigravity-cli\brain\efd833bf-6ba1-4a1b-a7ce-aa9aa87aa86c\.user_uploaded"

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

def create_card(title, subtitle, img_key, pins_info, filename, border_color="#3B82F6"):
    W, H = 1200, 800
    card = Image.new("RGB", (W, H), "#FFFFFF")
    draw = ImageDraw.Draw(card)

    # Outer border
    draw.rounded_rectangle([15, 15, W - 15, H - 15], radius=16, outline="#CBD5E1", width=2)
    # Header box
    draw.rounded_rectangle([15, 15, W - 15, 100], radius=16, fill="#0F172A")
    draw.text((40, 28), title, fill="#FFFFFF", font=get_font(24, bold=True))
    draw.text((40, 62), subtitle, fill="#94A3B8", font=get_font(15))

    # Place image on the left side
    img_box = [40, 130, 520, 750]
    draw.rounded_rectangle(img_box, radius=12, fill="#F8FAFC", outline="#E2E8F0", width=2)

    img_path = MEDIA.get(img_key)
    if img_path and os.path.exists(img_path):
        img = Image.open(img_path)
        avail_w = img_box[2] - img_box[0] - 30
        avail_h = img_box[3] - img_box[1] - 30
        img.thumbnail((avail_w, avail_h), Image.Resampling.LANCZOS)
        x_off = img_box[0] + (img_box[2] - img_box[0] - img.width) // 2
        y_off = img_box[1] + (img_box[3] - img_box[1] - img.height) // 2
        card.paste(img, (x_off, y_off))

    # Right side: Pinout details
    rx = 550
    draw.rounded_rectangle([rx, 130, W - 40, 750], radius=12, fill="#F8FAFC", outline="#E2E8F0", width=2)
    draw.text((rx + 25, 150), "IDENTIFICAÇÃO DOS TERMINAIS & ONDE LIGAR:", fill="#1E293B", font=get_font(18, bold=True))

    y = 195
    for pin, color, desc, where in pins_info:
        # Dynamic Pin badge width
        f_pin = get_font(13, bold=True)
        txt_w = draw.textlength(pin, font=f_pin)
        badge_w = max(110, int(txt_w + 24))
        draw.rounded_rectangle([rx + 25, y, rx + 25 + badge_w, y + 36], radius=6, fill=color)
        draw.text((rx + 37, y + 8), pin, fill="#FFFFFF", font=f_pin)

        # Description
        draw.text((rx + 35 + badge_w, y + 2), desc, fill="#0F172A", font=get_font(14, bold=True))
        draw.text((rx + 35 + badge_w, y + 22), f"-> Conectar em: {where}", fill="#475569", font=get_font(13))

        y += 62
        if y > 720:
            break

    out_path = os.path.join(PINOUTS_DIR, filename)
    card.save(out_path)
    print(f"Card saved: {out_path}")

    # Copy to artifacts
    art_path = os.path.join(ARTIFACT_DIR, filename)
    try:
        import shutil
        shutil.copyfile(out_path, art_path)
    except Exception as e:
        print("Error copying to artifact:", e)

def main():
    # 1. Disjuntor Steck SD 2P
    create_card(
        title="DISJUNTOR BIPOLAR (Q1) — STECK SD 2P",
        subtitle="Proteção Termomagnética Geral (Curva C 10A)",
        img_key="disjuntor",
        pins_info=[
            ("Borne 1", "#DC2626", "Entrada Fase L1", "Fase 1 vinda da tomada/rede 220V"),
            ("Borne 3", "#2563EB", "Entrada Fase L2 / Neutro", "Fase 2 (ou Neutro) da tomada/rede 220V"),
            ("Borne 2", "#DC2626", "Saída Fase L1 Protegida", "Vai para 1L1 do Contator E para o pino L da Fonte"),
            ("Borne 4", "#2563EB", "Saída Fase L2 Protegida", "Vai para 3L2 do Contator E para o pino N da Fonte"),
        ],
        filename="card_01_disjuntor_steck.png"
    )

    # 2. Contator Steck SC
    create_card(
        title="CONTATOR DE FORÇA (K1) — STECK SC",
        subtitle="Chave Eletromagnética de Potência & Selo",
        img_key="contator",
        pins_info=[
            ("Borne 1L1", "#DC2626", "Entrada de Força Fase 1", "Vem do borne 2 do Disjuntor Steck"),
            ("Borne 3L2", "#2563EB", "Entrada de Força Fase 2", "Vem do borne 4 do Disjuntor Steck"),
            ("Borne 2T1", "#B91C1C", "Saída de Força para Dimmer", "Vai para o fio L do plugue do Dimmer"),
            ("Borne 4T2", "#1D4ED8", "Saída de Força para Dimmer", "Vai para o fio N do plugue do Dimmer"),
            ("Borne 13", "#9333EA", "Contato Auxiliar NA (Selo)", "Vem do pino C (11) do Botão Verde Liga"),
            ("Borne 14", "#16A34A", "Contato Auxiliar NA (Selo)", "Vai para o pino A1 da bobina (mesmo fio)"),
            ("Borne A1", "#16A34A", "Bobina (+) 24V CC", "Vem do pino NO (14) do Botão Verde Liga"),
            ("Borne A2", "#334155", "Bobina (-) 0V / COM", "Vai direto no parafuso -V (0V) da Fonte Colmeia"),
        ],
        filename="card_02_contator_steck.png"
    )

    # 3. Fonte Chaveada Colmeia
    create_card(
        title="FONTE CHAVEADA COLMEIA (T1) — 24V DC",
        subtitle="Alimentação de Segurança do Comando e Tacômetro",
        img_key="colmeia",
        pins_info=[
            ("Borne L", "#DC2626", "Entrada Fase 220V AC", "Vem do borne 2 do Disjuntor Steck"),
            ("Borne N", "#2563EB", "Entrada Neutro/Fase 220V", "Vem do borne 4 do Disjuntor Steck"),
            ("Borne PE", "#16A34A", "Aterramento de Carcaça", "Ligar no fio verde de Terra da tomada e motor"),
            ("Borne -V", "#334155", "Saída DC Negativo (0V / COM)", "Ligar no A2 do contator e no GND do Tacômetro"),
            ("Borne +V", "#EA580C", "Saída DC Positivo (+24V)", "Vai para o pino 1 do Botão de Emergência"),
        ],
        filename="card_03_fonte_colmeia.png"
    )

    # 4. Botão de Emergência LAY7
    create_card(
        title="BOTÃO DE EMERGÊNCIA (S0) — LAY7 COGUMELO",
        subtitle="Desarme de Segurança NR-12 com Trava Mecânica (Gira-Puxa)",
        img_key="emergencia",
        pins_info=[
            ("Borne 1", "#EA580C", "Entrada NF (Normal Fechado)", "Vem do parafuso +V (+24V) da Fonte Colmeia"),
            ("Borne 2", "#EA580C", "Saída NF (Normal Fechado)", "Vai para o pino COM do Microswitch da tampa"),
        ],
        filename="card_04_emergencia_lay7.png"
    )

    # 5. Microswitch da Tampa KW11-3Z
    create_card(
        title="CHAVE DE SEGURANÇA DA TAMPA — KW11-3Z (PINOUT CORRETO)",
        subtitle="Microswitch com Rolete (Intertravamento e Alarme de Tampa Aberta)",
        img_key="microswitch",
        pins_info=[
            ("Pino COM", "#EA580C", "Comum (Sob o eixo da alavanca)", "Vem do borne 2 do Botão de Emergência (+24V)"),
            ("Pino NA / NO", "#16A34A", "Normal Aberto (Pino central)", "Vai para o pino C (11) do Botão Vermelho (Comando do Motor)"),
            ("Pino NF / NC", "#DC2626", "Normal Fechado (Pino frontal)", "Vai para o borne X1 do LED Vermelho (Tampa Aberta)"),
        ],
        filename="card_05_microswitch_tampa.png"
    )

    # 6. Botoeiras Sibratec XB2 (Desliga & Liga)
    # Combine red and green button in one card
    W, H = 1200, 800
    card = Image.new("RGB", (W, H), "#FFFFFF")
    draw = ImageDraw.Draw(card)
    draw.rounded_rectangle([15, 15, W - 15, H - 15], radius=16, outline="#CBD5E1", width=2)
    draw.rounded_rectangle([15, 15, W - 15, 100], radius=16, fill="#0F172A")
    draw.text((40, 28), "BOTOEIRAS DE PAINEL — SIBRATEC XB2", fill="#FFFFFF", font=get_font(24, bold=True))
    draw.text((40, 62), "Botão Desliga Vermelho (NF) & Botão Liga Verde (NA)", fill="#94A3B8", font=get_font(15))

    # Place Red button
    img_box_r = [40, 130, 280, 750]
    draw.rounded_rectangle(img_box_r, radius=12, fill="#FEF2F2", outline="#FECACA", width=2)
    draw.text((60, 145), "BOTÃO VERMELHO (DESLIGA)", fill="#DC2626", font=get_font(13, bold=True))
    draw.text((60, 165), "Modelo: XB2-EA145 (1NF)", fill="#7F1D1D", font=get_font(11))
    if os.path.exists(MEDIA["btn_vermelho"]):
        im = Image.open(MEDIA["btn_vermelho"])
        im.thumbnail((220, 500), Image.Resampling.LANCZOS)
        card.paste(im, (img_box_r[0] + (img_box_r[2] - img_box_r[0] - im.width)//2, 200))

    # Place Green button
    img_box_g = [300, 130, 540, 750]
    draw.rounded_rectangle(img_box_g, radius=12, fill="#F0FDF4", outline="#BBF7D0", width=2)
    draw.text((320, 145), "BOTÃO VERDE (LIGA)", fill="#16A34A", font=get_font(13, bold=True))
    draw.text((320, 165), "Modelo: XB2-EA135 (1NA)", fill="#14532D", font=get_font(11))
    if os.path.exists(MEDIA["btn_verde"]):
        im = Image.open(MEDIA["btn_verde"])
        im.thumbnail((220, 500), Image.Resampling.LANCZOS)
        card.paste(im, (img_box_g[0] + (img_box_g[2] - img_box_g[0] - im.width)//2, 200))

    # Right side: Instructions
    rx = 560
    draw.rounded_rectangle([rx, 130, W - 40, 750], radius=12, fill="#F8FAFC", outline="#E2E8F0", width=2)
    draw.text((rx + 25, 150), "COMO LIGAR OS BOTÕES SIBRATEC XB2:", fill="#1E293B", font=get_font(18, bold=True))

    pins_botoes = [
        ("VERMELHO: C (11)", "#DC2626", "Entrada do Botão Desliga", "Vem do pino NA (NO) do Microswitch da tampa"),
        ("VERMELHO: NC (12)", "#DC2626", "Saída do Botão Desliga", "Vai para o pino C (11) do Botão Verde"),
        ("VERDE: C (11)", "#16A34A", "Entrada do Botão Liga", "Vem do pino NC (12) do botão vermelho"),
        ("SELO 13", "#9333EA", "Puxar fio do pino C (11)", "Ligar no borne 13 (NA) do contator Steck"),
        ("VERDE: NO (14)", "#16A34A", "Saída de Pulso de Partida", "Vai para o borne A1 da bobina do contator"),
        ("SELO 14", "#16A34A", "Puxar fio do pino A1", "Ligar no borne 14 (NA) do contator Steck"),
    ]

    y = 200
    for pin, color, desc, where in pins_botoes:
        draw.rounded_rectangle([rx + 25, y, rx + 200, y + 36], radius=6, fill=color)
        draw.text((rx + 35, y + 7), pin, fill="#FFFFFF", font=get_font(14, bold=True))
        draw.text((rx + 215, y + 2), desc, fill="#0F172A", font=get_font(14, bold=True))
        draw.text((rx + 215, y + 22), f"-> {where}", fill="#475569", font=get_font(13))
        y += 85

    out_path = os.path.join(PINOUTS_DIR, "card_06_botoes_sibratec.png")
    card.save(out_path)
    print(f"Card saved: {out_path}")
    try:
        import shutil
        shutil.copyfile(out_path, os.path.join(ARTIFACT_DIR, "card_06_botoes_sibratec.png"))
    except: pass

    # 7. Dimmer AC 220V & Motor
    create_card(
        title="DIMMER AC 220V & MOTOR DE TANQUINHO",
        subtitle="Regulador de Velocidade e Conexão de Carga",
        img_key="dimmer",
        pins_info=[
            ("Plugue Macho 1", "#B91C1C", "Entrada Fase L (Dimmer)", "Conectar no borne 2T1 do contator Steck"),
            ("Plugue Macho 2", "#1D4ED8", "Entrada Fase N (Dimmer)", "Conectar no borne 4T2 do contator Steck"),
            ("Tomada Fêmea 1", "#B91C1C", "Saída Regulada 1 (Dimmer)", "Conectar no Fio Comum do motor"),
            ("Tomada Fêmea 2", "#1D4ED8", "Saída Regulada 2 (Dimmer)", "Conectar no Fio de Marcha do motor"),
            ("Capacitor 12uF", "#0284C7", "Capacitor Permanente de Marcha", "Ligar entre Fio Auxiliar e Fio de Marcha"),
            ("Carcaça Motor", "#16A34A", "Aterramento de Proteção PE", "Ligar no fio de Terra verde da bancada"),
        ],
        filename="card_07_dimmer_motor.png"
    )

    # 8. Tacômetro & Sensor Hall
    create_card(
        title="TACÔMETRO DIGITAL CF5135B & SENSOR HALL M12",
        subtitle="Medição de RPM com Display Digital e Sensor Magnético",
        img_key="tacometro",
        pins_info=[
            ("Pino VCC (+)", "#DC2626", "Alimentação Positiva Display", "Ligar no parafuso +V (+24V) da Fonte Colmeia"),
            ("Pino GND (-)", "#334155", "Alimentação Negativa Display", "Ligar no parafuso -V (0V) da Fonte Colmeia"),
            ("Fio Marrom", "#78350F", "+V do Sensor Hall M12", "Ligar no pino +V_sensor do display (ou +V da fonte)"),
            ("Fio Preto", "#0F172A", "Sinal NPN do Sensor Hall", "Ligar no pino Sinal_in do display"),
            ("Fio Azul", "#2563EB", "GND do Sensor Hall M12", "Ligar no pino GND_sensor do display (ou -V)"),
            ("Ímã Neodímio", "#DC2626", "Alvo Magnético no Eixo", "Colar no eixo/polia (distância de 1 a 5mm do sensor)"),
        ],
        filename="card_08_tacometro_sensor.png"
    )

    # 9. Sinaleiros LED 22mm
    create_card(
        title="SINALIZADORES VISUAIS LED 22MM (PORTA DO PAINEL)",
        subtitle="Indicação de Status Operacional e Segurança",
        img_key="led_verde",
        pins_info=[
            ("LED BRANCO: X1", "#0284C7", "Positivo Painel Ligado", "Ligar no parafuso +V (+24V) da Fonte Colmeia"),
            ("LED BRANCO: X2", "#334155", "Negativo Painel Ligado", "Ligar no parafuso -V (0V) da Fonte Colmeia"),
            ("LED VERDE: X1", "#16A34A", "Positivo Motor Ligado", "Ligar no borne A1 do Contator Steck (em paralelo c/ bobina)"),
            ("LED VERDE: X2", "#334155", "Negativo Motor Ligado", "Ligar no parafuso -V (0V) da Fonte Colmeia (borne A2/0V)"),
            ("LED VERMELHO: X1", "#DC2626", "Positivo Alarme Tampa Aberta", "Ligar no pino NF (NC) do Microswitch da tampa"),
            ("LED VERMELHO: X2", "#334155", "Negativo Alarme Tampa Aberta", "Ligar no parafuso -V (0V) da Fonte Colmeia"),
        ],
        filename="card_09_sinaleiros_led.png"
    )

if __name__ == "__main__":
    main()
