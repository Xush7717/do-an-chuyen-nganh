<script setup lang="ts">
import type { ShippingAddress } from '~/types'

// Type definitions
interface ReviewProduct {
  id: number
  name: string
  image_url?: string
}

definePageMeta({
  middleware: 'auth',
})

const route = useRoute()
const orderId = route.params.id as string
const authStore = useAuthStore()

const { getOrderDetails } = useOrderService()

// Fetch order details
const { data: orderResponse, pending, error, refresh } = await getOrderDetails(orderId)

// Extract order from response
const order = computed(() => orderResponse.value?.data)

// Review modal state
const showReviewModal = ref(false)
const selectedProduct = ref<ReviewProduct | null>(null)
const submittingReview = ref(false)
const reviewForm = ref({
  rating: 5,
  comment: ''
})

// Open review modal
const openReviewModal = (product: ReviewProduct) => {
  selectedProduct.value = product
  reviewForm.value = { rating: 5, comment: '' }
  showReviewModal.value = true
}

// Close review modal
const closeReviewModal = () => {
  showReviewModal.value = false
  selectedProduct.value = null
  reviewForm.value = { rating: 5, comment: '' }
}

// Submit review
const submitReview = async () => {
  if (!selectedProduct.value) return

  submittingReview.value = true
  try {
    await $fetch('http://127.0.0.1:8000/api/reviews', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        'Content-Type': 'application/json'
      },
      body: {
        product_id: selectedProduct.value.id,
        rating: reviewForm.value.rating,
        comment: reviewForm.value.comment
      }
    })

    // Close modal
    closeReviewModal()

    // Refresh order data to update is_reviewed flags
    await refresh()

    // Show success message
    alert('Review submitted successfully!')
  } catch (error: any) {
    alert(error?.data?.message || 'Failed to submit review')
  } finally {
    submittingReview.value = false
  }
}

// Parse shipping address
const shippingAddress = computed(() => {
  if (!order.value?.shipping_address) return null

  if (typeof order.value.shipping_address === 'string') {
    try {
      return JSON.parse(order.value.shipping_address) as ShippingAddress
    } catch {
      return null
    }
  }
  return order.value.shipping_address as ShippingAddress
})

// Format currency (handles both string and number types)
const formatCurrency = (value: string | number | undefined) => {
  if (value === undefined || value === null) return '$0.00'
  const numValue = typeof value === 'string' ? parseFloat(value) : value
  return isNaN(numValue) ? '$0.00' : `$${numValue.toFixed(2)}`
}

// Format date
const formatDate = (dateString?: string) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// Get status badge color
const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    processing: 'bg-blue-100 text-blue-700',
    shipped: 'bg-purple-100 text-purple-700',
    delivered: 'bg-emerald-100 text-emerald-700',
    cancelled: 'bg-red-100 text-red-700',
  }
  return colors[status] || 'bg-gray-100 text-gray-700'
}

// Get payment status badge color
const getPaymentStatusColor = (status?: string) => {
  if (!status) return 'bg-gray-100 text-gray-700'

  const colors: Record<string, string> = {
    succeeded: 'bg-emerald-100 text-emerald-700',
    pending: 'bg-yellow-100 text-yellow-700',
    failed: 'bg-red-100 text-red-700',
  }
  return colors[status] || 'bg-gray-100 text-gray-700'
}

// Calculate item subtotal
const itemSubtotal = (quantity: number, price: string | number) => {
  const numPrice = typeof price === 'string' ? parseFloat(price) : price
  return formatCurrency(quantity * numPrice)
}

