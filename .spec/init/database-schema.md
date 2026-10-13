# Mario's Auto Sales & Rentals — Database Schema

<!-- inputs: project-description.md@sha256:8c1c79fdea95 user-stories.md@sha256:8a1a2a8530b3 -->

## Overview

O modelo é pequeno: **cars** é a entidade central, ligada a **categories** (marca), a **car_photos** (galeria ordenada) e a três tabelas de apoio (**body_types**, **transmissions**, **drivetrains**) que substituem os enums do README. **settings** guarda os dados da loja em uma única linha e **users** guarda os administradores do Filament. A venda é registrada no próprio carro (`sold_at`, `sold_price`), e o dashboard é calculado por consultas sobre `cars`, sem tabela de relatório.

Convenções (Laravel/Eloquent): tabelas no plural em snake_case, `id` bigint auto-incremento, FKs `<singular>_id`, `created_at`/`updated_at` nas tabelas de domínio. Campos categóricos usam tabelas de apoio, nunca enum. Fotos guardam o caminho do arquivo em coluna `file_path`. Carros usam exclusão definitiva (US-3.7), sem soft delete.

## Schema (DBML)

```dbml
// Lookup tables first, then domain tables.

Table body_types {
  id bigint [pk, increment]
  name varchar [unique, not null]
  slug varchar [unique, not null]
  sort_order integer [not null, default: 0]
  created_at timestamp
  updated_at timestamp
}

Table transmissions {
  id bigint [pk, increment]
  name varchar [unique, not null]
  slug varchar [unique, not null]
  sort_order integer [not null, default: 0]
  created_at timestamp
  updated_at timestamp
}

Table drivetrains {
  id bigint [pk, increment]
  name varchar [unique, not null]
  slug varchar [unique, not null]
  sort_order integer [not null, default: 0]
  created_at timestamp
  updated_at timestamp
}

Table categories {
  id bigint [pk, increment]
  name varchar [unique, not null]
  sort_order integer [not null, default: 0]
  created_at timestamp
  updated_at timestamp
}

Table users {
  id bigint [pk, increment]
  name varchar [not null]
  email varchar [unique, not null]
  password varchar [not null]
  remember_token varchar [null]
  created_at timestamp
  updated_at timestamp
}

Table cars {
  id bigint [pk, increment]
  category_id bigint [ref: > categories.id, not null]
  body_type_id bigint [ref: > body_types.id, not null]
  transmission_id bigint [ref: > transmissions.id, not null]
  drivetrain_id bigint [ref: > drivetrains.id, not null]
  title varchar [not null, note: 'ex.: 2019 Jeep Compass']
  trim varchar [null, note: 'ex.: Limited 4x4']
  slug varchar [unique, not null, note: 'title + trim + id']
  price unsigned_integer [null, note: 'USD sem centavos, ja com taxas; obrigatorio quando is_active']
  mileage unsigned_integer [null, note: 'milhas']
  color varchar [null]
  engine varchar [null]
  description text [null]
  is_active boolean [not null, default: true]
  price_drop boolean [not null, default: false]
  sold_at timestamp [null, note: 'preenchido = carro vendido']
  sold_price unsigned_integer [null, note: 'USD, padrao = price']
  created_at timestamp
  updated_at timestamp

  indexes {
    (is_active, category_id)
    (is_active, created_at)
    sold_at
  }
}

Table car_photos {
  id bigint [pk, increment]
  car_id bigint [ref: > cars.id, not null, note: 'cascade on delete']
  file_path varchar [not null]
  sort_order integer [not null, default: 0, note: 'menor = primeiro; a 1a e a capa']
  created_at timestamp
  updated_at timestamp

  indexes {
    (car_id, sort_order)
  }
}

Table settings {
  id bigint [pk, increment]
  whatsapp varchar [not null, note: 'somente digitos com codigo do pais, ex.: 19784174036']
  phone varchar [not null]
  street varchar [not null]
  city varchar [not null]
  state varchar [not null]
  zip varchar [not null]
  hours_mon_fri varchar [not null]
  hours_sat varchar [not null]
  hours_sun varchar [not null]
  created_at timestamp
  updated_at timestamp
}
```

## Relationships

