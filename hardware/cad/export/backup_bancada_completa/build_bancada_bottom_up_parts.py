import urllib.request
import json
import os
import time

FUSION_URL = "http://127.0.0.1:27182/mcp"
EXPORT_DIR = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\export\bancada"
ARTIFACTS_DIR = r"C:\Users\andrl\.gemini\antigravity-cli\brain\efd833bf-6ba1-4a1b-a7ce-aa9aa87aa86c"
os.makedirs(EXPORT_DIR, exist_ok=True)
os.makedirs(ARTIFACTS_DIR, exist_ok=True)

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
    with urllib.request.urlopen(req, timeout=300) as resp:
        sess = resp.headers.get("MCP-Session-Id", session_id)
        content = resp.read().decode("utf-8")
        return sess, json.loads(content) if content else None

def init_session():
    sess, _ = send_rpc(None, "initialize", {
        "protocolVersion": "2024-11-05",
        "capabilities": {},
        "clientInfo": {"name": "bancada_parts_builder", "version": "1.0"}
    }, 1)
    send_rpc(sess, "notifications/initialized")
    return sess

def execute_script(sess, script_content, read_only=False):
    _, res = send_rpc(sess, "tools/call", {
        "name": "fusion_mcp_execute",
        "arguments": {
            "featureType": "script",
            "object": {
                "script": script_content,
                "readOnly": read_only
            }
        }
    }, int(time.time() * 1000) % 1000000)
    return res

