import urllib.request
import json
import time
import os

FUSION_URL = "http://127.0.0.1:27182/mcp"
EXPORT_DIR = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\export\bancada"
ARTIFACTS_DIR = r"C:\Users\andrl\.gemini\antigravity-cli\brain\efd833bf-6ba1-4a1b-a7ce-aa9aa87aa86c"

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
        "clientInfo": {"name": "remodel_lateral_system", "version": "1.0"}
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
import adsk.core, adsk.fusion, os, math, time

EXPORT_DIR = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\export\bancada"
ARTIFACTS_DIR = r"C:\Users\andrl\.gemini\antigravity-cli\brain\efd833bf-6ba1-4a1b-a7ce-aa9aa87aa86c"

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

    def delete_old_file(target_folder, name):
        for i in range(target_folder.dataFiles.count - 1, -1, -1):
            try:
                df = target_folder.dataFiles.item(i)
                if df and df.name == name:
                    df.deleteMe()
            except Exception:
                pass

    def get_mat_app(design, name, base_keyword, r, g, b):
        app_obj = design.appearances.itemByName(name)
        if app_obj:
            return app_obj
        base_app = None
        for a in fusion_lib.appearances:
            if base_keyword.lower() in a.name.lower():
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
    # 1. 03_Placa_Base_Desalinhamento_Motor (230 x 160 x 15 mm)
    # Totalmente livre da carcaça do motor e com jacking no centro do perfil!
    # =========================================================================
    print("1. Remodelando 03_Placa_Base_Desalinhamento_Motor ampliada...")
    delete_old_file(folder, "03_Placa_Base_Desalinhamento_Motor")

    doc3 = app.documents.add(adsk.core.DocumentTypes.FusionDesignDocumentType)
    des3 = adsk.fusion.Design.cast(doc3.products.itemByProductType("DesignProductType"))
    des3.designType = adsk.fusion.DesignTypes.ParametricDesignType
    root3 = des3.rootComponent
    up3 = des3.userParameters

    up3.add("placa_comp", adsk.core.ValueInput.createByString("230.0 mm"), "mm", "Comprimento X")
    up3.add("placa_larg", adsk.core.ValueInput.createByString("160.0 mm"), "mm", "Largura Y")
    up3.add("placa_esp", adsk.core.ValueInput.createByString("15.0 mm"), "mm", "Espessura Z")

    # Furos pés do motor IEC 63 (80 x 100 mm)
    up3.add("motor_dx", adsk.core.ValueInput.createByString("80.0 mm"), "mm", "Entre-furos motor X")
    up3.add("motor_dy", adsk.core.ValueInput.createByString("100.0 mm"), "mm", "Entre-furos motor Y")

    # Rasgos oblongos M10 fora do pé do motor (X = +-75.0 mm, Y = +-47.5 mm)
    up3.add("slot_dx", adsk.core.ValueInput.createByString("150.0 mm"), "mm", "Entre-rasgos X (fora do pe do motor)")
    up3.add("slot_dy", adsk.core.ValueInput.createByString("95.0 mm"), "mm", "Entre-rasgos Y (trilho interno)")
    up3.add("slot_w", adsk.core.ValueInput.createByString("11.5 mm"), "mm", "Largura rasgo M10")
    up3.add("slot_stroke", adsk.core.ValueInput.createByString("16.0 mm"), "mm", "Curso livre de desalinhamento Y (+-8 mm)")

    # Furos Jacking verticais M8 (X = +-100.0 mm, Y = +-67.5 mm - CENTRO EXATO DO PERFIL 40x80!)
    up3.add("jack_dx", adsk.core.ValueInput.createByString("200.0 mm"), "mm", "Entre-furos Jacking X")
    up3.add("jack_dy", adsk.core.ValueInput.createByString("135.0 mm"), "mm", "Entre-furos Jacking Y (centro do perfil)")

    xy3 = root3.xYConstructionPlane

    # Corpo da placa
    sk3_1 = root3.sketches.add(xy3)
    sk3_1.name = "Esboco_Corpo_Placa"
    l3 = sk3_1.sketchCurves.sketchLines
    p_hl = 11.5 # 230 / 2
    p_hw = 8.0  # 160 / 2
    p1 = l3.addByTwoPoints(adsk.core.Point3D.create(-p_hl, -p_hw, 0), adsk.core.Point3D.create(p_hl, -p_hw, 0))
    p2 = l3.addByTwoPoints(adsk.core.Point3D.create(p_hl, -p_hw, 0), adsk.core.Point3D.create(p_hl, p_hw, 0))
    p3 = l3.addByTwoPoints(adsk.core.Point3D.create(p_hl, p_hw, 0), adsk.core.Point3D.create(-p_hl, p_hw, 0))
    p4 = l3.addByTwoPoints(adsk.core.Point3D.create(-p_hl, p_hw, 0), adsk.core.Point3D.create(-p_hl, -p_hw, 0))
    sk3_1.geometricConstraints.addHorizontal(p1); sk3_1.geometricConstraints.addHorizontal(p3)
    sk3_1.geometricConstraints.addVertical(p2); sk3_1.geometricConstraints.addVertical(p4)
    sk3_1.geometricConstraints.addCoincident(p1.endSketchPoint, p2.startSketchPoint)
    sk3_1.geometricConstraints.addCoincident(p2.endSketchPoint, p3.startSketchPoint)
    sk3_1.geometricConstraints.addCoincident(p3.endSketchPoint, p4.startSketchPoint)
    sk3_1.geometricConstraints.addCoincident(p4.endSketchPoint, p1.startSketchPoint)
    sk3_1.sketchDimensions.addDistanceDimension(p4.startSketchPoint, p2.startSketchPoint, adsk.fusion.DimensionOrientations.HorizontalDimensionOrientation, adsk.core.Point3D.create(0, -p_hw - 1.0, 0)).parameter.expression = "placa_comp"
    sk3_1.sketchDimensions.addDistanceDimension(p1.startSketchPoint, p3.startSketchPoint, adsk.fusion.DimensionOrientations.VerticalDimensionOrientation, adsk.core.Point3D.create(p_hl + 1.0, 0, 0)).parameter.expression = "placa_larg"
    sk3_1.sketchDimensions.addDistanceDimension(sk3_1.originPoint, p4.startSketchPoint, adsk.fusion.DimensionOrientations.HorizontalDimensionOrientation, adsk.core.Point3D.create(-p_hl/2, -p_hw - 2.0, 0)).parameter.expression = "placa_comp / 2"
    sk3_1.sketchDimensions.addDistanceDimension(sk3_1.originPoint, p1.startSketchPoint, adsk.fusion.DimensionOrientations.VerticalDimensionOrientation, adsk.core.Point3D.create(p_hl + 2.0, -p_hw/2, 0)).parameter.expression = "placa_larg / 2"

    ext3_in = root3.features.extrudeFeatures.createInput(sk3_1.profiles.item(0), adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext3_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("placa_esp"))
    ext3 = root3.features.extrudeFeatures.add(ext3_in)
    body_placa = ext3.bodies.item(0)

    # Furos pés do motor (X = +-40mm, Y = +-50mm)
    sk3_mot = root3.sketches.add(xy3)
    sk3_mot.name = "Esboco_Furos_Motor"
    for mx, my in [(-4.0, -5.0), (-4.0, 5.0), (4.0, -5.0), (4.0, 5.0)]:
        sk3_mot.sketchCurves.sketchCircles.addByCenterRadius(adsk.core.Point3D.create(mx, my, 0), 0.45)
    profs_mot = adsk.core.ObjectCollection.create()
    for p in sk3_mot.profiles: profs_mot.add(p)
    ext_mot_in = root3.features.extrudeFeatures.createInput(profs_mot, adsk.fusion.FeatureOperations.CutFeatureOperation)
    ext_mot_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("placa_esp + 1.0 mm"))
    ext_mot_in.participantBodies = [body_placa]
    root3.features.extrudeFeatures.add(ext_mot_in)

    # 4 Rasgos oblongos transversais em Y para fixação à mesa (X = +-75mm, Y = +-47.5mm)
    # Totalmente fora da projeção do motor!
    sk3_slots = root3.sketches.add(xy3)
    sk3_slots.name = "Esboco_Rasgos_Oblongos_M10"
    slot_r = 0.575 # 11.5 mm / 2
    h_str = 0.8    # 16.0 mm / 2 (+-8mm de curso util)
    for sx in [-7.5, 7.5]:
        for sy in [-4.75, 4.75]:
            l_s1 = sk3_slots.sketchCurves.sketchLines.addByTwoPoints(adsk.core.Point3D.create(sx - slot_r, sy - h_str, 0), adsk.core.Point3D.create(sx - slot_r, sy + h_str, 0))
            l_s2 = sk3_slots.sketchCurves.sketchLines.addByTwoPoints(adsk.core.Point3D.create(sx + slot_r, sy + h_str, 0), adsk.core.Point3D.create(sx + slot_r, sy - h_str, 0))
            a_top = sk3_slots.sketchCurves.sketchArcs.addByThreePoints(l_s1.endSketchPoint.geometry, adsk.core.Point3D.create(sx, sy + h_str + slot_r, 0), l_s2.startSketchPoint.geometry)
            a_bot = sk3_slots.sketchCurves.sketchArcs.addByThreePoints(l_s2.endSketchPoint.geometry, adsk.core.Point3D.create(sx, sy - h_str - slot_r, 0), l_s1.startSketchPoint.geometry)
            sk3_slots.geometricConstraints.addCoincident(l_s1.endSketchPoint, a_top.startSketchPoint)
            sk3_slots.geometricConstraints.addCoincident(l_s2.startSketchPoint, a_top.endSketchPoint)
            sk3_slots.geometricConstraints.addCoincident(l_s2.endSketchPoint, a_bot.startSketchPoint)
            sk3_slots.geometricConstraints.addCoincident(l_s1.startSketchPoint, a_bot.endSketchPoint)

    profs_slots = adsk.core.ObjectCollection.create()
    for p in sk3_slots.profiles: profs_slots.add(p)
    ext_s_in = root3.features.extrudeFeatures.createInput(profs_slots, adsk.fusion.FeatureOperations.CutFeatureOperation)
    ext_s_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("placa_esp + 1.0 mm"))
    ext_s_in.participantBodies = [body_placa]
    root3.features.extrudeFeatures.add(ext_s_in)

    # 4 Furos Jacking verticais M8 (X = +-100.0 mm, Y = +-67.5 mm - CENTRO DO PERFIL!)
    sk3_jack = root3.sketches.add(xy3)
    sk3_jack.name = "Esboco_Furos_Jacking_M8"
    for jx, jy in [(-10.0, -6.75), (-10.0, 6.75), (10.0, -6.75), (10.0, 6.75)]:
        sk3_jack.sketchCurves.sketchCircles.addByCenterRadius(adsk.core.Point3D.create(jx, jy, 0), 0.4) # M8
    profs_jack = adsk.core.ObjectCollection.create()
    for p in sk3_jack.profiles: profs_jack.add(p)
    ext_j_in = root3.features.extrudeFeatures.createInput(profs_jack, adsk.fusion.FeatureOperations.CutFeatureOperation)
    ext_j_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("placa_esp + 1.0 mm"))
    ext_j_in.participantBodies = [body_placa]
    root3.features.extrudeFeatures.add(ext_j_in)

    # Chanfros nos 4 cantos da placa
    edge_col = adsk.core.ObjectCollection.create()
    for e in body_placa.edges:
        if abs(e.length - 1.5) < 0.01:
            p_mid = e.pointOnEdge
            if abs(abs(p_mid.x) - p_hl) < 0.1 and abs(abs(p_mid.y) - p_hw) < 0.1:
                edge_col.add(e)
    if edge_col.count > 0:
        chamf_in = root3.features.chamferFeatures.createInput2()
        chamf_in.chamferEdgeSets.addEqualDistanceChamferEdgeSet(edge_col, adsk.core.ValueInput.createByString("5.0 mm"), True)
        root3.features.chamferFeatures.add(chamf_in)

    app_ouro = get_mat_app(des3, "Mat_Alum_Anodizado_Ouro", "anodizado com brilho", 225, 175, 45)
    body_placa.appearance = app_ouro

    step_m3 = os.path.join(EXPORT_DIR, "03_Placa_Base_Desalinhamento_Motor.step")
    des3.exportManager.execute(des3.exportManager.createSTEPExportOptions(step_m3))
    doc3.saveAs("03_Placa_Base_Desalinhamento_Motor", folder, "Placa de Desalinhamento Ampliada", "")
    doc3.close(False)
    print("03_Placa_Base_Desalinhamento_Motor concluida com sucesso!")

    # =========================================================================
    # 2. 11_Bloco_Encosto_Push_Pull_Lateral: PLACA LATERAL FIXADA NO TRILHO EXTERNO
    # Afixada na face vertical do perfil (Y = +-107.5mm) no canal T (Z = 20mm)
    # =========================================================================
    print("2. Remodelando 11_Bloco_Encosto_Push_Pull_Lateral (Placa de Fixacao Lateral Externa)...")
    delete_old_file(folder, "11_Bloco_Encosto_Push_Pull_Lateral")

    doc11 = app.documents.add(adsk.core.DocumentTypes.FusionDesignDocumentType)
    des11 = adsk.fusion.Design.cast(doc11.products.itemByProductType("DesignProductType"))
    des11.designType = adsk.fusion.DesignTypes.ParametricDesignType
    root11 = des11.rootComponent

    # Geometria no sistema local:
    # A face que encosta na lateral do perfil fica no plano XZ em Y = 0.
    # O corpo se projeta para fora (direção +Y) com espessura de 12.0 mm (Y de 0 ate 1.2 cm).
    # Largura em X: 40.0 mm (X de -2.0 ate +2.0 cm).
    # Altura em Z: de Z = 0.8 cm ate Z = 5.2 cm (altura total de 44.0 mm).
    # Furo de fixacao inferior: M8 em X=0, Z=2.0 cm (coincide com o canal T lateral em Z=20mm).
    # Furo roscado superior push-pull: M8 em X=0, Z=4.75 cm (coincide com meia espessura da placa base, Z=47.5mm).
    xz11 = root11.xZConstructionPlane
    sk11 = root11.sketches.add(xz11)
    l11 = sk11.sketchCurves.sketchLines
    # Note: on xZConstructionPlane, u = X, v = -Z
    # Z vai de 0.8 ate 5.2 cm -> v vai de -0.8 ate -5.2 cm
    b1 = l11.addByTwoPoints(adsk.core.Point3D.create(-2.0, -0.8, 0), adsk.core.Point3D.create(2.0, -0.8, 0))
    b2 = l11.addByTwoPoints(adsk.core.Point3D.create(2.0, -0.8, 0), adsk.core.Point3D.create(2.0, -5.2, 0))
    b3 = l11.addByTwoPoints(adsk.core.Point3D.create(2.0, -5.2, 0), adsk.core.Point3D.create(-2.0, -5.2, 0))
    b4 = l11.addByTwoPoints(adsk.core.Point3D.create(-2.0, -5.2, 0), adsk.core.Point3D.create(-2.0, -0.8, 0))
    sk11.geometricConstraints.addHorizontal(b1); sk11.geometricConstraints.addHorizontal(b3)
    sk11.geometricConstraints.addVertical(b2); sk11.geometricConstraints.addVertical(b4)
    sk11.geometricConstraints.addCoincident(b1.endSketchPoint, b2.startSketchPoint)
    sk11.geometricConstraints.addCoincident(b2.endSketchPoint, b3.startSketchPoint)
    sk11.geometricConstraints.addCoincident(b3.endSketchPoint, b4.startSketchPoint)
    sk11.geometricConstraints.addCoincident(b4.endSketchPoint, b1.startSketchPoint)

    profs11 = adsk.core.ObjectCollection.create()
    profs11.add(sk11.profiles.item(0))
    ext11_in = root11.features.extrudeFeatures.createInput(profs11, adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext11_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("12.0 mm"))
    ext11 = root11.features.extrudeFeatures.add(ext11_in)
    b11 = ext11.bodies.item(0)

    # Furo passante M8 inferior para parafuso de fixacao no canal T lateral (X=0, Z=20mm -> v=-2.0cm)
    sk11_fix = root11.sketches.add(xz11)
    sk11_fix.sketchCurves.sketchCircles.addByCenterRadius(adsk.core.Point3D.create(0.0, -2.0, 0), 0.45)
    profs_fix = adsk.core.ObjectCollection.create()
    for p in sk11_fix.profiles: profs_fix.add(p)
    ext_fix_in = root11.features.extrudeFeatures.createInput(profs_fix, adsk.fusion.FeatureOperations.CutFeatureOperation)
    ext_fix_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("15.0 mm"))
    ext_fix_in.participantBodies = [b11]
    root11.features.extrudeFeatures.add(ext_fix_in)

    # Rebaixo para cabeca Allen M8 no furo inferior (D=14mm, prof=6mm)
    # Face externa fica em Y=12mm
    plane_outer_in = root11.constructionPlanes.createInput()
    plane_outer_in.setByOffset(xz11, adsk.core.ValueInput.createByString("12.0 mm"))
    plane_outer = root11.constructionPlanes.add(plane_outer_in)
    sk11_cb = root11.sketches.add(plane_outer)
    sk11_cb.sketchCurves.sketchCircles.addByCenterRadius(adsk.core.Point3D.create(0.0, -2.0, 0), 0.7) # D=14mm
    profs_cb = adsk.core.ObjectCollection.create()
    for p in sk11_cb.profiles: profs_cb.add(p)
    ext_cb_in = root11.features.extrudeFeatures.createInput(profs_cb, adsk.fusion.FeatureOperations.CutFeatureOperation)
    ext_cb_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("-6.0 mm"))
    ext_cb_in.participantBodies = [b11]
    root11.features.extrudeFeatures.add(ext_cb_in)

    # Furo roscado M8 superior para o manipulo push-pull (X=0, Z=47.5mm -> v=-4.75cm)
    sk11_th = root11.sketches.add(xz11)
    sk11_th.sketchCurves.sketchCircles.addByCenterRadius(adsk.core.Point3D.create(0.0, -4.75, 0), 0.4) # M8
    profs_th = adsk.core.ObjectCollection.create()
    for p in sk11_th.profiles: profs_th.add(p)
    ext_th_in = root11.features.extrudeFeatures.createInput(profs_th, adsk.fusion.FeatureOperations.CutFeatureOperation)
    ext_th_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("15.0 mm"))
    ext_th_in.participantBodies = [b11]
    root11.features.extrudeFeatures.add(ext_th_in)

    app_orange = get_mat_app(des11, "Mat_Alum_Anodizado_Laranja", "anodizado com brilho", 240, 115, 25)
    b11.appearance = app_orange

    step_m11 = os.path.join(EXPORT_DIR, "11_Bloco_Encosto_Push_Pull_Lateral.step")
    des11.exportManager.execute(des11.exportManager.createSTEPExportOptions(step_m11))
    doc11.saveAs("11_Bloco_Encosto_Push_Pull_Lateral", folder, "Placa de Encosto no Trilho Lateral Externo", "")
    doc11.close(False)
    print("11_Bloco_Encosto_Push_Pull_Lateral concluido com sucesso!")

    # =========================================================================
    # 3. 13_Manipulo_Push_Pull_M8 (Comprimento estendido para alcance lateral)
    # =========================================================================
    print("3. Remodelando 13_Manipulo_Push_Pull_M8 para alcance lateral...")
    delete_old_file(folder, "13_Manipulo_Push_Pull_M8")

    doc13 = app.documents.add(adsk.core.DocumentTypes.FusionDesignDocumentType)
    des13 = adsk.fusion.Design.cast(doc13.products.itemByProductType("DesignProductType"))
    des13.designType = adsk.fusion.DesignTypes.ParametricDesignType
    root13 = des13.rootComponent

    xz13 = root13.xZConstructionPlane
    # Origem do manipulo: na base do manipulo/flange em Y=0.
    # A haste roscada se projeta na direcao -Y por 45 mm (vai de Y=0 ate Y=-4.5 cm).
    # A cabeca recartilhada se projeta na direcao +Y por 12 mm (vai de Y=0 ate Y=+1.2 cm).
    sk13_s = root13.sketches.add(xz13)
    sk13_s.sketchCurves.sketchCircles.addByCenterRadius(adsk.core.Point3D.create(0, 0, 0), 0.4) # M8
    profs_s = adsk.core.ObjectCollection.create()
    for p in sk13_s.profiles: profs_s.add(p)
    ext_s_in = root13.features.extrudeFeatures.createInput(profs_s, adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext_s_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("-45.0 mm"))
    ext13_s = root13.features.extrudeFeatures.add(ext_s_in)
    b13 = ext13_s.bodies.item(0)

    # Cabeca recartilhada (D=22mm, L=12mm)
    sk13_h = root13.sketches.add(xz13)
    sk13_h.sketchCurves.sketchCircles.addByCenterRadius(adsk.core.Point3D.create(0, 0, 0), 1.1)
    profs_h = adsk.core.ObjectCollection.create()
    for p in sk13_h.profiles: profs_h.add(p)
    ext_h_in = root13.features.extrudeFeatures.createInput(profs_h, adsk.fusion.FeatureOperations.JoinFeatureOperation)
    ext_h_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("12.0 mm"))
    root13.features.extrudeFeatures.add(ext_h_in)

    # Pastilha de contato em latao na ponta (D=7mm, L=3mm em Y=-45mm)
    plane_tip_in = root13.constructionPlanes.createInput()
    plane_tip_in.setByOffset(xz13, adsk.core.ValueInput.createByString("-45.0 mm"))
    plane_tip = root13.constructionPlanes.add(plane_tip_in)
    sk13_t = root13.sketches.add(plane_tip)
    sk13_t.sketchCurves.sketchCircles.addByCenterRadius(adsk.core.Point3D.create(0, 0, 0), 0.35)
    profs_t = adsk.core.ObjectCollection.create()
    for p in sk13_t.profiles: profs_t.add(p)
    ext_t_in = root13.features.extrudeFeatures.createInput(profs_t, adsk.fusion.FeatureOperations.JoinFeatureOperation)
    ext_t_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("-3.0 mm"))
    root13.features.extrudeFeatures.add(ext_t_in)

    app_steel = get_mat_app(des13, "Mat_Aco_Inox_Polido_Real", "polido", 220, 224, 228)
    b13.appearance = app_steel

    step_m13 = os.path.join(EXPORT_DIR, "13_Manipulo_Push_Pull_M8.step")
    des13.exportManager.execute(des13.exportManager.createSTEPExportOptions(step_m13))
    doc13.saveAs("13_Manipulo_Push_Pull_M8", folder, "Manipulo Push-Pull M8 Alcance Lateral", "")
    doc13.close(False)
    print("13_Manipulo_Push_Pull_M8 concluido com sucesso!")

    print("TODAS_AS_PECAS_REMODELADAS_COM_SUCESSO")
'''

if __name__ == "__main__":
    sess = init_session()
    print("Iniciando remodelagem das peças no Fusion 360...")
    res = execute_script(sess, SCRIPT_REMODEL)
    print("Resultado recebido:")
    if res and "result" in res and "content" in res["result"]:
        print(res["result"]["content"][0]["text"])
    else:
        print(res)
