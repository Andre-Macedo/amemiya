# -*- coding: utf-8 -*-
"""
Construcao Oficial das Montagens Mestres (Assemblies) do Sistema Amemiya:
- amemiya_main_node_assembly.FCStd:
    -> App::Link para amemiya_main_node_base.FCStd (Body_Base)
    -> App::Link para amemiya_main_node_lid.FCStd (Body_Tampa)
    -> App::Link para amemiya_main_node_battery_door.FCStd (Body_Porta_Bateria)
    -> Todos os componentes eletroeletronicos, placas, chicotes, display e parafusos.
- amemiya_probe_tower_assembly.FCStd:
    -> App::Link para amemiya_probe_tower_body.FCStd (Gabinete_Torre_Base)
    -> App::Link para amemiya_probe_tower_cap.FCStd (Tampa_Superior_GX16)
    -> PCB KiCad com 58 furos, sensores ADXL345, INMP441, DS18B20, GX16-8, imas e parafusos.
"""

import FreeCAD
import Part
import os

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
# 1. MONTAGEM MESTRE: MAIN NODE (amemiya_main_node_assembly.FCStd)
# ==============================================================================
print("==========================================================")
print("1/2: Construindo Montagem Mestre do Main Node...")

# 1.1 Carregar pecas individuais
doc_base = FreeCAD.openDocument(os.path.join(cad_dir, "amemiya_main_node_base.FCStd"))
doc_lid = FreeCAD.openDocument(os.path.join(cad_dir, "amemiya_main_node_lid.FCStd"))
doc_door = FreeCAD.openDocument(os.path.join(cad_dir, "amemiya_main_node_battery_door.FCStd"))

# 1.2 Criar novo documento de montagem
assy_main_path = os.path.join(cad_dir, "amemiya_main_node_assembly.FCStd")
doc_assy_main = FreeCAD.newDocument("amemiya_main_node_assembly")
doc_assy_main.saveAs(assy_main_path) # Salvar para habilitar referencias externas

# 1.3 Criar App::Links parametricos para as pecas
grp_mech_main = doc_assy_main.addObject("App::DocumentObjectGroup", "1_Estrutura_Mecanica_Carcaca")
grp_mech_main.Label = "1. Estrutura Mecanica da Carcaca (Pecas Linkadas)"

link_base = doc_assy_main.addObject("App::Link", "Gabinete_Base_Link")
link_base.setLink(doc_base.getObject("Body_Base"))
link_base.Label = "Peca_Base_Gabinete [Link -> amemiya_main_node_base.FCStd]"
grp_mech_main.addObject(link_base)

link_lid = doc_assy_main.addObject("App::Link", "Tampa_Superior_Link")
link_lid.setLink(doc_lid.getObject("Body_Tampa"))
link_lid.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 38.0), FreeCAD.Rotation())
link_lid.Label = "Peca_Tampa_Superior [Link -> amemiya_main_node_lid.FCStd]"
grp_mech_main.addObject(link_lid)

link_door = doc_assy_main.addObject("App::Link", "Tampa_Bateria_Link")
link_door.setLink(doc_door.getObject("Body_Porta_Bateria"))
link_door.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0.0), FreeCAD.Rotation())
link_door.Label = "Peca_Tampa_Bateria [Link -> amemiya_main_node_battery_door.FCStd]"
grp_mech_main.addObject(link_door)

doc_assy_main.recompute()

# 1.4 Copiar componentes eletronicos do amemiya_main_node_enclosure
doc_main_src = FreeCAD.openDocument(os.path.join(cad_dir, "amemiya_main_node_enclosure.FCStd"))

# Grupos tematicos
grp_bat = doc_assy_main.addObject("App::DocumentObjectGroup", "2_Alimentacao_Baterias_BMS")
grp_bat.Label = "2. Alimentacao (2x 18650 e BMS TP4056)"

grp_pcb = doc_assy_main.addObject("App::DocumentObjectGroup", "3_Eletronica_PCB_e_Modulos")
grp_pcb.Label = "3. Eletronica Principal (PCB, ESP32, LoRa)"

