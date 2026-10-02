# Sistema de Metrologia Lean Tech

Repositório do Sistema de Metrologia Lean Tech, uma aplicação web desenvolvida em Laravel e Filament para gestão de instrumentos de medição, calibrações e processos metrológicos, assegurando conformidade e rastreabilidade.

---

## 1. Objetivo

Este documento detalha o processo de configuração e execução do ambiente de desenvolvimento local da aplicação utilizando Docker. O objetivo é permitir que todos os membros da equipe, independentemente do nível técnico, possam executar e testar o sistema.

---

## 2. Pré-requisitos de Software

As seguintes ferramentas são necessárias para a configuração do ambiente de desenvolvimento:

1.  **Git:**
    * **Descrição:** Sistema de controle de versões distribuído, utilizado para clonar o repositório do projeto.
    * **Instalação:** Acessar [git-scm.com/downloads](https://git-scm.com/downloads) e seguir as instruções específicas para o seu sistema operacional (Windows, macOS ou Linux). Para Windows, baixe o instalador e siga as opções padrão ("Next").

2.  **Docker Desktop:**
    * **Descrição:** Plataforma para desenvolvimento, envio e execução de aplicações em containers. Abstrai as dependências de sistema (PHP, Nginx, MySQL, etc.), encapsulando-as em ambientes isolados.
    * **Instalação:** Acessar [www.docker.com/products/docker-desktop/](https://www.docker.com/products/docker-desktop/) e seguir as instruções de instalação para o seu sistema operacional. Após a instalação, garantir que o Docker Desktop esteja em execução (verificar o ícone da baleia na bandeja do sistema).

3.  **PhpStorm (Opcional - Recomendado para Desenvolvimento):**
    * **Descrição:** Ambiente de Desenvolvimento Integrado (IDE) robusto para PHP, facilitando a edição de código, depuração e integração com ferramentas de desenvolvimento, incluindo um terminal integrado que usaremos.
    * **Instalação:** Acessar [www.jetbrains.com/phpstorm/](https://www.jetbrains.com/phpstorm/).
    * **Licenciamento:** Licenças educacionais gratuitas estão disponíveis para estudantes através do [Programa Educacional da JetBrains](https://www.jetbrains.com/community/education/#students).

---

## 3. Configuração do Projeto (Passo a Passo com PhpStorm)

Os passos seguintes detalham o processo para iniciar a aplicação utilizando o PhpStorm e Docker.

1.  **Abrir o Projeto no PhpStorm:**
    * Abra o PhpStorm.
    * Na tela de boas-vindas, clique em "**Get from VCS**".
    * No campo "URL", cole a **URL do repositório Git** do projeto.
    * Escolha a pasta no seu computador onde deseja salvar o projeto (campo "Directory").
    * Clique em "**Clone**". O PhpStorm fará o download do código.

2.  **Criar Arquivo de Configuração (`.env`):**
    * No painel "Project" do PhpStorm (geralmente à esquerda), localize o arquivo chamado `.env.example`.
    * Clique com o botão direito sobre ele e escolha "**Copy**".
    * Clique com o botão direito na **raiz do projeto** (a pasta principal listada no painel "Project") e escolha "**Paste**".
    * Na janela que aparecer, nomeie o novo arquivo como `.env` (apenas ponto-env) e clique "OK".

3.  **Abrir o Terminal Integrado no PhpStorm:**
    * Na barra inferior do PhpStorm, clique na aba "**Terminal**". Isso abrirá um painel de linha de comando já no diretório correto do projeto.

4.  **Iniciar os Containers Docker:**
    * No Terminal do PhpStorm, **copie e cole** o seguinte comando e pressione Enter:
        ```bash
        docker-compose up -d --build
        ```
    * Aguarde a conclusão. Na primeira vez, pode levar alguns minutos. Quando o prompt do terminal reaparecer, o processo estará concluído.

5.  **Instalar Dependências PHP (via Terminal do PhpStorm):**
    * Copie e cole o seguinte comando e pressione Enter:
        ```bash
        docker-compose exec app composer install
        ```
    * Aguarde a instalação das bibliotecas.

6.  **Gerar Chave da Aplicação (via Terminal do PhpStorm):**
    * Copie e cole o seguinte comando e pressione Enter:
        ```bash
        docker-compose exec app php artisan key:generate
        ```

7.  **Preparar o Banco de Dados (via Terminal do PhpStorm):**
    * Execute as migrações globais e dos módulos:
        ```bash
        docker-compose exec app php artisan migrate
        docker-compose exec app php artisan module:migrate Metrology
        docker-compose exec app php artisan module:migrate IoT
        ```
    * Execute os seeders de dados iniciais:
        ```bash
        docker-compose exec app php artisan module:seed Metrology
        ```
      
8.  **Linkar Armazenamento (via Terminal do PhpStorm):**
    * Copie e cole o seguinte comando e pressione Enter:
        ```bash
        docker-compose exec app php artisan storage:link
        ```

**Configuração concluída!** A aplicação está instalada e pronta para ser acessada.

---

## 4. Criação do Usuário Administrador (Via Filament)

O acesso ao painel de administração requer um usuário.

1.  No **Terminal integrado do PhpStorm**, execute o comando:
    ```bash
    docker-compose exec app php artisan make:filament-user
    ```
2.  Siga as instruções no terminal, fornecendo:
    * `Name`: Seu nome completo.
    * `Email address`: Seu endereço de e-mail.
    * `Password`: Uma senha segura (não será visível durante a digitação). Confirme a senha quando solicitado.
3.  **Anote o e-mail e a senha que você criou.**

---

## 5. Acesso aos Serviços

Abra seu navegador web:

* **Aplicação Principal (Painel Filament):**
    * URL: [**http://localhost:8000**](http://localhost:8000)
    * Autenticação: Utilize o e-mail e senha criados no passo 4.

* **phpMyAdmin (Gerenciamento do Banco de Dados):**
    * URL: [**http://localhost:8080**](http://localhost:8080)
    * Servidor: `db`
    * Usuário: `root`
    * Senha: `rootpass`

* **MailHog (Visualizador de E-mails de Teste):**
    * URL: [**http://localhost:8025**](http://localhost:8025)

---

## 6. Arquitetura Modular e Estrutura do Projeto

Este projeto adota uma **arquitetura modular** (`nwidart/laravel-modules`). A funcionalidade está organizada em módulos independentes com baixo acoplamento:

* **`Modules/Metrology/`**: Módulo metrológico completo. Gestão de calibrações, instrumentos, padrões de referência RBC, cálculo de incerteza (GUM), cartas de controle de Shewhart (ILAC-G24), laudos de impacto e integridade forense criptográfica (ISO 17025 / FDA 21 CFR Part 11).
* **`Modules/IoT/`**: Monitoramento contínuo em tempo real. Ponte MQTT Mosquitto, telemetria de vibração e temperatura, classificação de severidade ISO 20816-3 e microserviço de inteligência artificial (XGBoost).
* **`Modules/System/`**: Multi-tenancy central, perfis e permissões via Filament Shield, auditoria de acessos e usuários.
* **`hardware/`**: Projetos de eletrônica e mecânica. Esquemáticos KiCad modulares, placas de circuito impresso (Main Node, Gateway LoRa, Torre de Sensores), arquivos de fabricação e modelos 3D CAD/STEP para montagem de bancadas industriais (NR-12).

---

## 7. Parar o Ambiente Docker

Quando terminar de usar a aplicação, você pode parar os containers.

* **Opção 1 (via Terminal do PhpStorm):**
    ```bash
    docker-compose down
    ```
* **Opção 2 (via Docker Desktop):**
    1.  Abra o Docker Desktop.
    2.  Vá à seção "Containers".
    3.  Localize o grupo de containers do projeto (terá o nome da pasta do projeto).
    4.  Clique no botão "Stop" associado ao grupo.

*(Para iniciar novamente, use `docker-compose up -d` no terminal ou o botão "Start" no Docker Desktop.)*

---

## 8. Documentação Adicional

Para mais detalhes técnicos sobre as tecnologias utilizadas:

* **Laravel:** [laravel.com/docs/](https://laravel.com/docs/)
* **Filament:** [filamentphp.com/docs/](https://filamentphp.com/docs/)
* **Docker:** [docs.docker.com/](https://docs.docker.com/)
* **Nwidart/Laravel-Modules:** [nwidart.com/laravel-modules/v11/introduction](https://nwidart.com/laravel-modules/v11/introduction)

---

## 9. Resolução de Problemas Comuns (Troubleshooting)

* **Erro "port is already allocated" no `docker-compose up`:** Outra aplicação está usando uma das portas (8000, 8080, 3307, etc.). Feche a aplicação conflitante ou pare outros projetos Docker. Tente `docker-compose down` e depois `docker-compose up -d --build`.
* **localhost:8000 não carrega ("Connection Refused"):** Verifique no Docker Desktop se os containers `amemiya-app` e `amemiya-nginx` estão com status "Running" (verde). Se não, tente iniciá-los. Verifique os logs no Docker Desktop (clique no container > Logs).
* **Comandos `docker-compose exec` falham:** Verifique se os containers estão realmente em execução (`docker ps` no terminal ou no Docker Desktop).

Para outros problemas, por favor, reporte à equipe de desenvolvimento.
