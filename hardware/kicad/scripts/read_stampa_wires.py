sch = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_bancada_painel\amemiya_bancada_painel.kicad_sch'
txt = open(sch, encoding='utf-8').read()

import re
wires = re.findall(r'\(wire[\s\S]*?\(pts[\s\S]*?\)', txt)
for w in wires:
    if "212" in w or "204" in w or "218" in w:
        print(w.strip().replace('\n', ' '))
