<script setup lang="ts">
definePageMeta({
  middleware: 'auth'
})

const cartStore = useCartStore()
const router = useRouter()

// Tax calculation (dummy - 10%)
const taxRate = 0.10
const tax = computed(() => cartStore.totalAmount * taxRate)
const total = computed(() => cartStore.totalAmount + tax.value)

// Fetch cart on mount
onMounted(async () => {
  await cartStore.fetchCart()
})

// Handle quantity change
const updateQuantity = async (itemId: number, newQuantity: number) => {
  if (newQuantity < 1) return
  try {
    await cartStore.updateQuantity(itemId, newQuantity)
  } catch (error) {
    console.error('Failed to update quantity:', error)
  }
}

// Handle remove item
const removeItem = async (itemId: number) => {
  try {
    await cartStore.removeItem(itemId)
  } catch (error) {
    console.error('Failed to remove item:', error)
  }
}

// Navigate to checkout
const proceedToCheckout = () => {
  router.push('/checkout')
}

// Continue shopping
const continueShopping = () => {
  router.push('/products')
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 pt-28 pb-16">
    <div class="container mx-auto px-4">
      <!-- Page Title -->
      <div class="mb-8 relative">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900">Shopping Cart</h1>
        <p class="text-gray-600 mt-2">{{ cartStore.count }} {{ cartStore.count === 1 ? 'item' : 'items' }} in your cart</p>

        <!-- Updating Indicator -->
        <div v-if="cartStore.updating" class="absolute top-0 right-0 flex items-center gap-2 text-emerald-600">
          <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span class="text-sm font-medium">Updating...</span>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="cartStore.loading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600"></div>
      </div>

      <!-- Empty Cart State -->
      <div v-else-if="cartStore.items.length === 0" class="text-center py-20">
        <div class="max-w-md mx-auto">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-24 w-24 mx-auto text-gray-300 mb-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
            />
          </svg>
          <h2 class="text-2xl font-semibold text-gray-900 mb-3">Your cart is empty</h2>
          <p class="text-gray-600 mb-8">Looks like you haven't added any items to your cart yet.</p>
          <button
            @click="continueShopping"
            class="btn bg-emerald-400 hover:bg-emerald-700 text-white px-8 py-3 rounded-xl shadow-lg transition-colors"
          >
            Continue Shopping
          </button>
        </div>
      </div>

      <!-- Cart Items Grid -->
      <div v-else class="grid lg:grid-cols-3 gap-8">
        <!-- Left: Cart Items List -->
        <div class="lg:col-span-2 space-y-4">
          <div
            v-for="item in cartStore.items"
            :key="item.id"
            class="bg-white rounded-2xl p-4 md:p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all"
            :class="{ 'opacity-60 pointer-events-none': cartStore.updating }"
          >
            <div class="flex gap-4 md:gap-6">
              <!-- Product Image -->
              <div class="flex-shrink-0">
                <img
                  :src="item.product.image_url || 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=200&h=200&fit=crop'"
                  :alt="item.product.name"
                  class="w-20 h-20 md:w-28 md:h-28 object-cover rounded-xl"
                >
              </div>

              <!-- Product Details -->
              <div class="flex-1 min-w-0">
                <div class="flex justify-between items-start gap-4 mb-2">
                  <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-lg text-gray-900 truncate">{{ item.product.name }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ item.product.category || 'General' }}</p>
                    <p class="text-sm text-gray-400">Seller: {{ item.product.seller || 'Unknown' }}</p>
                  </div>
                  <button
                    @click="removeItem(item.id)"
                    :disabled="cartStore.updating"
                    class="flex-shrink-0 text-red-500 hover:text-red-700 p-2 rounded-lg hover:bg-red-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    aria-label="Remove item"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-4">
                  <!-- Price -->
                  <div class="text-2xl font-bold text-emerald-600">
                    ${{ Number(item.product.price).toFixed(2) }}
                  </div>

                  <!-- Quantity Stepper -->
                  <div class="flex items-center gap-3 bg-gray-50 rounded-xl p-2 w-fit">
                    <button
                      @click="updateQuantity(item.id, item.quantity - 1)"
                      :disabled="item.quantity <= 1 || cartStore.updating"
                      class="w-8 h-8 flex items-center justify-center rounded-lg bg-white hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors border border-gray-200"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                      </svg>
                    </button>
                    <span class="w-12 text-center font-semibold text-gray-900">{{ item.quantity }}</span>
                    <button
                      @click="updateQuantity(item.id, item.quantity + 1)"
                      :disabled="cartStore.updating"
                      class="w-8 h-8 flex items-center justify-center rounded-lg bg-white hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors border border-gray-200"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                      </svg>
                    </button>
                  </div>
                </div>

                <!-- Item Total -->
                <div class="text-right mt-3 text-sm text-gray-600">
                  Item Total: <span class="font-semibold text-gray-900">${{ (Number(item.product.price) * item.quantity).toFixed(2) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Order Summary (Sticky) -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 sticky top-28">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Order Summary</h2>

            <div class="space-y-4 mb-6">
              <div class="flex justify-between text-gray-600">
                <span>Subtotal</span>
                <span class="font-semibold text-gray-900">${{ cartStore.totalAmount.toFixed(2) }}</span>
              </div>
              <div class="flex justify-between text-gray-600">
                <span>Tax (10%)</span>
                <span class="font-semibold text-gray-900">${{ tax.toFixed(2) }}</span>
              </div>
              <div class="h-px bg-gray-200"></div>
              <div class="flex justify-between text-lg font-bold text-gray-900">
                <span>Total</span>
                <span class="text-emerald-600">${{ total.toFixed(2) }}</span>
              </div>
            </div>

            <button
              @click="proceedToCheckout"
              :disabled="cartStore.updating"
              class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Proceed to Checkout
            </button>

            <button
              @click="continueShopping"
              :disabled="cartStore.updating"
              class="w-full mt-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-6 rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Continue Shopping
            </button>

            <!-- Trust Badges -->
            <div class="mt-6 pt-6 border-t border-gray-200 space-y-3">
              <div class="flex items-center gap-3 text-sm text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span>Secure Checkout</span>
              </div>
              <div class="flex items-center gap-3 text-sm text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Free Shipping on orders over $50</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
