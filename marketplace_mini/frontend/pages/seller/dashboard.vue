<script setup lang="ts">
definePageMeta({
  middleware: 'auth',
})

const authStore = useAuthStore()
const sellerService = useSellerService()
const router = useRouter()

// Check if user is a seller
if (authStore.user?.role !== 'seller') {
  router.push('/')
}

// State
const stats = ref({
  total_products: 0,
  total_orders: 0,
  total_revenue: '0.00',
})
const recentOrders = ref<any[]>([])
const loading = ref(true)

// Fetch dashboard stats
const fetchStats = async () => {
  loading.value = true
  try {
    const response: any = await sellerService.getDashboardStats()
    stats.value = response.stats
    recentOrders.value = response.recent_orders || []
  } catch (err: any) {
    console.error('Failed to fetch stats:', err)
  } finally {
    loading.value = false
  }
}

// Format date
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
  })
}

// Load stats on mount
onMounted(() => {
  fetchStats()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Seller Dashboard</h1>
        <p class="mt-2 text-gray-600">Welcome back, {{ authStore.user?.name }}!</p>
      </div>

      <!-- Loading Stats -->
      <div v-if="loading" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div v-for="i in 3" :key="i" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 animate-pulse">
          <div class="h-16 bg-gray-200 rounded" />
        </div>
      </div>

      <!-- Stats Cards -->
      <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Total Products</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total_products }}</p>
            </div>
            <div class="h-12 w-12 bg-emerald-100 rounded-lg flex items-center justify-center">
              <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Total Orders</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total_orders }}</p>
            </div>
            <div class="h-12 w-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Total Revenue</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">${{ stats.total_revenue }}</p>
            </div>
            <div class="h-12 w-12 bg-purple-100 rounded-lg flex items-center justify-center">
              <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <NuxtLink
            to="/seller/products/create"
            class="flex items-center gap-3 p-4 rounded-lg border-2 border-dashed border-gray-300 hover:border-emerald-500 hover:bg-emerald-50 transition-colors"
          >
            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span class="text-sm font-medium text-gray-700">Add Product</span>
          </NuxtLink>

          <NuxtLink
            to="/seller/products"
            class="flex items-center gap-3 p-4 rounded-lg border-2 border-dashed border-gray-300 hover:border-blue-500 hover:bg-blue-50 transition-colors"
          >
            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <span class="text-sm font-medium text-gray-700">Manage Products</span>
          </NuxtLink>

          <NuxtLink
            to="/seller/orders"
            class="flex items-center gap-3 p-4 rounded-lg border-2 border-dashed border-gray-300 hover:border-purple-500 hover:bg-purple-50 transition-colors"
          >
            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <span class="text-sm font-medium text-gray-700">View Orders</span>
          </NuxtLink>

          <NuxtLink
            to="/seller/coupons"
            class="flex items-center gap-3 p-4 rounded-lg border-2 border-dashed border-gray-300 hover:border-orange-500 hover:bg-orange-50 transition-colors"
          >
            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
            </svg>
            <span class="text-sm font-medium text-gray-700">Manage Coupons</span>
          </NuxtLink>
        </div>
      </div>

      <!-- Recent Orders -->
      <div v-if="!loading && recentOrders.length > 0" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-semibold text-gray-900">Recent Orders</h2>
          <NuxtLink to="/seller/orders" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">
            View All →
          </NuxtLink>
        </div>
        <div class="space-y-3">
          <div
            v-for="order in recentOrders"
            :key="order.id"
            class="flex items-center justify-between p-4 rounded-lg border border-gray-200 hover:bg-slate-50 transition-colors"
          >
            <div class="flex-1">
              <div class="flex items-center gap-3">
                <span class="font-semibold text-gray-900">Order #{{ order.id }}</span>
                <span class="text-sm text-gray-600">{{ order.user.name }}</span>
              </div>
              <p class="text-sm text-gray-500 mt-1">
                {{ order.order_items.length }} item(s) • {{ formatDate(order.created_at) }}
              </p>
            </div>
            <div class="text-right">
              <span
                :class="[
                  'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium',
                  order.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                  order.status === 'processing' ? 'bg-blue-100 text-blue-800' :
                  order.status === 'delivered' ? 'bg-green-100 text-green-800' :
                  'bg-gray-100 text-gray-800'
                ]"
              >
                {{ order.status }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
