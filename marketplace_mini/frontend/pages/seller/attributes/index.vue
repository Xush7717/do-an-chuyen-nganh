<script setup lang="ts">
definePageMeta({
  middleware: ['auth', 'seller'],
  //layout: 'seller'
})

const config = useRuntimeConfig()
const authStore = useAuthStore()

interface VariantOption {
  name: string
  values: string[]
}

interface VariantTemplate {
  id: number
  name: string
  options: VariantOption[]
  seller_id: number
  created_at: string
  updated_at: string
}

// State
const templates = ref<VariantTemplate[]>([])
const loading = ref(false)
const showModal = ref(false)
const editingTemplate = ref<VariantTemplate | null>(null)

// Form state
const templateForm = ref({
  name: '',
  options: [{ name: '', values: [] as string[] }]
})
const currentValueInput = ref<Record<number, string>>({})

// Fetch templates
const fetchTemplates = async () => {
  loading.value = true
  try {
    const response: any = await $fetch(`${config.public.apiBase}/seller/variant-templates`, {
      headers: {
        Authorization: `Bearer ${authStore.token}`
      }
    })
    templates.value = response.templates || []
  } catch (error) {
    console.error('Failed to fetch templates:', error)
  } finally {
    loading.value = false
  }
}

// Open create modal
const openCreateModal = () => {
  editingTemplate.value = null
  templateForm.value = {
    name: '',
    options: [{ name: '', values: [] }]
  }
  currentValueInput.value = {}
  showModal.value = true
}

// Open edit modal
const openEditModal = (template: VariantTemplate) => {
  editingTemplate.value = template
  templateForm.value = {
    name: template.name,
    options: JSON.parse(JSON.stringify(template.options))
  }
  currentValueInput.value = {}
  showModal.value = true
}

// Close modal
const closeModal = () => {
  showModal.value = false
  editingTemplate.value = null
}

// Add option to form
const addOption = () => {
  templateForm.value.options.push({ name: '', values: [] })
}

// Remove option from form
const removeOption = (index: number) => {
  templateForm.value.options.splice(index, 1)
}

// Add value to option
const addValue = (optionIndex: number) => {
  const value = currentValueInput.value[optionIndex]?.trim()
  if (!value) return

  const option = templateForm.value.options[optionIndex]
  if (!option.values.includes(value)) {
    option.values.push(value)
  }
  currentValueInput.value[optionIndex] = ''
}

// Remove value from option
const removeValue = (optionIndex: number, valueIndex: number) => {
  templateForm.value.options[optionIndex].values.splice(valueIndex, 1)
}

// Handle Enter key for adding values
const handleValueKeydown = (event: KeyboardEvent, optionIndex: number) => {
  if (event.key === 'Enter') {
    event.preventDefault()
    addValue(optionIndex)
  }
}

