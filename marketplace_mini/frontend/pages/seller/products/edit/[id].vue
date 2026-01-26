<script setup lang="ts">
definePageMeta({
  middleware: ['auth', 'seller'],
  //layout: 'seller'
})

const config = useRuntimeConfig()
const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

// Get product ID from route
const productId = Number(route.params.id)

interface VariantOption {
  name: string
  values: string[]
}

interface VariantTemplate {
  id: number
  name: string
  options: VariantOption[]
}

interface OptionGroup {
  attributeName: string
  templateId: number | null
  availableValues: string[]
  selectedValues: string[]
  customInput: string
}

// Form state
const productForm = ref({
  name: '',
  category_id: '',
  description: '',
  price: '',
  stock_quantity: '',
  image: null as File | null,
  imagePreview: null as string | null
})

const currentImageUrl = ref<string | null>(null)

// Variant state
const templates = ref<VariantTemplate[]>([])
const optionGroups = ref<OptionGroup[]>([])
const variantMatrix = ref<any[]>([])

// Bulk edit inputs
const bulkPrice = ref('')
const bulkStock = ref('')

// Categories
const categories = ref<any[]>([])
const loading = ref(false)
const loadingProduct = ref(true)
const errors = ref<Record<string, string[]>>({})

// Computed: Is variant mode active
const isVariantMode = computed(() => optionGroups.value.length > 0)

// Fetch categories
const fetchCategories = async () => {
  try {
    const response: any = await $fetch(`${config.public.apiBase}/categories`)
    categories.value = response.data || []
  } catch (error) {
    console.error('Failed to fetch categories:', error)
  }
}

// Fetch templates
const fetchTemplates = async () => {
  try {
    const response: any = await $fetch(`${config.public.apiBase}/seller/variant-templates`, {
      headers: {
        Authorization: `Bearer ${authStore.token}`
      }
    })
    templates.value = response.templates || []
  } catch (error) {
    console.error('Failed to fetch templates:', error)
  }
}

// Fetch product data and hydrate form
const fetchProduct = async () => {
  loadingProduct.value = true
  try {
    const response: any = await $fetch(`${config.public.apiBase}/seller/products/${productId}`, {
      headers: {
        Authorization: `Bearer ${authStore.token}`
      }
    })
    const product = response.product

    // Pre-fill basic form fields
    productForm.value.name = product.name
    productForm.value.category_id = product.category_id.toString()
    productForm.value.description = product.description || ''

    // Set current image
    currentImageUrl.value = product.image_url

    // === HYDRATION LOGIC ===
    // Case A: Simple Product (has_variants: false)
    if (!product.has_variants) {
      productForm.value.price = product.price.toString()
      productForm.value.stock_quantity = product.stock_quantity.toString()
      optionGroups.value = []
      variantMatrix.value = []
    }
    // Case B: Variant Product (has_variants: true)
    else {
      // Step 1: Clear simple mode fields (they won't be shown)
      productForm.value.price = ''
      productForm.value.stock_quantity = ''

      // Step 2: Load Options into optionGroups
      // product.options is a JSON array like: [{name: "Size", values: ["S", "M"]}, {name: "Color", values: ["Red", "Blue"]}]
      if (product.options && Array.isArray(product.options)) {
        optionGroups.value = product.options.map((option: any) => ({
          attributeName: option.name,
          templateId: null, 
          availableValues: [...option.values],
          selectedValues: [...option.values], // All values are selected (they're already in use)
          customInput: ''
        }))
      }

      // Step 3: Load Variants into variantMatrix
      // product.variants is an array of variant objects
      if (product.variants && Array.isArray(product.variants)) {
        variantMatrix.value = product.variants.map((variant: any) => ({
          id: variant.id, // Keep ID for updates
          sku: variant.sku,
          price: variant.price.toString(),
          stock_quantity: variant.stock_quantity.toString(),
          attributes: variant.attributes, // Object like {Size: "M", Color: "Red"}
          image: null, 
          imagePreview: variant.image_url, // Show existing image as preview
          existingImageUrl: variant.image_url // Keep reference to existing image
        }))
      }
    }
  } catch (err: any) {
    alert(err.data?.message || 'Failed to fetch product')
    router.push('/seller/products')
  } finally {
    loadingProduct.value = false
  }
}

// Add option group
const addOptionGroup = () => {
  optionGroups.value.push({
    attributeName: '',
    templateId: null,
    availableValues: [],
    selectedValues: [],
    customInput: ''
  })
}

// Remove option group
const removeOptionGroup = (index: number) => {
  optionGroups.value.splice(index, 1)
  generateVariantMatrix()
}

