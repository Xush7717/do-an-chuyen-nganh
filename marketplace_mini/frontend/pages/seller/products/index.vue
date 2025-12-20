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
const products = ref<any[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const deletingId = ref<number | null>(null)

// Fetch products
const fetchProducts = async () => {
  loading.value = true
  error.value = null
  try {
    const response: any = await sellerService.getProducts()
    products.value = response.products.data || []
  } catch (err: any) {
    error.value = err.data?.message || 'Failed to fetch products'
  } finally {
    loading.value = false
  }
}

// Delete product with confirmation
const deleteProduct = async (id: number, name: string) => {
  if (!confirm(`Are you sure you want to delete "${name}"?`)) {
    return
  }

  deletingId.value = id
  try {
    await sellerService.deleteProduct(id)
    products.value = products.value.filter(p => p.id !== id)
  } catch (err: any) {
    alert(err.data?.message || 'Failed to delete product')
  } finally {
    deletingId.value = null
  }
}

// Format price
const formatPrice = (price: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(price)
}

// Handle image load error
const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  img.src = 'https://via.placeholder.com/640x480/e2e8f0/64748b?text=No+Image'
}

// Load products on mount
onMounted(() => {
  fetchProducts()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-8">
      <!-- Back to Dashboard -->
      <div class="mb-6">
        <NuxtLink to="/seller/dashboard" class="inline-flex items-center gap-2 text-gray-500 hover:text-emerald-600 transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m12 19-7-7 7-7"/>
            <path d="M19 12H5"/>
          </svg>
          <span class="font-medium">Back to Dashboard</span>
        </NuxtLink>
      </div>

      <!-- Header -->
      <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">My Products</h1>
          <p class="mt-2 text-gray-600">Manage your product listings</p>
        </div>
        <NuxtLink
          to="/seller/products/create"
          class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Add New Product
        </NuxtLink>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white rounded-xl shadow-sm p-12 border border-gray-100 text-center">
        <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-emerald-600 border-r-transparent" />
        <p class="mt-4 text-gray-600">Loading products...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-6">
        <p class="text-red-800">{{ error }}</p>
        <button @click="fetchProducts" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
          Try Again
        </button>
      </div>

      <!-- Empty State -->
      <div v-else-if="products.length === 0" class="bg-white rounded-xl shadow-sm p-12 border border-gray-100 text-center">
        <svg class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">No products yet</h3>
        <p class="text-gray-600 mb-6">Start by adding your first product to the marketplace</p>
        <NuxtLink
          to="/seller/products/create"
          class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Add Product
        </NuxtLink>
      </div>

      <!-- Products Table (Desktop) -->
      <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="hidden md:block overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-50 border-b border-gray-200">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Product</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Category</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Price</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Stock</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="product in products" :key="product.id" class="hover:bg-slate-50 transition-colors">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-4">
                    <img
                      :src="product.image_url || 'https://via.placeholder.com/640x480/e2e8f0/64748b?text=No+Image'"
                      :alt="product.name"
                      @error="handleImageError"
                      class="h-12 w-12 rounded-lg object-cover border border-gray-200"
                    >
                    <div>
                      <p class="font-medium text-gray-900">{{ product.name }}</p>
                      <p class="text-sm text-gray-500 line-clamp-1">{{ product.description }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ product.category?.name || 'N/A' }}
                  </span>
                </td>
                <td class="px-6 py-4 font-medium text-gray-900">
                  {{ formatPrice(product.price) }}
                </td>
                <td class="px-6 py-4">
                  <span
                    :class="[
                      'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium',
                      product.stock_quantity > 10 ? 'bg-green-100 text-green-800' :
                      product.stock_quantity > 0 ? 'bg-yellow-100 text-yellow-800' :
                      'bg-red-100 text-red-800'
                    ]"
                  >
                    {{ product.stock_quantity }} in stock
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <NuxtLink
                      :to="`/seller/products/edit/${product.id}`"
                      class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-700 hover:text-blue-900 hover:bg-blue-50 rounded-lg transition-colors"
                    >
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                      Edit
                    </NuxtLink>
                    <button
                      @click="deleteProduct(product.id, product.name)"
                      :disabled="deletingId === product.id"
                      class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-700 hover:text-red-900 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-50"
                    >
                      <svg v-if="deletingId === product.id" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Products Grid (Mobile) -->
        <div class="md:hidden divide-y divide-gray-200">
          <div v-for="product in products" :key="product.id" class="p-4">
            <div class="flex gap-4">
              <img
                :src="product.image_url || 'https://via.placeholder.com/640x480/e2e8f0/64748b?text=No+Image'"
                :alt="product.name"
                @error="handleImageError"
                class="h-20 w-20 rounded-lg object-cover border border-gray-200"
              >
              <div class="flex-1 min-w-0">
                <h3 class="font-medium text-gray-900 truncate">{{ product.name }}</h3>
                <p class="text-sm text-gray-500 mt-1">{{ product.category?.name || 'N/A' }}</p>
                <p class="text-lg font-semibold text-emerald-600 mt-2">{{ formatPrice(product.price) }}</p>
                <p class="text-sm text-gray-600 mt-1">Stock: {{ product.stock_quantity }}</p>
              </div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-2">
              <NuxtLink
                :to="`/seller/products/edit/${product.id}`"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-blue-700 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit
              </NuxtLink>
              <button
                @click="deleteProduct(product.id, product.name)"
                :disabled="deletingId === product.id"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-red-700 hover:text-red-900 bg-red-50 hover:bg-red-100 rounded-lg transition-colors disabled:opacity-50"
              >
                <svg v-if="deletingId === product.id" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
