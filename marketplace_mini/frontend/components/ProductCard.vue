<script setup lang="ts">
import type { Product } from '~/types'

const props = defineProps<{
  product: Product
}>()

const cartStore = useCartStore()
const addingToCart = ref(false)

// Image state management
const imageUrl = ref(props.product.image_url || 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&h=500&fit=crop')
const imageLoadError = ref(false)

// Helper computed properties for template
const categoryName = computed(() => {
  return props.product.category?.name || 'General'
})

const sellerName = computed(() => {
  // Try to get shop_name from seller.seller, fall back to seller.name
  return props.product.seller?.seller?.shop_name || props.product.seller?.name || 'Unknown Seller'
})

// Handle image load error
const handleImageError = () => {
  if (!imageLoadError.value) {
    imageLoadError.value = true
    imageUrl.value = 'https://via.placeholder.com/640x480/e2e8f0/64748b?text=No+Image'
  }
}

// Add to cart handler (only for simple products)
const handleAddToCart = async (event: MouseEvent) => {
  // Prevent navigation to product page
  event.preventDefault()
  event.stopPropagation()

  if (addingToCart.value) return

  addingToCart.value = true
  try {
    await cartStore.addToCart({
      product_id: props.product.id,
      quantity: 1
    })
  } catch (error) {
    console.error('Failed to add to cart:', error)
  } finally {
    addingToCart.value = false
  }
}
</script>

<template>
  <NuxtLink
    :to="`/products/${product.id}`"
    class="group block bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-2 border border-gray-100 no-underline"
  >
    <!-- Product Image -->
    <div class="relative aspect-square overflow-hidden bg-slate-100">
      <img
        :src="imageUrl"
        :alt="product.name"
        @error="handleImageError"
        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
      >
      <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-semibold text-violet-600">
        {{ categoryName }}
      </div>
    </div>

    <!-- Product Info -->
    <div class="p-5">
      <h3 class="font-semibold text-lg text-gray-900 mb-2 line-clamp-2 group-hover:text-yellow-700 transition-colors">
        {{ product.name }}
      </h3>

      <div class="text-sm text-gray-500 mb-3">
        by {{ sellerName }}
      </div>

      <div class="flex items-center justify-between">
        <div class="flex flex-col">
          <span class="text-2xl font-bold text-gray-900">
            ${{ Number(product.price).toFixed(2) }}
          </span>
        </div>
      </div>

      <!-- Smart Button: Variant Products -> Select Options | Simple Products -> Add to Cart -->
      <button
        v-if="product.has_variants"
        @click.prevent="navigateTo(`/products/${product.id}`)"
        class="mt-4 w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 px-4 rounded-xl transition-colors duration-200 shadow-md hover:shadow-lg flex items-center justify-center gap-2"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
        <span>Select Options</span>
      </button>
      <button
        v-else
        @click="handleAddToCart"
        :disabled="addingToCart"
        class="mt-4 w-full bg-violet-600 hover:bg-emerald-700 text-white font-medium py-2.5 px-4 rounded-xl transition-colors duration-200 shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <span v-if="addingToCart">Adding...</span>
        <span v-else>Add to Cart</span>
      </button>
    </div>
  </NuxtLink>
</template>
