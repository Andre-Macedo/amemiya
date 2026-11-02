import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Lean Tech Metrologia & IoT',
  description: 'Portal de Documentação Técnica, Engenharia Metrológica e Telemetria Preditiva Amemiya.',
  lang: 'pt-BR',
  lastUpdated: true,
  cleanUrls: true,
  ignoreDeadLinks: 'localhostLinks',
  srcExclude: [
    '**/planning/**',
    '**/architecture/**',
    '**/iot/**',
    '**/metrology/**',
    '**/saas/**',
    '**/slide_screenshots/**',
    '**/*.pdf',
    '**/*.pptx'
  ],

  themeConfig: {
    siteTitle: 'Lean Tech Metrologia',
    logo: '/logo.svg',

    nav: [
      { text: 'Visão Geral', link: '/' },
      { text: 'Tutoriais', link: '/tutoriais/inicio-rapido-docker' },
      { text: 'Guias Práticos', link: '/guias/calibrar-adxl345' },
      { text: 'Referência', link: '/referencia/pinagem-hardware' },
      { text: 'Explicações & ADRs', link: '/explicacoes/arquitetura-distribuida' },
      { 
        text: 'API REST (Scramble)', 
        link: 'http://localhost:8000/docs/api',
        target: '_blank'
      }
    ],

    sidebar: {
      '/tutoriais/': [
        {
          text: '1. Tutoriais (Comece Aqui)',
          collapsed: false,
          items: [
            { text: 'Início Rápido com Docker', link: '/tutoriais/inicio-rapido-docker' },
            { text: 'Primeira Conexão na Bancada ESP32', link: '/tutoriais/primeira-bancada-esp32' }
          ]
        }
      ],
      '/guias/': [
        {
          text: '2. Guias Práticos (How-To)',
          collapsed: false,
          items: [
            { text: 'Calibração Estática ADXL345 (±1g)', link: '/guias/calibrar-adxl345' },
            { text: 'Medição de RPM e Alinhamento Sensor Hall', link: '/guias/medir-rpm-tacometro' },
            { text: 'Emissão de Laudo PDF com Incerteza (U)', link: '/guias/emitir-certificado-web' },
            { text: 'Configuração do Broker MQTT e Nós', link: '/guias/configurar-broker-mqtt' }
          ]
        }
      ],
      '/referencia/': [
        {
          text: '3. Referência Técnica',
          collapsed: false,
          items: [
            { text: 'Pinagem e Interfaces de Hardware', link: '/referencia/pinagem-hardware' },
            { text: 'Payload LoRa Binário (27 bytes)', link: '/referencia/payload-lora-binario' },
            { text: 'Dicionário de Dados & ER (MySQL)', link: '/referencia/dicionario-dados-er' },
            { text: 'Tópicos MQTT e Payloads JSON', link: '/referencia/api-mqtt-telemetria' },
            { text: 'API REST OpenAPI / Swagger (Scramble) ↗', link: 'http://localhost:8000/docs/api' }
          ]
        }
      ],
      '/explicacoes/': [
        {
          text: '4. Explicações e Decisões de Arquitetura',
          collapsed: false,
          items: [
            { text: 'Arquitetura Distribuída em 5 Camadas', link: '/explicacoes/arquitetura-distribuida' },
            { text: 'Inferência TinyML vs ML Cloud (XGBoost)', link: '/explicacoes/tinyml-vs-cloud' },
            { text: 'DSP de Vibração, FFT e Janelamento Hann', link: '/explicacoes/dsp-vibracao-fft' },
            { text: 'Norma ISO 20816-3 e Zonas de Severidade', link: '/explicacoes/norma-iso-20816' },
            { text: 'ADR-001: LoRa 915MHz vs ESP-NOW', link: '/explicacoes/ADR-001-lora-vs-espnow' },
            { text: 'ADR-002: Deep-Sleep & Power-Gating', link: '/explicacoes/ADR-002-deep-sleep-power-gating' },
            { text: 'ADR-003: Alimentação Step-Up vs LDO', link: '/explicacoes/ADR-003-alimentacao-stepup-vs-ldo' }
          ]
        }
      ]
    },

    search: {
      provider: 'local',
      options: {
        locales: {
          root: {
            translations: {
              button: {
                buttonText: 'Buscar documentação',
                buttonAriaLabel: 'Buscar documentação'
              },
              modal: {
                noResultsText: 'Nenhum resultado encontrado para',
                resetButtonTitle: 'Limpar busca',
                footer: {
                  selectText: 'para selecionar',
                  navigateText: 'para navegar',
                  closeText: 'para fechar'
                }
              }
            }
          }
        }
      }
    },

    footer: {
      message: 'Sistema de Metrologia Lean Tech & Telemetria Industrial Amemiya.',
      copyright: 'Copyright © 2026 Lean Tech. Padrão ISO 17025 e ISO 20816-3.'
    }
  }
})
