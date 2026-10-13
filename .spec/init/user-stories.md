# Mario's Auto Sales & Rentals — User Stories

<!-- inputs: project-description.md@sha256:5afc63a6b489 -->

## Overview

Site de vitrine de carros usados da Mario's Auto Sales & Rentals (Dracut, MA) e painel administrativo para a loja cadastrar o estoque pelo celular. O objetivo do produto é mostrar os carros disponíveis e levar o visitante a chamar a loja no WhatsApp; não há venda online, financiamento, reserva nem login de cliente.

O painel também registra vendas (carro vendido sai da vitrine) e tem um dashboard simples de vendas e estoque, que não existe nos mockups.

Todas as histórias abaixo pertencem à v1 (sistema funcional em produção, em Laravel + Livewire + Filament, seguindo os mockups). Aluguel de carros, importação de estoque, publicação em marketplaces, leads salvos e níveis de permissão estão fora do escopo (v2).

**User Types:**
- **Visitor** - pessoa que acessa o site público para ver os carros e contatar a loja (sem conta)
- **Store admin** - Mario ou João, usuário autenticado do painel `/admin` que cadastra carros, categorias e dados da loja

---

## 1. Browsing the lot (public site)

### US-1.1: Landing header and hero
**As a** Visitor
**I want to** ver o topo do site com logo, contatos e a chamada principal
**So that** entender rapidamente quem é a loja e chegar ao estoque

**Acceptance Criteria:**
- [ ] Header fixo com logo (altura 56px no desktop, 42px no celular, `width: auto`), endereço, horário e telefone vindos de Store info, e faixa vermelha de 3px embaixo
- [ ] No celular o header mostra só logo + telefone
- [ ] Hero com foto do pátio ocupando o fundo, etiqueta `Used cars · Dracut, MA`, título `Quality used cars. Prices that make sense.`, subtítulo e botão `Browse our cars`
- [ ] Clicar em `Browse our cars` rola a página até a seção de estoque
- [ ] No celular a foto fica em cima e o texto embaixo; não há menu de navegação nem botão de WhatsApp no header

**Expected Result:** O visitante vê a identidade da loja e chega ao estoque com um toque.

---

### US-1.2: Filter cars by make, body type and max price
**As a** Visitor
**I want to** filtrar o estoque por marca, carroceria e preço máximo
**So that** achar só os carros que me interessam

**Acceptance Criteria:**
- [ ] No desktop a barra de busca tem Make, Body type, Max price e botão Search
- [ ] No celular a busca tem só Body type e Max price, lado a lado, e aplica o filtro ao mudar o valor
- [ ] Body type aceita `SUV`, `Car` e `Truck`; Max price filtra carros com preço menor ou igual ao valor
- [ ] Ao mudar qualquer filtro a lista volta aos 12 primeiros carros
- [ ] Só carros ativos (`is_active = true`) entram no resultado

**Expected Result:** A grade mostra apenas os carros ativos que atendem aos filtros escolhidos.

---

### US-1.3: Shop by make buttons
**As a** Visitor
**I want to** escolher uma marca pelos botões da seção Shop by make
**So that** navegar o estoque por marca com um toque

**Acceptance Criteria:**
- [ ] Há um botão `All` primeiro e um botão por categoria, na ordem definida no admin, com a quantidade de carros ativos (ex.: `Jeep 4`)
- [ ] A marca selecionada fica vermelha e filtra a grade
- [ ] Categoria sem carro ativo não aparece
- [ ] No celular os botões ficam em uma linha com rolagem horizontal
- [ ] Os botões respondem com hover (sobem 2px, borda e texto vermelhos)

**Expected Result:** Clicar numa marca mostra só os carros ativos dela, e a contagem reflete apenas carros ativos.

---

### US-1.4: Load more cars (incremental pagination)
**As a** Visitor
**I want to** carregar mais carros com um botão
**So that** ver todo o estoque sem recarregar a página

