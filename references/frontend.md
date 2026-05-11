# Frontend Reference — HTML5, Tailwind CSS 4.2.2, Vanilla JavaScript

## Table of Contents
1. [HTML5 Semantic Structure](#1-html5-semantic-structure)
2. [Tailwind CSS 4.2.2 — What's New & How to Use](#2-tailwind-css-422)
3. [Responsive Design Patterns](#3-responsive-design-patterns)
4. [Forms — HTML5 + Tailwind](#4-forms)
5. [Components — Pure HTML + Tailwind](#5-components)
6. [Vanilla JavaScript Patterns](#6-vanilla-javascript)
7. [Fetch API & AJAX](#7-fetch-api--ajax)
8. [DOM Manipulation Patterns](#8-dom-manipulation-patterns)
9. [PHP + HTML Templating](#9-php--html-templating)
10. [Performance](#10-performance)

---

## 1. HTML5 Semantic Structure

Always use semantic elements. Never use `<div>` where a semantic element applies.

```html
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Page description for SEO">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <title><?= htmlspecialchars($title ?? 'My App') ?></title>
    <!-- Tailwind CSS 4.2.2 via CDN -->
    <style>@import url('https://unpkg.com/tailwindcss@^4/dist/tailwind.css');</style>
    <!-- Or link compiled CSS -->
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased">

    <!-- Skip link — accessibility requirement -->
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50
              focus:px-4 focus:py-2 focus:bg-blue-600 focus:text-white focus:rounded-md">
        Skip to main content
    </a>

    <header class="bg-white border-b border-gray-200">
        <nav aria-label="Main navigation" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Navigation content -->
        </nav>
    </header>

    <main id="main-content" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page-specific content -->
        <article>
            <header><h1>Article Title</h1></header>
            <section aria-labelledby="section-heading">
                <h2 id="section-heading">Section Title</h2>
                <!-- Content -->
            </section>
        </article>
    </main>

    <footer class="bg-gray-900 text-white mt-auto">
        <!-- Footer content -->
    </footer>

    <script type="module" src="/assets/js/app.js"></script>
</body>
</html>
```

### Semantic element cheat sheet
```html
<!-- Structure -->
<header>     Site or section header
<nav>        Navigation links (use aria-label to differentiate multiple navs)
<main>       Primary content — only ONE per page
<article>    Self-contained content (blog post, card, comment)
<section>    Thematic group — use with a heading
<aside>      Sidebar, related content
<footer>     Site or section footer

<!-- Text -->
<h1>–<h6>   Headings — logical hierarchy, never skip levels
<p>          Paragraph
<figure>/<figcaption>  Image with caption
<blockquote cite="url"> Quoted content
<time datetime="2025-01-01"> Date/time
<address>    Contact information
<mark>       Highlighted text
<abbr title="Full text"> Abbreviation
<details>/<summary>  Expandable content (no JS required)
```

---

## 2. Tailwind CSS 4.2.2

### Installation options

**Option A — CDN (quick projects, prototypes)**
```html
<style>
/* In your <style> tag or CSS file */
@import url('https://unpkg.com/tailwindcss@^4/dist/tailwind.css');

/* Then add customizations below */
@theme {
    --color-brand: #6366f1;
    --font-sans: 'Inter', sans-serif;
}
</style>
```

**Option B — npm build (production)**
```bash
npm install tailwindcss@^4 @tailwindcss/cli
npx @tailwindcss/cli -i ./src/css/app.css -o ./public/assets/css/app.css --watch
```

```css
/* src/css/app.css */
@import "tailwindcss";

/* v4: CSS-first configuration — no tailwind.config.js needed */
@theme {
    /* Override or extend design tokens */
    --color-brand-50:  #eff6ff;
    --color-brand-500: #3b82f6;
    --color-brand-900: #1e3a8a;

    --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif;
    --font-mono: 'JetBrains Mono', ui-monospace, monospace;

    --radius-card: 0.75rem;
    --shadow-card: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}

/* Custom base styles */
@layer base {
    *, *::before, *::after { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body { font-family: var(--font-sans); }
    :focus-visible { outline: 2px solid var(--color-brand-500); outline-offset: 2px; }
}

/* Custom component classes */
@layer components {
    .btn {
        @apply inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg
               font-medium text-sm transition-all duration-150 focus:outline-none
               focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50
               disabled:cursor-not-allowed min-h-[44px];
    }
    .btn-primary {
        @apply btn bg-brand-500 text-white hover:bg-brand-600 focus-visible:ring-brand-500;
    }
    .btn-secondary {
        @apply btn bg-white text-gray-700 border border-gray-300 hover:bg-gray-50;
    }
    .card {
        @apply bg-white rounded-[--radius-card] shadow-[--shadow-card] overflow-hidden;
    }
    .form-input {
        @apply block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
               placeholder:text-gray-400 focus:border-brand-500 focus:ring-1
               focus:ring-brand-500 focus:outline-none;
    }
    .form-label {
        @apply block text-sm font-medium text-gray-700 mb-1;
    }
    .form-error {
        @apply mt-1 text-sm text-red-600;
    }
}

/* Custom utilities */
@utility text-balance {
    text-wrap: balance;
}
@utility scrollbar-hide {
    scrollbar-width: none;
    &::-webkit-scrollbar { display: none; }
}
```

### v4 changes from v3
| v3 | v4 |
|---|---|
| `tailwind.config.js` | `@theme {}` block in CSS |
| `@tailwind base/components/utilities` | `@import "tailwindcss"` |
| `theme.extend.colors` | `--color-*` CSS variables in `@theme` |
| `plugins: []` | `@utility` directive |
| `content: ['**/*.php']` | Auto-detected from imports |
| `text-shadow-*` (plugin) | Built-in `text-shadow-*` utilities |

---

## 3. Responsive Design Patterns

### Tailwind v4 breakpoints
```css
/* Default breakpoints (can be overridden in @theme) */
/* sm: 40rem (640px) | md: 48rem (768px) | lg: 64rem (1024px) | xl: 80rem (1280px) */
```

### Mobile-first layouts
```html
<!-- Stack on mobile, row on tablet+ -->
<div class="flex flex-col md:flex-row gap-4">
    <aside class="w-full md:w-64 lg:w-72 shrink-0">
        <!-- Sidebar -->
    </aside>
    <main class="flex-1 min-w-0"> <!-- min-w-0 prevents flex overflow -->
        <!-- Content -->
    </main>
</div>

<!-- Responsive grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
    <!-- Cards auto-fill grid -->
</div>

<!-- Full-screen layout -->
<body class="flex flex-col min-h-dvh">  <!-- dvh = dynamic viewport height -->
    <header class="shrink-0">...</header>
    <main class="flex-1">...</main>
    <footer class="shrink-0">...</footer>
</body>

<!-- Centered container -->
<div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">

<!-- Responsive navigation -->
<nav>
    <!-- Desktop links -->
    <ul class="hidden md:flex gap-6 items-center">...</ul>
    <!-- Mobile hamburger -->
    <button class="md:hidden p-2 min-h-[44px] min-w-[44px]"
            aria-label="Open menu" aria-expanded="false" aria-controls="mobile-menu">
        <!-- Hamburger icon SVG -->
    </button>
</nav>
<!-- Mobile menu (toggled by JS) -->
<div id="mobile-menu" class="hidden md:hidden">...</div>
```

---

## 4. Forms

### Full accessible form example
```html
<form method="POST" action="/users" class="space-y-6" novalidate>
    <!-- CSRF token — required on every POST form -->
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    <!-- Text input -->
    <div>
        <label for="name" class="form-label">
            Full Name <span class="text-red-500" aria-label="required">*</span>
        </label>
        <input type="text" id="name" name="name"
               value="<?= htmlspecialchars($old['name'] ?? '') ?>"
               autocomplete="name"
               required
               aria-describedby="name-error"
               aria-invalid="<?= isset($errors['name']) ? 'true' : 'false' ?>"
               class="form-input <?= isset($errors['name']) ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : '' ?>">
        <?php if (isset($errors['name'])): ?>
            <p id="name-error" role="alert" class="form-error">
                <?= htmlspecialchars($errors['name']) ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Email input -->
    <div>
        <label for="email" class="form-label">Email Address <span class="text-red-500">*</span></label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($old['email'] ?? '') ?>"
               autocomplete="email"
               required
               aria-describedby="<?= isset($errors['email']) ? 'email-error' : '' ?>"
               class="form-input <?= isset($errors['email']) ? 'border-red-500' : '' ?>">
        <?php if (isset($errors['email'])): ?>
            <p id="email-error" role="alert" class="form-error"><?= htmlspecialchars($errors['email']) ?></p>
        <?php endif; ?>
    </div>

    <!-- Select -->
    <div>
        <label for="role" class="form-label">Role</label>
        <select id="role" name="role" class="form-input">
            <?php foreach (App\Enums\UserRole::cases() as $role): ?>
                <option value="<?= $role->value ?>"
                    <?= ($old['role'] ?? '') === $role->value ? 'selected' : '' ?>>
                    <?= ucfirst($role->value) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Checkbox -->
    <div class="flex items-start gap-3">
        <input type="checkbox" id="terms" name="terms" value="1"
               class="mt-1 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
               required>
        <label for="terms" class="text-sm text-gray-700">
            I agree to the <a href="/terms" class="text-brand-500 hover:underline">Terms of Service</a>
        </label>
    </div>

    <!-- File upload -->
    <div>
        <label for="avatar" class="form-label">Avatar (optional)</label>
        <input type="file" id="avatar" name="avatar"
               accept="image/jpeg,image/png,image/webp"
               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4
                      file:rounded-lg file:border-0 file:text-sm file:font-medium
                      file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
        <p class="mt-1 text-xs text-gray-500">JPG, PNG, or WebP. Max 2MB.</p>
    </div>

    <!-- Submit -->
    <div class="flex items-center justify-end gap-4">
        <a href="/users" class="btn-secondary">Cancel</a>
        <button type="submit" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Create User
        </button>
    </div>
</form>
```

---

## 5. Components

### Flash messages / alerts
```html
<?php foreach (['success', 'error', 'warning', 'info'] as $type):
    if (!isset($_SESSION['flash'][$type])) continue;
    $classes = match($type) {
        'success' => 'bg-green-50 border-green-400 text-green-800',
        'error'   => 'bg-red-50 border-red-400 text-red-800',
        'warning' => 'bg-yellow-50 border-yellow-400 text-yellow-800',
        'info'    => 'bg-blue-50 border-blue-400 text-blue-800',
    };
?>
    <div role="alert" class="border-l-4 p-4 rounded-r-lg <?= $classes ?>">
        <?= htmlspecialchars($_SESSION['flash'][$type]) ?>
    </div>
<?php unset($_SESSION['flash'][$type]); endforeach; ?>
```

### Data table
```html
<div class="overflow-x-auto rounded-lg border border-gray-200">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($users as $user): ?>
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-semibold text-sm" aria-hidden="true">
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        </div>
                        <span class="font-medium text-gray-900"><?= htmlspecialchars($user['name']) ?></span>
                    </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($user['email']) ?></td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        <?= $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' ?>">
                        <?= htmlspecialchars($user['role']) ?>
                    </span>
                </td>
                <td class="px-6 py-4 text-right text-sm">
                    <a href="/users/<?= $user['id'] ?>/edit" class="text-brand-600 hover:text-brand-700 font-medium">Edit</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

---

## 6. Vanilla JavaScript Patterns

### ES Module structure
```js
// public/assets/js/app.js
import { initNavigation } from './modules/navigation.js';
import { initForms } from './modules/forms.js';
import { Toast } from './modules/toast.js';

document.addEventListener('DOMContentLoaded', () => {
    initNavigation();
    initForms();
});

export { Toast };
```

```js
// public/assets/js/modules/navigation.js
export function initNavigation() {
    const toggleBtn = document.querySelector('[aria-controls="mobile-menu"]');
    const menu      = document.getElementById('mobile-menu');
    if (!toggleBtn || !menu) return;

    toggleBtn.addEventListener('click', () => {
        const isOpen = toggleBtn.getAttribute('aria-expanded') === 'true';
        toggleBtn.setAttribute('aria-expanded', String(!isOpen));
        menu.classList.toggle('hidden');
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!toggleBtn.contains(e.target) && !menu.contains(e.target)) {
            toggleBtn.setAttribute('aria-expanded', 'false');
            menu.classList.add('hidden');
        }
    });
}
```

---

## 7. Fetch API & AJAX

### Base fetch helper with CSRF
```js
// public/assets/js/modules/api.js
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

async function apiFetch(url, options = {}) {
    const controller = new AbortController();
    const timeout    = setTimeout(() => controller.abort(), 10_000); // 10s timeout

    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-Token': csrfToken,
                ...options.headers,
            },
            signal: controller.signal,
            ...options,
        });

        clearTimeout(timeout);

        if (!response.ok) {
            const error = await response.json().catch(() => ({ error: 'Request failed' }));
            throw new ApiError(error.error ?? 'Request failed', response.status, error.errors);
        }

        return response.status === 204 ? null : response.json();

    } catch (err) {
        clearTimeout(timeout);
        if (err.name === 'AbortError') throw new Error('Request timed out');
        throw err;
    }
}

class ApiError extends Error {
    constructor(message, status, errors = {}) {
        super(message);
        this.status = status;
        this.errors = errors;
    }
}

export const api = {
    get:    (url)         => apiFetch(url),
    post:   (url, data)   => apiFetch(url, { method: 'POST',   body: JSON.stringify(data) }),
    put:    (url, data)   => apiFetch(url, { method: 'PUT',    body: JSON.stringify(data) }),
    patch:  (url, data)   => apiFetch(url, { method: 'PATCH',  body: JSON.stringify(data) }),
    delete: (url)         => apiFetch(url, { method: 'DELETE' }),
};

// Form data (for file uploads)
export async function postForm(url, formData) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrfToken, 'Accept': 'application/json' },
        body: formData,  // Don't set Content-Type — browser sets multipart boundary
    });
    if (!response.ok) throw new Error('Upload failed');
    return response.json();
}
```

### AJAX form submission
```js
// Progressively enhance a form with AJAX
export function initForms() {
    document.querySelectorAll('[data-ajax-form]').forEach(attachAjaxForm);
}

function attachAjaxForm(form) {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = form.querySelector('[type="submit"]');
        const originalText = btn.textContent;

        // Loading state
        btn.disabled = true;
        btn.innerHTML = '<span class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span> Saving...';
        clearErrors(form);

        try {
            const method = (form.dataset.method ?? form.method).toUpperCase();
            const url    = form.action;
            const body   = form.enctype === 'multipart/form-data'
                ? new FormData(form)
                : JSON.stringify(Object.fromEntries(new FormData(form)));

            const data = await apiFetch(url, { method, body,
                headers: form.enctype === 'multipart/form-data' ? {} : { 'Content-Type': 'application/json' }
            });

            form.dispatchEvent(new CustomEvent('ajax:success', { detail: data, bubbles: true }));
            Toast.success(data?.message ?? 'Saved successfully');

            if (data?.redirect) window.location.href = data.redirect;

        } catch (err) {
            if (err instanceof ApiError && err.errors) {
                displayErrors(form, err.errors);
            }
            Toast.error(err.message ?? 'Something went wrong');
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    });
}