- Uma **category** tem muitos **cars**; um **car** pertence a uma **category** (FK `restrict on delete`: categoria com carros, ativos, ocultos ou vendidos, não pode ser excluída).
- Um **car** pertence a um **body_type**, a uma **transmission** e a um **drivetrain** (FKs `restrict on delete`).
- Um **car** tem muitas **car_photos** (`cascade on delete`); a de menor `sort_order` é a capa.
- **settings** não se relaciona com outras tabelas e mantém uma única linha.
- **users** não se relaciona com outras tabelas (sem permissões nem autoria na v1).

## Lookup Table Seeds

- **body_types:** SUV (`suv`), Car (`car`), Truck (`truck`).
- **transmissions:** Automatic (`automatic`), Manual (`manual`).
- **drivetrains:** FWD (`fwd`), RWD (`rwd`), AWD (`awd`), 4WD (`4wd`).
- **categories:** sem seed em produção — o Mario cadastra as marcas no admin. Só o `DemoCarSeeder` (ambiente local) cria marcas de exemplo.
- **settings** (uma linha, `SettingSeeder`): whatsapp `19784174036`, phone `(978) 417-4036`, street `1336 Merrimack Ave`, city `Dracut`, state `MA`, zip `01826`, hours_mon_fri `9:00 AM – 6:00 PM`, hours_sat `9:00 AM – 4:00 PM`, hours_sun `Closed`.

## Notes & Conventions

- **Estado do carro é derivado, sem tabela de status:** `Sold` = `sold_at` preenchido; `On site` = `is_active = true`; `Hidden` = `is_active = false` e `sold_at` nulo. Marcar como vendido define `sold_at`, `sold_price` e `is_active = false` na mesma transação (US-8.1); reabrir apaga `sold_at`/`sold_price` (US-8.2). Carro vendido nunca pode ter `is_active = true` (validação na aplicação).
- **`is_active` nasce `true`** (decisão do cliente, US-3.6), então `price` e pelo menos uma foto são exigidos pela aplicação ao salvar ativo. Por isso `price` é nullable no banco: permite rascunho desativado (US-3.3).
- **Dinheiro e milhas** são inteiros sem centavos; formatação na view. O dashboard soma `sold_price` por mês de `sold_at` no fuso `America/New_York` (US-8.4 a 8.6); índice em `sold_at`.
- **Garantia de Massachusetts** não é persistida: calculada por `Car::maWarranty()` a partir de `mileage` (US-2.4).
- **Contagem por marca, paginação, mensagens de WhatsApp, slugs e dashboard** são derivados em runtime (consultas e accessors); nada é gravado além dos campos acima.
- **Cache de `settings`** (`Cache::rememberForever('settings')`) fica no cache do framework, não em tabela do domínio; é limpo ao salvar (US-5.1).
- **Fotos:** `car_photos.file_path` aponta para o arquivo no disk configurado (`s3`/R2; local em dev). As variantes `thumb` (600px) e `large` (1600px) são geradas de forma síncrona no upload, a partir do original (sem fila) (US-3.4, US-7.2).
- **Exclusão:** `cars` usa exclusão definitiva (US-3.7), sem `deleted_at`; excluir um carro vendido remove a venda dos números do dashboard, e a confirmação deve avisar.
- **Tabelas padrão do framework** (`password_reset_tokens`, `sessions`, `cache`) vêm das migrations do Laravel e não são modeladas aqui. Não há `jobs`/`failed_jobs`: o projeto não usa filas.
- **Itens do projeto sem dados no banco:** Publicação (regra sobre `cars.is_active`), Preço anunciado (regra sobre `cars.price`), Mensagem de WhatsApp e Paginação incremental (derivadas), e as decisões de idioma, fotos reais, dados iniciais, pasta, testes, vendas simples e repositório (não são dados).

## Open Questions

- **Fotos: `car_photos` ou Spatie Media Library?** O README previa `spatie/laravel-medialibrary` (tabela `media`, conversões prontas). Aqui segui a regra de manter o caminho do arquivo em uma tabela própria (`car_photos`). Se preferir manter o Spatie, `car_photos` é substituída pela tabela `media` do pacote e as conversões ficam automáticas.