**Acceptance Criteria:**
- [ ] A grade mostra 12 carros por vez, ordenados do mais recente para o mais antigo (`created_at desc`)
- [ ] O botão `Show more cars` soma mais 12 carros sem recarregar a página (Livewire)
- [ ] O texto `Showing X of Y cars` reflete o total filtrado
- [ ] O botão some quando todos os carros do filtro já estão visíveis
- [ ] O valor de carros por página vem de `CARS_PER_PAGE` (padrão 12)

**Expected Result:** O visitante percorre o estoque em blocos de 12, e o contador sempre bate com o filtro atual.

---

### US-1.5: Empty state when no car matches
**As a** Visitor
**I want to** ver uma mensagem útil quando nenhum carro atende ao filtro
**So that** saber o que fazer em vez de ver uma tela vazia

**Acceptance Criteria:**
- [ ] Com filtros sem resultado a grade mostra um estado vazio em inglês
- [ ] A mensagem sugere chamar a loja no WhatsApp, com link `wa.me` usando o número de Store info
- [ ] Limpar ou alterar o filtro traz os carros de volta

**Expected Result:** O visitante entende que não há carro com aquele filtro e tem um caminho para falar com a loja.

---

### US-1.6: Shareable filter URLs
**As a** Visitor
**I want to** abrir um link com filtros já aplicados
**So that** compartilhar uma busca ou chegar nela pelo Google

**Acceptance Criteria:**
- [ ] A home aceita `?make=jeep&body=suv&max=15000&page=2` e aplica os filtros correspondentes
- [ ] Alterar filtros atualiza a query string
- [ ] Valores inválidos na query string são ignorados sem erro (a página abre sem filtro)

**Expected Result:** Um link com query string reproduz exatamente a busca.

---

## 2. Car details and contact (public site)

### US-2.1: Car card
**As a** Visitor
**I want to** ver cada carro num card com as informações principais
**So that** comparar carros rapidamente

**Acceptance Criteria:**
- [ ] O card mostra foto de capa (a 1ª foto), nome, versão (se houver) e etiquetas de milhas, câmbio e tração
- [ ] O preço aparece formatado em USD com o texto `incl. dealer fees`
- [ ] O selo `Price drop` aparece só quando o carro tem `price_drop` ativo
- [ ] Botão `View details` aparece no hover no desktop e sempre visível no celular
- [ ] No hover o card sobe 4px com sombra, a foto dá zoom de 6% e o título fica vermelho
- [ ] Botão verde `Ask about this car` abre o WhatsApp do carro

**Expected Result:** Cada carro ativo aparece como um card completo e clicável.

---

### US-2.2: Car detail modal
**As a** Visitor
**I want to** abrir o detalhe do carro numa janela
**So that** ver fotos e especificações sem sair da lista

**Acceptance Criteria:**
- [ ] O modal mostra galeria de fotos, nome, versão, milhas, preço e o aviso `Includes dealer fees. Plus tax, title & registration.`
- [ ] Mostra specs: motor (opcional), câmbio, tração, carroceria e cor, além da descrição
- [ ] O botão `WhatsApp about this car` fica fixo no rodapé do modal
- [ ] `Esc` fecha o modal, o foco fica preso dentro dele enquanto aberto e volta ao card de origem ao fechar
- [ ] Campos opcionais vazios (milhas, cor, motor, descrição) não aparecem no modal

**Expected Result:** O visitante vê todos os dados do carro e pode chamar a loja sem perder a posição na lista.

---

### US-2.3: Car page by slug
**As a** Visitor
**I want to** abrir uma página própria de cada carro em `/cars/{slug}`
**So that** compartilhar o link de um carro e encontrá-lo pelo Google

