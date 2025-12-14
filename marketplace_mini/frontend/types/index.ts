/**
 * Category entity type
 */
export interface Category {
  id: number
  name: string
  slug: string
  icon_class: string
  products_count?: number
  created_at?: string
  updated_at?: string
}

/**
 * API Response wrapper for categories
 */
export interface CategoryResponse {
  data: Category[]
}

/**
 * Seller profile entity
 */
export interface Seller {
  user_id: number
  shop_name: string
  description?: string
  logo_url?: string
  created_at?: string
  updated_at?: string
}

/**
 * User entity (simplified for seller info)
 */
export interface User {
  id: number
  name: string
  email: string
  role: string
  seller?: Seller
}

/**
 * Product entity type
 */
export interface Product {
  id: number
  seller_id: number
  category_id: number
  name: string
  slug: string
  description?: string
  price: number
  stock_quantity: number
  image_url?: string
  created_at?: string
  updated_at?: string
  // Relationships
  seller?: User
  category?: Category
}

/**
 * API Response wrapper for single product
 */
export interface ProductResponse {
  data: Product
}

/**
 * Laravel pagination link item
 */
export interface PaginationLinkItem {
  url: string | null
  label: string
  active: boolean
}

/**
 * API Response wrapper for paginated products (Laravel structure)
 */
export interface ProductsResponse {
  data: Product[]
  current_page: number
  first_page_url: string
  from: number
  last_page: number
  last_page_url: string
  links: PaginationLinkItem[]
  next_page_url: string | null
  path: string
  per_page: number
  prev_page_url: string | null
  to: number
  total: number
}

/**
 * Product filters for catalog page
 */
export interface ProductFilters {
  search?: string
  category_id?: number
  min_price?: number
  max_price?: number
  sort?: 'newest' | 'price_asc' | 'price_desc'
  page?: number
  limit?: number
}
