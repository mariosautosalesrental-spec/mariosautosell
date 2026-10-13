# Mario's Auto Sales & Rentals — Project Phases

<!-- inputs: project-description.md@sha256:8c1c79fdea95 user-stories.md@sha256:8a1a2a8530b3 database-schema.md@sha256:05d70d23c9bc -->

## Overview

A construção do app Laravel (pasta `/home/eliel/workspace/projetos/marios-auto`) segue a ordem fundação primeiro, fluxos depois: **Phase 1** prepara o projeto e as ferramentas; **Phase 2** cria todo o banco (migrations e seeders); **Phase 3** entrega todos os models já com relacionamentos completos e as regras de negócio do carro; **Phase 4** monta a base visual do site e do admin. Só então entram os fluxos: site público (**Phases 5–7**), admin de login, Store info e categorias (**Phase 8**), carros e fotos (**Phase 9**), lista e vendas (**Phase 10**), dashboard (**Phase 11**) e fechamento em produção (**Phase 12**). São 12 fases e 111 tarefas, cada fase pensada para uma sessão de agente; as fases são referenciadas pelo número.

**Linha de corte (primeira entrega):** o site no ar e o Mario operando sozinho exigem as Phases 1–10 e a Phase 12. A Phase 11 (dashboard) pode entrar logo depois sem bloquear o lançamento.

**Referências de design:** os mockups HTML aprovados ficam em `.spec/init/design/` e cada tarefa de tela aponta para eles, com o seletor da seção. O dashboard e o estado `Sold` não existem nos mockups; seguem os tokens do mockup do admin.

**Conventions:**
- `[ ]` pending · `[x]` done in the codebase (nenhuma tarefa está feita: o app Laravel ainda não existe).
- Phases and sub-phases are numbered (`Phase 1`, `Phase 5.3`) for reference by AI agents.
- Business-logic tasks list the **feature tests** to generate (PHPUnit, `php artisan test`); frontend-only tasks list validatable acceptance criteria and a **Design ref**.

---

## Phase 1: Bootstrap e ferramentas do projeto

**Goal:** Criar o app Laravel na pasta nova, com pacotes, ambiente, painel Filament vazio, tokens de design e base de testes. · **Depends on:** nenhuma · **Covers:** Tech Stack, Workflow 8 (ambiente), identidade visual

### Phase 1.1: Projeto, pacotes e configuração

- [x] **Task:** Criar o projeto Laravel em `/home/eliel/workspace/projetos/marios-auto` (Laravel 13, PHP 8.3 ou superior) e iniciar o git
  - **Acceptance criteria:**
    - `php artisan --version` mostra Laravel 13 e `php -v` mostra 8.3 ou superior
    - `php artisan serve` abre a página inicial padrão sem erro
    - Repositório git inicializado com `.gitignore` do Laravel e primeiro commit
  - **Traces:** Tech Stack

- [x] **Task:** Instalar os pacotes PHP da stack
  - **Acceptance criteria:**
    - `composer.json` lista `livewire/livewire` (v4, vem com o Filament), `filament/filament` ^5, `intervention/image` ^4 (conversões de foto) e `league/flysystem-aws-s3-v3`
    - `php artisan about` roda sem erro com os pacotes instalados
    - Versões ficam travadas no `composer.lock`
  - **Traces:** Tech Stack; US-3.4

- [x] **Task:** Configurar front-end: Vite + Tailwind CSS 4 com os tokens de cor e fontes do mockup
  - **Acceptance criteria:**
    - `tailwind.config.js` define `accent #B3121F`, `whatsapp #1A7F45`, `ink #18161A`, `muted #5E5A55`, `bg #F7F6F4`, `surface #FFFFFF`, `line #E6E2DD`, `chip #EFEDEA`, `dark #141216`
    - Fontes Barlow Condensed (títulos, 700–800, caixa alta), Barlow (texto, 400–700) e Yellowtail (script) carregadas por Google Fonts
    - `npm run dev` e `npm run build` terminam sem erro
    - Sem classes ou configuração de modo escuro (tema claro fixo)
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → `:root` (variáveis de cor e fontes)
  - **Traces:** Tech Stack; identidade visual; US-6.5

- [x] **Task:** Configurar ambiente e `config/marios.php`
  - **Acceptance criteria:**
    - `.env.example` traz `APP_NAME`, `APP_URL`, `APP_LOCALE=en`, `APP_TIMEZONE=America/New_York`, `DB_*`, `FILESYSTEM_DISK`, `AWS_*` (incluindo `AWS_ENDPOINT` e `AWS_URL`) e `CARS_PER_PAGE=12`
    - `config/marios.php` expõe `cars_per_page` lendo `CARS_PER_PAGE` com padrão 12
    - `QUEUE_CONNECTION=sync` (o projeto não usa filas); telefone, WhatsApp, endereço e horários **não** estão no `.env`
  - **Feature tests:**
    - `config_cars_per_page_defaults_to_12` → sem a variável, `config('marios.cars_per_page')` vale 12
  - **Traces:** Tech Stack; Workflow 8; US-1.4

- [x] **Task:** Configurar discos de arquivo: público local em desenvolvimento e `s3` (R2) em produção
  - **Acceptance criteria:**
    - `php artisan storage:link` funciona e o disco `public` serve arquivos em desenvolvimento
    - O disco `s3` aceita `AWS_ENDPOINT` e `AWS_URL` (Cloudflare R2) por variável de ambiente
    - Uma variável escolhe o disco usado pelas fotos de carros, sem mudar código
  - **Traces:** Tech Stack; US-3.4

- [x] **Task:** Configurar a base de testes (PHPUnit)
  - **Acceptance criteria:**
    - `phpunit.xml` usa SQLite em memória e `APP_TIMEZONE=America/New_York`
    - Um teste de exemplo com `RefreshDatabase` passa em `php artisan test`
    - Os testes rodam sem acesso à rede
  - **Feature tests:**
    - `ExampleTest` → a home responde sem erro e o runner está verde
  - **Traces:** Tech Stack (Testes)

### Phase 1.2: Painel Filament, ativos e documentação

- [x] **Task:** Criar o painel Filament em `/admin` com login e marca da loja
  - **Acceptance criteria:**
    - Provider do painel com `path('admin')`, login habilitado, idioma `en` e fonte Barlow
    - Cor primária do painel `#B3121F`; visitante não autenticado é redirecionado para a tela de login
    - `php artisan make:filament-user` cria um usuário que entra no painel
  - **Design ref:** `.spec/init/design/Mario's Admin.html` → `#loginForm`, `.rail`
  - **Traces:** Tech Stack; US-3.1

- [x] **Task:** Extrair dos mockups os ativos visuais para o app
  - **Acceptance criteria:**
    - O logo (PNG com fundo transparente) é salvo em `public/images/logo.png` e a foto do pátio em `public/images/hero.jpg`
    - As fotos de exemplo dos carros são salvas em `database/seeders/demo-photos/` e usadas só em ambiente local (não vão para produção)
    - Um comando ou script documentado reproduz a extração a partir dos HTML dos mockups
    - O logo nunca é esticado (`width: auto` com altura fixa)
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → imagens embutidas (data URIs): logo, hero, fotos de carros
  - **Traces:** Identidade visual; US-1.1

- [x] **Task:** Copiar `.spec/` para o projeto e escrever o `README.md` do app
  - **Acceptance criteria:**
    - `.spec/init/*.md` presente no projeto novo
    - `README.md` documenta instalação local (`composer install`, `npm install`, `.env`, `key:generate`, `migrate --seed`, `make:filament-user`, `storage:link`, `npm run dev`, `php artisan serve`) e como rodar `php artisan test`
    - Site em `http://localhost:8000` e admin em `http://localhost:8000/admin` descritos
  - **Traces:** Workflow 8; Tech Stack

---

## Phase 2: Fundação do banco de dados

**Goal:** Criar todas as migrations e os seeders das tabelas de apoio e das configurações da loja. · **Depends on:** Phase 1 · **Covers:** Tabelas: body_types, transmissions, drivetrains, categories, users, cars, car_photos, settings

### Phase 2.1: Migrations

- [ ] **Task:** Migrations das tabelas de apoio `body_types`, `transmissions` e `drivetrains`
  - **Acceptance criteria:**
    - Cada tabela tem `id`, `name` (único), `slug` (único), `sort_order` (inteiro, padrão 0) e timestamps
    - Nenhuma coluna usa enum
  - **Feature tests:**
    - `lookup_tables_have_unique_name_and_slug` → inserir duplicado de `name` ou `slug` lança exceção
  - **Traces:** Tabelas body_types, transmissions, drivetrains

