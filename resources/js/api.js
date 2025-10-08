import axios from "axios";
import router from "./router";
import store from "./store/store";
import Swal from "sweetalert2";

const api = axios.create({ baseURL: "/api/" });

api.interceptors.request.use(
    function (config) {
        const token = localStorage.getItem("token");
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }

        return config;
    },
    function (error) {
        return Promise.reject(error);
    }
);

api.interceptors.response.use(
    function (response) {
        return response;
    },
    function (error) {
        if (error.response.status === 401) {
            localStorage.removeItem("token");
            store.dispatch("removeToken", 0);
            router.push({ name: "Login" });
        }
        return Promise.reject(error);
    }
);

export default api;
