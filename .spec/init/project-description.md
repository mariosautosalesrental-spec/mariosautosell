# Mario's Auto Sales & Rentals — Project Description

## Overview

Site institucional com vitrine de carros usados da **Mario's Auto Sales & Rentals**, revenda independente em Dracut, Massachusetts (EUA), mais um **painel administrativo** para a loja cadastrar os carros pelo celular, no pátio. O público são moradores de Dracut, Lowell e região (inclui comunidade brasileira e latina). O site é em **inglês**, com valores em **USD** e milhas.

O objetivo é um só: **mostrar os carros disponíveis e levar o cliente a chamar a loja no WhatsApp**. Não há venda online, financiamento, reserva nem login de cliente. O fluxo central é: o Mario cadastra um carro no admin → ele aparece no site → o visitante filtra, abre o detalhe e fala com a loja pelo WhatsApp com uma mensagem pronta.

**Fase atual (v1):** sistema funcional em produção — login no admin, banco de dados, upload de fotos e site lendo os carros cadastrados. Hoje o repositório contém apenas os **mockups clicáveis** (HTML + Vite, dados de exemplo) que servem de referência visual e de comportamento; o app real será construído em **Laravel + Livewire + Filament**. Aluguel de carros, apesar do "& Rentals" no logo, está **fora do escopo**; a oficina também não entra no site.

### Key Concepts

- **Car (carro):** item do estoque. Campos: title (ex.: `2019 Jeep Compass`), trim (ex.: `Limited 4x4`), price (USD inteiro, sem centavos, já com taxas da loja), mileage (milhas, opcional), body type (`SUV`, `Car`, `Truck`), transmission (`Automatic`, `Manual`), drivetrain (`FWD`, `RWD`, `AWD`, `4WD`), color, description, fotos ordenáveis (a 1ª é a capa), `is_active`, `price_drop`.
- **Category (categoria/marca):** agrupa carros e vira um botão em "Shop by make". Tem ordem definida no admin. Não pode ser excluída se ainda tem carros; só aparece no site se tiver carro ativo.
- **Publicação:** um carro só aparece no site com `is_active = true`; novos carros já nascem ativos (`is_active = true` por padrão), por isso o cadastro exige preço e pelo menos uma foto; o admin pode desligar depois (ou salvar desativado, sem exigir preço/foto). Vendeu → desliga "Show on website" (não precisa excluir).
- **Sold (vendido):** estado do carro depois da venda. Ao marcar como vendido o carro grava `sold_at` (data) e `sold_price` (valor da venda, pré-preenchido com o preço anunciado e editável), é **desativado automaticamente** (`is_active = false`) e some da vitrine, das contagens das marcas e do sitemap, mas **continua cadastrado** no admin para o histórico. Carro vendido não pode ser reativado sem antes ser reaberto ("Mark as available").
- **Dashboard:** tela inicial do admin com os números de vendas e estoque, calculados direto dos carros (`sold_at`, `sold_price`, `is_active`), sem tabela de relatório separada. "Mês" = mês-calendário no fuso `America/New_York`.
- **Price drop:** selo opcional exibido no card do carro.
- **Preço anunciado:** já inclui todas as taxas da loja (doc fee etc.); impostos, título e registro ficam de fora e são avisados no site. Nunca condicionar o preço a financiamento.
- **Garantia de usados de Massachusetts:** calculada pela milhagem (M.G.L. c. 90 § 7N¼): menos de 40.000 mi → 90 dias ou 3.750 mi; 40.000–79.999 → 60 dias ou 2.500 mi; 80.000–124.999 → 30 dias ou 1.250 mi; 125.000+ → sem garantia legal. Vale para carros acima de US$ 700.
- **Store info:** registro único com WhatsApp, telefone, endereço (street, city, state, ZIP) e horários (Mon–Fri, Saturday, Sunday, texto livre). Alimenta header, "Visit the lot", rodapé, mapa e todos os links de WhatsApp.
- **Mensagem de WhatsApp:** link `https://wa.me/<numero>?text=<mensagem>`; carro: `Hi Mario, is the {title} {trim} still available?`; trade-in: `Hi Mario, I'd like an offer on my car.`
- **Paginação incremental:** 12 carros por carga; "Show more cars" soma mais 12; ao mudar filtro volta aos 12 primeiros; ordem padrão `created_at desc`.
- **Usuários do admin:** 1 ou 2 (Mario e João), sem níveis de permissão na v1.

## Tech Stack

