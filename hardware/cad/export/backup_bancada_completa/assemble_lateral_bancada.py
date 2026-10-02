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
        "clientInfo": {"name": "assemble_lateral_bancada", "version": "1.0"}
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

SCRIPT_ASSEMBLE_LATERAL = r'''
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

    print("Reconstruindo amemiya_bancada_testes_assembly com sistema lateral...")
    delete_old_file(amemiya_proj.rootFolder, "amemiya_bancada_testes_assembly")

    files = {df.name: df for df in folder.dataFiles}

    doc_ass = app.documents.add(adsk.core.DocumentTypes.FusionDesignDocumentType)
    doc_ass.saveAs("amemiya_bancada_testes_assembly", amemiya_proj.rootFolder, "Bancada com Fixacao Lateral e Placa Ampliada", "")
    doc_ass.activate()

    design_ass = adsk.fusion.Design.cast(doc_ass.products.itemByProductType("DesignProductType"))
    root_ass = design_ass.rootComponent

    # 1. Chassi Base (01_Mesa_Base_Bancada)
    occ_chassi = root_ass.occurrences.addByInsert(files["01_Mesa_Base_Bancada"], adsk.core.Matrix3D.create(), True)
    occ_chassi.isGrounded = True

    # 2. Sapatas Niveladoras nos Cantos Inferiores (Z = -4.0 cm)
    for sx, sy in [(-31.0, -8.75), (-31.0, 8.75), (31.0, -8.75), (31.0, 8.75)]:
        m = adsk.core.Matrix3D.create()
        m.translation = adsk.core.Vector3D.create(sx, sy, -4.0)
        root_ass.occurrences.addByInsert(files["02_Sapata_Niveladora_Borracha"], m, True)

    # 3. Placa Base Ampliada (230 x 160 x 15 mm) em Z = 4.0 cm
    m_pl = adsk.core.Matrix3D.create()
    m_pl.translation = adsk.core.Vector3D.create(-23.0, 0.0, 4.0)
    occ_placa = root_ass.occurrences.addByInsert(files["03_Placa_Base_Desalinhamento_Motor"], m_pl, True)

    # 4. Parafusos Jacking de Elevacao M8 (X = +-100.0 mm, Y = +-67.5 mm - CENTRO DO PERFIL!)
    for jx, jy in [(-23.0 - 10.0, -6.75), (-23.0 - 10.0, 6.75), (-23.0 + 10.0, -6.75), (-23.0 + 10.0, 6.75)]:
        m = adsk.core.Matrix3D.create()
        m.translation = adsk.core.Vector3D.create(jx, jy, 5.5)
        root_ass.occurrences.addByInsert(files["12_Parafuso_Jacking_Elevacao_M8"], m, True)

    # 5. Parafusos M10 Allen nos Rasgos Oblongos (X = +-75.0 mm, Y = +-47.5 mm - FORA DO MOTOR!)
    for sx, sy in [(-23.0 - 7.5, -4.75), (-23.0 - 7.5, 4.75), (-23.0 + 7.5, -4.75), (-23.0 + 7.5, 4.75)]:
        m = adsk.core.Matrix3D.create()
        m.translation = adsk.core.Vector3D.create(sx, sy, 5.5)
        root_ass.occurrences.addByInsert(files["10_Parafuso_Fixacao_M10_Allen"], m, True)

    # 6. Placas de Encosto no Trilho Lateral Externo (Y = +-107.5 mm, Z = 0.0 cm)
    # Placa Esquerda (+Y = 10.75 cm)
    m_blk_l = adsk.core.Matrix3D.create()
    m_blk_l.translation = adsk.core.Vector3D.create(-23.0, 10.75, 0.0)
    root_ass.occurrences.addByInsert(files["11_Bloco_Encosto_Push_Pull_Lateral"], m_blk_l, True)

    # Parafuso de fixacao M8 da placa lateral ao canal T (X = -23.0 cm, Y = 11.35 cm, Z = 2.0 cm)
    m_b_fix_l = adsk.core.Matrix3D.create()
    m_b_fix_l.setToRotation(-math.pi / 2, adsk.core.Vector3D.create(1, 0, 0), adsk.core.Point3D.create(0, 0, 0))
    m_b_fix_l.translation = adsk.core.Vector3D.create(-23.0, 11.35, 2.0)
    root_ass.occurrences.addByInsert(files["10_Parafuso_Fixacao_M10_Allen"], m_b_fix_l, True)

    # Manipulo Push-Pull M8 Esquerdo (Ponta toca na borda da placa em Y = 8.0 cm!)
    m_man_l = adsk.core.Matrix3D.create()
    m_man_l.translation = adsk.core.Vector3D.create(-23.0, 12.8, 4.75)
    root_ass.occurrences.addByInsert(files["13_Manipulo_Push_Pull_M8"], m_man_l, True)

    # Placa Direita (-Y = -10.75 cm, rotacionada 180° em Z)
    m_blk_r = adsk.core.Matrix3D.create()
    m_blk_r.setToRotation(math.pi, adsk.core.Vector3D.create(0, 0, 1), adsk.core.Point3D.create(0, 0, 0))
    m_blk_r.translation = adsk.core.Vector3D.create(-23.0, -10.75, 0.0)
    root_ass.occurrences.addByInsert(files["11_Bloco_Encosto_Push_Pull_Lateral"], m_blk_r, True)

    # Parafuso de fixacao M8 da placa lateral direita (X = -23.0 cm, Y = -11.35 cm, Z = 2.0 cm)
    m_b_fix_r = adsk.core.Matrix3D.create()
    m_b_fix_r.setToRotation(math.pi / 2, adsk.core.Vector3D.create(1, 0, 0), adsk.core.Point3D.create(0, 0, 0))
    m_b_fix_r.translation = adsk.core.Vector3D.create(-23.0, -11.35, 2.0)
    root_ass.occurrences.addByInsert(files["10_Parafuso_Fixacao_M10_Allen"], m_b_fix_r, True)

    # Manipulo Push-Pull M8 Direito (Ponta toca na borda da placa em Y = -8.0 cm!)
    m_man_r = adsk.core.Matrix3D.create()
    m_man_r.setToRotation(math.pi, adsk.core.Vector3D.create(0, 0, 1), adsk.core.Point3D.create(0, 0, 0))
    m_man_r.translation = adsk.core.Vector3D.create(-23.0, -12.8, 4.75)
    root_ass.occurrences.addByInsert(files["13_Manipulo_Push_Pull_M8"], m_man_r, True)

    # 7. Motor Elétrico Azul WEG (Assentado em Z = 5.5 cm, Eixo em Z = 10.5 cm)
    m_mot = adsk.core.Matrix3D.create()
    m_mot.translation = adsk.core.Vector3D.create(-23.0, 0.0, 5.5)
    occ_mot = root_ass.occurrences.addByInsert(files["04_Motor_Eletrico_Trifasico"], m_mot, True)

    # 8. Acoplamento Flexivel de Mandibula (Centro em X = -13.5 cm, Z = 10.5 cm)
    m_acop = adsk.core.Matrix3D.create()
    m_acop.translation = adsk.core.Vector3D.create(-13.5, 0.0, 10.5)
    occ_acop = root_ass.occurrences.addByInsert(files["05_Acoplamento_Flexivel_Mandibula"], m_acop, True)

    # 9. Subplacas e Mancais A e B
    for pos_x in [-4.0, 22.0]:
        m_s = adsk.core.Matrix3D.create()
        m_s.translation = adsk.core.Vector3D.create(pos_x, 0.0, 4.0)
        root_ass.occurrences.addByInsert(files["09_Subplaca_Troca_Rapida_Mancal"], m_s, True)

        m_m = adsk.core.Matrix3D.create()
        m_m.translation = adsk.core.Vector3D.create(pos_x, 0.0, 7.17)
        root_ass.occurrences.addByInsert(files["07_Mancal_Pillow_Block_UCP204"], m_m, True)

        for py in [-4.75, 4.75]:
            m_b = adsk.core.Matrix3D.create()
            m_b.translation = adsk.core.Vector3D.create(pos_x, py, 8.67)
            root_ass.occurrences.addByInsert(files["10_Parafuso_Fixacao_M10_Allen"], m_b, True)

    # 10. Eixo Rotativo Retificado 20mm (X_start = -12.5 cm, Z = 10.5 cm, L = 420mm)
    m_sh = adsk.core.Matrix3D.create()
    m_sh.translation = adsk.core.Vector3D.create(-12.5, 0.0, 10.5)
    occ_eixo = root_ass.occurrences.addByInsert(files["06_Eixo_Rotativo_Retificado_20mm"], m_sh, True)

    # 11. Disco de Desbalanceamento Inox (X = +9.0 cm, Z = 10.5 cm)
    m_dsc = adsk.core.Matrix3D.create()
    m_dsc.translation = adsk.core.Vector3D.create(9.0, 0.0, 10.5)
    occ_disco = root_ass.occurrences.addByInsert(files["08_Disco_Desbalanceamento_Calibrado"], m_dsc, True)

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

    for occ in root_ass.occurrences:
        n = occ.name.lower()
        if "01_mesa" in n:
            for b in occ.bRepBodies:
                if "base" in b.name.lower() or "ferro" in b.name.lower():
                    b.appearance = mat_ferro_base
                else:
                    b.appearance = mat_alum_acetinado
        elif "02_sapata" in n:
            occ.appearance = mat_borracha
            for b in occ.bRepBodies: b.appearance = mat_borracha
        elif "03_placa" in n:
            occ.appearance = mat_alum_ouro
            for b in occ.bRepBodies: b.appearance = mat_alum_ouro
        elif "04_motor" in n:
            occ.appearance = mat_azul_weg
            for b in occ.bRepBodies: b.appearance = mat_azul_weg
        elif "05_acoplamento" in n:
            for b in occ.bRepBodies:
                if "corpo2" in b.name.lower():
                    b.appearance = mat_poly_red
                else:
                    b.appearance = mat_alum_acetinado
        elif "06_eixo" in n or "08_disco" in n:
            occ.appearance = mat_aco_inox
            for b in occ.bRepBodies: b.appearance = mat_aco_inox
        elif "07_mancal" in n:
            occ.appearance = mat_verde_mancal
            for b in occ.bRepBodies: b.appearance = mat_verde_mancal
        elif "09_subplaca" in n:
            occ.appearance = mat_alum_acetinado
            for b in occ.bRepBodies: b.appearance = mat_alum_acetinado
        elif "10_parafuso" in n or "12_parafuso" in n or "13_manipulo" in n:
            occ.appearance = mat_aco_inox
            for b in occ.bRepBodies: b.appearance = mat_aco_inox
        elif "11_bloco" in n:
            occ.appearance = mat_alum_laranja
            for b in occ.bRepBodies: b.appearance = mat_alum_laranja

    step_ass = os.path.join(EXPORT_DIR, "amemiya_bancada_testes_assembly.step")
    design_ass.exportManager.execute(design_ass.exportManager.createSTEPExportOptions(step_ass))
    doc_ass.save("Montagem atualizada com sistema lateral e folgas perfeitas")
    print("Montagem Salva e STEP exportado com sucesso!")

    # =========================================================================
    # RENDERIZAR IMAGENS DE DETALHE COMPROVANDO O NOVO LAYOUT
    # =========================================================================
    print("Renderizando imagens de alta definicao...")
    vp = app.activeViewport
    vp.visualStyle = 2
    cam = vp.camera
    cam.isSmoothTransition = False

    # 1. Vista Superior Exata (Top-down) mostrando o motor, rasgos M10 livres e jacking centralizado
    cam.target = adsk.core.Point3D.create(-23.0, 0.0, 5.0)
    cam.eye = adsk.core.Point3D.create(-23.0, 0.0, 48.0)
    cam.upVector = adsk.core.Vector3D.create(1.0, 0.0, 0.0)
    cam.viewExtents = 34.0
    vp.camera = cam
    vp.refresh()
    time.sleep(0.3)
    vp.saveAsImageFile(os.path.join(ARTIFACTS_DIR, "bancada_lateral_top_motor.png"), 1600, 900)

    # 2. Detalhe em Perspectiva do Sistema Lateral e Motor
    cam.target = adsk.core.Point3D.create(-23.0, 0.0, 8.0)
    cam.eye = adsk.core.Point3D.create(-44.0, -28.0, 26.0)
    cam.upVector = adsk.core.Vector3D.create(0.0, 0.0, 1.0)
    cam.viewExtents = 36.0
    vp.camera = cam
    vp.refresh()
    time.sleep(0.3)
    vp.saveAsImageFile(os.path.join(ARTIFACTS_DIR, "bancada_lateral_detalhe_motor.png"), 1600, 900)

    # 3. Vista Lateral mostrando a placa de encosto afixada no canal T lateral do perfil
    cam.target = adsk.core.Point3D.create(-23.0, 0.0, 6.0)
    cam.eye = adsk.core.Point3D.create(-23.0, -38.0, 12.0)
    cam.upVector = adsk.core.Vector3D.create(0.0, 0.0, 1.0)
    cam.viewExtents = 32.0
    vp.camera = cam
    vp.refresh()
    time.sleep(0.3)
    vp.saveAsImageFile(os.path.join(ARTIFACTS_DIR, "bancada_lateral_vista_lateral.png"), 1600, 900)

    # 4. Vista Isométrica Geral da Bancada
    cam.target = adsk.core.Point3D.create(0.0, 0.0, 7.0)
    cam.eye = adsk.core.Point3D.create(-55.0, -45.0, 45.0)
    cam.upVector = adsk.core.Vector3D.create(0.0, 0.0, 1.0)
    cam.viewExtents = 68.0
    vp.camera = cam
    vp.refresh()
    time.sleep(0.3)
    vp.saveAsImageFile(os.path.join(ARTIFACTS_DIR, "bancada_lateral_iso.png"), 1600, 900)

    # =========================================================================
    # CAPTURA DE FRAMES DA ROTACAO COAXIAL COM O NOVO SISTEMA
    # =========================================================================
    print("Capturando 24 frames de rotacao PURA no Eixo X...")
    frames_dir = os.path.join(ARTIFACTS_DIR, "anim_frames_hybrid")
    os.makedirs(frames_dir, exist_ok=True)

    cam.target = adsk.core.Point3D.create(0.0, 0.0, 10.0)
    cam.eye = adsk.core.Point3D.create(-48.0, -38.0, 38.0)
    cam.upVector = adsk.core.Vector3D.create(0.0, 0.0, 1.0)
    cam.viewExtents = 62.0
    vp.camera = cam
    vp.refresh()

    mat_ac_base = occ_acop.transform
    mat_sh_base = occ_eixo.transform
    mat_dsc_base = occ_disco.transform
    rot_axis = adsk.core.Vector3D.create(1.0, 0.0, 0.0)
    rot_pt = adsk.core.Point3D.create(0.0, 0.0, 10.5)

    for frame in range(24):
        theta = (2.0 * math.pi / 24.0) * frame
        rot_mat = adsk.core.Matrix3D.create()
        rot_mat.setToRotation(theta, rot_axis, rot_pt)

        m_a = mat_ac_base.copy()
        m_a.transformBy(rot_mat)
        occ_acop.transform = m_a

        m_s = mat_sh_base.copy()
        m_s.transformBy(rot_mat)
        occ_eixo.transform = m_s

        m_d = mat_dsc_base.copy()
        m_d.transformBy(rot_mat)
        occ_disco.transform = m_d

        vp.refresh()
        fn = os.path.join(frames_dir, f"hyb_frame_{frame:02d}.png")
        vp.saveAsImageFile(fn, 800, 450)

    occ_acop.transform = mat_ac_base
    occ_eixo.transform = mat_sh_base
    occ_disco.transform = mat_dsc_base
    vp.refresh()

    print("MONTAGEM_LATERAL_CONCLUIDA_COM_SUCESSO")
'''

if __name__ == "__main__":
    sess = init_session()
    print("Iniciando montagem final da bancada com sistema lateral...")
    res = execute_script(sess, SCRIPT_ASSEMBLE_LATERAL)
    print("Resultado recebido:")
    if res and "result" in res and "content" in res["result"]:
        print(res["result"]["content"][0]["text"])
    else:
        print(res)

    # Compilar GIF
    try:
        from PIL import Image
        frames_dir = os.path.join(ARTIFACTS_DIR, "anim_frames_hybrid")
        frame_files = [os.path.join(frames_dir, f"hyb_frame_{i:02d}.png") for i in range(24)]
        imgs = [Image.open(f) for f in frame_files if os.path.exists(f)]
        if len(imgs) == 24:
            gif_path = os.path.join(ARTIFACTS_DIR, "bancada_lateral_rotacao.gif")
            imgs[0].save(
                gif_path,
                save_all=True,
                append_images=imgs[1:],
                duration=40,
                loop=0
            )
            print(f"GIF compilado com sucesso em: {gif_path}")
    except Exception as e:
        print("Erro ao compilar GIF:", e)
