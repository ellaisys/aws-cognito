import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        lib: {
            entry: [
                'resources/js/cognito.js'
            ],
            name: 'Cognito',
            formats: ['iife'],
            fileName: 'cognito',
        },
        rollupOptions: {
            output: {
                entryFileNames: '[name]-[format].[hash].js',
                assetFileNames: '[name]-[hash][extname]',
            },
        },
        outDir: 'dist',
        emptyOutDir: true,
    }
});
