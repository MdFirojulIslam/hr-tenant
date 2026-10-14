<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { useStore } from "vuex";
import SideBar from "./Sidebar.vue";

const store = useStore();
const route = useRoute();

const toggleSidebar = () => {
    store.commit("TOGGLE_SIDEBAR");
};

// Single source of truth for the breakpoint that separates the in-flow
// desktop sidebar from the off-canvas mobile/tablet sidebar. Must stay in
// sync with the media queries in Sidebar.vue.
const DESKTOP_QUERY = "(min-width: 992px)";

const mediaQueryList = window.matchMedia(DESKTOP_QUERY);
const isDesktop = ref(mediaQueryList.matches);

const sidebarOpen = computed(() => store.getters.getSidebarOpen);

// The off-canvas panel is only relevant below the desktop breakpoint.
const mobileOpen = computed(() => !isDesktop.value && sidebarOpen.value);

const closeSidebar = () => {
    if (sidebarOpen.value) {
        store.commit("TOGGLE_SIDEBAR");
    }
};

const onMediaChange = (event) => {
    isDesktop.value = event.matches;
    // Entering the mobile range must never leave the off-canvas panel open.
    if (!event.matches && sidebarOpen.value) {
        store.commit("TOGGLE_SIDEBAR");
    }
};

const onKeydown = (event) => {
    if (event.key === "Escape" && !isDesktop.value) {
        closeSidebar();
    }
};

// Close the off-canvas panel once navigation has occurred.
watch(
    () => route.fullPath,
    () => {
        if (!isDesktop.value) {
            closeSidebar();
        }
    },
);

// Initialise on a small screen with the off-canvas panel closed.
if (!isDesktop.value && sidebarOpen.value) {
    store.commit("TOGGLE_SIDEBAR");
}

onMounted(() => {
    mediaQueryList.addEventListener("change", onMediaChange);
    document.addEventListener("keydown", onKeydown);
});

onUnmounted(() => {
    mediaQueryList.removeEventListener("change", onMediaChange);
    document.removeEventListener("keydown", onKeydown);
});
</script>

<template>
    <div class="layout-wrapper">
        <div id="layoutSidenav">
            <!-- Shared Sidebar (desktop rail / mobile off-canvas) -->
            <SideBar v-if="$store.getters.getToken !== 0"></SideBar>

            <!-- Backdrop for the mobile off-canvas sidebar -->
            <div
                v-if="$store.getters.getToken !== 0 && mobileOpen"
                class="sidebar-backdrop"
                @click="closeSidebar"
            ></div>

            <!-- Content Area (Body only; the Navbar has been removed) -->
            <div id="layoutSidenav_content">
                <!-- Mobile-only sidebar opener. Hidden on the Dashboard, whose
                     own header provides the toggle. -->
                <div
                    v-if="
                        $store.getters.getToken !== 0 &&
                        !isDesktop &&
                        route.name !== 'Dashboard'
                    "
                    class="mobile-nav-fallback"
                >
                    <button
                        class="nav-toggle"
                        type="button"
                        @click="toggleSidebar"
                        aria-label="Toggle navigation"
                    >
                        <i class="fas fa-bars"></i>
                    </button>
                </div>

                <!-- Main Body (Scrollable Area) -->
                <main class="main">
                    <div class="container-fluid body-background">
                        <router-view></router-view>
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>

<style scoped>
.body-background {
    background-color: #ffffff;
}

.layout-wrapper {
    height: 100vh;
    overflow: hidden;
    background-color: #f8f8f8;
}

#layoutSidenav {
    display: flex;
    height: 100%;
    overflow: hidden;
}

#layoutSidenav_content {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
    height: 100%;
    overflow: hidden;
}

/* Neutralise the legacy SB Admin negative margin that shifts the content
   under the sidebar below the desktop breakpoint. */
#layoutSidenav #layoutSidenav_content {
    margin-left: 0;
}

.main {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
}

/* Slim mobile-only fallback for opening the off-canvas sidebar on pages
   that do not provide their own header toggle. */
.mobile-nav-fallback {
    display: flex;
    align-items: center;
    flex-shrink: 0;
    padding: 8px 12px 0;
}

.mobile-nav-fallback .nav-toggle {
    background: transparent;
    border: none;
    color: #33444a;
    font-size: 19px;
    line-height: 1;
    padding: 8px 10px;
    border-radius: 10px;
    cursor: pointer;
    transition:
        background-color 0.15s ease-in-out,
        color 0.15s ease-in-out;
}

.mobile-nav-fallback .nav-toggle:hover {
    background-color: #f3f6f5;
    color: #0e8a5f;
}

/* Mobile / tablet: off-canvas sidebar with a dismissible backdrop. */
@media (max-width: 991.98px) {
    .sidebar-backdrop {
        position: fixed;
        inset: 0;
        background-color: rgba(0, 0, 0, 0.45);
        z-index: 1040;
    }
}
</style>
