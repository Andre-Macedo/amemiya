import re

with open(r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2\amemiya_carrier_v2.kicad_pcb', 'r', encoding='utf-8') as f:
    text = f.read()

def delete_sexp_containing(text, substring, keyword):
    while True:
        pos = text.find(substring)
        if pos == -1:
            break
        start = text.rfind(keyword, 0, pos)
        if start == -1:
            break
        count = 0
        end = -1
        for i in range(start, len(text)):
            if text[i] == '(': count += 1
            elif text[i] == ')':
                count -= 1
                if count == 0:
                    end = i + 1
                    break
        if end != -1:
            text = text[:start] + text[end:]
        else:
            break
    return text

to_remove = [
    'J_AMP1', 'R_B1', 'R_BIAS1', 'R_BIAS2', 'C_BIAS1', 'R_F1', 'C_F1',
    'D_AMP_HI1', 'D_AMP_LO1', 'J_TACHO1', 'R_PU_TACHO1', 'R_S_TACHO1', 'C_F_TACHO1'
]

for ref in to_remove:
    before = len(text)
    text = delete_sexp_containing(text, f'(property "Reference" "{ref}"', "(footprint")
    print(f"Removed {ref}: {before - len(text)} chars deleted.")

# Also remove segment/via tracks of obsolete nets
bad_net_numbers = []
# Find net numbers for obsolete nets
# In kicad_pcb: (net 12 "/Sensors_AFE/AMP_IN+")
net_matches = re.findall(r'\(net ([0-9]+) "([^"]+)"\)', text)
bad_nets = {
    '/Sensors_AFE/AMP_IN+', '/Sensors_AFE/AMP_IN-', '/Sensors_AFE/BIAS_1V65',
    '/MCU_ESP32S3/AMP_ADC', '/Sensors_AFE/TACHO_RAW', '/MCU_ESP32S3/TACHO',
    '/MCU_ESP32S3/I2S_SD', '/MCU_ESP32S3/I2S_SCK', '/MCU_ESP32S3/I2S_WS'
}

for num, name in net_matches:
    if name in bad_nets:
        bad_net_numbers.append(num)
        print(f"Obsolete net {name} -> number {num}")

# Delete tracks with (net <num>)
for num in bad_net_numbers:
    before = len(text)
    text = delete_sexp_containing(text, f'(net {num})', "(segment")
    text = delete_sexp_containing(text, f'(net {num})', "(via")
    print(f"Removed net {num} tracks: {before - len(text)} chars deleted.")

with open(r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2\amemiya_carrier_v2.kicad_pcb', 'w', encoding='utf-8') as f:
    f.write(text)

print("PCB file updated via S-expression parsing successfully!")
