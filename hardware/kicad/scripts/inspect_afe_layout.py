import re

with open(r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2\02_Sensors_AFE.kicad_sch', 'r', encoding='utf-8') as f:
    text = f.read()

# Let's see all text items
texts = re.findall(r'\(text "([^"]+)"[\s\S]*?\(at ([0-9.-]+) ([0-9.-]+)', text)
print("Text annotations:")
for t, x, y in texts:
    print(f"  ({x}, {y}): {t[:50]}")

# Let's see all symbols and their positions
symbols = re.findall(r'\(symbol[\s\S]*?\(property "Reference" "([^"]+)"[\s\S]*?\(at ([0-9.-]+) ([0-9.-]+)', text)
print("\nSymbols:")
for ref, x, y in symbols:
    print(f"  {ref} at ({x}, {y})")

# Let's see all hierarchical labels and their positions
hlabels = re.findall(r'\(hierarchical_label "([^"]+)"[\s\S]*?\(at ([0-9.-]+) ([0-9.-]+)', text)
print("\nHierarchical labels:")
for name, x, y in hlabels:
    print(f"  {name} at ({x}, {y})")
