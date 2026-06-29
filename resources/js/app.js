import "./bootstrap";

// import { createApp } from 'vue';
import { createApp } from "vue/dist/vue.esm-bundler";
import store from "./store/store.js";
import app from "./layouts/app.vue";
import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap";
import router from "./router.js";

const tokenExpiration = parseInt(
    localStorage.getItem("tokenExpiration") || 0,
    10,
);

if (tokenExpiration) {
    if (Date.now() > tokenExpiration) {
        store.dispatch("removeToken");
    }
}

createApp(app).use(router).use(store).mount("#app");
