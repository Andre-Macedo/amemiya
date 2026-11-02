# Dicionário de Dados & Modelo Entidade-Relacionamento

Esta seção detalha as entidades fundamentais do banco de dados relacional MySQL 8.0, convenções de identificadores únicos universais ordenáveis no tempo (**ULID**) e relacionamentos dos módulos de Metrologia e IoT.

---

## 1. Padrão de Identificadores (ULID)

Todas as chaves primárias do sistema utilizam strings de 26 caracteres geradas no padrão **ULID** (*Universally Unique Lexicographically Sortable Identifier*).

```
 01ARZ3NDEK          TSV4RRFFQ69G5FAV
|----------|        |----------------|
 Timestamp               Aleatório
 (48 bits)               (80 bits)
```

**Vantagens sobre UUIDv4 e IDs Auto-Increment:**
* Indexação $B\text{-Tree}$ balanceada e sem fragmentação de disco por conter ordenação cronológica estrita.
* Ausência de colisões em nós descentralizados ou ambientes distribuídos sem dependência de locks globais.

---

## 2. Entidades do Módulo IoT

### Tabela: `iot_nodes` (Nós Sensores)
Armazena a identificação e os metadados dos nós de campo.

| Coluna | Tipo | Nulo | Descrição |
| :--- | :--- | :---: | :--- |
| `id` | `VARCHAR(26)` | Não | Chave primária ULID |
| `tenant_id` | `VARCHAR(26)` | Não | ID do cliente locatário (Multi-tenancy) |
| `gateway_id` | `VARCHAR(26)` | Sim | FK para `iot_gateways` |
| `machine_id` | `VARCHAR(26)` | Sim | Máquina/ativo monitorado |
| `code` | `VARCHAR(50)` | Não | Código de fábrica (ex: `NODE_01`) |
| `name` | `VARCHAR(100)` | Não | Nome legível (ex: `Mancal Traseiro Ventilador V-101`) |
| `status` | `VARCHAR(30)` | Não | `online`, `offline`, `maintenance` |
| `last_seen_at` | `TIMESTAMP` | Sim | Timestamp da última transmissão recebida |
| `created_at` / `updated_at` | `TIMESTAMP` | Sim | Timestamps padrão Laravel |

### Tabela: `iot_sensor_data` (Séries Temporais de Telemetria)
Armazena as leituras de alta frequência já validadas e calculadas.

| Coluna | Tipo | Nulo | Descrição |
| :--- | :--- | :---: | :--- |
| `id` | `VARCHAR(26)` | Não | Chave primária ULID |
| `iot_node_id` | `VARCHAR(26)` | Não | FK para `iot_nodes` |
| `rpm` | `UNSIGNED INT` | Sim | Velocidade em RPM |
| `temp_celsius` | `DECIMAL(6,2)` | Sim | Temperatura do mancal (°C) |
| `vrms_x` | `DECIMAL(8,3)` | Sim | Velocidade RMS no eixo X (mm/s) |
| `vrms_y` | `DECIMAL(8,3)` | Sim | Velocidade RMS no eixo Y (mm/s) |
| `vrms_z` | `DECIMAL(8,3)` | Sim | Velocidade RMS no eixo Z (mm/s) |
| `peak_accel` | `DECIMAL(8,3)` | Sim | Pico máximo de aceleração (g) |
| `crest_factor` | `DECIMAL(6,2)` | Sim | Fator de crista |
| `iso_zone` | `ENUM('A','B','C','D')` | Não | Classificação ISO 20816-3 |
| `battery_voltage` | `DECIMAL(4,2)` | Sim | Tensão medida da bateria (V) |
| `recorded_at` | `TIMESTAMP` | Não | Momento real da amostragem na bancada |

---

## 3. Entidades do Módulo Metrologia

### Tabela: `instruments` (Parque de Instrumentos)
| Coluna | Tipo | Nulo | Descrição |
| :--- | :--- | :---: | :--- |
| `id` | `VARCHAR(26)` | Não | Chave primária ULID |
| `tag` | `VARCHAR(50)` | Não | Tag metrológico (ex: `CAL-MIC-042`) |
| `description` | `VARCHAR(255)` | Não | Descrição do item |
| `instrument_type_id` | `VARCHAR(26)` | Não | FK para tipos (Paquímetro, Acelerômetro, etc.) |
| `max_permissible_error` | `DECIMAL(12,6)` | Sim | Critério de aceitação metrológico |
| `calibration_interval_days` | `INT` | Não | Periodicidade em dias |

### Tabela: `calibrations` (Eventos de Calibração)
| Coluna | Tipo | Nulo | Descrição |
| :--- | :--- | :---: | :--- |
| `id` | `VARCHAR(26)` | Não | Chave primária ULID |
| `instrument_id` | `VARCHAR(26)` | Não | FK para `instruments` |
| `certificate_number` | `VARCHAR(50)` | Não | Número único do laudo oficial |
| `expanded_uncertainty` | `DECIMAL(12,6)` | Não | Incerteza expandida calculada $U$ |
| `coverage_factor_k` | `DECIMAL(4,2)` | Não | Fator de abrangência $k$ (ex: 2.00) |
| `sha256_hash` | `CHAR(64)` | Sim | Hash criptográfico de integridade do PDF |
| `approved_at` | `TIMESTAMP` | Sim | Data formal de emissão/assinatura |
