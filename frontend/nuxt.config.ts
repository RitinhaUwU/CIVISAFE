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
      apiBase: '',
      reverbAppKey: '',
      reverbHost: '',
      reverbPort: '',
      reverbScheme: '',

      appVersion: process.env.COMMIT_SHA ?? 'dev - ' + (new Date().toLocaleString('pt-PT'))
    },
  },

  compatibilityDate: '2024-07-11',

  vite: {
    optimizeDeps: {
      include: [
        '@tanstack/table-core',
        '@vue/devtools-core',
        '@vue/devtools-kit',
        'axios',
        'chart.js',
        'idb',
        'laravel-echo',
        'leaflet', // CJS
        'pusher-js', // CJS
        'vue-chartjs',
      ]
    }
  },

  // https://www.npmjs.com/package/@nuxt/icon#client-bundle
  // TODO: tentar fazer com que os icons sejam bundled ao invés de os obter pela rede
  // Pode ser necessário trocar do iconify para o nuxt/icon ou algo do tipo
  // icon: {
  //   provider: "none",
  //   clientBundle: {
  //     scan: true,
  //     // icons: ['lucide'],
  //   },
  // },

  // Fonte: https://stackoverflow.com/a/79379859
  // workbox, manifest devOptions **must** be set. registerType might be able to also be autoUpdate, but haven't tried it
  pwa: {
    registerType: 'autoUpdate',

    manifest: {
      name: 'CIVISAFE',
      short_name: 'CIVISAFE',
      description: 'Plataforma CIVISAFE',
      theme_color: '#ffffff',
      icons: [
        { src: '/icon_64.png', type: 'image/png', sizes: '64x64' },
        { src: '/icon_192.png', type: 'image/png', sizes: '192x192' },
        { src: '/icon_256.png', type: 'image/png', sizes: '256x256' },
        { src: '/icon_512.png', type: 'image/png', sizes: '512x512' },
        { src: '/icon_512.png', type: 'image/png', purpose: 'any', sizes: '512x512' },
      ],
      start_url: '/',
      display: 'standalone',
      background_color: '#ffffff',
      lang: 'pt'
    },

    workbox: {
      // 1. Cache all standard assets, but explicitly LEAVE OUT 'html'
      globPatterns: ['**/*.{js,css,svg,png,ico}'],

      // 2. Explicitly tell Workbox to ignore the empty static file on disk
      globIgnores: ['**/index.html'],

      // 3. Force the Service Worker to fetch the root route during installation.
      // This forces the request through Nitro, caching the HTML *with* your injected env vars.
      additionalManifestEntries: [
        { url: '/', revision: `${Date.now()}` }
      ],

      // 4. Set the SPA fallback to the populated route we just cached
      navigateFallback: '/',

      cleanupOutdatedCaches: true,
      clientsClaim: true,
      skipWaiting: true,
    },

    devOptions: {
      enabled: false,
      suppressWarnings: true,
      navigateFallbackAllowlist: [/^\/$/],
      type: 'module',
    },
  },

  // In addition, you *must* have this Nitro option set to pre-render the homepage, even if you have SSR turned off:
  // Tive de comentar isto para as variáveis de ambiente carregarem. Parece que dá um erro no primeiro carregamento mas
  // depois como a página já está em cache, ele não se queixa
  // nitro: {
  //   prerender: {
  //     routes: ['/'],
  //   },
  // },
})
