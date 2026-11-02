import pcbnew

b = pcbnew.LoadBoard(r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2\amemiya_carrier_v2.kicad_pcb')

fps = [fp.GetReference() for fp in b.GetFootprints()]
print(f"Total footprints: {len(fps)}")
print("Footprints:", sorted(fps))

targets = ['J_AMP', 'R_B', 'R_BIAS', 'C_BIAS', 'R_F', 'C_F', 'D_AMP', 'J_TACHO', 'R_PU_TACHO', 'R_S_TACHO', 'C_F_TACHO']
matching = [ref for ref in fps if any(t in ref for t in targets)]
print("\nMatching targets to remove:", matching)