- [ ] **Task:** Migration da tabela `categories`
  - **Acceptance criteria:**
    - Colunas `id`, `name` (único, obrigatório), `sort_order` (padrão 0) e timestamps
  - **Traces:** Tabela categories; US-4.1

- [ ] **Task:** Migration da tabela `users` e das tabelas do framework
  - **Acceptance criteria:**
    - `users` tem `name`, `email` (único), `password`, `remember_token` e timestamps
    - As tabelas padrão `password_reset_tokens`, `sessions` e `cache` existem; não há tabelas `jobs` nem `failed_jobs`
  - **Traces:** Tabela users; US-3.1

- [ ] **Task:** Migration da tabela `settings`
  - **Acceptance criteria:**
    - Colunas `whatsapp`, `phone`, `street`, `city`, `state`, `zip`, `hours_mon_fri`, `hours_sat`, `hours_sun` (todas obrigatórias) e timestamps
  - **Traces:** Tabela settings; US-5.1

- [ ] **Task:** Migration da tabela `cars`
  - **Acceptance criteria:**
    - FKs `category_id`, `body_type_id`, `transmission_id`, `drivetrain_id`, todas `restrict on delete`
    - Colunas `title`, `trim` (nulo), `slug` (único), `price` e `mileage` (inteiros sem sinal, nulos), `color`, `engine`, `description` (nulos), `is_active` (padrão `true`), `price_drop` (padrão `false`), `sold_at` e `sold_price` (nulos)
    - Índices em `(is_active, category_id)`, `(is_active, created_at)` e `sold_at`
  - **Feature tests:**
    - `cars_defaults_is_active_true_and_price_drop_false` → inserção mínima grava os padrões
    - `category_with_cars_cannot_be_deleted_at_db_level` → apagar categoria com carro lança exceção
    - `cars_slug_must_be_unique` → duas inserções com o mesmo `slug` falham
  - **Traces:** Tabela cars; US-3.3; US-3.6; US-8.1

- [ ] **Task:** Migration da tabela `car_photos`
  - **Acceptance criteria:**
    - Colunas `car_id` (FK com `cascade on delete`), `file_path`, `sort_order` (padrão 0) e timestamps
    - Índice em `(car_id, sort_order)`
  - **Feature tests:**
    - `deleting_car_removes_its_photo_rows` → apagar o carro apaga as linhas de `car_photos`
  - **Traces:** Tabela car_photos; US-3.4

### Phase 2.2: Seeders

- [ ] **Task:** Seeders das tabelas de apoio
  - **Acceptance criteria:**
    - body_types: SUV, Car, Truck; transmissions: Automatic, Manual; drivetrains: FWD, RWD, AWD, 4WD, cada um com `slug` e `sort_order`
    - Rodar duas vezes não duplica linhas (idempotente)
  - **Feature tests:**
    - `lookup_seeders_create_expected_rows` → conteúdo exato e ordem
    - `lookup_seeders_are_idempotent` → segunda execução mantém as mesmas contagens
  - **Traces:** Seeds de apoio; US-3.3

- [ ] **Task:** `SettingSeeder` com os dados da loja
  - **Acceptance criteria:**
    - Cria exatamente uma linha: whatsapp `19784174036`, phone `(978) 417-4036`, street `1336 Merrimack Ave`, city `Dracut`, state `MA`, zip `01826`, hours_mon_fri `9:00 AM – 6:00 PM`, hours_sat `9:00 AM – 4:00 PM`, hours_sun `Closed`
    - Rodar de novo não cria segunda linha nem sobrescreve valores já editados no admin
  - **Feature tests:**
    - `setting_seeder_keeps_single_row` → duas execuções resultam em uma linha
    - `setting_seeder_does_not_overwrite_edited_values` → valor alterado permanece
  - **Traces:** Seeds; US-5.1

- [ ] **Task:** Ligar o `DatabaseSeeder`
  - **Acceptance criteria:**
    - Ambientes de produção executam apenas as tabelas de apoio e o `SettingSeeder`; nenhuma categoria é pré-cadastrada (o Mario as cria no admin)
    - `php artisan migrate --seed` roda do zero sem erro em MySQL e em SQLite
  - **Feature tests:**
    - `database_seeder_runs_without_demo_data_in_production` → com `APP_ENV=production` não existe nenhum carro
  - **Traces:** Workflow 8; US-7.1

---

## Phase 3: Models, relacionamentos e regras de negócio

**Goal:** Criar todos os models com relacionamentos completos e as regras do carro (publicação, garantia, venda, WhatsApp, slug). · **Depends on:** Phase 2 · **Covers:** Tabelas todas; US-2.4, US-2.5, US-3.6, US-4.3, US-5.2, US-8.1, US-8.2

### Phase 3.1: Models e relacionamentos

- [ ] **Task:** Models `BodyType`, `Transmission` e `Drivetrain`
  - **Acceptance criteria:**
    - Cada um tem `hasMany(Car)`, `$fillable` e escopo `ordered` por `sort_order`
  - **Traces:** Tabelas body_types, transmissions, drivetrains

- [ ] **Task:** Model `Category`
  - **Acceptance criteria:**
    - `hasMany(Car)`; escopo `ordered`; escopo `withActiveCars()` que traz só categorias com pelo menos um carro ativo e a contagem `active_cars_count`
    - Excluir categoria que tem carros (ativos, ocultos ou vendidos) lança exceção de domínio com mensagem clara
  - **Feature tests:**
    - `with_active_cars_counts_only_active_cars` → ocultos e vendidos não entram na contagem
    - `category_without_active_cars_is_excluded_from_public_scope` → some do escopo
    - `category_with_cars_cannot_be_deleted` → qualquer carro, inclusive vendido, bloqueia a exclusão
  - **Traces:** Tabela categories; US-1.3; US-4.3

- [ ] **Task:** Model `Car` com relacionamentos e casts
  - **Acceptance criteria:**
    - `belongsTo` Category, BodyType, Transmission e Drivetrain; `hasMany(CarPhoto)` ordenado por `sort_order`
    - Casts: `is_active` e `price_drop` booleanos, `price`, `mileage` e `sold_price` inteiros, `sold_at` data/hora
    - `$fillable` definido (sem `guarded = []`)
  - **Traces:** Tabela cars; Key Concepts (Car)

- [ ] **Task:** Model `CarPhoto` com URLs
  - **Acceptance criteria:**
    - `belongsTo(Car)`; métodos `url()`, `thumbUrl()` e `largeUrl()` leem o disco configurado
    - `thumbUrl()` e `largeUrl()` caem para a imagem original enquanto a conversão não existe
  - **Feature tests:**
    - `photo_urls_fall_back_to_original_when_conversion_missing` → `thumbUrl()` devolve a URL original
  - **Traces:** Tabela car_photos; US-3.4; US-7.2

- [ ] **Task:** Model `Setting` com cache
  - **Acceptance criteria:**
    - `Setting::current()` devolve a única linha usando `Cache::rememberForever('settings')`
    - Salvar ou apagar limpa o cache, e o valor novo aparece na chamada seguinte
    - Se não houver linha, cria uma com os padrões do seeder
  - **Feature tests:**
    - `setting_current_is_cached` → segunda chamada não consulta o banco
    - `saving_setting_clears_cache` → valor novo é lido depois do save
  - **Traces:** Tabela settings; US-5.1; US-5.2

- [ ] **Task:** Model `User` pronto para o Filament
  - **Acceptance criteria:**
    - Implementa `FilamentUser` e `canAccessPanel` retorna `true` para qualquer usuário (sem níveis de permissão na v1)
    - `password` com cast `hashed`
  - **Feature tests:**
    - `any_user_can_access_admin_panel` → `canAccessPanel` verdadeiro
  - **Traces:** Tabela users; US-3.1

- [ ] **Task:** Factories de todos os models
  - **Acceptance criteria:**
    - `CarFactory` com estados `inactive()`, `sold()`, `priceDrop()` e `withPhotos(n)`
    - Factories para BodyType, Transmission, Drivetrain, Category, CarPhoto, Setting e User
    - Dados gerados respeitam as regras (carro ativo tem preço e ao menos uma foto)
  - **Traces:** Todas as tabelas (suporte a testes)

