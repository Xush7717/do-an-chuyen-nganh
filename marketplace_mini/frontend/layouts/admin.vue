<script setup lang="ts">
const authStore = useAuthStore()
const router = useRouter()

const handleLogout = async () => {
  await authStore.logout()
}

// Navigation items
const navItems = [
  { name: 'Dashboard', path: '/admin/dashboard', icon: 'carbon:dashboard' },
  { name: 'Categories', path: '/admin/categories', icon: 'carbon:category' },
  { name: 'Users', path: '/admin/users', icon: 'carbon:user-multiple' },
  { name: 'Products', path: '/admin/products', icon: 'carbon:shopping-bag' },
]
</script>

<template>
  <div class="min-h-screen bg-slate-100">
    <!-- Sidebar -->
    <aside class="fixed left-0 top-0 h-screen w-64 bg-white border-r border-gray-200 flex flex-col z-50">
      <!-- Logo/Brand -->
      <div class="h-16 flex items-center px-6 border-b border-gray-200">
        <h1 class="text-xl font-bold bg-gradient-to-r from-violet-600 to-purple-600 bg-clip-text text-transparent">
          MarketMini Admin
        </h1>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
        <NuxtLink
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-violet-50 hover:text-violet-600 transition-all duration-200 no-underline"
          active-class="bg-violet-100 text-violet-700 font-semibold"
        >
          <Icon :icon="item.icon" class="text-xl" />
          <span>{{ item.name }}</span>
        </NuxtLink>
      </nav>

      <!-- User Section -->
      <div class="p-4 border-t border-gray-200">
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-50">
          <div class="h-10 w-10 rounded-full bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-white font-semibold">
            {{ authStore.user?.name?.charAt(0).toUpperCase() || 'A' }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900 truncate">{{ authStore.user?.name }}</p>
            <p class="text-xs text-gray-500 truncate">{{ authStore.user?.email }}</p>
          </div>
        </div>
        <button
          @click="handleLogout"
          class="mt-2 w-full flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 transition-colors duration-200 text-sm font-medium"
        >
          <Icon icon="carbon:logout" class="text-lg" />
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="pl-64">
      <!-- Top Header -->
      <header class="h-16 bg-white border-b border-gray-200 px-8 flex items-center justify-between sticky top-0 z-40">
        <div>
          <h2 class="text-xl font-semibold text-gray-900">{{ $route.meta.title || 'Admin Panel' }}</h2>
        </div>
        <div class="flex items-center gap-4">
          <span class="text-sm text-gray-600">
            {{ new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
          </span>
        </div>
      </header>

      <!-- Page Content -->
      <main class="p-8">
        <slot />
      </main>
    </div>
  </div>
</template>
