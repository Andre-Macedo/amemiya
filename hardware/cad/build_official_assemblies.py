# -*- coding: utf-8 -*-
"""
Construcao das Montagens Mestres (Assemblies) com App::Link:
- amemiya_main_node_assembly.FCStd referencia amemiya_main_node_base.FCStd, amemiya_main_node_lid.FCStd e amemiya_main_node_battery_door.FCStd
- amemiya_probe_tower_assembly.FCStd referencia amemiya_probe_tower_body.FCStd e amemiya_probe_tower_cap.FCStd
- As montagens contem toda a eletronica, sensores, chicotes, conectores e fixadores.
"""

import FreeCAD
import Part
import os
import shutil

cad_dir = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad"

def apply_style(obj, rgb, transp=0, vis=True):
    if hasattr(obj, 'ViewObject') and obj.ViewObject is not None:
        if hasattr(obj.ViewObject, 'ShapeColor'):
            try: obj.ViewObject.ShapeColor = rgb
            except Exception: pass
        if hasattr(obj.ViewObject, 'Transparency'):
            try: obj.ViewObject.Transparency = transp
            except Exception: pass
        if hasattr(obj.ViewObject, 'Visibility'):
            try: obj.ViewObject.Visibility = vis
            except Exception: pass

# ==============================================================================
# 1. MONTAGEM MESTRE DO MAIN NODE (amemiya_main_node_assembly.FCStd)
# ==============================================================================
print("1/2: Construindo amemiya_main_node_assembly.FCStd com App::Link...")
assy_main_path = os.path.join(cad_dir, "amemiya_main_node_assembly.FCStd")

# Abrir os arquivos de pecas dedicados
doc_base = FreeCAD.openDocument(os.path.join(cad_dir, "amemiya_main_node_base.FCStd"))
doc_lid = FreeCAD.openDocument(os.path.join(cad_dir, "amemiya_main_node_lid.FCStd"))
doc_door = FreeCAD.openDocument(os.path.join(cad_dir, "amemiya_main_node_battery_door.FCStd"))

# Criar o documento de montagem e salvar imediatamente para permitir links externos
doc_assy_main = FreeCAD.newDocument("amemiya_main_node_assembly")
doc_assy_main.saveAs(assy_main_path)

# 1.1 Links Externos para as Pecas Parametricas
link_base = doc_assy_main.addObject("App::Link", "Peca_Base_Gabinete")
link_base.setLink(doc_base.getObject("Body_Base"))
link_base.Label = "1. Peca: Gabinete Base (amemiya_main_node_base.FCStd)"

link_lid = doc_assy_main.addObject("App::Link", "Peca_Tampa_Superior")
link_lid.setLink(doc_lid.getObject("Body_Tampa"))
link_lid.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 38.0), FreeCAD.Rotation())
link_lid.Label = "2. Peca: Tampa Superior (amemiya_main_node_lid.FCStd)"

link_door = doc_assy_main.addObject("App::Link", "Peca_Tampa_Bateria")
link_door.setLink(doc_door.getObject("Body_Porta_Bateria"))
link_door.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0.0), FreeCAD.Rotation())
link_door.Label = "3. Peca: Tampa Bateria (amemiya_main_node_battery_door.FCStd)"

doc_assy_main.recompute()

# 1.2 Importar Eletronica e Componentes do amemiya_main_node_enclosure existente
doc_src = FreeCAD.openDocument(os.path.join(cad_dir, "amemiya_main_node_enclosure.FCStd"))

# Componentes a serem copiados para o assembly
comp_names = [
    "Suporte_Bateria_Dupla_2x18650", "Celula_18650_A", "Celula_18650_B",
    "Modulo_BMS_Carregador_TP4056", "Placa_PCB_Principal_MainNode",
    "Modulo_ESP32_S3_DevKit", "Modulo_LoRa_Ebyte_E32_TTL_100",
    "Resistores_Limitadores_LEDs_Buzzer", "Conectores_JST_XH_PCB",
    "Conector_GX16_8_Sonda_Torre", "Conector_Jack_P2_Femea_SCT013",
    "Conector_GX12_3_Tacometro", "Chicotes_Fios_Sensores_JST",
    "Display_OLED_096_I2C", "LED_Power_Bateria_Verde",
    "LED_Alerta_Metrologico_Laranja", "LED_Comunicacao_LoRa_Azul",
    "Buzzer_Piezo_Alarme", "Parafusos_M3_Tampa_Superior_ISO7380",
    "Parafusos_M3_Fixacao_PCB_Principal", "Parafusos_M3_Tampa_Bateria_DIN7991",
    "Insertos_Roscados_Latao_M3"
]

