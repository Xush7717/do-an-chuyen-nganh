<script setup lang="ts">
interface Product {
  id: number
  name: string
  price: number
  image: string
  category: string
  rating?: number
  seller?: string
}

const props = defineProps<{
  product: Product
}>()
</script>

<template>
  <NuxtLink
    :to="`/products/${product.id}`"
    class="group block bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-2 border border-gray-100 no-underline"
  >
    <!-- Product Image -->
    <div class="relative aspect-square overflow-hidden bg-slate-100">
      <img
        :src="product.image"
        :alt="product.name"
        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
      >
      <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-semibold text-violet-600">
        {{ product.category }}
      </div>
    </div>

    <!-- Product Info -->
    <div class="p-5">
      <h3 class="font-semibold text-lg text-gray-900 mb-2 line-clamp-2 group-hover:text-violet-600 transition-colors">
        {{ product.name }}
      </h3>

      <div v-if="product.seller" class="text-sm text-gray-500 mb-3">
        by {{ product.seller }}
      </div>

      <div class="flex items-center justify-between">
        <div class="flex flex-col">
          <span class="text-2xl font-bold text-gray-900">
            ${{ product.price.toFixed(2) }}
          </span>
        </div>

        <div v-if="product.rating" class="flex items-center gap-1">
          <svg class="h-5 w-5 text-amber-400 fill-current" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
          </svg>
          <span class="text-sm font-medium text-gray-700">{{ product.rating }}</span>
        </div>
      </div>

      <!-- Add to Cart Button -->
      <button class="mt-4 w-full bg-violet-600 hover:bg-violet-700 text-white font-medium py-2.5 px-4 rounded-xl transition-colors duration-200 shadow-md hover:shadow-lg">
        Add to Cart
      </button>
    </div>
  </NuxtLink>
</template>
