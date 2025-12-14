<script setup lang="ts">
definePageMeta({
  layout: 'admin',
  middleware: 'admin',
})

const config = useRuntimeConfig()
const authStore = useAuthStore()

interface Category {
  id: number
  name: string
  slug: string
  icon_class: string
  products_count?: number
  created_at: string
  updated_at: string
}

interface CategoryForm {
  name: string
  icon_class: string
}

// State
const categories = ref<Category[]>([])
const loading = ref(false)
const showModal = ref(false)
const editingCategory = ref<Category | null>(null)
const formData = ref<CategoryForm>({
  name: '',
  icon_class: '',
})
const formErrors = ref<Record<string, string[]>>({})
const deleteConfirm = ref<number | null>(null)

// Fetch categories
const fetchCategories = async () => {
  loading.value = true
  try {
    const response = await $fetch<{ categories: Category[] }>(
      `${config.public.apiBase}/admin/categories`,
      {
        headers: {
          Authorization: `Bearer ${authStore.token}`,
        },
      }
    )
    categories.value = response.categories
  } catch (error: any) {
    console.error('Failed to fetch categories:', error)
  } finally {
    loading.value = false
  }
}

// Open modal for create
const openCreateModal = () => {
  editingCategory.value = null
  formData.value = {
    name: '',
    icon_class: '',
  }
  formErrors.value = {}
  showModal.value = true
}

// Open modal for edit
const openEditModal = (category: Category) => {
  editingCategory.value = category
  formData.value = {
    name: category.name,
    icon_class: category.icon_class,
  }
  formErrors.value = {}
  showModal.value = true
}

// Close modal
const closeModal = () => {
  showModal.value = false
  editingCategory.value = null
  formData.value = {
    name: '',
    icon_class: '',
  }
  formErrors.value = {}
}