### Phase 3.2: Regras de negócio do carro

- [ ] **Task:** Escopos de situação do carro
  - **Acceptance criteria:**
    - `active()` = `is_active` verdadeiro e `sold_at` nulo; `sold()` = `sold_at` preenchido; `hidden()` = inativo e não vendido; `latestFirst()` ordena por `created_at` decrescente
    - Atributo `status` devolve `on_site`, `hidden` ou `sold`
  - **Feature tests:**
    - `active_scope_excludes_hidden_and_sold` → só carros no site
    - `status_attribute_is_derived_from_flags` → três estados corretos
    - `latest_first_orders_by_created_at_desc` → ordem decrescente
  - **Traces:** US-1.2; US-8.3; Key Concepts (Publicação, Sold)

- [ ] **Task:** Geração de `slug`
  - **Acceptance criteria:**
    - O slug vem de título + versão + id (ex.: `2019-jeep-compass-limited-4x4-12`) e é gravado após o primeiro save
    - Muda quando título ou versão mudam e continua único
    - Sem versão, não deixa hífens duplicados
  - **Feature tests:**
    - `slug_is_built_from_title_trim_and_id` → formato esperado
    - `slug_without_trim_has_no_double_dash` → sem `--`
    - `slug_updates_when_title_changes` → novo slug, único
  - **Traces:** Tabela cars; US-2.3

- [ ] **Task:** `Car::maWarranty()` — garantia de usados de Massachusetts
  - **Acceptance criteria:**
    - Menos de 40.000 mi → `90 days or 3,750 miles`; 40.000–79.999 → `60 days or 2,500 miles`; 80.000–124.999 → `30 days or 1,250 miles`; 125.000 ou mais → `null`
    - Milhagem nula → `null`
  - **Feature tests:**
    - `warranty_at_39999_and_40000` → 90 dias e 60 dias
    - `warranty_at_79999_and_80000` → 60 dias e 30 dias
    - `warranty_at_124999_and_125000` → 30 dias e `null`
    - `warranty_is_null_without_mileage` → `null`
  - **Traces:** US-2.4; Key Concepts (Garantia)

- [ ] **Task:** Links de WhatsApp (`Car::whatsappUrl()` e trade-in)
  - **Acceptance criteria:**
    - Carro: `https://wa.me/<whatsapp>?text=` com `Hi Mario, is the {title} {trim} still available?` codificada em URL
    - Sem `trim`, a mensagem não tem espaço duplo nem `null`
    - Trade-in: `Hi Mario, I'd like an offer on my car.`
    - O número vem sempre de `Setting::current()->whatsapp`
  - **Feature tests:**
    - `car_whatsapp_url_contains_encoded_message` → texto e número corretos
    - `car_whatsapp_url_without_trim_is_clean` → sem espaço duplo
    - `whatsapp_url_uses_updated_setting_number` → mudar o número muda o link
    - `trade_in_whatsapp_url_has_expected_message` → texto do trade-in
  - **Traces:** US-2.5; US-2.6; Key Concepts (Mensagem de WhatsApp)

- [ ] **Task:** Marcar como vendido e reabrir (`markAsSold()` e `reopen()`)
  - **Acceptance criteria:**
    - `markAsSold($soldAt = hoje, $soldPrice = price)` grava `sold_at`, `sold_price` e `is_active = false` na mesma transação
    - Data futura e valor menor que 1 são rejeitados com erro de validação
    - Chamar de novo em carro já vendido não altera nada (idempotente)
    - `reopen()` apaga `sold_at` e `sold_price`, mantém `is_active = false` e só vale para carro vendido
  - **Feature tests:**
    - `mark_as_sold_sets_fields_and_deactivates` → três campos gravados
    - `mark_as_sold_defaults_price_to_listed_price` → `sold_price` = `price`
    - `mark_as_sold_rejects_future_date` → erro
    - `mark_as_sold_rejects_zero_price` → erro
    - `mark_as_sold_is_idempotent` → segunda chamada não muda data
    - `reopen_clears_sale_and_keeps_car_hidden` → campos nulos e inativo
    - `reopen_rejects_car_not_sold` → erro
  - **Traces:** US-8.1; US-8.2; Key Concepts (Sold)

- [ ] **Task:** Regra de publicação (pode ficar ativo?)
  - **Acceptance criteria:**
    - Carro só pode ser ativo com preço e pelo menos uma foto
    - Carro vendido nunca pode ser ativo (exige reabrir antes)
    - A regra fica em uma classe reutilizável (ex.: `CanBeActive`) usada pelo formulário e pelo interruptor da lista, com mensagens em inglês
  - **Feature tests:**
    - `cannot_activate_without_price` → falha com mensagem
    - `cannot_activate_without_photo` → falha com mensagem
    - `cannot_activate_sold_car` → falha pedindo para reabrir
    - `can_activate_with_price_and_photo` → passa
    - `draft_without_price_can_be_saved_inactive` → inativo sem preço é aceito
  - **Traces:** US-3.3; US-3.6

- [ ] **Task:** `DemoCarSeeder` (somente ambiente local)
  - **Acceptance criteria:**
    - Cria as marcas de exemplo (Chevrolet, Fiat, Jeep e Volkswagen) e carros de exemplo com fotos de `database/seeders/demo-photos/` nessas categorias, incluindo um com `price_drop`, um oculto e um vendido
    - Só roda quando `APP_ENV=local`
    - Idempotente: não duplica carros
  - **Feature tests:**
    - `demo_seeder_only_runs_locally` → em `production` não cria carros
    - `demo_seeder_creates_active_hidden_and_sold_cars` → três situações presentes
  - **Traces:** Seeds; Workflow 1

---

## Phase 4: Fundação do front-end do site público

**Goal:** Criar layout, componentes reutilizáveis, card de carro e tema do admin conforme os mockups. · **Depends on:** Phase 1, Phase 3 · **Covers:** US-6.4, US-6.5, US-2.1, design do site e do admin

### Phase 4.1: Layout e componentes do site

- [ ] **Task:** Layout base `layouts/app.blade.php`
  - **Acceptance criteria:**
    - `<html lang="en">`, viewport, fontes, `@vite`, link `Skip to content` e tema claro fixo
    - Estrutura `header`, `main id="main"` e `footer`
    - Mobile-first, sem rolagem horizontal
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → estrutura geral do `<body>`
  - **Traces:** US-6.4; US-6.5

- [ ] **Task:** Componente `header`
  - **Acceptance criteria:**
    - Fixo no topo; logo à esquerda (56px no desktop, 42px no celular, `width: auto`)
    - À direita endereço, horário e telefone com ícones, lidos de `Setting::current()`
    - No celular mostra só logo + telefone; faixa vermelha de 3px embaixo
    - Sem menu de navegação e sem botão de WhatsApp no header
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → header / `#top`
  - **Traces:** US-1.1; US-5.2

- [ ] **Task:** Componente `footer`
  - **Acceptance criteria:**
    - Logo, aviso legal de preço, links `Privacy Policy`, `Terms of Use` e `Accessibility` e copyright
    - Aviso de que o preço inclui taxas da loja e exclui impostos, título e registro
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → `#legal` / rodapé
  - **Traces:** US-2.7; US-6.1

- [ ] **Task:** Componentes de UI reutilizáveis e ícones
  - **Acceptance criteria:**
    - Botões (vermelho, verde de WhatsApp, secundário), chips, selo `Price drop` e títulos de seção
    - Ícones SVG (telefone, pino, relógio, WhatsApp, busca, fechar, setas); decorativos com `aria-hidden`, de ação com `aria-label`
    - Alvos de toque de pelo menos 44px
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → botões, chips e ícones
  - **Traces:** US-6.4

- [ ] **Task:** Componente `car-card`
  - **Acceptance criteria:**
    - Foto de capa (a primeira por `sort_order`), selo `Price drop` só quando `price_drop`, nome, versão, chips de milhas, câmbio e tração
    - Preço em USD sem centavos com `incl. dealer fees` e botão verde `Ask about this car`
    - `View details` sobre a foto: no hover no desktop, sempre visível no celular
    - Hover: sobe 4px com sombra, foto com zoom de 6%, título vermelho
    - Carro sem foto mostra placeholder; campos nulos não deixam espaços vazios
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → `#grid` (card do carro)
  - **Traces:** US-2.1