// Handle template selection
const onTemplateSelected = (groupIndex: number) => {
  const group = optionGroups.value[groupIndex]
  const template = templates.value.find(t => t.id === group.templateId)

  if (template && template.options.length > 0) {
    // Remove the current group first
    optionGroups.value.splice(groupIndex, 1)

    // Add option group for EACH option in the template
    template.options.forEach((option, index) => {
      optionGroups.value.splice(groupIndex + index, 0, {
        attributeName: option.name,
        templateId: group.templateId,
        availableValues: [...option.values],
        selectedValues: [],
        customInput: ''
      })
    })

    generateVariantMatrix()
  } else {
    group.attributeName = ''
    group.availableValues = []
    group.selectedValues = []
  }
}

// Toggle value selection
const toggleValue = (groupIndex: number, value: string) => {
  const group = optionGroups.value[groupIndex]
  const index = group.selectedValues.indexOf(value)

  if (index > -1) {
    group.selectedValues.splice(index, 1)
  } else {
    group.selectedValues.push(value)
  }

  generateVariantMatrix()
}

// Add custom value
const addCustomValue = (groupIndex: number) => {
  const group = optionGroups.value[groupIndex]
  const value = group.customInput.trim()

  if (!value) return

  // Add to available values if not exists
  if (!group.availableValues.includes(value)) {
    group.availableValues.push(value)
  }

  // Add to selected values if not already selected
  if (!group.selectedValues.includes(value)) {
    group.selectedValues.push(value)
  }

  group.customInput = ''
  generateVariantMatrix()
}

// Handle Enter key for custom values
const handleCustomValueKeydown = (event: KeyboardEvent, groupIndex: number) => {
  if (event.key === 'Enter') {
    event.preventDefault()
    addCustomValue(groupIndex)
  }
}

// Generate SKU helper
const generateSku = (attributes: Record<string, string>) => {
  const productPrefix = productForm.value.name
    .split(' ')
    .map(word => word.charAt(0).toUpperCase())
    .join('')
    .slice(0, 3) || 'PRD'

  const variantSuffix = Object.values(attributes)
    .map(v => v.slice(0, 2).toUpperCase())
    .join('-')

  return `${productPrefix}-${variantSuffix}-${Date.now().toString().slice(-4)}`
}

// Generate variant matrix (Cartesian product)
const generateVariantMatrix = () => {
  const validGroups = optionGroups.value.filter(g =>
    g.attributeName && g.selectedValues.length > 0
  )

  if (validGroups.length === 0) {
    variantMatrix.value = []
    return
  }

  const combinations: Array<Record<string, string>> = []

  const generate = (index: number, current: Record<string, string>) => {
    if (index === validGroups.length) {
      combinations.push({ ...current })
      return
    }

    const group = validGroups[index]
    for (const value of group.selectedValues) {
      current[group.attributeName] = value
      generate(index + 1, current)
    }
  }

  generate(0, {})

  // Preserve existing data where possible (including variant IDs)
  const existingMap = new Map(
    variantMatrix.value.map(v => [JSON.stringify(v.attributes), v])
  )

  variantMatrix.value = combinations.map(attributes => {
    const existing = existingMap.get(JSON.stringify(attributes))
    return existing || {
      id: null, // New variant (no ID)
      sku: generateSku(attributes),
      price: '',
      stock_quantity: '',
      attributes,
      image: null,
      imagePreview: null,
      existingImageUrl: null
    }
  })
}

// Apply bulk price to all variants
const applyBulkPrice = () => {
  if (!bulkPrice.value) return
  variantMatrix.value.forEach(variant => {
    variant.price = bulkPrice.value
  })
}

// Apply bulk stock to all variants
const applyBulkStock = () => {
  if (!bulkStock.value) return
  variantMatrix.value.forEach(variant => {
    variant.stock_quantity = bulkStock.value
  })
}

// Handle main image upload
const handleImageUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    productForm.value.image = target.files[0]
    productForm.value.imagePreview = URL.createObjectURL(target.files[0])
  }
}

// Handle variant image upload
const handleVariantImageUpload = (event: Event, variantIndex: number) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    const variant = variantMatrix.value[variantIndex]
    variant.image = target.files[0]
    variant.imagePreview = URL.createObjectURL(target.files[0])
  }
}

// Clear new main image
const clearNewImage = () => {
  productForm.value.image = null
  productForm.value.imagePreview = null
}