function clearErrors(form) {
    form.querySelectorAll('[data-error]').forEach(el => { el.textContent = ''; el.hidden = true; });
    form.querySelectorAll('[aria-invalid]').forEach(el => el.removeAttribute('aria-invalid'));
}

function displayErrors(form, errors) {
    for (const [field, messages] of Object.entries(errors)) {
        const input = form.querySelector(`[name="${field}"]`);
        const errorEl = form.querySelector(`[data-error="${field}"]`);
        if (input)   { input.setAttribute('aria-invalid', 'true'); }
        if (errorEl) { errorEl.textContent = Array.isArray(messages) ? messages[0] : messages; errorEl.hidden = false; }
    }
    // Focus first error
    form.querySelector('[aria-invalid="true"]')?.focus();
}
```

### Toast notifications
```js
// public/assets/js/modules/toast.js
export class Toast {
    static #container = null;

    static #getContainer() {
        if (!this.#container) {
            this.#container = Object.assign(document.createElement('div'), {
                className: 'fixed bottom-4 right-4 z-50 flex flex-col gap-2',
                'aria-live': 'polite',
                'aria-atomic': 'false',
            });
            document.body.appendChild(this.#container);
        }
        return this.#container;
    }

    static show(message, type = 'info', duration = 4000) {
        const colors = {
            success: 'bg-green-600',
            error:   'bg-red-600',
            warning: 'bg-yellow-500',
            info:    'bg-blue-600',
        };

        const toast = document.createElement('div');
        toast.role = 'status';
        toast.className = `${colors[type] ?? colors.info} text-white px-4 py-3 rounded-lg shadow-lg
                           text-sm font-medium max-w-sm flex items-center justify-between gap-3
                           transform translate-y-2 opacity-0 transition-all duration-300`;
        toast.innerHTML = `<span>${message}</span>
            <button onclick="this.parentElement.remove()" class="shrink-0 text-white/70 hover:text-white" aria-label="Dismiss">✕</button>`;

        this.#getContainer().appendChild(toast);
        requestAnimationFrame(() => toast.classList.replace('opacity-0', 'opacity-100'));
        setTimeout(() => { toast.classList.replace('opacity-100', 'opacity-0'); setTimeout(() => toast.remove(), 300); }, duration);
    }

    static success(msg, dur) { this.show(msg, 'success', dur); }
    static error(msg, dur)   { this.show(msg, 'error', dur); }
    static warning(msg, dur) { this.show(msg, 'warning', dur); }
    static info(msg, dur)    { this.show(msg, 'info', dur); }
}
```

---

## 8. DOM Manipulation Patterns

```js
// Prefer data attributes for JS hooks — not class names
// <button data-action="delete" data-id="42" data-confirm="Are you sure?">Delete</button>
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;

    const { action, id, confirm: confirmMsg } = btn.dataset;

    if (confirmMsg && !window.confirm(confirmMsg)) return;

    switch (action) {
        case 'delete':
            await handleDelete(id);
            break;
        case 'toggle-status':
            await handleToggle(id, btn);
            break;
    }
});

