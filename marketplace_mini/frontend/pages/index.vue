<script setup lang="ts">
definePageMeta({
  layout: 'default',
})

// Dummy product data
const products = ref([
  {
    id: 1,
    name: 'Premium Wireless Headphones',
    price: 299.99,
    image: 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&h=500&fit=crop',
    category: 'Electronics',
    rating: 4.8,
    seller: 'TechPro Official',
  },
  {
    id: 2,
    name: 'Minimalist Leather Wallet',
    price: 79.99,
    image: 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=500&h=500&fit=crop',
    category: 'Fashion',
    rating: 4.9,
    seller: 'StyleCraft',
  },
  {
    id: 3,
    name: 'Smart Watch Pro Series',
    price: 399.99,
    image: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500&h=500&fit=crop',
    category: 'Electronics',
    rating: 4.7,
    seller: 'TechPro Official',
  },
  {
    id: 4,
    name: 'Organic Cotton T-Shirt',
    price: 49.99,
    image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=500&h=500&fit=crop',
    category: 'Fashion',
    rating: 4.6,
    seller: 'EcoWear',
  },
  {
    id: 5,
    name: 'Mechanical Keyboard RGB',
    price: 159.99,
    image: 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=500&h=500&fit=crop',
    category: 'Electronics',
    rating: 4.9,
    seller: 'Digital Dreams',
  },
  {
    id: 6,
    name: 'Designer Sunglasses',
    price: 189.99,
    image: 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=500&h=500&fit=crop',
    category: 'Fashion',
    rating: 4.8,
    seller: 'StyleCraft',
  },
  {
    id: 7,
    name: 'Portable Speaker Bluetooth',
    price: 129.99,
    image: 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=500&h=500&fit=crop',
    category: 'Electronics',
    rating: 4.7,
    seller: 'TechPro Official',
  },
  {
    id: 8,
    name: 'Canvas Backpack Premium',
    price: 99.99,
    image: 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=500&h=500&fit=crop',
    category: 'Fashion',
    rating: 4.9,
    seller: 'Modern Essentials',
  },
])

// Fetch categories from API
const config = useRuntimeConfig()
const { data: categoriesData, pending: categoriesLoading } = await useFetch<{ data: any[] }>(
  `${config.public.apiBase}/categories`
)

// Limit to 6 categories for home page display
const displayedCategories = computed(() => {
  return categoriesData.value?.data?.slice(0, 6) || []
})
</script>

