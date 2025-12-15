<script setup lang="ts">
definePageMeta({
  middleware: 'auth',
})

const route = useRoute()
const cartStore = useCartStore()

// Get order ID from route params
const orderId = route.params.id as string

// Fetch cart on mount to update badge count (cart should be empty after checkout)
onMounted(async () => {
  await cartStore.fetchCart()
})

// Navigate to home
const goHome = () => {
  navigateTo('/')
}

// Navigate to order details
const viewOrderDetails = () => {
  navigateTo(`/profile/orders/${orderId}`)
}

// Navigate to all orders
const viewAllOrders = () => {
  navigateTo('/profile/orders')
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex items-center justify-center px-4 py-16">
    <div class="max-w-2xl w-full">
      <!-- Success Card -->
      <div class="bg-white rounded-3xl shadow-xl p-8 md:p-12 text-center">
        <!-- Success Icon -->
        <div class="mb-6">
          <div class="mx-auto w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center">
            <svg class="w-12 h-12 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
        </div>

        <!-- Success Message -->
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
          Order Placed Successfully!
        </h1>
        <p class="text-lg text-gray-600 mb-8">
          Thank you for your purchase. Your order has been confirmed and is being processed.
        </p>

        <!-- Order Details -->
        <div class="bg-slate-50 rounded-2xl p-6 mb-8">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <p class="text-sm text-gray-500 mb-1">Order Number</p>
              <p class="text-2xl font-bold text-gray-900">#{{ orderId }}</p>
            </div>
            <div class="flex items-center gap-2 text-emerald-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="font-semibold">Payment Confirmed</span>
            </div>
          </div>
        </div>

        <!-- Information Box -->
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6 mb-8 text-left">
          <div class="flex gap-3">
            <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
              <h3 class="font-semibold text-blue-900 mb-2">What's Next?</h3>
              <ul class="text-sm text-blue-800 space-y-1">
                <li>• You will receive an email confirmation shortly</li>
                <li>• Your order will be processed and shipped within 2-3 business days</li>
                <li>• Track your order status in your account dashboard</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <button
            @click="viewOrderDetails"
            class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-8 rounded-xl transition-all shadow-lg hover:shadow-xl"
          >
            View Order Details
          </button>
          <button
            @click="viewAllOrders"
            class="bg-white border-2 border-emerald-600 text-emerald-600 hover:bg-emerald-50 font-semibold py-3 px-8 rounded-xl transition-all"
          >
            View All Orders
          </button>
          <button
            @click="goHome"
            class="bg-white border-2 border-gray-300 hover:border-emerald-600 text-gray-700 hover:text-emerald-600 font-semibold py-3 px-8 rounded-xl transition-all"
          >
            Continue Shopping
          </button>
        </div>

        <!-- Additional Help -->
        <div class="mt-8 pt-8 border-t border-gray-200">
          <p class="text-sm text-gray-500">
            Need help with your order?
            <a href="#" class="text-emerald-600 hover:text-emerald-700 font-medium ml-1">Contact Support</a>
          </p>
        </div>
      </div>

      <!-- Decorative Elements -->
      <div class="mt-8 text-center">
        <div class="inline-flex items-center gap-2 text-gray-400">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          <span class="text-sm">Secured Payment by Stripe</span>
        </div>
      </div>
    </div>
  </div>
</template>
