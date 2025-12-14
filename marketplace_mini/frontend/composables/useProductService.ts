import type { Product, ProductResponse, ProductsResponse, ProductFilters } from '~/types'

/**
 * Product Service Composable
 * Provides reusable methods for fetching product data from the API
 */
export const useProductService = () => {
  const config = useRuntimeConfig()
  const baseUrl = config.public.apiBase

  /**
   * Fetch products with optional filters and pagination
   * @param params - Filter parameters (search, category_id, price range, sort, etc.)
   * @returns UseFetchReturn with paginated products data and loading state
   */
  const getProducts = (params: ProductFilters = {}) => {
    // Build query string from params
    const queryParams = new URLSearchParams()

    if (params.search) queryParams.append('search', params.search)
    if (params.category_id) queryParams.append('category_id', params.category_id.toString())
    if (params.min_price) queryParams.append('min_price', params.min_price.toString())
    if (params.max_price) queryParams.append('max_price', params.max_price.toString())
    if (params.sort) queryParams.append('sort', params.sort)
    if (params.page) queryParams.append('page', params.page.toString())
    if (params.limit) queryParams.append('limit', params.limit.toString())

    const queryString = queryParams.toString()
    const url = `${baseUrl}/products${queryString ? `?${queryString}` : ''}`

    return useFetch<ProductsResponse>(url, {
      key: `products-${queryString}`,
      // Watch for param changes to refetch
      watch: false,
    })
  }

  /**
   * Fetch a single product by ID with relationships
   * @param id - Product ID
   * @returns UseFetchReturn with product data
   */
  const getProductById = (id: number | string) => {
    return useFetch<ProductResponse>(`${baseUrl}/products/${id}`, {
      key: `product-${id}`,
    })
  }

  /**
   * Fetch featured products for homepage (top 8 newest)
   * Shortcut method for homepage display
   * @returns UseFetchReturn with featured products
   */
  const getFeatured = () => {
    return useFetch<ProductsResponse>(`${baseUrl}/products?limit=8&sort=newest`, {
      key: 'products-featured',
    })
  }

  return {
    getProducts,
    getProductById,
    getFeatured,
  }
}
