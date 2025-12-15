# Implementation Plan: Marketplace Mini

## Goal
Build a vibrant, intuitive mini marketplace with Buyer and Seller portals using Nuxt 3 and Laravel 11.

## User Review Required
> [!IMPORTANT]
> **UI Overhaul**: The first step is to redefine the visual identity (colors, typography) in `unocss.config.ts`. This will affect all subsequent UI development.

## Proposed Changes

### Phase 1: Foundation & UI System
**Goal**: Establish a "premium" and vivid look, set up layouts.
- [x] **Design System**:
    - Update `unocss.config.ts` with a new color palette (e.g., Primary: Violet/Indigo, Secondary: Teal/Cyan, Accent: Amber).
    - Define typography (Inter/Outfit).
- [x] **Layouts**:
    - Refactor `AppHeader.vue` and `AppFooter.vue` to use the new design system.
    - Create `layouts/default.vue` (Buyer) and `layouts/seller.vue` (Seller).
- [x] **Home Page**:
    - Create `pages/index.vue` with a Hero section, Featured Products, and Categories.

### Phase 2: Authentication (Buyer & Seller)
**Goal**: Secure login/registration.
- [x] **Backend**:
    - Setup Sanctum.
    - Create `AuthController` (login, register, logout).
    - Define User roles (buyer, seller).
- [x] **Frontend**:
    - Create `pages/auth/login.vue` and `pages/auth/register.vue`.
    - Implement Auth Store (`stores/auth.ts`).
- [x] **Testing**:
    - Verify token generation and storage.
    - Test protected routes (middleware).

### Phase 3: Catalog & Search
**Goal**: Browse and find products.
- [x] **Backend**:
    - `Product` model & migration.
    - `ProductController` (index, show).
    - Search/Filter logic (price, category).
- [x] **Frontend**:
    - `pages/products/index.vue` (Grid view with filters).
    - `pages/products/[id].vue` (Product details).
    - Product Card component.
- [x] **Testing**:
    - Test pagination and filters.
    - Verify empty states.

### Phase 4: Cart & Checkout
**Goal**: Purchase flow.
- [x] **Backend**:
    - `Cart` logic (session or DB).
    - `Order` model & migration.
    - Stripe Payment Intent API.
- [x] **Frontend**:
    - `stores/cart.ts` (Add/Remove/Update).
    - `pages/cart.vue`.
    - `pages/checkout.vue` (Stripe Elements integration).
- [x] **Testing**:
    - Verify total calculation.
    - Test successful/failed payment flows.

### Phase 5: Seller Portal
**Goal**: Manage products.
- [ ] **Backend**:
    - Seller middleware.
    - Product CRUD APIs (create, update, delete).
    - Image upload handling.
- [ ] **Frontend**:
    - `pages/seller/dashboard.vue`.
    - `pages/seller/products/create.vue`.
    - `pages/seller/orders.vue`.
- [ ] **Testing**:
    - Verify unauthorized access is blocked.
    - Test image upload constraints.

### Phase 6: Reviews & Coupons
**Goal**: Engagement and retention.
- [ ] **Backend**:
    - `Review` model.
    - `Coupon` model and validation logic.
- [ ] **Frontend**:
    - Review section in Product Details.
    - Coupon input in Checkout.
- [ ] **Testing**:
    - Verify coupon validity and expiration.

## Verification Plan
### Automated Tests
- **Backend**: `php artisan test` (Feature tests for APIs).
- **Frontend**: Manual verification via browser (Nuxt).

### Manual Verification
- **UI Check**: Ensure responsiveness on Mobile/Desktop.
- **Flow Check**: Complete a full purchase cycle (Register -> Browse -> Add to Cart -> Checkout -> Seller View).
