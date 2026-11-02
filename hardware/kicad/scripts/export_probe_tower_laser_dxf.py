"""
Script de Exportação de Precisão para Manufatura a Laser e Furação CNC
Projeto: Amemiya IoT - Sonda Torre Dual (Probe Tower Dual)
Empresa: Lean Tech Metrology & Industrial IoT

Gera os arquivos otimizados para:
1. Máquina Laser (LightBurn, LaserGRBL, EzCad, CAD Viewers):
   - DXF Sólido Preto e Branco (Hachura SOLID com fundo preto e trilhas brancas sólidas)
   - DXF de Trilhas Brancas Sólidas (Hachura SOLID branca sobre contorno)
   - DXF Negativo de Desgaste/Ablação (Pocketing)
   - DXF Positivo de Trilhas e Pads
   - PNG 1200 DPI 1:1 Preto e Branco (Negativo e Positivo)
   - SVG Vetorial 1:1
2. Furação CNC:
   - DXF de Furação com Círculos Reais de Broca e Centro em Cruz
   - DXF de Contorno da Placa (Edge.Cuts 24x48 mm)
"""

import os
import sys
import subprocess
import json
import shapely
from shapely.geometry import Polygon, MultiPolygon, LineString, Point, box
from shapely.ops import unary_union
import ezdxf
from PIL import Image, ImageDraw

REPO_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", ".."))
PROJECT_DIR = os.path.join(REPO_ROOT, "hardware", "kicad", "amemiya_probe_tower_dual")
PCB_FILE = os.path.join(PROJECT_DIR, "amemiya_probe_tower_dual.kicad_pcb")
OUT_DIR = os.path.join(PROJECT_DIR, "fabrication", "laser_export")
KICAD_PYTHON = r"C:\Program Files\KiCad\9.0\bin\python.exe"

os.makedirs(OUT_DIR, exist_ok=True)

def extract_geometry():
    """Usa o Python do KiCad para extrair dados geométricos exatos do PCB."""
    py_code = f"""
import pcbnew, json
b = pcbnew.LoadBoard(r"{PCB_FILE}")
bb = b.GetBoardEdgesBoundingBox()
info = {{
    "box": {{"x": bb.GetX()/1e6, "y": bb.GetY()/1e6, "w": bb.GetWidth()/1e6, "h": bb.GetHeight()/1e6}},
    "tracks": [],
    "pads": [],
    "drills": []
}}
for t in b.GetTracks():
    info["tracks"].append({{
        "layer": t.GetLayerName(), "net": t.GetNetname(),
        "x1": t.GetStart().x/1e6, "y1": t.GetStart().y/1e6,
        "x2": t.GetEnd().x/1e6, "y2": t.GetEnd().y/1e6,
        "width": t.GetWidth()/1e6
    }})
for fp in b.GetFootprints():
    ref = fp.GetReference()
    for p in fp.Pads():
        pos = p.GetPosition()
        size = p.GetSize()
        drill = p.GetDrillSize()
        layers = []
        if p.IsOnLayer(pcbnew.B_Cu): layers.append("B.Cu")
        if p.IsOnLayer(pcbnew.F_Cu): layers.append("F.Cu")
        info["pads"].append({{
            "ref": ref, "num": p.GetNumber(), "net": p.GetNetname(),
            "x": pos.x/1e6, "y": pos.y/1e6, "w": size.x/1e6, "h": size.y/1e6,
            "drill": drill.x/1e6, "layers": layers
        }})
        if drill.x > 0:
            info["drills"].append({{"ref": ref, "num": p.GetNumber(), "x": pos.x/1e6, "y": pos.y/1e6, "drill": drill.x/1e6}})

print(json.dumps(info))
"""
    res = subprocess.run([KICAD_PYTHON, "-c", py_code], capture_output=True, text=True, check=True)
    return json.loads(res.stdout)

