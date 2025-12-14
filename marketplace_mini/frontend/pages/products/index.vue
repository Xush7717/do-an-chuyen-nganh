<script setup lang="ts">
import type { ProductFilters, ProductsResponse } from '~/types'

definePageMeta({
  layout: 'default',
})

// Get route to read URL query parameters
const route = useRoute()

// Fetch categories for filter sidebar
const { getAll } = useCategoryService()
const { data: categoriesData } = await getAll()
const categories = computed(() => categoriesData.value?.data || [])

// Separate search query for input (not live)
// Initialize from URL query param if present (from header search)
const searchQuery = ref((route.query.search as string) || '')

// Parse category_id from URL query param
const categoryIdFromUrl = route.query.category_id
  ? parseInt(route.query.category_id as string)
  : undefined

// Reactive filters
const filters = ref<ProductFilters>({
  search: (route.query.search as string) || '',
  category_id: categoryIdFromUrl,
  min_price: undefined,
  max_price: undefined,
  sort: 'newest',
  page: 1,
  limit: 12,
})

// Build API URL from filters
const buildApiUrl = (filterParams: ProductFilters) => {
  const params = new URLSearchParams()
  if (filterParams.search) params.append('search', filterParams.search)
  if (filterParams.category_id) params.append('category_id', filterParams.category_id.toString())
  if (filterParams.min_price) params.append('min_price', filterParams.min_price.toString())
  if (filterParams.max_price) params.append('max_price', filterParams.max_price.toString())
  if (filterParams.sort) params.append('sort', filterParams.sort)
  if (filterParams.page) params.append('page', filterParams.page.toString())
  if (filterParams.limit) params.append('limit', filterParams.limit.toString())

  const queryString = params.toString()
  return queryString ? `?${queryString}` : ''
}

// Reactive data
const config = useRuntimeConfig()
const productsData = ref<ProductsResponse | null>(null)
const productsLoading = ref(true)

// Fetch products function
const fetchProducts = async () => {
  productsLoading.value = true
  try {
    const url = `${config.public.apiBase}/products${buildApiUrl(filters.value)}`
    const response = await $fetch<ProductsResponse>(url)
    productsData.value = response
  } catch (error) {
    console.error('Error fetching products:', error)
    productsData.value = null
  } finally {
    productsLoading.value = false
  }
}

// Initial fetch
await fetchProducts()

// Watch filters and refetch
watch(filters, async () => {
  await fetchProducts()
}, { deep: true })

// Computed properties for products and pagination
const products = computed(() => productsData.value?.data || [])
const pagination = computed(() => {
  if (!productsData.value) return null
  return {
    current_page: productsData.value.current_page,
    last_page: productsData.value.last_page,
    per_page: productsData.value.per_page,
    total: productsData.value.total,
    from: productsData.value.from,
    to: productsData.value.to,
  }
})

// Filter handlers
const handleCategoryFilter = (categoryId: number | undefined) => {
  filters.value.category_id = categoryId
  filters.value.page = 1 // Reset to first page
}

const handleSortChange = (sort: 'newest' | 'price_asc' | 'price_desc') => {
  filters.value.sort = sort
  filters.value.page = 1
}

const handlePriceFilter = () => {
  filters.value.page = 1
}

const handlePageChange = (page: number) => {
  filters.value.page = page
  // Scroll to top of products
  window.scrollTo({ top: 300, behavior: 'smooth' })
}

// Handle search button click
const handleSearch = () => {
  filters.value.search = searchQuery.value.trim()
  filters.value.page = 1 // Reset to first page
}