**Acceptance Criteria:**
- [ ] `GET /cars/{slug}` mostra o detalhe do carro ativo com o mesmo conteúdo do modal
- [ ] Slug é gerado no save a partir de título + versão + id (ex.: `2019-jeep-compass-limited-4x4-12`) e é único
- [ ] Carro inexistente ou inativo retorna 404
- [ ] O `<title>` da página é `{title} {trim} for sale in Dracut, MA`
- [ ] Abrir um carro pela home mostra o modal; acessar a URL direta mostra a página

**Expected Result:** Cada carro ativo tem uma URL própria compartilhável.

---

### US-2.4: Massachusetts used-vehicle warranty
**As a** Visitor
**I want to** ver a garantia legal de Massachusetts do carro
**So that** saber meus direitos antes de comprar

**Acceptance Criteria:**
- [ ] Menos de 40.000 mi mostra `90 days or 3,750 miles`
- [ ] De 40.000 a 79.999 mi mostra `60 days or 2,500 miles`
- [ ] De 80.000 a 124.999 mi mostra `30 days or 1,250 miles`
- [ ] 125.000 mi ou mais não mostra garantia legal
- [ ] Carro sem milhagem informada não mostra a faixa de garantia
- [ ] A regra é implementada em `Car::maWarranty()` e coberta por teste unitário

**Expected Result:** O detalhe do carro mostra a faixa de garantia correta pela milhagem.

---

### US-2.5: Ask about a car on WhatsApp
**As a** Visitor
**I want to** chamar a loja no WhatsApp a partir de um carro
**So that** perguntar se o carro ainda está disponível

**Acceptance Criteria:**
- [ ] O link é `https://wa.me/<numero>?text=<mensagem>` com o número vindo de Store info
- [ ] A mensagem pronta é `Hi Mario, is the {title} {trim} still available?` com texto codificado na URL
- [ ] Quando o carro não tem versão, a mensagem não deixa espaço duplo nem `null`
- [ ] O mesmo link vale no card e no modal/página do carro
- [ ] Se o número de WhatsApp for alterado no admin, os novos links usam o novo número

**Expected Result:** Um toque abre o WhatsApp com a mensagem do carro já escrita.

---

### US-2.6: Trade-in offer request
**As a** Visitor
**I want to** pedir uma oferta pelo meu carro com um toque
**So that** vender meu carro usado para a loja

**Acceptance Criteria:**
- [ ] A faixa vermelha mostra `Trading in? Get a cash offer.` e o botão `Value my car`
- [ ] O botão abre `wa.me` com a mensagem `Hi Mario, I'd like an offer on my car.`
- [ ] Não há formulário e nenhum lead é salvo na v1

**Expected Result:** O visitante inicia uma conversa de trade-in no WhatsApp.

---

### US-2.7: Trust block and Visit the lot
**As a** Visitor
**I want to** ver os diferenciais da loja, o endereço, os horários e o mapa
**So that** confiar na loja e saber como chegar

**Acceptance Criteria:**
- [ ] O bloco de confiança tem três colunas: preços claros, garantia de usados de Massachusetts, loja local e familiar
- [ ] `Visit the lot` mostra endereço, telefone e horários vindos de Store info
- [ ] Mapa do Google incorporado (iframe) com botão `Open in Google Maps` apontando para o endereço da loja
- [ ] Domingo com texto `Closed` é exibido como cadastrado
- [ ] O rodapé tem logo, aviso legal de preço, links para Privacy, Terms e Accessibility e copyright

**Expected Result:** O visitante encontra endereço, horários e mapa, e vê as garantias da loja.

---

## 3. Cars management (admin)

### US-3.1: Admin login
**As a** Store admin
**I want to** entrar no painel com e-mail e senha
**So that** gerenciar o estoque com segurança

**Acceptance Criteria:**
- [ ] `/admin` exige login (Filament); visitante não autenticado é redirecionado para a tela de login
- [ ] Existem 1 ou 2 usuários (Mario e João), criados via `php artisan make:filament-user`
- [ ] Credenciais inválidas mostram mensagem de erro e não entram
- [ ] Todos os usuários têm o mesmo acesso (sem níveis de permissão na v1)
- [ ] O painel é em inglês e usável no celular

