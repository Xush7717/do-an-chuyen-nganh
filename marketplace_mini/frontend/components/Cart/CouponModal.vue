<script setup lang="ts">
const props = defineProps<{
  show: boolean
}>()

const emit = defineEmits<{
  close: []
  apply: [code: string, sellerId: number, sellerName: string]
}>()

const config = useRuntimeConfig()
const cartStore = useCartStore()

const loading = ref(false)
const availableCoupons = ref<any[]>([])
const error = ref<string | null>(null)

// Fetch available coupons when modal opens
watch(() => props.show, async (isOpen) => {
  if (isOpen) {
    await fetchAvailableCoupons()
  }
})

const fetchAvailableCoupons = async () => {
  loading.value = true
  error.value = null
  try {
    const cartItems = cartStore.items.map(item => ({
      product_id: item.product.id,
      quantity: item.quantity
    }))

    const response = await $fetch<any>(`${config.public.apiBase}/coupons/available`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: {
        cart_items: cartItems
      }
    })

    availableCoupons.value = response.coupons || []
  } catch (err: any) {
    error.value = err.data?.message || 'Failed to load coupons'
  } finally {
    loading.value = false
  }
}

const applyCoupon = (code: string, sellerId: number, sellerName: string) => {
  emit('apply', code, sellerId, sellerName)
}

const closeModal = () => {
  emit('close')
}

// Format discount display
const formatDiscount = (coupon: any) => {
  if (coupon.type === 'percentage') {
    return `${coupon.value}% off`
  }
  return `$${coupon.value} off`
}

// Check if coupon is already applied
const isCouponApplied = (sellerId: number) => {
  return cartStore.appliedCoupons.some(c => c.sellerId === sellerId)
}

const getAppliedCouponCode = (sellerId: number) => {
  return cartStore.appliedCoupons.find(c => c.sellerId === sellerId)?.code
}
</script>

<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
    @click.self="closeModal"
  >
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden shadow-2xl">
      <!-- Header -->
      <div class="p-6 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Available Coupons</h2>
        <button
          @click="closeModal"
          class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <svg class="h-6 w-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="p-6 overflow-y-auto max-h-[calc(80vh-140px)]">
        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600"></div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
          {{ error }}
        </div>

        <!-- Empty State -->
        <div v-else-if="availableCoupons.length === 0" class="text-center py-12">
          <svg class="h-16 w-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">No Coupons Available</h3>
          <p class="text-gray-600">There are no active coupons for the items in your cart.</p>
        </div>

        <!-- Coupons List -->
        <div v-else class="space-y-6">
          <div
            v-for="seller in availableCoupons"
            :key="seller.seller_id"
            class="border border-gray-200 rounded-xl p-4"
          >
            <!-- Seller Header -->
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="font-semibold text-gray-900">{{ seller.seller_name }}</h3>
                <p class="text-sm text-gray-500">Subtotal: ${{ seller.subtotal.toFixed(2) }}</p>
              </div>
              <span
                v-if="isCouponApplied(seller.seller_id)"
                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800"
              >
                Applied: {{ getAppliedCouponCode(seller.seller_id) }}
              </span>
            </div>

            <!-- Coupon Options -->
            <div class="space-y-3">
              <div
                v-for="coupon in seller.coupons"
                :key="coupon.id"
                class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-emerald-500 hover:bg-emerald-50 transition-all"
              >
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-1">
                    <span class="font-mono font-bold text-emerald-600 text-lg">{{ coupon.code }}</span>
                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-orange-100 text-orange-800">
                      {{ formatDiscount(coupon) }}
                    </span>
                  </div>
                  <p class="text-sm text-gray-600">
                    Save ${{ coupon.discount_amount.toFixed(2) }} • Min order: ${{ Number(coupon.min_order_value).toFixed(2) }}
                  </p>
                  <p v-if="coupon.expires_at" class="text-xs text-gray-500 mt-1">
                    Expires: {{ new Date(coupon.expires_at).toLocaleDateString() }}
                  </p>
                </div>
                <button
                  @click="applyCoupon(coupon.code, seller.seller_id, seller.seller_name)"
                  :class="[
                    'px-4 py-2 rounded-lg font-medium transition-colors',
                    isCouponApplied(seller.seller_id) && getAppliedCouponCode(seller.seller_id) === coupon.code
                      ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                      : 'bg-emerald-600 text-white hover:bg-emerald-700'
                  ]"
                  :disabled="isCouponApplied(seller.seller_id) && getAppliedCouponCode(seller.seller_id) === coupon.code"
                >
                  {{
                    isCouponApplied(seller.seller_id) && getAppliedCouponCode(seller.seller_id) === coupon.code
                      ? 'Applied'
                      : 'Apply'
                  }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="p-6 border-t border-gray-200 bg-gray-50">
        <button
          @click="closeModal"
          class="w-full bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors"
        >
          Done
        </button>
      </div>
    </div>
  </div>
</template>
