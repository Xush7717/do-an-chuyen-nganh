<script setup lang="ts">
definePageMeta({
  layout: 'default',
})

// Get product ID from route params
const route = useRoute()
const router = useRouter()
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

// Cart functionality
const cartStore = useCartStore()
const quantity = ref(1)
const addingToCart = ref(false)
const showSuccessMessage = ref(false)
const addedQuantity = ref(0)

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

// Handle quantity change
const incrementQuantity = () => {
  if (quantity.value < (product.value?.stock_quantity || 0)) {
    quantity.value++
  }
}

const decrementQuantity = () => {
  if (quantity.value > 1) {
    quantity.value--
  }
}

// Handle add to cart
const handleAddToCart = async () => {
  if (!product.value || addingToCart.value) return

  addingToCart.value = true
  addedQuantity.value = quantity.value

  try {
    await cartStore.addToCart({ id: product.value.id }, quantity.value)

    // Show success message
    showSuccessMessage.value = true
    setTimeout(() => {
      showSuccessMessage.value = false
    }, 3000)

    // Reset quantity to 1 after adding
    quantity.value = 1
  } catch (error) {
    console.error('Failed to add to cart:', error)
  } finally {
    addingToCart.value = false
  }
}

// Breadcrumb
const breadcrumb = computed(() => [
  { label: 'Home', to: '/' },
  { label: 'Products', to: '/products' },
  { label: product.value?.name || 'Product Details', to: null },
])
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <div class="container mx-auto px-4 py-8 pt-16">
      <!-- Success Notification -->
      <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="transform translate-y-2 opacity-0"
        enter-to-class="transform translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="transform translate-y-0 opacity-100"
        leave-to-class="transform translate-y-2 opacity-0"
      >
        <div
          v-if="showSuccessMessage"
          class="fixed top-24 right-4 z-50 bg-emerald-600 text-white px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3 max-w-md"
        >
          <svg class="h-6 w-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div>
            <p class="font-semibold">Added to cart!</p>
            <p class="text-sm text-emerald-100">{{ addedQuantity }} {{ addedQuantity === 1 ? 'item' : 'items' }} added successfully</p>
          </div>
        </div>
      </transition>

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

            <!-- Quantity Selector -->
            <div v-if="inStock" class="space-y-3">
              <label class="text-sm font-semibold text-gray-700">Quantity</label>
              <div class="flex items-center gap-4">
                <div class="flex items-center gap-3 bg-gray-100 rounded-xl p-2">
                  <button
                    @click="decrementQuantity"
                    :disabled="quantity <= 1"
                    class="w-10 h-10 flex items-center justify-center rounded-lg bg-white hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors border border-gray-200"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                    </svg>
                  </button>
                  <span class="w-16 text-center font-bold text-xl text-gray-900">{{ quantity }}</span>
                  <button
                    @click="incrementQuantity"
                    :disabled="quantity >= product.stock_quantity"
                    class="w-10 h-10 flex items-center justify-center rounded-lg bg-white hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors border border-gray-200"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                  </button>
                </div>
                <span class="text-sm text-gray-500">
                  {{ product.stock_quantity }} available
                </span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 pt-4">
              <button
                @click="handleAddToCart"
                :disabled="!inStock || addingToCart"
                :class="[
                  'flex-1 font-semibold py-4 px-8 rounded-xl transition-all duration-200 shadow-lg',
                  inStock
                    ? 'bg-emerald-600 hover:bg-emerald-700 text-white hover:shadow-xl hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:scale-100'
                    : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                ]"
              >
                <span class="flex items-center justify-center gap-2">
                  <svg v-if="!addingToCart" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                  <svg v-else class="animate-spin h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ addingToCart ? 'Adding to Cart...' : (inStock ? 'Add to Cart' : 'Out of Stock') }}
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