// Save template (create or update)
const saveTemplate = async () => {
  // Validation
  if (!templateForm.value.name.trim()) {
    alert('Please enter a template name')
    return
  }

  if (templateForm.value.options.length === 0) {
    alert('Please add at least one option')
    return
  }

  for (const option of templateForm.value.options) {
    if (!option.name.trim()) {
      alert('All options must have a name')
      return
    }
    if (option.values.length === 0) {
      alert(`Option "${option.name}" must have at least one value`)
      return
    }
  }

  loading.value = true
  try {
    if (editingTemplate.value) {
      // Update
      await $fetch(`${config.public.apiBase}/seller/variant-templates/${editingTemplate.value.id}`, {
        method: 'PUT',
        headers: {
          Authorization: `Bearer ${authStore.token}`
        },
        body: templateForm.value
      })
    } else {
      // Create
      await $fetch(`${config.public.apiBase}/seller/variant-templates`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${authStore.token}`
        },
        body: templateForm.value
      })
    }

    await fetchTemplates()
    closeModal()
  } catch (error: any) {
    console.error('Failed to save template:', error)
    alert(error.data?.message || 'Failed to save template')
  } finally {
    loading.value = false
  }
}

// Delete template
const deleteTemplate = async (id: number) => {
  if (!confirm('Are you sure you want to delete this template?')) return

  loading.value = true
  try {
    await $fetch(`${config.public.apiBase}/seller/variant-templates/${id}`, {
      method: 'DELETE',
      headers: {
        Authorization: `Bearer ${authStore.token}`
      }
    })
    await fetchTemplates()
  } catch (error: any) {
    console.error('Failed to delete template:', error)
    alert(error.data?.message || 'Failed to delete template')
  } finally {
    loading.value = false
  }
}

// Load templates on mount
onMounted(() => {
  fetchTemplates()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 p-6">
    <div class="max-w-6xl mx-auto">
      <!-- Header -->
      
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Attribute Manager</h1>
          <p class="text-gray-600 mt-1">Manage reusable variant templates for your products</p>
        </div>
        <button
          @click="openCreateModal"
          class="px-6 py-3 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700 transition-colors"
        >
          + Create Template
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loading && templates.length === 0" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600"></div>
      </div>

      <!-- Empty State -->
      <div v-else-if="templates.length === 0" class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">No Templates Yet</h3>
        <p class="text-gray-600 mb-6">Create your first variant template to streamline product creation</p>
        <button
          @click="openCreateModal"
          class="px-6 py-2 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700 transition-colors"
        >
          Create Your First Template
        </button>
      </div>

      <!-- Templates Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="template in templates"
          :key="template.id"
          class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-shadow"
        >
          <!-- Template Header -->
          <div class="flex items-start justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">{{ template.name }}</h3>
            <div class="flex gap-2">
              <button
                @click="openEditModal(template)"
                class="p-2 text-gray-400 hover:text-emerald-600 transition-colors"
                title="Edit"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button
                @click="deleteTemplate(template.id)"
                class="p-2 text-gray-400 hover:text-red-600 transition-colors"
                title="Delete"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Template Options -->
          <div class="space-y-3">
            <div
              v-for="(option, index) in template.options"
              :key="index"
              class="bg-slate-50 rounded-lg p-3"
            >
              <div class="text-sm font-medium text-gray-700 mb-2">{{ option.name }}</div>
              <div class="flex flex-wrap gap-1.5">
                <span
                  v-for="(value, vIndex) in option.values"
                  :key="vIndex"
                  class="inline-block px-2 py-1 bg-white border border-slate-200 rounded text-xs text-gray-600"
                >
                  {{ value }}
                </span>
              </div>
            </div>
          </div>

          <!-- Template Meta -->
          <div class="mt-4 pt-4 border-t border-slate-200 text-xs text-gray-500">
            {{ template.options.length }} option{{ template.options.length !== 1 ? 's' : '' }}
          </div>
        </div>
      </div>

      <!-- Create/Edit Modal -->
      <div
        v-if="showModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
        @click.self="closeModal"
      >
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
          <!-- Modal Header -->
          <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900">
              {{ editingTemplate ? 'Edit Template' : 'Create Template' }}
            </h2>
            <button
              @click="closeModal"
              class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Modal Body -->
          <div class="px-6 py-4 space-y-6">
            <!-- Template Name -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Template Name *</label>
              <input
                v-model="templateForm.name"
                type="text"
                placeholder="e.g., Men's Sizes, Colors, Styles"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
              />
            </div>

            <!-- Options -->
            <div>
              <div class="flex items-center justify-between mb-3">
                <label class="block text-sm font-semibold text-gray-700">Options *</label>
                <button
                  @click="addOption"
                  type="button"
                  class="text-sm text-emerald-600 hover:text-emerald-700 font-medium"
                >
                  + Add Option
                </button>
              </div>

              <div class="space-y-4">
                <div
                  v-for="(option, optionIndex) in templateForm.options"
                  :key="optionIndex"
                  class="border border-slate-200 rounded-lg p-4 bg-slate-50"
                >
                  <!-- Option Header -->
                  <div class="flex items-center gap-3 mb-3">
                    <input
                      v-model="option.name"
                      type="text"
                      placeholder="Option name (e.g., Size, Color)"
                      class="flex-1 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                    />
                    <button
                      v-if="templateForm.options.length > 1"
                      @click="removeOption(optionIndex)"
                      type="button"
                      class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                      title="Remove option"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>

                  <!-- Values Input -->
                  <div class="mb-2">
                    <div class="flex gap-2">
                      <input
                        v-model="currentValueInput[optionIndex]"
                        type="text"
                        placeholder="Add a value (e.g., S, M, L)"
                        @keydown="handleValueKeydown($event, optionIndex)"
                        class="flex-1 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm"
                      />
                      <button
                        @click="addValue(optionIndex)"
                        type="button"
                        class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium"
                      >
                        Add
                      </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Press Enter to add quickly</p>
                  </div>

                  <!-- Values List -->
                  <div v-if="option.values.length > 0" class="flex flex-wrap gap-2">
                    <div
                      v-for="(value, valueIndex) in option.values"
                      :key="valueIndex"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-sm"
                    >
                      <span class="text-gray-700">{{ value }}</span>
                      <button
                        @click="removeValue(optionIndex, valueIndex)"
                        type="button"
                        class="text-gray-400 hover:text-red-500 transition-colors"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </button>
                    </div>
                  </div>
                  <div v-else class="text-sm text-gray-500 italic">
                    No values added yet
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="sticky bottom-0 bg-slate-50 border-t border-slate-200 px-6 py-4 flex justify-end gap-3">
            <button
              @click="closeModal"
              type="button"
              class="px-6 py-2 border border-slate-300 text-gray-700 rounded-lg hover:bg-slate-100 transition-colors font-medium"
            >
              Cancel
            </button>
            <button
              @click="saveTemplate"
              type="button"
              :disabled="loading"
              class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ loading ? 'Saving...' : (editingTemplate ? 'Update Template' : 'Create Template') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