// Infinite scroll with IntersectionObserver
function initInfiniteScroll(loadMore) {
    const sentinel = document.getElementById('scroll-sentinel');
    if (!sentinel) return;

    let loading = false;
    const observer = new IntersectionObserver(async ([entry]) => {
        if (!entry.isIntersecting || loading) return;
        loading = true;
        await loadMore();
        loading = false;
    }, { rootMargin: '200px' });

    observer.observe(sentinel);
    return () => observer.disconnect();
}

// Debounce for search inputs
function debounce(fn, delay = 300) {
    let timer;
    return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), delay); };
}

document.getElementById('search')?.addEventListener('input', debounce(async (e) => {
    const results = await api.get(`/api/search?q=${encodeURIComponent(e.target.value)}`);
    renderResults(results);
}));
```

---

## 9. PHP + HTML Templating

### Reusable PHP view includes
```php
<?php
// src/Views/layout.php — base layout
function renderLayout(string $title, callable $content, array $data = []): void
{
    extract($data, EXTR_SKIP);
    include __DIR__ . '/partials/head.php';
    include __DIR__ . '/partials/nav.php';
    $content();
    include __DIR__ . '/partials/footer.php';
}

// Output buffering for complex views
function view(string $template, array $data = []): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    include __DIR__ . "/../../src/Views/$template.php";
    return ob_get_clean();
}

