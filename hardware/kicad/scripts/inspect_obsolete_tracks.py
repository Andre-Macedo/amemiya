import pcbnew

b = pcbnew.LoadBoard(r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2\amemiya_carrier_v2.kicad_pcb')

bad_nets = {
    '/Sensors_AFE/AMP_IN+', '/Sensors_AFE/AMP_IN-', '/Sensors_AFE/BIAS_1V65',
    '/MCU_ESP32S3/AMP_ADC', '/Sensors_AFE/TACHO_RAW', '/MCU_ESP32S3/TACHO',
    '/MCU_ESP32S3/I2S_SD', '/MCU_ESP32S3/I2S_SCK', '/MCU_ESP32S3/I2S_WS'
}

count = 0
for t in b.GetTracks():
    if t.GetNetname() in bad_nets:
        count += 1
print(f"Tracks belonging to obsolete nets: {count}")