// Back to orders list
const goBack = () => {
  navigateTo('/profile/orders')
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 pt-28 pb-16">
    <div class="max-w-5xl mx-auto px-4">
      <!-- Back Button -->
      <button
        @click="goBack"
        class="mb-8 inline-flex items-center gap-2 bg-white hover:bg-emerald-50 text-gray-700 hover:text-emerald-700 font-semibold px-5 py-3 rounded-xl border-2 border-gray-200 hover:border-emerald-500 transition-all shadow-sm hover:shadow-md"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        <span>Back to Orders</span>
      </button>

      <!-- Loading State -->
      <div v-if="pending" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
        <p class="text-red-700">Failed to load order details. Please try again.</p>
      </div>

      <!-- Order Not Found -->
      <div v-else-if="!order" class="bg-white rounded-2xl shadow-sm p-12 text-center">
        <h3 class="text-xl font-semibold text-gray-900 mb-2">Order Not Found</h3>
        <p class="text-gray-600">The order you're looking for doesn't exist.</p>
      </div>

      <!-- Order Details -->
      <div v-else class="space-y-6">
        <!-- Header Card -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
              <h1 class="text-3xl font-bold text-gray-900 mb-2">Order #{{ order.id }}</h1>
              <p class="text-gray-600">Placed on {{ formatDate(order.created_at) }}</p>
            </div>
            <span
              :class="getStatusColor(order.status)"
              class="px-4 py-2 rounded-xl text-sm font-semibold uppercase self-start"
            >
              {{ order.status }}
            </span>
          </div>

          <!-- Order Summary Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-slate-50 rounded-xl p-4">
              <p class="text-sm text-gray-500 mb-1">Subtotal</p>
              <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(order.total_amount) }}</p>
            </div>
            <div class="bg-slate-50 rounded-xl p-4">
              <p class="text-sm text-gray-500 mb-1">Discount</p>
              <p class="text-2xl font-bold text-emerald-600">-{{ formatCurrency(order.discount_amount) }}</p>
            </div>
            <div class="bg-slate-50 rounded-xl p-4">
              <p class="text-sm text-gray-500 mb-1">Tax (10%)</p>
              <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(order.tax_amount || 0) }}</p>
            </div>
            <div class="bg-orange-100 rounded-xl p-4">
              <p class="text-sm text-orange-900 mb-1">Final Amount</p>
              <p class="text-2xl font-bold text-orange-900">{{ formatCurrency(order.final_amount) }}</p>
            </div>
          </div>
        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Left Column: Order Items -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Items Card -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
              <h2 class="text-xl font-bold text-gray-900 mb-4">Order Items</h2>

              <div v-if="order.order_items && order.order_items.length > 0" class="space-y-4">
                <div
                  v-for="item in order.order_items"
                  :key="item.id"
                  class="flex gap-4 p-4 border border-gray-200 rounded-xl hover:border-emerald-300 transition-colors"
                >
                  <!-- Product Image -->
                  <img
                    v-if="item.product?.image_url"
                    :src="item.product.image_url"
                    :alt="item.product_name"
                    class="w-20 h-20 object-cover rounded-lg"
                  />
                  <div
                    v-else
                    class="w-20 h-20 bg-slate-100 rounded-lg flex items-center justify-center"
                  >
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>

                  <!-- Product Info -->
                  <div class="flex-1">
                    <h3 class="font-semibold text-gray-900 mb-1">{{ item.product_name }}</h3>
                    <p class="text-sm text-gray-600">Quantity: {{ item.quantity }}</p>
                    <p class="text-sm text-gray-600">Price: {{ formatCurrency(item.price_at_purchase) }}</p>

                    <!-- Review Button (Only show if order is delivered and not reviewed) -->
                    <div v-if="order.status === 'delivered'" class="mt-3">
                      <button
                        v-if="!item.is_reviewed"
                        @click="openReviewModal({ id: item.product_id, name: item.product_name, image_url: item.product?.image_url })"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-700 border border-emerald-300 rounded-lg hover:bg-emerald-600 hover:text-white transition-colors text-sm font-medium"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                        Write Review
                      </button>
                      <span
                        v-else
                        class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm font-medium"
                      >
                        <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        Reviewed
                      </span>
                    </div>
                  </div>

                  <!-- Item Total -->
                  <div class="text-right">
                    <p class="font-bold text-gray-900">{{ itemSubtotal(item.quantity, item.price_at_purchase) }}</p>
                  </div>
                </div>
              </div>

              <div v-else class="text-center py-8 text-gray-500">
                No items found in this order
              </div>
            </div>
          </div>

          <!-- Right Column: Shipping & Payment -->
          <div class="space-y-6">
            <!-- Shipping Address Card -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
              <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Shipping Address
              </h2>

              <div v-if="shippingAddress" class="space-y-2 text-sm">
                <p class="font-semibold text-gray-900">{{ shippingAddress.name }}</p>
                <p class="text-gray-600">{{ shippingAddress.phone }}</p>
                <p class="text-gray-600">{{ shippingAddress.address }}</p>
                <p class="text-gray-600">{{ shippingAddress.city }}</p>
              </div>
              <div v-else class="text-sm text-gray-500">
                No shipping address available
              </div>
            </div>

            <!-- Payment Info Card -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
              <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                Payment Information
              </h2>

              <div v-if="order.payment" class="space-y-3">
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Gateway</span>
                  <span class="font-semibold text-gray-900 uppercase">{{ order.payment.gateway }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Amount</span>
                  <span class="font-semibold text-gray-900">{{ formatCurrency(order.payment.amount) }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Status</span>
                  <span
                    :class="getPaymentStatusColor(order.payment.status)"
                    class="px-3 py-1 rounded-full text-xs font-semibold uppercase"
                  >
                    {{ order.payment.status }}
                  </span>
                </div>
                <div class="pt-3 border-t border-gray-200">
                  <p class="text-xs text-gray-500">Transaction ID</p>
                  <p class="text-xs font-mono text-gray-700 break-all">{{ order.payment.transaction_id }}</p>
                </div>
              </div>
              <div v-else class="text-sm text-gray-500">
                No payment information available
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Review Modal -->
    <div
      v-if="showReviewModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="closeReviewModal"
    >
      <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-xl font-bold text-gray-900">Write Your Review</h3>
          <button
            @click="closeReviewModal"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div v-if="selectedProduct" class="mb-4 p-3 bg-slate-50 rounded-lg">
          <p class="font-semibold text-gray-900">{{ selectedProduct.name }}</p>
        </div>

        <!-- Rating -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
          <div class="flex gap-1">
            <button
              v-for="star in 5"
              :key="star"
              @click="reviewForm.rating = star"
              type="button"
              class="text-3xl transition-colors"
              :class="star <= reviewForm.rating ? 'text-yellow-500' : 'text-slate-300'"
            >
              ★
            </button>
          </div>
        </div>

        <!-- Comment -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Comment</label>
          <textarea
            v-model="reviewForm.comment"
            rows="4"
            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
            placeholder="Share your experience with this product..."
          ></textarea>
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
          <button
            @click="submitReview"
            :disabled="submittingReview"
            class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium"
          >
            {{ submittingReview ? 'Submitting...' : 'Submit Review' }}
          </button>
          <button
            @click="closeReviewModal"
            :disabled="submittingReview"
            class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 disabled:opacity-50 font-medium"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