- [ ] **Task:** Acessibilidade e movimento globais
  - **Acceptance criteria:**
    - Anel de foco visível em todos os controles; `prefers-reduced-motion` desliga transições, zoom e deslocamentos
    - Contraste de texto de pelo menos 4.5:1 com os tokens de cor
    - Só `<button>` e `<a>` reais como controles
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → regras de foco e movimento
  - **Traces:** US-6.4; US-6.5

- [ ] **Task:** Formatadores de exibição
  - **Acceptance criteria:**
    - Preço `$1,250`, milhas `12,345 mi` e valores nulos tratados
    - Disponíveis como helpers ou métodos de view usados por site e admin
  - **Feature tests:**
    - `price_formatter_has_no_decimals_and_thousands_separator` → `1250` vira `$1,250`
    - `mileage_formatter_handles_null` → vazio sem erro
  - **Traces:** US-2.1; US-8.4

### Phase 4.2: Tema do admin

- [ ] **Task:** Tema do Filament com a identidade do mockup
  - **Acceptance criteria:**
    - Barra lateral escura (`#18161A`), vermelho de destaque, fontes Barlow e cards claros
    - No celular, navegação em barra inferior com Cars, Categories e Store info
    - Tema compila com `npm run build` e é aplicado em `/admin`
  - **Design ref:** `.spec/init/design/Mario's Admin.html` → `.rail`, `.tabbar`, `.card.panel`
  - **Traces:** US-3.1; Tech Stack

---

## Phase 5: Site público — home, busca e estoque

**Goal:** Montar a home com hero, busca, marcas, grade de carros e paginação incremental. · **Depends on:** Phase 4 · **Covers:** US-1.1 a US-1.6, US-5.2

### Phase 5.1: Home e hero

- [ ] **Task:** Rota `/` e página `home.blade.php`
  - **Acceptance criteria:**
    - `GET /` responde 200 e compõe header, hero, busca, marcas, estoque, confiança, trade-in, visita e rodapé nessa ordem
    - `<title>` e meta description definidos
    - Os dados de contato vêm de `Setting::current()`, sem valores fixos nas views
  - **Feature tests:**
    - `home_renders_store_info_from_settings` → telefone, horário e endereço do banco aparecem
    - `home_has_no_hardcoded_contact_data` → alterar a linha de Setting muda todos os textos
  - **Traces:** US-1.1; US-5.2; Workflow 1

- [ ] **Task:** Seção hero
  - **Acceptance criteria:**
    - Foto do pátio de fundo com sombra escura; etiqueta `Used cars · Dracut, MA`, título `Quality used cars. Prices that make sense.`, subtítulo `A local, family-run lot where you get a good car and a straight answer.`
    - Botão `Browse our cars` rola até o estoque (`#inventory`)
    - No celular, foto em cima e texto embaixo
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → `#hero`, `#hCta`, `#hImg`
  - **Traces:** US-1.1

- [ ] **Task:** Propagação de Store info para o site
  - **Acceptance criteria:**
    - Após alterar o `Setting`, a próxima requisição mostra os novos valores em header, `Visit the lot`, rodapé, mapa e botões de WhatsApp
    - Nenhum telefone, horário ou endereço fica fixo no código ou no `.env`
  - **Feature tests:**
    - `updated_setting_appears_everywhere_on_next_request` → header, visita, rodapé e link de WhatsApp refletem o novo valor
  - **Traces:** US-5.2

### Phase 5.2: Estoque com Livewire

- [ ] **Task:** Componente Livewire `Inventory` com filtros
  - **Acceptance criteria:**
    - Propriedades `make`, `body` e `max` aplicadas sobre `Car::active()` com eager loading
    - `make` casa com `Str::slug(category.name)`; `body` com o `slug` de `body_types`; `max` é inclusivo
    - Filtros combinam; valores inválidos são ignorados
    - Ordem `created_at` decrescente
  - **Feature tests:**
    - `filters_by_make_body_and_max_price_combined` → só carros que atendem aos três
    - `max_price_is_inclusive` → preço igual ao máximo entra
    - `only_active_cars_are_listed` → ocultos e vendidos nunca aparecem
    - `invalid_filter_values_are_ignored` → sem erro e sem filtro
    - `orders_by_created_at_desc` → mais recentes primeiro
  - **Traces:** US-1.2; Workflow 1

- [ ] **Task:** Paginação incremental `Show more cars`
  - **Acceptance criteria:**
    - Mostra 12 carros (`CARS_PER_PAGE`); `Show more cars` soma mais 12 sem recarregar
    - Texto `Showing X of Y cars` reflete o total filtrado
    - O botão some quando todos estão visíveis; mudar qualquer filtro volta aos 12 primeiros
  - **Feature tests:**
    - `shows_12_then_24_after_show_more` → contagens 12 e 24
    - `counter_matches_filtered_total` → `Showing X of Y`
    - `show_more_button_hidden_at_end` → ausente com todos visíveis
    - `changing_filter_resets_to_first_12` → volta a 12
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → `#more`, `#moreBtn`, `#moreText`, `#count`
  - **Traces:** US-1.4; Key Concepts (Paginação incremental)

- [ ] **Task:** Barra de busca (desktop e celular)
  - **Acceptance criteria:**
    - Desktop: Make, Body type (`All types`, SUV, Car, Truck), Max price (`No max`, `$10,000`, `$15,000`, `$20,000`, …) e botão `Search`
    - Celular: só Body type e Max price, lado a lado, aplicando o filtro ao mudar
    - Controles acessíveis por teclado e com rótulos
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → `#searchForm`, `#fMake`, `#fBody`, `#fPrice`, `#searchBtn`
  - **Traces:** US-1.2

- [ ] **Task:** Botões `Shop by make`
  - **Acceptance criteria:**
    - Primeiro `All`, depois uma categoria por botão na ordem de `sort_order`, com a contagem de carros ativos
    - Selecionada fica vermelha; categoria sem carro ativo não aparece
    - No celular, uma linha com rolagem horizontal; hover sobe 2px com borda e texto vermelhos
  - **Feature tests:**
    - `make_buttons_follow_sort_order` → ordem do admin
    - `make_counts_only_active_cars` → vendido e oculto não contam
    - `category_without_active_cars_is_hidden` → botão ausente
    - `selecting_make_filters_grid` → grade reflete a marca
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → `#makes`, `#makesH`
  - **Traces:** US-1.3

- [ ] **Task:** Grade de carros e estado vazio
  - **Acceptance criteria:**
    - Grade de `car-card` com 1 coluna no celular e mais colunas em telas maiores
    - Sem resultado, mostra mensagem em inglês e link `wa.me` com o número de Store info
    - Alterar ou limpar o filtro traz os carros de volta
  - **Feature tests:**
    - `empty_state_shows_whatsapp_link` → link usa o número de Setting
    - `clearing_filters_restores_cars` → carros voltam
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → `#grid`, estado vazio
  - **Traces:** US-1.5

- [ ] **Task:** Sincronização com a query string
  - **Acceptance criteria:**
    - A URL aceita `?make=jeep&body=suv&max=15000&page=2` e aplica os filtros; `page=N` mostra os primeiros `N × 12` carros
    - Mudar filtros atualiza a URL sem recarregar a página
    - Valores inválidos na URL são ignorados e a página abre sem filtro
  - **Feature tests:**
    - `query_string_applies_filters` → resultado igual ao filtro manual
    - `page_param_shows_n_times_12_cars` → `page=2` mostra 24
    - `invalid_query_values_do_not_error` → 200 e sem filtro
  - **Traces:** US-1.6

- [ ] **Task:** Desempenho da listagem
  - **Acceptance criteria:**
    - Carros, fotos e categoria carregados com eager loading, sem consulta por card
    - Contagem de consultas da home constante em relação ao número de carros
  - **Feature tests:**
    - `home_query_count_does_not_grow_with_cars` → a mesma quantidade de consultas com 5 e com 30 carros
  - **Traces:** US-1.4

---

## Phase 6: Site público — detalhe do carro e contato

**Goal:** Detalhe em modal e em página própria, garantia de MA, WhatsApp, trade-in, confiança e mapa. · **Depends on:** Phase 5 · **Covers:** US-2.1 a US-2.7

### Phase 6.1: Detalhe do carro

