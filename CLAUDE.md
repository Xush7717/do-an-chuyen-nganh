# CLAUDE.md

This file provides context, strict guidelines, and architectural rules for Claude Code when working on the "Marketplace Mini" repository.
 
## 1. Project Context & Environment

- **Project Name:** Marketplace Mini (Monorepo)
- **Architecture:** Decoupled Backend (Laravel API) + Frontend (Nuxt 3 SPA).
- **OS Environment:** **Windows Native (Laragon)**.
    - ⚠️ **CRITICAL:** `pcntl` and `posix` extensions are **NOT available**.
    - **Performance:** We have DISABLED Laravel Octane/RoadRunner due to Windows compatibility issues.
- **Server Strategy:**
    - **Development:** ALWAYS use `php artisan serve` (Standard PHP Server).
    - **Frontend:** Use `npm run dev`.

## 2. Tech Stack & Preferences (STRICT)

### Backend (Laravel 11)
- **Core:** PHP 8.2+, Laravel 11.x, Eloquent ORM.
- **Auth:** Laravel Sanctum (SPA Authentication).
- **Features:**
    - **Laravel Folio:** File-based routing (admin/static pages).
    - **Livewire Volt:** Functional API components.
    - **Stripe PHP:** Payment processing service.
- **Database:**
    - **MySQL** (Host: `127.0.0.1`, Port: `3306`, DB: `marketplace_db`).
    - **Redis** (Host: `127.0.0.1`, Port: `6379`).
- **Coding Style:** Service-Repository Pattern.
    - Logic -> `app/Services/`
    - DB Queries -> `app/Repositories/`
    - Constants -> `app/Enums/`

### Frontend (Nuxt 3)
- **Core:** Nuxt 3.x, Vue 3 (Composition API `<script setup>` **ONLY**).
- **State Management:** Pinia (Stores located in `stores/`).
- **Styling:** **UnoCSS** acting as Tailwind CSS.
    - **Rule:** Use standard Tailwind utility classes (e.g., `flex`, `p-4`, `text-emerald-600`).
    - **Responsiveness:** **Mobile-first approach**. Use `md:`, `lg:` modifiers for desktop.
    - **Theme:** Primary Color: Emerald (`emerald-500/600`). Backgrounds: Slate (`slate-50/100`).
- **Build Tool:** **Vite 5.x** (Strictly pinned).

## 3. Project Structure Map

```text
marketplace_mini/
├── backend/                  # Laravel 11 API
│   ├── app/
│   │   ├── Enums/            # Status, Roles
│   │   ├── Http/Controllers/ # API Controllers
│   │   ├── Models/           # Eloquent Models
│   │   ├── Services/         # Business Logic
│   │   └── Repositories/     # Data Access Layer
│   ├── config/
│   ├── database/migrations/
│   └── routes/
│
└── frontend/                 # Nuxt 3 Client
    ├── components/           # Reusable Vue components
    │   ├── Common/           # Buttons, Inputs
    │   └── Marketplace/      # ProductCard, Cart
    ├── layouts/              # App layouts
    ├── pages/                # File-based routing
    ├── stores/               # Pinia stores
    ├── unocss.config.ts      # UnoCSS Configuration
    └── package.json          # Dependencies
```
## 4. Development Workflow & Commands

### Backend Setup (Windows)
```bash
cd backend
# 1. Start Server (Standard)
php artisan serve
# (Server runs at [http://127.0.0.1:8000](http://127.0.0.1:8000))

# 2. Database Updates
php artisan migrate

# 3. Testing
php artisan test
```

### Frontend Setup
```bash
cd frontend
# 1. Start Dev Server
npm run dev
# (Client runs at http://localhost:3000)
```

## 5. Definition of Done (Checklist)

Before marking a task as **COMPLETED**, you must verify:

1.  **Backend Integrity:**
    -   Run `vendor/bin/pint` to format PHP code.
    -   Ensure API responses return correct JSON structure.
    -   **No Octane/RoadRunner references** in the new code (Stick to standard PHP).

2.  **Frontend Quality:**
    -   **Mobile Check:** Ensure layout does not break on small screens (`< 640px`).
    -   **Type Safety:** No TypeScript errors in the console.
    -   **Mock Data:** If API is not connected yet, use `ref` with dummy data to demonstrate UI functionality.

3.  **Code Style:**
    -   Vue components use `<script setup lang="ts">`.
    -   **Tailwind First:** No custom CSS in `<style>` tags unless Tailwind utility classes cannot handle the specific design.

