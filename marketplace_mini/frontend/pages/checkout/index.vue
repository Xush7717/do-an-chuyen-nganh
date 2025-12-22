<script setup lang="ts">
import { nextTick } from 'vue'
import { loadStripe, type Stripe, type StripeElements } from '@stripe/stripe-js'

definePageMeta({
  middleware: 'auth',
})

const config = useRuntimeConfig()
const authStore = useAuthStore()
const cartStore = useCartStore()

// State
const loading = ref(true)
const processing = ref(false)
const errorMessage = ref('')
const isRedirecting = ref(false) // Flag to prevent re-initialization during redirect

// Stripe variables
let stripe: Stripe | null = null
let elements: StripeElements | null = null
let paymentElement: any = null
let clientSecret = ''

// Shipping form
const shippingForm = reactive({
  name: '',
  phone: '',
  address: '',
  city: '',
})

// Form validation errors
const formErrors = reactive({
  name: '',
  phone: '',
  address: '',
  city: '',
})

// Tax calculation (10%)
const taxRate = 0.10
const subtotal = computed(() => cartStore.subtotal)
const discount = computed(() => cartStore.totalDiscount)
const tax = computed(() => (subtotal.value - discount.value) * taxRate)
const total = computed(() => subtotal.value - discount.value + tax.value)

// Initialize Stripe and fetch payment intent
onMounted(async () => {
  // If we're redirecting after order completion, skip initialization
  if (isRedirecting.value) {
    console.log('Redirecting to success page, skipping checkout initialization')
    return
  }

  try {
    // Fetch cart
    await cartStore.fetchCart()

    // // Check if cart is empty
    // if (cartStore.items.length === 0) {
    //   console.log('Cart is empty, redirecting to cart page')
    //   loading.value = false
    //   await navigateTo('/cart', { replace: true })
    //   return
    // }

    // Initialize Stripe
    const stripeKey = config.public.stripeKey
    if (!stripeKey) {
      errorMessage.value = 'Stripe is not configured. Please add NUXT_PUBLIC_STRIPE_KEY to your .env file.'
      loading.value = false
      return
    }

    stripe = await loadStripe(stripeKey)
    if (!stripe) {
      errorMessage.value = 'Failed to load Stripe'
      loading.value = false
      return
    }

    // Fetch PaymentIntent from backend
    const response = await $fetch<{
      success: boolean
      data: {
        clientSecret: string
        amount: number
      }
    }>(`${config.public.apiBase}/checkout/intent`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        'Content-Type': 'application/json',
      },
      body: {
        coupon_codes: cartStore.appliedCoupons.map(c => c.code),
      },
    })

    if (!response.success) {
      errorMessage.value = 'Failed to initialize payment'
      loading.value = false
      return
    }

    clientSecret = response.data.clientSecret

    // Create Stripe Elements
    elements = stripe.elements({
      clientSecret,
      appearance: {
        theme: 'stripe',
        variables: {
          colorPrimary: '#10b981', // emerald-600
        },
      },
    })

    // Set loading to false to render the form
    loading.value = false

    // CRITICAL: Wait for Vue to render the DOM
    await nextTick()

    // Create and mount Payment Element
    try {
      paymentElement = elements.create('payment')

      // Ensure the DOM element exists before mounting
      const paymentContainer = document.getElementById('payment-element')
      if (paymentContainer) {
        // Check if already has children (already mounted)
        if (paymentContainer.children.length > 0) {
          console.log('Payment element already mounted, clearing first')
          paymentContainer.innerHTML = ''
        }

        paymentElement.mount('#payment-element')
        console.log('Payment element mounted successfully')
      } else {
        console.error('Payment element container not found!')
        errorMessage.value = 'Failed to initialize payment form. Please refresh the page.'
      }
    } catch (mountError: any) {
      console.error('Payment element mount error:', mountError)
      errorMessage.value = 'Failed to load payment form. Please refresh the page.'
    }
  } catch (error: any) {
    console.error('Checkout initialization error:', error)
    errorMessage.value = error.data?.message || error.message || 'Failed to initialize checkout'
    loading.value = false
  }
})

// Cleanup on unmount
onBeforeUnmount(() => {
  console.log('Checkout page unmounting, cleaning up Stripe elements')

  if (paymentElement) {
    try {
      paymentElement.unmount()
      paymentElement = null
      console.log('Payment element unmounted')
    } catch (error) {
      console.error('Error unmounting payment element:', error)
    }
  }

  if (elements) {
    try {
      // No need to explicitly destroy, just nullify
      elements = null
      console.log('Stripe Elements instance cleared')
    } catch (error) {
      console.error('Error clearing Stripe Elements:', error)
    }
  }
})