**Expected Result:** Só usuários autenticados acessam o painel.

---

### US-3.2: Cars list
**As a** Store admin
**I want to** ver a lista de carros com foto, preço e situação
**So that** achar e conferir rapidamente o estoque cadastrado

**Acceptance Criteria:**
- [ ] A lista mostra foto, nome, versão, milhas, categoria, quantidade de fotos, selos (`Price drop`, `Hidden from site`) e preço
- [ ] Há busca por nome, filtro por categoria e filtro por status (ver US-8.3)
- [ ] Cada linha tem editar e excluir
- [ ] Carros ativos e ocultos aparecem na lista
- [ ] Funciona em tela de celular

**Expected Result:** O admin enxerga todo o estoque, ativo ou oculto, e encontra um carro por nome ou categoria.

---

### US-3.3: Create a car
**As a** Store admin
**I want to** cadastrar um carro novo
**So that** colocá-lo à venda no site

**Acceptance Criteria:**
- [ ] O formulário tem: Photos, Category, Name, Version, Price, Mileage, Body type, Transmission, Drivetrain, Color, Description, `Show on website` e `Price drop` badge
- [ ] Category, Name, Body type, Transmission e Drivetrain são obrigatórios; `Show on website` vem ligado por padrão; com ele ligado, Price e pelo menos uma Photo são obrigatórios (desligado, o carro pode ser salvo como rascunho sem preço/foto)
- [ ] Body type aceita `SUV`/`Car`/`Truck`; Transmission `Automatic`/`Manual`; Drivetrain `FWD`/`RWD`/`AWD`/`4WD`
- [ ] Price e Mileage são inteiros sem centavos (USD e milhas)
- [ ] Ao lado do formulário há uma prévia de como o card vai aparecer no site
- [ ] Ao salvar, o slug é gerado e o carro aparece na lista

**Expected Result:** O carro fica cadastrado e, se ativo, aparece no site.

---

### US-3.4: Photos upload and ordering
**As a** Store admin
**I want to** enviar várias fotos e definir a ordem delas
**So that** mostrar o carro bem, com a melhor foto na capa

**Acceptance Criteria:**
- [ ] O upload aceita várias fotos de uma vez, enviadas pelo celular ou computador
- [ ] As fotos podem ser reordenadas e a 1ª é a capa
- [ ] Cada foto gera conversões WebP `thumb` (600px) e `large` (1600px) na hora do upload, sem fila (ver US-7.2)
- [ ] Arquivos que não são imagem são rejeitados com mensagem clara
- [ ] As fotos ficam no disk configurado (`s3`/R2; disco local em desenvolvimento)

**Expected Result:** O carro tem uma galeria ordenada, com a capa escolhida pelo admin.

---

### US-3.5: Edit a car
**As a** Store admin
**I want to** editar os dados de um carro
**So that** corrigir preço, descrição ou fotos

**Acceptance Criteria:**
- [ ] Todos os campos do cadastro podem ser alterados
- [ ] Adicionar, remover e reordenar fotos funciona na edição
- [ ] Alterações aparecem no site logo após salvar
- [ ] A prévia do card reflete o que está no formulário

**Expected Result:** O site passa a mostrar os dados atualizados do carro.

---

### US-3.6: Publish and hide a car
**As a** Store admin
**I want to** ligar e desligar um carro no site direto da lista
**So that** tirar o carro do site quando vender, sem excluí-lo

**Acceptance Criteria:**
- [ ] Há um interruptor **On site / Hidden** na lista e `Show on website` no formulário
- [ ] Não é possível ativar carro sem preço ou sem pelo menos uma foto, tanto no formulário quanto no interruptor da lista, com mensagem explicando o motivo
- [ ] Desligar o interruptor remove o carro do site e da contagem das marcas imediatamente
- [ ] Carro oculto continua na lista do admin com o selo `Hidden from site`
- [ ] Carro vendido não pode ser ligado no interruptor (mensagem pede para reabri-lo antes, ver US-8.2)
- [ ] Carros novos já nascem ativos (`is_active = true` por padrão) e aparecem no site assim que salvos

