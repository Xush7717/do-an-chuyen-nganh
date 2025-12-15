<script setup lang="ts">
import type { Order, ShippingAddress, OrdersResponse } from '~/types'

definePageMeta({
  middleware: 'auth',
})

const config = useRuntimeConfig()
const authStore = useAuthStore()
const currentPage = ref(1)

// State
const ordersResponse = ref<OrdersResponse | null>(null)
const pending = ref(false)
const error = ref(false)

// Extract orders from nested response
const orders = computed(() => ordersResponse.value?.data?.data || [])
const pagination = computed(() => ordersResponse.value?.data)

// Fetch orders function
const fetchOrders = async (page: number = 1) => {
  pending.value = true
  error.value = false

  try {
    const response = await $fetch<OrdersResponse>(`${config.public.apiBase}/orders?page=${page}`, {
      headers: {
        Authorization: `Bearer ${authStore.token}`,
      },
    })
    ordersResponse.value = response
  } catch (err) {
    console.error('Failed to fetch orders:', err)
    error.value = true
  } finally {
    pending.value = false
  }
}

// Initial fetch
await fetchOrders(currentPage.value)

// Handle page change
const goToPage = async (page: number) => {
  currentPage.value = page
  await fetchOrders(page)
  // Scroll to top of orders list
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Handle retry
const handleRetry = async () => {
  await fetchOrders(currentPage.value)
}

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
    month: 'short',
    day: 'numeric',
  })
}

// Parse shipping address
const getShippingName = (address: string | ShippingAddress) => {
  if (typeof address === 'string') {
    try {
      const parsed = JSON.parse(address) as ShippingAddress
      return parsed.name
    } catch {
      return 'N/A'
    }
  }
  return address.name
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

// Navigate to order details
const viewOrderDetails = (orderId: number) => {
  navigateTo(`/profile/orders/${orderId}`)
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 pt-28 pb-16">
    <div class="max-w-6xl mx-auto px-4">
      <!-- Page Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">My Orders</h1>
        <p class="text-gray-600">View and track your order history</p>
      </div>

      <!-- Loading State -->
      <div v-if="pending" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
        <p class="text-red-700">Failed to load orders. Please try again.</p>
        <button
          @click="handleRetry"
          class="mt-4 bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded-lg transition-colors"
        >
          Retry
        </button>
      </div>

      <!-- Empty State -->
      <div v-else-if="orders.length === 0" class="bg-white rounded-2xl shadow-sm p-12 text-center">
        <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
          </svg>
        </div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">No Orders Yet</h3>
        <p class="text-gray-600 mb-6">You haven't placed any orders yet.</p>
        <NuxtLink
          to="/products"
          class="inline-block bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-3 rounded-xl font-semibold transition-colors"
        >
          Start Shopping
        </NuxtLink>
      </div>

      <!-- Orders List -->
      <div v-else class="space-y-4">
        <div
          v-for="order in orders"
          :key="order.id"
          class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-shadow p-6"
        >
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Order Info -->
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-3">
                <h3 class="text-lg font-bold text-gray-900">Order #{{ order.id }}</h3>
                <span
                  :class="getStatusColor(order.status)"
                  class="px-3 py-1 rounded-full text-xs font-semibold uppercase"
                >
                  {{ order.status }}
                </span>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                <div>
                  <p class="text-gray-500">Date</p>
                  <p class="font-medium text-gray-900">{{ formatDate(order.created_at) }}</p>
                </div>
                <div>
                  <p class="text-gray-500">Total</p>
                  <p class="font-bold text-#E27317">{{ formatCurrency(order.final_amount) }}</p>
                </div>
                <div>
                  <p class="text-gray-500">Ship To</p>
                  <p class="font-medium text-gray-900">{{ getShippingName(order.shipping_address) }}</p>
                </div>
              </div>
            </div>

            <!-- Action Button -->
            <button
              @click="viewOrderDetails(order.id)"
              class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-xl font-semibold transition-colors whitespace-nowrap"
            >
              View Details
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div
        v-if="pagination && pagination.last_page > 1"
        class="mt-8 flex justify-center items-center gap-2"
      >
        <button
          :disabled="pagination.current_page === 1"
          @click="goToPage(pagination.current_page - 1)"
          class="px-4 py-2 rounded-lg border border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
        >
          Previous
        </button>

        <div class="flex gap-2">
          <button
            v-for="page in pagination.last_page"
            :key="page"
            @click="goToPage(page)"
            :class="[
              page === pagination.current_page
                ? 'bg-blue-600 text-white'
                : 'bg-white text-gray-700 hover:bg-gray-50',
              'px-4 py-2 rounded-lg border border-gray-300 transition-colors'
            ]"
          >
            {{ page }}
          </button>
        </div>

        <button
          :disabled="pagination.current_page === pagination.last_page"
          @click="goToPage(pagination.current_page + 1)"
          class="px-4 py-2 rounded-lg border border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>
