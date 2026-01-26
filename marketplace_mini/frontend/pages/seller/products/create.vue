<script setup lang="ts">
definePageMeta({
  middleware: ['auth', 'seller'],
  //layout: 'seller'
})

const config = useRuntimeConfig()
const authStore = useAuthStore()
const router = useRouter()

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

// Variant state
const templates = ref<VariantTemplate[]>([])
const optionGroups = ref<OptionGroup[]>([])
const variantMatrix = ref<any[]>([])

// Bulk edit inputs
const bulkPrice = ref('')
const bulkStock = ref('')

// Show transition alert
const showVariantModeAlert = ref(false)

// Categories
const categories = ref<any[]>([])
const loading = ref(false)

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

// Add option group
const addOptionGroup = () => {
  // Show alert when transitioning to variant mode
  if (optionGroups.value.length === 0) {
    showVariantModeAlert.value = true
    setTimeout(() => {
      showVariantModeAlert.value = false
    }, 5000)
  }

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

// Generate variant matrix (Cartesian product) - ONLY from SELECTED values
const generateVariantMatrix = () => {
  // Filter groups that have SELECTED values (not just available)
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
    // CRITICAL: Use selectedValues, not availableValues
    for (const value of group.selectedValues) {
      current[group.attributeName] = value
      generate(index + 1, current)
    }
  }

  generate(0, {})

  // Preserve existing data where possible
  const existingMap = new Map(
    variantMatrix.value.map(v => [JSON.stringify(v.attributes), v])
  )

  variantMatrix.value = combinations.map(attributes => {
    const existing = existingMap.get(JSON.stringify(attributes))
    return existing || {
      sku: generateSku(attributes),
      price: '',
      stock_quantity: '',
      attributes,
      image: null,
      imagePreview: null
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

// Submit form
const submitProduct = async () => {
  // --- 1. Xác định chế độ dựa trên dữ liệu thực tế (Smart Form Logic) ---
  // Nếu có nhóm tùy chọn nào được định nghĩa -> Là hàng biến thể
  const hasVariants = optionGroups.value.length > 0;

  // --- 2. Validation ---
  if (!productForm.value.name.trim()) return alert('Please enter product name');
  if (!productForm.value.category_id) return alert('Please select a category');
  if (!productForm.value.image) return alert('Please upload a product image');

  // Validate theo chế độ
  if (!hasVariants) {
    // Chế độ Simple
    if (!productForm.value.price) return alert('Please enter product price');
    if (!productForm.value.stock_quantity) return alert('Please enter stock quantity');
  } else {
    // Chế độ Variant
    // Lọc ra các nhóm có chọn giá trị (ví dụ chọn Size S, M)
    const validGroups = optionGroups.value.filter(g =>
      g.attributeName && g.selectedValues.length > 0
    );

    if (validGroups.length === 0) return alert('Please select at least one variant option');
    
    if (variantMatrix.value.length === 0) return alert('No variant combinations generated');

    // Kiểm tra từng dòng trong bảng biến thể
    for (const variant of variantMatrix.value) {
      if (!variant.price || !variant.stock_quantity) {
        return alert('All variants must have Price and Stock quantity');
      }
      // SKU có thể để backend tự sinh nếu rỗng
      // if (!variant.sku) return alert('Missing SKU');
    }
  }

  loading.value = true;

  try {
    const formData = new FormData();

    // --- 3. Append dữ liệu cơ bản ---
    formData.append('name', productForm.value.name);
    formData.append('category_id', productForm.value.category_id);
    if (productForm.value.description) {
      formData.append('description', productForm.value.description);
    }
    // Chỉ append nếu là file thực sự (đề phòng trường hợp edit sau này)
    if (productForm.value.image instanceof File) {
        formData.append('image', productForm.value.image);
    }

    if (!hasVariants) {
      // --- CASE 1: Sản phẩm đơn giản ---
      formData.append('price', productForm.value.price);
      formData.append('stock_quantity', productForm.value.stock_quantity);
    } else {
      // --- CASE 2: Sản phẩm biến thể ---
      
      // Gửi cấu trúc Option (để lưu vào cột 'options' JSON)
      const validGroups = optionGroups.value.filter(g =>
        g.attributeName && g.selectedValues.length > 0
      );
      
      const optionsPayload = validGroups.map(g => ({
        name: g.attributeName,
        values: g.selectedValues // Backend sẽ lưu cái này để biết sản phẩm có Size S, M
      }));
      formData.append('options', JSON.stringify(optionsPayload));

      // Gửi danh sách biến thể (Variant Matrix)
      variantMatrix.value.forEach((variant, index) => {
        // Lưu ý: Laravel validate mảng cần index rõ ràng: variants[0][sku]
        formData.append(`variants[${index}][sku]`, variant.sku || ''); // Gửi rỗng nếu không có
        formData.append(`variants[${index}][price]`, variant.price);
        formData.append(`variants[${index}][stock_quantity]`, variant.stock_quantity);
        
        // Attributes: Gửi dưới dạng JSON string (VD: '{"Color":"Red", "Size":"M"}')
        formData.append(`variants[${index}][attributes]`, JSON.stringify(variant.attributes));

        // Ảnh biến thể (Nếu có)
        if (variant.image instanceof File) {
          formData.append(`variants[${index}][image]`, variant.image);
        }
      });
    }

    // --- 4. Gửi API ) ---
    await $fetch(`${config.public.apiBase}/seller/products`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        Accept: 'application/json', 
      },
      body: formData,
    });

    alert('Product created successfully!');
    router.push('/seller/products');
  } catch (error: any) {
    console.error('Failed to create product:', error);
    // Hiển thị lỗi chi tiết từ Laravel trả về nếu có
    const msg = error.data?.message || error.message || 'Failed to create product';
    alert(msg);
  } finally {
    loading.value = false;
  }
};
// const submitProduct = async () => {
//   // Validation
//   if (!productForm.value.name.trim()) {
//     alert('Please enter product name')
//     return
//   }
//   if (!productForm.value.category_id) {
//     alert('Please select a category')
//     return
//   }
//   if (!productForm.value.image) {
//     alert('Please upload a product image')
//     return
//   }

//   // Simple product validation
//   if (!isVariantMode.value) {
//     if (!productForm.value.price) {
//       alert('Please enter product price')
//       return
//     }
//     if (!productForm.value.stock_quantity) {
//       alert('Please enter stock quantity')
//       return
//     }
//   } else {
//     // Variant product validation
//     const validGroups = optionGroups.value.filter(g =>
//       g.attributeName && g.selectedValues.length > 0
//     )

//     if (validGroups.length === 0) {
//       alert('Please select at least one variant option')
//       return
//     }
//     if (variantMatrix.value.length === 0) {
//       alert('No variant combinations generated')
//       return
//     }
//     for (const variant of variantMatrix.value) {
//       if (!variant.sku || !variant.price || !variant.stock_quantity) {
//         alert('All variants must have SKU, price, and stock quantity')
//         return
//       }
//     }
//   }

//   loading.value = true

//   try {
//     const formData = new FormData()

//     // Basic product fields
//     formData.append('name', productForm.value.name)
//     formData.append('category_id', productForm.value.category_id)
//     if (productForm.value.description) {
//       formData.append('description', productForm.value.description)
//     }
//     formData.append('image', productForm.value.image!)

//     if (!isVariantMode.value) {
//       // Simple product
//       formData.append('price', productForm.value.price)
//       formData.append('stock_quantity', productForm.value.stock_quantity)
//     } else {
//       // Variant product
//       const validGroups = optionGroups.value.filter(g =>
//         g.attributeName && g.selectedValues.length > 0
//       )

//       const options = validGroups.map(g => ({
//         name: g.attributeName,
//         values: g.selectedValues
//       }))

//       formData.append('options', JSON.stringify(options))

//       // Add variants
//       variantMatrix.value.forEach((variant, index) => {
//         formData.append(`variants[${index}][sku]`, variant.sku)
//         formData.append(`variants[${index}][price]`, variant.price)
//         formData.append(`variants[${index}][stock_quantity]`, variant.stock_quantity)
//         formData.append(`variants[${index}][attributes]`, JSON.stringify(variant.attributes))

//         // Only append image if provided
//         if (variant.image) {
//           formData.append(`variants[${index}][image]`, variant.image)
//         }
//       })
//     }

//     await $fetch(`${config.public.apiBase}/seller/products`, {
//       method: 'POST',
//       headers: {
//         Authorization: `Bearer ${authStore.token}`
//       },
//       body: formData
//     })

//     alert('Product created successfully!')
//     router.push('/seller/products')
//   } catch (error: any) {
//     console.error('Failed to create product:', error)
//     alert(error.data?.message || 'Failed to create product')
//   } finally {
//     loading.value = false
//   }
// }

// Load data on mount
onMounted(() => {
  fetchCategories()
  fetchTemplates()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 p-6">
    <div class="max-w-5xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <NuxtLink
          to="/seller/products"
          class="inline-flex items-center gap-2 text-emerald-600 hover:text-emerald-700 mb-4"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Back to Products
        </NuxtLink>
        <h1 class="text-3xl font-bold text-gray-900">Create New Product</h1>
        <p class="text-gray-600 mt-1">Add a new product to your store</p>
      </div>

      <!-- Variant Mode Alert -->
      <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="transform opacity-0 -translate-y-2"
        enter-to-class="transform opacity-100 translate-y-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="transform opacity-100 translate-y-0"
        leave-to-class="transform opacity-0 -translate-y-2"
      >
        <div v-if="showVariantModeAlert" class="mb-6 bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-blue-700 font-medium">
                Switched to Variant Mode
              </p>
              <p class="text-sm text-blue-600 mt-1">
                Set prices and stock quantities in the variant table below. The standard price/stock fields have been hidden.
              </p>
            </div>
          </div>
        </div>
      </Transition>

      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <!-- Basic Information -->
        <div class="space-y-6 mb-8">
          <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>

          <!-- Product Name -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Product Name *</label>
            <input
              v-model="productForm.name"
              type="text"
              placeholder="e.g., Premium Cotton T-Shirt"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            />
          </div>

          <!-- Category -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Category *</label>
            <select
              v-model="productForm.category_id"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            >
              <option value="">Select a category</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
            <textarea
              v-model="productForm.description"
              rows="4"
              placeholder="Describe your product..."
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            />
          </div>

          <!-- Main Product Image -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Product Image *</label>
            <div class="flex gap-4">
              <div v-if="productForm.imagePreview" class="w-32 h-32 rounded-lg overflow-hidden border border-slate-200">
                <img :src="productForm.imagePreview" alt="Preview" class="w-full h-full object-cover" />
              </div>
              <div class="flex-1">
                <input
                  type="file"
                  accept="image/*"
                  @change="handleImageUpload"
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                />
                <p class="text-xs text-gray-500 mt-1">Max size: 2MB. Recommended: 800x800px</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Simple Product Fields (Only visible when NO variants) -->
        <Transition
          enter-active-class="transition duration-300 ease-out"
          enter-from-class="transform opacity-0 scale-95"
          enter-to-class="transform opacity-100 scale-100"
          leave-active-class="transition duration-200 ease-in"
          leave-from-class="transform opacity-100 scale-100"
          leave-to-class="transform opacity-0 scale-95"
        >
          <div v-if="!isVariantMode" class="space-y-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900">Pricing & Inventory</h3>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Price *</label>
                <input
                  v-model="productForm.price"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                />
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Stock Quantity *</label>
                <input
                  v-model="productForm.stock_quantity"
                  type="number"
                  min="0"
                  placeholder="0"
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                />
              </div>
            </div>
          </div>
        </Transition>

        <!-- Variant Options Section (Always visible) -->
        <div class="space-y-6 mb-8">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-semibold text-gray-900">Variant Options</h3>
              <p class="text-sm text-gray-600 mt-1">
                {{ isVariantMode ? 'Managing product variants' : 'Add options to create variants (e.g., Size, Color)' }}
              </p>
            </div>
            <div class="flex gap-2">
              <NuxtLink
                to="/seller/attributes"
                target="_blank"
                class="text-sm text-emerald-600 hover:text-emerald-700 font-medium"
              >
                Manage Templates
              </NuxtLink>
              <button
                type="button"
                @click="addOptionGroup"
                class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium"
              >
                + Add Option
              </button>
            </div>
          </div>

          <!-- Option Groups -->
          <div v-if="optionGroups.length > 0" class="space-y-4">
            <div
              v-for="(group, groupIndex) in optionGroups"
              :key="groupIndex"
              class="border border-slate-200 rounded-lg p-4 bg-slate-50"
            >
              <!-- Template Selector -->
              <div class="flex items-start gap-3 mb-4">
                <div class="flex-1">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Attribute Name *</label>
                  <input
                    v-model="group.attributeName"
                    type="text"
                    placeholder="e.g., Size, Color"
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                  />
                </div>
                <div class="flex-1">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Load from Template</label>
                  <select
                    v-model="group.templateId"
                    @change="onTemplateSelected(groupIndex)"
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                  >
                    <option :value="null">None (manual)</option>
                    <option v-for="template in templates" :key="template.id" :value="template.id">
                      {{ template.name }}
                    </option>
                  </select>
                </div>
                <button
                  type="button"
                  @click="removeOptionGroup(groupIndex)"
                  class="mt-7 p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                  title="Remove option group"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>

              <!-- Available Values (Multi-Select) -->
              <div v-if="group.availableValues.length > 0" class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Available Values - Click to Select ({{ group.selectedValues.length }} selected)
                </label>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="value in group.availableValues"
                    :key="value"
                    type="button"
                    @click="toggleValue(groupIndex, value)"
                    :class="[
                      'px-3 py-1.5 rounded-lg border-2 transition-all font-medium text-sm',
                      group.selectedValues.includes(value)
                        ? 'border-emerald-600 bg-emerald-600 text-white'
                        : 'border-slate-300 bg-white text-gray-700 hover:border-emerald-600'
                    ]"
                  >
                    {{ value }}
                  </button>
                </div>
              </div>

              <!-- Custom Value Input -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Add Custom Value</label>
                <div class="flex gap-2">
                  <input
                    v-model="group.customInput"
                    type="text"
                    placeholder="Type a custom value (e.g., XS)"
                    @keydown="handleCustomValueKeydown($event, groupIndex)"
                    class="flex-1 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm"
                  />
                  <button
                    type="button"
                    @click="addCustomValue(groupIndex)"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium"
                  >
                    Add
                  </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">Press Enter to add quickly</p>
              </div>

              <!-- Selected Values Display -->
              <div v-if="group.selectedValues.length > 0" class="mt-3 pt-3 border-t border-slate-200">
                <div class="text-sm font-medium text-gray-700 mb-2">
                  Selected for this product:
                </div>
                <div class="flex flex-wrap gap-1.5">
                  <span
                    v-for="value in group.selectedValues"
                    :key="value"
                    class="inline-block px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-xs font-medium"
                  >
                    {{ value }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State (No options added yet) -->
          <div v-else class="bg-slate-100 border-2 border-dashed border-slate-300 rounded-lg p-8 text-center">
            <svg class="w-12 h-12 text-slate-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            <p class="text-gray-600 mb-3">No variant options defined</p>
            <p class="text-sm text-gray-500">
              This will be a simple product with one price and stock quantity.
            </p>
            <button
              type="button"
              @click="addOptionGroup"
              class="mt-4 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium"
            >
              Add Variant Options
            </button>
          </div>
        </div>

        <!-- Variant Matrix (Only visible when variants exist) -->
        <Transition
          enter-active-class="transition duration-300 ease-out"
          enter-from-class="transform opacity-0 scale-95"
          enter-to-class="transform opacity-100 scale-100"
          leave-active-class="transition duration-200 ease-in"
          leave-from-class="transform opacity-100 scale-100"
          leave-to-class="transform opacity-0 scale-95"
        >
          <div v-if="isVariantMode && variantMatrix.length > 0" class="space-y-4 mb-8">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-900">
                Variant Matrix ({{ variantMatrix.length }} combinations)
              </h3>
            </div>

            <!-- Bulk Edit Toolbar -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="text-sm font-medium text-blue-900 mb-3">Bulk Edit All Variants</div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex gap-2">
                  <input
                    v-model="bulkPrice"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="Bulk Price"
                    class="flex-1 px-3 py-2 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
                  />
                  <button
                    type="button"
                    @click="applyBulkPrice"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm whitespace-nowrap"
                  >
                    Apply to All
                  </button>
                </div>
                <div class="flex gap-2">
                  <input
                    v-model="bulkStock"
                    type="number"
                    min="0"
                    placeholder="Bulk Stock"
                    class="flex-1 px-3 py-2 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
                  />
                  <button
                    type="button"
                    @click="applyBulkStock"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm whitespace-nowrap"
                  >
                    Apply to All
                  </button>
                </div>
              </div>
            </div>

            <!-- Variant Table -->
            <div class="overflow-x-auto">
              <table class="w-full border border-slate-200 rounded-lg overflow-hidden">
                <thead class="bg-slate-100">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Variant</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">SKU *</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Price *</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Stock *</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Image (Optional)</th>
                  </tr>
                </thead>
                <tbody class="bg-white">
                  <tr
                    v-for="(variant, index) in variantMatrix"
                    :key="index"
                    class="border-t border-slate-200 hover:bg-slate-50"
                  >
                    <td class="px-4 py-3">
                      <div class="text-sm font-medium text-gray-900">
                        {{ Object.entries(variant.attributes).map(([k, v]) => `${k}: ${v}`).join(', ') }}
                      </div>
                    </td>
                    <td class="px-4 py-3">
                      <input
                        v-model="variant.sku"
                        type="text"
                        class="w-full px-2 py-1 border border-slate-300 rounded text-sm focus:ring-2 focus:ring-emerald-500"
                      />
                    </td>
                    <td class="px-4 py-3">
                      <input
                        v-model="variant.price"
                        type="number"
                        step="0.01"
                        min="0"
                        class="w-24 px-2 py-1 border border-slate-300 rounded text-sm focus:ring-2 focus:ring-emerald-500"
                      />
                    </td>
                    <td class="px-4 py-3">
                      <input
                        v-model="variant.stock_quantity"
                        type="number"
                        min="0"
                        class="w-20 px-2 py-1 border border-slate-300 rounded text-sm focus:ring-2 focus:ring-emerald-500"
                      />
                    </td>
                    <td class="px-4 py-3">
                      <div class="flex items-center gap-2">
                        <div v-if="variant.imagePreview" class="w-12 h-12 rounded overflow-hidden border border-slate-200">
                          <img :src="variant.imagePreview" alt="Variant" class="w-full h-full object-cover" />
                        </div>
                        <div v-else class="w-12 h-12 rounded overflow-hidden border border-slate-200 bg-slate-100 flex items-center justify-center">
                          <span class="text-xs text-slate-400">Default</span>
                        </div>
                        <input
                          type="file"
                          accept="image/*"
                          @change="handleVariantImageUpload($event, index)"
                          class="text-xs w-32"
                        />
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </Transition>

        <!-- No variants warning (when options exist but no selections made) -->
        <div v-if="isVariantMode && variantMatrix.length === 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-8">
          <p class="text-sm text-yellow-800">
            Select values from the options above to generate variant combinations
          </p>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end gap-3 pt-6 border-t border-slate-200">
          <NuxtLink
            to="/seller/products"
            class="px-6 py-2 border border-slate-300 text-gray-700 rounded-lg hover:bg-slate-100 transition-colors font-medium"
          >
            Cancel
          </NuxtLink>
          <button
            type="button"
            @click="submitProduct"
            :disabled="loading"
            class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ loading ? 'Creating...' : 'Create Product' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
