import re

with open(r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2\03_MCU_ESP32S3.kicad_sch', 'r', encoding='utf-8') as f:
    text = f.read()

# Let's see what is connected with labels in 03_MCU_ESP32S3.kicad_sch
labels = re.findall(r'\(hierarchical_label "([^"]+)"', text)
print("Connected hierarchical labels on ESP32-S3:", labels)

nc_count = len(re.findall(r'\(no_connect', text))
print("Total no_connect pins on ESP32-S3:", nc_count)