for cname in comp_names:
    src_obj = doc_src.getObject(cname)
    if src_obj:
        cp = doc_assy_main.copyObject(src_obj, False)
        # Manter cores
        if hasattr(src_obj, 'ViewObject') and hasattr(src_obj.ViewObject, 'ShapeColor'):
            apply_style(cp, src_obj.ViewObject.ShapeColor,
                        getattr(src_obj.ViewObject, 'Transparency', 0),
                        getattr(src_obj.ViewObject, 'Visibility', True))

doc_assy_main.recompute()
doc_assy_main.save()
print("amemiya_main_node_assembly.FCStd salvo com sucesso com App::Links!")

# ==============================================================================
# 2. MONTAGEM MESTRE DA SONDA TORRE (amemiya_probe_tower_assembly.FCStd)
# ==============================================================================
print("2/2: Construindo amemiya_probe_tower_assembly.FCStd com App::Link...")
assy_tower_path = os.path.join(cad_dir, "amemiya_probe_tower_assembly.FCStd")

doc_t_body = FreeCAD.openDocument(os.path.join(cad_dir, "amemiya_probe_tower_body.FCStd"))
doc_t_cap = FreeCAD.openDocument(os.path.join(cad_dir, "amemiya_probe_tower_cap.FCStd"))
doc_t_src = FreeCAD.openDocument(os.path.join(cad_dir, "amemiya_probe_tower_enclosure.FCStd"))

doc_assy_tower = FreeCAD.newDocument("amemiya_probe_tower_assembly")
doc_assy_tower.saveAs(assy_tower_path)

# Links para as pecas individuais da Torre
link_t_body = doc_assy_tower.addObject("App::Link", "Peca_Corpo_Torre")
link_t_body.setLink(doc_t_body.getObject("Gabinete_Torre_Base"))
link_t_body.Label = "1. Peca: Gabinete Torre Base (amemiya_probe_tower_body.FCStd)"
apply_style(link_t_body, (0.92, 0.48, 0.12), transp=65)

link_t_cap = doc_assy_tower.addObject("App::Link", "Peca_Tampa_Torre")
link_t_cap.setLink(doc_t_cap.getObject("Tampa_Superior_GX16"))
link_t_cap.Label = "2. Peca: Tampa Superior GX16 (amemiya_probe_tower_cap.FCStd)"
apply_style(link_t_cap, (0.32, 0.36, 0.42), transp=50)

doc_assy_tower.recompute()

# Copiar os componentes eletronicos e de fixacao da torre
tower_comps = [
    "Bulbo_Termico_DS18B20", "Terminais_DS18B20", "Capacitor_C1_100nF_DS18B20",
    "Resistor_R1_4k7_Pullup_DS18B20", "Modulo_Acelerometro_ADXL345",
    "Modulo_Microfone_INMP441_Circular", "Conectores_Amarelos_INMP441",
    "Pinos_Metalicos_INMP441", "Conector_JST_XH_Macho_PCB",
    "Conector_JST_XH_Femea_Plugue", "Chicote_Fios_Internos_8Vias",
    "Conector_Aviacao_GX16_8_Vias", "Imas_Neodimio_Base_10mm",
    "Parafusos_Fixacao_M3_Mancal", "Parafusos_Tampa_M2_5"
]

for tc in tower_comps:
    src_obj = doc_t_src.getObject(tc)
    if src_obj:
        cp = doc_assy_tower.copyObject(src_obj, False)
        if hasattr(src_obj, 'ViewObject') and hasattr(src_obj.ViewObject, 'ShapeColor'):
            apply_style(cp, src_obj.ViewObject.ShapeColor,
                        getattr(src_obj.ViewObject, 'Transparency', 0),
                        getattr(src_obj.ViewObject, 'Visibility', True))

# Copiar PCB KiCad
for obj in doc_t_src.Objects:
    if 'Placa' in obj.Label or 'amemiya_probe_tower' in obj.Name:
        try:
            cp = doc_assy_tower.copyObject(obj, False)
            if hasattr(obj, 'ViewObject') and hasattr(obj.ViewObject, 'ShapeColor'):
                apply_style(cp, obj.ViewObject.ShapeColor,
                            getattr(obj.ViewObject, 'Transparency', 0),
                            getattr(obj.ViewObject, 'Visibility', True))
        except Exception: pass

doc_assy_tower.recompute()
doc_assy_tower.save()
print("amemiya_probe_tower_assembly.FCStd salvo com sucesso com App::Links!")

print("\nAssemblies mestres 100% integrados via App::Link aos arquivos de pecas individuais!")
