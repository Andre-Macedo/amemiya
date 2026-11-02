import urllib.request
import json
import os
import math
import time
from PIL import Image

FUSION_URL = "http://127.0.0.1:27182/mcp"
ARTIFACTS_DIR = r"C:\Users\andrl\.gemini\antigravity-cli\brain\efd833bf-6ba1-4a1b-a7ce-aa9aa87aa86c"
FRAMES_DIR = os.path.join(ARTIFACTS_DIR, "scratch", "frames_rotacao_fusion")
GIF_OUTPUT = os.path.join(ARTIFACTS_DIR, "bancada_nova_rotacao_30_45.gif")
GIF_DOCS = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\docs\bancada_nova_rotacao_30_45.gif"

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
    with urllib.request.urlopen(req, timeout=120) as resp:
        sess = resp.headers.get("MCP-Session-Id", session_id)
        content = resp.read().decode("utf-8")
        return sess, json.loads(content) if content else None

def init_session():
    sess, _ = send_rpc(None, "initialize", {
        "protocolVersion": "2024-11-05",
        "capabilities": {},
        "clientInfo": {"name": "rotacao_fusion", "version": "1.0"}
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

SCRIPT_CAPTURE_SWEEP = r'''
import adsk.core, adsk.fusion, os, math, time

def run(context):
    app = adsk.core.Application.get()
    vp = app.activeViewport
    if not vp:
        print("Nenhum viewport ativo encontrado.")
        return
        
    cam = vp.camera
    orig_eye = cam.eye
    orig_target = cam.target
    orig_up = cam.upVector
    
    # Se camera nao estiver isometrica, ajusta para vista diagonal elevada
    vp.camera.viewOrientation = adsk.core.ViewOrientations.IsoTopLeftViewOrientation
    vp.fit()
    vp.refresh()
    
    cam = vp.camera
    target = cam.target
    eye = cam.eye
    up = cam.upVector
    
    dx = eye.x - target.x
    dy = eye.y - target.y
    dz = eye.z - target.z
    r_xy = math.sqrt(dx*dx + dy*dy)
    base_angle = math.atan2(dy, dx)
    
    frames_dir = r"C:\Users\andrl\.gemini\antigravity-cli\brain\efd833bf-6ba1-4a1b-a7ce-aa9aa87aa86c\scratch\frames_rotacao_fusion"
    os.makedirs(frames_dir, exist_ok=True)
    
    num_frames = 24
    sweep_half_angle = math.radians(20.0) # Varia 40 graus no total (-20 a +20)
    
    for i in range(num_frames):
        theta_offset = sweep_half_angle * math.sin(2.0 * math.pi * i / num_frames)
        cur_angle = base_angle + theta_offset
        new_x = target.x + r_xy * math.cos(cur_angle)
        new_y = target.y + r_xy * math.sin(cur_angle)
        
        cam.eye = adsk.core.Point3D.create(new_x, new_y, eye.z)
        cam.target = target
        cam.upVector = up
        vp.camera = cam
        vp.refresh()
        
        frame_path = os.path.join(frames_dir, f"frame_{i:02d}.png")
        vp.saveAsImageFile(frame_path, 1280, 720)
        
    # Restaura
    cam.eye = orig_eye
    cam.target = orig_target
    cam.upVector = orig_up
    vp.camera = cam
    vp.refresh()
    print("FRAMES_CAPTURED_SUCCESSFULLY")
'''

def compile_gif():
    frame_files = [os.path.join(FRAMES_DIR, f"frame_{i:02d}.png") for i in range(24)]
    imgs = [Image.open(f) for f in frame_files if os.path.exists(f)]
    if len(imgs) == 24:
        imgs[0].save(
            GIF_OUTPUT,
            save_all=True,
            append_images=imgs[1:],
            duration=80,
            loop=0
        )
        imgs[0].save(
            GIF_DOCS,
            save_all=True,
            append_images=imgs[1:],
            duration=80,
            loop=0
        )
        print(f"GIF compilado com sucesso:\n -> {GIF_OUTPUT}\n -> {GIF_DOCS}")
        return True
    else:
        print(f"Apenas {len(imgs)} frames encontrados.")
        return False

if __name__ == "__main__":
    try:
        sess = init_session()
        print("Conectado ao Autodesk Fusion! Executando varredura angular da bancada...")
        res = execute_script(sess, SCRIPT_CAPTURE_SWEEP)
        print("Resultado do script:", res)
        compile_gif()
    except Exception as e:
        print("FUSION_OFFLINE_OR_ERROR:", e)
