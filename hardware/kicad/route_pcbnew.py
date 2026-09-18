# -*- coding: utf-8 -*-
import sys
import pcbnew

pcb_path = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_probe_tower\amemiya_probe_tower.kicad_pcb"
board = pcbnew.LoadBoard(pcb_path)
print("Board loaded:", board.GetFileName())

# Remover todas as trilhas e vias existentes para roteamento limpo
tracks = list(board.GetTracks())
for t in tracks:
    board.Remove(t)
print(f"Removed {len(tracks)} old tracks.")

# Pegar as redes via FindNet
net_3v3 = board.FindNet("+3V3")
net_gnd = board.FindNet("GND")
net_1w  = board.FindNet("1W_DQ")
net_scl = board.FindNet("I2C_SCL")
net_sda = board.FindNet("I2C_SDA")
net_sck = board.FindNet("I2S_SCK")
net_sd  = board.FindNet("I2S_SD")
net_ws  = board.FindNet("I2S_WS")

def add_track(x1_mm, y1_mm, x2_mm, y2_mm, layer, net, width_mm=0.3):
    t = pcbnew.PCB_TRACK(board)
    t.SetStart(pcbnew.VECTOR2I(pcbnew.FromMM(x1_mm), pcbnew.FromMM(y1_mm)))
    t.SetEnd(pcbnew.VECTOR2I(pcbnew.FromMM(x2_mm), pcbnew.FromMM(y2_mm)))
    t.SetWidth(pcbnew.FromMM(width_mm))
    t.SetLayer(layer)
    if net:
        t.SetNet(net)
    board.Add(t)

# Roteamento seguro sem cruzamentos:
# Net 8 (I2S_WS) no F.Cu na borda direita
add_track(20.89, 44.5, 21.5, 43.89, pcbnew.F_Cu, net_ws)
add_track(21.5, 43.89, 21.5, 33.0, pcbnew.F_Cu, net_ws)
add_track(21.5, 33.0, 17.08, 33.0, pcbnew.F_Cu, net_ws)

# Net 6 (I2S_SCK) no F.Cu
add_track(15.81, 44.5, 15.81, 37.0, pcbnew.F_Cu, net_sck)
add_track(15.81, 37.0, 17.08, 35.54, pcbnew.F_Cu, net_sck)

# Net 7 (I2S_SD) no B.Cu descendo pela direita e contornando por cima do mic
add_track(18.35, 44.5, 18.35, 39.5, pcbnew.B_Cu, net_sd)
add_track(18.35, 39.5, 6.92, 39.5, pcbnew.B_Cu, net_sd)
add_track(6.92, 39.5, 6.92, 35.54, pcbnew.B_Cu, net_sd)

# Net 4 (I2C_SCL) no B.Cu
add_track(10.73, 44.5, 10.73, 38.0, pcbnew.B_Cu, net_scl)
add_track(10.73, 38.0, 2.5, 29.77, pcbnew.B_Cu, net_scl)
add_track(2.5, 29.77, 2.5, 26.89, pcbnew.B_Cu, net_scl)
add_track(2.5, 26.89, 4.5, 26.89, pcbnew.B_Cu, net_scl)

# Net 5 (I2C_SDA) no B.Cu
add_track(13.27, 44.5, 13.27, 37.0, pcbnew.B_Cu, net_sda)
add_track(13.27, 37.0, 3.5, 27.23, pcbnew.B_Cu, net_sda)
add_track(3.5, 27.23, 3.5, 24.35, pcbnew.B_Cu, net_sda)
add_track(3.5, 24.35, 4.5, 24.35, pcbnew.B_Cu, net_sda)

# Net 3 (1W_DQ) no F.Cu
add_track(8.19, 44.5, 8.19, 41.0, pcbnew.F_Cu, net_1w)
add_track(8.19, 41.0, 10.0, 39.19, pcbnew.F_Cu, net_1w)
add_track(10.0, 39.19, 10.0, 10.0, pcbnew.F_Cu, net_1w)
add_track(10.0, 10.0, 14.54, 5.46, pcbnew.F_Cu, net_1w)
add_track(14.54, 5.46, 14.54, 4.5, pcbnew.F_Cu, net_1w)
add_track(14.54, 5.46, 17.08, 8.0, pcbnew.F_Cu, net_1w)
add_track(17.08, 8.0, 17.08, 8.5, pcbnew.F_Cu, net_1w)

# Net 1 (+3V3) no F.Cu na borda esquerda
add_track(3.11, 44.5, 1.5, 42.89, pcbnew.F_Cu, net_3v3, 0.4)
add_track(1.5, 42.89, 1.5, 4.5, pcbnew.F_Cu, net_3v3, 0.4)
add_track(1.5, 30.46, 6.92, 30.46, pcbnew.F_Cu, net_3v3, 0.4)
add_track(1.5, 14.19, 4.5, 14.19, pcbnew.F_Cu, net_3v3, 0.4)
add_track(1.5, 11.65, 4.5, 11.65, pcbnew.F_Cu, net_3v3, 0.4)
add_track(1.5, 4.5, 9.46, 4.5, pcbnew.F_Cu, net_3v3, 0.4)
add_track(9.46, 4.5, 12.0, 8.5, pcbnew.F_Cu, net_3v3, 0.4)
add_track(9.46, 4.5, 8.19, 8.5, pcbnew.F_Cu, net_3v3, 0.4)

# Net 2 (GND) no B.Cu na borda esquerda e piso
add_track(5.65, 44.5, 5.65, 43.65, pcbnew.B_Cu, net_gnd, 0.4)
add_track(5.65, 43.65, 5.65, 33.0, pcbnew.B_Cu, net_gnd, 0.4)
add_track(5.65, 33.0, 6.92, 33.0, pcbnew.B_Cu, net_gnd, 0.4)
add_track(5.65, 33.0, 5.65, 21.81, pcbnew.B_Cu, net_gnd, 0.4)
add_track(5.65, 21.81, 4.5, 21.81, pcbnew.B_Cu, net_gnd, 0.4)
add_track(5.65, 21.81, 5.65, 9.11, pcbnew.B_Cu, net_gnd, 0.4)
add_track(5.65, 9.11, 4.5, 9.11, pcbnew.B_Cu, net_gnd, 0.4)
add_track(5.65, 9.11, 5.65, 2.0, pcbnew.B_Cu, net_gnd, 0.4)
add_track(5.65, 2.0, 12.0, 2.0, pcbnew.B_Cu, net_gnd, 0.4)
add_track(12.0, 2.0, 12.0, 4.5, pcbnew.B_Cu, net_gnd, 0.4)
add_track(12.0, 4.5, 10.73, 8.5, pcbnew.B_Cu, net_gnd, 0.4)
# J_MIC pin 4 (L/R) to GND via borda direita
add_track(17.08, 30.46, 22.5, 30.46, pcbnew.B_Cu, net_gnd, 0.4)
add_track(22.5, 30.46, 22.5, 46.5, pcbnew.B_Cu, net_gnd, 0.4)
add_track(22.5, 46.5, 5.65, 46.5, pcbnew.B_Cu, net_gnd, 0.4)
add_track(5.65, 46.5, 5.65, 44.5, pcbnew.B_Cu, net_gnd, 0.4)

board.Save(pcb_path)
print("Board saved successfully via pcbnew!")
