# Diretrizes de Modelagem CAD e Montagem no Autodesk Fusion 360

Este documento consolida os padrões de engenharia, melhores práticas e diretrizes técnicas adotadas no projeto **Amemiya / Sistema Metrológico Lean Tech** para o desenvolvimento de peças e montagens mecânicas no Autodesk Fusion 360.

---

## 1. Filosofia Bottom-Up (Peça a Peça)

O desenvolvimento segue estritamente a metodologia **Bottom-Up**:
1. **Modelagem de Peças Isoladas:**
   - Cada peça física ou componente eletrônico é modelado em seu **próprio arquivo individual** (ex: `01_Gabinete_Corpo_Torre`, `02_Placa_PCB_Torre_Com_Sensores`).
   - Cada arquivo deve conter apenas o corpo/componente daquela peça específica.
   - Nenhuma peça deve ser modelada "solta" dentro do arquivo de montagem.
2. **Organização de Pastas no Projeto Fusion 360:**
   - Pasta `01_Probe_Tower_Components/`: Peças isoladas da Torre de Medição.
   - Pasta `02_Main_Node_Components/`: Peças isoladas do Main Node.
   - Pasta `03_Tacho_Probe_Components/`: Peças isoladas do Sensor Tacômetro.
   - Pasta `04_Bancada_Components/`: Peças isoladas da Bancada / Acessórios.
3. **Arquivo de Montagem Dedicado:**
   - A montagem fica na raiz do projeto ou em pasta dedicada, nomeada como `amemiya_<modulo>_assembly` (ex: `amemiya_probe_tower_assembly`).
   - O arquivo de montagem **apenas referencia** os componentes individuais através de instâncias (`Occurrences`).

---

## 2. Esboços e Dimensionamento Paramétrico (Sketches & Dimensions)

Todos os esboços devem ser matematicamente definidos e estáveis:

### 2.1 Regra de Ouro: Esboço 100% Restrito (`isFullyConstrained: True`)
- No Fusion 360, todas as linhas do esboço devem ser **pretas** (completamente restritas). Nenhuma linha azul (sub-restringida) é permitida em peças finais.
- **Ancoragem na Origem:** O esboço deve sempre iniciar ancorado na Origem do plano `(0, 0, 0)` usando restrição de coincidência (`geometricConstraints.addCoincident`).
- **Restrições Geométricas:** Aplicar relações geométricas explícitas:
  - Horizontal e Vertical (`addHorizontal`, `addVertical`).
  - Coincidentes para fechar perfis (`addCoincident`).
  - Concêntricas para furações e rebaixos (`addConcentric`).
  - Tangência em curvas e concordâncias (`addTangent`).

### 2.2 Tabela de Parâmetros de Usuário (`User Parameters` / `fx`)
- **Proibido Magic Numbers:** Nenhuma medida crítica deve ser inserida como valor fixo não nomeado.
- Todas as cotas devem ser registradas como parâmetros de usuário através de `design.userParameters.add(nome, adsk.core.ValueInput.createByString(expressao), unidade, descricao)`.
- **Exemplo de Nomenclatura:**
  - `largura_corpo = 30 mm`
  - `profundidade_corpo = 32 mm`
  - `altura_corpo = 56 mm`
  - `espessura_parede = 2.5 mm`
  - `diam_furo_gx16 = 16 mm`
- **Cotas no Esboço:** As cotas dimensionais no esboço devem consumir o parâmetro nominal (`sketchDimensions.addDistanceDimension(p1, p2, orientation, textPos)` -> `dim.parameter.expression = "largura_corpo"`).

---

## 3. Montagens e Juntas Cinemáticas (Assembly & Kinematic Joints)

O Fusion 360 adota a filosofia baseada em **Graus de Liberdade (DoF - Degrees of Freedom)**, em vez de restrições acumulativas clássicas (*Mates* de outros softwares CAD).

### 3.1 Componente Aterrado (*Grounded Base*)
- Em toda montagem, o componente que serve de estrutura base (gabinete/chassi) **DEVE ser aterrado**:
  ```python
  occ_base = root.occurrences.addByInsert(df_base, id_matrix, True)
  occ_base.isGrounded = True
  ```
- O aterramento fixa a peça no espaço absoluto do mundo 3D (exibe o ícone de alfinete na árvore). Todas as outras peças se referenciam a ela.

### 3.2 Juntas Rígidas (*Rigid Joints / As-Built Joints*)
- Uma **Junta Rígida** (*Rigid Joint*) trava instantaneamente todos os **6 Graus de Liberdade** (3 translações + 3 rotações).
- Ela garante simultaneamente:
  - Concentricidade de eixos e furações.
  - Coplanaridade e paralelismo de faces em contato.
  - Alinhamento de orientação espacial.
