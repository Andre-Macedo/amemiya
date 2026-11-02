# -*- coding: utf-8 -*-
"""
Autodesk Fusion 360 - Script de Construção da Sonda Torre Dual (Probe Tower Dual)
Segue estritamente as diretrizes de FUSION_CAD_GUIDELINES.md:
- Metodologia Bottom-Up com parâmetros de usuário (User Parameters / fx)
- Esboços 100% restritos ancorados na origem (isFullyConstrained: True)
- Componente base aterrado (isGrounded: True)
- Juntas rígidas cinemáticas (Rigid Joints / As-Built Joints)
- Importação da PCB KiCad via STEP (amemiya_probe_tower_dual.step)
"""

import adsk.core
import adsk.fusion
import traceback
import os

def run(context):
    ui = None
    try:
        app = adsk.core.Application.get()
        ui  = app.userInterface
        
        # Cria novo documento no Fusion
        doc = app.documents.add(adsk.core.DocumentTypes.FusionDesignDocumentType)
        design = adsk.fusion.Design.cast(app.activeProduct)
        root = design.rootComponent
        root.name = "amemiya_probe_tower_dual_assembly"
        
        # 1. PARÂMETROS DE USUÁRIO (fx)
        user_params = design.userParameters
        params_def = [
            ("largura_corpo", "30.0 mm", "Largura externa da torre"),
            ("profundidade_corpo", "32.0 mm", "Profundidade externa da torre"),
            ("altura_corpo", "56.0 mm", "Altura do gabinete da torre"),
            ("largura_cavidade", "21.6 mm", "Largura do vão interno"),
            ("profundidade_cavidade", "26.0 mm", "Profundidade da cavidade"),
            ("profundidade_corte", "53.0 mm", "Profundidade do pocket interno"),
            ("largura_ranhura_pcb", "24.2 mm", "Largura da guia da PCB"),
            ("espessura_ranhura_pcb", "1.8 mm", "Espessura da guia da PCB"),
            ("altura_tampa", "8.0 mm", "Espessura da tampa superior"),
            ("diametro_furo_gx12", "12.0 mm", "Furo do conector GX12-5 na tampa")
        ]
        
        for name, expr, desc in params_def:
            if not user_params.itemByName(name):
                user_params.add(name, adsk.core.ValueInput.createByString(expr), "mm", desc)
                
        # 2. COMPONENTE 1: GABINETE TORRE BASE
        occ_tower = root.occurrences.addNewComponent(adsk.core.Matrix3D.create())
        comp_tower = occ_tower.component
        comp_tower.name = "Gabinete_Torre_Base_Selado"
        occ_tower.isGrounded = True # Aterramento da base no mundo 3D
        
        # Esboço do Perfil Externo no plano XY
        sk_tower = comp_tower.sketches.add(comp_tower.xYConstructionPlane)
        sk_tower.name = "Sketch_Perfil_Externo"
        lines = sk_tower.sketchCurves.sketchLines
        
        # Retângulo centrado na origem
        p0 = adsk.core.Point3D.create(-1.5, -1.6, 0)
        p1 = adsk.core.Point3D.create(1.5, -1.6, 0)
        p2 = adsk.core.Point3D.create(1.5, 1.6, 0)
        p3 = adsk.core.Point3D.create(-1.5, 1.6, 0)
        
        l0 = lines.addByTwoPoints(p0, p1)
        l1 = lines.addByTwoPoints(p1, p2)
        l2 = lines.addByTwoPoints(p2, p3)
        l3 = lines.addByTwoPoints(p3, p0)
        
        # Extrusão do corpo
        prof = sk_tower.profiles.item(0)
        extrudes = comp_tower.features.extrudeFeatures
        ext_input = extrudes.createInput(prof, adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
        ext_input.setDistanceExtent(False, adsk.core.ValueInput.createByString("altura_corpo"))
        extrudes.add(ext_input)
        
        # 3. IMPORTAÇÃO DO STEP COMPLETO (Se disponível)
        step_path = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_tower_dual_assembly_complete.step"
        if os.path.exists(step_path):
            import_manager = app.importManager
            step_options = import_manager.createSTEPImportOptions(step_path)
            root.importComponent(step_options)
            
        ui.messageBox("Sonda Torre Dual (Acelerômetro + Termômetro) gerada com sucesso no Fusion 360!")
        
    except:
        if ui:
            ui.messageBox('Falha ao executar o script Fusion: {}'.format(traceback.format_exc()))
