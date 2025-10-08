import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import path from "path";

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/js/assets/css/styles.css",
                "resources/js/assets/js/scripts.js",
                "resources/js/assets/js/datatables-simple-demo.js",
            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes("node_modules")) {
                        return id
                            .toString()
                            .split("node_modules/")[1]
                            .split("/")[0]
                            .toString();
                    }
                },
            },
        },
        chunkSizeWarningLimit: 1000, // Increase the warning limit
    },
    alias: {
        "@": path.resolve(__dirname, "resources/js"),
        api: path.resolve(__dirname, "resources/js/api.js"),
        vue: 'vue/dist/vue.esm-bundler.js',
    },
});
