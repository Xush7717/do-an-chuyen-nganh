export default defineNuxtPlugin(async (nuxtApp) => {
  const authStore = useAuthStore()

  // If token exists but user is not loaded, fetch user data
  if (authStore.token && !authStore.user) {
    try {
      await authStore.fetchUser()
    } catch (error) {
      console.error('Failed to fetch user on app init:', error)
      // Token is invalid, clear it
      authStore.token = null
    }
  }
})