// Submit form
const submitForm = async () => {
  loading.value = true
  formErrors.value = {}

  try {
    if (editingCategory.value) {
      // Update existing category
      await $fetch(`${config.public.apiBase}/admin/categories/${editingCategory.value.id}`, {
        method: 'PUT',
        headers: {
          Authorization: `Bearer ${authStore.token}`,
        },
        body: formData.value,
      })
    } else {
      // Create new category
      await $fetch(`${config.public.apiBase}/admin/categories`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${authStore.token}`,
        },
        body: formData.value,
      })
    }

    closeModal()
    await fetchCategories()
  } catch (error: any) {
    if (error.data?.errors) {
      formErrors.value = error.data.errors
    } else {
      console.error('Failed to save category:', error)
    }
  } finally {
    loading.value = false
  }
}

// Delete category
const deleteCategory = async (id: number) => {
  if (deleteConfirm.value !== id) {
    deleteConfirm.value = id
    setTimeout(() => {
      deleteConfirm.value = null
    }, 3000)
    return
  }

  loading.value = true
  try {
    await $fetch(`${config.public.apiBase}/admin/categories/${id}`, {
      method: 'DELETE',
      headers: {
        Authorization: `Bearer ${authStore.token}`,
      },
    })
    await fetchCategories()
    deleteConfirm.value = null
  } catch (error: any) {
    console.error('Failed to delete category:', error)
    alert(error.data?.message || 'Failed to delete category')
  } finally {
    loading.value = false
  }
}

// Format date
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

// Load categories on mount
onMounted(() => {
  fetchCategories()
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Categories</h1>
        <p class="text-gray-600 mt-1">Manage product categories and their icons</p>
      </div>
      <button
        @click="openCreateModal"
        class="flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white px-6 py-3 rounded-xl font-medium transition-colors duration-200 shadow-lg shadow-violet-600/30"
      >
        <Icon icon="carbon:add" class="text-xl" />
        <span>New Category</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading && categories.length === 0" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-violet-600"></div>
    </div>

    <!-- Categories Table -->
    <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
      <table class="w-full">
        <thead class="bg-slate-50 border-b border-gray-200">
          <tr>
            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
              ID
            </th>
            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
              Icon
            </th>
            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
              Name
            </th>
            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
              Slug
            </th>
            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
              Products
            </th>
            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
              Created
            </th>
            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="category in categories" :key="category.id" class="hover:bg-slate-50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              #{{ category.id }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <Icon :icon="category.icon_class" class="text-3xl text-violet-600" />
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="font-medium text-gray-900">{{ category.name }}</div>
              <div class="text-xs text-gray-500 font-mono">{{ category.icon_class }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
              {{ category.slug }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-violet-100 text-violet-800">
                {{ category.products_count || 0 }} products
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
              {{ formatDate(category.created_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex items-center justify-end gap-2">
                <button
                  @click="openEditModal(category)"
                  class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors"
                >
                  <Icon icon="carbon:edit" class="text-base" />
                  <span>Edit</span>
                </button>
                <button
                  @click="deleteCategory(category.id)"
                  class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg transition-colors"
                  :class="deleteConfirm === category.id
                    ? 'bg-red-600 text-white hover:bg-red-700'
                    : 'bg-red-50 text-red-600 hover:bg-red-100'"
                >
                  <Icon icon="carbon:trash-can" class="text-base" />
                  <span>{{ deleteConfirm === category.id ? 'Confirm?' : 'Delete' }}</span>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Empty State -->
      <div v-if="categories.length === 0" class="text-center py-12">
        <Icon icon="carbon:category" class="text-6xl text-gray-300 mb-4" />
        <p class="text-gray-500 text-lg">No categories found</p>
        <button
          @click="openCreateModal"
          class="mt-4 inline-flex items-center gap-2 text-violet-600 hover:text-violet-700 font-medium"
        >
          <Icon icon="carbon:add" />
          <span>Create your first category</span>
        </button>
      </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
        @click.self="closeModal"
      >
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.stop>
          <!-- Modal Header -->
          <div class="bg-gradient-to-r from-violet-600 to-purple-600 px-6 py-4 text-white">
            <h3 class="text-xl font-bold">
              {{ editingCategory ? 'Edit Category' : 'Create New Category' }}
            </h3>
          </div>

          <!-- Modal Body -->
          <form @submit.prevent="submitForm" class="p-6 space-y-4">
            <!-- Category Name -->
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                Category Name
              </label>
              <input
                id="name"
                v-model="formData.name"
                type="text"
                required
                class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:border-violet-500 focus:ring-4 focus:ring-violet-500/10 outline-none transition-all"
                placeholder="e.g., Electronics"
              />
              <p v-if="formErrors.name" class="mt-1 text-sm text-red-600">
                {{ formErrors.name[0] }}
              </p>
            </div>

            <!-- Icon Class -->
            <div>
              <label for="icon_class" class="block text-sm font-medium text-gray-700 mb-2">
                Icon Class
              </label>
              <div class="relative">
                <input
                  id="icon_class"
                  v-model="formData.icon_class"
                  type="text"
                  required
                  class="w-full px-4 py-3 pr-12 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:border-violet-500 focus:ring-4 focus:ring-violet-500/10 outline-none transition-all font-mono text-sm"
                  placeholder="e.g., carbon:laptop"
                />
                <Icon
                  v-if="formData.icon_class"
                  :icon="formData.icon_class"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-2xl text-violet-600"
                />
              </div>
              <p v-if="formErrors.icon_class" class="mt-1 text-sm text-red-600">
                {{ formErrors.icon_class[0] }}
              </p>
              <p class="mt-1 text-xs text-gray-500">
                Browse icons at
                <a
                  href="https://icones.js.org/collection/carbon"
                  target="_blank"
                  class="text-violet-600 hover:underline"
                >
                  Carbon Icons
                </a>
              </p>
            </div>

            <!-- Modal Actions -->
            <div class="flex items-center gap-3 pt-4">
              <button
                type="button"
                @click="closeModal"
                class="flex-1 px-4 py-3 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="flex-1 px-4 py-3 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ loading ? 'Saving...' : (editingCategory ? 'Update' : 'Create') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