- [ ] **Task:** Componente Livewire `CarDetail` (modal)
  - **Acceptance criteria:**
    - Abre ao clicar em `View details` sem recarregar; mostra galeria, nome, versão, milhas, preço e `Includes dealer fees. Plus tax, title & registration.`
    - Specs: motor (opcional), câmbio, tração, carroceria e cor, e descrição
    - Campos opcionais vazios não aparecem; `WhatsApp about this car` fixo no rodapé do modal
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → `#detail`, `#dTitle`, `#dMeta`, `#dPrice`, `#dSpecs`, `#dWa`
  - **Traces:** US-2.2

- [ ] **Task:** Acessibilidade do modal
  - **Acceptance criteria:**
    - Foco preso dentro do modal; `Esc` fecha; ao fechar, o foco volta ao card de origem
    - Botão de fechar com `aria-label` e papel `dialog` com título associado
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → `#detail`, `#dClose`
  - **Traces:** US-2.2; US-6.4

- [ ] **Task:** Página `/cars/{slug}`
  - **Acceptance criteria:**
    - Mostra o mesmo conteúdo do detalhe para carro ativo
    - Carro inexistente, oculto ou vendido retorna 404
    - `<title>` é `{title} {trim} for sale in Dracut, MA`; pela home abre modal e pela URL direta abre a página
  - **Feature tests:**
    - `car_page_renders_active_car` → 200 e dados do carro
    - `car_page_returns_404_for_hidden_car` → 404
    - `car_page_returns_404_for_sold_car` → 404
    - `car_page_returns_404_for_unknown_slug` → 404
    - `car_page_title_has_expected_format` → título exato
  - **Traces:** US-2.3; Workflow 2

- [ ] **Task:** Faixa de garantia de Massachusetts no detalhe
  - **Acceptance criteria:**
    - Exibe a faixa conforme `Car::maWarranty()` no modal e na página
    - Sem milhagem ou com 125.000 mi ou mais, a faixa não aparece
  - **Feature tests:**
    - `detail_shows_90_day_warranty_under_40000` → texto presente
    - `detail_hides_warranty_at_125000` → ausente
    - `detail_hides_warranty_without_mileage` → ausente
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → `#dWarranty`
  - **Traces:** US-2.4

- [ ] **Task:** Galeria de fotos
  - **Acceptance criteria:**
    - Miniaturas navegáveis por clique e teclado; usa `largeUrl()` na foto grande e `thumbUrl()` nas miniaturas
    - `alt` descritivo (`{color} {title}`)
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → `#dImg`
  - **Traces:** US-2.2; US-6.4

- [ ] **Task:** Links de WhatsApp no card, no modal e na página
  - **Acceptance criteria:**
    - Todos usam `Car::whatsappUrl()` e abrem `wa.me` com a mensagem pronta
    - Alterar o número em Store info muda todos os links
  - **Feature tests:**
    - `card_modal_and_page_use_same_whatsapp_link` → três links iguais
    - `whatsapp_links_follow_setting_number` → refletem o número atual
  - **Traces:** US-2.5

- [ ] **Task:** Ligar o `car-card` ao modal
  - **Acceptance criteria:**
    - `View details` abre o `CarDetail` do carro certo
    - Selo `Price drop` só aparece nos carros marcados
  - **Feature tests:**
    - `price_drop_badge_only_when_flagged` → selo presente só quando `price_drop`
  - **Traces:** US-2.1; US-2.2

### Phase 6.2: Seções de conversão

- [ ] **Task:** Faixa de trade-in
  - **Acceptance criteria:**
    - Faixa vermelha com `Trading in? Get a cash offer.` e botão `Value my car`
    - O botão abre `wa.me` com `Hi Mario, I'd like an offer on my car.`; sem formulário e sem lead salvo
  - **Feature tests:**
    - `trade_in_button_links_to_whatsapp_with_message` → URL e texto corretos
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → `#trade`
  - **Traces:** US-2.6; Workflow 3

- [ ] **Task:** Bloco de confiança
  - **Acceptance criteria:**
    - Três colunas: preços claros, garantia de usados de Massachusetts e loja local e familiar
    - Empilha em uma coluna no celular
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → `#why`
  - **Traces:** US-2.7

- [ ] **Task:** Seção `Visit the lot`
  - **Acceptance criteria:**
    - Endereço, telefone e horários de Store info; `Closed` aparece como cadastrado
    - Mapa do Google incorporado (iframe) e botão `Open in Google Maps` apontando para o endereço atual
    - Zoom leve do mapa no hover e botão que fica vermelho
  - **Feature tests:**
    - `visit_section_uses_setting_address_in_map_links` → iframe e link contêm o endereço de Setting
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → `#visit`, `#visitH`
  - **Traces:** US-2.7; US-5.2

---

## Phase 7: Legal, SEO e acessibilidade

**Goal:** Páginas legais, metadados, dados estruturados, sitemap, robots e revisão de acessibilidade. · **Depends on:** Phase 6 · **Covers:** US-6.1 a US-6.5

### Phase 7.1: Páginas legais e SEO

- [ ] **Task:** Páginas `/privacy`, `/terms` e `/accessibility`
  - **Acceptance criteria:**
    - As três rotas respondem 200, em inglês, linkadas no rodapé
    - Terms cita a garantia de usados de Massachusetts; Accessibility traz telefone e endereço de Store info
    - Textos tratados como rascunho até a revisão do advogado (nota no README)
  - **Feature tests:**
    - `legal_pages_return_200` → três rotas
    - `accessibility_page_shows_store_phone_and_address` → dados de Setting
    - `terms_mentions_massachusetts_warranty` → texto presente
  - **Design ref:** `.spec/init/design/Mario's Car Dealer.html` → `#legal`, `#lTitle`
  - **Traces:** US-6.1; Workflow 7

- [ ] **Task:** Componente de metadados (`<title>` e description)
  - **Acceptance criteria:**
    - Home usa `Used Cars in Dracut, MA | Mario's Auto Sales & Rentals` com meta description
    - Cada página define o próprio título e description
  - **Feature tests:**
    - `home_title_matches_spec` → título exato
  - **Traces:** US-6.2

- [ ] **Task:** JSON-LD `AutoDealer` na home
  - **Acceptance criteria:**
    - Contém nome, endereço, telefone e horários vindos de Store info
    - JSON válido dentro de `<script type="application/ld+json">`
  - **Feature tests:**
    - `home_has_valid_autodealer_json_ld` → decodifica e contém endereço e telefone de Setting
  - **Traces:** US-6.2

- [ ] **Task:** JSON-LD `Car` na página do carro
  - **Acceptance criteria:**
    - Contém nome, preço em USD, milhagem, câmbio, cor e imagem
    - Não aparece em carro que retorna 404
  - **Feature tests:**
    - `car_page_has_valid_car_json_ld` → campos presentes e preço correto
  - **Traces:** US-6.2

- [ ] **Task:** `/sitemap.xml` e `robots.txt`
  - **Acceptance criteria:**
    - Sitemap lista a home, os carros ativos e as páginas legais, com URLs absolutas de `APP_URL`
    - Carro oculto, vendido ou excluído não aparece
    - `robots.txt` existe e referencia o sitemap
  - **Feature tests:**
    - `sitemap_lists_active_cars_and_legal_pages` → URLs esperadas
    - `sitemap_excludes_hidden_sold_and_deleted_cars` → ausentes
    - `robots_txt_points_to_sitemap` → linha `Sitemap:`
  - **Traces:** US-6.3; US-8.1

- [ ] **Task:** Textos `alt` das fotos
  - **Acceptance criteria:**
    - Fotos de carros usam `alt` descritivo (`{color} {title}`, ex.: `White 2018 Jeep Compass`); decorativas usam `alt=""`
  - **Traces:** US-6.2; US-6.4

### Phase 7.2: Acessibilidade e páginas de erro

- [ ] **Task:** Auditoria de teclado e leitor de tela
  - **Acceptance criteria:**
    - Home, modal e páginas legais navegáveis só com teclado, com ordem de foco lógica
    - Botões só de ícone com `aria-label`; áreas de toque de pelo menos 44px
    - Verificação com axe/Lighthouse sem violações críticas, registrada no README
  - **Traces:** US-6.4

- [ ] **Task:** Verificação de contraste, movimento reduzido e tema claro
  - **Acceptance criteria:**
    - Texto com contraste de pelo menos 4.5:1 nas combinações usadas
    - Com `prefers-reduced-motion: reduce` não há hover com movimento nem transições
    - Nenhum estilo de modo escuro no projeto
  - **Traces:** US-6.4; US-6.5