| Layer | Technology |
| --- | --- |
| Backend (alvo) | Laravel 13 (PHP 8.3 ou superior) |
| Site público (alvo) | Blade + Livewire 4 (filtros e "Show more" sem recarregar) |
| Admin (alvo) | Filament 5, painel em `/admin`, login obrigatório; dashboard com widgets nativos do Filament (`StatsOverviewWidget`, `ChartWidget`, tabela) |
| Fotos (alvo) | Caminho do arquivo em `car_photos`; conversões WebP (`thumb` 600px, `large` 1600px) geradas com Intervention Image 4 **na hora do upload**, sem fila nem jobs |
| Storage (alvo) | Cloudflare R2 ou S3 (disk `s3`) |
| Banco (alvo) | MySQL 8 ou PostgreSQL |
| CSS (alvo) | Tailwind CSS 4 com tokens da identidade visual |
| Fontes | Google Fonts: Barlow Condensed (títulos), Barlow (texto); Yellowtail (script, só no site) |
| Hospedagem (alvo) | Laravel Cloud (domínio próprio + HTTPS) |
| Mockups (atual) | HTML estático de arquivo único (CSS/JS/imagens embutidas), sem backend |
| Tooling (atual) | Vite ^8.3.4, multi-página (`index.html` = site, `admin.html` = painel), Node v24.19.0, npm 11.17.0; scripts `dev`, `build`, `preview` |
| Deploy dos mockups (atual) | Vercel (`vercel.json` com `cleanUrls`), repositório `github.com/elieldiniz/mario-car`, branch `main` |
| Testes | PHPUnit (padrão do Laravel), `php artisan test`; Livewire testing helpers |

Identidade visual: tema claro fixo; `accent #B3121F`, `whatsapp #1A7F45`, `ink #18161A`, `muted #5E5A55`, `bg #F7F6F4`, `surface #FFFFFF`, `line #E6E2DD`, `chip #EFEDEA`, `dark #141216`.

## Core Workflows

### 1. Visitante navega e filtra o estoque

1. Abre `/`: header fixo (logo, endereço, horário, telefone), hero com foto do pátio e botão `Browse our cars`.
2. Filtra por **Make**, **Body type** e **Max price** (no celular só Body type e Max price; a marca fica nos botões "Shop by make", com `All` primeiro e contagem de carros ativos).
3. A grade "On the lot" mostra cards de 12 em 12 com `Show more cars` e texto `Showing X of Y cars`; filtro sem resultado mostra estado vazio sugerindo o WhatsApp.
4. Query strings aceitas: `?make=jeep&body=suv&max=15000&page=2`.

### 2. Visitante vê o detalhe do carro e chama no WhatsApp

1. No card: foto de capa, selo `Price drop` (se marcado), `View details`, nome/versão, etiquetas (milhas, câmbio, tração), preço com `incl. dealer fees` e botão `Ask about this car`.
2. O detalhe abre em modal (galeria, specs, descrição, faixa de garantia de MA calculada pela milhagem, aviso `Includes dealer fees. Plus tax, title & registration.`) e também em página própria `/cars/{slug}` para SEO e compartilhamento.
3. O botão `WhatsApp about this car` abre `wa.me` com a mensagem pronta do carro.
4. Modal acessível: foco preso, `Esc` fecha, foco volta ao card de origem.

### 3. Visitante pede oferta de trade-in

1. Na faixa `Trading in? Get a cash offer.` clica em `Value my car`.
2. Abre o WhatsApp com `Hi Mario, I'd like an offer on my car.` (sem formulário nem lead salvo na v1).

### 4. Admin gerencia carros (Cars)

1. Login em `/admin` (Filament), uso principal pelo celular.
2. Lista: foto, nome, versão, milhas, categoria, nº de fotos, selos (`Price drop`, `Hidden from site`), preço; interruptor **On site / Hidden** direto na lista; busca por nome; filtro por categoria; editar e excluir com confirmação.
3. Formulário criar/editar com os campos do carro, upload múltiplo de fotos reordenáveis e prévia do card ao lado.
4. Validação: não ativa carro sem preço ou sem foto (no formulário e no interruptor da lista).

### 5. Admin gerencia categorias (Categories)

1. Lista com nome e quantidade de carros.
2. Adicionar, renomear, reordenar (setas) e excluir; a ordem define a ordem dos botões "Shop by make".
3. Exclusão bloqueada se a categoria ainda tem carros.

### 6. Admin edita informações da loja (Store info)

1. Formulário único: WhatsApp, telefone, horários e endereço.
2. Ao salvar, o cache de `settings` é limpo; header, "Visit the lot", rodapé, mapa e botões de WhatsApp passam a refletir o novo valor.

### 7. Páginas legais, SEO e acessibilidade