**Expected Result:** Só carros completos e ativos aparecem no site.

---

### US-3.7: Delete a car
**As a** Store admin
**I want to** excluir um carro
**So that** remover cadastros feitos por engano

**Acceptance Criteria:**
- [ ] Excluir pede confirmação antes de executar
- [ ] Após excluir, o carro e suas fotos somem da lista e do site
- [ ] Excluir não afeta a categoria do carro

**Expected Result:** O carro é removido de vez, só depois da confirmação.

---

## 4. Categories management (admin)

### US-4.1: Manage categories
**As a** Store admin
**I want to** adicionar e renomear categorias (marcas)
**So that** manter os botões Shop by make atualizados

**Acceptance Criteria:**
- [ ] A lista mostra o nome e a quantidade de carros de cada categoria
- [ ] É possível adicionar e renomear; o nome é obrigatório e único
- [ ] Renomear atualiza o nome nos carros e no site
- [ ] A contagem do admin considera todos os carros, a do site só os ativos

**Expected Result:** As categorias cadastradas alimentam o select do carro e os botões do site.

---

### US-4.2: Reorder categories
**As a** Store admin
**I want to** mudar a ordem das categorias com setas
**So that** controlar a ordem dos botões no site

**Acceptance Criteria:**
- [ ] Setas para cima/baixo alteram `sort_order`
- [ ] A ordem definida no admin é a ordem dos botões `Shop by make` no site (depois de `All`)
- [ ] Novas categorias entram no fim da lista

**Expected Result:** A ordem dos botões no site reflete a ordem escolhida no admin.

---

### US-4.3: Delete a category
**As a** Store admin
**I want to** excluir uma categoria sem carros
**So that** limpar categorias que não uso mais

**Acceptance Criteria:**
- [ ] Excluir pede confirmação
- [ ] Categoria que ainda tem carros (ativos ou ocultos) não pode ser excluída e mostra mensagem explicando
- [ ] A restrição existe também no banco (FK `restrict on delete`)

**Expected Result:** Só categorias vazias são removidas.

---

## 5. Store info (admin)

### US-5.1: Edit store information
**As a** Store admin
**I want to** editar WhatsApp, telefone, horários e endereço da loja
**So that** manter os dados de contato corretos sem depender de programador

**Acceptance Criteria:**
- [ ] Formulário único (sem lista) com WhatsApp number, Phone, hours (Mon–Fri, Saturday, Sunday, texto livre como `Closed`) e address (street, city, state, ZIP)
- [ ] WhatsApp aceita só dígitos com código do país (ex.: `19784174036`) e é obrigatório
- [ ] Os valores iniciais vêm do `SettingSeeder`: (978) 417-4036, Mon–Fri 9:00 AM – 6:00 PM, Saturday 9:00 AM – 4:00 PM, Sunday Closed, 1336 Merrimack Ave, Dracut, MA 01826
- [ ] Existe uma única linha de settings; o formulário edita sempre essa linha
- [ ] Ao salvar o cache `settings` é limpo

**Expected Result:** Os dados da loja ficam salvos e prontos para uso em todo o site.

---

### US-5.2: Store info propagates to the site
**As a** Visitor
**I want to** ver sempre telefone, horários, endereço e links de WhatsApp atualizados
**So that** contatar a loja pelos dados corretos

**Acceptance Criteria:**
- [ ] Header, `Visit the lot`, rodapé, mapa e todos os botões de WhatsApp usam os valores de Store info
- [ ] Após o admin salvar, a próxima visita ao site já mostra os novos valores (sem deploy)
- [ ] Nenhum telefone, horário ou endereço fica fixo no código ou no `.env`

**Expected Result:** Uma alteração em Store info muda todos os pontos de contato do site.

