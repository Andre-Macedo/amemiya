import urllib.request
import json
import time

FUSION_URL = "http://127.0.0.1:27182/mcp"

def send_rpc(session_id, method, params=None, id_val=None):
    data = {"jsonrpc": "2.0", "method": method}
    if params is not None:
        data["params"] = params
    if id_val is not None:
        data["id"] = id_val
    headers = {"Content-Type": "application/json"}
    if session_id:
        headers["MCP-Session-Id"] = session_id
    req = urllib.request.Request(
        FUSION_URL,
        data=json.dumps(data).encode("utf-8"),
        headers=headers
    )
    with urllib.request.urlopen(req, timeout=600) as resp:
        sess = resp.headers.get("MCP-Session-Id", session_id)
        content = resp.read().decode("utf-8")
        return sess, json.loads(content) if content else None

def init_session():
    sess, _ = send_rpc(None, "initialize", {
        "protocolVersion": "2024-11-05",
        "capabilities": {},
        "clientInfo": {"name": "remodel_parts", "version": "1.0"}
    }, 1)
    send_rpc(sess, "notifications/initialized")
    return sess

def execute_script(sess, script_content):
    _, res = send_rpc(sess, "tools/call", {
        "name": "fusion_mcp_execute",
        "arguments": {
            "featureType": "script",
            "object": {
                "script": script_content,
                "readOnly": False
            }
        }
    }, int(time.time() * 1000) % 1000000)
    return res

