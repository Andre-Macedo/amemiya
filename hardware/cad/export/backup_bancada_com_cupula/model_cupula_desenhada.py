import urllib.request
import json
import time
import os

FUSION_URL = "http://127.0.0.1:27182/mcp"
EXPORT_DIR = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\export\bancada"

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
        "clientInfo": {"name": "model_cupula_desenhada", "version": "1.0"}
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

SCRIPT_MODEL_CUPULA = r'''
import adsk.core, adsk.fusion, os, math, time

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

    # 1. Modelar 14_Cupula_Estrutural_2020 na versao original solicitada (alca preta e passa-cabos integrados/desenhados)
    print("Modelando 14_Cupula_Estrutural_2020 versao solicitada pelo usuario...")
    delete_old_file(folder, "14_Cupula_Estrutural_2020")

    doc14 = app.documents.add(adsk.core.DocumentTypes.FusionDesignDocumentType)
    des14 = adsk.fusion.Design.cast(doc14.products.itemByProductType("DesignProductType"))
    des14.designType = adsk.fusion.DesignTypes.ParametricDesignType
    root14 = des14.rootComponent

    # Funcao de secao transversal 2020 (em cm)
    def get_2020_points(u_c=0.0, v_c=0.0):
        pts_rel = [
            (0.85, 1.0), (0.35, 1.0), (0.30, 0.95), (0.30, 0.85), (0.475, 0.85), (0.475, 0.60),
            (-0.475, 0.60), (-0.475, 0.85), (-0.30, 0.85), (-0.30, 0.95), (-0.35, 1.0), (-0.85, 1.0),
            (-1.0, 0.85), (-1.0, 0.35), (-0.95, 0.30), (-0.85, 0.30), (-0.85, 0.475), (-0.60, 0.475),
            (-0.60, -0.475), (-0.85, -0.475), (-0.85, -0.30), (-0.95, -0.30), (-1.0, -0.35), (-1.0, -0.85),
            (-0.85, -1.0), (-0.35, -1.0), (-0.30, -0.95), (-0.30, -0.85), (-0.475, -0.85), (-0.475, -0.60),
            (0.475, -0.60), (0.475, -0.85), (0.30, -0.85), (0.30, -0.95), (0.35, -1.0), (0.85, -1.0),
            (1.0, -0.85), (1.0, -0.35), (0.95, -0.30), (0.85, -0.30), (0.85, -0.475), (0.60, -0.475),
            (0.60, 0.475), (0.85, 0.475), (0.85, 0.30), (0.95, 0.30), (1.0, 0.35), (1.0, 0.85)
        ]
        return [(u + u_c, v + v_c) for u, v in pts_rel]

    # Helper para extrudar perfil 2020 ao longo de Z
    def add_perfil_z(cx, cy, z_start, length, name):
        plane = root14.xYConstructionPlane
        if abs(z_start) > 0.001:
            p_in = root14.constructionPlanes.createInput()
            p_in.setByOffset(plane, adsk.core.ValueInput.createByString(f"{z_start*10:.2f} mm"))
            plane = root14.constructionPlanes.add(p_in)
        sk = root14.sketches.add(plane)
        lines = sk.sketchCurves.sketchLines
        pts = get_2020_points(cx, cy)
        for i in range(len(pts)):
            p1 = adsk.core.Point3D.create(pts[i][0], pts[i][1], 0)
            p2 = adsk.core.Point3D.create(pts[(i+1)%len(pts)][0], pts[(i+1)%len(pts)][1], 0)
            lines.addByTwoPoints(p1, p2)
        sk.sketchCurves.sketchCircles.addByCenterRadius(adsk.core.Point3D.create(cx, cy, 0), 0.25)
        target_prof = max(list(sk.profiles), key=lambda p: p.areaProperties().area)
        ext_in = root14.features.extrudeFeatures.createInput(target_prof, adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
        ext_in.setDistanceExtent(False, adsk.core.ValueInput.createByString(f"{length*10:.2f} mm"))
        ext = root14.features.extrudeFeatures.add(ext_in)
        b = ext.bodies.item(0)
        b.name = name
        return b

    # Helper para extrudar perfil 2020 ao longo de X
    def add_perfil_x(cy, cz, x_start, length, name):
        plane = root14.yZConstructionPlane
        if abs(x_start) > 0.001:
            p_in = root14.constructionPlanes.createInput()
            p_in.setByOffset(plane, adsk.core.ValueInput.createByString(f"{x_start*10:.2f} mm"))
            plane = root14.constructionPlanes.add(p_in)
        sk = root14.sketches.add(plane)
        lines = sk.sketchCurves.sketchLines
        pts = get_2020_points(cy, cz)
        for i in range(len(pts)):
            p1 = adsk.core.Point3D.create(0, pts[i][0], pts[i][1])
            p2 = adsk.core.Point3D.create(0, pts[(i+1)%len(pts)][0], pts[(i+1)%len(pts)][1])
            lines.addByTwoPoints(p1, p2)
        sk.sketchCurves.sketchCircles.addByCenterRadius(adsk.core.Point3D.create(0, cy, cz), 0.25)
        target_prof = max(list(sk.profiles), key=lambda p: p.areaProperties().area)
        ext_in = root14.features.extrudeFeatures.createInput(target_prof, adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
        ext_in.setDistanceExtent(False, adsk.core.ValueInput.createByString(f"{length*10:.2f} mm"))
        ext = root14.features.extrudeFeatures.add(ext_in)
        b = ext.bodies.item(0)
        b.name = name
        return b

    # Helper para extrudar perfil 2020 ao longo de Y
    def add_perfil_y(cx, cz, y_start, length, name):
        plane = root14.xZConstructionPlane
        if abs(y_start) > 0.001:
            p_in = root14.constructionPlanes.createInput()
            p_in.setByOffset(plane, adsk.core.ValueInput.createByString(f"{y_start*10:.2f} mm"))
            plane = root14.constructionPlanes.add(p_in)
        sk = root14.sketches.add(plane)
        lines = sk.sketchCurves.sketchLines
        pts = get_2020_points(cx, cz)
        for i in range(len(pts)):
            p1 = adsk.core.Point3D.create(pts[i][0], 0, pts[i][1])
            p2 = adsk.core.Point3D.create(pts[(i+1)%len(pts)][0], 0, pts[(i+1)%len(pts)][1])
            lines.addByTwoPoints(p1, p2)
        sk.sketchCurves.sketchCircles.addByCenterRadius(adsk.core.Point3D.create(cx, 0, cz), 0.25)
        target_prof = max(list(sk.profiles), key=lambda p: p.areaProperties().area)
        ext_in = root14.features.extrudeFeatures.createInput(target_prof, adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
        ext_in.setDistanceExtent(False, adsk.core.ValueInput.createByString(f"{length*10:.2f} mm"))
        ext = root14.features.extrudeFeatures.add(ext_in)
        b = ext.bodies.item(0)
        b.name = name
        return b

    col_bodies = []
    # 4 Colunas Verticais 2020 (Altura 170mm)
    col_bodies.append(add_perfil_z(-20.0, -9.75, 0.0, 17.0, "Coluna_2020_Front_Left"))
    col_bodies.append(add_perfil_z(20.0, -9.75, 0.0, 17.0, "Coluna_2020_Front_Right"))
    col_bodies.append(add_perfil_z(-20.0, 9.75, 0.0, 17.0, "Coluna_2020_Rear_Left"))
    col_bodies.append(add_perfil_z(20.0, 9.75, 0.0, 17.0, "Coluna_2020_Rear_Right"))

    # 4 Vigas Longitudinais 2020 em X (380mm)
    col_bodies.append(add_perfil_x(-9.75, 1.0, -19.0, 38.0, "Viga_2020_Front_Bottom"))
    col_bodies.append(add_perfil_x(9.75, 1.0, -19.0, 38.0, "Viga_2020_Rear_Bottom"))
    col_bodies.append(add_perfil_x(-9.75, 16.0, -19.0, 38.0, "Viga_2020_Front_Top"))
    col_bodies.append(add_perfil_x(9.75, 16.0, -19.0, 38.0, "Viga_2020_Rear_Top"))

    # 4 Travessas Transversais 2020 em Y (175mm)
    col_bodies.append(add_perfil_y(-20.0, 1.0, -8.75, 17.5, "Travessa_2020_Left_Bottom"))
    col_bodies.append(add_perfil_y(20.0, 1.0, -8.75, 17.5, "Travessa_2020_Right_Bottom"))
    col_bodies.append(add_perfil_y(-20.0, 16.0, -8.75, 17.5, "Travessa_2020_Left_Top"))
    col_bodies.append(add_perfil_y(20.0, 16.0, -8.75, 17.5, "Travessa_2020_Right_Top"))

    # Painéis de Policarbonato Cristal (4mm)
    panels = []
    # Teto
    plane_teto_in = root14.constructionPlanes.createInput()
    plane_teto_in.setByOffset(root14.xYConstructionPlane, adsk.core.ValueInput.createByString("166.0 mm"))
    plane_teto = root14.constructionPlanes.add(plane_teto_in)
    sk_teto = root14.sketches.add(plane_teto)
    sk_teto.sketchCurves.sketchLines.addTwoPointRectangle(adsk.core.Point3D.create(-19.0, -8.75, 0), adsk.core.Point3D.create(19.0, 8.75, 0))
    ext_teto_in = root14.features.extrudeFeatures.createInput(sk_teto.profiles.item(0), adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext_teto_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("4.0 mm"))
    ext_teto = root14.features.extrudeFeatures.add(ext_teto_in)
    b_teto = ext_teto.bodies.item(0); b_teto.name = "Painel_Teto_Policarbonato"; panels.append(b_teto)

    # Painel Frontal
    plane_pf_in = root14.constructionPlanes.createInput()
    plane_pf_in.setByOffset(root14.xZConstructionPlane, adsk.core.ValueInput.createByString("-99.5 mm"))
    plane_pf = root14.constructionPlanes.add(plane_pf_in)
    sk_pf = root14.sketches.add(plane_pf)
    p1_pf = sk_pf.modelToSketchSpace(adsk.core.Point3D.create(-19.0, -9.95, 2.0))
    p2_pf = sk_pf.modelToSketchSpace(adsk.core.Point3D.create(19.0, -9.95, 15.0))
    sk_pf.sketchCurves.sketchLines.addTwoPointRectangle(p1_pf, p2_pf)
    ext_pf_in = root14.features.extrudeFeatures.createInput(sk_pf.profiles.item(0), adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext_pf_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("4.0 mm"))
    ext_pf = root14.features.extrudeFeatures.add(ext_pf_in)
    b_pf = ext_pf.bodies.item(0); b_pf.name = "Painel_Frontal_Policarbonato"; panels.append(b_pf)

    # Painel Traseiro com os 3 furos de passa-cabos
    plane_pt_in = root14.constructionPlanes.createInput()
    plane_pt_in.setByOffset(root14.xZConstructionPlane, adsk.core.ValueInput.createByString("95.5 mm"))
    plane_pt = root14.constructionPlanes.add(plane_pt_in)
    sk_pt = root14.sketches.add(plane_pt)
    p1_pt = sk_pt.modelToSketchSpace(adsk.core.Point3D.create(-19.0, 9.55, 2.0))
    p2_pt = sk_pt.modelToSketchSpace(adsk.core.Point3D.create(19.0, 9.55, 15.0))
    sk_pt.sketchCurves.sketchLines.addTwoPointRectangle(p1_pt, p2_pt)
    for gx in [-13.0, 0.5, 14.0]:
        pt_hole = sk_pt.modelToSketchSpace(adsk.core.Point3D.create(gx, 9.55, 8.5))
        sk_pt.sketchCurves.sketchCircles.addByCenterRadius(pt_hole, 0.9)
    prof_pt = max(list(sk_pt.profiles), key=lambda p: p.areaProperties().area)
    ext_pt_in = root14.features.extrudeFeatures.createInput(prof_pt, adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext_pt_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("4.0 mm"))
    ext_pt = root14.features.extrudeFeatures.add(ext_pt_in)
    b_pt = ext_pt.bodies.item(0); b_pt.name = "Painel_Traseiro_Policarbonato"; panels.append(b_pt)

    # Painel Lateral Esquerdo
    plane_ple_in = root14.constructionPlanes.createInput()
    plane_ple_in.setByOffset(root14.yZConstructionPlane, adsk.core.ValueInput.createByString("-199.5 mm"))
    plane_ple = root14.constructionPlanes.add(plane_ple_in)
    sk_ple = root14.sketches.add(plane_ple)
    p1_ple = sk_ple.modelToSketchSpace(adsk.core.Point3D.create(-19.95, -8.75, 2.0))
    p2_ple = sk_ple.modelToSketchSpace(adsk.core.Point3D.create(-19.95, 8.75, 15.0))
    sk_ple.sketchCurves.sketchLines.addTwoPointRectangle(p1_ple, p2_ple)
    ext_ple_in = root14.features.extrudeFeatures.createInput(sk_ple.profiles.item(0), adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext_ple_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("4.0 mm"))
    ext_ple = root14.features.extrudeFeatures.add(ext_ple_in)
    b_ple = ext_ple.bodies.item(0); b_ple.name = "Painel_Lateral_Esq_Policarbonato"; panels.append(b_ple)

    # Painel Lateral Direito
    plane_pld_in = root14.constructionPlanes.createInput()
    plane_pld_in.setByOffset(root14.yZConstructionPlane, adsk.core.ValueInput.createByString("195.5 mm"))
    plane_pld = root14.constructionPlanes.add(plane_pld_in)
    sk_pld = root14.sketches.add(plane_pld)
    p1_pld = sk_pld.modelToSketchSpace(adsk.core.Point3D.create(19.55, -8.75, 2.0))
    p2_pld = sk_pld.modelToSketchSpace(adsk.core.Point3D.create(19.55, 8.75, 15.0))
    sk_pld.sketchCurves.sketchLines.addTwoPointRectangle(p1_pld, p2_pld)
    ext_pld_in = root14.features.extrudeFeatures.createInput(sk_pld.profiles.item(0), adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext_pld_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("4.0 mm"))
    ext_pld = root14.features.extrudeFeatures.add(ext_pld_in)
    b_pld = ext_pld.bodies.item(0); b_pld.name = "Painel_Lateral_Dir_Policarbonato"; panels.append(b_pld)

    # Alca Puxador Preta na viga frontal
    plane_zh_in = root14.constructionPlanes.createInput()
    plane_zh_in.setByOffset(root14.xYConstructionPlane, adsk.core.ValueInput.createByString("85.0 mm"))
    plane_zh = root14.constructionPlanes.add(plane_zh_in)
    sk_h = root14.sketches.add(plane_zh)
    lh = sk_h.sketchCurves.sketchLines
    lh.addByTwoPoints(adsk.core.Point3D.create(-5.8, -10.75, 0), adsk.core.Point3D.create(5.8, -10.75, 0))
    lh.addByTwoPoints(adsk.core.Point3D.create(5.8, -10.75, 0), adsk.core.Point3D.create(5.8, -14.55, 0))
    lh.addByTwoPoints(adsk.core.Point3D.create(5.8, -14.55, 0), adsk.core.Point3D.create(-5.8, -14.55, 0))
    lh.addByTwoPoints(adsk.core.Point3D.create(-5.8, -14.55, 0), adsk.core.Point3D.create(-5.8, -10.75, 0))
    lh.addByTwoPoints(adsk.core.Point3D.create(-4.2, -10.75, 0), adsk.core.Point3D.create(4.2, -10.75, 0))
    lh.addByTwoPoints(adsk.core.Point3D.create(4.2, -10.75, 0), adsk.core.Point3D.create(4.2, -13.15, 0))
    lh.addByTwoPoints(adsk.core.Point3D.create(4.2, -13.15, 0), adsk.core.Point3D.create(-4.2, -13.15, 0))
    lh.addByTwoPoints(adsk.core.Point3D.create(-4.2, -13.15, 0), adsk.core.Point3D.create(-4.2, -10.75, 0))
    prof_h = max(list(sk_h.profiles), key=lambda p: p.areaProperties().area)
    ext_h_in = root14.features.extrudeFeatures.createInput(prof_h, adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
    ext_h_in.setDistanceExtent(True, adsk.core.ValueInput.createByString("8.0 mm"))
    ext_h = root14.features.extrudeFeatures.add(ext_h_in)
    b_handle = ext_h.bodies.item(0)
    b_handle.name = "Alca_Puxador_Preta"

    edges_h = adsk.core.ObjectCollection.create()
    for e in b_handle.edges:
        if e.pointOnEdge.y < -14.0:
            edges_h.add(e)
    if edges_h.count > 0:
        fil_h = root14.features.filletFeatures.createInput()
        fil_h.addConstantRadiusEdgeSet(edges_h, adsk.core.ValueInput.createByString("3.0 mm"), True)
        root14.features.filletFeatures.add(fil_h)

    # Os 3 Passa-Cabos da versao solicitada (desenhados como relevo/features na traseira)
    grommet_bodies = []
    for gx in [-13.0, 0.5, 14.0]:
        p_out_in = root14.constructionPlanes.createInput()
        p_out_in.setByOffset(root14.xZConstructionPlane, adsk.core.ValueInput.createByString("99.5 mm"))
        p_out = root14.constructionPlanes.add(p_out_in)
        sk_g = root14.sketches.add(p_out)
        pt_c = sk_g.modelToSketchSpace(adsk.core.Point3D.create(gx, 9.95, 8.5))
        sk_g.sketchCurves.sketchCircles.addByCenterRadius(pt_c, 1.2)
        sk_g.sketchCurves.sketchCircles.addByCenterRadius(pt_c, 0.35)
        prof_g = max(list(sk_g.profiles), key=lambda p: p.areaProperties().area)
        ext_g_in = root14.features.extrudeFeatures.createInput(prof_g, adsk.fusion.FeatureOperations.NewBodyFeatureOperation)
        ext_g_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("2.0 mm"))
        ext_g = root14.features.extrudeFeatures.add(ext_g_in)
        bg = ext_g.bodies.item(0)
        
        ext_neck_in = root14.features.extrudeFeatures.createInput(prof_g, adsk.fusion.FeatureOperations.JoinFeatureOperation)
        ext_neck_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("-4.0 mm"))
        root14.features.extrudeFeatures.add(ext_neck_in)
        
        p_in_in = root14.constructionPlanes.createInput()
        p_in_in.setByOffset(root14.xZConstructionPlane, adsk.core.ValueInput.createByString("95.5 mm"))
        p_in = root14.constructionPlanes.add(p_in_in)
        sk_in = root14.sketches.add(p_in)
        pt_in = sk_in.modelToSketchSpace(adsk.core.Point3D.create(gx, 9.55, 8.5))
        sk_in.sketchCurves.sketchCircles.addByCenterRadius(pt_in, 1.2)
        sk_in.sketchCurves.sketchCircles.addByCenterRadius(pt_in, 0.35)
        prof_in = max(list(sk_in.profiles), key=lambda p: p.areaProperties().area)
        ext_in_in = root14.features.extrudeFeatures.createInput(prof_in, adsk.fusion.FeatureOperations.JoinFeatureOperation)
        ext_in_in.setDistanceExtent(False, adsk.core.ValueInput.createByString("-2.0 mm"))
        root14.features.extrudeFeatures.add(ext_in_in)
        bg.name = f"Passa_Cabo_Traseiro_{gx}"
        grommet_bodies.append(bg)

    # Materiais Realistas
    app_alum = get_mat_app(des14, "Mat_Alum_Acetinado_Real", "acetinado", 215, 218, 222)
    app_poly = get_mat_app(des14, "Mat_Policarbonato_Cristal_Real", "vidro", 240, 248, 255)
    app_hand = get_mat_app(des14, "Mat_Poliamida_Preta_Real", "plástico", 28, 28, 30)
    app_grom = get_mat_app(des14, "Mat_Borracha_Preta_Real", "borracha", 30, 30, 32)

    for b in col_bodies:
        b.appearance = app_alum
    for p in panels:
        p.appearance = app_poly
    b_handle.appearance = app_hand
    for g in grommet_bodies:
        g.appearance = app_grom

    step14 = os.path.join(EXPORT_DIR, "14_Cupula_Estrutural_2020.step")
    des14.exportManager.execute(des14.exportManager.createSTEPExportOptions(step14))
    doc14.saveAs("14_Cupula_Estrutural_2020", folder, "Cupula NR-12 com Alca Preta e Passa-Cabos Desenhados", "")
    doc14.close(False)
    print("14_Cupula_Estrutural_2020 gravada com sucesso!")
'''

if __name__ == "__main__":
    sess = init_session()
    print("Executando modelagem da 14_Cupula_Estrutural_2020 na versao solicitada...")
    res = execute_script(sess, SCRIPT_MODEL_CUPULA)
    if res and "result" in res and "content" in res["result"]:
        print(res["result"]["content"][0]["text"])
    else:
        print(res)
