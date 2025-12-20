<script setup lang="ts">
definePageMeta({
  middleware: 'auth',
})

const authStore = useAuthStore()
const sellerService = useSellerService()
const router = useRouter()

// Check if user is a seller
if (authStore.user?.role !== 'seller') {
  router.push('/')
}

// Form state
const form = ref({
  name: '',
  category_id: '',
  description: '',
  price: '',
  stock_quantity: '',
})

const imageFile = ref<File | null>(null)
const imagePreview = ref<string | null>(null)
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})
const categories = ref<any[]>([])
const loadingCategories = ref(true)

// Fetch categories
const fetchCategories = async () => {
  loadingCategories.value = true
  try {
    const config = useRuntimeConfig()
    const response: any = await $fetch(`${config.public.apiBase}/categories`)
    categories.value = response.data || []
  } catch (err) {
    console.error('Failed to fetch categories:', err)
  } finally {
    loadingCategories.value = false
  }
}

// Load categories on mount
onMounted(() => {
  fetchCategories()
})

// Handle file input
const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]

  if (file) {
    // Validate file size (max 2MB)
    if (file.size > 2048 * 1024) {
      errors.value.image = ['Image file size must not exceed 2MB']
      imageFile.value = null
      imagePreview.value = null
      return
    }

    // Validate file type
    if (!file.type.startsWith('image/')) {
      errors.value.image = ['File must be an image']
      imageFile.value = null
      imagePreview.value = null
      return
    }

    imageFile.value = file
    errors.value.image = []

    // Create preview
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target?.result as string
    }
    reader.readAsDataURL(file)
  }
}

// Submit form
const submitForm = async () => {
  if (!imageFile.value) {
    errors.value.image = ['Product image is required']
    return
  }

  loading.value = true
  errors.value = {}

  try {
    // Create FormData
    const formData = new FormData()
    formData.append('name', form.value.name)
    formData.append('category_id', form.value.category_id)
    formData.append('description', form.value.description)
    formData.append('price', form.value.price)
    formData.append('stock_quantity', form.value.stock_quantity)
    formData.append('image', imageFile.value)

    await sellerService.createProduct(formData)

    // Success - redirect to products list
    router.push('/seller/products')
  } catch (err: any) {
    if (err.data?.errors) {
      errors.value = err.data.errors
    } else {
      errors.value.general = [err.data?.message || 'Failed to create product']
    }
  } finally {
    loading.value = false
  }
}

// Get error message for field
const getError = (field: string): string => {
  return errors.value[field]?.[0] || ''
}
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <div class="max-w-3xl mx-auto pt-24 px-4 sm:px-6 lg:px-8 pb-16">
      <!-- Back to Dashboard -->
      <div class="mb-6 ">
        <NuxtLink to="/seller/dashboard" class="inline-flex items-center gap-2 text-gray-500 hover:text-emerald-600 transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m12 19-7-7 7-7"/>
            <path d="M19 12H5"/>
          </svg>
          <span class="font-medium">Back to Dashboard</span>
        </NuxtLink>
      </div>

      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Add New Product</h1>
        <p class="mt-2 text-gray-600">Create a new product listing for your store</p>
      </div>

      <!-- General Error -->
      <div v-if="errors.general" class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
        <p class="text-red-800 text-sm">{{ errors.general[0] }}</p>
      </div>

      <!-- Form -->
      <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
        <!-- Product Name -->
        <div>
          <label for="name" class="block text-sm font-medium text-gray-900 mb-2">
            Product Name <span class="text-red-600">*</span>
          </label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            required
            class="w-95% px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            :class="{ 'border-red-500': getError('name') }"
            placeholder="Enter product name"
          >
          <p v-if="getError('name')" class="mt-2 text-sm text-red-600">{{ getError('name') }}</p>
        </div>

        <!-- Category -->
        <div>
          <label for="category" class="block text-sm font-medium text-gray-900 mb-2">
            Category <span class="text-red-600">*</span>
          </label>
          <select
            id="category"
            v-model="form.category_id"
            required
            :disabled="loadingCategories"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
            :class="{ 'border-red-500': getError('category_id') }"
          >
            <option value="">{{ loadingCategories ? 'Loading categories...' : 'Select a category' }}</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
          <p v-if="getError('category_id')" class="mt-2 text-sm text-red-600">{{ getError('category_id') }}</p>
        </div>

        <!-- Description -->
        <div>
          <label for="description" class="block text-sm font-medium text-gray-900 mb-2">
            Description
          </label>
          <textarea
            id="description"
            v-model="form.description"
            rows="4"
            class="w-95% px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            :class="{ 'border-red-500': getError('description') }"
            placeholder="Describe your product..."
          />
          <p v-if="getError('description')" class="mt-2 text-sm text-red-600">{{ getError('description') }}</p>
        </div>

        <!-- Price and Stock (Side by Side) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Price -->
          <div>
            <label for="price" class="block text-sm font-medium text-gray-900 mb-2">
              Price (USD) <span class="text-red-600">*</span>
            </label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">$</span>
              <input
                id="price"
                v-model="form.price"
                type="number"
                step="0.01"
                min="0"
                required
                class="w-80% pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                :class="{ 'border-red-500': getError('price') }"
                placeholder="0.00"
              >
            </div>
            <p v-if="getError('price')" class="mt-2 text-sm text-red-600">{{ getError('price') }}</p>
          </div>

          <!-- Stock Quantity -->
          <div>
            <label for="stock" class="block text-sm font-medium text-gray-900 mb-2">
              Stock Quantity <span class="text-red-600">*</span>
            </label>
            <input
              id="stock"
              v-model="form.stock_quantity"
              type="number"
              min="0"
              required
              class="w-90% px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
              :class="{ 'border-red-500': getError('stock_quantity') }"
              placeholder="0"
            >
            <p v-if="getError('stock_quantity')" class="mt-2 text-sm text-red-600">{{ getError('stock_quantity') }}</p>
          </div>
        </div>

        <!-- Product Image -->
        <div>
          <label for="image" class="block text-sm font-medium text-gray-900 mb-2">
            Product Image <span class="text-red-600">*</span>
          </label>
          <div class="flex flex-col gap-4">
            <input
              id="image"
              type="file"
              accept="image/*"
              required
              @change="handleFileChange"
              class="block w-full text-sm text-gray-600 file:mr-4 file:py-3 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 file:cursor-pointer cursor-pointer"
              :class="{ 'border-red-500': getError('image') }"
            >
            <!-- Image Preview -->
            <div v-if="imagePreview" class="relative inline-block">
              <img
                :src="imagePreview"
                alt="Preview"
                class="h-48 w-48 object-cover rounded-lg border-2 border-gray-200"
              >
              <button
                type="button"
                @click="imageFile = null; imagePreview = null"
                class="absolute -top-2 -right-2 h-8 w-8 bg-red-500 text-white rounded-full hover:bg-red-600 flex items-center justify-center shadow-lg"
              >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
          <p class="mt-2 text-sm text-gray-500">Maximum file size: 2MB. Accepted formats: JPG, PNG, GIF</p>
          <p v-if="getError('image')" class="mt-2 text-sm text-red-600">{{ getError('image') }}</p>
        </div>

        <!-- Submit Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200">
          <button
            type="submit"
            :disabled="loading"
            class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="loading" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span v-if="loading">Creating...</span>
            <span v-else>Create Product</span>
          </button>
          <NuxtLink
            to="/seller/products"
            class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors"
          >
            Cancel
          </NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>
