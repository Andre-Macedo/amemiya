# EGM Design Documents & Architecture Sitemap

## Visão Geral
Este sitemap indexa todas as especificações técnicas, designs de arquitetura e contratos do Elephant-Goldfish Model (EGM) no ecossistema do Sistema de Metrologia Lean Tech.

---

## 🏛️ Especificações Centrais de Arquitetura

- **[ARQUITETURA_ECOSSISTEMA_LEAN_TECH.md](./ARQUITETURA_ECOSSISTEMA_LEAN_TECH.md)**: Especificação Oficial da Arquitetura do Sistema.
  - *Escopo*: Integração de ponta a ponta (ESP32-S3 -> MQTT Mosquitto -> Laravel Queue / Redis -> ML FastAPI XGBoost -> Reverb WebSockets -> Next.js / Filament / React Native).
  - *Hardware v37.0*: Pinout completo, barramento SPI multiplexado com display TFT 3.2" ILI9341 + Touch XPT2046 (`J_TFT`), conector circular industrial GX16-8 para a sonda e alimentação externa regulada.
  - *Modelo de Dados*: Tópicos MQTT, canais WebSockets multi-tenant (`tenant.{tenantSlug}.iot`) e especificação das 36 features preditivas.

- **[pinagem-hardware.md](../referencia/pinagem-hardware.md)**: Documento de referência de pinagem de hardware, conectores industriais e limites elétricos (MOSFET de backlight, multiplexação de CS).

- **[Arquitetura_Software_e_Banco_de_Dados_IoT.md](../Arquitetura_Software_e_Banco_de_Dados_IoT.md)**: Visão detalhada de software, banco de dados, tabelas relacionais de telemetria e integração com o microserviço de ML.

---

## 📄 Contratos Arquiteturais EGM (Peanuts & Hay)

### Repositório Principal (`amemiya`)
- **[amemiya/EGM.md](../../EGM.md)**: Blueprint raiz do backend Laravel 12 e orquestração Docker.
- **[amemiya/app/EGM.md](../../app/EGM.md)**: Infraestrutura central, multi-tenancy `X-Tenant`, Filament Admin Panel.
- **[amemiya/config/EGM.md](../../config/EGM.md)**: Camada de configuração declarativa.
- **[amemiya/Modules/EGM.md](../../Modules/EGM.md)**: Blueprint intermediário de módulos de negócio.
- **[amemiya/Modules/IoT/EGM.md](../../Modules/IoT/EGM.md)**: Telemetria industrial, ponte MQTT, filas Redis e eventos WebSockets.
- **[amemiya/Modules/IoT/ml_service/EGM.md](../../Modules/IoT/ml_service/EGM.md)**: Microserviço Python FastAPI com modelo XGBoost de 36 features.
- **[amemiya/Modules/Metrology/EGM.md](../../Modules/Metrology/EGM.md)**: Calibrações ISO/IEC 17025 e assinatura eletrônica 21 CFR Part 11.
- **[amemiya/Modules/System/EGM.md](../../Modules/System/EGM.md)**: Governança, RBAC Filament Shield e Audit Chain indelével.

### Portal Web (`metrology-sass-front`)
- **[metrology-sass-front/EGM.md](../../../metrology-sass-front/EGM.md)**: Blueprint raiz do frontend Next.js App Router.
- **[metrology-sass-front/app/EGM.md](../../../metrology-sass-front/app/EGM.md)**: Roteamento internacionalizado `[locale]` e rotas protegidas.
- **[metrology-sass-front/components/EGM.md](../../../metrology-sass-front/components/EGM.md)**: Design system e instrumentação IoT (Zonas ISO 20816).
- **[metrology-sass-front/features/EGM.md](../../../metrology-sass-front/features/EGM.md)**: Módulos de calibração, inventário e regras de negócio.
- **[metrology-sass-front/lib/EGM.md](../../../metrology-sass-front/lib/EGM.md)**: Cliente HTTP com injeção de `X-Tenant` e tipagens.

### Aplicativo Mobile (`amemiya-app`)
- **[amemiya-app/EGM.md](../../../amemiya-app/EGM.md)**: Blueprint raiz do app React Native / Expo.
- **[amemiya-app/app/EGM.md](../../../amemiya-app/app/EGM.md)**: Navegação Expo Router por abas e checklists de campo.
- **[amemiya-app/components/EGM.md](../../../amemiya-app/components/EGM.md)**: IHM industrial, gráficos de bateria/vibração e alarme háptico Zona D.
- **[amemiya-app/services/EGM.md](../../../amemiya-app/services/EGM.md)**: Sincronização offline-first e persistência local.
- **[amemiya-app/types/EGM.md](../../../amemiya-app/types/EGM.md)**: Contratos TypeScript de telemetria v37.0 (`battery_voltage`).
