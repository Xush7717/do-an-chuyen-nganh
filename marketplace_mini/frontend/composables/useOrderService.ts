import type { OrderResponse, OrdersResponse } from '~/types'

/**
 * Order Service Composable
 * Provides methods for fetching order data from the API
 */
export const useOrderService = () => {
  const config = useRuntimeConfig()
  const authStore = useAuthStore()
  const baseUrl = config.public.apiBase

  /**
   * Fetch all orders for the authenticated user (paginated)
   * @param page - Optional page number for pagination
   * @returns UseFetchReturn with paginated orders data
   */
  const getMyOrders = (page: number = 1) => {
    return useFetch<OrdersResponse>(`${baseUrl}/orders?page=${page}`, {
      key: `orders-page-${page}`,
      headers: {
        Authorization: `Bearer ${authStore.token}`,
      },
    })
  }

  /**
   * Fetch a single order by ID
   * @param id - Order ID
   * @returns UseFetchReturn with order details
   */
  const getOrderDetails = (id: number | string) => {
    return useFetch<OrderResponse>(`${baseUrl}/orders/${id}`, {
      key: `order-${id}`,
      headers: {
        Authorization: `Bearer ${authStore.token}`,
      },
    })
  }

  return {
    getMyOrders,
    getOrderDetails,
  }
}
