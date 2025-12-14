<script setup lang="ts">
definePageMeta({
  layout: 'default',
})

// Get product ID from route params
const route = useRoute()
const productId = route.params.id as string

// Fetch product details
const config = useRuntimeConfig()
const loading = ref(true)
const error = ref(false)
const productData = ref<any>(null)

try {
  const response = await $fetch(`${config.public.apiBase}/products/${productId}`)
  productData.value = response
  error.value = false
} catch (e) {
  console.error('Error fetching product:', e)
  error.value = true
} finally {
  loading.value = false
}

// Extract product from response
const product = computed(() => productData.value?.data)

// Helper computed properties
const imageUrl = computed(() => {
  return product.value?.image_url || 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&h=800&fit=crop'
})

const sellerName = computed(() => {
  return product.value?.seller?.seller?.shop_name || product.value?.seller?.name || 'Unknown Seller'
})

const categoryName = computed(() => {
  return product.value?.category?.name || 'General'
})

const inStock = computed(() => {
  return (product.value?.stock_quantity || 0) > 0
})

// Breadcrumb
const breadcrumb = computed(() => [
  { label: 'Home', to: '/' },
  { label: 'Products', to: '/products' },
  { label: product.value?.name || 'Product Details', to: null },
])
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <div class="container mx-auto px-4 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-violet-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
        <svg class="mx-auto h-16 w-16 text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">Product Not Found</h3>
        <p class="text-gray-600 mb-4">The product you're looking for doesn't exist or has been removed.</p>
        <NuxtLink
          to="/products"
          class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white font-medium px-6 py-3 rounded-lg transition-colors no-underline"
        >
          Browse Products
        </NuxtLink>
      </div>

      <!-- Product Details -->
      <div v-else-if="product">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm mb-6">
          <template v-for="(crumb, index) in breadcrumb" :key="index">
            <NuxtLink
              v-if="crumb.to"
              :to="crumb.to"
              class="text-gray-600 hover:text-violet-600 transition-colors no-underline"
            >
              {{ crumb.label }}
            </NuxtLink>
            <span v-else class="text-gray-900 font-medium">{{ crumb.label }}</span>
            <svg
              v-if="index < breadcrumb.length - 1"
              class="h-4 w-4 text-gray-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </template>
        </nav>

        <!-- Product Content -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-12">
          <!-- Product Image -->
          <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="aspect-square">
              <img
                :src="imageUrl"
                :alt="product.name"
                class="w-full h-full object-cover"
              >
            </div>
          </div>

          <!-- Product Info -->
          <div class="space-y-6">
            <!-- Category Badge -->
            <div>
              <NuxtLink
                :to="`/products?category_id=${product.category_id}`"
                class="inline-flex items-center gap-2 bg-violet-100 text-violet-700 px-4 py-2 rounded-full text-sm font-medium hover:bg-violet-200 transition-colors no-underline"
              >
                <Icon :icon="product.category?.icon_class || 'carbon:category'" class="text-lg" />
                {{ categoryName }}
              </NuxtLink>
            </div>

            <!-- Product Name -->
            <h1 class="text-4xl font-bold text-gray-900">
              {{ product.name }}
            </h1>

            <!-- Price -->
            <div class="flex items-baseline gap-3">
              <span class="text-5xl font-bold text-gray-900">
                ${{ Number(product.price).toFixed(2) }}
              </span>
            </div>

            <!-- Stock Status -->
            <div class="flex items-center gap-2">
              <div
                :class="[
                  'h-3 w-3 rounded-full',
                  inStock ? 'bg-green-500' : 'bg-red-500'
                ]"
              ></div>
              <span :class="[
                'font-medium',
                inStock ? 'text-green-700' : 'text-red-700'
              ]">
                {{ inStock ? `In Stock (${product.stock_quantity} available)` : 'Out of Stock' }}
              </span>
            </div>

            <!-- Description -->
            <div v-if="product.description" class="prose max-w-none">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Description</h3>
              <p class="text-gray-600 leading-relaxed">
                {{ product.description }}
              </p>
            </div>

            <!-- Seller Info Box -->
            <div class="bg-slate-100 rounded-2xl p-6 border border-slate-200">
              <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
                Sold By
              </h3>
              <div class="flex items-center gap-4">
                <div class="h-16 w-16 bg-violet-600 rounded-full flex items-center justify-center">
                  <span class="text-2xl font-bold text-white">
                    {{ sellerName.charAt(0).toUpperCase() }}
                  </span>
                </div>
                <div>
                  <h4 class="text-xl font-bold text-gray-900">
                    {{ sellerName }}
                  </h4>
                  <p class="text-sm text-gray-600">Verified Seller</p>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 pt-4">
              <button
                :disabled="!inStock"
                :class="[
                  'flex-1 font-semibold py-4 px-8 rounded-xl transition-all duration-200 shadow-lg',
                  inStock
                    ? 'bg-violet-600 hover:bg-violet-700 text-white hover:shadow-xl hover:scale-105'
                    : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                ]"
              >
                <span class="flex items-center justify-center gap-2">
                  <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                  {{ inStock ? 'Add to Cart' : 'Out of Stock' }}
                </span>
              </button>

              <button
                class="sm:w-auto bg-white border-2 border-violet-600 text-violet-600 hover:bg-violet-50 font-semibold py-4 px-8 rounded-xl transition-all duration-200"
              >
                <svg class="h-6 w-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
              </button>
            </div>

            <!-- Product Meta -->
            <div class="border-t border-gray-200 pt-6 space-y-3">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Product ID:</span>
                <span class="font-medium text-gray-900">#{{ product.id }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">SKU:</span>
                <span class="font-medium text-gray-900">{{ product.slug.toUpperCase() }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Back to Products Button -->
        <div class="text-center">
          <NuxtLink
            to="/products"
            class="inline-flex items-center gap-2 text-violet-600 hover:text-violet-700 font-semibold transition-colors no-underline"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Products
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>
