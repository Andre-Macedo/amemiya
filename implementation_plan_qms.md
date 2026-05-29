# Plano de Evolução: QMS & Rastreabilidade Industrial (Amemiya)

Este documento esboça a visão de futuro para transformar o Amemiya em uma plataforma de Gestão da Qualidade (QMS) e Controle de Produção (MES Light), justificando o posicionamento de alto ticket no mercado industrial.

## 1. Visão Geral da Arquitetura de Módulos

O sistema utilizará um modelo de **Feature Flags** vinculado ao Tenant. O plano base ("Metrologia Core") é o ponto de entrada, e funcionalidades avançadas são ativadas como Add-ons.

### Catálogo de Módulos (Add-ons)
| ID do Módulo | Nome | Descrição |
| :--- | :--- | :--- |
| `metrology_core` | Metrologia Base | Gestão de Instrumentos, Calibrações e Padrões. |
| `production_track` | Rastreabilidade | Vincula medições de linha a peças e ordens de produção. |
| `quality_qms` | Qualidade Avançada | Auditorias, CAPAs (Ações Corretivas) e Documentação ISO. |
| `logistics_rfid` | Logística & RFID | Controle físico de movimentação via tags e portais. |
| `billing_addon` | Faturamento | Gestão financeira para laboratórios de prestação de serviço. |

---

## 2. Modelagem de Dados: O Elo de Produção

Para justificar o valor estratégico, o sistema precisa responder: *"Quais peças foram medidas por este instrumento quando ele estava fora de tolerância?"*.

### Novas Entidades Sugeridas (Futuro)
1. **`Part` (Peça/Produto):** Cadastro técnico do que a fábrica produz (Nome, Desenho, Tolerâncias de Engenharia).
2. **`ProductionOrder` (Ordem de Produção):** Lote específico sendo produzido.
3. **`InspectionRecord` (Registro de Inspeção):** O "fato" operacional.
    - `part_id`: Qual peça?
    - `production_order_id`: Qual lote?
    - `instrument_id`: Com qual ferramenta mediu?
    - `measured_value`: Qual foi o valor?
    - `result`: Passou ou Falhou?

---

## 3. Fluxo de Valor: Rastreabilidade Reversa

O grande diferencial competitivo do Amemiya será o **Relatório de Impacto de Calibração**:

1. Uma calibração de um Paquímetro retorna como **REJEITADA**.
2. O sistema busca todos os `InspectionRecords` realizados por esse instrumento desde a última calibração aprovada.
3. O Amemiya gera uma lista de **Ordens de Produção sob Risco**.
4. O Gerente de Qualidade recebe um alerta para segregar apenas os lotes afetados, evitando um recall total.

---

## 4. Estratégia de Tecnologia (Floor Access)

Para o uso intenso no chão de fábrica (onde o ticket de R$ 2.000 se paga):

- **Tauri / Desktop:** O executável Windows permitirá integração nativa com portas seriais (RS232/USB) de balanças e paquímetros digitais, eliminando erro de digitação humana.
- **Offline First:** Capacidade de coletar medições na linha de produção mesmo sem internet, sincronizando quando o sinal estabilizar.

---

## 5. Próximos Passos para Reflexão

- [ ] Definir como o Amemiya pode importar Ordens de Produção de ERPs externos (via Webhooks ou API).
- [ ] Desenhar a interface de "Modo Quiosque" para operadores de máquina (UI simplificada e de alto contraste).
- [ ] Avaliar o uso de banco de dados NoSQL (como MongoDB) ou tabelas particionadas para o volume massivo de registros de inspeção.