// Submit form with PUT method spoofing
const updateProduct = async () => {
  const hasVariants = optionGroups.value.length > 0

  // Validation
  if (!productForm.value.name.trim()) return alert('Please enter product name')
  if (!productForm.value.category_id) return alert('Please select a category')

  if (!hasVariants) {
    if (!productForm.value.price) return alert('Please enter product price')
    if (!productForm.value.stock_quantity) return alert('Please enter stock quantity')
  } else {
    const validGroups = optionGroups.value.filter(g =>
      g.attributeName && g.selectedValues.length > 0
    )

    if (validGroups.length === 0) return alert('Please select at least one variant option')
    if (variantMatrix.value.length === 0) return alert('No variant combinations generated')

    for (const variant of variantMatrix.value) {
      if (!variant.price || !variant.stock_quantity) {
        return alert('All variants must have Price and Stock quantity')
      }
    }
  }

  loading.value = true

  try {
    const formData = new FormData()

    // === METHOD SPOOFING FOR PUT ===
    formData.append('_method', 'PUT')

    // Basic fields
    formData.append('name', productForm.value.name)
    formData.append('category_id', productForm.value.category_id)
    if (productForm.value.description) {
      formData.append('description', productForm.value.description)
    }

    // Only append image if a NEW file was selected
    if (productForm.value.image instanceof File) {
      formData.append('image', productForm.value.image)
    }

    if (!hasVariants) {
      // Simple product
      formData.append('price', productForm.value.price)
      formData.append('stock_quantity', productForm.value.stock_quantity)
    } else {
      // Variant product
      const validGroups = optionGroups.value.filter(g =>
        g.attributeName && g.selectedValues.length > 0
      )

      const optionsPayload = validGroups.map(g => ({
        name: g.attributeName,
        values: g.selectedValues
      }))
      formData.append('options', JSON.stringify(optionsPayload))

      // Variants
      variantMatrix.value.forEach((variant, index) => {
        // Include ID if it exists (for updating existing variants)
        if (variant.id) {
          formData.append(`variants[${index}][id]`, variant.id.toString())
        }
        formData.append(`variants[${index}][sku]`, variant.sku || '')
        formData.append(`variants[${index}][price]`, variant.price)
        formData.append(`variants[${index}][stock_quantity]`, variant.stock_quantity)
        formData.append(`variants[${index}][attributes]`, JSON.stringify(variant.attributes))

        // Only append image if a NEW file was selected
        if (variant.image instanceof File) {
          formData.append(`variants[${index}][image]`, variant.image)
        }
      })
    }

    // Use POST with _method spoofing instead of PUT
    await $fetch(`${config.public.apiBase}/seller/products/${productId}`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        Accept: 'application/json'
      },
      body: formData
    })

    alert('Product updated successfully!')
    router.push('/seller/products')
  } catch (error: any) {
    console.error('Failed to update product:', error)
    const msg = error.data?.message || error.message || 'Failed to update product'
    alert(msg)
  } finally {
    loading.value = false
  }
}

