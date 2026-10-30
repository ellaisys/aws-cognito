import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        lib: {
            entry: ['resources/js/cognito.js'],
            name: 'Cognito',
            formats: ['iife'],
        },
        rollupOptions: {
            output: {
                entryFileNames: '[name]-[format].[hash].js',
            },
        },
        outDir: 'dist',
        emptyOutDir: true,
    },
});
