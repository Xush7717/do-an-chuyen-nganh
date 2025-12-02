import { defineStore } from 'pinia'

interface User {
  id: number
  name: string
  email: string
  role: 'buyer' | 'seller'
  email_verified_at: string | null
  created_at: string
  updated_at: string
}

interface LoginCredentials {
  email: string
  password: string
}

interface RegisterCredentials {
  name: string
  email: string
  password: string
  password_confirmation: string
  role?: 'buyer' | 'seller'
}

export const useAuthStore = defineStore('auth', () => {
  const config = useRuntimeConfig()
  const router = useRouter()

  const user = ref<User | null>(null)
  const token = useCookie<string | null>('auth_token', {
    maxAge: 60 * 60 * 24 * 7, // 7 days
  })
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const loading = ref(false)
  const error = ref<string | null>(null)

  /**
   * Register a new user
   */
  async function register(credentials: RegisterCredentials) {
    loading.value = true
    error.value = null

    try {
      const response = await $fetch<{
        user: User
        token: string
        message: string
      }>(`${config.public.apiBase}/auth/register`, {
        method: 'POST',
        body: credentials,
      })

      token.value = response.token
      user.value = response.user

      // Redirect based on role
      if (response.user.role === 'seller') {
        await router.push('/seller/dashboard')
      } else {
        await router.push('/')
      }

      return response
    } catch (err: any) {
      error.value = err.data?.message || 'Registration failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Login user
   */
  async function login(credentials: LoginCredentials) {
    loading.value = true
    error.value = null

    try {
      const response = await $fetch<{
        user: User
        token: string
        message: string
      }>(`${config.public.apiBase}/auth/login`, {
        method: 'POST',
        body: credentials,
      })

      token.value = response.token
      user.value = response.user

      // Redirect based on role
      if (response.user.role === 'seller') {
        await router.push('/seller/dashboard')
      } else {
        await router.push('/')
      }

      return response
    } catch (err: any) {
      error.value = err.data?.message || 'Login failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Logout user
   */
  async function logout() {
    loading.value = true
    error.value = null

    try {
      if (token.value) {
        await $fetch(`${config.public.apiBase}/auth/logout`, {
          method: 'POST',
          headers: {
            Authorization: `Bearer ${token.value}`,
          },
        })
      }
    } catch (err: any) {
      console.error('Logout error:', err)
    } finally {
      token.value = null
      user.value = null
      loading.value = false
      await router.push('/')
    }
  }

  /**
   * Fetch current user
   */
  async function fetchUser() {
    if (!token.value) {
      return null
    }

    loading.value = true
    error.value = null

    try {
      const response = await $fetch<{ user: User }>(
        `${config.public.apiBase}/auth/me`,
        {
          headers: {
            Authorization: `Bearer ${token.value}`,
          },
        }
      )

      user.value = response.user
      return response.user
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch user'
      token.value = null
      user.value = null
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Clear error
   */
  function clearError() {
    error.value = null
  }

  return {
    user,
    token,
    isAuthenticated,
    loading,
    error,
    register,
    login,
    logout,
    fetchUser,
    clearError,
  }
})
