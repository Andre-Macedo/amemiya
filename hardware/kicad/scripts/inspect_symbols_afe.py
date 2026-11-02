import re

with open(r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2\02_Sensors_AFE.kicad_sch', 'r', encoding='utf-8') as f:
    text = f.read()

# Check what symbols have references for AMP or TACHO
symbols_to_remove = ['J_AMP', 'R_B', 'R_BIAS1', 'R_BIAS2', 'C_BIAS', 'R_F1', 'C_F', 'D_AMP_HI', 'D_AMP_LO', 'J_TACHO', 'R_PU_TACHO', 'R_S_TACHO', 'C_F_TACHO']

for ref in symbols_to_remove:
    pos = text.find(f'(property "Reference" "{ref}"')
    print(f'Symbol {ref}: found at pos {pos}')

# Check hierarchical labels
print('AMP_ADC label:', text.find('"AMP_ADC"'))
print('TACHO label:', text.find('"TACHO"'))
print('TACHO_RAW label:', text.find('"TACHO_RAW"'))
