sch = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_bancada_painel\amemiya_bancada_painel.kicad_sch'
txt = open(sch, encoding='utf-8').read()

import re
pos = txt.find('S_TAMPA')
# find nearest wires around S_TAMPA
print("Text around S_TAMPA:")
# print 500 chars before and after
print(txt[pos-300:pos+700])