---

## 6. Legal, SEO and accessibility

### US-6.1: Legal pages
**As a** Visitor
**I want to** ler a Privacy Policy, os Terms of Use e a declaração de Accessibility
**So that** saber como a loja trata meus dados e como pedir ajuda

**Acceptance Criteria:**
- [ ] `GET /privacy`, `/terms` e `/accessibility` existem e estão linkadas no rodapé
- [ ] Os Terms citam a garantia de usados de Massachusetts
- [ ] A página Accessibility traz telefone e endereço para pedir ajuda
- [ ] Os textos são em inglês e marcados como pendentes de revisão jurídica do cliente

**Expected Result:** As três páginas legais abrem a partir do rodapé.

---

### US-6.2: SEO metadata and structured data
**As a** Visitor
**I want to** encontrar a loja e os carros no Google
**So that** chegar ao site pela busca

**Acceptance Criteria:**
- [ ] O `<title>` da home é `Used Cars in Dracut, MA | Mario's Auto Sales & Rentals` com meta description
- [ ] A home inclui Schema.org `AutoDealer` com nome, endereço, telefone e horários de Store info
- [ ] Cada página de carro inclui Schema.org `Car` com nome, preço, milhagem, câmbio, cor e imagem
- [ ] Fotos têm `alt` descritivo (ex.: `White 2018 Jeep Compass`)

**Expected Result:** As páginas têm metadados e dados estruturados válidos.

---

### US-6.3: Sitemap and robots
**As a** Visitor
**I want to** que o Google receba um sitemap atualizado
**So that** os carros ativos serem indexados

**Acceptance Criteria:**
- [ ] `GET /sitemap.xml` lista a home, os carros ativos e as páginas legais
- [ ] Carro oculto ou excluído não aparece no sitemap
- [ ] `robots.txt` existe e referencia o sitemap

**Expected Result:** O sitemap sempre reflete o estoque ativo.

---

### US-6.4: Keyboard and screen-reader access
**As a** Visitor
**I want to** usar o site só com teclado e leitor de tela
**So that** comprar sem barreiras de acessibilidade (WCAG 2.1 AA)

**Acceptance Criteria:**
- [ ] Todos os controles são `<button>` ou `<a>` reais com foco visível e área de toque de pelo menos 44px
- [ ] Botões só de ícone têm `aria-label`; fotos informativas têm `alt` e decorativas `alt=""`
- [ ] O modal prende o foco, fecha com `Esc` e devolve o foco ao card de origem
- [ ] Contraste de texto mínimo de 4.5:1

**Expected Result:** O site é navegável por teclado e compatível com leitores de tela.

---

### US-6.5: Reduced motion and light theme
**As a** Visitor
**I want to** que as animações sejam desligadas quando eu peço menos movimento
**So that** evitar desconforto

**Acceptance Criteria:**
- [ ] Com `prefers-reduced-motion: reduce` os efeitos de hover, zoom e transições são desativados
- [ ] O tema é claro fixo, sem modo escuro
- [ ] As cores e fontes seguem os tokens do mockup (Barlow Condensed e Barlow)

**Expected Result:** O site respeita a preferência de movimento e mantém a identidade visual do mockup.

---

## 7. Deployment and operations

### US-7.1: Production deploy
**As a** Store admin
**I want to** colocar o sistema no ar em produção
**So that** o cliente usar o site e o admin de verdade

**Acceptance Criteria:**
- [ ] O app roda no Laravel Cloud (ambiente `production`) com MySQL gerenciado e bucket de storage
- [ ] O deploy executa `php artisan migrate --force`, `php artisan optimize` e `php artisan filament:optimize`
- [ ] HTTPS ativo e `APP_URL` configurado; variáveis de ambiente documentadas no `.env.example`
- [ ] Migrations e seeders de produção (tabelas de apoio e `SettingSeeder`) rodam, sem categorias pré-cadastradas (o Mario as cria no admin); `DemoCarSeeder` só em ambiente local

