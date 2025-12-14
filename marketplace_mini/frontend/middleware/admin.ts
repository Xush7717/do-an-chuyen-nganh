export default defineNuxtRouteMiddleware(async (to, from) => {
  const authStore = useAuthStore()

  // Ensure user is fetched
  if (authStore.token && !authStore.user) {
    try {
      await authStore.fetchUser()
    } catch (error) {
      return navigateTo('/auth/login')
    }
  }

  // Check if user is authenticated
  if (!authStore.token || !authStore.user) {
    return navigateTo('/auth/login')
  }

  // Check if user is admin
  if (authStore.user.role !== 'admin') {
    return navigateTo('/')
  }
})
