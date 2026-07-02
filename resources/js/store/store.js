// src/store/index.js
import { createStore } from "vuex";
import router from "../router";

const store = createStore({
    state: {
        // Variables
        token: localStorage.getItem("token") || 0,
        employeeId: null,
        roleId: localStorage.getItem("roleId") || null,
        user: (() => {
            try {
                return JSON.parse(localStorage.getItem("user"));
            } catch {
                return null;
            }
        })(),
        tokenTimeout: null,
        permissions: (() => {
            try {
                return JSON.parse(localStorage.getItem("permissions"));
            } catch {
                return null;
            }
        })(),
        notification: {
            successMessage: "",
            errorMessage: "",
        },
    },

    mutations: {
        // Update variable value
        UPDATE_TOKEN(state, payload) {
            state.token = payload;
        },
        SET_EMPLOYEE_ID(state, payload) {
            state.employeeId = payload;
        },
        SET_ROLE_ID(state, payload) {
            state.roleId = payload;
        },
        SET_USER(state, payload) {
            state.user = payload;
        },
        SET_TOKEN_TIMEOUT(state, payload) {
            state.tokenTimeout = payload;
        },
        SET_PERMISSIONS(state, payload) {
            state.permissions = payload;
        },
        setSuccess(state, message) {
            state.notification.successMessage = message;
            state.notification.errorMessage = "";
        },

        setError(state, message) {
            state.notification.errorMessage = message;
            state.notification.successMessage = "";
        },

        clearNotification(state) {
            state.notification.successMessage = "";
            state.notification.errorMessage = "";
        },
    },

    actions: {
        // Action to set token
        setToken({ commit, dispatch, state }, payload) {
            const expirationTime = Date.now() + 21600000; // Token valid for 6 hours

            localStorage.setItem("token", payload);
            localStorage.setItem("tokenExpiration", expirationTime);
            commit("UPDATE_TOKEN", payload);

            // Clear existing timeout
            if (state.tokenTimeout) {
                clearTimeout(state.tokenTimeout);
            }

            // Set token expiration timeout
            const timeoutId = setTimeout(() => {
                dispatch("removeToken");
            }, 21600000); // 6 hours
            commit("SET_TOKEN_TIMEOUT", timeoutId);
        },

        removeToken({ commit }) {
            commit("UPDATE_TOKEN", 0);
            commit("SET_EMPLOYEE_ID", null);
            commit("SET_TOKEN_TIMEOUT", null);

            // Clear all localStorage data
            localStorage.removeItem("token");
            localStorage.removeItem("tokenExpiration");
            localStorage.removeItem("permissions");
            localStorage.removeItem("user");
            localStorage.removeItem("roleId");

            // Redirect to login page
            router.push({ name: "Login" });
        },

        setEmployeeId({ commit }, payload) {
            commit("SET_EMPLOYEE_ID", payload);
        },

        setRoleId({ commit }, payload) {
            localStorage.setItem("roleId", payload);
            commit("SET_ROLE_ID", payload);
        },

        setUser({ commit }, payload) {
            localStorage.setItem("user", JSON.stringify(payload));
            commit("SET_USER", payload);
        },

        setPermissions({ commit }, payload) {
            localStorage.setItem("permissions", JSON.stringify(payload));
            commit("SET_PERMISSIONS", payload);
        },
    },

    getters: {
        // Retrieve state variables
        getToken(state) {
            return state.token;
        },
        getEmployeeId(state) {
            return state.employeeId;
        },
        getRoleId(state) {
            return state.roleId;
        },
        getUser(state) {
            return state.user;
        },
        getPermissions(state) {
            return state.permissions;
        },
    },
});

// Check token expiration on app initialization
if (localStorage.getItem("tokenExpiration")) {
    const expirationTime = parseInt(
        localStorage.getItem("tokenExpiration"),
        10,
    );
    if (Date.now() > expirationTime) {
        store.dispatch("removeToken");
    }
}

export default store;