- [ ] **Task:** Páginas de erro 404 e 500 com a marca
  - **Acceptance criteria:**
    - 404 e 500 usam o layout do site, em inglês, com link para a home
    - 404 de carro inexistente ou vendido usa essa página
  - **Feature tests:**
    - `unknown_route_renders_branded_404` → 404 com layout do site
  - **Traces:** US-2.3; US-8.1

---

## Phase 8: Admin — login, Store info e categorias

**Goal:** Entregar o acesso ao painel e as telas Store info e Categories. · **Depends on:** Phase 3, Phase 4 · **Covers:** US-3.1, US-4.1 a US-4.3, US-5.1, US-5.2

### Phase 8.1: Login

- [ ] **Task:** Acesso ao painel e usuários
  - **Acceptance criteria:**
    - `/admin` exige login; visitante é redirecionado para a tela de login
    - Credenciais inválidas mostram erro e não entram; todos os usuários têm o mesmo acesso
    - `AdminUserSeeder` cria um usuário só em ambiente local; produção usa `php artisan make:filament-user`
  - **Feature tests:**
    - `guest_is_redirected_from_admin_to_login` → 302 para o login
    - `invalid_credentials_do_not_log_in` → erro e sem sessão
    - `valid_user_reaches_admin` → painel carrega
    - `admin_seeder_does_not_run_in_production` → sem usuário em produção
  - **Design ref:** `.spec/init/design/Mario's Admin.html` → `#loginForm`, `#lEmail`, `#lPass`
  - **Traces:** US-3.1; Workflow 4

- [ ] **Task:** Tela de login com a marca
  - **Acceptance criteria:**
    - Logo, textos em inglês e campos e-mail/senha como no mockup
    - Usável no celular
  - **Design ref:** `.spec/init/design/Mario's Admin.html` → `#loginForm`
  - **Traces:** US-3.1

### Phase 8.2: Store info

- [ ] **Task:** Página Filament `StoreSettings`
  - **Acceptance criteria:**
    - Formulário único com WhatsApp, Phone, horários (Mon–Fri, Saturday, Sunday, texto livre) e endereço (street, city, state, ZIP)
    - Todos obrigatórios; WhatsApp só dígitos com código do país (`^[1-9][0-9]{7,14}$`)
    - Edita sempre a linha única de `settings`; ao salvar o cache é limpo
  - **Feature tests:**
    - `store_info_saves_to_single_row` → mesma linha atualizada
    - `store_info_requires_all_fields` → erros de campo vazio
    - `whatsapp_rejects_non_digits` → `+1 (978)` é rejeitado
    - `whatsapp_accepts_country_code_digits` → `19784174036` passa
    - `saving_store_info_clears_cache` → valor novo no próximo `Setting::current()`
  - **Traces:** US-5.1; Workflow 6

- [ ] **Task:** Visual da tela Store info
  - **Acceptance criteria:**
    - Campos agrupados como no mockup, com mensagem de sucesso após salvar
    - Usável no celular
  - **Design ref:** `.spec/init/design/Mario's Admin.html` → `#storeForm`, `#phInput`, `#sMF`, `#sSat`, `#sSun`
  - **Traces:** US-5.1

- [ ] **Task:** Teste de integração admin → site
  - **Acceptance criteria:**
    - Alterar Store info no painel muda header, visita, rodapé e links de WhatsApp na próxima visita ao site
  - **Feature tests:**
    - `store_info_change_in_admin_reflects_on_public_site` → fluxo completo
  - **Traces:** US-5.2

### Phase 8.3: Categories

- [ ] **Task:** `CategoryResource` com lista, criar e renomear
  - **Acceptance criteria:**
    - Lista com nome e quantidade de carros (todos os carros da categoria), ordenada por `sort_order`
    - Criar e renomear com nome obrigatório e único; nova categoria vai para o fim
    - Renomear atualiza o nome mostrado nos carros e no site
  - **Feature tests:**
    - `category_name_is_required_and_unique` → erros de validação
    - `new_category_gets_last_sort_order` → entra no fim
    - `renaming_category_updates_public_label` → site mostra o novo nome
  - **Design ref:** `.spec/init/design/Mario's Admin.html` → `#catFilter`, `#newCat`, `#addCat`
  - **Traces:** US-4.1; Workflow 5

- [ ] **Task:** Reordenar categorias
  - **Acceptance criteria:**
    - Setas para cima e para baixo trocam o `sort_order` com a vizinha
    - A ordem do admin é a ordem dos botões `Shop by make`; primeira não sobe e última não desce
  - **Feature tests:**
    - `move_up_swaps_sort_order` → ordem trocada
    - `first_category_cannot_move_up` → sem mudança
    - `site_buttons_follow_admin_order` → site na ordem nova
  - **Traces:** US-4.2

- [ ] **Task:** Excluir categoria
  - **Acceptance criteria:**
    - Exclusão pede confirmação
    - Categoria com qualquer carro (ativo, oculto ou vendido) não é excluída e mostra mensagem explicando
    - Categoria vazia é excluída
  - **Feature tests:**
    - `category_with_cars_cannot_be_deleted_in_admin` → mensagem e registro mantido
    - `empty_category_is_deleted_after_confirmation` → removida
    - `sold_car_still_blocks_category_deletion` → bloqueia
  - **Traces:** US-4.3

- [ ] **Task:** Visual das telas de categorias
  - **Acceptance criteria:**
    - Lista e edição como no mockup, usáveis no celular
  - **Design ref:** `.spec/init/design/Mario's Admin.html` → `#catEdit`, `#newCat`
  - **Traces:** US-4.1

---

## Phase 9: Admin — formulário de carro e fotos

**Goal:** Criar e editar carros com regras de publicação, upload e ordenação de fotos, conversões e prévia do card. · **Depends on:** Phase 8 · **Covers:** US-3.3, US-3.4, US-3.5, US-7.2

### Phase 9.1: Formulário

- [ ] **Task:** `CarResource` com páginas de criar e editar
  - **Acceptance criteria:**
    - Menu `Cars` com listagem, criar e editar
    - Campos: Photos, Category, Name, Version, Price, Mileage, Body type, Transmission, Drivetrain, Color, Engine, Description, `Show on website` (ligado por padrão) e `Price drop` badge
    - Selects de categoria e dos três apoios vêm do banco
  - **Design ref:** `.spec/init/design/Mario's Admin.html` → `#fTitle`, `#fTrim`, `#fPrice`, `#fMiles`, `#fColor`, `#fDesc`, `#fActive`, `#fDrop`
  - **Traces:** US-3.3; US-3.5; Workflow 4

- [ ] **Task:** Validação do formulário
  - **Acceptance criteria:**
    - Category, Name, Body type, Transmission e Drivetrain obrigatórios
    - Price e Mileage inteiros sem centavos (Price de 1 em diante, Mileage de 0 em diante)
    - Ligado, `Show on website` exige Price e pelo menos uma foto; desligado, o carro salva como rascunho
    - Carro vendido não pode ser ligado
  - **Feature tests:**
    - `create_requires_mandatory_fields` → erros nos cinco campos
    - `price_and_mileage_must_be_integers` → decimais rejeitados
    - `active_car_requires_price_and_photo` → mensagens claras
    - `inactive_draft_saves_without_price` → salva
    - `sold_car_cannot_be_turned_on` → bloqueado
  - **Traces:** US-3.3; US-3.6

- [ ] **Task:** Slug e valores padrão ao criar
  - **Acceptance criteria:**
    - Ao salvar, o carro recebe slug único e `is_active = true` (se válido)
    - O carro aparece no site assim que salvo ativo
  - **Feature tests:**
    - `created_car_gets_unique_slug` → slug gravado
    - `created_active_car_appears_on_site` → home lista o carro
  - **Traces:** US-3.3; US-3.6

- [ ] **Task:** Edição de carro
  - **Acceptance criteria:**
    - Todos os campos podem ser alterados, inclusive fotos
    - Alterações aparecem no site logo após salvar
  - **Feature tests:**
    - `edit_updates_public_page` → novo preço na página do carro
    - `edit_keeps_validation_rules` → mesmas regras do cadastro
  - **Traces:** US-3.5

### Phase 9.2: Fotos e prévia

