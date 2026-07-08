import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin'
import { wordpressPlugin, wordpressThemeJson } from '@roots/vite-plugin';

// Set APP_URL if it doesn't exist for Laravel Vite plugin
if (! process.env.APP_URL) {
  process.env.APP_URL = 'http://example.test';
}

export default defineConfig({
  base: '/app/themes/sage/public/build/',
  server: {
    host: 'localhost',
    port: 5174,
    strictPort: true,
    origin: 'http://localhost:5174',
    // WP runs on a different origin (localhost:8080) and loads dev assets
    // (JS, fonts, @vite/client) cross-origin. Vite 8 restricts CORS to its own
    // origin by default, which blocks them — allow local WP origins.
    cors: {
      origin: /^https?:\/\/(localhost|127\.0\.0\.1)(?::\d+)?$/,
    },
  },
  plugins: [
    tailwindcss(),
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/css/editor.css',
        'resources/js/editor.js',
      ],
      refresh: true,
      assets: ['resources/images/**', 'resources/fonts/**'],
    }),

    wordpressPlugin(),

    // Generate the theme.json file in the public/build/assets directory
    // based on the Tailwind config and the theme.json file from base theme folder
    wordpressThemeJson({
      disableTailwindColors: false,
      disableTailwindFonts: false,
      disableTailwindFontSizes: false,
      disableTailwindBorderRadius: false,
    }),
  ],
  css: {
    // Emit CSS source maps in dev so DevTools attributes each rule to its
    // partial (button.css, wrap.css, …) instead of one flattened stylesheet.
    devSourcemap: true,
  },
  resolve: {
    alias: {
      '@scripts': '/resources/js',
      '@styles': '/resources/css',
      '@fonts': '/resources/fonts',
      '@images': '/resources/images',
    },
  },
})
