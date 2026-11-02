sch = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_bancada_painel\amemiya_bancada_painel.kicad_sch'
txt = open(sch, encoding='utf-8').read()

pos = txt.find('(symbol "Industrial:SW_SPDT"')
if pos == -1:
    pos = txt.find('CHAVE_TAMPA_SPDT')
    # print context
    print(txt[pos-500:pos+500])
else:
    print(txt[pos:pos+1000])