**Expected Result:** O site e o admin funcionam em produção sobre HTTPS.

---

### US-7.2: Photo conversion on upload
**As a** Store admin
**I want to** que as fotos enviadas sejam otimizadas automaticamente ao subir
**So that** o site carregue rápido sem eu precisar redimensionar nada

**Acceptance Criteria:**
- [ ] As conversões WebP `thumb` (600px) e `large` (1600px) são geradas de forma síncrona durante o upload, sem fila, job ou worker
- [ ] A foto original é mantida e usada como alternativa se uma conversão estiver ausente
- [ ] Falha de conversão é registrada em log e não impede o upload nem derruba o site

**Expected Result:** Ao salvar o carro, as fotos já ficam disponíveis em WebP otimizado no site.

---

## 8. Sales and dashboard (admin)

### US-8.1: Mark a car as sold
**As a** Store admin
**I want to** marcar um carro como vendido com um toque
**So that** ele saia da vitrine e a venda conte no dashboard

**Acceptance Criteria:**
- [ ] A ação **Mark as sold** existe na lista e na edição do carro, com confirmação
- [ ] A confirmação traz data da venda (padrão: hoje, não pode ser futura) e valor da venda (padrão: preço anunciado, inteiro maior que zero), ambos editáveis
- [ ] Ao confirmar, grava `sold_at` e `sold_price` e define `is_active = false` na mesma operação
- [ ] O carro some do site, das contagens por marca (`Jeep 4`), do `Showing X of Y` e do sitemap imediatamente
- [ ] O carro continua na lista do admin com o selo `Sold` e não é excluído nem perde as fotos
- [ ] Acessar `/cars/{slug}` de um carro vendido retorna 404
- [ ] Marcar como vendido um carro já vendido não duplica a venda

**Expected Result:** O carro fica registrado como vendido, invisível ao público e contado no dashboard.

---

### US-8.2: Reopen a sold car
**As a** Store admin
**I want to** reabrir um carro marcado como vendido por engano
**So that** eu corrija o erro sem recadastrar o carro

**Acceptance Criteria:**
- [ ] A ação **Mark as available** aparece só em carros vendidos, com confirmação
- [ ] Ao confirmar, `sold_at` e `sold_price` são apagados e a venda deixa de contar no dashboard
- [ ] O carro continua oculto (`is_active = false`) até o admin ligar `Show on website`
- [ ] O selo `Sold` some da lista

**Expected Result:** O carro volta ao estoque oculto e a venda é desfeita nos números.

---

### US-8.3: Filter cars by status in the admin list
**As a** Store admin
**I want to** filtrar a lista de carros por status
**So that** eu veja só os vendidos, os do site ou os ocultos

**Acceptance Criteria:**
- [ ] A lista tem um filtro de status com `All`, `On site`, `Hidden` e `Sold`
- [ ] `On site` mostra carros ativos; `Hidden` mostra inativos não vendidos; `Sold` mostra carros com `sold_at`
- [ ] A lista mostra a data e o valor da venda nos carros vendidos
- [ ] O filtro combina com a busca por nome e o filtro por categoria
- [ ] Sem filtro, a lista mostra todos os carros

**Expected Result:** O admin encontra rapidamente os carros vendidos ou qualquer outro grupo.

---

### US-8.4: Monthly sales on the dashboard
**As a** Store admin
**I want to** ver as vendas do mês assim que entro no painel
**So that** eu acompanhe como a loja está indo

**Acceptance Criteria:**
- [ ] `/admin` abre no dashboard e mostra quantidade de carros vendidos e valor total (soma de `sold_price`) no mês-calendário atual, no fuso `America/New_York`
- [ ] Mostra a variação em relação ao mês anterior (quantidade e valor); sem vendas no mês anterior mostra `—` em vez de divisão por zero
- [ ] A venda entra no mês de `sold_at`, e não da data de cadastro do carro
- [ ] Marcar ou reabrir um carro atualiza os números sem ação manual
- [ ] Valores em USD formatados e legíveis no celular