1. Rotas `/privacy`, `/terms`, `/accessibility`, `/sitemap.xml` (home, carros ativos e páginas legais) e `robots.txt`.
2. SEO: `<title>` da home `Used Cars in Dracut, MA | Mario's Auto Sales & Rentals`; título por carro `2019 Jeep Compass Limited 4x4 for sale in Dracut, MA`; Schema.org `AutoDealer` (home) e `Car` (cada carro); `alt` descritivo nas fotos.
3. Acessibilidade WCAG 2.1 AA: contraste 4.5:1, alvos de toque ≥ 44px, `aria-label` em botões só de ícone, navegação por teclado, respeito a `prefers-reduced-motion`.

### 8. Deploy em produção

1. Conectar o repositório ao Laravel Cloud, ambiente `production`, MySQL gerenciado e bucket de storage.
2. Comandos: `php artisan migrate --force`, `php artisan optimize`, `php artisan filament:optimize`; não há fila nem worker: as conversões de imagem rodam no próprio upload.
3. Domínio próprio com HTTPS; cadastrar no Google Search Console e enviar `/sitemap.xml`.

### 9. Admin marca carro como vendido e acompanha o dashboard

1. Na lista de carros (ou na edição), o admin toca em **Mark as sold**. Abre uma confirmação com **data da venda** (padrão: hoje) e **valor da venda** (padrão: preço anunciado), ambos editáveis.
2. Ao confirmar, o carro grava `sold_at` e `sold_price`, vira inativo (`is_active = false`) e sai imediatamente do site, das contagens por marca e do sitemap; continua na lista do admin com o selo `Sold`.
3. Na lista de carros há um filtro de **status**: `All`, `On site`, `Hidden` e `Sold`.
4. **Mark as available** reabre o carro (apaga `sold_at`/`sold_price`); ele continua oculto até o admin ligá-lo de novo em `Show on website`.
5. A tela inicial do admin (dashboard) mostra: **vendas do mês** (quantidade e valor total) comparadas ao mês anterior; **estoque atual** (carros no site, ocultos e valor total do que está no site); gráfico de vendas dos **últimos 6 meses**; e os **últimos carros vendidos** (foto, nome, valor, data).
6. Os números se atualizam sozinhos assim que um carro é marcado como vendido; não há lançamento manual separado.

## Decisions

- **Horário e WhatsApp são dados, não código:** ficam em Store info no admin e podem ser alterados a qualquer momento pelo Mario/João; nada é fixo no código. Os valores iniciais do seeder (`SettingSeeder`) são provisórios até a loja confirmar.
- **Fotos reais:** serão enviadas pelo próprio admin (upload no formulário do carro). As fotos de exemplo dos mockups não vão para produção.
- **Sem fila/jobs:** o projeto não usa filas (`QUEUE_CONNECTION=sync`, sem worker e sem tabelas `jobs`/`failed_jobs`); tudo que for pesado, como as conversões de foto, roda de forma síncrona na requisição.
- **Sem categorias pré-cadastradas:** nenhuma marca é criada por seeder em produção; o Mario cadastra as categorias na tela Categories do admin. Apenas o ambiente local (`DemoCarSeeder`) cria marcas de exemplo.
- **Versões:** Filament 3 não instala no Laravel 13 e tem avisos de segurança; a v1 usa as versões atuais (Laravel 13, Filament 5, Livewire 4, Tailwind CSS 4, Intervention Image 4).
- **Idioma:** todo o conteúdo do site e do admin em **inglês**.
- **Dados iniciais = os do mockup:** WhatsApp/telefone `(978) 417-4036` (`19784174036`), horário Mon–Fri 9:00 AM – 6:00 PM, Saturday 9:00 AM – 4:00 PM, Sunday Closed, endereço 1336 Merrimack Ave, Dracut, MA 01826, e o nome "Mario's Auto Sales & Rentals". Entram no `SettingSeeder` e podem ser trocados depois no admin. Domínio, valor da doc fee e revisão jurídica dos textos legais **não bloqueiam** o desenvolvimento: ficam como pendências do cliente, fora do escopo desta fase.
- **Admin em inglês**, igual ao mockup (`APP_LOCALE=en`).
- **Pasta do app:** `/home/eliel/workspace/projetos/marios-auto`.
- **Testes:** padrão do Laravel — PHPUnit via `php artisan test` (feature tests, incluindo componentes Livewire e recursos Filament) e unit tests para regras como a garantia de MA.
- **Vendas e dashboard (simples):** sem módulo financeiro, comprador ou comissão; vendido = campos `sold_at` e `sold_price` no próprio carro. Detalhes na workflow 9.
- **Repositório:** o app Laravel será criado em uma **nova pasta, fora deste repositório**, seguindo fielmente os mockups (layout, textos, cores, interações). Este repositório (`mario-car`) permanece só com os mockups, que continuam na Vercel como demonstração para o cliente.
