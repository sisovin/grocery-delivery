# Nourish Grocery Delivery

Comprehensive Prompt Engineering + Native PHP MVC implementation for **Nourish**, localized in Khmer and designed for delivery coverage across all 24 provinces/capital in Cambodia.

Documentation variants:

- Technical documentation (this file): [README.md](README.md)
- Khmer business documentation: [README.km.md](README.km.md)

This README is generated from GROCERY-DELIVERY.md and aligned with the current project tree.

## 1) Purpose

This project defines and operationalizes a Prompt Engineering framework for grocery content generation:

- Product description
- Banner copy
- Social caption
- Email content
- Push notification
- SMS-ready short copy

All outputs keep one consistent Nourish brand voice:

- Fresh
- Natural
- Trustworthy
- Modern
- Farm-to-home feeling

## 2) Brand Foundation (Khmer Localization)

### Persona
Creative AI assistant for the Nourish brand (fresh + organic grocery delivery platform).

### Brand Promise
- Free delivery on orders above $20
- Same-day delivery
- 100% secure payment

### Brand Keywords Pool
Each content output should include 2-3 keywords from:

- Nourish your home
- Earth's finest
- Farm-fresh
- Quality you can taste
- Convenience you deserve
- Local farms
- Seasonal
- Certified organic

## 3) Prompt Framework Contract

### Input Variables
- Product name
- Origin / highlight
- Deal (optional)

### Output Structure
1. Headline
   - Under 10 words
   - Should convey freshness, convenience, or natural quality
2. Descriptive Blurb
   - 50-80 words
   - Must include product name, origin/highlight, 2-3 brand keywords, sensory freshness language
   - Must mention: Organic, Free Delivery, Same-Day Delivery
3. Key Trust Points (3 points)
   - Quality pillar
   - Convenience pillar
   - Trust pillar
4. CTA
   - Must include: Nourish your home + Earth's finest
   - Mention deal when available

## 4) Coverage Area (24 Provinces/Capital)

ភ្នំពេញ, កណ្ដាល, កំពង់ធំ, កំពង់ឆ្នាំង, កំពង់ស្ពឺ, កំពង់ចាម, កំពត, កែប, កោះកុង, ក្រចេះ, មណ្ឌលគីរី, បាត់ដំបង, បន្ទាយមានជ័យ, ប៉ៃលិន, ព្រះវិហារ, ព្រៃវែង, ពោធិ៍សាត់, រតនគីរី, សៀមរាប, ស្ទឹងត្រែង, ស្វាយរៀង, តាកែវ, ត្បូងឃ្មុំ, ឧត្តរមានជ័យ

## 5) Tech Stack

- PHP 8.5+ (native MVC, no full framework)
- PDO MySQL
- HTML5
- Tailwind CSS 2.2.4 (CLI)
- Vanilla JavaScript

## 6) Implemented Architecture

- Front Controller: public/index.php
- Application Bootstrap: bootstrap/app.php
- Core Runtime: app/Core
- MVC Modules:
  - Controllers: app/Controllers
  - Models: app/Models
  - Repositories: app/Repositories
  - Services: app/Services
  - Views: app/Views
- Routes: routes/web.php
- SQL migrations/seeds: database/migrations, database/seeds
- CLI utility: bin/console

### Security Foundations Included
- Session bootstrap + secure cookie params
- CSRF token generation and validation
- PDO prepared statements

## 7) Current Routes

- GET /
- GET /products/{id}
- GET /admin
- GET /customer
- GET /supplier
- POST /api/prompt/generate

## 8) Quick Start

### Prerequisites
- PHP 8.5+
- MySQL / MariaDB
- Node.js + npm

### Install & Configure

1. Create env file:

```bash
copy .env.example .env
```

2. Update DB credentials in .env.

3. Install Node dependencies:

```bash
npm install
```

4. Build Tailwind CSS:

```bash
npm run build:css
```

5. Run database migration + seed:

```bash
php bin/console migrate
php bin/console seed
```

6. Serve application:

```bash
php bin/console serve
```

Open: http://127.0.0.1:8000

## 9) Prompt API Example

### Request

```http
POST /api/prompt/generate
Content-Type: application/x-www-form-urlencoded

product=ប្រអប់ប៉េងប៉ោះ Heirloom&origin=Johnson Family Farm&deal=Subscribe & Save 15%&_token=<csrf>
```

### Response (shape)

```json
{
  "headline": "...",
  "blurb": "...",
  "keywords": ["...", "...", "..."],
  "trustPoints": ["...", "...", "..."],
  "cta": "..."
}
```