grp_conn = doc_assy_main.addObject("App::DocumentObjectGroup", "4_Conectores_e_Chicotes")
grp_conn.Label = "4. Conectores e Chicotes (GX16, GX12, P2, JST)"

grp_ihm = doc_assy_main.addObject("App::DocumentObjectGroup", "5_Interface_IHM_e_Sinalizacao")
grp_ihm.Label = "5. Interface IHM (OLED, LEDs, Buzzer)"

grp_fix = doc_assy_main.addObject("App::DocumentObjectGroup", "6_Elementos_de_Fixacao")
grp_fix.Label = "6. Elementos de Fixacao (Parafusos e Insertos)"

category_mapping = {
    "Suporte_Bateria_Dupla_2x18650": grp_bat,
    "Celula_18650_A": grp_bat,
    "Celula_18650_B": grp_bat,
    "Modulo_BMS_Carregador_TP4056": grp_bat,
    
    "Placa_PCB_Principal_MainNode": grp_pcb,
    "Modulo_ESP32_S3_DevKit": grp_pcb,
    "Modulo_LoRa_Ebyte_E32_TTL_100": grp_pcb,
    "Resistores_Limitadores_LEDs_Buzzer": grp_pcb,
    
    "Conectores_JST_XH_PCB": grp_conn,
    "Conector_GX16_8_Sonda_Torre": grp_conn,
    "Conector_Jack_P2_Femea_SCT013": grp_conn,
    "Conector_GX12_3_Tacometro": grp_conn,
    "Chicotes_Fios_Sensores_JST": grp_conn,
    
    "Display_OLED_096_I2C": grp_ihm,
    "LED_Power_Bateria_Verde": grp_ihm,
    "LED_Alerta_Metrologico_Laranja": grp_ihm,
    "LED_Comunicacao_LoRa_Azul": grp_ihm,
    "Buzzer_Piezo_Alarme": grp_ihm,
    
    "Parafusos_M3_Tampa_Superior_ISO7380": grp_fix,
    "Parafusos_M3_Fixacao_PCB_Principal": grp_fix,
    "Parafusos_M3_Tampa_Bateria_DIN7991": grp_fix,
    "Insertos_Roscados_Latao_M3": grp_fix
}

for comp_name, target_grp in category_mapping.items():
    src_obj = doc_main_src.getObject(comp_name)
    if src_obj:
        cp = doc_assy_main.copyObject(src_obj, False)
        target_grp.addObject(cp)
        if hasattr(src_obj, 'ViewObject') and hasattr(src_obj.ViewObject, 'ShapeColor'):
            apply_style(cp, src_obj.ViewObject.ShapeColor,
                        getattr(src_obj.ViewObject, 'Transparency', 0),
                        getattr(src_obj.ViewObject, 'Visibility', True))

doc_assy_main.recompute()
doc_assy_main.save()
print("Montagem do Main Node finalizada e salva em:", assy_main_path)

# ==============================================================================
# 2. MONTAGEM MESTRE: SONDA TORRE (amemiya_probe_tower_assembly.FCStd)
# ==============================================================================
print("\n==========================================================")
print("2/2: Construindo Montagem Mestre da Sonda Torre...")

doc_t_body = FreeCAD.openDocument(os.path.join(cad_dir, "amemiya_probe_tower_body.FCStd"))
doc_t_cap = FreeCAD.openDocument(os.path.join(cad_dir, "amemiya_probe_tower_cap.FCStd"))
doc_t_src = FreeCAD.openDocument(os.path.join(cad_dir, "amemiya_probe_tower_enclosure.FCStd"))

assy_tower_path = os.path.join(cad_dir, "amemiya_probe_tower_assembly.FCStd")
doc_assy_tower = FreeCAD.newDocument("amemiya_probe_tower_assembly")
doc_assy_tower.saveAs(assy_tower_path)

# 2.1 Links parametricos para pecas da Torre
grp_t_mech = doc_assy_tower.addObject("App::DocumentObjectGroup", "1_Estrutura_Mecanica_Torre")
grp_t_mech.Label = "1. Estrutura Mecanica da Torre (Pecas Linkadas)"