- [ ] **Task:** Upload de várias fotos
  - **Acceptance criteria:**
    - Aceita várias fotos por vez (jpg, png, webp), pelo celular ou computador, com limite de 10 MB por arquivo
    - Arquivos que não são imagem são rejeitados com mensagem clara
    - Cada foto vai para o disco configurado em `cars/{car_id}/` e cria uma linha em `car_photos`
  - **Feature tests:**
    - `upload_accepts_multiple_images` → linhas criadas
    - `upload_rejects_non_image_files` → erro
    - `upload_rejects_files_over_limit` → erro
    - `photo_path_is_saved_in_car_photos` → `file_path` correto
  - **Traces:** US-3.4

- [ ] **Task:** Reordenar fotos e definir a capa
  - **Acceptance criteria:**
    - Fotos reordenáveis por arrastar; a primeira é a capa
    - A ordem é gravada em `sort_order` e usada no card, no modal e na prévia
  - **Feature tests:**
    - `reordering_photos_persists_sort_order` → ordem gravada
    - `first_photo_is_cover_in_public_card` → capa correta
  - **Traces:** US-3.4

- [ ] **Task:** Remover fotos
  - **Acceptance criteria:**
    - Remover uma foto apaga a linha e os arquivos (original e conversões)
    - Se o carro ativo ficar sem foto, a validação de publicação bloqueia o salvamento
  - **Feature tests:**
    - `removing_photo_deletes_row_and_files` → linha e arquivos removidos
    - `removing_last_photo_of_active_car_is_blocked` → erro
  - **Traces:** US-3.4; US-3.6

- [ ] **Task:** Conversões `thumb` e `large` síncronas no upload (`PhotoConverter`)
  - **Acceptance criteria:**
    - Um serviço `PhotoConverter` (Intervention Image) gera WebP `thumb` (600px de largura) e `large` (1600px) a partir do original, em caminhos por convenção, durante o upload
    - Roda de forma síncrona ao criar a foto: sem job, fila ou worker
    - Se uma conversão faltar, as URLs usam o original
    - Falha de conversão é registrada em log e não derruba o upload
  - **Feature tests:**
    - `converter_creates_thumb_and_large_webp_on_upload` → arquivos com larguras máximas
    - `urls_use_conversions_after_upload` → `thumbUrl()` aponta para a conversão
    - `conversion_failure_is_logged_and_not_thrown` → log registrado
  - **Traces:** US-7.2; US-3.4

- [ ] **Task:** Prévia do card ao lado do formulário
  - **Acceptance criteria:**
    - Mostra capa, nome, versão, preço e selo `Price drop` atualizados enquanto o formulário é preenchido
    - Visual igual ao `car-card` do site
  - **Design ref:** `.spec/init/design/Mario's Admin.html` → `.card.preview`, `#pvTitle`, `#pvSub`, `#pvPrice`
  - **Traces:** US-3.3

- [ ] **Task:** Formulário usável no celular
  - **Acceptance criteria:**
    - Uma coluna no celular, campos grandes e upload com opção de câmera
    - Salvar e voltar para a lista funcionam com um toque
  - **Design ref:** `.spec/init/design/Mario's Admin.html` → layout mobile do formulário
  - **Traces:** US-3.3; Workflow 4

---

## Phase 10: Admin — lista de carros e vendas

**Goal:** Lista de carros com busca e filtros, interruptor, exclusão, marcar como vendido e reabrir. · **Depends on:** Phase 9 · **Covers:** US-3.2, US-3.6, US-3.7, US-8.1, US-8.2, US-8.3

### Phase 10.1: Lista de carros

- [ ] **Task:** Tabela de carros do admin
  - **Acceptance criteria:**
    - Colunas: foto, nome, versão, milhas, categoria, quantidade de fotos, selos (`Price drop`, `Hidden from site`, `Sold`) e preço
    - Carros ativos, ocultos e vendidos aparecem; eager loading evita consultas por linha
    - Em tela pequena as linhas viram cards legíveis
  - **Feature tests:**
    - `list_shows_active_hidden_and_sold_cars` → três situações
    - `list_query_count_is_constant` → sem N+1
  - **Design ref:** `.spec/init/design/Mario's Admin.html` → `.card.panel` (lista de carros)
  - **Traces:** US-3.2

- [ ] **Task:** Busca por nome e filtro por categoria
  - **Acceptance criteria:**
    - Busca por título e versão; filtro por categoria
    - Combinam entre si
  - **Feature tests:**
    - `search_matches_title_and_trim` → resultado correto
    - `category_filter_limits_list` → só a categoria
    - `search_and_category_combine` → interseção
  - **Design ref:** `.spec/init/design/Mario's Admin.html` → `#q`, `#catFilter`
  - **Traces:** US-3.2

- [ ] **Task:** Interruptor `On site / Hidden`
  - **Acceptance criteria:**
    - Alterna `is_active` direto na lista
    - Ativar sem preço, sem foto ou com carro vendido é bloqueado com mensagem explicando o motivo
    - Desligar remove o carro do site e das contagens imediatamente
  - **Feature tests:**
    - `toggle_off_hides_car_from_site_and_counts` → some da home e do botão da marca
    - `toggle_on_without_photo_is_blocked` → mensagem
    - `toggle_on_sold_car_is_blocked` → pede para reabrir
  - **Traces:** US-3.6

- [ ] **Task:** Editar e excluir na linha
  - **Acceptance criteria:**
    - Editar abre o formulário; excluir pede confirmação
    - Excluir remove o carro, as fotos e os arquivos; a categoria não é afetada
    - Ao excluir carro vendido, a confirmação avisa que a venda sairá dos números do dashboard
  - **Feature tests:**
    - `delete_requires_confirmation` → sem confirmar, nada acontece
    - `delete_removes_car_photos_and_files` → tudo removido
    - `delete_keeps_category` → categoria permanece
  - **Traces:** US-3.7; US-8.4

### Phase 10.2: Vendas

- [ ] **Task:** Ação `Mark as sold`
  - **Acceptance criteria:**
    - Disponível na lista e na edição, com confirmação: data da venda (padrão hoje, não pode ser futura) e valor (padrão o preço anunciado, inteiro maior que 0), ambos editáveis
    - Ao confirmar, chama `Car::markAsSold()`: grava `sold_at` e `sold_price` e desativa o carro
    - O carro some do site, das contagens por marca, do `Showing X of Y` e do sitemap; `/cars/{slug}` passa a dar 404
    - O carro continua no admin com o selo `Sold`; notificação de sucesso
  - **Feature tests:**
    - `mark_as_sold_action_updates_car` → campos e inativo
    - `sold_car_disappears_from_home_counts_and_sitemap` → ausente em três lugares
    - `sold_car_page_returns_404` → 404
    - `mark_as_sold_rejects_future_date_in_form` → erro
    - `mark_as_sold_twice_does_not_duplicate_sale` → uma venda só
  - **Traces:** US-8.1; Workflow 9

- [ ] **Task:** Ação `Mark as available`
  - **Acceptance criteria:**
    - Aparece só em carros vendidos, com confirmação
    - Apaga `sold_at` e `sold_price`; o carro continua oculto até o admin ligar `Show on website`
    - O selo `Sold` some e a venda deixa de contar no dashboard
  - **Feature tests:**
    - `reopen_action_clears_sale_and_keeps_hidden` → campos nulos e oculto
    - `reopen_not_available_for_unsold_car` → ação ausente
  - **Traces:** US-8.2

- [ ] **Task:** Filtro de status e dados da venda na lista
  - **Acceptance criteria:**
    - Filtro `All`, `On site`, `Hidden` e `Sold` combinável com busca e categoria
    - Carros vendidos mostram data e valor da venda
    - Sem filtro, a lista mostra todos os carros
  - **Feature tests:**
    - `status_filter_on_site_shows_active_only` → só ativos
    - `status_filter_hidden_excludes_sold` → inativos não vendidos
    - `status_filter_sold_shows_sold_with_date_and_price` → vendidos com dados
    - `status_filter_combines_with_search_and_category` → combinação correta
  - **Design ref:** `.spec/init/design/Mario's Admin.html` → `.card.panel` (filtros da lista); sem referência para `Sold` — seguir os tokens do mockup
  - **Traces:** US-8.3

---

## Phase 11: Admin — dashboard de vendas e estoque

**Goal:** Tela inicial do painel com vendas do mês, comparação, estoque, gráfico de 6 meses e últimas vendas. · **Depends on:** Phase 10 · **Covers:** US-8.4, US-8.5, US-8.6

### Phase 11.1: Consultas e widgets

