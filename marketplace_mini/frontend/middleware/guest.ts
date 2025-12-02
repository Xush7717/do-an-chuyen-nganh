export default defineNuxtRouteMiddleware(async (to, from) => {
  const authStore = useAuthStore()

  // If user is already authenticated, redirect based on role
  if (authStore.token && authStore.user) {
    if (authStore.user.role === 'seller') {
      return navigateTo('/seller/dashboard')
    } else {
      return navigateTo('/')
    }
  }
})
