//https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2024-04-03',
  devtools: { enabled: true },
  modules: [
    '@pinia/nuxt',
    '@unocss/nuxt'
  ],
  css: [
    '~/assets/css/main.css'
  ],
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://127.0.0.1:8000/api',
      stripeKey: process.env.NUXT_PUBLIC_STRIPE_KEY || ''
    }
  },
  app: {
    pageTransition: {
      name: 'page',
      mode: 'out-in'
    }
  },
  devServer: {
    port: 3000, // ✅ This is the correct way to set the dev server port
  },
  vite: {
    server: {
      hmr: {
        protocol: "http",
        host: 'localhost',
        clientPort: 3000,
        port: 3000,
      },
    },
  },
}
)

