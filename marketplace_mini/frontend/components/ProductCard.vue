<script setup lang="ts">
import type { Product } from '~/types'

const props = defineProps<{
  product: Product
}>()

const cartStore = useCartStore()
const addingToCart = ref(false)

// Helper computed properties for template
const imageUrl = computed(() => {
  return props.product.image_url || 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&h=500&fit=crop'
})

const categoryName = computed(() => {
  return props.product.category?.name || 'General'
})

const sellerName = computed(() => {
  // Try to get shop_name from seller.seller, fall back to seller.name
  return props.product.seller?.seller?.shop_name || props.product.seller?.name || 'Unknown Seller'
})

// Add to cart handler
const handleAddToCart = async (event: MouseEvent) => {
  // Prevent navigation to product page
  event.preventDefault()
  event.stopPropagation()

  if (addingToCart.value) return

  addingToCart.value = true
  try {
    await cartStore.addToCart({ id: props.product.id })
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

      <!-- Add to Cart Button -->
      <button
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
