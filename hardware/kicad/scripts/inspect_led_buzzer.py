import os
import re

base = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2'
for f in os.listdir(base):
    if f.endswith('.kicad_sch'):
        txt = open(os.path.join(base, f), encoding='utf-8').read()
        for ref in ['R_LED1', 'R_LED2', 'R_LED3', 'R_BUZ1', 'J_BUZ1', 'J_LEDS1', 'Q_BUZ']:
            m = re.search(r'\(property "Reference" "' + ref + r'"[\s\S]*?\(property "Value" "([^"]+)"', txt)
            if m:
                print(f"{f} -> {ref}: value = {m.group(1)}")
