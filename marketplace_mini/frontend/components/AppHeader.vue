<script setup lang="ts">
const authStore = useAuthStore()
const router = useRouter()

const cartItemCount = ref(3)
const searchQuery = ref('')
const isMenuOpen = ref(false)
const isUserMenuOpen = ref(false)
const userMenuRef = ref<HTMLElement | null>(null)

const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value
}

const toggleUserMenu = () => {
  isUserMenuOpen.value = !isUserMenuOpen.value
}

const handleSearch = () => {
  const query = searchQuery.value.trim()
  if (query) {
    // Navigate to products page with search query
    router.push({
      path: '/products',
      query: { search: query }
    })
    // Close mobile menu if open
    isMenuOpen.value = false
  }
}

const logout = async () => {
  await authStore.logout()
  isUserMenuOpen.value = false
}

const getUserInitial = computed(() => {
  return authStore.user?.name?.charAt(0).toUpperCase() || 'U'
})

// Close dropdown when clicking outside
const handleClickOutside = (event: MouseEvent) => {
  if (userMenuRef.value && !userMenuRef.value.contains(event.target as Node)) {
    isUserMenuOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <header class="fixed top-0 left-0 right-0 bg-white/80 backdrop-blur-md border-b border-gray-100 z-50 transition-all duration-300">
    <div class="container mx-auto px-4">
      <div class="flex items-center justify-between h-20">
        <!-- Mobile: Hamburger Menu (Left) -->
        <button
          class="md:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors"
          @click="toggleMenu"
          aria-label="Toggle menu"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <!-- Logo -->
        <NuxtLink
          to="/"
          class="font-display font-bold text-2xl md:text-3xl bg-gradient-to-r from-primary-600 to-secondary-500 bg-clip-text text-transparent absolute left-1/2 -translate-x-1/2 md:relative md:left-auto md:translate-x-0 no-underline hover:opacity-90 transition-opacity"
        >
          MarketMini
        </NuxtLink>

        <!-- Search Bar (Desktop) -->
        <div class="hidden md:flex flex-1 max-w-xl mx-12">
          <div class="relative w-full group">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search for products..."
              class="w-full pl-12 pr-4 py-3 rounded-full border border-solid border-gray-200 bg-gray-50 focus:bg-white focus:border-primary focus:ring-4 focus:ring-primary/10 outline-none transition-all duration-300"
              @keyup.enter="handleSearch"
            >
            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-primary transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 md:gap-6">
          <!-- Sell Button -->
          <NuxtLink
            to="/sell"
            class="hidden md:flex items-center gap-2 text-gray-600 hover:text-primary font-medium transition-colors no-underline"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Sell
          </NuxtLink>

          <!-- Cart -->
          <NuxtLink
            to="/cart"
            class="relative p-2 text-gray-600 hover:text-primary transition-colors no-underline"
            aria-label="Shopping cart"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
            <span
              v-if="cartItemCount > 0"
              class="absolute top-0 right-0 bg-accent text-white text-[10px] font-bold rounded-full h-5 w-5 flex items-center justify-center shadow-sm border-2 border-white"
            >
              {{ cartItemCount }}
            </span>
          </NuxtLink>

          <!-- Auth Section -->
          <div class="hidden sm:flex items-center gap-3">
            <!-- Guest: Show Login/Register -->
            <template v-if="!authStore.isAuthenticated">
              <NuxtLink
                to="/auth/login"
                class="text-gray-600 hover:text-primary font-medium transition-colors no-underline"
              >
                Login
              </NuxtLink>
              <NuxtLink
                to="/auth/register"
                class="btn btn-primary rounded-full px-6 shadow-lg shadow-primary/20 hover:shadow-primary/40 no-underline"
              >
                Register
              </NuxtLink>
            </template>

            <!-- Authenticated: Show User Profile -->
            <div v-else class="relative" ref="userMenuRef">
              <button
                @click="toggleUserMenu"
                class="flex items-center gap-3 px-4 py-2 rounded-full hover:bg-gray-100 transition-colors"
              >
                <div class="h-9 w-9 rounded-full bg-gradient-to-br from-primary-500 to-secondary-500 flex items-center justify-center text-white font-semibold text-sm shadow-md">
                  {{ getUserInitial }}
                </div>
                <span class="font-medium text-gray-700">{{ authStore.user?.name }}</span>
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-4 w-4 text-gray-500 transition-transform"
                  :class="{ 'rotate-180': isUserMenuOpen }"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <!-- Dropdown Menu -->
              <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
              >
                <div
                  v-if="isUserMenuOpen"
                  class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg ring-1 ring-black/5 py-2 z-50"
                >
                  <div class="px-4 py-3 border-b border-gray-100">
                    <p class="text-sm font-medium text-gray-900">{{ authStore.user?.name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ authStore.user?.email }}</p>
                  </div>

                  <div class="py-1">
                    <NuxtLink
                      to="/profile"
                      @click="isUserMenuOpen = false"
                      class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors no-underline"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                      My Profile
                    </NuxtLink>

                    <NuxtLink
                      to="/profile/orders"
                      @click="isUserMenuOpen = false"
                      class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors no-underline"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                      </svg>
                      My Orders
                    </NuxtLink>

                    <!-- Seller Dashboard (Only for Sellers) -->
                    <NuxtLink
                      v-if="authStore.user?.role === 'seller'"
                      to="/seller/dashboard"
                      @click="isUserMenuOpen = false"
                      class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors no-underline"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                      </svg>
                      Seller Dashboard
                    </NuxtLink>
                  </div>

                  <div class="border-t border-gray-100 py-1">
                    <button
                      @click="logout"
                      class="flex border-none bg-white items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors w-full text-left"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                      </svg>
                      Logout
                    </button>
                  </div>
                </div>
              </transition>
            </div>
          </div>
        </div>
      </div>

      <!-- Mobile Search -->
      <div class="md:hidden pb-4">
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search..."
            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all"
            @keyup.enter="handleSearch"
          >
          <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Mobile Menu -->
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="transform -translate-y-4 opacity-0"
        enter-to-class="transform translate-y-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="transform translate-y-0 opacity-100"
        leave-to-class="transform -translate-y-4 opacity-0"
      >
        <div
          v-if="isMenuOpen"
          class="md:hidden border-t border-gray-100 py-4 space-y-2"
        >
          <NuxtLink
            to="/sell"
            class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl transition-colors no-underline"
            @click="isMenuOpen = false"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Sell on MarketMini
          </NuxtLink>

          <div class="h-px bg-gray-100 my-2"></div>

          <!-- Guest Menu -->
          <template v-if="!authStore.isAuthenticated">
            <NuxtLink
              to="/auth/login"
              class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl transition-colors no-underline"
              @click="isMenuOpen = false"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
              </svg>
              Login
            </NuxtLink>
            <NuxtLink
              to="/auth/register"
              class="flex items-center gap-3 px-4 py-3 text-primary font-medium hover:bg-primary-50 rounded-xl transition-colors no-underline"
              @click="isMenuOpen = false"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
              </svg>
              Create Account
            </NuxtLink>
          </template>

          <!-- Authenticated Menu -->
          <template v-else>
            <!-- User Info -->
            <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 rounded-xl">
              <div class="h-10 w-10 rounded-full bg-gradient-to-br from-primary-500 to-secondary-500 flex items-center justify-center text-white font-semibold shadow-md">
                {{ getUserInitial }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ authStore.user?.name }}</p>
                <p class="text-xs text-gray-500 truncate">{{ authStore.user?.email }}</p>
              </div>
            </div>

            <NuxtLink
              to="/profile"
              class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl transition-colors no-underline"
              @click="isMenuOpen = false"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              My Profile
            </NuxtLink>

            <NuxtLink
              to="/profile/orders"
              class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl transition-colors no-underline"
              @click="isMenuOpen = false"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
              </svg>
              My Orders
            </NuxtLink>

            <!-- Seller Dashboard (Only for Sellers) -->
            <NuxtLink
              v-if="authStore.user?.role === 'seller'"
              to="/seller/dashboard"
              class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl transition-colors no-underline"
              @click="isMenuOpen = false"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              Seller Dashboard
            </NuxtLink>

            <div class="h-px bg-gray-100 my-2"></div>

            <button
              @click="logout"
              class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-xl transition-colors w-full text-left"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              Logout
            </button>
          </template>
        </div>
      </transition>
    </div>
  </header>
</template>