// Load data on mount
onMounted(() => {
  fetchCategories()
  fetchTemplates()
  fetchProduct()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <div class="max-w-5xl mx-auto pt-24 px-4 sm:px-6 lg:px-8 pb-16">
      <!-- Back Button -->
      <div class="mb-6">
        <NuxtLink to="/seller/products" class="inline-flex items-center gap-2 text-gray-500 hover:text-emerald-600 transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m12 19-7-7 7-7"/>
            <path d="M19 12H5"/>
          </svg>
          <span class="font-medium">Back to Products</span>
        </NuxtLink>
      </div>

      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Edit Product</h1>
        <p class="mt-2 text-gray-600">Update your product information and variants</p>
      </div>

      <!-- Loading State -->
      <div v-if="loadingProduct" class="bg-white rounded-xl shadow-sm p-12 border border-gray-100 text-center">
        <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-emerald-600 border-r-transparent" />
        <p class="mt-4 text-gray-600">Loading product...</p>
      </div>

      <!-- Form -->
      <form v-else @submit.prevent="updateProduct" class="space-y-6">
        <!-- Main Product Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
          <h2 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-3">Basic Information</h2>

          <!-- Product Name -->
          <div>
            <label for="name" class="block text-sm font-medium text-gray-900 mb-2">
              Product Name <span class="text-red-600">*</span>
            </label>
            <input
              id="name"
              v-model="productForm.name"
              type="text"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
              placeholder="Enter product name"
            >
          </div>

          <!-- Category -->
          <div>
            <label for="category" class="block text-sm font-medium text-gray-900 mb-2">
              Category <span class="text-red-600">*</span>
            </label>
            <select
              id="category"
              v-model="productForm.category_id"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            >
              <option value="">Select a category</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
          </div>

          <!-- Description -->
          <div>
            <label for="description" class="block text-sm font-medium text-gray-900 mb-2">
              Description
            </label>
            <textarea
              id="description"
              v-model="productForm.description"
              rows="4"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
              placeholder="Describe your product..."
            />
          </div>

          <!-- Main Product Image -->
          <div>
            <label class="block text-sm font-medium text-gray-900 mb-2">Product Image</label>

            <!-- Current Image (if no new image selected) -->
            <div v-if="currentImageUrl && !productForm.imagePreview" class="mb-4">
              <p class="text-sm text-gray-600 mb-2">Current image:</p>
              <img :src="currentImageUrl" alt="Current product" class="h-48 w-48 object-cover rounded-lg border-2 border-gray-200">
            </div>

            <!-- New Image Preview -->
            <div v-if="productForm.imagePreview" class="mb-4">
              <p class="text-sm text-gray-600 mb-2">New image:</p>
              <div class="relative inline-block">
                <img :src="productForm.imagePreview" alt="Preview" class="h-48 w-48 object-cover rounded-lg border-2 border-emerald-500">
                <button type="button" @click="clearNewImage" class="absolute -top-2 -right-2 h-8 w-8 bg-red-500 text-white rounded-full hover:bg-red-600 flex items-center justify-center shadow-lg">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>

            <input
              type="file"
              accept="image/*"
              @change="handleImageUpload"
              class="block w-full text-sm text-gray-600 file:mr-4 file:py-3 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 file:cursor-pointer cursor-pointer"
            >
            <p class="mt-2 text-sm text-gray-500">Leave empty to keep current image. Max: 2MB</p>
          </div>
        </div>

        <!-- Simple Mode: Price & Stock (Shown when no option groups) -->
        <Transition
          enter-active-class="transition ease-out duration-200"
          enter-from-class="opacity-0 -translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition ease-in duration-150"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-2"
        >
          <div v-if="!isVariantMode" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-3 mb-6">Pricing & Inventory</h2>
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
                    v-model="productForm.price"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                    placeholder="0.00"
                  >
                </div>
              </div>

              <!-- Stock -->
              <div>
                <label for="stock" class="block text-sm font-medium text-gray-900 mb-2">
                  Stock Quantity <span class="text-red-600">*</span>
                </label>
                <input
                  id="stock"
                  v-model="productForm.stock_quantity"
                  type="number"
                  min="0"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                  placeholder="0"
                >
              </div>
            </div>
          </div>
        </Transition>

        <!-- Variant Configuration -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
          <div class="flex items-center justify-between border-b border-gray-200 pb-3">
            <h2 class="text-lg font-semibold text-gray-900">Product Variants</h2>
            <button
              type="button"
              @click="addOptionGroup"
              class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Add Variant Option
            </button>
          </div>

          <!-- Empty State -->
          <div v-if="optionGroups.length === 0" class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">Simple Product Mode</h3>
            <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">
              This product has no variants. Click "Add Variant Option" to create variations like Size or Color.
            </p>
          </div>

          <!-- Option Groups -->
          <div v-for="(group, groupIndex) in optionGroups" :key="groupIndex" class="border border-gray-200 rounded-lg p-4 space-y-4">
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1 space-y-4">
                <!-- Template Selector & Attribute Name -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Load Template (Optional)</label>
                    <select
                      v-model="group.templateId"
                      @change="onTemplateSelected(groupIndex)"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                    >
                      <option :value="null">Select a template...</option>
                      <option v-for="template in templates" :key="template.id" :value="template.id">
                        {{ template.name }}
                      </option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Attribute Name</label>
                    <input
                      v-model="group.attributeName"
                      type="text"
                      placeholder="e.g., Size, Color"
                      class="w-95% px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                    >
                  </div>
                </div>

                <!-- Available Values Multi-Select -->
                <div v-if="group.availableValues.length > 0">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Select Values</label>
                  <div class="flex flex-wrap gap-2">
                    <button
                      v-for="value in group.availableValues"
                      :key="value"
                      type="button"
                      @click="toggleValue(groupIndex, value)"
                      :class="[
                        'px-3 py-1.5 text-sm font-medium rounded-lg border-2 transition-all',
                        group.selectedValues.includes(value)
                          ? 'bg-emerald-500 text-white border-emerald-600'
                          : 'bg-white text-gray-700 border-gray-300 hover:border-emerald-500'
                      ]"
                    >
                      {{ value }}
                    </button>
                  </div>
                </div>

                <!-- Custom Value Input -->
                <!-- <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Add Custom Value</label>
                  <div class="flex gap-2">
                    <input
                      v-model="group.customInput"
                      type="text"
                      placeholder="Type and press Enter"
                      @keydown="handleCustomValueKeydown($event, groupIndex)"
                      class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                    >
                    <button
                      type="button"
                      @click="addCustomValue(groupIndex)"
                      class="px-4 py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors"
                    >
                      Add
                    </button>
                  </div>
                </div> -->
              </div>

              <!-- Remove Button -->
              <button
                type="button"
                @click="removeOptionGroup(groupIndex)"
                class="flex-shrink-0 p-2 mt-6 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
              >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Variant Matrix -->
        <Transition
          enter-active-class="transition ease-out duration-200"
          enter-from-class="opacity-0 -translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition ease-in duration-150"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-2"
        >
          <div v-if="isVariantMode && variantMatrix.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
            <h2 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-3">Variant Pricing & Inventory</h2>

            <!-- Bulk Edit Toolbar -->
            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
              <p class="text-sm font-medium text-emerald-900 mb-3">Bulk Edit (Apply to All Variants)</p>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex gap-2">
                  <input
                    v-model="bulkPrice"
                    type="number"
                    step="0.01"
                    placeholder="Price"
                    class="flex-1 px-4 py-2 border border-emerald-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                  >
                  <button
                    type="button"
                    @click="applyBulkPrice"
                    class="px-4 py-2 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700"
                  >
                    Apply Price
                  </button>
                </div>
                <div class="flex gap-2">
                  <input
                    v-model="bulkStock"
                    type="number"
                    placeholder="Stock"
                    class="flex-1 px-4 py-2 border border-emerald-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                  >
                  <button
                    type="button"
                    @click="applyBulkStock"
                    class="px-4 py-2 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700"
                  >
                    Apply Stock
                  </button>
                </div>
              </div>
            </div>

            <!-- Variant Table -->
            <div class="overflow-x-auto">
              <table class="w-full border-collapse">
                <thead>
                  <tr class="bg-gray-50">
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 border-b-2 border-gray-200">Variant</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 border-b-2 border-gray-200">SKU</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 border-b-2 border-gray-200">Price</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 border-b-2 border-gray-200">Stock</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 border-b-2 border-gray-200">Image</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(variant, index) in variantMatrix" :key="index" class="border-b border-gray-200 hover:bg-gray-50">
                    <!-- Variant Name -->
                    <td class="px-4 py-3">
                      <div class="flex flex-col gap-1">
                        <span v-for="(value, key) in variant.attributes" :key="key" class="text-sm">
                          <span class="font-medium text-gray-700">{{ key }}:</span>
                          <span class="text-gray-900">{{ value }}</span>
                        </span>
                      </div>
                    </td>

                    <!-- SKU -->
                    <td class="px-4 py-3">
                      <input
                        v-model="variant.sku"
                        type="text"
                        class="w-32 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                        placeholder="SKU"
                      >
                    </td>

                    <!-- Price -->
                    <td class="px-4 py-3">
                      <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">$</span>
                        <input
                          v-model="variant.price"
                          type="number"
                          step="0.01"
                          min="0"
                          required
                          class="w-28 pl-6 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                          placeholder="0.00"
                        >
                      </div>
                    </td>

                    <!-- Stock -->
                    <td class="px-4 py-3">
                      <input
                        v-model="variant.stock_quantity"
                        type="number"
                        min="0"
                        required
                        class="w-24 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                        placeholder="0"
                      >
                    </td>

                    <!-- Image -->
                    <td class="px-4 py-3">
                      <div class="flex items-center gap-3">
                        <!-- Image Preview -->
                        <div v-if="variant.imagePreview || variant.existingImageUrl" class="relative">
                          <img
                            :src="variant.imagePreview || variant.existingImageUrl"
                            alt="Variant image"
                            class="h-16 w-16 object-cover rounded-lg border-2"
                            :class="variant.imagePreview ? 'border-emerald-500' : 'border-gray-300'"
                          >
                          <span v-if="!variant.imagePreview && variant.existingImageUrl" class="absolute -top-1 -right-1 px-1.5 py-0.5 bg-blue-500 text-white text-xs rounded-full">
                            Current
                          </span>
                        </div>

                        <!-- Upload Button -->
                        <label class="cursor-pointer px-3 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                          {{ variant.imagePreview ? 'Change' : 'Upload' }}
                          <input
                            type="file"
                            accept="image/*"
                            @change="handleVariantImageUpload($event, index)"
                            class="hidden"
                          >
                        </label>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </Transition>

        <!-- Submit Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 pt-6">
          <button
            type="submit"
            :disabled="loading"
            class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="loading" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ loading ? 'Updating...' : 'Update Product' }}</span>
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
