<script setup>
import { ref, onMounted, computed } from "vue";
import api from "@/api";
import { useStore } from "vuex";
import Footer from "./Footer.vue";

const error = ref([]);
const nav_tree = ref([]);
const dashboard = ref("");

const openMenus = ref([]);

const store = useStore();
const user = store.getters.getUser;

const sidebarOpen = computed(() => store.getters.getSidebarOpen);

const toggleSidebar = () => {
    store.commit("TOGGLE_SIDEBAR");
};

const toggleMenu = (id) => {
    if (openMenus.value.includes(id)) {
        openMenus.value = openMenus.value.filter((menuId) => menuId !== id);
    } else {
        openMenus.value.push(id);
    }
};

const filterActiveNodes = (nodes) => {
    return nodes
        .filter((node) => node.active === 1 && node.id !== 1)
        .map((node) => ({
            ...node,
            children: node.children ? filterActiveNodes(node.children) : [],
        }));
};

const getData = async () => {
    try {
        let { data } = await api.get("/navigation");
        if (data && data.tree) {
            dashboard.value = data.dashboard;
            nav_tree.value = filterActiveNodes(data.tree);
        }
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

const isDashboardFirst = computed(() => {
    return nav_tree.value.length > 0 && nav_tree.value[0].name === "Dashboard";
});

onMounted(() => getData());
</script>

<template>
    <div
        id="layoutSidenav_nav"
        :class="{ 'sidebar-collapsed': !sidebarOpen, 'sidebar-open': sidebarOpen }"
    >
        <nav class="sb-sidenav accordion sidebar-design" id="sidenavAccordion">
            <!-- Branding + App Title + Toggle -->
            <div class="sidebar-header">
                <img
                    src="/public/storage/uploads/1718087744_artisan.png"
                    alt="Artisan Logo"
                    class="sidebar-logo"
                />
                <span class="sidebar-title">ARTISAN HRM</span>
                <button
                    class="sidebar-toggle"
                    type="button"
                    @click="toggleSidebar"
                    aria-label="Toggle sidebar"
                >
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <!-- Employee Identity -->
            <div class="sidebar-identity">
                <span class="identity-name">{{ user?.Full_Name }}</span>
                <span class="identity-designation">{{ user?.des_name }}</span>
            </div>

            <!-- Profile Section -->
            <div class="sb-sidenav-menu">
                <!-- Nav Links -->
                <div class="nav mt-4 flex-column">
                    <div v-if="dashboard">
                        <Router-Link
                            class="nav-link"
                            :to="{ name: 'Dashboard' }"
                            active-class="active"
                        >
                            <div class="sb-nav-link-icon">
                                <i class="fa-solid fa-chart-pie"></i>
                            </div>
                            <span class="menu-names">Dashboard</span>
                        </Router-Link>
                    </div>

                    <div>
                        <Router-Link
                            class="nav-link"
                            :to="{ name: 'Profile' }"
                            active-class="active"
                        >
                            <div class="sb-nav-link-icon">
                                <i class="fa-regular fa-user"></i>
                            </div>
                            <span class="menu-names">Profile</span>
                        </Router-Link>
                    </div>

                    <div v-for="nav in nav_tree" :key="nav.id">
                        <a
                            class="nav-link"
                            :class="{ collapsed: !openMenus.includes(nav.id) }"
                            href="javascript:void(0)"
                            @click="toggleMenu(nav.id)"
                        >
                            <div class="sb-nav-link-icon">
                                <i :class="nav.icon + ''"></i>
                            </div>
                            <span class="menu-names">{{ nav.name }}</span>
                            <div
                                class="sb-sidenav-collapse-arrow"
                                v-if="nav.children && nav.children.length"
                            >
                                <i class="fas fa-angle-down"></i>
                            </div>
                        </a>

                        <div
                            class="collapse-menu"
                            v-if="nav.children && nav.children.length"
                            v-show="openMenus.includes(nav.id)"
                        >
                            <nav
                                class="sb-sidenav-menu-nested nav flex-column"
                                v-for="child in nav.children"
                                :key="child.id"
                            >
                                <RouterLink
                                    v-if="$router.hasRoute(child.page)"
                                    class="nav-link"
                                    active-class="active"
                                    :to="{ name: child.page }"
                                >
                                    <span class="child-names">
                                        {{ child.name }}
                                    </span>
                                </RouterLink>

                                <span v-else class="nav-link text-danger">
                                    {{ child.name }}
                                </span>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            <Footer />
        </nav>
    </div>
</template>
<style scoped>
/* Sidebar container */
#layoutSidenav_nav {
    height: 100%;
    display: flex;
    flex-direction: column;
    font-family: "Segoe UI Variable", "Segoe UI", system-ui, sans-serif;
    color: #242424;
}

/* Reset the legacy SB Admin flex-basis / transform so the sidebar rail
   sizes itself to its content inside the flex layout. */
#layoutSidenav #layoutSidenav_nav {
    flex: 0 0 auto;
    margin-left: 0;
    transform: none;
}

.sidebar-design {
    width: 260px;
    height: 100%;
    background-color: #f8f8f8;
    display: flex;
    flex-direction: column;
    transition: width 0.15s ease-in-out;
}

