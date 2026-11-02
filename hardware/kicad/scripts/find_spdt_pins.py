sch = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_bancada_painel\amemiya_bancada_painel.kicad_sch'
txt = open(sch, encoding='utf-8').read()

pos = txt.find('(symbol "SW_Limit_Safety_SPDT_1_1"')
print(txt[pos:pos+1200])
