sch = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_bancada_painel\amemiya_bancada_painel.kicad_sch'
txt = open(sch, encoding='utf-8').read()

import re
# S_TAMPA at (212, 78)
# COM is at (212, 72.92)
# NC is at (212, 83.08)
# NO is at (219.62, 78)

wires = re.findall(r'\(wire[\s\S]*?\(pts[\s\S]*?\)', txt)
for w in wires:
    if "72.92" in w or "83.08" in w or "219.62" in w:
        print("S_TAMPA wire:", w.strip().replace('\n', ' '))
