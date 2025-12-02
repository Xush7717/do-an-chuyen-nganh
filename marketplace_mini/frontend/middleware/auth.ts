export default defineNuxtRouteMiddleware(async (to, from) => {
  const authStore = useAuthStore()

  // If user is not authenticated, fetch user data
  if (authStore.token && !authStore.user) {
    try {
      await authStore.fetchUser()
    } catch (error) {
      console.error('Failed to fetch user:', error)
      return navigateTo('/auth/login')
    }
  }

  // If no token, redirect to login
  if (!authStore.token) {
    return navigateTo('/auth/login')
  }

  // If user is authenticated, check if they are authorized
  if (authStore.user) {
    // Check for seller-only routes
    if (to.path.startsWith('/seller') && authStore.user.role !== 'seller') {
      return navigateTo('/')
    }
  }
})