- **As-Built Joint (Recomendado quando peças já inseridas na posição nominal):**
  ```python
  asBuiltJoints = root.asBuiltJoints
  joint_input = asBuiltJoints.createInput(occ_filho, occ_pai, None)
  joint_input.setAsRigidJointMotion()
  junta = asBuiltJoints.add(joint_input)
  junta.name = "Junta_Rigida_Tampa_Gabinete"
  ```
- **Hierarquia de Juntas:**
  - Base aterrada -> Componentes internos (PCB, Suportes).
  - Base aterrada -> Tampas / Fechamentos.
  - Tampas -> Conectores e Parafusos.

### 3.3 Juntas de Revolução e Cinemática Ativa (*Revolute Joints & Motion Studies*)
- Para conjuntos rotativos (eixos, rotores de motor, mancais, discos inerciais), utiliza-se **Junta de Revolução** (*Revolute Joint*) para permitir a rotação contínua de 360° em torno do eixo coaxial:
  ```python
  circle_edge = occ_eixo.bRepBodies.item(0).edges[idx] # Aresta circular do eixo/mancal
  geo = adsk.fusion.JointGeometry.createByCurve(circle_edge, adsk.fusion.JointKeyPointTypes.CenterKeyPoint)
  jin = asBuiltJoints.createInput(occ_eixo, occ_mancal_a, geo)
  jin.setAsRevoluteJointMotion(adsk.fusion.JointDirections.XAxisJointDirection)
  j_rev = asBuiltJoints.add(jin)
  j_rev.name = "Junta_Revolucao_Eixo_Principal"
  ```
- **Simulação Interativa:** Permite ao usuário clicar com o botão direito na junta na árvore do Fusion e selecionar **"Animar Modelo" (*Animate Model*)** ou criar simulações temporais no **Estudo de Movimento (*Motion Study*)**.

### 3.4 Mecanismo de Desalinhamento Cinemático (Push-Pull + Jacking Bolts)
- Em bancadas de ensaio e simulação de falhas mecânicas, o operador **não deve remover o motor** para aplicar calços soltos.
- O ajuste de desalinhamento deve ser 100% mecânico por fusos graduados:
  - **Desalinhamento Vertical ($\Delta Z$ e $\theta_v$):** 4 parafusos de elevação verticais (*jacking bolts*) roscados nos 4 cantos da placa do motor, apoiando diretamente sobre a mesa de alumínio. Passo de rosca de $1.0\text{ mm}$ permite resolução micrométrica por fração de volta.
  - **Desalinhamento Horizontal ($\Delta Y$ e $\theta_h$):** 2 blocos de encosto lateral com parafusos horizontais push-pull (manípulos recartilhados com ponta em latão) atuando nas faces laterais da placa do motor.

### 3.5 Verificação de Graus de Liberdade e Testes
1. **Arrasto Manual:** Ao tentar clicar e arrastar qualquer componente na viewport, apenas os graus de liberdade intencionais (giro do eixo em torno de X) devem se mover. Todas as bases e mancais devem estar rigidamente fixados.
2. **Pasta de Juntas:** Todas as juntas devem estar visíveis, nomeadas e ativas na pasta `Joints` do navegador do Fusion.
3. **Análise de Interferência:** Executar `Inspect > Interference` entre todos os componentes para certificar ausência de sobreposição sólida indesejada.

---

## 4. Gerenciamento de Arquivos e Execução de Scripts via API Fusion

Ao automatizar criação de peças e montagens via API do Fusion 360, as seguintes regras devem ser rigorosamente seguidas:

1. **Evitar Sobrescrita Concorrente:**
   - Nunca chamar múltiplos `doc.save()` consecutivos em curto intervalo de tempo. O Fusion sincroniza arquivos na nuvem da Autodesk e chamadas repetidas podem gerar timeouts ou duplicatas.
2. **Prevenção de Arquivos Duplicados na Nuvem:**
   - Antes de criar um arquivo de montagem, verificar se já existe arquivo com o mesmo nome na pasta através de `target_folder.dataFiles`.
   - Caso existam versões de teste ou duplicatas antigas, fechar todos os documentos abertos e chamar `df.deleteMe()` antes de gravar a versão definitiva.
3. **Atualização de Visualização:**
   - Sempre executar `vp.camera.isFitView = True` e `vp.refresh()` após a inserção e aplicação de juntas para garantir que o usuário veja o modelo montado centralizado ao abrir o software.

---

## 5. Exportação e Entregáveis

Para cada conjunto de hardware desenvolvido:
1. **Pasta de Exportação:** `hardware/cad/export/<nome_modulo>/`
2. **Arquivos STEP Individuais:** Cada componente exportado em formato STEP AP214/AP242 (`.step`).
3. **Arquivo STEP de Montagem:** Montagem completa exportada com hierarquia de corpos (`amemiya_<modulo>_assembly.step`).
4. **Capturas de Tela:** Imagens PNG de alta resolução salvas na pasta de artefatos para inspeção visual rápida.
