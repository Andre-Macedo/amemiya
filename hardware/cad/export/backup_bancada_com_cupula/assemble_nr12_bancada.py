import urllib.request
import json
import time
import os
import math

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
        "clientInfo": {"name": "assemble_nr12", "version": "1.0"}
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

SCRIPT_ASSEMBLE_NR12 = r'''
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

    print("Reconstruindo amemiya_bancada_testes_assembly com juntas articuladas e pes encostados...")
    delete_old_file(amemiya_proj.rootFolder, "amemiya_bancada_testes_assembly")

    files = {df.name: df for df in folder.dataFiles}

    doc_ass = app.documents.add(adsk.core.DocumentTypes.FusionDesignDocumentType)
    doc_ass.saveAs("amemiya_bancada_testes_assembly", amemiya_proj.rootFolder, "Montagem Bancada NR-12 com Tampa Articulada e Perfis Normatizados", "")
    doc_ass.activate()

    design_ass = adsk.fusion.Design.cast(doc_ass.products.itemByProductType("DesignProductType"))
    root_ass = design_ass.rootComponent

    # Funcao auxiliar para adicionar AsBuiltJoint rígida
    def add_rigid_joint(occ1, occ2, name):
        try:
            jin = root_ass.asBuiltJoints.createInput(occ1, occ2, None)
            jin.setAsRigidJointMotion()
            j = root_ass.asBuiltJoints.add(jin)
            j.name = name
            return j
        except Exception as e:
            print(f"Aviso junta {name}: {e}")
            return None

    # 1. Chassi Base (01_Mesa_Base_Bancada com Perfis Normatizados Chanfrados) Aterrado
    occ_chassi = root_ass.occurrences.addByInsert(files["01_Mesa_Base_Bancada"], adsk.core.Matrix3D.create(), True)
    occ_chassi.isGrounded = True

    # 2. Sapatas Niveladoras Encostando Perfeitamente na Base de Ferro (Z = -1.2 cm)
    # A base de ferro vai de Z = -1.2 cm ate 0. O topo do colar metalico da sapata esta em Z_local = 0.0 cm.
    # Com Z_trans = -1.2 cm, o colar toca exatamente a face inferior em Z = -1.2 cm (contato 0mm, perfeitamente encostado)!
    idx_sap = 1
    for sx, sy in [(-31.0, -8.75), (-31.0, 8.75), (31.0, -8.75), (31.0, 8.75)]:
        m = adsk.core.Matrix3D.create()
        m.translation = adsk.core.Vector3D.create(sx, sy, -1.2)
        occ_sap = root_ass.occurrences.addByInsert(files["02_Sapata_Niveladora_Borracha"], m, True)
        add_rigid_joint(occ_sap, occ_chassi, f"Junta_Rigida_Sapata_{idx_sap}")
        idx_sap += 1

    # 3. Placa Base Ampliada de Desalinhamento (230 x 160 x 15 mm) em X = -23.0 cm, Z = 4.0 cm
    # Estende de X = -34.5 cm ate X = -11.5 cm!
    m_pl = adsk.core.Matrix3D.create()
    m_pl.translation = adsk.core.Vector3D.create(-23.0, 0.0, 4.0)
    occ_placa = root_ass.occurrences.addByInsert(files["03_Placa_Base_Desalinhamento_Motor"], m_pl, True)
    add_rigid_joint(occ_placa, occ_chassi, "Junta_Rigida_Placa_Desalinhamento")

    # 4. Parafusos Jacking de Elevacao M8 (X = +-100.0 mm, Y = +-67.5 mm - CENTRO DO PERFIL!)
    idx_jk = 1
    for jx, jy in [(-23.0 - 10.0, -6.75), (-23.0 - 10.0, 6.75), (-23.0 + 10.0, -6.75), (-23.0 + 10.0, 6.75)]:
        m = adsk.core.Matrix3D.create()
        m.translation = adsk.core.Vector3D.create(jx, jy, 5.5)
        occ_jk = root_ass.occurrences.addByInsert(files["12_Parafuso_Jacking_Elevacao_M8"], m, True)
        add_rigid_joint(occ_jk, occ_placa, f"Junta_Rigida_Jacking_{idx_jk}")
        idx_jk += 1

    # 5. Parafusos M10 Allen nos Rasgos Oblongos (X = +-75.0 mm, Y = +-47.5 mm)
    idx_m10 = 1
    for sx, sy in [(-23.0 - 7.5, -4.75), (-23.0 - 7.5, 4.75), (-23.0 + 7.5, -4.75), (-23.0 + 7.5, 4.75)]:
        m = adsk.core.Matrix3D.create()
        m.translation = adsk.core.Vector3D.create(sx, sy, 5.5)
        occ_m10 = root_ass.occurrences.addByInsert(files["10_Parafuso_Fixacao_M10_Allen"], m, True)
        add_rigid_joint(occ_m10, occ_chassi, f"Junta_Rigida_M10_Mesa_{idx_m10}")
        idx_m10 += 1

    # 6. Placas de Encosto no Trilho Lateral Externo (Y = +-107.5 mm, Z = 0.0 cm)
    # Placa Esquerda (+Y = 10.75 cm)
    m_blk_l = adsk.core.Matrix3D.create()
    m_blk_l.translation = adsk.core.Vector3D.create(-23.0, 10.75, 0.0)
    occ_blk_l = root_ass.occurrences.addByInsert(files["11_Bloco_Encosto_Push_Pull_Lateral"], m_blk_l, True)
    add_rigid_joint(occ_blk_l, occ_chassi, "Junta_Rigida_Bloco_Lateral_Esq")

    m_b_fix_l = adsk.core.Matrix3D.create()
    m_b_fix_l.setToRotation(-math.pi / 2, adsk.core.Vector3D.create(1, 0, 0), adsk.core.Point3D.create(0, 0, 0))
    m_b_fix_l.translation = adsk.core.Vector3D.create(-23.0, 11.35, 2.0)
    occ_sc_l = root_ass.occurrences.addByInsert(files["10_Parafuso_Fixacao_M10_Allen"], m_b_fix_l, True)
    add_rigid_joint(occ_sc_l, occ_blk_l, "Junta_Rigida_Parafuso_Bloco_Esq")

    m_man_l = adsk.core.Matrix3D.create()
    m_man_l.translation = adsk.core.Vector3D.create(-23.0, 12.8, 4.75)
    occ_man_l = root_ass.occurrences.addByInsert(files["13_Manipulo_Push_Pull_M8"], m_man_l, True)
    add_rigid_joint(occ_man_l, occ_blk_l, "Junta_Rigida_Manipulo_Esq")

    # Placa Direita (-Y = -10.75 cm)
    m_blk_r = adsk.core.Matrix3D.create()
    m_blk_r.setToRotation(math.pi, adsk.core.Vector3D.create(0, 0, 1), adsk.core.Point3D.create(0, 0, 0))
    m_blk_r.translation = adsk.core.Vector3D.create(-23.0, -10.75, 0.0)
    occ_blk_r = root_ass.occurrences.addByInsert(files["11_Bloco_Encosto_Push_Pull_Lateral"], m_blk_r, True)
    add_rigid_joint(occ_blk_r, occ_chassi, "Junta_Rigida_Bloco_Lateral_Dir")

    m_b_fix_r = adsk.core.Matrix3D.create()
    m_b_fix_r.setToRotation(math.pi / 2, adsk.core.Vector3D.create(1, 0, 0), adsk.core.Point3D.create(0, 0, 0))
    m_b_fix_r.translation = adsk.core.Vector3D.create(-23.0, -11.35, 2.0)
    occ_sc_r = root_ass.occurrences.addByInsert(files["10_Parafuso_Fixacao_M10_Allen"], m_b_fix_r, True)
    add_rigid_joint(occ_sc_r, occ_blk_r, "Junta_Rigida_Parafuso_Bloco_Dir")

    m_man_r = adsk.core.Matrix3D.create()
    m_man_r.setToRotation(math.pi, adsk.core.Vector3D.create(0, 0, 1), adsk.core.Point3D.create(0, 0, 0))
    m_man_r.translation = adsk.core.Vector3D.create(-23.0, -12.8, 4.75)
    occ_man_r = root_ass.occurrences.addByInsert(files["13_Manipulo_Push_Pull_M8"], m_man_r, True)
    add_rigid_joint(occ_man_r, occ_blk_r, "Junta_Rigida_Manipulo_Dir")

    # 7. Motor Elétrico Azul WEG (Assentado em Z = 5.5 cm, Eixo em Z = 10.5 cm)
    # Com eixo estendido para 65mm, ponta do eixo em X = -10.0 cm!
    m_mot = adsk.core.Matrix3D.create()
    m_mot.translation = adsk.core.Vector3D.create(-23.0, 0.0, 5.5)
    occ_mot = root_ass.occurrences.addByInsert(files["04_Motor_Eletrico_Trifasico"], m_mot, True)
    add_rigid_joint(occ_mot, occ_placa, "Junta_Rigida_Motor_Placa")

    # 8. Acoplamento Flexivel de Mandibula (Centro em X = -8.0 cm, Z = 10.5 cm)
    m_acop = adsk.core.Matrix3D.create()
    m_acop.translation = adsk.core.Vector3D.create(-8.0, 0.0, 10.5)
    occ_acop = root_ass.occurrences.addByInsert(files["05_Acoplamento_Flexivel_Mandibula"], m_acop, True)
    add_rigid_joint(occ_acop, occ_chassi, "Junta_Rigida_Acoplamento")

    # 9. Subplacas e Mancais A e B
    idx_manc = 1
    for pos_x in [0.5, 27.5]:
        m_s = adsk.core.Matrix3D.create()
        m_s.translation = adsk.core.Vector3D.create(pos_x, 0.0, 4.0)
        occ_sub = root_ass.occurrences.addByInsert(files["09_Subplaca_Troca_Rapida_Mancal"], m_s, True)
        add_rigid_joint(occ_sub, occ_chassi, f"Junta_Rigida_Subplaca_{idx_manc}")

        m_m = adsk.core.Matrix3D.create()
        m_m.translation = adsk.core.Vector3D.create(pos_x, 0.0, 7.17)
        occ_m = root_ass.occurrences.addByInsert(files["07_Mancal_Pillow_Block_UCP204"], m_m, True)
        add_rigid_joint(occ_m, occ_sub, f"Junta_Rigida_Mancal_{idx_manc}")

        idx_b = 1
        for py in [-4.75, 4.75]:
            m_b = adsk.core.Matrix3D.create()
            m_b.translation = adsk.core.Vector3D.create(pos_x, py, 8.67)
            occ_b = root_ass.occurrences.addByInsert(files["10_Parafuso_Fixacao_M10_Allen"], m_b, True)
            add_rigid_joint(occ_b, occ_m, f"Junta_Rigida_Parafuso_Mancal_{idx_manc}_{idx_b}")
            idx_b += 1
        idx_manc += 1

    # 10. Eixo Rotativo Retificado 20mm (Inicia em X = -7.0 cm, Z = 10.5 cm, L = 420mm)
    m_sh = adsk.core.Matrix3D.create()
    m_sh.translation = adsk.core.Vector3D.create(-7.0, 0.0, 10.5)
    occ_eixo = root_ass.occurrences.addByInsert(files["06_Eixo_Rotativo_Retificado_20mm"], m_sh, True)
    add_rigid_joint(occ_eixo, occ_chassi, "Junta_Rigida_Eixo_Rotativo")

    # 11. Disco de Desbalanceamento Inox (Centro em X = +14.0 cm, Z = 10.5 cm)
    m_dsc = adsk.core.Matrix3D.create()
    m_dsc.translation = adsk.core.Vector3D.create(14.0, 0.0, 10.5)
    occ_disco = root_ass.occurrences.addByInsert(files["08_Disco_Desbalanceamento_Calibrado"], m_dsc, True)
    add_rigid_joint(occ_disco, occ_eixo, "Junta_Rigida_Disco_Desbalanceamento")

    # =========================================================================
    # 12. ENCLAUSURAMENTO E SEGURANCA NR-12 COM JUNTA ARTICULADA (REVOLUTA)
    # =========================================================================
    # 12.1 - 2 Dobradiças Articuladas no Canal T Traseiro da Mesa (Y = +10.75 cm, Z = 4.0 cm)
    # Fixadas rigidamente na base da bancada
    m_dob1 = adsk.core.Matrix3D.create()
    m_dob1.translation = adsk.core.Vector3D.create(0.0, 10.75, 4.0)
    occ_dob1 = root_ass.occurrences.addByInsert(files["18_Dobradica_Articulada_Mesa"], m_dob1, True)
    add_rigid_joint(occ_dob1, occ_chassi, "Junta_Rigida_Dobradica_1")

    m_dob2 = adsk.core.Matrix3D.create()
    m_dob2.translation = adsk.core.Vector3D.create(27.0, 10.75, 4.0)
    occ_dob2 = root_ass.occurrences.addByInsert(files["18_Dobradica_Articulada_Mesa"], m_dob2, True)
    add_rigid_joint(occ_dob2, occ_chassi, "Junta_Rigida_Dobradica_2")

    # 12.2 - Cúpula Estrutural 2020 (L = 420 mm, W = 215 mm, H = 170 mm)
    # Posicionada em X = +13.5 cm (spans de X = -7.5cm ate +34.5cm, gap de 40mm para a placa do motor!)
    m_cup = adsk.core.Matrix3D.create()
    m_cup.translation = adsk.core.Vector3D.create(13.5, 0.0, 4.0)
    occ_cup = root_ass.occurrences.addByInsert(files["14_Cupula_Estrutural_2020"], m_cup, True)

    # 12.3 - CRIAR JUNTA REVOLUTA DE ARTICULAÇÃO DA TAMPA!
    # A cúpula gira livremente em torno do pino da dobradiça
    # Eixo de rotação orientado para que rotação positiva (0 a 100 graus) abra a tampa para CIMA (+Z)!
    b_dob = occ_dob1.bRepBodies.item(0)
    cyl_face = None
    for f in b_dob.faces:
        if f.geometry.surfaceType == adsk.core.SurfaceTypes.CylinderSurfaceType:
            cyl_face = f
            break
            
    geo_knuckle = adsk.fusion.JointGeometry.createByNonPlanarFace(cyl_face, adsk.fusion.JointKeyPointTypes.MiddleKeyPoint)
    jin_rev = root_ass.asBuiltJoints.createInput(occ_cup, occ_dob1, geo_knuckle)
    # Usar CustomJointDirection com vetor (-1, 0, 0) para que ângulo positivo abra a tampa para cima!
    axis_sketch = root_ass.sketches.add(root_ass.xYConstructionPlane)
    axis_line = axis_sketch.sketchCurves.sketchLines.addByTwoPoints(
        adsk.core.Point3D.create(0, 10.75, 4.0),
        adsk.core.Point3D.create(-10.0, 10.75, 4.0)
    )
    jin_rev.setAsRevoluteJointMotion(adsk.fusion.JointDirections.CustomJointDirection, axis_line)
    j_rev = root_ass.asBuiltJoints.add(jin_rev)
    j_rev.name = "Junta_Articulada_Abertura_Cupula"

    rev_motion = adsk.fusion.RevoluteJointMotion.cast(j_rev.jointMotion)
    rev_motion.rotationLimits.isMinimumValueEnabled = True
    rev_motion.rotationLimits.minimumValue = 0.0 # 0 graus = fechada
    rev_motion.rotationLimits.isMaximumValueEnabled = True
    rev_motion.rotationLimits.maximumValue = math.radians(105.0) # 105 graus = abertura total

    # 12.4 - Microswitch de Seguranca de Intertravamento (X = -6.0 cm, Y = -10.75 cm, Z = 2.0 cm)
    m_sw = adsk.core.Matrix3D.create()
    m_sw.translation = adsk.core.Vector3D.create(-6.0, -10.75, 2.0)
    occ_sw = root_ass.occurrences.addByInsert(files["16_Microswitch_Seguranca_Tampa"], m_sw, True)
    add_rigid_joint(occ_sw, occ_chassi, "Junta_Rigida_Microswitch_Seguranca")

    # 12.7 - Botão de Parada de Emergência NR-12 no Canal T Frontal (X = +30.0 cm, Y = -10.75 cm, Z = 0.0 cm)
    m_btn = adsk.core.Matrix3D.create()
    m_btn.setToRotation(math.pi, adsk.core.Vector3D.create(0, 0, 1), adsk.core.Point3D.create(0, 0, 0))
    m_btn.translation = adsk.core.Vector3D.create(30.0, -10.75, 0.0)
    occ_btn = root_ass.occurrences.addByInsert(files["15_Botao_Emergencia_NR12"], m_btn, True)
    add_rigid_joint(occ_btn, occ_chassi, "Junta_Rigida_Botao_Emergencia")

    # =========================================================================
    # APLICAR MATERIAIS DE ALTA FIDELIDADE
    # =========================================================================
    print("Aplicando materiais de alta fidelidade...")
    mat_alum_acetinado = get_mat_app(design_ass, "Mat_Alum_Acetinado_Real", "acetinado", 215, 218, 222)
    mat_ferro_base = get_mat_app(design_ass, "Mat_Ferro_Fundido_Real", "ferro", 55, 55, 60)
    mat_alum_ouro = get_mat_app(design_ass, "Mat_Alum_Anodizado_Ouro", "anodizado com brilho", 225, 175, 45)
    mat_alum_laranja = get_mat_app(design_ass, "Mat_Alum_Anodizado_Laranja", "anodizado com brilho", 240, 115, 25)
    mat_aco_inox = get_mat_app(design_ass, "Mat_Aco_Inox_Polido_Real", "polido", 220, 224, 228)
    mat_azul_weg = get_mat_app(design_ass, "Mat_Azul_WEG_Real", "esmalte", 10, 75, 150)
    mat_verde_mancal = get_mat_app(design_ass, "Mat_Verde_Mancal_Real", "esmalte", 35, 130, 60)
    mat_borracha = get_mat_app(design_ass, "Mat_Borracha_Preta_Real", "borracha", 35, 35, 35)
    mat_poly_red = get_mat_app(design_ass, "Mat_Poliuretano_Vermelho", "esmalte", 215, 30, 30)
    mat_polycarb = get_mat_app(design_ass, "Mat_Policarbonato_Cristal_Real", "vidro", 240, 248, 255)
    mat_poliamida = get_mat_app(design_ass, "Mat_Poliamida_Preta_Real", "plástico", 28, 28, 30)
    mat_yellow_nr12 = get_mat_app(design_ass, "Mat_Amarelo_Advertencia_NR12", "esmalte", 245, 205, 10)
    mat_red_nr12 = get_mat_app(design_ass, "Mat_Vermelho_Emergencia_NR12", "esmalte", 220, 25, 25)

    for occ in root_ass.occurrences:
        n = occ.name.lower()
        if "01_mesa" in n:
            for b in occ.bRepBodies:
                if "base" in b.name.lower() or "ferro" in b.name.lower():
                    b.appearance = mat_ferro_base
                else:
                    b.appearance = mat_alum_acetinado
        elif "02_sapata" in n:
            for b in occ.bRepBodies:
                max_z = max(v.geometry.z for v in b.vertices)
                if max_z > 0.0:
                    b.appearance = mat_aco_inox
                else:
                    b.appearance = mat_borracha
        elif "03_placa" in n:
            for b in occ.bRepBodies: b.appearance = mat_alum_laranja
        elif "04_motor" in n:
            for b in occ.bRepBodies:
                if "carcaça" in b.name.lower() or "estator" in b.name.lower():
                    b.appearance = mat_azul_weg
                elif "eixo" in b.name.lower():
                    b.appearance = mat_aco_inox
                else:
                    b.appearance = mat_azul_weg
        elif "05_acoplamento" in n:
            for b in occ.bRepBodies:
                if "elastico" in b.name.lower() or "aranha" in b.name.lower():
                    b.appearance = mat_poly_red
                else:
                    b.appearance = mat_alum_acetinado
        elif "06_eixo" in n:
            for b in occ.bRepBodies: b.appearance = mat_aco_inox
        elif "07_mancal" in n:
            for b in occ.bRepBodies:
                if "corpo" in b.name.lower() or "fundido" in b.name.lower():
                    b.appearance = mat_verde_mancal
                else:
                    b.appearance = mat_aco_inox
        elif "08_disco" in n:
            for b in occ.bRepBodies: b.appearance = mat_aco_inox
        elif "09_subplaca" in n:
            for b in occ.bRepBodies: b.appearance = mat_alum_acetinado
        elif "10_parafuso" in n or "12_parafuso" in n:
            for b in occ.bRepBodies: b.appearance = mat_aco_inox
        elif "11_bloco" in n:
            for b in occ.bRepBodies: b.appearance = mat_alum_laranja
        elif "13_manipulo" in n:
            for b in occ.bRepBodies: b.appearance = mat_aco_inox
        elif "14_cupula" in n:
            for b in occ.bRepBodies:
                bn = b.name.lower()
                if "painel" in bn or "policarbonato" in bn:
                    b.appearance = mat_polycarb
                elif "alca" in bn or "handle" in bn or "puxador" in bn:
                    b.appearance = mat_poliamida
                elif "passa_cabo" in bn:
                    b.appearance = mat_borracha
                else:
                    b.appearance = mat_alum_acetinado
        elif "17_passa_cabo" in n:
            for b in occ.bRepBodies: b.appearance = mat_borracha
        elif "18_dobradica" in n or "19_alca" in n:
            for b in occ.bRepBodies: b.appearance = mat_poliamida
        elif "15_botao" in n:
            for b in occ.bRepBodies:
                bn = b.name.lower()
                if "corpo3" in bn or "vermelho" in bn or "head" in bn or "btn" in bn:
                    b.appearance = mat_red_nr12
                elif "corpo2" in bn or "amarelo" in bn or "yel" in bn:
                    b.appearance = mat_yellow_nr12
                else:
                    b.appearance = mat_alum_acetinado
        elif "16_microswitch" in n:
            for b in occ.bRepBodies:
                bn = b.name.lower()
                if "corpo3" in bn or "rolete" in bn:
                    b.appearance = mat_red_nr12
                elif "corpo2" in bn or "brk" in bn or "suporte" in bn:
                    b.appearance = mat_alum_acetinado
                else:
                    b.appearance = mat_poliamida

    step_ass = os.path.join(EXPORT_DIR, "amemiya_bancada_testes_assembly.step")
    design_ass.exportManager.execute(design_ass.exportManager.createSTEPExportOptions(step_ass))
    doc_ass.save("Montagem atualizada com tampa articulada e perfis normatizados")
    print("Montagem Salva e STEP exportado com sucesso!")

    # =========================================================================
    # RENDERIZAR IMAGENS DE ALTA DEFINICAO DA BANCADA
    # =========================================================================
    print("Renderizando imagens de alta definicao...")
    vp = app.activeViewport
    vp.visualStyle = 2
    cam = vp.camera
    cam.isSmoothTransition = False

    # 1. Isométrica Geral Fechada (com perfis chanfrados e sapatas encostadas)
    cam.target = adsk.core.Point3D.create(0.0, 0.0, 9.0)
    cam.eye = adsk.core.Point3D.create(-55.0, -48.0, 48.0)
    cam.upVector = adsk.core.Vector3D.create(0.0, 0.0, 1.0)
    cam.viewExtents = 72.0
    vp.camera = cam
    vp.refresh()
    time.sleep(0.3)
    vp.saveAsImageFile(os.path.join(ARTIFACTS_DIR, "bancada_nr12_iso_fechada.png"), 1600, 900)

    # 2. Isométrica com TAMPA ABERTA (Articulação da Junta Revoluta demonstrada a 75 graus para CIMA!)
    print("Abrindo tampa a 75 graus para cima para demonstrar articulacao...")
    rot_axis = adsk.core.Vector3D.create(1.0, 0.0, 0.0)
    rot_pt = adsk.core.Point3D.create(0.0, 10.75, 4.0)
    
    mat_cup_base = occ_cup.transform.copy()

    def set_lid_angle(angle_deg):
        rot_mat = adsk.core.Matrix3D.create()
        rot_mat.setToRotation(-math.radians(angle_deg), rot_axis, rot_pt)
        m_c = mat_cup_base.copy()
        m_c.transformBy(rot_mat)
        occ_cup.transform = m_c

    # Abrir tampa a 75 graus
    set_lid_angle(75.0)
    vp.refresh()
    time.sleep(0.3)
    cam.target = adsk.core.Point3D.create(5.0, -2.0, 12.0)
    cam.eye = adsk.core.Point3D.create(-50.0, -45.0, 45.0)
    cam.upVector = adsk.core.Vector3D.create(0.0, 0.0, 1.0)
    cam.viewExtents = 68.0
    vp.camera = cam
    vp.refresh()
    time.sleep(0.3)
    vp.saveAsImageFile(os.path.join(ARTIFACTS_DIR, "bancada_nr12_tampa_aberta.png"), 1600, 900)

    # Fechar a tampa novamente
    set_lid_angle(0.0)
    vp.refresh()

    # 3. Detalhe Frontal Botão de Emergência, Espaço Livre Motor-Cúpula e Perfis Chanfrados
    cam.target = adsk.core.Point3D.create(-5.0, -5.0, 8.0)
    cam.eye = adsk.core.Point3D.create(-15.0, -42.0, 22.0)
    cam.upVector = adsk.core.Vector3D.create(0.0, 0.0, 1.0)
    cam.viewExtents = 42.0
    vp.camera = cam
    vp.refresh()
    time.sleep(0.3)
    vp.saveAsImageFile(os.path.join(ARTIFACTS_DIR, "bancada_nr12_detalhe_seguranca.png"), 1600, 900)

    # 4. Detalhe Específico das Sapatas Encostadas na Base e dos Canais T Chanfrados
    cam.target = adsk.core.Point3D.create(-28.0, -6.0, 0.0)
    cam.eye = adsk.core.Point3D.create(-46.0, -28.0, 12.0)
    cam.upVector = adsk.core.Vector3D.create(0.0, 0.0, 1.0)
    cam.viewExtents = 30.0
    vp.camera = cam
    vp.refresh()
    time.sleep(0.3)
    vp.saveAsImageFile(os.path.join(ARTIFACTS_DIR, "bancada_nr12_detalhe_pes_perfil.png"), 1600, 900)

    # 5. Vista Superior Transparência e Acesso Livre ao Motor
    cam.target = adsk.core.Point3D.create(0.0, 0.0, 6.0)
    cam.eye = adsk.core.Point3D.create(0.0, 0.0, 52.0)
    cam.upVector = adsk.core.Vector3D.create(1.0, 0.0, 0.0)
    cam.viewExtents = 55.0
    vp.camera = cam
    vp.refresh()
    time.sleep(0.3)
    vp.saveAsImageFile(os.path.join(ARTIFACTS_DIR, "bancada_nr12_top_transparencia.png"), 1600, 900)

    # 6. Vista Traseira com os 3 Passa-Cabos de Borracha Pretos
    cam.target = adsk.core.Point3D.create(13.5, 5.0, 10.0)
    cam.eye = adsk.core.Point3D.create(15.0, 48.0, 26.0)
    cam.upVector = adsk.core.Vector3D.create(0.0, 0.0, 1.0)
    cam.viewExtents = 48.0
    vp.camera = cam
    vp.refresh()
    time.sleep(0.3)
    vp.saveAsImageFile(os.path.join(ARTIFACTS_DIR, "bancada_nr12_detalhe_traseiro_passacabos.png"), 1600, 900)

    # 7. Vista Detalhe Frontal da Alça Preta em Ponte
    cam.target = adsk.core.Point3D.create(13.5, -10.0, 8.5)
    cam.eye = adsk.core.Point3D.create(13.5, -42.0, 18.0)
    cam.upVector = adsk.core.Vector3D.create(0.0, 0.0, 1.0)
    cam.viewExtents = 35.0
    vp.camera = cam
    vp.refresh()
    time.sleep(0.3)
    vp.saveAsImageFile(os.path.join(ARTIFACTS_DIR, "bancada_nr12_detalhe_alca_frontal.png"), 1600, 900)

    # 6. Animação da Abertura e Fechamento da Tampa (Demonstrando a Articulação Suave)
    print("Capturando 24 frames da animacao de abertura da tampa sob junta revoluta...")
    frames_dir = os.path.join(ARTIFACTS_DIR, "anim_frames_abertura")
    os.makedirs(frames_dir, exist_ok=True)

    cam.target = adsk.core.Point3D.create(5.0, -2.0, 12.0)
    cam.eye = adsk.core.Point3D.create(-48.0, -42.0, 42.0)
    cam.upVector = adsk.core.Vector3D.create(0.0, 0.0, 1.0)
    cam.viewExtents = 68.0
    vp.camera = cam
    vp.refresh()

    for f in range(24):
        if f < 12:
            ang_deg = (f / 11.0) * 75.0
        else:
            ang_deg = ((23 - f) / 11.0) * 75.0
        set_lid_angle(ang_deg)
        vp.refresh()
        fn = os.path.join(frames_dir, f"open_frame_{f:02d}.png")
        vp.saveAsImageFile(fn, 800, 450)

    set_lid_angle(0.0)
    vp.refresh()

    print("MONTAGEM_NR12_CONCLUIDA_COM_SUCESSO")
'''

def main():
    sess = init_session()
    print("Iniciando montagem final da bancada com protecao NR-12 e juntas cinematicas...")
    res = execute_script(sess, SCRIPT_ASSEMBLE_NR12)
    print("Resultado recebido:")
    if res and "result" in res and "content" in res["result"]:
        print(res["result"]["content"][0]["text"])
    else:
        print(res)

    # Compilar GIF da Abertura da Tampa
    try:
        from PIL import Image
        frames_dir = os.path.join(ARTIFACTS_DIR, "anim_frames_abertura")
        frame_files = [os.path.join(frames_dir, f"open_frame_{i:02d}.png") for i in range(24)]
        imgs = [Image.open(f) for f in frame_files if os.path.exists(f)]
        if len(imgs) == 24:
            gif_path = os.path.join(ARTIFACTS_DIR, "bancada_nr12_abertura_tampa.gif")
            imgs[0].save(
                gif_path,
                save_all=True,
                append_images=imgs[1:],
                duration=60,
                loop=0
            )
            print(f"GIF de abertura compilado com sucesso em: {gif_path}")
    except Exception as e:
        print("Erro ao compilar GIF:", e)

if __name__ == "__main__":
    main()
