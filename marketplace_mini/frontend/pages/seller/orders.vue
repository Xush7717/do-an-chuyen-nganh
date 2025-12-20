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
const orders = ref<any[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const expandedOrders = ref<Set<number>>(new Set())
const updatingStatusId = ref<number | null>(null)
const selectedStatuses = ref<Record<number, string>>({})

// Fetch orders
const fetchOrders = async () => {
  loading.value = true
  error.value = null
  try {
    const response: any = await sellerService.getOrders()
    orders.value = response.orders.data || []

    // Initialize selected statuses
    orders.value.forEach(order => {
      selectedStatuses.value[order.id] = order.status
    })
  } catch (err: any) {
    error.value = err.data?.message || 'Failed to fetch orders'
  } finally {
    loading.value = false
  }
}

// Parse shipping address (handle JSON string or object)
const parseShippingAddress = (addressData: any) => {
  try {
    // If it's already an object, return it
    if (typeof addressData === 'object' && addressData !== null) {
      return addressData
    }
    // If it's a string, try to parse it
    if (typeof addressData === 'string') {
      return JSON.parse(addressData)
    }
    return null
  } catch (err) {
    console.error('Failed to parse shipping address:', err)
    return null
  }
}

// Update order status
const updateStatus = async (orderId: number) => {
  const newStatus = selectedStatuses.value[orderId]

  if (!newStatus) {
    return
  }

  updatingStatusId.value = orderId
  try {
    const response: any = await sellerService.updateOrderStatus(orderId, newStatus)

    // Update the order in the list
    const orderIndex = orders.value.findIndex(o => o.id === orderId)
    if (orderIndex !== -1) {
      orders.value[orderIndex].status = newStatus
    }

    // Show success notification (you could use a toast library here)
    alert('Order status updated successfully!')
  } catch (err: any) {
    alert(err.data?.message || 'Failed to update order status')
    // Revert status on error
    const order = orders.value.find(o => o.id === orderId)
    if (order) {
      selectedStatuses.value[orderId] = order.status
    }
  } finally {
    updatingStatusId.value = null
  }
}

// Toggle order details
const toggleOrder = (orderId: number) => {
  if (expandedOrders.value.has(orderId)) {
    expandedOrders.value.delete(orderId)
  } else {
    expandedOrders.value.add(orderId)
  }
}

// Format price
const formatPrice = (price: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(price)
}

// Format date
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// Get status badge color
const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    shipped: 'bg-purple-100 text-purple-800',
    delivered: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

// Calculate total for seller's items in an order
const getSellerTotal = (orderItems: any[]) => {
  return orderItems.reduce((sum, item) => {
    return sum + (item.quantity * item.price_at_purchase)
  }, 0)
}

// Load orders on mount
onMounted(() => {
  fetchOrders()
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
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">My Orders</h1>
        <p class="mt-2 text-gray-600">Orders containing your products</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white rounded-xl shadow-sm p-12 border border-gray-100 text-center">
        <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-emerald-600 border-r-transparent" />
        <p class="mt-4 text-gray-600">Loading orders...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-6">
        <p class="text-red-800">{{ error }}</p>
        <button @click="fetchOrders" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
          Try Again
        </button>
      </div>

      <!-- Empty State -->
      <div v-else-if="orders.length === 0" class="bg-white rounded-xl shadow-sm p-12 border border-gray-100 text-center">
        <svg class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
        </svg>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">No orders yet</h3>
        <p class="text-gray-600">Orders containing your products will appear here</p>
      </div>

      <!-- Orders List -->
      <div v-else class="space-y-4">
        <div
          v-for="order in orders"
          :key="order.id"
          class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
        >
          <!-- Order Header -->
          <div
            class="p-6 cursor-pointer hover:bg-slate-50 transition-colors"
            @click="toggleOrder(order.id)"
          >
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                  <h3 class="text-lg font-semibold text-gray-900">Order #{{ order.id }}</h3>
                  <span
                    :class="['inline-flex items-center px-3 py-1 rounded-full text-xs font-medium', getStatusColor(order.status)]"
                  >
                    {{ order.status }}
                  </span>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6 text-sm text-gray-600">
                  <div class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ order.user.name }}
                  </div>
                  <div class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ formatDate(order.created_at) }}
                  </div>
                  <div class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    {{ order.order_items.length }} item(s) from you
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-4">
                <div class="text-right">
                  <p class="text-sm text-gray-600">Your earnings</p>
                  <p class="text-xl font-bold text-emerald-600">
                    {{ formatPrice(getSellerTotal(order.order_items)) }}
                  </p>
                </div>
                <svg
                  :class="['h-6 w-6 text-gray-400 transition-transform', expandedOrders.has(order.id) ? 'rotate-180' : '']"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div>
          </div>

          <!-- Order Details (Expandable) -->
          <div v-if="expandedOrders.has(order.id)" class="border-t border-gray-200 bg-slate-50 p-6">
            <!-- Status Management -->
            <div class="mb-6 p-4 bg-white rounded-lg border border-gray-200">
              <h5 class="font-semibold text-gray-900 mb-3">Update Order Status</h5>
              <div class="flex flex-col sm:flex-row gap-3">
                <select
                  v-model="selectedStatuses[order.id]"
                  class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                >
                  <option value="pending">Pending</option>
                  <option value="processing">Processing</option>
                  <option value="shipped">Shipped</option>
                  <option value="delivered">Delivered</option>
                  <option value="cancelled">Cancelled</option>
                </select>
                <button
                  @click="updateStatus(order.id)"
                  :disabled="updatingStatusId === order.id || selectedStatuses[order.id] === order.status"
                  class="px-6 py-2 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span v-if="updatingStatusId === order.id">Updating...</span>
                  <span v-else>Update Status</span>
                </button>
              </div>
              <p class="mt-2 text-sm text-gray-500">
                Current status: <span class="font-medium capitalize">{{ order.status }}</span>
              </p>
            </div>

            <h4 class="font-semibold text-gray-900 mb-4">Your Items in This Order:</h4>
            <div class="space-y-3">
              <div
                v-for="item in order.order_items"
                :key="item.id"
                class="flex items-center gap-4 p-4 bg-white rounded-lg border border-gray-200"
              >
                <img
                  :src="item.product?.image_url || '/placeholder.png'"
                  :alt="item.product_name"
                  class="h-16 w-16 rounded-lg object-cover border border-gray-200"
                >
                <div class="flex-1 min-w-0">
                  <h5 class="font-medium text-gray-900 truncate">{{ item.product_name }}</h5>
                  <p class="text-sm text-gray-600 mt-1">
                    Quantity: {{ item.quantity }} × {{ formatPrice(item.price_at_purchase) }}
                  </p>
                </div>
                <div class="text-right">
                  <p class="font-semibold text-gray-900">
                    {{ formatPrice(item.quantity * item.price_at_purchase) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Shipping Address -->
            <div class="mt-6 p-4 bg-slate-50 rounded-lg border border-gray-200">
              <h5 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Shipping Address
              </h5>
              <div v-if="parseShippingAddress(order.shipping_address)" class="space-y-2">
                <p class="font-semibold text-gray-900">
                  {{ parseShippingAddress(order.shipping_address).name }}
                </p>
                <p class="text-sm text-gray-600">
                  <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                  {{ parseShippingAddress(order.shipping_address).phone }}
                </p>
                <p class="text-sm text-gray-700 leading-relaxed">
                  {{ parseShippingAddress(order.shipping_address).address }}<br>
                  {{ parseShippingAddress(order.shipping_address).city }}, {{ parseShippingAddress(order.shipping_address).state }} {{ parseShippingAddress(order.shipping_address).zip }}
                </p>
              </div>
              <p v-else class="text-sm text-gray-700 whitespace-pre-line">{{ order.shipping_address }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