// Validate form
const validateForm = (): boolean => {
  let isValid = true

  // Reset errors
  formErrors.name = ''
  formErrors.phone = ''
  formErrors.address = ''
  formErrors.city = ''

  if (!shippingForm.name.trim()) {
    formErrors.name = 'Name is required'
    isValid = false
  }

  if (!shippingForm.phone.trim()) {
    formErrors.phone = 'Phone is required'
    isValid = false
  }

  if (!shippingForm.address.trim()) {
    formErrors.address = 'Address is required'
    isValid = false
  }

  if (!shippingForm.city.trim()) {
    formErrors.city = 'City is required'
    isValid = false
  }

  return isValid
}

// Handle payment submission
const handleSubmit = async () => {
  if (!stripe || !elements) {
    errorMessage.value = 'Stripe is not initialized'
    return
  }

  // Validate form
  if (!validateForm()) {
    errorMessage.value = 'Please fill in all required fields'
    return
  }

  processing.value = true
  errorMessage.value = ''

  try {
    // Confirm payment with Stripe
    const { error: stripeError, paymentIntent } = await stripe.confirmPayment({
      elements,
      redirect: 'if_required',
      confirmParams: {
        return_url: `${window.location.origin}/checkout/success/pending`,
        payment_method_data: {
          billing_details: {
            name: shippingForm.name,
            phone: shippingForm.phone,
          },
        },
      },
    })

    if (stripeError) {
      console.error('Stripe Error:', stripeError)
      errorMessage.value = stripeError.message || 'Payment failed'
      processing.value = false
      return
    }

    if (paymentIntent && paymentIntent.status === 'succeeded') {
      // Place order in backend
      try {
        console.log('Placing order with payment intent:', paymentIntent.id)

        const orderResponse = await $fetch<{
          success: boolean
          data: {
            order_id: number
          }
        }>(`${config.public.apiBase}/checkout/place-order`, {
          method: 'POST',
          headers: {
            Authorization: `Bearer ${authStore.token}`,
            'Content-Type': 'application/json',
          },
          body: {
            payment_intent_id: paymentIntent.id,
            coupon_codes: cartStore.appliedCoupons.map(c => c.code),
            shipping_address: {
              name: shippingForm.name,
              phone: shippingForm.phone,
              address: shippingForm.address,
              city: shippingForm.city,
            },
          },
        })

        console.log('Order response:', orderResponse)

        if (orderResponse.success) {
          console.log('Order placed successfully, order ID:', orderResponse.data.order_id)

          // Set redirecting flag FIRST to prevent any re-initialization
          isRedirecting.value = true

          // Redirect immediately to success page (cart will be fetched there)
          const redirectPath = `/checkout/success/${orderResponse.data.order_id}`
          console.log('Redirecting to:', redirectPath)

          // Use window.location.replace for hard navigation
          // This ensures the page actually changes and isn't stuck
          window.location.replace(redirectPath)

          // Return to prevent further execution
          return
        } else {
          console.error('Order response not successful:', orderResponse)
          errorMessage.value = 'Order placement failed'
          processing.value = false
        }
      } catch (backendError: any) {
        console.error('Backend Error:', backendError)
        errorMessage.value = backendError.data?.message || backendError.message || 'Failed to create order. Please contact support with your payment confirmation.'
        processing.value = false
      }
    } else {
      console.error('Payment intent status not succeeded:', paymentIntent?.status)
      errorMessage.value = 'Payment was not completed successfully'
      processing.value = false
    }
  } catch (error: any) {
    console.error('Payment error:', error)
    errorMessage.value = error.data?.message || error.message || 'An error occurred during payment'
    processing.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 pt-24 pb-16">
    <div class="container mx-auto px-4">
      <!-- Page Title -->
      <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900">Checkout</h1>
        <p class="text-gray-600 mt-2">Complete your purchase securely</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="errorMessage && !stripe" class="bg-red-50 border border-red-200 rounded-2xl p-6 text-center">
        <svg class="mx-auto h-12 w-12 text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3 class="text-lg font-semibold text-red-900 mb-2">Checkout Error</h3>
        <p class="text-red-700 mb-4">{{ errorMessage }}</p>
        <NuxtLink
          to="/cart"
          class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-6 py-3 rounded-lg transition-colors no-underline"
        >
          Return to Cart
        </NuxtLink>
      </div>

      <!-- Checkout Form -->
      <div v-else class="grid lg:grid-cols-2 gap-8">
        <!-- Left: Shipping Information -->
        <div class="space-y-6">
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Shipping Information</h2>

            <form @submit.prevent="handleSubmit" class="space-y-4">
              <!-- Name -->
              <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                  Full Name <span class="text-red-500">*</span>
                </label>
                <input
                  id="name"
                  v-model="shippingForm.name"
                  type="text"
                  class="w-95% px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                  placeholder="John Doe"
                  :class="{ 'border-red-500': formErrors.name }"
                >
                <p v-if="formErrors.name" class="mt-1 text-sm text-red-600">{{ formErrors.name }}</p>
              </div>

              <!-- Phone -->
              <div>
                <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">
                  Phone Number <span class="text-red-500">*</span>
                </label>
                <input
                  id="phone"
                  v-model="shippingForm.phone"
                  type="tel"
                  class="w-95% px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                  placeholder="+1 (555) 123-4567"
                  :class="{ 'border-red-500': formErrors.phone }"
                >
                <p v-if="formErrors.phone" class="mt-1 text-sm text-red-600">{{ formErrors.phone }}</p>
              </div>

              <!-- Address -->
              <div>
                <label for="address" class="block text-sm font-semibold text-gray-700 mb-2">
                  Street Address <span class="text-red-500">*</span>
                </label>
                <textarea
                  id="address"
                  v-model="shippingForm.address"
                  rows="3"
                  class="w-95% px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent resize-none"
                  placeholder="123 Main St, Apartment 4B"
                  :class="{ 'border-red-500': formErrors.address }"
                ></textarea>
                <p v-if="formErrors.address" class="mt-1 text-sm text-red-600">{{ formErrors.address }}</p>
              </div>

              <!-- City -->
              <div>
                <label for="city" class="block text-sm font-semibold text-gray-700 mb-2">
                  City <span class="text-red-500">*</span>
                </label>
                <input
                  id="city"
                  v-model="shippingForm.city"
                  type="text"
                  class="w-95% px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                  placeholder="New York"
                  :class="{ 'border-red-500': formErrors.city }"
                >
                <p v-if="formErrors.city" class="mt-1 text-sm text-red-600">{{ formErrors.city }}</p>
              </div>
            </form>
          </div>

          <!-- Payment Element -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Payment Details</h2>

            <!-- Stripe Payment Element Container -->
            <div id="payment-element" class="mb-4"></div>

            <!-- Error Message -->
            <div v-if="errorMessage" class="mb-4 bg-red-50 border border-red-200 rounded-lg p-4">
              <p class="text-sm text-red-700">{{ errorMessage }}</p>
            </div>
          </div>
        </div>

        <!-- Right: Order Summary -->
        <div class="lg:sticky lg:top-24 h-fit">
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Order Summary</h2>

            <!-- Cart Items -->
            <div class="space-y-4 mb-6 max-h-80 overflow-y-auto">
              <div
                v-for="item in cartStore.items"
                :key="item.id"
                class="flex gap-4 pb-4 border-b border-gray-100 last:border-0"
              >
                <img
                  :src="item.product.image_url || 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=100&h=100&fit=crop'"
                  :alt="item.product.name"
                  class="w-16 h-16 object-cover rounded-lg flex-shrink-0"
                >
                <div class="flex-1 min-w-0">
                  <h4 class="font-semibold text-gray-900 text-sm truncate">{{ item.product.name }}</h4>
                  <p class="text-sm text-gray-500">Qty: {{ item.quantity }}</p>
                  <p class="text-sm font-semibold text-emerald-600">${{ Number(item.product.price).toFixed(2) }}</p>
                </div>
                <div class="text-right">
                  <p class="font-semibold text-gray-900">${{ (Number(item.product.price) * item.quantity).toFixed(2) }}</p>
                </div>
              </div>
            </div>

            <!-- Pricing Summary -->
            <div class="space-y-3 mb-6 pt-4 border-t border-gray-200">
              <div class="flex justify-between text-gray-600">
                <span>Subtotal</span>
                <span class="font-semibold text-gray-900">${{ subtotal.toFixed(2) }}</span>
              </div>

              <!-- Multiple Coupons Display -->
              <div v-if="cartStore.appliedCoupons.length > 0" class="space-y-2">
                <div
                  v-for="coupon in cartStore.appliedCoupons"
                  :key="coupon.code"
                  class="flex justify-between text-emerald-600"
                >
                  <div class="flex items-center gap-2">
                    <span>Discount</span>
                    <span class="text-xs font-mono bg-emerald-100 px-2 py-0.5 rounded">{{ coupon.code }}</span>
                  </div>
                  <span class="font-semibold">-${{ coupon.discountAmount.toFixed(2) }}</span>
                </div>
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

            <!-- Submit Button -->
            <button
              @click="handleSubmit"
              :disabled="processing"
              class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-4 px-6 rounded-xl transition-all shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:shadow-lg"
            >
              <span v-if="processing" class="flex items-center justify-center gap-2">
                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing Payment...
              </span>
              <span v-else>Pay ${{ total.toFixed(2) }}</span>
            </button>

            <!-- Security Badge -->
            <div class="mt-6 pt-6 border-t border-gray-200">
              <div class="flex items-center justify-center gap-2 text-sm text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <span>Secured by Stripe</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
