import re

with open(r'hardware\kicad\amemiya_probe_tower\amemiya_probe_tower.kicad_pcb', 'r', encoding='utf-8') as f:
    text = f.read()

# Find footprints
blocks = re.findall(r'\(footprint\s+"([^"]*)"\s+(.*?)\n\t\)', text, re.DOTALL)
for fp_name, body in blocks:
    at_m = re.search(r'\(at\s+([0-9.-]+)\s+([0-9.-]+)(?:\s+([0-9.-]+))?\)', body)
    ref_m = re.search(r'\(property\s+"Reference"\s+"([^"]*)"', body)
    val_m = re.search(r'\(property\s+"Value"\s+"([^"]*)"', body)
    pos_str = f"({at_m.group(1)}, {at_m.group(2)})" if at_m else "None"
    rot_str = at_m.group(3) if at_m and at_m.group(3) else "0"
    ref_str = ref_m.group(1) if ref_m else "Unknown"
    val_str = val_m.group(1) if val_m else "Unknown"
    print(f"Ref: {ref_str:<12} Val: {val_str:<15} Pos: {pos_str:<18} Rot: {rot_str:<5} FP: {fp_name}")

# Board dimensions
edge_cuts = re.findall(r'\(gr_line\s+\(start\s+([0-9.-]+)\s+([0-9.-]+)\)\s+\(end\s+([0-9.-]+)\s+([0-9.-]+)\).*?\(layer\s+"Edge\.Cuts"\)', text)
print("\nEdge.Cuts lines:")
for l in edge_cuts:
    print(f"  Start: ({l[0]}, {l[1]}) -> End: ({l[2]}, {l[3]})")