- [ ] **Task:** Serviço de relatório `SalesReport`
  - **Acceptance criteria:**
    - Vendas do mês-calendário atual em `America/New_York`: quantidade e soma de `sold_price`, pela data de `sold_at`
    - Mês anterior e variação; sem vendas no mês anterior a variação é `null`, sem divisão por zero
    - Série de 6 meses com zeros, 5 últimas vendas e contagens de estoque (no site, ocultos, valor total do que está no site)
    - Carros vendidos não entram no estoque; carro reaberto sai das vendas
  - **Feature tests:**
    - `month_totals_use_sold_price_not_listed_price` → soma do valor vendido
    - `sale_at_month_end_in_new_york_counts_in_that_month` → venda às 23:30 do último dia entra no mês certo
    - `previous_month_zero_returns_null_change` → sem divisão por zero
    - `reopened_car_is_excluded_from_sales` → não conta
    - `six_month_series_includes_zero_months` → seis pontos
    - `inventory_counts_exclude_sold_cars` → contagens corretas
  - **Traces:** US-8.4; US-8.5; US-8.6; Key Concepts (Dashboard)

- [ ] **Task:** Dashboard como página inicial do `/admin`
  - **Acceptance criteria:**
    - `/admin` abre no dashboard, primeiro item do menu
    - Ordem: vendas do mês, estoque, gráfico, últimas vendas
  - **Feature tests:**
    - `admin_home_is_dashboard` → `/admin` mostra os widgets
  - **Traces:** US-8.4; Workflow 9

- [ ] **Task:** Widget `Sales this month`
  - **Acceptance criteria:**
    - Mostra quantidade, valor total em USD e variação contra o mês anterior (`—` quando não há base)
    - Usa `SalesReport`
  - **Feature tests:**
    - `sales_widget_shows_count_total_and_change` → valores corretos
    - `sales_widget_shows_dash_without_previous_month` → `—`
  - **Traces:** US-8.4

- [ ] **Task:** Widget de estoque
  - **Acceptance criteria:**
    - Mostra carros no site, ocultos e valor total do que está no site
    - Vendidos não contam
  - **Feature tests:**
    - `inventory_widget_counts_on_site_and_hidden` → números corretos
    - `inventory_widget_excludes_sold` → vendidos fora
  - **Traces:** US-8.5

- [ ] **Task:** Gráfico de vendas dos últimos 6 meses
  - **Acceptance criteria:**
    - Uma barra por mês (quantidade vendida), incluindo o mês atual; meses sem venda com zero
    - Legível no celular
  - **Feature tests:**
    - `chart_data_has_six_months_with_zeros` → seis rótulos e valores
  - **Traces:** US-8.6

- [ ] **Task:** Tabela das últimas vendas
  - **Acceptance criteria:**
    - 5 últimos carros vendidos em ordem de `sold_at` decrescente: foto, nome, valor e data, com link para editar o carro
    - Sem vendas, mostra mensagem de estado vazio
  - **Feature tests:**
    - `latest_sales_lists_five_most_recent` → ordem e limite
    - `latest_sales_empty_state` → mensagem
  - **Traces:** US-8.5

### Phase 11.2: Integração e visual

- [ ] **Task:** Atualização automática ao vender
  - **Acceptance criteria:**
    - Marcar um carro como vendido atualiza os três blocos do dashboard na próxima carga, sem ação manual
    - Reabrir desfaz o efeito
  - **Feature tests:**
    - `marking_car_sold_updates_dashboard_numbers` → vendas do mês e estoque mudam
    - `reopening_car_reverts_dashboard_numbers` → voltam
  - **Traces:** US-8.4; US-8.1; Workflow 9

- [ ] **Task:** Layout responsivo do dashboard
  - **Acceptance criteria:**
    - Blocos empilham no celular, sem rolagem horizontal
    - Valores em USD legíveis e fontes do tema do admin
  - **Design ref:** Sem referência no mockup (dashboard não existe nele); seguir os tokens de `.spec/init/design/Mario's Admin.html`
  - **Traces:** US-8.4

---

## Phase 12: Qualidade, segurança e entrada em produção

**Goal:** Fechar a v1: jornadas de ponta a ponta, segurança, desempenho, deploy no Laravel Cloud e entrega ao cliente. · **Depends on:** Phase 1–10 (Phase 11 é opcional para o lançamento) · **Covers:** US-7.1, Workflow 8, qualidade geral

### Phase 12.1: Qualidade e segurança

- [ ] **Task:** Testes de jornada completa
  - **Acceptance criteria:**
    - Admin cria carro → aparece no site → recebe link de WhatsApp correto → é marcado como vendido → some do site, do sitemap e das contagens → entra no dashboard
    - Jornada de Store info e de categorias cobertas ponta a ponta
  - **Feature tests:**
    - `car_lifecycle_from_creation_to_sale` → fluxo completo
    - `store_info_and_category_changes_reach_the_site` → mudanças refletidas
  - **Traces:** Workflows 1, 2, 4, 6, 9

- [ ] **Task:** Segurança e autorização
  - **Acceptance criteria:**
    - Rotas `/admin/*` exigem login; login com limite de tentativas
    - Descrição e campos de texto escapados na saída (sem XSS)
    - Mass assignment protegido e CSRF ativo nos formulários
  - **Feature tests:**
    - `admin_routes_redirect_guests` → todas as rotas protegidas
    - `car_description_is_escaped_on_public_page` → HTML não é interpretado
    - `login_is_rate_limited` → bloqueio após tentativas
  - **Traces:** US-3.1; Tech Stack

- [ ] **Task:** Desempenho e cache
  - **Acceptance criteria:**
    - `php artisan optimize` e `filament:optimize` rodam sem erro
    - `Setting` em cache; consultas das páginas públicas com eager loading e índices de `cars` usados
  - **Feature tests:**
    - `public_pages_do_not_query_settings_repeatedly` → uma leitura de cache por requisição
  - **Traces:** Workflow 8; Tabela cars

### Phase 12.2: Deploy e entrega

- [ ] **Task:** Variáveis de ambiente e seeders de produção
  - **Acceptance criteria:**
    - `.env.example` completo e documentado; produção roda apenas apoio e `SettingSeeder` (sem categorias; o Mario as cadastra no admin)
    - `DemoCarSeeder` e usuário demo não rodam em produção
  - **Traces:** US-7.1; Workflow 8

- [ ] **Task:** Deploy no Laravel Cloud
  - **Acceptance criteria:**
    - Ambiente `production` com MySQL gerenciado e bucket de storage (R2/S3) ligados
    - Comandos de deploy: `php artisan migrate --force`, `php artisan optimize`, `php artisan filament:optimize`
    - Sem worker: o projeto não usa filas (conversões síncronas no upload)
    - Domínio próprio com HTTPS e `APP_URL` correto
  - **Traces:** US-7.1; US-7.2; Workflow 8

- [ ] **Task:** Usuários reais do admin e substituição de dados de exemplo
  - **Acceptance criteria:**
    - Usuários do Mario e do João criados com `make:filament-user` e senhas fortes
    - Nenhum carro, foto ou credencial de demonstração em produção
    - Store info revisada com horário e WhatsApp reais (editáveis no admin)
  - **Traces:** US-3.1; US-5.1; Workflow 6

- [ ] **Task:** Busca do Google e perfil da empresa
  - **Acceptance criteria:**
    - Site cadastrado no Google Search Console e `/sitemap.xml` enviado
    - Google Business Profile criado ou atualizado (tarefa operacional, fora do código)
  - **Traces:** US-6.3; Workflow 8

- [ ] **Task:** Guia de uso para a loja
  - **Acceptance criteria:**
    - README (ou página de ajuda) explica em inglês e passo a passo: cadastrar carro, ordenar fotos, ocultar, marcar como vendido, reabrir e ler o dashboard
    - Lista de pendências do cliente para o go-live: fotos reais, domínio, doc fee e revisão jurídica dos textos
  - **Traces:** US-3.3; US-8.1; Workflow 8

---

## Open Questions

- **Fotos:** o plano segue o schema (`car_photos` + conversão síncrona com Intervention Image, sem fila). Se o cliente preferir `spatie/laravel-medialibrary` (recomendado no schema, com conversões prontas), as tarefas das Phases 1.1, 3.1 e 9.2 mudam.
- **Limite de upload:** 10 MB por foto foi assumido (a spec não define); confirmar com o cliente.
- **Pendências do cliente fora do código:** fotos reais, domínio, doc fee e revisão jurídica dos textos legais (Phase 12.2).