link_t_body = doc_assy_tower.addObject("App::Link", "Corpo_Torre_Link")
link_t_body.setLink(doc_t_body.getObject("Gabinete_Torre_Base"))
link_t_body.Label = "Peca_Gabinete_Torre [Link -> amemiya_probe_tower_body.FCStd]"
apply_style(link_t_body, (0.92, 0.48, 0.12), transp=65)
grp_t_mech.addObject(link_t_body)

link_t_cap = doc_assy_tower.addObject("App::Link", "Tampa_Torre_Link")
link_t_cap.setLink(doc_t_cap.getObject("Tampa_Superior_GX16"))
link_t_cap.Label = "Peca_Tampa_Superior_GX16 [Link -> amemiya_probe_tower_cap.FCStd]"
apply_style(link_t_cap, (0.32, 0.36, 0.42), transp=50)
grp_t_mech.addObject(link_t_cap)

doc_assy_tower.recompute()

# 2.2 Copiar eletronica e sensores
grp_t_elec = doc_assy_tower.addObject("App::DocumentObjectGroup", "2_Eletronica_e_Sensores")
grp_t_elec.Label = "2. Eletronica e Sensores (PCB, ADXL, Mic, DS18B20)"

grp_t_fix = doc_assy_tower.addObject("App::DocumentObjectGroup", "3_Fixadores_e_Imas")
grp_t_fix.Label = "3. Fixadores, Imas e Conectores"

tower_elec_mapping = {
    "Bulbo_Termico_DS18B20": grp_t_elec,
    "Terminais_DS18B20": grp_t_elec,
    "Capacitor_C1_100nF_DS18B20": grp_t_elec,
    "Resistor_R1_4k7_Pullup_DS18B20": grp_t_elec,
    "Modulo_Acelerometro_ADXL345": grp_t_elec,
    "Modulo_Microfone_INMP441_Circular": grp_t_elec,
    "Conectores_Amarelos_INMP441": grp_t_elec,
    "Pinos_Metalicos_INMP441": grp_t_elec,
    "Conector_JST_XH_Macho_PCB": grp_t_elec,
    "Conector_JST_XH_Femea_Plugue": grp_t_elec,
    "Chicote_Fios_Internos_8Vias": grp_t_elec,
    
    "Conector_Aviacao_GX16_8_Vias": grp_t_fix,
    "Imas_Neodimio_Base_10mm": grp_t_fix,
    "Parafusos_Fixacao_M3_Mancal": grp_t_fix,
    "Parafusos_Tampa_M2_5": grp_t_fix
}

for tname, target_grp in tower_elec_mapping.items():
    src_obj = doc_t_src.getObject(tname)
    if src_obj:
        cp = doc_assy_tower.copyObject(src_obj, False)
        target_grp.addObject(cp)
        if hasattr(src_obj, 'ViewObject') and hasattr(src_obj.ViewObject, 'ShapeColor'):
            apply_style(cp, src_obj.ViewObject.ShapeColor,
                        getattr(src_obj.ViewObject, 'Transparency', 0),
                        getattr(src_obj.ViewObject, 'Visibility', True))

# Copiar Placa KiCad
for obj in doc_t_src.Objects:
    if 'Placa' in obj.Label or 'amemiya_probe_tower' in obj.Name:
        try:
            cp = doc_assy_tower.copyObject(obj, False)
            grp_t_elec.addObject(cp)
            if hasattr(obj, 'ViewObject') and hasattr(obj.ViewObject, 'ShapeColor'):
                apply_style(cp, obj.ViewObject.ShapeColor,
                            getattr(obj.ViewObject, 'Transparency', 0),
                            getattr(obj.ViewObject, 'Visibility', True))
        except Exception: pass

doc_assy_tower.recompute()
doc_assy_tower.save()
print("Montagem da Sonda Torre finalizada e salva em:", assy_tower_path)

print("\n==========================================================")
print("Todas as Montagens Mestres foram criadas com Sucesso via App::Link!")