def add_shapely_hatch(msp, geom, layer_name, rgb_color):
    """Adiciona hachura sólida DXF (HATCH pattern SOLID) com TrueColor RGB."""
    if geom.is_empty:
        return
    polys = [geom] if isinstance(geom, Polygon) else list(geom.geoms) if isinstance(geom, MultiPolygon) else []
    true_col = ezdxf.rgb2int(rgb_color)
    for poly in polys:
        p_clean = poly.simplify(0.005, preserve_topology=True)
        if p_clean.is_empty or not hasattr(p_clean, 'exterior'):
            continue
        hatch = msp.add_hatch(dxfattribs={"layer": layer_name, "color": 7, "true_color": true_col})
        hatch.set_solid_fill()
        ext_coords = [(pt[0], pt[1]) for pt in p_clean.exterior.coords]
        hatch.paths.add_polyline_path(ext_coords, is_closed=True, flags=ezdxf.const.BOUNDARY_PATH_EXTERNAL)
        for interior in p_clean.interiors:
            int_coords = [(pt[0], pt[1]) for pt in interior.coords]
            hatch.paths.add_polyline_path(int_coords, is_closed=True, flags=ezdxf.const.BOUNDARY_PATH_DEFAULT)

def poly_to_ezdxf(msp, geom, layer_name="0", rgb_color=None):
    if geom.is_empty:
        return
    polys = [geom] if isinstance(geom, Polygon) else list(geom.geoms) if isinstance(geom, MultiPolygon) else []
    dxfattribs = {"layer": layer_name}
    if rgb_color:
        dxfattribs["true_color"] = ezdxf.rgb2int(rgb_color)
    for poly in polys:
        ext_pts = [(p[0], p[1]) for p in poly.exterior.coords]
        msp.add_lwpolyline(ext_pts, close=True, dxfattribs=dxfattribs)
        for interior in poly.interiors:
            int_pts = [(p[0], p[1]) for p in interior.coords]
            msp.add_lwpolyline(int_pts, close=True, dxfattribs=dxfattribs)

