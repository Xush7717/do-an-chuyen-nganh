<script setup lang="ts">
// Type definitions
interface Coupon {
  id: number
  seller_id: number
  code: string
  type: 'percentage' | 'fixed'
  value: number
  min_order_value: number
  usage_limit: number | null
  usage_count: number
  expires_at: string
  created_at: string
  updated_at: string
}

interface CouponsResponse {
  coupons: Coupon[]
}

definePageMeta({
  middleware: ['auth', 'seller'],
})

const authStore = useAuthStore()
const config = useRuntimeConfig()

const coupons = ref<Coupon[]>([])
const loading = ref(true)
const showCreateForm = ref(false)
const submitting = ref(false)

const couponForm = ref({
  code: '',
  type: 'percentage',
  value: 0,
  min_order_value: 0,
  usage_limit: null as number | null,
  expires_at: ''
})

const loadCoupons = async () => {
  loading.value = true
  try {
    const data = await $fetch<CouponsResponse>(`${config.public.apiBase}/seller/coupons`, {
      headers: {
        Authorization: `Bearer ${authStore.token}`
      }
    })
    coupons.value = data.coupons
  } catch (error) {
    console.error('Failed to load coupons:', error)
  } finally {
    loading.value = false
  }
}

const createCoupon = async () => {
  submitting.value = true
  try {
    await $fetch(`${config.public.apiBase}/seller/coupons`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        'Content-Type': 'application/json'
      },
      body: {
        code: couponForm.value.code,
        type: couponForm.value.type,
        value: Number(couponForm.value.value),
        min_order_value: Number(couponForm.value.min_order_value),
        usage_limit: couponForm.value.usage_limit ? Number(couponForm.value.usage_limit) : null,
        expires_at: couponForm.value.expires_at
      }
    })

    showCreateForm.value = false
    couponForm.value = {
      code: '',
      type: 'percentage',
      value: 0,
      min_order_value: 0,
      usage_limit: null,
      expires_at: ''
    }
    await loadCoupons()
  } catch (error: any) {
    alert(error?.data?.message || 'Failed to create coupon')
  } finally {
    submitting.value = false
  }
}

const deleteCoupon = async (id: number) => {
  if (!confirm('Are you sure you want to delete this coupon?')) return

  try {
    await $fetch(`${config.public.apiBase}/seller/coupons/${id}`, {
      method: 'DELETE',
      headers: {
        Authorization: `Bearer ${authStore.token}`
      }
    })
    await loadCoupons()
  } catch (error: any) {
    alert(error?.data?.message || 'Failed to delete coupon')
  }
}

onMounted(() => {
  loadCoupons()
})

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const isExpired = (date: string) => {
  return new Date(date) < new Date()
}

const getMinDate = () => {
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  return tomorrow.toISOString().split('T')[0]
}
</script>

<template>
  <div class="container mx-auto px-4 py-8 pt-16">
    <div class="mb-6">
        <NuxtLink to="/seller/dashboard" class="inline-flex items-center gap-2 text-gray-500 hover:text-emerald-600 transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m12 19-7-7 7-7"/>
            <path d="M19 12H5"/>
          </svg>
          <span class="font-medium">Back to Dashboard</span>
        </NuxtLink>
      </div>
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-bold text-slate-800">My Coupons</h1>
      <button
        @click="showCreateForm = true"
        class="px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-semibold"
      >
        Create Coupon
      </button>
    </div>

    <!-- Create Coupon Form -->
    <div v-if="showCreateForm" class="mb-8 bg-white rounded-xl shadow-md p-6 border border-slate-200">
      <h2 class="text-xl font-bold mb-4">Create New Coupon</h2>
      <form @submit.prevent="createCoupon" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-2">Coupon Code</label>
          <input
            v-model="couponForm.code"
            type="text"
            required
            class="w-85% px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
            placeholder="SAVE10"
          >
        </div>

        <div>
          <label class="block text-sm font-medium mb-2">Discount Type</label>
          <select
            v-model="couponForm.type"
            class="w-98% px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
          >
            <option value="percentage">Percentage (%)</option>
            <option value="fixed">Fixed Amount ($)</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-2">
            Discount Value {{ couponForm.type === 'percentage' ? '(%)' : '($)' }}
          </label>
          <input
            v-model="couponForm.value"
            type="number"
            step="0.01"
            min="0"
            :max="couponForm.type === 'percentage' ? 100 : undefined"
            required
            class="w-85% px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
          >
        </div>

        <div>
          <label class="block text-sm font-medium mb-2">Minimum Order Value ($)</label>
          <input
            v-model="couponForm.min_order_value"
            type="number"
            step="0.01"
            min="0"
            required
            class="w-94% px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
          >
        </div>

        <div>
          <label class="block text-sm font-medium mb-2">Usage Limit (Optional)</label>
          <input
            v-model="couponForm.usage_limit"
            type="number"
            min="1"
            class="w-85% px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
            placeholder="Unlimited"
          >
        </div>

        <div>
          <label class="block text-sm font-medium mb-2">Expiry Date</label>
          <input
            v-model="couponForm.expires_at"
            type="date"
            :min="getMinDate()"
            required
            class="w-94% px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
          >
        </div>

        <div class="md:col-span-2 flex gap-3">
          <button
            type="submit"
            :disabled="submitting"
            class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50"
          >
            {{ submitting ? 'Creating...' : 'Create Coupon' }}
          </button>
          <button
            type="button"
            @click="showCreateForm = false"
            class="px-6 py-2 bg-slate-300 text-slate-700 rounded-lg hover:bg-slate-400"
          >
            Cancel
          </button>
        </div>
      </form>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600 mx-auto"></div>
    </div>

    <!-- Coupons List -->
    <div v-else-if="coupons.length === 0" class="text-center py-12 bg-white rounded-xl shadow-md">
      <p class="text-slate-600">No coupons created yet. Create your first coupon!</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="coupon in coupons"
        :key="coupon.id"
        class="bg-white rounded-xl shadow-md p-6 border-2 border-slate-200 relative"
        :class="{ 'opacity-60': isExpired(coupon.expires_at) }"
      >
        <div v-if="isExpired(coupon.expires_at)" class="absolute top-4 right-4">
          <span class="px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-full">
            Expired
          </span>
        </div>

        <div class="mb-4">
          <div class="text-3xl font-bold text-emerald-600 font-mono mb-2">
            {{ coupon.code }}
          </div>
          <div class="text-xl font-bold text-slate-800">
            {{ coupon.type === 'percentage' ? `${coupon.value}% OFF` : `$${coupon.value} OFF` }}
          </div>
        </div>

        <div class="space-y-2 text-sm text-slate-600 mb-4">
          <div class="flex justify-between">
            <span>Min. Order:</span>
            <span class="font-semibold">${{ Number(coupon.min_order_value).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between">
            <span>Usage:</span>
            <span class="font-semibold">
              {{ coupon.usage_count }}{{ coupon.usage_limit ? ` / ${coupon.usage_limit}` : ' (Unlimited)' }}
            </span>
          </div>
          <div class="flex justify-between">
            <span>Expires:</span>
            <span class="font-semibold">{{ formatDate(coupon.expires_at) }}</span>
          </div>
        </div>

        <button
          @click="deleteCoupon(coupon.id)"
          class="w-full px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 font-semibold transition-colors"
        >
          Delete
        </button>
      </div>
    </div>
  </div>
</template>