## 10) Data & Knowledge Assets

- data/: UI/UX and product design intelligence datasets
- references/: architecture, auth, security, frontend, PDO/MySQL references
- templates/: four HTML reference templates (Home/Admin/Customer/Supplier)
- scripts/: Python tooling for search/design-system workflows

## 11) Project Tree (Synced)

```text
.
|-- app
|   |-- Controllers
|   |   |-- DashboardController.php
|   |   |-- HomeController.php
|   |   |-- ProductController.php
|   |   `-- PromptController.php
|   |-- Core
|   |   |-- App.php
|   |   |-- Controller.php
|   |   |-- Csrf.php
|   |   |-- Database.php
|   |   |-- Env.php
|   |   |-- helpers.php
|   |   |-- Request.php
|   |   |-- Response.php
|   |   |-- Router.php
|   |   |-- Session.php
|   |   `-- View.php
|   |-- Models
|   |   `-- Product.php
|   |-- Repositories
|   |   `-- ProductRepository.php
|   |-- Services
|   |   `-- PromptContentService.php
|   `-- Views
|       |-- dashboard
|       |   |-- admin.php
|       |   |-- customer.php
|       |   `-- supplier.php
|       |-- home
|       |   |-- index.php
|       |   `-- product-show.php
|       `-- layouts
|           `-- main.php
|-- bin
|   `-- console
|-- bootstrap
|   `-- app.php
|-- config
|   |-- app.php
|   |-- constants.php
|   |-- database.php
|   `-- env.php
|-- configs
|   `-- constants.php
|-- data
|   |-- stacks
|   |   |-- angular.csv
|   |   |-- astro.csv
|   |   |-- flutter.csv
|   |   |-- html-tailwind.csv
|   |   |-- jetpack-compose.csv
|   |   |-- laravel.csv
|   |   |-- nextjs.csv
|   |   |-- nuxt-ui.csv
|   |   |-- nuxtjs.csv
|   |   |-- react-native.csv
|   |   |-- react.csv
|   |   |-- shadcn.csv
|   |   |-- svelte.csv
|   |   |-- swiftui.csv
|   |   |-- threejs.csv
|   |   `-- vue.csv
|   |-- app-interface.csv
|   |-- charts.csv
|   |-- colors.csv
|   |-- design.csv
|   |-- draft.csv
|   |-- google-fonts.csv
|   |-- icons.csv
|   |-- landing.csv
|   |-- products.csv
|   |-- react-performance.csv
|   |-- styles.csv
|   |-- typography.csv
|   |-- ui-reasoning.csv
|   `-- ux-guidelines.csv
|-- database
|   |-- migrations
|   |   `-- 001_create_tables.sql
|   `-- seeds
|       `-- 001_products_seed.sql
|-- public
|   |-- assets
|   |   |-- css
|   |   |   `-- app.css
|   |   `-- js
|   |       `-- app.js
|   `-- index.php
|-- references
|   |-- architecture.md
|   |-- auth-schema.md
|   |-- authentication.md
|   |-- authorization.md
|   |-- frontend.md
|   |-- middleware.md
|   |-- pdo-mysql.md
|   |-- php-core.md
|   `-- security.md
|-- resources
|   `-- css
|       `-- app.css
|-- routes
|   `-- web.php
|-- scripts
|   |-- core.py
|   |-- design_system.py
|   |-- rg.cjs
|   |-- rg.cmd
|   |-- rg.ps1
|   `-- search.py
|-- storage
|   |-- cache
|   |   `-- .gitkeep
|   `-- logs
|       `-- .gitkeep
|-- templates
|   |-- 01-Home-Template-20260510.html
|   |-- 02-Admin-Template-20260510.html
|   |-- 03-Customer-Template-20260510.html
|   `-- 04-Supplierr-Template-20260510.html
|-- tests
|   `-- config
|       `-- env_constants_test.php
|-- .env
|-- .env.example
|-- .gitignore
|-- 01_SKILL.md
|-- 02_SKILL.md
|-- composer.json
|-- GROCERY-DELIVERY.md
|-- LICENSE
|-- package-lock.json
|-- package.json
|-- README.md
|-- SKILL.md
`-- tailwind.config.js
```

## 12) Production Content Rules

- Keep output clean, simple, and modern
- Avoid over-promotional wording
- Keep consistent Nourish brand voice in all channels
- Ensure prompt outputs are channel-ready for App/Web/Social/Push/SMS

## 13) License

MIT (see LICENSE)
