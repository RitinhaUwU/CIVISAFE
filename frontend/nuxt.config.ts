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
    },
  },

  compatibilityDate: '2024-07-11',

  vite: {
    optimizeDeps: {
      include: [
        '@vue/devtools-core',
        '@vue/devtools-kit',
        'axios',
        'laravel-echo',
        'pusher-js',
        'idb',
        'leaflet'
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

  // pwa: {
  //   registerType: 'autoUpdate',
  //   manifest: {
  //     name: 'CIVISAFE - Gestão de Ocorrências',
  //     short_name: 'CIVISAFE',
  //     // theme_color: '#ff6900',
  //     // background_color: '#ffffff',
  //     lang: 'pt',
  //     display: 'standalone',
  //     // start_url: '/',
  //     icons: [
  //       { src: '/icons/icon-192.png', sizes: '192x192', type: 'image/png' },
  //       { src: '/icons/icon-512.png', sizes: '512x512', type: 'image/png' }
  //     ]
  //   },
  //   workbox: {
  //     navigateFallback: '/',
  //     globPatterns: ['**/*.{js,css,html,ico,png,svg,woff,woff2}']
  //   },
  //   devOptions: {
  //     enabled: true,
  //     type: 'module'
  //   }
  // },


  // Fonte: https://stackoverflow.com/a/79379859
  // workbox, manifest devOptions **must** be set. registerType might be able to also be autoUpdate, but haven't tried it
  pwa: {
    registerType: 'prompt',

    manifest: {
      name: 'CIVISAFE',
      short_name: 'CIVISAFE',
      description: 'Plataforma CIVISAFE',
      theme_color: '#ffffff',
    },

    workbox: {
      globPatterns: ['**/*.{js,css,html,svg,png,ico}'],
      cleanupOutdatedCaches: true,
      clientsClaim: true,
    },

    devOptions: {
      enabled: false,
      suppressWarnings: true,
      navigateFallback: '/',
      navigateFallbackAllowlist: [/^\/$/],
      type: 'module',
    },
  },

  // In addition, you *must* have this Nitro option set to pre-render the homepage, even if you have SSR turned off:
  nitro: {
    prerender: {
      routes: ['/'],
    },
  },
})
