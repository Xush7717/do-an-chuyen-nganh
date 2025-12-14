import { defineStore } from 'pinia'
import { useAuthStore } from './auth'

interface CartProduct {
  id: number
  name: string
  price: number
  image_url: string
  category: string | null
  seller: string | null
}

interface CartItem {
  id: number
  product_id: number
  quantity: number
  product: CartProduct
}

export const useCartStore = defineStore('cart', () => {
  const config = useRuntimeConfig()
  const router = useRouter()
  const authStore = useAuthStore()

  const items = ref<CartItem[]>([])
  const loading = ref(false) // For initial fetch
  const updating = ref(false) // For update/remove operations
  const error = ref<string | null>(null)

  /**
   * Computed: Number of distinct products in cart
   */
  const count = computed(() => {
    return items.value.length
  })

  /**
   * Computed: Total amount of all items
   */
  const totalAmount = computed(() => {
    return items.value.reduce((total, item) => {
      return total + (item.product.price * item.quantity)
    }, 0)
  })

  /**
   * Fetch cart items from API
   */
  async function fetchCart() {
    if (!authStore.isAuthenticated) {
      items.value = []
      return
    }

    loading.value = true
    error.value = null

    try {
      const response = await $fetch<{
        success: boolean
        data: CartItem[]
      }>(`${config.public.apiBase}/cart`, {
        headers: {
          Authorization: `Bearer ${authStore.token}`,
        },
      })

      items.value = response.data
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch cart'
      console.error('Fetch cart error:', err)
    } finally {
      loading.value = false
    }
  }

  /**
   * Add product to cart
   * CRITICAL: Redirects to login if not authenticated
   */
  async function addToCart(product: { id: number }, quantity: number = 1) {
    // Check authentication first
    if (!authStore.isAuthenticated) {
      // Redirect to login page
      await router.push('/login')
      return
    }

    updating.value = true
    error.value = null

    try {
      await $fetch(`${config.public.apiBase}/cart/items`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${authStore.token}`,
        },
        body: {
          product_id: product.id,
          quantity,
        },
      })

      // Refresh cart after adding to get updated items
      await fetchCart()
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to add item to cart'
      console.error('Add to cart error:', err)
      throw err
    } finally {
      updating.value = false
    }
  }

  /**
   * Update cart item quantity (with optimistic update)
   */
  async function updateQuantity(itemId: number, quantity: number) {
    if (!authStore.isAuthenticated) {
      return
    }

    // Find the item to update
    const itemIndex = items.value.findIndex(item => item.id === itemId)
    if (itemIndex === -1) return

    // Store original quantity for rollback
    const originalQuantity = items.value[itemIndex].quantity

    // Optimistic update - update UI immediately
    items.value[itemIndex].quantity = quantity

    updating.value = true
    error.value = null

    try {
      await $fetch(`${config.public.apiBase}/cart/items/${itemId}`, {
        method: 'PUT',
        headers: {
          Authorization: `Bearer ${authStore.token}`,
        },
        body: {
          quantity,
        },
      })
      // Success - the optimistic update is already applied
    } catch (err: any) {
      // Rollback on error
      items.value[itemIndex].quantity = originalQuantity
      error.value = err.data?.message || 'Failed to update item'
      console.error('Update quantity error:', err)
      throw err
    } finally {
      updating.value = false
    }
  }

  /**
   * Remove item from cart (with optimistic update)
   */
  async function removeItem(itemId: number) {
    if (!authStore.isAuthenticated) {
      return
    }

    // Store original items for rollback
    const originalItems = [...items.value]

    // Optimistic update - remove from UI immediately
    items.value = items.value.filter(item => item.id !== itemId)

    updating.value = true
    error.value = null

    try {
      await $fetch(`${config.public.apiBase}/cart/items/${itemId}`, {
        method: 'DELETE',
        headers: {
          Authorization: `Bearer ${authStore.token}`,
        },
      })
      // Success - the optimistic update is already applied
    } catch (err: any) {
      // Rollback on error
      items.value = originalItems
      error.value = err.data?.message || 'Failed to remove item'
      console.error('Remove item error:', err)
      throw err
    } finally {
      updating.value = false
    }
  }

  /**
   * Clear error
   */
  function clearError() {
    error.value = null
  }

  /**
   * Clear cart (on logout)
   */
  function clearCart() {
    items.value = []
  }

  return {
    items,
    loading,
    updating,
    error,
    count,
    totalAmount,
    fetchCart,
    addToCart,
    updateQuantity,
    removeItem,
    clearError,
    clearCart,
  }
})