**Expected Result:** O dashboard mostra corretamente quantos carros e quanto foi vendido no mês.

---

### US-8.5: Inventory and recent sales on the dashboard
**As a** Store admin
**I want to** ver o estoque atual e as últimas vendas no dashboard
**So that** eu tenha uma visão rápida da loja

**Acceptance Criteria:**
- [ ] Mostra a quantidade de carros `On site`, a de `Hidden` e o valor total dos carros no site
- [ ] Carros vendidos não entram na contagem de estoque
- [ ] Lista os 5 últimos carros vendidos (foto, nome, valor, data) em ordem de `sold_at` decrescente, cada um levando à edição do carro
- [ ] Sem vendas, a lista mostra uma mensagem de estado vazio

**Expected Result:** O dashboard resume o estoque e as vendas mais recentes.

---

### US-8.6: Sales chart for the last 6 months
**As a** Store admin
**I want to** ver um gráfico de vendas dos últimos 6 meses
**So that** eu compare o desempenho mês a mês

**Acceptance Criteria:**
- [ ] O gráfico mostra uma barra por mês (quantidade vendida) nos 6 últimos meses-calendário, incluindo o atual
- [ ] Meses sem venda aparecem com zero
- [ ] O gráfico é legível em tela de celular

**Expected Result:** O admin vê a evolução das vendas ao longo dos últimos meses.

---

## Appendix: User Story Status

| ID | Story | Priority | Status |
|----|-------|----------|--------|
| US-1.1 | Landing header and hero | High | Done |
| US-1.2 | Filter cars by make, body type and max price | High | Done |
| US-1.3 | Shop by make buttons | High | Done |
| US-1.4 | Load more cars (incremental pagination) | High | Done |
| US-2.1 | Car card | High | Done |
| US-2.2 | Car detail modal | High | Done |
| US-2.4 | Massachusetts used-vehicle warranty | High | Done |
| US-2.5 | Ask about a car on WhatsApp | High | Done |
| US-3.1 | Admin login | High | Done |
| US-3.2 | Cars list | High | Done |
| US-3.3 | Create a car | High | Done |
| US-3.4 | Photos upload and ordering | High | Done |
| US-3.5 | Edit a car | High | Done |
| US-3.6 | Publish and hide a car | High | Done |
| US-4.1 | Manage categories | High | Done |
| US-5.1 | Edit store information | High | Done |
| US-5.2 | Store info propagates to the site | High | Done |
| US-6.4 | Keyboard and screen-reader access | High | Done |
| US-7.1 | Production deploy | High | Pending |
| US-1.5 | Empty state when no car matches | Medium | Done |
| US-2.3 | Car page by slug | Medium | Done |
| US-2.6 | Trade-in offer request | Medium | Done |
| US-2.7 | Trust block and Visit the lot | Medium | Done |
| US-3.7 | Delete a car | Medium | Done |
| US-4.2 | Reorder categories | Medium | Done |
| US-4.3 | Delete a category | Medium | Done |
| US-6.1 | Legal pages | Medium | Done |
| US-6.2 | SEO metadata and structured data | Medium | Done |
| US-7.2 | Photo conversion on upload | Medium | Done |
| US-1.6 | Shareable filter URLs | Low | Done |
| US-6.3 | Sitemap and robots | Low | Done |
| US-6.5 | Reduced motion and light theme | Low | Done |
| US-8.1 | Mark a car as sold | High | Done |
| US-8.3 | Filter cars by status in the admin list | High | Done |
| US-8.4 | Monthly sales on the dashboard | High | Done |
| US-8.2 | Reopen a sold car | Medium | Done |
| US-8.5 | Inventory and recent sales on the dashboard | Medium | Done |
| US-8.6 | Sales chart for the last 6 months | Low | Done |
