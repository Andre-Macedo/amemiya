import re

path = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\scripts\generate_pinout_cards.py'
txt = open(path, encoding='utf-8').read()

for i, line in enumerate(txt.splitlines()):
    if any(k in line for k in ["Microswitch", "microswitch", "Pino NC", "Pino NO", "Pino COM", "Tampa"]):
        print(f"Line {i+1}: {line.strip()}")