/* Branding + app title + toggle */
.sidebar-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    border-bottom: 1px solid #e5e5e5;
    flex-shrink: 0;
}

.sidebar-logo {
    height: 32px;
    width: 32px;
    object-fit: contain;
    flex-shrink: 0;
}

.sidebar-title {
    flex: 1;
    color: #242424;
    font-size: 16px;
    font-weight: 600;
    letter-spacing: 0.5px;
    white-space: nowrap;
    overflow: hidden;
}

.sidebar-toggle {
    background: transparent;
    border: none;
    color: #242424;
    font-size: 16px;
    padding: 4px 6px;
    border-radius: 4px;
    cursor: pointer;
    flex-shrink: 0;
    transition: background-color 0.15s ease-in-out;
}

.sidebar-toggle:hover {
    background-color: #f0f0f0;
}

/* Employee identity */
.sidebar-identity {
    display: flex;
    flex-direction: column;
    padding: 12px 16px;
    border-bottom: 1px solid #e5e5e5;
    flex-shrink: 0;
}

.identity-name {
    color: #242424;
    font-size: 14px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.identity-designation {
    color: #616161;
    font-size: 12px;
    font-weight: 400;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Desktop: collapsed icon rail */
@media (min-width: 992px) {
    #layoutSidenav_nav.sidebar-collapsed .sidebar-design {
        width: 56px;
    }

    #layoutSidenav_nav.sidebar-collapsed .sidebar-header {
        flex-direction: column;
        gap: 6px;
        padding: 12px 8px;
    }

    #layoutSidenav_nav.sidebar-collapsed .sidebar-title,
    #layoutSidenav_nav.sidebar-collapsed .sidebar-identity,
    #layoutSidenav_nav.sidebar-collapsed .sb-sidenav-menu,
    #layoutSidenav_nav.sidebar-collapsed :deep(.footer) {
        display: none;
    }
}

.sb-sidenav-menu {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
}

.sb-sidenav-menu::-webkit-scrollbar {
    width: 8px;
}

.sb-sidenav-menu::-webkit-scrollbar-track {
    background: transparent;
}

.sb-sidenav-menu::-webkit-scrollbar-thumb {
    background: #d1d1d1;
    border-radius: 4px;
}

.sb-sidenav-menu::-webkit-scrollbar-thumb:hover {
    background: #b3b3b3;
}

/* Main navigation text */
.menu-names {
    color: #242424;
    font-size: 14px;
    font-weight: 500;
    letter-spacing: 0;
}

/* Child navigation text */
.child-names {
    color: #242424;
    font-size: 14px;
    font-weight: 400;
    letter-spacing: 0;
}

/* Collapse arrow */
:deep(.sb-sidenav-collapse-arrow) {
    color: #616161;
    font-size: 12px;
    transition: transform 0.2s ease;
}

/* Base navigation item */
:deep(.nav-link) {
    border-radius: 4px !important;
    background-color: transparent !important;
    color: #242424 !important;
    cursor: pointer;
    text-decoration: none !important;
    transition:
        background-color 0.15s ease-in-out,
        color 0.15s ease-in-out !important;
}

/* Main navigation items */
:deep(.sb-sidenav-menu > .nav > div > .nav-link) {
    display: flex !important;
    align-items: center !important;
    min-height: 36px !important;
    padding: 0 12px !important;
    margin: 0 !important;
    font-size: 14px !important;
    font-weight: 500 !important;
}

/* Gap between main navigation items */
:deep(.sb-sidenav-menu > .nav.flex-column) {
    gap: 4px !important;
}

/* Icon-to-text gap */
:deep(.sb-nav-link-icon) {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 16px;
    margin-right: 8px !important;
    color: #242424;
    font-size: 15px;
}

/* Hover state */
:deep(.nav-link:hover) {
    background-color: #f0f0f0 !important;
    color: #242424 !important;
}

/* Active / selected state */
:deep(.nav-link.active) {
    background-color: #e6e6e6 !important;
    color: #242424 !important;
}

/* Parent to child list spacing */
:deep(.collapse-menu) {
    display: flex;
    flex-direction: column;
    gap: 2px;
    margin-top: 4px;
}

/* Child list indentation */
:deep(.sb-sidenav-menu-nested) {
    display: flex;
    flex-direction: column;
    margin-left: 0 !important;
    padding-left: 24px !important;
    padding-right: 12px !important;
}

/* Child navigation items */
:deep(.sb-sidenav-menu-nested .nav-link) {
    display: flex !important;
    align-items: center !important;
    min-height: 32px !important;
    padding: 0 12px !important;
    font-size: 14px !important;
    font-weight: 400 !important;
}

@media (max-width: 991.98px) {
    #layoutSidenav #layoutSidenav_nav {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: min(260px, 86vw);
        z-index: 1050;
        transform: translateX(-100%);
        transition: transform 0.2s ease-in-out;
        /* Fully out of the way when closed: never intercept taps. */
        pointer-events: none;
    }

    #layoutSidenav #layoutSidenav_nav.sidebar-open {
        transform: translateX(0);
        pointer-events: auto;
        box-shadow: 0 0 16px rgba(0, 0, 0, 0.2);
    }

    .sidebar-design {
        width: 100%;
    }
}
</style>
