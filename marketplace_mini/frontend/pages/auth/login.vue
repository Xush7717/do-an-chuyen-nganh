
<script setup lang="ts">
definePageMeta({
  middleware: 'guest',
})

const authStore = useAuthStore()

const email = ref('')
const password = ref('')

const handleLogin = async () => {
  authStore.clearError()
  try {
    await authStore.login({ email: email.value, password: password.value })
  } catch (error) {
    console.error('Login failed', error)
  }
}
</script>

<template>
  <div class="flex items-center justify-center pt-4 pb-12 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-primary-50 via-white to-primary-50 min-h-[calc(100vh-80px)]">
    <div class="max-w-md w-full space-y-8 bg-white/80 backdrop-blur-xl px-12 py-10 rounded-2xl shadow-xl border border-white/50">
      <div>
        <h2 class="mt-6 text-center text-3xl font-display font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary-600 to-secondary-600">
          Welcome back
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Please enter your details.
        </p>
      </div>
      <form class="mt-8 space-y-6" @submit.prevent="handleLogin">
        <div v-if="authStore.error" class="rounded-xl bg-red-50 p-4 border border-red-200">
          <p class="text-sm text-red-800">{{ authStore.error }}</p>
        </div>

        <div class="space-y-4">
          <div>
            <label for="email-address" class="sr-only">Email address</label>
            <input
              id="email-address"
              v-model="email"
              name="email"
              type="email"
              autocomplete="email"
              required
              class="appearance-none rounded-xl relative block w-95% px-4 py-3 border border-gray-200 bg-gray-50/50 placeholder-gray-500 text-gray-900 focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 sm:text-sm transition-all duration-300"
              placeholder="Email address"
            >
          </div>
          <div>
            <label for="password" class="sr-only">Password</label>
            <input
              id="password"
              v-model="password"
              name="password"
              type="password"
              autocomplete="current-password"
              required
              class="appearance-none rounded-xl relative block w-95% px-4 py-3 border border-gray-200 bg-gray-50/50 placeholder-gray-500 text-gray-900 focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 sm:text-sm transition-all duration-300"
              placeholder="Password"
            >
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input
              id="remember-me"
              name="remember-me"
              type="checkbox"
              class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
            >
            <label for="remember-me" class="ml-2 block text-sm text-gray-900">
              Remember me
            </label>
          </div>

          <div class="text-sm">
            <a href="#" class="font-medium text-primary hover:text-primary-700">
              Forgot your password?
            </a>
          </div>
        </div>

        <div>
          <button
            type="submit"
            :disabled="authStore.loading"
            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-xl text-white bg-gradient-to-r from-primary-600 to-primary-500 hover:from-primary-700 hover:to-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all duration-300 shadow-lg shadow-primary/25 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="authStore.loading">Signing in...</span>
            <span v-else>Sign in</span>
          </button>
        </div>

        <p class="mt-2 text-center text-sm text-gray-600">
          Don't have an account?
          <NuxtLink to="/auth/register" class="font-medium text-primary hover:text-primary-700 transition-colors">
            Create a new account
          </NuxtLink>
        </p>
      </form>
    </div>
  </div>
</template>
