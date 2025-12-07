import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import react from '@vitejs/plugin-react';
import path from 'path';

// Enable React's new JSX transform
const reactPlugin = react({
  jsxImportSource: 'react',
  babel: {
    plugins: [
      ['@babel/plugin-transform-react-jsx', {
        runtime: 'automatic',
        importSource: 'react'
      }]
    ]
  }
});

export default defineConfig({
  plugins: [
    vue({
      template: {
        transformAssetUrls: {
          // The Vue plugin renames the `assets` option to `transformAssetUrls` in Vite
          includeAbsolute: false,
        },
      },
    }),
    reactPlugin // Use the configured React plugin
  ],
  root: 'resources',
  publicDir: '../../public',
  build: {
    outDir: '../../public/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: path.resolve(__dirname, 'resources/index.html'),
    },
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources/js'),
      '~': path.resolve(__dirname, './resources'),
    },
  },
  esbuild: {
    jsxInject: `import React from 'react'`
  },
  server: {
    host: '0.0.0.0',
    port: 3000,
    strictPort: true,
    hmr: {
      host: 'localhost',
    },
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
      '/storage': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
    },
  },
});
