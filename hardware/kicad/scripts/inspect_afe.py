import re

with open(r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2\02_Sensors_AFE.kicad_sch', 'r', encoding='utf-8') as f:
    text = f.read()

refs = sorted(list(set(re.findall(r'\(property "Reference" "([^"]+)"', text))))
print('AFE References:', refs)

labels = sorted(list(set(re.findall(r'\(hierarchical_label "([^"]+)"', text))))
print('AFE Hierarchical Labels:', labels)
