<script setup lang="ts">
// Type definitions
interface Review {
  id: number
  product_id: number
  user_id: number
  rating: number
  comment: string | null
  created_at: string
  updated_at: string
  user?: {
    id: number
    name: string
  }
}

interface ReviewStats {
  average_rating: number
  review_count: number
}

interface ReviewsResponse {
  reviews: Review[]
  stats: ReviewStats
}

const props = defineProps<{
  productId: number
}>()

const reviews = ref<Review[]>([])
const stats = ref<ReviewStats>({ average_rating: 0, review_count: 0 })
const loading = ref(true)

const loadReviews = async () => {
  try {
    const data = await $fetch<ReviewsResponse>(`http://127.0.0.1:8000/api/products/${props.productId}/reviews`)
    reviews.value = data.reviews
    stats.value = data.stats
  } catch (error) {
    console.error('Failed to load reviews:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadReviews()
})

const renderStars = (rating: number) => {
  return '★'.repeat(rating) + '☆'.repeat(5 - rating)
}
</script>

<template>
  <div class="mt-8">
    <h2 class="text-2xl font-bold text-slate-800 mb-4">Customer Reviews</h2>

    <!-- DEBUG INFO (Remove this after testing) -->
    <!-- <div class="mb-4 p-3 bg-gray-100 border border-gray-300 rounded text-xs font-mono">
      <div><strong>Debug Info:</strong></div>
      <div>isLoggedIn: {{ isLoggedIn }}</div>
      <div>canReview: {{ canReview }}</div>
      <div>showForm: {{ showForm }}</div>
      <div>Has token: {{ !!authStore.token }}</div>
      <div>Has user: {{ !!authStore.user }}</div>
    </div> -->

    <div v-if="!loading" class="mb-6">
      <div class="flex items-center gap-2 mb-4">
        <span class="text-3xl font-bold text-emerald-600">{{ stats.average_rating }}</span>
        <div class="text-yellow-500 text-xl">{{ renderStars(Math.round(stats.average_rating)) }}</div>
        <span class="text-slate-600">({{ stats.review_count }} reviews)</span>
      </div>

      <!-- Info: Reviews can only be submitted from order page -->
      <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <p class="text-blue-800 text-sm">
          <strong>Note:</strong> You can write a review for this product from your
          <NuxtLink to="/profile/orders" class="font-semibold underline hover:text-blue-900">Order History</NuxtLink>
          after your order has been delivered.
        </p>
      </div>
    </div>

    <div v-if="loading" class="text-center py-8">
      <p class="text-slate-600">Loading reviews...</p>
    </div>

    <div v-else-if="reviews.length === 0" class="text-center py-8">
      <p class="text-slate-600">No reviews yet. Be the first to review this product!</p>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="review in reviews"
        :key="review.id"
        class="p-4 bg-slate-50 rounded-lg"
      >
        <div class="flex items-start justify-between mb-2">
          <div>
            <div class="font-semibold text-slate-800">{{ review.user?.name || 'Anonymous' }}</div>
            <div class="text-yellow-500">{{ renderStars(review.rating) }}</div>
          </div>
          <div class="text-sm text-slate-500">
            {{ new Date(review.created_at).toLocaleDateString() }}
          </div>
        </div>
        <p v-if="review.comment" class="text-slate-700">{{ review.comment }}</p>
      </div>
    </div>
  </div>
</template>
