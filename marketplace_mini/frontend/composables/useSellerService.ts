/**
 * Seller Service Composable
 * Provides methods for seller-specific operations (product management, orders)
 */
export const useSellerService = () => {
  const config = useRuntimeConfig()
  const baseUrl = config.public.apiBase
  const authStore = useAuthStore()

  /**
   * Get authorization headers
   */
  const getHeaders = () => {
    return {
      Authorization: `Bearer ${authStore.token}`,
    }
  }

  /**
   * Fetch seller's products with pagination
   * @param page - Page number
   * @param perPage - Items per page
   */
  const getProducts = async (page = 1, perPage = 15) => {
    return await $fetch(`${baseUrl}/seller/products?page=${page}&per_page=${perPage}`, {
      headers: getHeaders(),
    })
  }

  /**
   * Get a single product by ID
   * @param id - Product ID
   */
  const getProduct = async (id: number) => {
    return await $fetch(`${baseUrl}/seller/products/${id}`, {
      headers: getHeaders(),
    })
  }

  /**
   * Create a new product with image upload
   * @param formData - FormData containing product fields and image file
   */
  const createProduct = async (formData: FormData) => {
    return await $fetch(`${baseUrl}/seller/products`, {
      method: 'POST',
      headers: getHeaders(),
      body: formData,
    })
  }

  /**
   * Update an existing product
   * @param id - Product ID
   * @param formData - FormData containing updated fields
   */
  const updateProduct = async (id: number, formData: FormData) => {
    // Laravel doesn't support PUT with FormData, use POST with _method override
    formData.append('_method', 'PUT')
    return await $fetch(`${baseUrl}/seller/products/${id}`, {
      method: 'POST',
      headers: getHeaders(),
      body: formData,
    })
  }

  /**
   * Delete a product
   * @param id - Product ID
   */
  const deleteProduct = async (id: number) => {
    return await $fetch(`${baseUrl}/seller/products/${id}`, {
      method: 'DELETE',
      headers: getHeaders(),
    })
  }

  /**
   * Fetch seller's orders (orders containing seller's products)
   * @param page - Page number
   * @param perPage - Items per page
   */
  const getOrders = async (page = 1, perPage = 15) => {
    return await $fetch(`${baseUrl}/seller/orders?page=${page}&per_page=${perPage}`, {
      headers: getHeaders(),
    })
  }

  /**
   * Get a single order by ID
   * @param id - Order ID
   */
  const getOrder = async (id: number) => {
    return await $fetch(`${baseUrl}/seller/orders/${id}`, {
      headers: getHeaders(),
    })
  }

  /**
   * Update order status
   * @param orderId - Order ID
   * @param status - New status (pending, processing, shipped, delivered, cancelled)
   */
  const updateOrderStatus = async (orderId: number, status: string) => {
    return await $fetch(`${baseUrl}/seller/orders/${orderId}/status`, {
      method: 'PATCH',
      headers: getHeaders(),
      body: { status },
    })
  }

  /**
   * Fetch dashboard statistics
   * Includes total products, orders, revenue, and recent orders
   */
  const getDashboardStats = async () => {
    return await $fetch(`${baseUrl}/seller/stats`, {
      headers: getHeaders(),
    })
  }

  return {
    getProducts,
    getProduct,
    createProduct,
    updateProduct,
    deleteProduct,
    getOrders,
    getOrder,
    updateOrderStatus,
    getDashboardStats,
  }
}