def generate_laser_files():
    print(f"Extraindo geometria do PCB: {PCB_FILE}...")
    geo = extract_geometry()
    board_box = box(0.0, 0.0, 24.0, 48.0)

    for layer_name in ["B.Cu", "F.Cu"]:
        print(f"\nProcessando camada: {layer_name}")
        copper_shapes = []

        for t in geo["tracks"]:
            if t["layer"] == layer_name:
                ls = LineString([(t["x1"], t["y1"]), (t["x2"], t["y2"])])
                copper_shapes.append(ls.buffer(t["width"] / 2.0, cap_style="round", join_style="round", resolution=32))

        for pad in geo["pads"]:
            if layer_name in pad["layers"]:
                px, py = pad["x"], pad["y"]
                pw, ph = pad["w"], pad["h"]
                if abs(pw - ph) < 1e-3:
                    copper_shapes.append(Point(px, py).buffer(pw / 2.0, resolution=32))
                else:
                    copper_shapes.append(box(px - pw/2.0, py - ph/2.0, px + pw/2.0, py + ph/2.0))

        if not copper_shapes:
            continue

        copper_pos = unary_union(copper_shapes).intersection(board_box)
        copper_neg = board_box.difference(copper_pos)

        layer_tag = layer_name.replace('.', '_')

        # 1. NOVO: DXF Sólido Preto e Branco (Hachura Sólida com Fundo Preto e Trilhas Brancas)
        doc_solid = ezdxf.new("R2010", setup=True)
        doc_solid.units = ezdxf.units.MM
        msp_solid = doc_solid.modelspace()
        doc_solid.layers.new(name="COBRE_A_REMOVER_PRETO", dxfattribs={"color": 7, "true_color": ezdxf.rgb2int((0, 0, 0))})
        doc_solid.layers.new(name="TRILHAS_PRESERVADAS_BRANCO", dxfattribs={"color": 7, "true_color": ezdxf.rgb2int((255, 255, 255))})
        doc_solid.layers.new(name="BORDA_PLACA", dxfattribs={"color": 3, "true_color": ezdxf.rgb2int((0, 255, 0))})
        add_shapely_hatch(msp_solid, copper_neg, "COBRE_A_REMOVER_PRETO", (0, 0, 0))
        add_shapely_hatch(msp_solid, copper_pos, "TRILHAS_PRESERVADAS_BRANCO", (255, 255, 255))
        poly_to_ezdxf(msp_solid, copper_pos, "TRILHAS_PRESERVADAS_BRANCO", (255, 255, 255))
        msp_solid.add_lwpolyline([(0,0), (24,0), (24,48), (0,48)], close=True, dxfattribs={"layer": "BORDA_PLACA"})
        doc_solid.saveas(os.path.join(OUT_DIR, f"probe_tower_dual_{layer_tag}_solid_black_white.dxf"))

        # 2. NOVO: DXF Trilhas Brancas Sólidas (apenas trilhas preenchidas de branco)
        doc_white = ezdxf.new("R2010", setup=True)
        doc_white.units = ezdxf.units.MM
        msp_white = doc_white.modelspace()
        doc_white.layers.new(name="TRILHAS_BRANCAS_SOLIDAS", dxfattribs={"color": 7, "true_color": ezdxf.rgb2int((255, 255, 255))})
        doc_white.layers.new(name="BORDA_PLACA", dxfattribs={"color": 3, "true_color": ezdxf.rgb2int((0, 255, 0))})
        add_shapely_hatch(msp_white, copper_pos, "TRILHAS_BRANCAS_SOLIDAS", (255, 255, 255))
        poly_to_ezdxf(msp_white, copper_pos, "TRILHAS_BRANCAS_SOLIDAS", (255, 255, 255))
        msp_white.add_lwpolyline([(0,0), (24,0), (24,48), (0,48)], close=True, dxfattribs={"layer": "BORDA_PLACA"})
        doc_white.saveas(os.path.join(OUT_DIR, f"probe_tower_dual_{layer_tag}_trilhas_brancas_solidas.dxf"))

        # 3. DXF Positivo Contorno
        doc_pos = ezdxf.new("R2010", setup=True)
        doc_pos.units = ezdxf.units.MM
        msp_pos = doc_pos.modelspace()
        doc_pos.layers.new(name="COPPER_TRACKS_PADS", dxfattribs={"color": 1})
        poly_to_ezdxf(msp_pos, copper_pos, layer_name="COPPER_TRACKS_PADS")
        doc_pos.layers.new(name="EDGE_CUTS", dxfattribs={"color": 3})
        msp_pos.add_lwpolyline([(0,0), (24,0), (24,48), (0,48)], close=True, dxfattribs={"layer": "EDGE_CUTS"})
        doc_pos.saveas(os.path.join(OUT_DIR, f"probe_tower_dual_{layer_tag}_positive.dxf"))

        # 4. DXF Negativo (Desgaste / Ablação a Laser)
        doc_neg = ezdxf.new("R2010", setup=True)
        doc_neg.units = ezdxf.units.MM
        msp_neg = doc_neg.modelspace()
        doc_neg.layers.new(name="LASER_ABLATION_POCKET", dxfattribs={"color": 7})
        poly_to_ezdxf(msp_neg, copper_neg, layer_name="LASER_ABLATION_POCKET")
        doc_neg.saveas(os.path.join(OUT_DIR, f"probe_tower_dual_{layer_tag}_negative_ablation.dxf"))

        # 5. Imagens Raster 1200 DPI
        DPI = 1200
        SCALE = DPI / 25.4
        img_w = int(round(24.0 * SCALE))
        img_h = int(round(48.0 * SCALE))

        img_neg = Image.new("L", (img_w, img_h), color=0)
        draw_neg = ImageDraw.Draw(img_neg)

        polys = [copper_pos] if isinstance(copper_pos, Polygon) else list(copper_pos.geoms) if isinstance(copper_pos, MultiPolygon) else []
        for poly in polys:
            ext_coords = [(p[0] * SCALE, p[1] * SCALE) for p in poly.exterior.coords]
            draw_neg.polygon(ext_coords, fill=255)
            for interior in poly.interiors:
                int_coords = [(p[0] * SCALE, p[1] * SCALE) for p in interior.coords]
                draw_neg.polygon(int_coords, fill=0)

        img_neg.save(os.path.join(OUT_DIR, f"probe_tower_dual_{layer_tag}_laser_ablation_negative_1200dpi.png"), dpi=(DPI, DPI))

        img_pos = Image.new("L", (img_w, img_h), color=255)
        draw_pos = ImageDraw.Draw(img_pos)
        for poly in polys:
            ext_coords = [(p[0] * SCALE, p[1] * SCALE) for p in poly.exterior.coords]
            draw_pos.polygon(ext_coords, fill=0)
            for interior in poly.interiors:
                int_coords = [(p[0] * SCALE, p[1] * SCALE) for p in interior.coords]
                draw_pos.polygon(int_coords, fill=255)

        img_pos.save(os.path.join(OUT_DIR, f"probe_tower_dual_{layer_tag}_positive_1200dpi.png"), dpi=(DPI, DPI))

        # 6. SVG Vetorial
        svg_path = os.path.join(OUT_DIR, f"probe_tower_dual_{layer_tag}_laser.svg")
        with open(svg_path, "w", encoding="utf-8") as f:
            f.write(f'<?xml version="1.0" encoding="UTF-8"?>\n')
            f.write(f'<svg xmlns="http://www.w3.org/2000/svg" width="24mm" height="48mm" viewBox="0 0 24 48">\n')
            f.write(f'  <rect x="0" y="0" width="24" height="48" fill="none" stroke="#00FF00" stroke-width="0.15" />\n')
            for poly in polys:
                ext_d = "M " + " L ".join([f"{p[0]:.4f},{p[1]:.4f}" for p in poly.exterior.coords]) + " Z"
                for hole in poly.interiors:
                    ext_d += " M " + " L ".join([f"{p[0]:.4f},{p[1]:.4f}" for p in hole.coords]) + " Z"
                f.write(f'  <path d="{ext_d}" fill="#000000" fill-rule="evenodd" stroke="none" />\n')
            f.write(f'</svg>\n')

    # CNC Furação
    print("\nProcessando furos para CNC Router...")
    doc_drill = ezdxf.new("R2010", setup=True)
    doc_drill.units = ezdxf.units.MM
    msp_drill = doc_drill.modelspace()
    doc_drill.layers.new(name="DRILL_HOLES", dxfattribs={"color": 4})
    doc_drill.layers.new(name="DRILL_CENTERS", dxfattribs={"color": 1})
    doc_drill.layers.new(name="EDGE_CUTS", dxfattribs={"color": 3})
    msp_drill.add_lwpolyline([(0,0), (24,0), (24,48), (0,48)], close=True, dxfattribs={"layer": "EDGE_CUTS"})
    for d in geo["drills"]:
        dx, dy, dr = d["x"], d["y"], d["drill"]
        msp_drill.add_circle((dx, dy), radius=dr/2.0, dxfattribs={"layer": "DRILL_HOLES"})
        msp_drill.add_line((dx - 0.4, dy), (dx + 0.4, dy), dxfattribs={"layer": "DRILL_CENTERS"})
        msp_drill.add_line((dx, dy - 0.4), (dx, dy + 0.4), dxfattribs={"layer": "DRILL_CENTERS"})
    doc_drill.saveas(os.path.join(OUT_DIR, "probe_tower_dual_cnc_drills.dxf"))

    # Edge Cuts
    doc_edge = ezdxf.new("R2010", setup=True)
    doc_edge.units = ezdxf.units.MM
    msp_edge = doc_edge.modelspace()
    doc_edge.layers.new(name="EDGE_CUTS", dxfattribs={"color": 3})
    msp_edge.add_lwpolyline([(0,0), (24,0), (24,48), (0,48)], close=True, dxfattribs={"layer": "EDGE_CUTS"})
    doc_edge.saveas(os.path.join(OUT_DIR, "probe_tower_dual_Edge_Cuts.dxf"))

    print(f"\n[OK] Todos os arquivos foram gerados com sucesso em:\n{OUT_DIR}")

if __name__ == "__main__":
    generate_laser_files()