// Safe output helper — use everywhere for user-supplied data
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
```

```html
<!-- In templates, always use e() or htmlspecialchars() -->

<!-- ❌ XSS vulnerability -->
<h1><?= $user['name'] ?></h1>

<!-- ✅ Safe output -->
<h1><?= e($user['name']) ?></h1>

<!-- ✅ URLs -->
<a href="/users/<?= (int) $user['id'] ?>">Profile</a>

<!-- ✅ JSON for JS — safe encoding -->
<script>
    const user = <?= json_encode($user, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
```

---

## 10. Performance

```html
<!-- Critical CSS inline, rest deferred -->
<style>/* inline critical path CSS */</style>
<link rel="preload" href="/assets/css/app.css" as="style" onload="this.onload=null;this.rel='stylesheet'">

<!-- Images: always width/height to prevent layout shift (CLS) -->
<img src="/uploads/photo.jpg" alt="Description" width="800" height="600"
     class="w-full h-auto" loading="lazy" decoding="async">

<!-- Preconnect to external domains -->
<link rel="preconnect" href="https://fonts.googleapis.com">

<!-- JS: defer non-critical, module = deferred by default -->
<script type="module" src="/assets/js/app.js"></script>
<script defer src="/assets/js/analytics.js"></script>
```

```js
// Cache API responses with Map
const cache = new Map();
async function cachedFetch(url, ttlMs = 60_000) {
    const hit = cache.get(url);
    if (hit && Date.now() - hit.ts < ttlMs) return hit.data;
    const data = await api.get(url);
    cache.set(url, { data, ts: Date.now() });
    return data;
}
```