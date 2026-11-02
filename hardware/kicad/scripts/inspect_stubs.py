import pcbnew

b = pcbnew.LoadBoard(r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2\amemiya_carrier_v2.kicad_pcb')

tracks_in_box = []
for t in b.GetTracks():
    # If net is Net-(J_AMP1-Pin_2)
    if "J_AMP" in t.GetNetname():
        tracks_in_box.append(t)
        continue
    # If in box X < 40 and 30 < Y < 65
    sx, sy = t.GetStart().x / 1e6, t.GetStart().y / 1e6
    ex, ey = t.GetEnd().x / 1e6, t.GetEnd().y / 1e6
    if (sx < 40 and 30 < sy < 65) or (ex < 40 and 30 < ey < 65):
        tracks_in_box.append(t)

print(f"Total candidate tracks to inspect/remove: {len(tracks_in_box)}")
for t in tracks_in_box:
    print(f"  Track net={t.GetNetname()} layer={t.GetLayerName()} ({t.GetStart().x/1e6:.2f}, {t.GetStart().y/1e6:.2f}) -> ({t.GetEnd().x/1e6:.2f}, {t.GetEnd().y/1e6:.2f})")
