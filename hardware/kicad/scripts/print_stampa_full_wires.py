sch = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_bancada_painel\amemiya_bancada_painel.kicad_sch'
txt = open(sch, encoding='utf-8').read()

import re
wires = re.findall(r'\(wire[\s\S]*?\(pts[\s\S]*?\)\)', txt)
for w in wires:
    if any(k in w for k in ["219.62", "83.08", "72.92"]):
        print("Wire:", w.strip().replace('\n', ' '))
