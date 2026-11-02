import os
import subprocess
import zipfile

KICAD_CLI = r"C:\Program Files\KiCad\9.0\bin\kicad-cli.exe"
BASE_DIR = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2"
PCB_FILE = os.path.join(BASE_DIR, "amemiya_carrier_v2.kicad_pcb")
ROOT_SCH = os.path.join(BASE_DIR, "amemiya_carrier_v2.kicad_sch")
FAB_DIR = os.path.join(BASE_DIR, "fabrication")
GERBER_DIR = os.path.join(FAB_DIR, "gerbers")

os.makedirs(GERBER_DIR, exist_ok=True)

# 1. PDF Esquemático
pdf_sch = os.path.join(BASE_DIR, "amemiya_carrier_v2_schematic.pdf")
subprocess.run([KICAD_CLI, "sch", "export", "pdf", "--output", pdf_sch, ROOT_SCH])
print("PDF Esquemático exportado.")

# 2. STEP 3D
step_pcb = os.path.join(BASE_DIR, "amemiya_carrier_v2.step")
subprocess.run([KICAD_CLI, "pcb", "export", "step", "--output", step_pcb, "--grid-origin", PCB_FILE])
print("STEP 3D exportado.")

# 3. Gerbers & Drill
subprocess.run([KICAD_CLI, "pcb", "export", "gerbers", "--output", GERBER_DIR, PCB_FILE])
subprocess.run([KICAD_CLI, "pcb", "export", "drill", "--output", GERBER_DIR, PCB_FILE])

zip_path = os.path.join(FAB_DIR, "amemiya_carrier_v2_gerbers.zip")
with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED) as z:
    for root, dirs, files in os.walk(GERBER_DIR):
        for fl in files:
            fpath = os.path.join(root, fl)
            z.write(fpath, os.path.basename(fpath))
print("Gerbers ZIP exportado.")
print("=== TODOS OS ARTEFATOS ATUALIZADOS COM SUCESSO! ===")