SCRIPT_PARTS = r'''
import adsk.core, adsk.fusion, os, json, time, math

EXPORT_DIR = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\export\bancada"

def draw_centered_rect(sketch, w_param, l_param, w_cm, l_cm):
    lines = sketch.sketchCurves.sketchLines
    constraints = sketch.geometricConstraints
    dimensions = sketch.sketchDimensions
    origin = sketch.originPoint
    
    hw = w_cm / 2.0
    hl = l_cm / 2.0
    
    p1 = adsk.core.Point3D.create(-hw, -hl, 0)
    p2 = adsk.core.Point3D.create(hw, -hl, 0)
    p3 = adsk.core.Point3D.create(hw, hl, 0)
    p4 = adsk.core.Point3D.create(-hw, hl, 0)
    
    l1 = lines.addByTwoPoints(p1, p2)
    l2 = lines.addByTwoPoints(p2, p3)
    l3 = lines.addByTwoPoints(p3, p4)
    l4 = lines.addByTwoPoints(p4, p1)
    
    constraints.addHorizontal(l1)
    constraints.addHorizontal(l3)
    constraints.addVertical(l2)
    constraints.addVertical(l4)
    constraints.addCoincident(l1.endSketchPoint, l2.startSketchPoint)
    constraints.addCoincident(l2.endSketchPoint, l3.startSketchPoint)
    constraints.addCoincident(l3.endSketchPoint, l4.startSketchPoint)
    constraints.addCoincident(l4.endSketchPoint, l1.startSketchPoint)
    
    dim_w = dimensions.addDistanceDimension(l4.startSketchPoint, l2.startSketchPoint, adsk.fusion.DimensionOrientations.HorizontalDimensionOrientation, adsk.core.Point3D.create(0, -hl - 0.5, 0))
    dim_w.parameter.expression = w_param
    
    dim_l = dimensions.addDistanceDimension(l1.startSketchPoint, l3.startSketchPoint, adsk.fusion.DimensionOrientations.VerticalDimensionOrientation, adsk.core.Point3D.create(hw + 0.5, 0, 0))
    dim_l.parameter.expression = l_param
    
    dim_cx = dimensions.addDistanceDimension(origin, l4.startSketchPoint, adsk.fusion.DimensionOrientations.HorizontalDimensionOrientation, adsk.core.Point3D.create(-hw / 2.0, -hl - 1.0, 0))
    dim_cx.parameter.expression = f"{w_param} / 2"
    
    dim_cy = dimensions.addDistanceDimension(origin, l1.startSketchPoint, adsk.fusion.DimensionOrientations.VerticalDimensionOrientation, adsk.core.Point3D.create(hw + 1.0, -hl / 2.0, 0))
    dim_cy.parameter.expression = f"{l_param} / 2"
    
    return sketch.profiles.item(0)

def draw_4_circle_pattern(sketch, dx_param, dy_param, dia_param, dx_cm, dy_cm, dia_cm):
    circs = sketch.sketchCurves.sketchCircles
    dims = sketch.sketchDimensions
    csts = sketch.geometricConstraints
    origin = sketch.originPoint
    
    hx = dx_cm / 2.0
    hy = dy_cm / 2.0
    r = dia_cm / 2.0
    
    c1 = circs.addByCenterRadius(adsk.core.Point3D.create(-hx, -hy, 0), r)
    c2 = circs.addByCenterRadius(adsk.core.Point3D.create(hx, -hy, 0), r)
    c3 = circs.addByCenterRadius(adsk.core.Point3D.create(hx, hy, 0), r)
    c4 = circs.addByCenterRadius(adsk.core.Point3D.create(-hx, hy, 0), r)
    
    d_dia = dims.addDiameterDimension(c1, adsk.core.Point3D.create(-hx + dia_cm, -hy + dia_cm, 0))
    d_dia.parameter.expression = dia_param
    csts.addEqual(c1, c2)
    csts.addEqual(c1, c3)
    csts.addEqual(c1, c4)
    
    csts.addHorizontalPoints(c1.centerSketchPoint, c2.centerSketchPoint)
    csts.addHorizontalPoints(c4.centerSketchPoint, c3.centerSketchPoint)
    csts.addVerticalPoints(c1.centerSketchPoint, c4.centerSketchPoint)
    csts.addVerticalPoints(c2.centerSketchPoint, c3.centerSketchPoint)
    
    dx = dims.addDistanceDimension(c1.centerSketchPoint, c2.centerSketchPoint, adsk.fusion.DimensionOrientations.HorizontalDimensionOrientation, adsk.core.Point3D.create(0, -hy - 0.7, 0))
    dx.parameter.expression = dx_param
    
    dy = dims.addDistanceDimension(c1.centerSketchPoint, c4.centerSketchPoint, adsk.fusion.DimensionOrientations.VerticalDimensionOrientation, adsk.core.Point3D.create(-hx - 0.7, 0, 0))
    dy.parameter.expression = dy_param
    
    cox = dims.addDistanceDimension(origin, c1.centerSketchPoint, adsk.fusion.DimensionOrientations.HorizontalDimensionOrientation, adsk.core.Point3D.create(-hx / 2.0, -hy - 1.2, 0))
    cox.parameter.expression = f"{dx_param} / 2"
    
    coy = dims.addDistanceDimension(origin, c1.centerSketchPoint, adsk.fusion.DimensionOrientations.VerticalDimensionOrientation, adsk.core.Point3D.create(-hx - 1.2, -hy / 2.0, 0))
    coy.parameter.expression = f"{dy_param} / 2"
    
    return [c1, c2, c3, c4]

def draw_single_circle(sketch, cx_param, cy_param, dia_param, cx_cm, cy_cm, dia_cm):
    circles = sketch.sketchCurves.sketchCircles
    dimensions = sketch.sketchDimensions
    constraints = sketch.geometricConstraints
    origin = sketch.originPoint
    
    c = circles.addByCenterRadius(adsk.core.Point3D.create(cx_cm, cy_cm, 0), dia_cm / 2.0)
    dim_dia = dimensions.addDiameterDimension(c, adsk.core.Point3D.create(cx_cm + dia_cm, cy_cm + dia_cm, 0))
    dim_dia.parameter.expression = dia_param
    
    if abs(cx_cm) < 0.001 and abs(cy_cm) < 0.001:
        constraints.addCoincident(c.centerSketchPoint, origin)
        return c
        
    if abs(cx_cm) < 0.001:
        constraints.addVerticalPoints(origin, c.centerSketchPoint)
    else:
        dim_x = dimensions.addDistanceDimension(origin, c.centerSketchPoint, adsk.fusion.DimensionOrientations.HorizontalDimensionOrientation, adsk.core.Point3D.create(cx_cm / 2.0, cy_cm - 0.5, 0))
        dim_x.parameter.expression = cx_param
        
    if abs(cy_cm) < 0.001:
        constraints.addHorizontalPoints(origin, c.centerSketchPoint)
    else:
        dim_y = dimensions.addDistanceDimension(origin, c.centerSketchPoint, adsk.fusion.DimensionOrientations.VerticalDimensionOrientation, adsk.core.Point3D.create(cx_cm + 0.5, cy_cm / 2.0, 0))
        dim_y.parameter.expression = cy_param
        
    return c

def draw_rect_by_center(sketch, cx_cm, cy_cm, w_cm, h_cm, cx_param, cy_param, w_param, h_param):
    lines = sketch.sketchCurves.sketchLines
    constraints = sketch.geometricConstraints
    dimensions = sketch.sketchDimensions
    origin = sketch.originPoint
    
    hw = w_cm / 2.0
    hh = h_cm / 2.0
    
    c_pt = sketch.sketchPoints.add(adsk.core.Point3D.create(cx_cm, cy_cm, 0))
    if abs(cx_cm) < 0.001:
        constraints.addVerticalPoints(origin, c_pt)
    else:
        dim_x = dimensions.addDistanceDimension(origin, c_pt, adsk.fusion.DimensionOrientations.HorizontalDimensionOrientation, adsk.core.Point3D.create(cx_cm / 2.0, cy_cm - 0.5, 0))
        dim_x.parameter.expression = cx_param
        
    if abs(cy_cm) < 0.001:
        constraints.addHorizontalPoints(origin, c_pt)
    else:
        dim_y = dimensions.addDistanceDimension(origin, c_pt, adsk.fusion.DimensionOrientations.VerticalDimensionOrientation, adsk.core.Point3D.create(cx_cm + 0.5, cy_cm / 2.0, 0))
        dim_y.parameter.expression = cy_param
        
    p1 = adsk.core.Point3D.create(cx_cm - hw, cy_cm - hh, 0)
    p2 = adsk.core.Point3D.create(cx_cm + hw, cy_cm - hh, 0)
    p3 = adsk.core.Point3D.create(cx_cm + hw, cy_cm + hh, 0)
    p4 = adsk.core.Point3D.create(cx_cm - hw, cy_cm + hh, 0)
    
    l1 = lines.addByTwoPoints(p1, p2)
    l2 = lines.addByTwoPoints(p2, p3)
    l3 = lines.addByTwoPoints(p3, p4)
    l4 = lines.addByTwoPoints(p4, p1)
    
    constraints.addHorizontal(l1)
    constraints.addHorizontal(l3)
    constraints.addVertical(l2)
    constraints.addVertical(l4)
    constraints.addCoincident(l1.endSketchPoint, l2.startSketchPoint)
    constraints.addCoincident(l2.endSketchPoint, l3.startSketchPoint)
    constraints.addCoincident(l3.endSketchPoint, l4.startSketchPoint)
    constraints.addCoincident(l4.endSketchPoint, l1.startSketchPoint)
    
    dim_w = dimensions.addDistanceDimension(l4.startSketchPoint, l2.startSketchPoint, adsk.fusion.DimensionOrientations.HorizontalDimensionOrientation, adsk.core.Point3D.create(cx_cm, cy_cm - hh - 0.3, 0))
    dim_w.parameter.expression = w_param
    
    dim_h = dimensions.addDistanceDimension(l1.startSketchPoint, l3.startSketchPoint, adsk.fusion.DimensionOrientations.VerticalDimensionOrientation, adsk.core.Point3D.create(cx_cm + hw + 0.3, cy_cm, 0))
    dim_h.parameter.expression = h_param
    
    dim_mid_x = dimensions.addDistanceDimension(c_pt, l4.startSketchPoint, adsk.fusion.DimensionOrientations.HorizontalDimensionOrientation, adsk.core.Point3D.create(cx_cm - hw/2.0, cy_cm - hh - 0.6, 0))
    dim_mid_x.parameter.expression = f"{w_param} / 2"
    
    dim_mid_y = dimensions.addDistanceDimension(c_pt, l1.startSketchPoint, adsk.fusion.DimensionOrientations.VerticalDimensionOrientation, adsk.core.Point3D.create(cx_cm + hw + 0.6, cy_cm - hh/2.0, 0))
    dim_mid_y.parameter.expression = f"{h_param} / 2"
    
    return l1

def export_and_save(doc, name, folder):
    design = adsk.fusion.Design.cast(doc.products.itemByProductType("DesignProductType"))
    step_path = os.path.join(EXPORT_DIR, f"{name}.step")
    design.exportManager.execute(design.exportManager.createSTEPExportOptions(step_path))
    print(f"Exported STEP: {step_path}")
    doc.saveAs(name, folder, f"Componente parametrico bancada {name}", "")
    doc.close(False)
    print(f"Saved and closed: {name}")

def set_color(body, root, red, green, blue, name):
    app = adsk.core.Application.get()
    des = root.parentDesign
    appearances = des.appearances
    existing = appearances.itemByName(name)
    if existing:
        body.appearance = existing
        return
    lib0 = app.materialLibraries.item(0)
    base_app = lib0.appearances.item(0) if lib0 and lib0.appearances.count > 0 else None
    if base_app:
        new_app = appearances.addByCopy(base_app, name)
        color_prop = new_app.appearanceProperties.itemByName("Color")
        if color_prop:
            color_val = adsk.core.Color.create(int(red*255), int(green*255), int(blue*255), 255)
            color_prop.value = color_val
        body.appearance = new_app

def run(context):
    app = adsk.core.Application.get()
    data = app.data
    
    for doc in list(app.documents):
        try:
            doc.close(False)
        except Exception:
            pass

    amemiya_proj = None
    for p in data.dataProjects:
        if p.name == "Amemiya":
            amemiya_proj = p
            break
    if not amemiya_proj:
        raise RuntimeError("Projeto 'Amemiya' nao encontrado!")

    target_folder = None
    for f in amemiya_proj.rootFolder.dataFolders:
        if f.name == "04_Bancada_Components":
            target_folder = f
            break
    if not target_folder:
        target_folder = amemiya_proj.rootFolder.dataFolders.add("04_Bancada_Components")

    def clean_files(name_list):
        for df in list(target_folder.dataFiles):
            if df.name in name_list:
                try:
                    df.deleteMe()
                    print(f"Removido arquivo antigo: {df.name}")
                except Exception as e:
                    print(f"Aviso ao remover {df.name}: {e}")

    # =========================================================================
    # 1. 01_Mesa_Base_Bancada (660 x 240 x 20 mm com 2 Canais T e 4 Furos Sapata)
    # =========================================================================
    print("1. Modelando 01_Mesa_Base_Bancada...")
    clean_files(["01_Mesa_Base_Bancada"])
    doc1 = app.documents.add(adsk.core.DocumentTypes.FusionDesignDocumentType)
    des1 = adsk.fusion.Design.cast(doc1.products.itemByProductType("DesignProductType"))
    des1.designType = adsk.fusion.DesignTypes.ParametricDesignType
    root1 = des1.rootComponent
    up1 = des1.userParameters

    up1.add("mesa_comp", adsk.core.ValueInput.createByString("660.0 mm"), "mm", "Comprimento da mesa")
    up1.add("mesa_larg", adsk.core.ValueInput.createByString("240.0 mm"), "mm", "Largura da mesa")
    up1.add("mesa_esp", adsk.core.ValueInput.createByString("20.0 mm"), "mm", "Espessura da mesa")
    up1.add("sapata_dx", adsk.core.ValueInput.createByString("600.0 mm"), "mm", "Distancia X furos sapatas")
    up1.add("sapata_dy", adsk.core.ValueInput.createByString("190.0 mm"), "mm", "Distancia Y furos sapatas")
    up1.add("sapata_hole_dia", adsk.core.ValueInput.createByString("10.5 mm"), "mm", "Furo passante M10 sapatas")
    up1.add("slot_spacing", adsk.core.ValueInput.createByString("95.0 mm"), "mm", "Distancia entre canais T (J mancal)")
    up1.add("slot_w", adsk.core.ValueInput.createByString("12.0 mm"), "mm", "Largura canal fixacao")
    up1.add("slot_len", adsk.core.ValueInput.createByString("620.0 mm"), "mm", "Comprimento util canais")
    up1.add("slot_depth", adsk.core.ValueInput.createByString("8.0 mm"), "mm", "Profundidade ranhura fixacao")

    xy1 = root1.xYConstructionPlane
    sk1_1 = root1.sketches.add(xy1)
    sk1_1.name = "Esboco_Bloco_Mesa"
    draw_centered_rect(sk1_1, "mesa_comp", "mesa_larg", 66.0, 24.0)
    print(f"Mesa Sk1 fullyConstrained: {sk1_1.isFullyConstrained}")

    ext1_1_in = root1.features.extrudeFeatures.createInput(sk1_1.profiles.item(0), adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext1_1_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("mesa_esp"))
    ext1_1 = root1.features.extrudeFeatures.add(ext1_1_in)
    b_mesa = ext1_1.bodies.item(0)

    # 4 Furos M10 para sapatas
    sk1_holes = root1.sketches.add(xy1)
    sk1_holes.name = "Esboco_Furos_Sapatas"
    draw_4_circle_pattern(sk1_holes, "sapata_dx", "sapata_dy", "sapata_hole_dia", 60.0, 19.0, 1.05)
    print(f"Mesa Sk Furos Sapatas fullyConstrained: {sk1_holes.isFullyConstrained}")

    profs1_h = adsk.core.ObjectCollection.create()
    for prf in sk1_holes.profiles: profs1_h.add(prf)
    ext1_h_in = root1.features.extrudeFeatures.createInput(profs1_h, adsk.fusion.FeatureOperations.CutFeatureOperation)
    ext1_h_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("mesa_esp + 1.0 mm"))
    root1.features.extrudeFeatures.add(ext1_h_in)

    # 2 Canais longitudinais de fixação no topo da mesa (Z = 20 mm)
    top_plane1_in = root1.constructionPlanes.createInput()
    top_plane1_in.setByOffset(xy1, adsk.core.ValueInput.createByString("mesa_esp"))
    top_plane1 = root1.constructionPlanes.add(top_plane1_in)

    sk1_slots = root1.sketches.add(top_plane1)
    sk1_slots.name = "Esboco_Canais_Fixacao_T"
    draw_rect_by_center(sk1_slots, 0.0, 4.75, 62.0, 1.2, "0.0 mm", "slot_spacing / 2", "slot_len", "slot_w")
    draw_rect_by_center(sk1_slots, 0.0, -4.75, 62.0, 1.2, "0.0 mm", "slot_spacing / 2", "slot_len", "slot_w")
    print(f"Mesa Sk Canais fullyConstrained: {sk1_slots.isFullyConstrained}")

    profs1_s = adsk.core.ObjectCollection.create()
    for prf in sk1_slots.profiles: profs1_s.add(prf)
    ext1_s_in = root1.features.extrudeFeatures.createInput(profs1_s, adsk.fusion.FeatureOperations.CutFeatureOperation)
    ext1_s_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("-slot_depth"))
    root1.features.extrudeFeatures.add(ext1_s_in)

    set_color(b_mesa, root1, 0.78, 0.80, 0.82, "Aluminio_Acetinado_Mesa")
    export_and_save(doc1, "01_Mesa_Base_Bancada", target_folder)
    print("01_Mesa_Base_Bancada concluida com sucesso!")

    # =========================================================================
    # 2. 02_Sapata_Niveladora_Borracha (Disco D=50mm + Flange D=32mm + Rosca M10)
    # =========================================================================
    print("2. Modelando 02_Sapata_Niveladora_Borracha...")
    clean_files(["02_Sapata_Niveladora_Borracha"])
    doc2 = app.documents.add(adsk.core.DocumentTypes.FusionDesignDocumentType)
    des2 = adsk.fusion.Design.cast(doc2.products.itemByProductType("DesignProductType"))
    des2.designType = adsk.fusion.DesignTypes.ParametricDesignType
    root2 = des2.rootComponent
    up2 = des2.userParameters

    up2.add("sap_rubber_dia", adsk.core.ValueInput.createByString("50.0 mm"), "mm", "Diametro base borracha")
    up2.add("sap_rubber_h", adsk.core.ValueInput.createByString("15.0 mm"), "mm", "Altura base borracha")
    up2.add("sap_metal_dia", adsk.core.ValueInput.createByString("32.0 mm"), "mm", "Diametro colar metalico")
    up2.add("sap_metal_h", adsk.core.ValueInput.createByString("8.0 mm"), "mm", "Altura colar metalico")
    up2.add("sap_stud_dia", adsk.core.ValueInput.createByString("10.0 mm"), "mm", "Diametro rosca M10")
    up2.add("sap_stud_len", adsk.core.ValueInput.createByString("35.0 mm"), "mm", "Comprimento espigao roscado")

    xy2 = root2.xYConstructionPlane
    sk2_1 = root2.sketches.add(xy2)
    sk2_1.name = "Esboco_Base_Borracha"
    draw_single_circle(sk2_1, "0 mm", "0 mm", "sap_rubber_dia", 0, 0, 5.0)
    print(f"Sapata Sk1 fullyConstrained: {sk2_1.isFullyConstrained}")

    ext2_1_in = root2.features.extrudeFeatures.createInput(sk2_1.profiles.item(0), adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext2_1_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("sap_rubber_h"))
    ext2_1 = root2.features.extrudeFeatures.add(ext2_1_in)
    b_rubber = ext2_1.bodies.item(0)

    # Colar de Aço Superior
    pl2_m_in = root2.constructionPlanes.createInput()
    pl2_m_in.setByOffset(xy2, adsk.core.ValueInput.createByString("sap_rubber_h"))
    pl2_m = root2.constructionPlanes.add(pl2_m_in)

    sk2_2 = root2.sketches.add(pl2_m)
    sk2_2.name = "Esboco_Colar_Metalico"
    draw_single_circle(sk2_2, "0 mm", "0 mm", "sap_metal_dia", 0, 0, 3.2)
    print(f"Sapata Sk2 fullyConstrained: {sk2_2.isFullyConstrained}")

    ext2_2_in = root2.features.extrudeFeatures.createInput(sk2_2.profiles.item(0), adsk.fusion.FeatureOperations.JoinFeatureOperation)
    ext2_2_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("sap_metal_h"))
    ext2_2 = root2.features.extrudeFeatures.add(ext2_2_in)

    # Espigão Roscado M10
    pl2_s_in = root2.constructionPlanes.createInput()
    pl2_s_in.setByOffset(pl2_m, adsk.core.ValueInput.createByString("sap_metal_h"))
    pl2_s = root2.constructionPlanes.add(pl2_s_in)

    sk2_3 = root2.sketches.add(pl2_s)
    sk2_3.name = "Esboco_Espigao_M10"
    draw_single_circle(sk2_3, "0 mm", "0 mm", "sap_stud_dia", 0, 0, 1.0)
    print(f"Sapata Sk3 fullyConstrained: {sk2_3.isFullyConstrained}")

    ext2_3_in = root2.features.extrudeFeatures.createInput(sk2_3.profiles.item(0), adsk.fusion.FeatureOperations.JoinFeatureOperation)
    ext2_3_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("sap_stud_len"))
    root2.features.extrudeFeatures.add(ext2_3_in)

    set_color(b_rubber, root2, 0.12, 0.12, 0.12, "Borracha_Preta_Antivibracao")
    export_and_save(doc2, "02_Sapata_Niveladora_Borracha", target_folder)
    print("02_Sapata_Niveladora_Borracha concluida com sucesso!")

    # =========================================================================
    # 3. 03_Placa_Base_Desalinhamento_Motor (160 x 180 x 15 mm com furos oblongos)
    # =========================================================================
    print("3. Modelando 03_Placa_Base_Desalinhamento_Motor...")
    clean_files(["03_Placa_Base_Desalinhamento_Motor"])
    doc3 = app.documents.add(adsk.core.DocumentTypes.FusionDesignDocumentType)
    des3 = adsk.fusion.Design.cast(doc3.products.itemByProductType("DesignProductType"))
    des3.designType = adsk.fusion.DesignTypes.ParametricDesignType
    root3 = des3.rootComponent
    up3 = des3.userParameters

    up3.add("placa_comp", adsk.core.ValueInput.createByString("160.0 mm"), "mm", "Comprimento placa motor")
    up3.add("placa_larg", adsk.core.ValueInput.createByString("180.0 mm"), "mm", "Largura placa motor")
    up3.add("placa_esp", adsk.core.ValueInput.createByString("15.0 mm"), "mm", "Espessura da placa")
    up3.add("oblong_dx", adsk.core.ValueInput.createByString("110.0 mm"), "mm", "Distancia X furos fixacao mesa")
    up3.add("oblong_dy", adsk.core.ValueInput.createByString("95.0 mm"), "mm", "Distancia Y canais mesa")
    up3.add("oblong_dia", adsk.core.ValueInput.createByString("11.0 mm"), "mm", "Diametro furos M10")
    up3.add("motor_furo_dx", adsk.core.ValueInput.createByString("90.0 mm"), "mm", "Entre-furos X pes motor IEC 63")
    up3.add("motor_furo_dy", adsk.core.ValueInput.createByString("112.0 mm"), "mm", "Entre-furos Y pes motor IEC 63")
    up3.add("motor_furo_dia", adsk.core.ValueInput.createByString("8.5 mm"), "mm", "Furos rosca M8 pes motor")

    xy3 = root3.xYConstructionPlane
    sk3_1 = root3.sketches.add(xy3)
    sk3_1.name = "Esboco_Placa_Motor"
    draw_centered_rect(sk3_1, "placa_comp", "placa_larg", 16.0, 18.0)
    print(f"Placa Motor Sk1 fullyConstrained: {sk3_1.isFullyConstrained}")

    ext3_1_in = root3.features.extrudeFeatures.createInput(sk3_1.profiles.item(0), adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext3_1_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("placa_esp"))
    ext3_1 = root3.features.extrudeFeatures.add(ext3_1_in)
    b_placa = ext3_1.bodies.item(0)

    # Furos M10 para fixar a placa na mesa
    sk3_oblong = root3.sketches.add(xy3)
    sk3_oblong.name = "Esboco_Furos_Mesa"
    draw_4_circle_pattern(sk3_oblong, "oblong_dx", "oblong_dy", "oblong_dia", 11.0, 9.5, 1.1)
    print(f"Placa Motor Sk Furos Mesa fullyConstrained: {sk3_oblong.isFullyConstrained}")

    profs3_ob = adsk.core.ObjectCollection.create()
    for prf in sk3_oblong.profiles: profs3_ob.add(prf)
    ext3_ob_in = root3.features.extrudeFeatures.createInput(profs3_ob, adsk.fusion.FeatureOperations.CutFeatureOperation)
    ext3_ob_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("placa_esp + 1.0 mm"))
    root3.features.extrudeFeatures.add(ext3_ob_in)

    # Furos M8 para os pés do motor
    sk3_motor_h = root3.sketches.add(xy3)
    sk3_motor_h.name = "Esboco_Furos_Pes_Motor"
    draw_4_circle_pattern(sk3_motor_h, "motor_furo_dx", "motor_furo_dy", "motor_furo_dia", 9.0, 11.2, 0.85)
    print(f"Placa Motor Sk Furos Pes fullyConstrained: {sk3_motor_h.isFullyConstrained}")

    profs3_mh = adsk.core.ObjectCollection.create()
    for prf in sk3_motor_h.profiles: profs3_mh.add(prf)
    ext3_mh_in = root3.features.extrudeFeatures.createInput(profs3_mh, adsk.fusion.FeatureOperations.CutFeatureOperation)
    ext3_mh_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("placa_esp + 1.0 mm"))
    root3.features.extrudeFeatures.add(ext3_mh_in)

    set_color(b_placa, root3, 0.85, 0.75, 0.25, "Aco_Zincado_Dourado_Placa")
    export_and_save(doc3, "03_Placa_Base_Desalinhamento_Motor", target_folder)
    print("03_Placa_Base_Desalinhamento_Motor concluida com sucesso!")

    # =========================================================================
    # 4. 04_Motor_Eletrico_Trifasico (Carcaça D=100mm, Pés, Caixa Bornes, Eixo D=14mm)
    # =========================================================================
    print("4. Modelando 04_Motor_Eletrico_Trifasico...")
    clean_files(["04_Motor_Eletrico_Trifasico"])
    doc4 = app.documents.add(adsk.core.DocumentTypes.FusionDesignDocumentType)
    des4 = adsk.fusion.Design.cast(doc4.products.itemByProductType("DesignProductType"))
    des4.designType = adsk.fusion.DesignTypes.ParametricDesignType
    root4 = des4.rootComponent
    up4 = des4.userParameters

    up4.add("motor_body_dia", adsk.core.ValueInput.createByString("100.0 mm"), "mm", "Diametro carcaça motor")
    up4.add("motor_body_len", adsk.core.ValueInput.createByString("130.0 mm"), "mm", "Comprimento carcaça")
    up4.add("motor_shaft_dia", adsk.core.ValueInput.createByString("14.0 mm"), "mm", "Diametro eixo saida")
    up4.add("motor_shaft_len", adsk.core.ValueInput.createByString("30.0 mm"), "mm", "Comprimento util eixo")
    up4.add("foot_w", adsk.core.ValueInput.createByString("110.0 mm"), "mm", "Comprimento pes motor X")
    up4.add("foot_d", adsk.core.ValueInput.createByString("140.0 mm"), "mm", "Largura pes motor Y")
    up4.add("foot_th", adsk.core.ValueInput.createByString("10.0 mm"), "mm", "Espessura pes motor")
    up4.add("box_w", adsk.core.ValueInput.createByString("55.0 mm"), "mm", "Largura caixa de bornes X")
    up4.add("box_d", adsk.core.ValueInput.createByString("50.0 mm"), "mm", "Comprimento caixa bornes Y")
    up4.add("box_h", adsk.core.ValueInput.createByString("35.0 mm"), "mm", "Altura caixa de bornes Z")
    up4.add("center_h", adsk.core.ValueInput.createByString("43.3 mm"), "mm", "Altura de centro do eixo")

    # Base / Pés do motor apoiados no plano XY
    xy4 = root4.xYConstructionPlane
    sk4_feet = root4.sketches.add(xy4)
    sk4_feet.name = "Esboco_Pes_Motor"
    draw_centered_rect(sk4_feet, "foot_w", "foot_d", 11.0, 14.0)
    print(f"Motor Sk1 Pes fullyConstrained: {sk4_feet.isFullyConstrained}")

    ext4_feet_in = root4.features.extrudeFeatures.createInput(sk4_feet.profiles.item(0), adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext4_feet_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("foot_th"))
    ext4_feet = root4.features.extrudeFeatures.add(ext4_feet_in)
    b_motor = ext4_feet.bodies.item(0)

    # Carcaça cilíndrica horizontal (Plano YZ em X = -motor_body_len/2)
    yz4 = root4.yZConstructionPlane
    pl4_cyl_in = root4.constructionPlanes.createInput()
    pl4_cyl_in.setByOffset(yz4, adsk.core.ValueInput.createByString("-(motor_body_len / 2)"))
    pl4_cyl = root4.constructionPlanes.add(pl4_cyl_in)

    sk4_cyl = root4.sketches.add(pl4_cyl)
    sk4_cyl.name = "Esboco_Carcaca_Cilindrica"
    draw_single_circle(sk4_cyl, "-center_h", "0 mm", "motor_body_dia", -4.33, 0.0, 10.0)
    print(f"Motor Sk2 Carcaca fullyConstrained: {sk4_cyl.isFullyConstrained}")

    ext4_cyl_in = root4.features.extrudeFeatures.createInput(sk4_cyl.profiles.item(0), adsk.fusion.FeatureOperations.JoinFeatureOperation)
    ext4_cyl_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("motor_body_len"))
    root4.features.extrudeFeatures.add(ext4_cyl_in)

    # Eixo de saída do motor (Plano YZ na face frontal X = +motor_body_len/2)
    pl4_shaft_in = root4.constructionPlanes.createInput()
    pl4_shaft_in.setByOffset(yz4, adsk.core.ValueInput.createByString("motor_body_len / 2"))
    pl4_shaft = root4.constructionPlanes.add(pl4_shaft_in)

    sk4_shaft = root4.sketches.add(pl4_shaft)
    sk4_shaft.name = "Esboco_Eixo_Motor"
    draw_single_circle(sk4_shaft, "-center_h", "0 mm", "motor_shaft_dia", -4.33, 0.0, 1.4)
    print(f"Motor Sk3 Eixo fullyConstrained: {sk4_shaft.isFullyConstrained}")

    ext4_shaft_in = root4.features.extrudeFeatures.createInput(sk4_shaft.profiles.item(0), adsk.fusion.FeatureOperations.JoinFeatureOperation)
    ext4_shaft_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("motor_shaft_len"))
    root4.features.extrudeFeatures.add(ext4_shaft_in)

    # Caixa de ligação elétrica no topo
    pl4_box_in = root4.constructionPlanes.createInput()
    pl4_box_in.setByOffset(xy4, adsk.core.ValueInput.createByString("center_h + (motor_body_dia / 2)"))
    pl4_box = root4.constructionPlanes.add(pl4_box_in)

    sk4_box = root4.sketches.add(pl4_box)
    sk4_box.name = "Esboco_Caixa_Bornes"
    draw_centered_rect(sk4_box, "box_w", "box_d", 5.5, 5.0)
    print(f"Motor Sk4 Bornes fullyConstrained: {sk4_box.isFullyConstrained}")

    ext4_box_in = root4.features.extrudeFeatures.createInput(sk4_box.profiles.item(0), adsk.fusion.FeatureOperations.JoinFeatureOperation)
    ext4_box_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("box_h"))
    root4.features.extrudeFeatures.add(ext4_box_in)

    set_color(b_motor, root4, 0.12, 0.35, 0.65, "Azul_Motor_Trifasico")
    export_and_save(doc4, "04_Motor_Eletrico_Trifasico", target_folder)
    print("04_Motor_Eletrico_Trifasico concluido com sucesso!")

    # =========================================================================
    # 5. 05_Acoplamento_Flexivel_Mandibula (Cubos D=36mm, Eixo D=14/20mm, Aranha)
    # =========================================================================
    print("5. Modelando 05_Acoplamento_Flexivel_Mandibula...")
    clean_files(["05_Acoplamento_Flexivel_Mandibula"])
    doc5 = app.documents.add(adsk.core.DocumentTypes.FusionDesignDocumentType)
    des5 = adsk.fusion.Design.cast(doc5.products.itemByProductType("DesignProductType"))
    des5.designType = adsk.fusion.DesignTypes.ParametricDesignType
    root5 = des5.rootComponent
    up5 = des5.userParameters

    up5.add("acopl_dia", adsk.core.ValueInput.createByString("36.0 mm"), "mm", "Diametro externo cubos")
    up5.add("acopl_hub_len", adsk.core.ValueInput.createByString("20.0 mm"), "mm", "Comprimento cada cubo")
    up5.add("spider_len", adsk.core.ValueInput.createByString("12.0 mm"), "mm", "Espessura aranha poliuretano")
    up5.add("hole_motor", adsk.core.ValueInput.createByString("14.0 mm"), "mm", "Furo lado motor")
    up5.add("hole_eixo", adsk.core.ValueInput.createByString("20.0 mm"), "mm", "Furo lado eixo principal")

    yz5 = root5.yZConstructionPlane
    sk5_1 = root5.sketches.add(yz5)
    sk5_1.name = "Esboco_Cubo_Motor"
    draw_single_circle(sk5_1, "0 mm", "0 mm", "acopl_dia", 0, 0, 3.6)
    draw_single_circle(sk5_1, "0 mm", "0 mm", "hole_motor", 0, 0, 1.4)
    print(f"Acoplamento Sk1 fullyConstrained: {sk5_1.isFullyConstrained}")

    prof5_1 = None
    for p in sk5_1.profiles:
        if p.areaProperties().area > 3.0: prof5_1 = p; break
    if not prof5_1: prof5_1 = sk5_1.profiles.item(0)

    ext5_1_in = root5.features.extrudeFeatures.createInput(prof5_1, adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext5_1_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("-acopl_hub_len"))
    ext5_1 = root5.features.extrudeFeatures.add(ext5_1_in)
    b_hub1 = ext5_1.bodies.item(0)

    # Aranha elástica central
    sk5_2 = root5.sketches.add(yz5)
    sk5_2.name = "Esboco_Aranha_Poliuretano"
    draw_single_circle(sk5_2, "0 mm", "0 mm", "acopl_dia", 0, 0, 3.6)
    draw_single_circle(sk5_2, "0 mm", "0 mm", "12.0 mm", 0, 0, 1.2)
    print(f"Acoplamento Sk2 fullyConstrained: {sk5_2.isFullyConstrained}")

    prof5_2 = None
    for p in sk5_2.profiles:
        if p.areaProperties().area > 3.0: prof5_2 = p; break
    if not prof5_2: prof5_2 = sk5_2.profiles.item(0)

    ext5_2_in = root5.features.extrudeFeatures.createInput(prof5_2, adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext5_2_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("spider_len"))
    ext5_2 = root5.features.extrudeFeatures.add(ext5_2_in)
    b_spider = ext5_2.bodies.item(0)

    # Cubo lado eixo
    pl5_hub2_in = root5.constructionPlanes.createInput()
    pl5_hub2_in.setByOffset(yz5, adsk.core.ValueInput.createByString("spider_len"))
    pl5_hub2 = root5.constructionPlanes.add(pl5_hub2_in)

    sk5_3 = root5.sketches.add(pl5_hub2)
    sk5_3.name = "Esboco_Cubo_Eixo"
    draw_single_circle(sk5_3, "0 mm", "0 mm", "acopl_dia", 0, 0, 3.6)
    draw_single_circle(sk5_3, "0 mm", "0 mm", "hole_eixo", 0, 0, 2.0)
    print(f"Acoplamento Sk3 fullyConstrained: {sk5_3.isFullyConstrained}")

    prof5_3 = None
    for p in sk5_3.profiles:
        if p.areaProperties().area > 3.0: prof5_3 = p; break
    if not prof5_3: prof5_3 = sk5_3.profiles.item(0)

    ext5_3_in = root5.features.extrudeFeatures.createInput(prof5_3, adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext5_3_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("acopl_hub_len"))
    ext5_3 = root5.features.extrudeFeatures.add(ext5_3_in)
    b_hub2 = ext5_3.bodies.item(0)

    set_color(b_hub1, root5, 0.45, 0.47, 0.50, "Aco_Fosfatizado_Acoplamento")
    set_color(b_spider, root5, 0.90, 0.15, 0.15, "Poliuretano_Vermelho_Aranha")
    set_color(b_hub2, root5, 0.45, 0.47, 0.50, "Aco_Fosfatizado_Acoplamento")

    export_and_save(doc5, "05_Acoplamento_Flexivel_Mandibula", target_folder)
    print("05_Acoplamento_Flexivel_Mandibula concluido com sucesso!")

    # =========================================================================
    # 6. 06_Eixo_Rotativo_Retificado_20mm (D=20mm x L=420mm)
    # =========================================================================
    print("6. Modelando 06_Eixo_Rotativo_Retificado_20mm...")
    clean_files(["06_Eixo_Rotativo_Retificado_20mm"])
    doc6 = app.documents.add(adsk.core.DocumentTypes.FusionDesignDocumentType)
    des6 = adsk.fusion.Design.cast(doc6.products.itemByProductType("DesignProductType"))
    des6.designType = adsk.fusion.DesignTypes.ParametricDesignType
    root6 = des6.rootComponent
    up6 = des6.userParameters

    up6.add("eixo_dia", adsk.core.ValueInput.createByString("20.0 mm"), "mm", "Diametro do eixo")
    up6.add("eixo_len", adsk.core.ValueInput.createByString("420.0 mm"), "mm", "Comprimento do eixo")

    yz6 = root6.yZConstructionPlane
    sk6_1 = root6.sketches.add(yz6)
    sk6_1.name = "Esboco_Eixo"
    draw_single_circle(sk6_1, "0 mm", "0 mm", "eixo_dia", 0, 0, 2.0)
    print(f"Eixo Sk1 fullyConstrained: {sk6_1.isFullyConstrained}")

    ext6_1_in = root6.features.extrudeFeatures.createInput(sk6_1.profiles.item(0), adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext6_1_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("eixo_len"))
    ext6_1 = root6.features.extrudeFeatures.add(ext6_1_in)
    b_eixo = ext6_1.bodies.item(0)

    set_color(b_eixo, root6, 0.92, 0.93, 0.95, "Aco_Retificado_Polido")
    export_and_save(doc6, "06_Eixo_Rotativo_Retificado_20mm", target_folder)
    print("06_Eixo_Rotativo_Retificado_20mm concluido com sucesso!")

    # =========================================================================
    # 7. 07_Mancal_Pillow_Block_UCP204 (Base L=127mm, J=95mm, H=33.3mm, D=20mm)
    # =========================================================================
    print("7. Modelando 07_Mancal_Pillow_Block_UCP204...")
    clean_files(["07_Mancal_Pillow_Block_UCP204"])
    doc7 = app.documents.add(adsk.core.DocumentTypes.FusionDesignDocumentType)
    des7 = adsk.fusion.Design.cast(doc7.products.itemByProductType("DesignProductType"))
    des7.designType = adsk.fusion.DesignTypes.ParametricDesignType
    root7 = des7.rootComponent
    up7 = des7.userParameters

    up7.add("mancal_l", adsk.core.ValueInput.createByString("38.0 mm"), "mm", "Comprimento da base X")
    up7.add("mancal_w", adsk.core.ValueInput.createByString("127.0 mm"), "mm", "Largura da base Y")
    up7.add("mancal_base_th", adsk.core.ValueInput.createByString("15.0 mm"), "mm", "Espessura da aba base")
    up7.add("mancal_center_h", adsk.core.ValueInput.createByString("33.3 mm"), "mm", "Altura de centro do rolamento")
    up7.add("mancal_j", adsk.core.ValueInput.createByString("95.0 mm"), "mm", "Distancia entre furos de fixacao")
    up7.add("mancal_hole_dia", adsk.core.ValueInput.createByString("13.0 mm"), "mm", "Diametro furos M10")
    up7.add("housing_dia", adsk.core.ValueInput.createByString("70.0 mm"), "mm", "Diametro carcaça central")
    up7.add("bearing_bore", adsk.core.ValueInput.createByString("20.0 mm"), "mm", "Furo rolamento UC204")
    up7.add("bearing_len", adsk.core.ValueInput.createByString("31.0 mm"), "mm", "Comprimento colar rolamento")

    xy7 = root7.xYConstructionPlane
    sk7_base = root7.sketches.add(xy7)
    sk7_base.name = "Esboco_Base_Mancal"
    draw_centered_rect(sk7_base, "mancal_l", "mancal_w", 3.8, 12.7)
    print(f"Mancal Sk1 Base fullyConstrained: {sk7_base.isFullyConstrained}")

    ext7_base_in = root7.features.extrudeFeatures.createInput(sk7_base.profiles.item(0), adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext7_base_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("mancal_base_th"))
    ext7_base = root7.features.extrudeFeatures.add(ext7_base_in)
    b_mancal = ext7_base.bodies.item(0)

    # 2 Furos de fixação na base M10 espaçados em J = 95mm
    sk7_holes = root7.sketches.add(xy7)
    sk7_holes.name = "Esboco_Furos_Fixacao_M10"
    draw_single_circle(sk7_holes, "0 mm", "mancal_j / 2", "mancal_hole_dia", 0.0, 4.75, 1.3)
    draw_single_circle(sk7_holes, "0 mm", "-(mancal_j / 2)", "mancal_hole_dia", 0.0, -4.75, 1.3)
    print(f"Mancal Sk Furos fullyConstrained: {sk7_holes.isFullyConstrained}")

    profs7_h = adsk.core.ObjectCollection.create()
    for prf in sk7_holes.profiles: profs7_h.add(prf)
    ext7_h_in = root7.features.extrudeFeatures.createInput(profs7_h, adsk.fusion.FeatureOperations.CutFeatureOperation)
    ext7_h_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("mancal_base_th + 1.0 mm"))
    root7.features.extrudeFeatures.add(ext7_h_in)

    # Carcaça central esférica (Plano YZ em X = -mancal_l/2)
    yz7 = root7.yZConstructionPlane
    pl7_c_in = root7.constructionPlanes.createInput()
    pl7_c_in.setByOffset(yz7, adsk.core.ValueInput.createByString("-(mancal_l / 2)"))
    pl7_c = root7.constructionPlanes.add(pl7_c_in)

    sk7_cyl = root7.sketches.add(pl7_c)
    sk7_cyl.name = "Esboco_Carcaca_Central"
    draw_single_circle(sk7_cyl, "-mancal_center_h", "0 mm", "housing_dia", -3.33, 0.0, 7.0)
    draw_single_circle(sk7_cyl, "-mancal_center_h", "0 mm", "bearing_bore", -3.33, 0.0, 2.0)
    print(f"Mancal Sk Carcaca fullyConstrained: {sk7_cyl.isFullyConstrained}")

    prof7_c = None
    for p in sk7_cyl.profiles:
        if p.areaProperties().area > 5.0: prof7_c = p; break
    if not prof7_c: prof7_c = sk7_cyl.profiles.item(0)

    ext7_c_in = root7.features.extrudeFeatures.createInput(prof7_c, adsk.fusion.FeatureOperations.JoinFeatureOperation)
    ext7_c_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("mancal_l"))
    root7.features.extrudeFeatures.add(ext7_c_in)

    set_color(b_mancal, root7, 0.15, 0.48, 0.28, "Verde_Ferro_Fundido_Mancal")
    export_and_save(doc7, "07_Mancal_Pillow_Block_UCP204", target_folder)
    print("07_Mancal_Pillow_Block_UCP204 concluido com sucesso!")

    # =========================================================================
    # 8. 08_Disco_Desbalanceamento_Calibrado (D=120mm, Furos R=35 e R=48)
    # =========================================================================
    print("8. Modelando 08_Disco_Desbalanceamento_Calibrado...")
    clean_files(["08_Disco_Desbalanceamento_Calibrado"])
    doc8 = app.documents.add(adsk.core.DocumentTypes.FusionDesignDocumentType)
    des8 = adsk.fusion.Design.cast(doc8.products.itemByProductType("DesignProductType"))
    des8.designType = adsk.fusion.DesignTypes.ParametricDesignType
    root8 = des8.rootComponent
    up8 = des8.userParameters

    up8.add("disco_dia", adsk.core.ValueInput.createByString("120.0 mm"), "mm", "Diametro disco inercia")
    up8.add("disco_th", adsk.core.ValueInput.createByString("12.0 mm"), "mm", "Espessura do disco")
    up8.add("disco_bore", adsk.core.ValueInput.createByString("20.0 mm"), "mm", "Furo eixo 20mm")
    up8.add("hub_dia", adsk.core.ValueInput.createByString("42.0 mm"), "mm", "Diametro cubo fixacao")
    up8.add("hub_len", adsk.core.ValueInput.createByString("14.0 mm"), "mm", "Saliente do cubo")
    up8.add("hole_r1", adsk.core.ValueInput.createByString("35.0 mm"), "mm", "Raio circulo furos 1")
    up8.add("hole_r2", adsk.core.ValueInput.createByString("48.0 mm"), "mm", "Raio circulo furos 2")
    up8.add("furo_m6_dia", adsk.core.ValueInput.createByString("6.0 mm"), "mm", "Diametro furos M6 massas")

    yz8 = root8.yZConstructionPlane
    sk8_1 = root8.sketches.add(yz8)
    sk8_1.name = "Esboco_Disco_Principal"
    draw_single_circle(sk8_1, "0 mm", "0 mm", "disco_dia", 0, 0, 12.0)
    draw_single_circle(sk8_1, "0 mm", "0 mm", "disco_bore", 0, 0, 2.0)

    # 4 Furos M6 a 90 graus em R=35mm (Sketch X e Y)
    draw_single_circle(sk8_1, "hole_r1", "0 mm", "furo_m6_dia", 3.5, 0.0, 0.6)
    draw_single_circle(sk8_1, "-hole_r1", "0 mm", "furo_m6_dia", -3.5, 0.0, 0.6)
    draw_single_circle(sk8_1, "0 mm", "hole_r1", "furo_m6_dia", 0.0, 3.5, 0.6)
    draw_single_circle(sk8_1, "0 mm", "-hole_r1", "furo_m6_dia", 0.0, -3.5, 0.6)
    
    # 4 Furos M6 a 90 graus em R=48mm (Sketch X e Y)
    draw_single_circle(sk8_1, "hole_r2", "0 mm", "furo_m6_dia", 4.8, 0.0, 0.6)
    draw_single_circle(sk8_1, "-hole_r2", "0 mm", "furo_m6_dia", -4.8, 0.0, 0.6)
    draw_single_circle(sk8_1, "0 mm", "hole_r2", "furo_m6_dia", 0.0, 4.8, 0.6)
    draw_single_circle(sk8_1, "0 mm", "-hole_r2", "furo_m6_dia", 0.0, -4.8, 0.6)
    print(f"Disco Sk1 fullyConstrained: {sk8_1.isFullyConstrained}")

    prof8_1 = None
    for p in sk8_1.profiles:
        if p.areaProperties().area > 40.0: prof8_1 = p; break
    if not prof8_1: prof8_1 = sk8_1.profiles.item(0)

    ext8_1_in = root8.features.extrudeFeatures.createInput(prof8_1, adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext8_1_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("disco_th"))
    ext8_1 = root8.features.extrudeFeatures.add(ext8_1_in)
    b_disco = ext8_1.bodies.item(0)

    # Cubo central saliente
    sk8_hub = root8.sketches.add(yz8)
    sk8_hub.name = "Esboco_Cubo_Central"
    draw_single_circle(sk8_hub, "0 mm", "0 mm", "hub_dia", 0, 0, 4.2)
    draw_single_circle(sk8_hub, "0 mm", "0 mm", "disco_bore", 0, 0, 2.0)
    print(f"Disco Sk Cubo fullyConstrained: {sk8_hub.isFullyConstrained}")

    prof8_h = None
    for p in sk8_hub.profiles:
        if p.areaProperties().area > 4.0: prof8_h = p; break
    if not prof8_h: prof8_h = sk8_hub.profiles.item(0)

    ext8_h_in = root8.features.extrudeFeatures.createInput(prof8_h, adsk.fusion.FeatureOperations.JoinFeatureOperation)
    ext8_h_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("-hub_len"))
    root8.features.extrudeFeatures.add(ext8_h_in)

    set_color(b_disco, root8, 0.82, 0.83, 0.85, "Aco_Usinado_Disco")
    export_and_save(doc8, "08_Disco_Desbalanceamento_Calibrado", target_folder)
    print("08_Disco_Desbalanceamento_Calibrado concluido com sucesso!")

    # =========================================================================
    # 9. 09_Subplaca_Troca_Rapida_Mancal (140 x 60 x 15 mm)
    # =========================================================================
    print("9. Modelando 09_Subplaca_Troca_Rapida_Mancal...")
    clean_files(["09_Subplaca_Troca_Rapida_Mancal"])
    doc9 = app.documents.add(adsk.core.DocumentTypes.FusionDesignDocumentType)
    des9 = adsk.fusion.Design.cast(doc9.products.itemByProductType("DesignProductType"))
    des9.designType = adsk.fusion.DesignTypes.ParametricDesignType
    root9 = des9.rootComponent
    up9 = des9.userParameters

    up9.add("sub_l", adsk.core.ValueInput.createByString("60.0 mm"), "mm", "Comprimento X subplaca")
    up9.add("sub_w", adsk.core.ValueInput.createByString("140.0 mm"), "mm", "Largura Y subplaca")
    up9.add("sub_th", adsk.core.ValueInput.createByString("15.0 mm"), "mm", "Espessura da subplaca")
    up9.add("sub_j", adsk.core.ValueInput.createByString("95.0 mm"), "mm", "Entre-furos mancal")
    up9.add("dowel_dia", adsk.core.ValueInput.createByString("8.0 mm"), "mm", "Furo pino guia dowel")
    up9.add("sub_hole_dia", adsk.core.ValueInput.createByString("11.0 mm"), "mm", "Furo passante M10 mesa")

    xy9 = root9.xYConstructionPlane
    sk9_1 = root9.sketches.add(xy9)
    sk9_1.name = "Esboco_Subplaca"
    draw_centered_rect(sk9_1, "sub_l", "sub_w", 6.0, 14.0)
    print(f"Subplaca Sk1 fullyConstrained: {sk9_1.isFullyConstrained}")

    ext9_1_in = root9.features.extrudeFeatures.createInput(sk9_1.profiles.item(0), adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext9_1_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("sub_th"))
    ext9_1 = root9.features.extrudeFeatures.add(ext9_1_in)
    b_sub = ext9_1.bodies.item(0)

    # 2 Furos de fixação M10 para a mesa em J = 95mm
    sk9_h = root9.sketches.add(xy9)
    sk9_h.name = "Esboco_Furos_M10"
    draw_single_circle(sk9_h, "0 mm", "sub_j / 2", "sub_hole_dia", 0.0, 4.75, 1.1)
    draw_single_circle(sk9_h, "0 mm", "-(sub_j / 2)", "sub_hole_dia", 0.0, -4.75, 1.1)
    # 2 Furos para pinos-guia Dowel Pin em Y = +- 60 mm
    up9.add("dowel_dy", adsk.core.ValueInput.createByString("120.0 mm"), "mm", "Distancia pinos guia")
    draw_single_circle(sk9_h, "0 mm", "dowel_dy / 2", "dowel_dia", 0.0, 6.0, 0.8)
    draw_single_circle(sk9_h, "0 mm", "-(dowel_dy / 2)", "dowel_dia", 0.0, -6.0, 0.8)
    print(f"Subplaca Sk Furos fullyConstrained: {sk9_h.isFullyConstrained}")

    profs9_h = adsk.core.ObjectCollection.create()
    for prf in sk9_h.profiles: profs9_h.add(prf)
    ext9_h_in = root9.features.extrudeFeatures.createInput(profs9_h, adsk.fusion.FeatureOperations.CutFeatureOperation)
    ext9_h_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("sub_th + 1.0 mm"))
    root9.features.extrudeFeatures.add(ext9_h_in)

    set_color(b_sub, root9, 0.75, 0.77, 0.80, "Aluminio_Anodizado_Subplaca")
    export_and_save(doc9, "09_Subplaca_Troca_Rapida_Mancal", target_folder)
    print("09_Subplaca_Troca_Rapida_Mancal concluida com sucesso!")

    # =========================================================================
    # 10. 10_Parafuso_Fixacao_M10_Allen (Cabeca D=16mm, H=10mm, Rosca M10 x 30mm)
    # =========================================================================
    print("10. Modelando 10_Parafuso_Fixacao_M10_Allen...")
    clean_files(["10_Parafuso_Fixacao_M10_Allen"])
    doc10 = app.documents.add(adsk.core.DocumentTypes.FusionDesignDocumentType)
    des10 = adsk.fusion.Design.cast(doc10.products.itemByProductType("DesignProductType"))
    des10.designType = adsk.fusion.DesignTypes.ParametricDesignType
    root10 = des10.rootComponent
    up10 = des10.userParameters

    up10.add("screw_m10_head_dia", adsk.core.ValueInput.createByString("16.0 mm"), "mm", "Cabeca parafuso M10")
    up10.add("screw_m10_head_h", adsk.core.ValueInput.createByString("10.0 mm"), "mm", "Altura cabeca")
    up10.add("screw_m10_thread_dia", adsk.core.ValueInput.createByString("10.0 mm"), "mm", "Diametro rosca M10")
    up10.add("screw_m10_len", adsk.core.ValueInput.createByString("30.0 mm"), "mm", "Comprimento rosca")

    xy10 = root10.xYConstructionPlane
    sk10_1 = root10.sketches.add(xy10)
    sk10_1.name = "Esboco_Cabeca_M10"
    draw_single_circle(sk10_1, "0 mm", "0 mm", "screw_m10_head_dia", 0, 0, 1.6)
    print(f"Parafuso M10 Sk1 fullyConstrained: {sk10_1.isFullyConstrained}")

    ext10_1_in = root10.features.extrudeFeatures.createInput(sk10_1.profiles.item(0), adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext10_1_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("screw_m10_head_h"))
    ext10_1 = root10.features.extrudeFeatures.add(ext10_1_in)
    b_screw = ext10_1.bodies.item(0)

    sk10_2 = root10.sketches.add(xy10)
    sk10_2.name = "Esboco_Haste_M10"
    draw_single_circle(sk10_2, "0 mm", "0 mm", "screw_m10_thread_dia", 0, 0, 1.0)
    print(f"Parafuso M10 Sk2 fullyConstrained: {sk10_2.isFullyConstrained}")

    ext10_2_in = root10.features.extrudeFeatures.createInput(sk10_2.profiles.item(0), adsk.fusion.FeatureOperations.JoinFeatureOperation)
    ext10_2_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("-screw_m10_len"))
    root10.features.extrudeFeatures.add(ext10_2_in)

    set_color(b_screw, root10, 0.20, 0.20, 0.22, "Aco_Oxidado_Negro")
    export_and_save(doc10, "10_Parafuso_Fixacao_M10_Allen", target_folder)
    print("10_Parafuso_Fixacao_M10_Allen concluido com sucesso!")

    print("TODOS OS 10 COMPONENTES DA BANCADA FORAM MODELADOS COM SUCESSO!")
'''

if __name__ == "__main__":
    sess = init_session()
    print("Iniciando construcao de todas as pecas Bottom-Up da Bancada de Testes...")
    res = execute_script(sess, SCRIPT_PARTS, read_only=False)
    print("Resultado:")
    print(json.dumps(res, indent=2))
