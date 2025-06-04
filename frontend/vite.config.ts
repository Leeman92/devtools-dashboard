import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
  server: {
    port: 5173,
    host: '0.0.0.0',
    proxy: {
      '/api': {
        target: 'http://172.17.0.1:80',
        changeOrigin: true,
        secure: false,
      },
    },
  },
  build: {
    outDir: 'dist',
    sourcemap: false,
    minify: 'terser',
    chunkSizeWarningLimit: 500,
    rollupOptions: {
      output: {
        manualChunks: {
          'react-core': ['react', 'react-dom'],
          'react-router': ['react-router-dom'],
          'react-query': ['@tanstack/react-query'],
          'radix-ui': [
            '@radix-ui/react-avatar',
            '@radix-ui/react-dropdown-menu', 
            '@radix-ui/react-icons',
            '@radix-ui/react-slot'
          ],
          'ui-utils': [
            'lucide-react',
            'class-variance-authority',
            'clsx',
            'tailwind-merge'
          ],
          'charts': ['recharts'],
          'date-utils': ['date-fns']
        },
        chunkFileNames: (chunkInfo) => {
          const facadeModuleId = chunkInfo.facadeModuleId
          if (facadeModuleId?.includes('node_modules')) {
            return 'vendor/[name]-[hash].js'
          }
          return 'assets/[name]-[hash].js'
        },
        assetFileNames: (assetInfo) => {
          const info = assetInfo.name?.split('.')
          const ext = info?.[info.length - 1]
          if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(ext || '')) {
            return 'images/[name]-[hash][extname]'
          }
          if (/css/i.test(ext || '')) {
            return 'styles/[name]-[hash][extname]'
          }
          return 'assets/[name]-[hash][extname]'
        }
      },
    },
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true,
        pure_funcs: ['console.log', 'console.info'],
        dead_code: true,
        conditionals: true,
        loops: true
      },
      mangle: {
        keep_fnames: false
      }
    }
  },
})
