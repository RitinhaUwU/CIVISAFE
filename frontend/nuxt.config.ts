// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  modules: [
    '@nuxt/eslint',
    '@nuxt/ui',
    '@vueuse/nuxt',
    '@vite-pwa/nuxt',
    '@pinia/nuxt'
  ],
  ssr: false,

  devtools: {
    enabled: true
  },

  css: [
    '~/assets/css/main.css',
    'leaflet/dist/leaflet.css'
  ],

  runtimeConfig: {
    public: {
      apiBase: ''
    }
  },

  compatibilityDate: '2024-07-11',

  vite: {
    optimizeDeps: {
      include: [
        '@vue/devtools-core',
        '@vue/devtools-kit',
        'axios',
      ]
    }
  },

  pwa: {
    registerType: 'autoUpdate',
    manifest: {
      name: 'CIVISAFE',
      short_name: 'CIVISAFE',
      description: 'Aplicação CIVISAFE',
      theme_color: '#ff6900',
      background_color: '#ffffff',
      display: 'standalone',
      start_url: '/',
      icons: [
        { src: '/icons/icon-192.png', sizes: '192x192', type: 'image/png' },
        { src: '/icons/icon-512.png', sizes: '512x512', type: 'image/png' }
      ]
    },
    workbox: {
      navigateFallback: '/',
      globPatterns: ['**/*.{js,css,html,ico,png,svg,woff,woff2}']
    },
    devOptions: {
      enabled: true,
      type: 'module'
    }
  },
})