// Clear all filters
const clearFilters = () => {
  searchQuery.value = '' // Clear search input
  filters.value = {
    search: '',
    category_id: undefined,
    min_price: undefined,
    max_price: undefined,
    sort: 'newest',
    page: 1,
    limit: 12,
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-100">
    <div class="container mx-auto px-4 py-8">
      <!-- Page Header -->
      <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">Browse Products</h1>
        <p class="text-lg text-gray-600">Discover amazing products from verified sellers</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Sidebar Filters -->
        <aside class="lg:col-span-1">
          <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sticky top-24">
            <!-- Header with Clear Button -->
            <div class="mb-5 pb-4 border-b border-gray-200">
              <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-bold text-gray-900">Filters</h2>
                <button
                  @click="clearFilters"
                  class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white bg-red-500 hover:bg-red-700 rounded-lg transition-colors"
                >
                  <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                  Clear All
                </button>
              </div>
            </div>

            <!-- Search Filter -->
            <div class="mb-5">
              <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
              <div class="flex gap-2">
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search products..."
                  class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  @keyup.enter="handleSearch"
                >
                <button
                  @click="handleSearch"
                  class="px-4 py-2 bg-blue-500 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-1"
                  title="Search"
                >
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Category Filter -->
            <div class="mb-5">
              <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
              <div class="space-y-1.5">
                <button
                  @click="handleCategoryFilter(undefined)"
                  :class="[
                    'w-full text-left px-3 py-2 rounded-lg transition-colors text-sm',
                    !filters.category_id
                      ? 'bg-accent-100 text-accent-800 font-medium'
                      : 'hover:bg-accent-500 text-gray-700'
                  ]"
                >
                  All Categories
                </button>
                <button
                  v-for="category in categories"
                  :key="category.id"
                  @click="handleCategoryFilter(category.id)"
                  :class="[
                    'w-full text-left px-3 py-2 rounded-lg transition-colors flex items-center gap-2 text-sm',
                    filters.category_id === category.id
                      ? 'bg-accent-100 text-accent-800 font-medium'
                      : 'hover:bg-accent-500 text-gray-700'
                  ]"
                >
                  <Icon :icon="category.icon_class" class="text-base flex-shrink-0" />
                  <span class="truncate">{{ category.name }}</span>
                </button>
              </div>
            </div>

            <!-- Price Range Filter -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Price Range</label>
              <div class="space-y-2">
                <input
                  v-model.number="filters.min_price"
                  type="number"
                  placeholder="Min ($)"
                  min="0"
                  step="0.01"
                  class="w-90% px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  @change="handlePriceFilter"
                >
                <input
                  v-model.number="filters.max_price"
                  type="number"
                  placeholder="Max ($)"
                  min="0"
                  step="0.01"
                  class="w-90% px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  @change="handlePriceFilter"
                >
              </div>
            </div>
          </div>
        </aside>

        <!-- Main Content -->
        <main class="lg:col-span-3">
          <!-- Toolbar -->
          <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="text-sm text-gray-600">
              <span v-if="pagination" class="font-medium text-gray-900">
                {{ pagination.total }} products found
              </span>
            </div>

            <!-- Sort Dropdown -->
            <div class="flex items-center gap-2">
              <label class="text-sm font-medium text-gray-700">Sort by:</label>
              <select
                v-model="filters.sort"
                @change="handleSortChange(filters.sort!)"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-transparent bg-white"
              >
                <option value="newest">Newest</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
              </select>
            </div>
          </div>

          <!-- Products Grid with Loading State -->
          <div class="mb-8">
            <!-- Loading Skeleton -->
            <div v-if="productsLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
              <div
                v-for="n in 12"
                :key="`skeleton-${n}`"
                class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 animate-pulse"
              >
                <!-- Skeleton Image -->
                <div class="aspect-square bg-gray-200"></div>
                <!-- Skeleton Content -->
                <div class="p-5 space-y-3">
                  <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                  <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                  <div class="h-6 bg-gray-200 rounded w-1/3"></div>
                  <div class="h-10 bg-gray-200 rounded"></div>
                </div>
              </div>
            </div>

            <!-- Products Grid -->
            <div v-else-if="products.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
              <ProductCard
                v-for="product in products"
                :key="product.id"
                :product="product"
              />
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
              <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
              </svg>
              <h3 class="text-xl font-semibold text-gray-900 mb-2">No products found</h3>
              <p class="text-gray-600 mb-4">Try adjusting your filters or search terms</p>
              <button
                @click="clearFilters"
                class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white font-medium px-6 py-3 rounded-lg transition-colors"
              >
                Clear Filters
              </button>
            </div>
          </div>

          <!-- Pagination (Always visible when there's pagination data) -->
          <div v-if="pagination && pagination.last_page > 1" class="flex justify-center items-center gap-2">
            <button
              @click="handlePageChange(pagination.current_page - 1)"
              :disabled="productsLoading || pagination.current_page === 1"
              :class="[
                'px-4 py-2 rounded-lg font-medium transition-colors',
                productsLoading || pagination.current_page === 1
                  ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                  : 'bg-white border border-gray-300 text-gray-700 hover:bg-slate-50'
              ]"
            >
              Previous
            </button>

            <!-- Page Numbers -->
            <div class="flex gap-2">
              <button
                v-for="page in Math.min(pagination.last_page, 5)"
                :key="page"
                @click="handlePageChange(page)"
                :disabled="productsLoading"
                :class="[
                  'px-4 py-2 rounded-lg font-medium transition-colors',
                  productsLoading
                    ? 'opacity-50 cursor-not-allowed'
                    : '',
                  pagination.current_page === page
                    ? 'bg-secondary text-white'
                    : 'bg-white border border-gray-300 text-gray-700 hover:bg-slate-50'
                ]"
              >
                {{ page }}
              </button>
            </div>

            <button
              @click="handlePageChange(pagination.current_page + 1)"
              :disabled="productsLoading || pagination.current_page === pagination.last_page"
              :class="[
                'px-4 py-2 rounded-lg font-medium transition-colors',
                productsLoading || pagination.current_page === pagination.last_page
                  ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                  : 'bg-white border border-gray-300 text-gray-700 hover:bg-slate-50'
              ]"
            >
              Next
            </button>
          </div>
        </main>
      </div>
    </div>
  </div>
</template>
