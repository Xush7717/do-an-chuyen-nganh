import type { Category, CategoryResponse } from '~/types'

/**
 * Category Service Composable
 * Provides reusable methods for fetching category data from the API
 */
export const useCategoryService = () => {
  const config = useRuntimeConfig()
  const baseUrl = config.public.apiBase

  /**
   * Fetch all categories from the API
   * @returns UseFetchReturn with categories data and loading state
   */
  const getAll = () => {
    return useFetch<CategoryResponse>(`${baseUrl}/categories`, {
      // Transform the response to extract just the data array
      transform: (response) => response,
      // Cache key for this request
      key: 'categories',
    })
  }

  /**
   * Fetch a single category by ID
   * @param id - Category ID
   */
  const getById = (id: number | string) => {
    return useFetch<{ data: Category }>(`${baseUrl}/categories/${id}`, {
      key: `category-${id}`,
    })
  }

  return {
    getAll,
    getById,
  }
}
