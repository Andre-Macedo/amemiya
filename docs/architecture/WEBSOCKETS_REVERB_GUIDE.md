# Guia de Configuração e Resolução de Problemas: WebSockets + Reverb + React + Sanctum

Este documento resume os principais desafios enfrentados e as soluções aplicadas durante a integração do dashboard de IoT em tempo real usando o ecosistema Laravel (Reverb, Echo, Sanctum) com Next.js no Docker.

## 1. Problema de Comunicação Docker & Reverb
**O Problema:** O frontend tentava conectar no Reverb (porta `8080`), mas o Nginx não estava roteando essa porta, e o Reverb não conseguia ouvir conexões externas adequadamente dentro da rede do Docker.
**A Solução:** 
- Criamos um serviço dedicado `reverb` no `docker-compose.yml` usando a mesma imagem da aplicação (`app`).
- O container do `reverb` executa o comando `php artisan reverb:start --host=0.0.0.0 --port=8080`.
- Mapeamos a porta diretamente no Docker Compose: `8080:8080`.
- No Frontend (`.env.local`), apontamos para `localhost:8080` e `wsHost: localhost`.

## 2. Autenticação e Sanctum (Loop de 401 Unauthorized)
**O Problema:** O uso de UUID/ULID como chave primária em vez de inteiros incrementais quebra o padrão do Laravel Sanctum. Quando o Sanctum tenta validar o token de um tenant ou usuário, a busca falhava silenciosamente e disparava loops infinitos de `401` no frontend, deslogando o usuário repetidamente.
**A Solução:**
- Adicionamos explicitamente a chamada `Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);` no `AppServiceProvider` para ensinar ao Laravel a usar nosso model customizado de token com `keyType = 'string'`.
- Garantimos o uso do `Relation::enforceMorphMap` para que o Laravel consiga resolver classes polimórficas (como `Tokenable`) corretamente com ULIDs no banco de dados.

## 3. Filas vs Broadcasting (Gráfico "Congelado")
**O Problema:** Ao disparar eventos no backend (ex: `SensorDataReceived`), nada chegava no frontend, apesar da conexão WebSocket (`Reverb`) estar estabelecida e ativa.
**O Motivo:** Eventos no Laravel que implementam a interface `ShouldBroadcast` são, por padrão, enfileirados. Como a variável `QUEUE_CONNECTION` estava definida como `database`, o evento ficava parado na tabela `jobs` aguardando ser processado.
**A Solução:**
- Alteramos o `.env` para usar o Redis (`QUEUE_CONNECTION=redis`).
- Adicionamos a extensão `php-redis` nativa na imagem Docker do PHP via PECL.
- Criamos um container `worker` rodando o processo contínuo `php artisan queue:work --tries=3` para esvaziar a fila em tempo real.

## 4. Frontend: Dessincronização de Tipos (TypeScript vs PHP)
**O Problema:** Erros do tipo `Cannot read properties of undefined (reading 'toFixed')` derrubavam a aplicação Next.js assim que o primeiro dado chegava.
**O Motivo:** O objeto DTO do PHP estava enviando o campo como `{ "piezo": { "rms": 0.5 }, "timestamp": "..." }`, enquanto o contrato Typescript esperava `{ "piezo_rms": 0.5, "measured_at": "..." }`.
**A Solução:**
- Atualizamos a interface `TelemetryData` no React.
- Adicionamos verificadores de segurança nas tags JSX: `typeof lastData?.rms_global === 'number' ? ... : ...`.
- No `Recharts`, usamos o fallback estético nas linhas: `isAnimationActive={false}` (para dados ao vivo fluírem suavemente) e `connectNulls={true}` garantindo que o gráfico desenhe linhas conectando todos os eventos recebidos.

## 5. Tenancy Midlewares e o "Super-Admin"
**O Problema:** Usuários globais (`super-admin`), que não possuem um `tenant_id` atrelado diretamente à sua entidade, não conseguiam autenticar ou acessar rotas de API, pois os middlewares `InitializeTenancyByHeader` bloqueavam o acesso.
**A Solução:**
- Modificamos os middlewares para identificar antecipadamente se o usuário é super-admin (via checagem de permissões Spatie ou ausência de escopo restrito) e dar passe-livre (bypass) na inicialização de Tenancy e Verificação de Assinatura.
- Padronizamos as comunicações de broadcast para não usarem UUID de tenant no nome do canal, mas sim o Slug legível: `tenant.slug.iot`. Isso facilita testes no `simulate_iot.php` e a leitura de contexto no `localStorage` do Frontend.