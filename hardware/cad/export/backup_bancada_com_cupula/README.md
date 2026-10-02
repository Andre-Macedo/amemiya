# Backup Completo da Bancada com Proteção NR-12 e Cúpula Articulada

**Data do Backup:** 2026-09-24  
**Projeto Fusion 360:** Amemiya  
**Pasta na Nuvem (Fusion):** `04_Bancada_Backup_Com_Cupula_NR12`  
**Diretório Local:** `hardware/cad/export/backup_bancada_com_cupula/`

---

## 1. Descrição do Conjunto
Este backup preserva o estado completo da Bancada de Testes de Vibração com adequação à norma **NR-12** e **ISO 10816**, contendo:

1. **Chassi Base (Perfis 40x80):** Perfis estruturais de alumínio extrudado 40x80 com canais T normatizados, montados sobre placa de ferro fundido de 12 mm com sapatas niveladoras antivibratórias ajustadas com contato faceado a 0 mm ($Z = -12\text{ mm}$).
2. **Sistema de Desalinhamento Controlado:**
   - Placa base usinada em alumínio anodizado laranja ($230 \times 160 \times 15\text{ mm}$) com 4 rasgos oblongos;
   - 4 parafusos jacking M8 para ajuste angular vertical e nivelamento micrométrico;
   - 2 blocos de encosto lateral push-pull com manípulos roscados M8 para ajuste horizontal angular e paralelo;
   - 4 parafusos M10 Allen de travamento mecânico.
3. **Trem de Potência e Rotação:**
   - Motor elétrico trifásico azul WEG Carcaça 63 (0.25 kW);
   - Acoplamento flexível de mandíbulas com elastômero central;
   - 2 mancais pillow block UCP204 com rolamentos auto-compensadores sobre subplacas de troca rápida em alumínio usinado ($Z = 71.7\text{ mm}$);
   - Eixo rotativo retificado $\varnothing 20\text{ mm} \times 420\text{ mm}$ centrado a $Z = 105\text{ mm}$;
   - Disco de desbalanceamento calibrado em aço inoxidável AISI 304 com 16 furos M6 a $45^\circ$ ($R = 50\text{ mm}$).
4. **Enclausuramento e Segurança NR-12:**
   - **Cúpula Estrutural:** Perfis de alumínio 2020 normatizados ($420 \times 215 \times 170\text{ mm}$) com fechamento completo em placas de policarbonato cristal antichoque de 4 mm;
   - **Articulação Cinemática:** Duas dobradiças industriais no canal T traseiro da base ($Y = +107.5\text{ mm}$, $Z = 40\text{ mm}$) com junta revoluta ativa (`Junta_Articulada_Abertura_Cupula`), permitindo abertura suave da tampa de $0^\circ$ a $105^\circ$;
   - **Alça Anatômica Frontal:** Puxador industrial tipo ponte em poliamida preta fosca ($116 \times 38 \times 16\text{ mm}$) na viga frontal;
   - **Passa-Cabos Herméticos:** 3 feições de passagem de cabos nos eixos dos sensores (Mancal 1, Tacômetro/Centro, Mancal 2);
   - **Dispositivos de Intertravamento:** Microswitch de fim-de-curso com rolete na junção da tampa para desarme instantâneo do motor em caso de abertura; botão de parada de emergência tipo cogumelo padrão NR-12 com trava e base de alerta amarela no frontal direito.

---

## 2. Inventário de Arquivos do Backup

### Arquivos STEP (.step)
- `01_Mesa_Base_Bancada.step`
- `02_Sapata_Niveladora_Borracha.step`
- `03_Placa_Base_Desalinhamento_Motor.step`
- `04_Motor_Eletrico_Trifasico.step`
- `05_Acoplamento_Flexivel_Mandibula.step`
- `06_Eixo_Rotativo_Retificado_20mm.step`
- `07_Mancal_Pillow_Block_UCP204.step`
- `08_Disco_Desbalanceamento_Calibrado.step`
- `09_Subplaca_Troca_Rapida_Mancal.step`
- `10_Parafuso_Fixacao_M10_Allen.step`
- `11_Bloco_Encosto_Push_Pull_Lateral.step`
- `12_Parafuso_Jacking_Elevacao_M8.step`
- `13_Manipulo_Push_Pull_M8.step`
- `14_Cupula_Estrutural_2020.step`
- `15_Botao_Emergencia_NR12.step`
- `16_Microswitch_Seguranca_Tampa.step`
- `17_Passa_Cabo_Borracha_Grommet.step`
- `18_Dobradica_Articulada_Mesa.step`
- `19_Alca_Puxador_Tampa.step`
- `amemiya_bancada_testes_assembly.step` (Montagem Completa)

### Mídia e Renders Técnicos
- `bancada_nr12_iso_fechada.png`: Vista isométrica geral com cúpula fechada
- `bancada_nr12_tampa_aberta.png`: Vista isométrica com a tampa aberta a $75^\circ$
- `bancada_nr12_detalhe_seguranca.png`: Detalhe do motor, folga para a cúpula e botoeira
- `bancada_nr12_detalhe_pes_perfil.png`: Detalhe dos canais T e pés niveladores
- `bancada_nr12_top_transparencia.png`: Vista superior demonstrando alinhamento coaxial
- `bancada_nr12_detalhe_traseiro_passacabos.png`: Detalhe dos 3 furos e feições de passagem de cabos
- `bancada_nr12_detalhe_alca_frontal.png`: Detalhe frontal da alça em ponte
- `bancada_nr12_abertura_tampa.gif`: Animação de ciclo completo de abertura da tampa