<template>
  <div class="min-h-screen">
    <!-- Hero Section (High Contrast Mode) -->
    <section class="bg-slate-900 text-white py-20 lg:py-28">
      <div class="container mx-auto px-4">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
          <!-- Text Left -->
          <div class="space-y-6">
            <div class="inline-block">
              <span class="bg-violet-600/20 text-violet-300 px-4 py-2 rounded-full text-sm font-medium backdrop-blur-sm border border-violet-500/30">
                New Collection 2024
              </span>
            </div>
            <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold leading-tight">
              Discover the
              <span class="bg-gradient-to-r from-violet-400 to-purple-400 bg-clip-text text-transparent">
                Extraordinary
              </span>
            </h1>
            <p class="text-xl text-gray-400 max-w-lg">
              Premium marketplace for digital and physical goods. Shop from verified sellers worldwide.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 pt-4">
              <NuxtLink
                to="/products"
                class="inline-flex items-center justify-center gap-2 bg-violet-600 hover:bg-violet-700 text-white font-semibold px-8 py-4 rounded-full transition-all duration-300 shadow-lg shadow-violet-600/30 hover:shadow-xl hover:shadow-violet-600/40 hover:scale-105 no-underline"
              >
                <span>Explore Now</span>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
              </NuxtLink>
              <NuxtLink
                to="/about"
                class="inline-flex items-center justify-center gap-2 bg-white/10 hover:bg-white/20 text-white font-semibold px-8 py-4 rounded-full transition-all duration-300 backdrop-blur-sm border border-white/20 no-underline"
              >
                Learn More
              </NuxtLink>
            </div>
          </div>

          <!-- Image Right -->
          <div class="relative hidden lg:block">
            <div class="relative z-10 transform hover:scale-105 transition-transform duration-500">
              <img
                src="https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=800&h=800&fit=crop"
                alt="Featured Product"
                class="rounded-3xl shadow-2xl shadow-violet-900/50"
              >
              <!-- Floating Badge -->
              <div class="absolute -top-6 -right-6 bg-gradient-to-br from-amber-400 to-orange-500 text-white px-6 py-4 rounded-2xl shadow-xl transform rotate-12">
                <div class="text-2xl font-bold">50%</div>
                <div class="text-xs uppercase tracking-wide">Off</div>
              </div>
            </div>
            <!-- Decorative Elements -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-violet-600/20 rounded-full blur-3xl -z-10"></div>
          </div>
        </div>
      </div>
    </section>

    <!-- Promo Banner -->
    <section class="bg-gradient-to-r from-teal-500 to-cyan-600 py-20">
      <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center text-white space-y-6">
          <div class="inline-block">
            <span class="bg-white/20 backdrop-blur-sm px-6 py-2 rounded-full text-sm font-medium uppercase tracking-wider border border-white/30">
              Limited Time Offer
            </span>
          </div>
          <h2 class="text-5xl md:text-6xl font-bold">
            Flash Sale - 50% Off
          </h2>
          <p class="text-xl text-teal-50 max-w-2xl mx-auto">
            Get amazing deals on selected items. Hurry up, offer ends soon!
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center pt-6">
            <NuxtLink
              to="/products"
              class="inline-flex items-center justify-center gap-2 bg-white text-teal-600 hover:bg-teal-50 font-bold px-8 py-4 rounded-full transition-all duration-300 shadow-xl hover:shadow-2xl hover:scale-105 no-underline"
            >
              <span>Shop Now</span>
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </NuxtLink>
          </div>

          <!-- Countdown Timer (Mock) -->
          <div class="flex justify-center gap-6 pt-8">
            <div class="bg-white/20 backdrop-blur-sm rounded-xl px-6 py-4 border border-white/30">
              <div class="text-3xl font-bold">12</div>
              <div class="text-sm text-teal-100">Hours</div>
            </div>
            <div class="bg-white/20 backdrop-blur-sm rounded-xl px-6 py-4 border border-white/30">
              <div class="text-3xl font-bold">34</div>
              <div class="text-sm text-teal-100">Minutes</div>
            </div>
            <div class="bg-white/20 backdrop-blur-sm rounded-xl px-6 py-4 border border-white/30">
              <div class="text-3xl font-bold">56</div>
              <div class="text-sm text-teal-100">Seconds</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Categories -->
     <section class="py-16 bg-slate-100">
      <div class="container mx-auto px-4">
        <div class="text-center mb-12">
          <h2 class="text-4xl font-bold text-gray-900 mb-4">Shop by Category</h2>
          <p class="text-lg text-gray-600">Explore our wide range of products</p>
        </div>

        <!-- Loading State -->
        <div v-if="categoriesLoading" class="flex justify-center items-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-violet-600"></div>
        </div>

        <!-- Categories Grid -->
        <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
          <div
            v-for="category in displayedCategories"
            :key="category.id"
            class="group bg-white rounded-2xl p-6 hover:shadow-md hover:-translate-y-1 transition-all duration-300 cursor-pointer border border-gray-100"
          >
            <div class="flex flex-col items-center text-center space-y-3">
              <Icon
                :icon="category.icon_class"
                class="text-5xl mb-2 text-violet-600 group-hover:scale-110 transition-transform duration-300"
              />
              <h3 class="font-semibold text-gray-900">{{ category.name }}</h3>
            </div>
          </div>
        </div>
      </div>
    </section>
    

    <!-- Featured Products -->
    <section class="py-16 bg-slate-200">
      <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-12">
          <div>
            <h2 class="text-4xl font-bold text-gray-900 mb-2">New Arrivals</h2>
            <p class="text-lg text-gray-600">Discover our latest products</p>
          </div>
          <NuxtLink
            to="/products"
            class="mt-4 md:mt-0 inline-flex items-center gap-2 text-violet-600 hover:text-#3498DB font-semibold transition-colors no-underline"
          >
            View All Products
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
          </NuxtLink>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <ProductCard
            v-for="product in products"
            :key="product.id"
            :product="product"
          />
        </div>
      </div>
    </section>

    <!-- Trust Indicators -->
    <section class="bg-white shadow-sm py-8 border-b border-gray-100">
      <div class="container mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <div class="flex flex-col items-center text-center space-y-2">
            <div class="h-12 w-12 bg-#fee685 rounded-full flex items-center justify-center">
              <svg class="h-6 w-6 text-#D35400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
              </svg>
            </div>
            <h3 class="font-semibold text-gray-900">Free Shipping</h3>
            <p class="text-sm text-gray-600">On orders over $50</p>
          </div>

          <div class="flex flex-col items-center text-center space-y-2">
            <div class="h-12 w-12 bg-#fee685 rounded-full flex items-center justify-center">
              <svg class="h-6 w-6 text-#D35400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </div>
            <h3 class="font-semibold text-gray-900">Secure Payment</h3>
            <p class="text-sm text-gray-600">100% protected</p>
          </div>

          <div class="flex flex-col items-center text-center space-y-2">
            <div class="h-12 w-12 bg-#fee685 rounded-full flex items-center justify-center">
              <svg class="h-6 w-6 text-#D35400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
            <h3 class="font-semibold text-gray-900">24/7 Support</h3>
            <p class="text-sm text-gray-600">Always here to help</p>
          </div>

          <div class="flex flex-col items-center text-center space-y-2">
            <div class="h-12 w-12 bg-#fee685 rounded-full flex items-center justify-center">
              <svg class="h-6 w-6 text-#D35400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <h3 class="font-semibold text-gray-900">Buyer Protection</h3>
            <p class="text-sm text-gray-600">Money-back guarantee</p>
          </div>
        </div>
      </div>
    </section>  

    <!-- Newsletter Section -->
  </div>
</template>