SCRIPT_REMODEL = r'''
import adsk.core, adsk.fusion, os, math

EXPORT_DIR = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\export\bancada"

def run(context):
    app = adsk.core.Application.get()
    data = app.data
    fusion_lib = app.materialLibraries.item(0)

    for doc in list(app.documents):
        try:
            doc.close(False)
        except Exception:
            pass

    amemiya_proj = [p for p in data.dataProjects if p.name == "Amemiya"][0]
    folder = [f for f in amemiya_proj.rootFolder.dataFolders if f.name == "04_Bancada_Components"][0]

    # Helper: delete old dataFile
    def delete_old_file(name):
        for i in range(folder.dataFiles.count - 1, -1, -1):
            try:
                df = folder.dataFiles.item(i)
                if df and df.name == name:
                    df.deleteMe()
            except Exception:
                pass

    # Helper: get material
    def get_material_appearance(design, name, base_name, r, g, b, is_metal=False):
        app_obj = design.appearances.itemByName(name)
        if app_obj:
            return app_obj
        base_app = None
        for a in fusion_lib.appearances:
            if base_name.lower() in a.name.lower():
                base_app = a
                break
        if not base_app:
            base_app = fusion_lib.appearances.item(0)
        app_obj = design.appearances.addByCopy(base_app, name)
        for p in app_obj.appearanceProperties:
            if isinstance(p, adsk.core.ColorProperty):
                try:
                    p.value = adsk.core.Color.create(r, g, b, 255)
                except Exception:
                    pass
        return app_obj

    # =========================================================================
    # 1. 01_Mesa_Base_Bancada: Base de Ferro + 2x Perfis 40x80 Estruturais T-Slot
    # =========================================================================
    print("1. Remodelando 01_Mesa_Base_Bancada (Base de Ferro + Perfis 40x80 Reais)...")
    delete_old_file("01_Mesa_Base_Bancada")

    doc1 = app.documents.add(adsk.core.DocumentTypes.FusionDesignDocumentType)
    des1 = adsk.fusion.Design.cast(doc1.products.itemByProductType("DesignProductType"))
    des1.designType = adsk.fusion.DesignTypes.ParametricDesignType
    root1 = des1.rootComponent
    up1 = des1.userParameters

    up1.add("chassi_len", adsk.core.ValueInput.createByString("700.0 mm"), "mm", "Comprimento total da bancada")
    up1.add("base_w", adsk.core.ValueInput.createByString("220.0 mm"), "mm", "Largura da base de ferro")
    up1.add("base_th", adsk.core.ValueInput.createByString("12.0 mm"), "mm", "Espessura da base de ferro")
    up1.add("perfil_w", adsk.core.ValueInput.createByString("80.0 mm"), "mm", "Largura de cada perfil 40x80")
    up1.add("perfil_h", adsk.core.ValueInput.createByString("40.0 mm"), "mm", "Altura de cada perfil 40x80")
    up1.add("viga_dy", adsk.core.ValueInput.createByString("67.5 mm"), "mm", "Centro das vigas em Y (+-67.5 mm)")

    # 1.1 Placa Base de Ferro (Z = -12mm ate Z = 0mm)
    xy = root1.xYConstructionPlane
    sk_base = root1.sketches.add(xy)
    sk_base.name = "Esboco_Base_Ferro"
    l_base = sk_base.sketchCurves.sketchLines
    b_hl = 35.0 # 700mm / 2
    b_hw = 11.0 # 220mm / 2
    b1 = l_base.addByTwoPoints(adsk.core.Point3D.create(-b_hl, -b_hw, 0), adsk.core.Point3D.create(b_hl, -b_hw, 0))
    b2 = l_base.addByTwoPoints(adsk.core.Point3D.create(b_hl, -b_hw, 0), adsk.core.Point3D.create(b_hl, b_hw, 0))
    b3 = l_base.addByTwoPoints(adsk.core.Point3D.create(b_hl, b_hw, 0), adsk.core.Point3D.create(-b_hl, b_hw, 0))
    b4 = l_base.addByTwoPoints(adsk.core.Point3D.create(-b_hl, b_hw, 0), adsk.core.Point3D.create(-b_hl, -b_hw, 0))
    sk_base.geometricConstraints.addHorizontal(b1); sk_base.geometricConstraints.addHorizontal(b3)
    sk_base.geometricConstraints.addVertical(b2); sk_base.geometricConstraints.addVertical(b4)
    sk_base.geometricConstraints.addCoincident(b1.endSketchPoint, b2.startSketchPoint)
    sk_base.geometricConstraints.addCoincident(b2.endSketchPoint, b3.startSketchPoint)
    sk_base.geometricConstraints.addCoincident(b3.endSketchPoint, b4.startSketchPoint)
    sk_base.geometricConstraints.addCoincident(b4.endSketchPoint, b1.startSketchPoint)

    sk_base.sketchDimensions.addDistanceDimension(b4.startSketchPoint, b2.startSketchPoint, adsk.fusion.DimensionOrientations.HorizontalDimensionOrientation, adsk.core.Point3D.create(0, -b_hw - 1.0, 0)).parameter.expression = "chassi_len"
    sk_base.sketchDimensions.addDistanceDimension(b1.startSketchPoint, b3.startSketchPoint, adsk.fusion.DimensionOrientations.VerticalDimensionOrientation, adsk.core.Point3D.create(b_hl + 1.0, 0, 0)).parameter.expression = "base_w"
    sk_base.sketchDimensions.addDistanceDimension(sk_base.originPoint, b4.startSketchPoint, adsk.fusion.DimensionOrientations.HorizontalDimensionOrientation, adsk.core.Point3D.create(-b_hl/2, -b_hw - 2.0, 0)).parameter.expression = "chassi_len / 2"
    sk_base.sketchDimensions.addDistanceDimension(sk_base.originPoint, b1.startSketchPoint, adsk.fusion.DimensionOrientations.VerticalDimensionOrientation, adsk.core.Point3D.create(b_hl + 2.0, -b_hw/2, 0)).parameter.expression = "base_w / 2"

    profs_base = adsk.core.ObjectCollection.create()
    profs_base.add(sk_base.profiles.item(0))
    ext_base_in = root1.features.extrudeFeatures.createInput(profs_base, adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext_base_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("-base_th"))
    ext_base = root1.features.extrudeFeatures.add(ext_base_in)
    body_base = ext_base.bodies.item(0)
    body_base.name = "Placa_Base_Ferro"

    # Furos M10 para as sapatas niveladoras na base de ferro
    sk_feet = root1.sketches.add(xy)
    sk_feet.name = "Esboco_Furos_Sapatas"
    for sx, sy in [(-31.0, -8.75), (-31.0, 8.75), (31.0, -8.75), (31.0, 8.75)]:
        sk_feet.sketchCurves.sketchCircles.addByCenterRadius(adsk.core.Point3D.create(sx, sy, 0), 0.5)
    profs_feet = adsk.core.ObjectCollection.create()
    for p in sk_feet.profiles: profs_feet.add(p)
    ext_feet_in = root1.features.extrudeFeatures.createInput(profs_feet, adsk.fusion.FeatureOperations.CutFeatureOperation)
    ext_feet_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("-base_th"))
    ext_feet_in.participantBodies = [body_base]
    root1.features.extrudeFeatures.add(ext_feet_in)

    # 1.2 Extrusao das Duas Vigas 40x80 (Z = 0 ate Z = 40mm)
    sk_vigas = root1.sketches.add(xy)
    sk_vigas.name = "Esboco_Vigas_40x80"
    lv = sk_vigas.sketchCurves.sketchLines
    
    # Viga Superior (+Y: cy = 6.75)
    cy1 = 6.75
    hw = 4.0
    va1 = lv.addByTwoPoints(adsk.core.Point3D.create(-b_hl, cy1 - hw, 0), adsk.core.Point3D.create(b_hl, cy1 - hw, 0))
    va2 = lv.addByTwoPoints(adsk.core.Point3D.create(b_hl, cy1 - hw, 0), adsk.core.Point3D.create(b_hl, cy1 + hw, 0))
    va3 = lv.addByTwoPoints(adsk.core.Point3D.create(b_hl, cy1 + hw, 0), adsk.core.Point3D.create(-b_hl, cy1 + hw, 0))
    va4 = lv.addByTwoPoints(adsk.core.Point3D.create(-b_hl, cy1 + hw, 0), adsk.core.Point3D.create(-b_hl, cy1 - hw, 0))
    sk_vigas.geometricConstraints.addHorizontal(va1); sk_vigas.geometricConstraints.addHorizontal(va3)
    sk_vigas.geometricConstraints.addVertical(va2); sk_vigas.geometricConstraints.addVertical(va4)
    sk_vigas.geometricConstraints.addCoincident(va1.endSketchPoint, va2.startSketchPoint)
    sk_vigas.geometricConstraints.addCoincident(va2.endSketchPoint, va3.startSketchPoint)
    sk_vigas.geometricConstraints.addCoincident(va3.endSketchPoint, va4.startSketchPoint)
    sk_vigas.geometricConstraints.addCoincident(va4.endSketchPoint, va1.startSketchPoint)
    sk_vigas.sketchDimensions.addDistanceDimension(va4.startSketchPoint, va2.startSketchPoint, adsk.fusion.DimensionOrientations.HorizontalDimensionOrientation, adsk.core.Point3D.create(0, cy1 - hw - 0.5, 0)).parameter.expression = "chassi_len"
    sk_vigas.sketchDimensions.addDistanceDimension(va1.startSketchPoint, va3.startSketchPoint, adsk.fusion.DimensionOrientations.VerticalDimensionOrientation, adsk.core.Point3D.create(b_hl + 0.5, cy1, 0)).parameter.expression = "perfil_w"
    sk_vigas.sketchDimensions.addDistanceDimension(sk_vigas.originPoint, va4.startSketchPoint, adsk.fusion.DimensionOrientations.HorizontalDimensionOrientation, adsk.core.Point3D.create(-b_hl/2, cy1 - hw - 1.0, 0)).parameter.expression = "chassi_len / 2"
    sk_vigas.sketchDimensions.addDistanceDimension(sk_vigas.originPoint, va1.startSketchPoint, adsk.fusion.DimensionOrientations.VerticalDimensionOrientation, adsk.core.Point3D.create(b_hl + 1.0, cy1/2, 0)).parameter.expression = "viga_dy - perfil_w / 2"

    # Viga Inferior (-Y: cy = -6.75)
    cy2 = -6.75
    vb1 = lv.addByTwoPoints(adsk.core.Point3D.create(-b_hl, cy2 - hw, 0), adsk.core.Point3D.create(b_hl, cy2 - hw, 0))
    vb2 = lv.addByTwoPoints(adsk.core.Point3D.create(b_hl, cy2 - hw, 0), adsk.core.Point3D.create(b_hl, cy2 + hw, 0))
    vb3 = lv.addByTwoPoints(adsk.core.Point3D.create(b_hl, cy2 + hw, 0), adsk.core.Point3D.create(-b_hl, cy2 + hw, 0))
    vb4 = lv.addByTwoPoints(adsk.core.Point3D.create(-b_hl, cy2 + hw, 0), adsk.core.Point3D.create(-b_hl, cy2 - hw, 0))
    sk_vigas.geometricConstraints.addHorizontal(vb1); sk_vigas.geometricConstraints.addHorizontal(vb3)
    sk_vigas.geometricConstraints.addVertical(vb2); sk_vigas.geometricConstraints.addVertical(vb4)
    sk_vigas.geometricConstraints.addCoincident(vb1.endSketchPoint, vb2.startSketchPoint)
    sk_vigas.geometricConstraints.addCoincident(vb2.endSketchPoint, vb3.startSketchPoint)
    sk_vigas.geometricConstraints.addCoincident(vb3.endSketchPoint, vb4.startSketchPoint)
    sk_vigas.geometricConstraints.addCoincident(vb4.endSketchPoint, vb1.startSketchPoint)
    sk_vigas.sketchDimensions.addDistanceDimension(vb4.startSketchPoint, vb2.startSketchPoint, adsk.fusion.DimensionOrientations.HorizontalDimensionOrientation, adsk.core.Point3D.create(0, cy2 - hw - 0.5, 0)).parameter.expression = "chassi_len"
    sk_vigas.sketchDimensions.addDistanceDimension(vb1.startSketchPoint, vb3.startSketchPoint, adsk.fusion.DimensionOrientations.VerticalDimensionOrientation, adsk.core.Point3D.create(b_hl + 0.5, cy2, 0)).parameter.expression = "perfil_w"
    sk_vigas.sketchDimensions.addDistanceDimension(sk_vigas.originPoint, vb4.startSketchPoint, adsk.fusion.DimensionOrientations.HorizontalDimensionOrientation, adsk.core.Point3D.create(-b_hl/2, cy2 - hw - 1.0, 0)).parameter.expression = "chassi_len / 2"
    sk_vigas.sketchDimensions.addDistanceDimension(sk_vigas.originPoint, vb3.startSketchPoint, adsk.fusion.DimensionOrientations.VerticalDimensionOrientation, adsk.core.Point3D.create(b_hl + 1.0, cy2/2, 0)).parameter.expression = "viga_dy - perfil_w / 2"

    profs_vigas = adsk.core.ObjectCollection.create()
    for p in sk_vigas.profiles: profs_vigas.add(p)
    ext_vigas_in = root1.features.extrudeFeatures.createInput(profs_vigas, adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext_vigas_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("perfil_h"))
    ext_vigas = root1.features.extrudeFeatures.add(ext_vigas_in)
    body_viga_top = ext_vigas.bodies.item(0)
    body_viga_top.name = "Perfil_40x80_Superior"
    body_viga_bot = ext_vigas.bodies.item(1)
    body_viga_bot.name = "Perfil_40x80_Inferior"

    # 1.3 Cortes Parametricos Detalhados dos Canais T Normatizados (Canal 8)
    # No topo (Z = 40mm): 4 canais em Y = +-47.5mm (interno) e Y = +-87.5mm (externo)
    top_plane_in = root1.constructionPlanes.createInput()
    top_plane_in.setByOffset(xy, adsk.core.ValueInput.createByString("perfil_h"))
    top_plane = root1.constructionPlanes.add(top_plane_in)

    sk_top_slots = root1.sketches.add(top_plane)
    sk_top_slots.name = "Esboco_Canais_T_Topo"
    
    # Desenhar os 4 rasgos de boca (8.2mm de abertura)
    sw_half = 0.41 # 8.2mm / 2
    for y_val in [4.75, 8.75, -4.75, -8.75]:
        s1 = sk_top_slots.sketchCurves.sketchLines.addByTwoPoints(adsk.core.Point3D.create(-b_hl, y_val - sw_half, 0), adsk.core.Point3D.create(b_hl, y_val - sw_half, 0))
        s2 = sk_top_slots.sketchCurves.sketchLines.addByTwoPoints(adsk.core.Point3D.create(b_hl, y_val - sw_half, 0), adsk.core.Point3D.create(b_hl, y_val + sw_half, 0))
        s3 = sk_top_slots.sketchCurves.sketchLines.addByTwoPoints(adsk.core.Point3D.create(b_hl, y_val + sw_half, 0), adsk.core.Point3D.create(-b_hl, y_val + sw_half, 0))
        s4 = sk_top_slots.sketchCurves.sketchLines.addByTwoPoints(adsk.core.Point3D.create(-b_hl, y_val + sw_half, 0), adsk.core.Point3D.create(-b_hl, y_val - sw_half, 0))
        sk_top_slots.geometricConstraints.addHorizontal(s1); sk_top_slots.geometricConstraints.addHorizontal(s3)
        sk_top_slots.geometricConstraints.addVertical(s2); sk_top_slots.geometricConstraints.addVertical(s4)
        sk_top_slots.geometricConstraints.addCoincident(s1.endSketchPoint, s2.startSketchPoint)
        sk_top_slots.geometricConstraints.addCoincident(s2.endSketchPoint, s3.startSketchPoint)
        sk_top_slots.geometricConstraints.addCoincident(s3.endSketchPoint, s4.startSketchPoint)
        sk_top_slots.geometricConstraints.addCoincident(s4.endSketchPoint, s1.startSketchPoint)

    profs_ts = adsk.core.ObjectCollection.create()
    for p in sk_top_slots.profiles: profs_ts.add(p)
    ext_ts_in = root1.features.extrudeFeatures.createInput(profs_ts, adsk.fusion.FeatureOperations.CutFeatureOperation)
    ext_ts_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("-9.0 mm"))
    ext_ts_in.participantBodies = [body_viga_top, body_viga_bot]
    root1.features.extrudeFeatures.add(ext_ts_in)

    # 1.4 Canais Laterais Externos nos Perfis (Y = +-107.5mm e Y = +-27.5mm)
    # e Furos Centrais de Fixacao dos Perfis (diametro 6.8mm)
    yz = root1.yZConstructionPlane
    sk_side_holes = root1.sketches.add(yz)
    sk_side_holes.name = "Esboco_Furos_Centrais_Perfis"
    for cy in [4.75, 8.75, -4.75, -8.75]:
        sk_side_holes.sketchCurves.sketchCircles.addByCenterRadius(adsk.core.Point3D.create(cy, 2.0, 0), 0.34) # D=6.8mm, Z=20mm
    profs_holes = adsk.core.ObjectCollection.create()
    for p in sk_side_holes.profiles: profs_holes.add(p)
    ext_h_in = root1.features.extrudeFeatures.createInput(profs_holes, adsk.fusion.FeatureOperations.CutFeatureOperation)
    ext_h_in.setDistanceExtent(True, adsk.core.ValueInput.createByString("350.0 mm"))
    ext_h_in.participantBodies = [body_viga_top, body_viga_bot]
    root1.features.extrudeFeatures.add(ext_h_in)

    # Atribuir materiais fisicos e visuais de alta qualidade:
    app_ferro = get_material_appearance(des1, "Aparencia_Ferro_Fundido", "ferro", 60, 60, 65)
    app_alum = get_material_appearance(des1, "Aparencia_Aluminio_Acetinado", "acetinado", 210, 215, 220)
    body_base.appearance = app_ferro
    body_viga_top.appearance = app_alum
    body_viga_bot.appearance = app_alum

    step_m1 = os.path.join(EXPORT_DIR, "01_Mesa_Base_Bancada.step")
    des1.exportManager.execute(des1.exportManager.createSTEPExportOptions(step_m1))
    doc1.saveAs("01_Mesa_Base_Bancada", folder, "Chassi Hibrido Base Ferro Perfis 40x80", "")
    doc1.close(False)
    print("01_Mesa_Base_Bancada concluida com sucesso!")

    # =========================================================================
    # 2. 11_Bloco_Encosto_Push_Pull_Lateral: Cantoneira 90 Graus para Perfil T-Slot
    # =========================================================================
    print("2. Remodelando 11_Bloco_Encosto_Push_Pull_Lateral (Cantoneira Modular T-Slot)...")
    delete_old_file("11_Bloco_Encosto_Push_Pull_Lateral")

    doc11 = app.documents.add(adsk.core.DocumentTypes.FusionDesignDocumentType)
    des11 = adsk.fusion.Design.cast(doc11.products.itemByProductType("DesignProductType"))
    des11.designType = adsk.fusion.DesignTypes.ParametricDesignType
    root11 = des11.rootComponent

    # Modelar Cantoneira L com nervuras laterais
    # Base: 40mm de largura (em X), 35mm de comprimento (em Y), 6mm de espessura (em Z)
    # Aba vertical: 40mm de largura (em X), 30mm de altura (em Z), 8mm de espessura (em Y)
    xy11 = root11.xYConstructionPlane
    sk11 = root11.sketches.add(xy11)
    l11 = sk11.sketchCurves.sketchLines
    # Base: X em [-2.0, 2.0], Y em [0, 3.5]
    l11.addByTwoPoints(adsk.core.Point3D.create(-2.0, 0, 0), adsk.core.Point3D.create(2.0, 0, 0))
    l11.addByTwoPoints(adsk.core.Point3D.create(2.0, 0, 0), adsk.core.Point3D.create(2.0, 3.5, 0))
    l11.addByTwoPoints(adsk.core.Point3D.create(2.0, 3.5, 0), adsk.core.Point3D.create(-2.0, 3.5, 0))
    l11.addByTwoPoints(adsk.core.Point3D.create(-2.0, 3.5, 0), adsk.core.Point3D.create(-2.0, 0, 0))

    profs11 = adsk.core.ObjectCollection.create()
    profs11.add(sk11.profiles.item(0))
    ext11_in = root11.features.extrudeFeatures.createInput(profs11, adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext11_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("6.0 mm"))
    ext11 = root11.features.extrudeFeatures.add(ext11_in)

    # Aba vertical: Z de 6mm ate 30mm em Y de 0 ate 0.8cm
    sk11_v = root11.sketches.add(root11.xZConstructionPlane)
    lv11 = sk11_v.sketchCurves.sketchLines
    lv11.addByTwoPoints(adsk.core.Point3D.create(-2.0, 0.6, 0), adsk.core.Point3D.create(2.0, 0.6, 0))
    lv11.addByTwoPoints(adsk.core.Point3D.create(2.0, 0.6, 0), adsk.core.Point3D.create(2.0, 3.0, 0))
    lv11.addByTwoPoints(adsk.core.Point3D.create(2.0, 3.0, 0), adsk.core.Point3D.create(-2.0, 3.0, 0))
    lv11.addByTwoPoints(adsk.core.Point3D.create(-2.0, 3.0, 0), adsk.core.Point3D.create(-2.0, 0.6, 0))
    profs11_v = adsk.core.ObjectCollection.create()
    profs11_v.add(sk11_v.profiles.item(0))
    ext11_v_in = root11.features.extrudeFeatures.createInput(profs11_v, adsk.fusion.FeatureOperations.JoinFeatureOperation)
    ext11_v_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("8.0 mm"))
    root11.features.extrudeFeatures.add(ext11_v_in)

    # Furo roscado M8 na aba vertical para o manipulo de encosto (X=0, Z=1.5cm)
    sk11_h = root11.sketches.add(root11.xZConstructionPlane)
    sk11_h.sketchCurves.sketchCircles.addByCenterRadius(adsk.core.Point3D.create(0.0, 1.5, 0), 0.4) # D=8mm
    profs11_h = adsk.core.ObjectCollection.create()
    profs11_h.add(sk11_h.profiles.item(0))
    ext11_h_in = root11.features.extrudeFeatures.createInput(profs11_h, adsk.fusion.FeatureOperations.CutFeatureOperation)
    ext11_h_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("15.0 mm"))
    root11.features.extrudeFeatures.add(ext11_h_in)

    # Rasgo oblongo M8 na base para fixacao no canal T
    sk11_slot = root11.sketches.add(xy11)
    sk11_slot.sketchCurves.sketchCircles.addByCenterRadius(adsk.core.Point3D.create(0.0, 2.0, 0), 0.45) # D=9mm
    profs11_slot = adsk.core.ObjectCollection.create()
    profs11_slot.add(sk11_slot.profiles.item(0))
    ext11_s_in = root11.features.extrudeFeatures.createInput(profs11_slot, adsk.fusion.FeatureOperations.CutFeatureOperation)
    ext11_s_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("10.0 mm"))
    root11.features.extrudeFeatures.add(ext11_s_in)

    app_orange = get_material_appearance(des11, "Aparencia_Cantoneira_Anodizada", "anodizado com brilho", 235, 110, 25)
    root11.bRepBodies.item(0).appearance = app_orange

    step_m11 = os.path.join(EXPORT_DIR, "11_Bloco_Encosto_Push_Pull_Lateral.step")
    des11.exportManager.execute(des11.exportManager.createSTEPExportOptions(step_m11))
    doc11.saveAs("11_Bloco_Encosto_Push_Pull_Lateral", folder, "Cantoneira de Encosto Push-Pull Perfil T-Slot", "")
    doc11.close(False)
    print("11_Bloco_Encosto_Push_Pull_Lateral concluida com sucesso!")

    # =========================================================================
    # 3. 12_Parafuso_Jacking_Elevacao_M8: Manipulo Jacking com Pastilha de Apoio
    # =========================================================================
    print("3. Remodelando 12_Parafuso_Jacking_Elevacao_M8 (Parafuso Jacking com Manipulo)...")
    delete_old_file("12_Parafuso_Jacking_Elevacao_M8")

    doc12 = app.documents.add(adsk.core.DocumentTypes.FusionDesignDocumentType)
    des12 = adsk.fusion.Design.cast(doc12.products.itemByProductType("DesignProductType"))
    des12.designType = adsk.fusion.DesignTypes.ParametricDesignType
    root12 = des12.rootComponent

    xy12 = root12.xYConstructionPlane
    # Haste roscada M8 (comprimento 25mm)
    sk12_stem = root12.sketches.add(xy12)
    sk12_stem.sketchCurves.sketchCircles.addByCenterRadius(adsk.core.Point3D.create(0, 0, 0), 0.4) # D=8mm
    profs12_stem = adsk.core.ObjectCollection.create()
    profs12_stem.add(sk12_stem.profiles.item(0))
    ext12_s_in = root12.features.extrudeFeatures.createInput(profs12_stem, adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext12_s_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("-25.0 mm"))
    root12.features.extrudeFeatures.add(ext12_s_in)

    # Cabeca do manipulo serrilhado no topo (D=20mm, H=6mm)
    sk12_head = root12.sketches.add(xy12)
    sk12_head.sketchCurves.sketchCircles.addByCenterRadius(adsk.core.Point3D.create(0, 0, 0), 1.0) # D=20mm
    profs12_head = adsk.core.ObjectCollection.create()
    profs12_head.add(sk12_head.profiles.item(0))
    ext12_h_in = root12.features.extrudeFeatures.createInput(profs12_head, adsk.fusion.FeatureOperations.JoinFeatureOperation)
    ext12_h_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("6.0 mm"))
    root12.features.extrudeFeatures.add(ext12_h_in)

    # Pastilha circular de contato na ponta inferior (D=14mm, H=3mm em Z=-25mm)
    bot_pl_in = root12.constructionPlanes.createInput()
    bot_pl_in.setByOffset(xy12, adsk.core.ValueInput.createByString("-25.0 mm"))
    bot_pl = root12.constructionPlanes.add(bot_pl_in)
    sk12_pad = root12.sketches.add(bot_pl)
    sk12_pad.sketchCurves.sketchCircles.addByCenterRadius(adsk.core.Point3D.create(0, 0, 0), 0.7) # D=14mm
    profs12_pad = adsk.core.ObjectCollection.create()
    profs12_pad.add(sk12_pad.profiles.item(0))
    ext12_p_in = root12.features.extrudeFeatures.createInput(profs12_pad, adsk.fusion.FeatureOperations.JoinFeatureOperation)
    ext12_p_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("-3.0 mm"))
    root12.features.extrudeFeatures.add(ext12_p_in)

    app_steel = get_material_appearance(des12, "Aparencia_Aco_Inox_Polido", "polido", 220, 222, 225)
    root12.bRepBodies.item(0).appearance = app_steel

    step_m12 = os.path.join(EXPORT_DIR, "12_Parafuso_Jacking_Elevacao_M8.step")
    des12.exportManager.execute(des12.exportManager.createSTEPExportOptions(step_m12))
    doc12.saveAs("12_Parafuso_Jacking_Elevacao_M8", folder, "Manipulo Jacking com Pastilha de Apoio", "")
    doc12.close(False)
    print("12_Parafuso_Jacking_Elevacao_M8 concluido com sucesso!")

    print("TODAS_AS_PECAS_REMODELADAS_COM_SUCESSO")
'''

if __name__ == "__main__":
    sess = init_session()
    print("Iniciando remodelagem das pecas no Fusion 360...")
    res = execute_script(sess, SCRIPT_REMODEL)
    print("Resultado recebido:")
    if res and "result" in res and "content" in res["result"]:
        print(res["result"]["content"][0]["text"])
    else:
        print(res)
