sch_path = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2\04_Peripherals_LoRa.kicad_sch'
txt = open(sch_path, encoding='utf-8').read()

pos = txt.find('(property "Reference" "J_BUZ"')
if pos != -1:
    print(txt[pos-100:pos+800])
