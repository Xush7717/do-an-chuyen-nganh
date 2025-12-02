<script setup lang="ts">
definePageMeta({
  middleware: 'guest',
})

const authStore = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const role = ref<'buyer' | 'seller'>('buyer')
const passwordError = ref('')

const handleRegister = async () => {
  authStore.clearError()
  passwordError.value = ''

  if (password.value !== confirmPassword.value) {
    passwordError.value = 'Passwords do not match'
    return
  }

  try {
    await authStore.register({
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: confirmPassword.value,
      role: role.value,
    })
  } catch (error) {
    console.error('Registration failed', error)
  }
}
</script>

<template>
  <div class="flex items-center justify-center pt-12 pb-12 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-primary-50 via-white to-primary-50 min-h-[calc(100vh-80px)]">
    <div class="max-w-md w-full space-y-8 bg-white/80 backdrop-blur-xl px-12 py-10 rounded-2xl shadow-xl border border-white/50">
      <div>
        <h2 class="mt-6 text-center text-3xl font-display font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary-600 to-secondary-600">
          Create an account
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Join us today and start exploring.
        </p>
      </div>
      <form class="mt-8 space-y-6" @submit.prevent="handleRegister">
        <div v-if="authStore.error || passwordError" class="rounded-xl bg-red-50 p-4 border border-red-200">
          <p class="text-sm text-red-800">{{ authStore.error || passwordError }}</p>
        </div>

        <div class="space-y-4">
          <div class="w-full">
            <label for="name" class="sr-only">Full Name</label>
            <input
              id="name"
              v-model="name"
              name="name"
              type="text"
              autocomplete="name"
              required
              class="appearance-none rounded-xl relative block w-95% px-4 py-3 border border-gray-200 bg-gray-50/50 placeholder-gray-500 text-gray-900 focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 sm:text-sm transition-all duration-300"
              placeholder="Full Name"
            >
          </div>
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
              autocomplete="new-password"
              required
              class="appearance-none rounded-xl relative block w-95% px-4 py-3 border border-gray-200 bg-gray-50/50 placeholder-gray-500 text-gray-900 focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 sm:text-sm transition-all duration-300"
              placeholder="Password"
            >
          </div>
          <div>
            <label for="confirm-password" class="sr-only">Confirm Password</label>
            <input
              id="confirm-password"
              v-model="confirmPassword"
              name="confirm-password"
              type="password"
              autocomplete="new-password"
              required
              class="appearance-none rounded-xl relative block w-95% px-4 py-3 border border-gray-200 bg-gray-50/50 placeholder-gray-500 text-gray-900 focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 sm:text-sm transition-all duration-300"
              placeholder="Confirm Password"
            >
          </div>
          
          <!-- Role Selection -->
          <div class="flex gap-4">
            <label class="flex-1 cursor-pointer">
              <input type="radio" v-model="role" value="buyer" class="peer sr-only">
              <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 text-center transition-all peer-checked:border-primary-500 peer-checked:bg-primary-50 peer-checked:text-primary-700 hover:bg-white">
                <span class="font-medium">Buyer</span>
              </div>
            </label>
            <label class="flex-1 cursor-pointer">
              <input type="radio" v-model="role" value="seller" class="peer sr-only">
              <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 text-center transition-all peer-checked:border-primary-500 peer-checked:bg-primary-50 peer-checked:text-primary-700 hover:bg-white">
                <span class="font-medium">Seller</span>
              </div>
            </label>
          </div>
        </div>
        
        <div>
          <button
            type="submit"
            :disabled="authStore.loading"
            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-xl text-white bg-gradient-to-r from-primary-600 to-primary-500 hover:from-primary-700 hover:to-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all duration-300 shadow-lg shadow-primary/25 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="authStore.loading">Creating account...</span>
            <span v-else>Create Account</span>
          </button>
        </div>

        <p class="mt-2 text-center text-sm text-gray-600">
          Already have an account?
          <NuxtLink to="/auth/login" class="font-medium text-primary hover:text-primary-700 transition-colors">
            Sign in
          </NuxtLink>
        </p>
      </form>
    </div>
  </div>
</template>
