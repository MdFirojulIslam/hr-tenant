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

// Only render an icon when the database actually provides one. Folder/child
// rows store the literal string "NULL" when they have no icon.
const hasIcon = (icon) =>
    typeof icon === "string" &&
    icon.trim() !== "" &&
    icon.trim().toUpperCase() !== "NULL";

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

            <!-- Navigation -->
            <div class="sb-sidenav-menu">
                <div class="nav mt-2 flex-column">
                    <div v-if="dashboard">
                        <Router-Link
                            class="nav-link nav-link-primary"
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
                            class="nav-link nav-link-primary"
                            :to="{ name: 'Profile' }"
                            active-class="active"
                        >
                            <div class="sb-nav-link-icon">
                                <i class="fa-regular fa-user"></i>
                            </div>
                            <span class="menu-names">Profile</span>
                        </Router-Link>
                    </div>

                    <div
                        class="nav-group"
                        v-for="nav in nav_tree"
                        :key="nav.id"
                    >
                        <a
                            class="nav-link nav-group-header"
                            :class="{
                                'group-open': openMenus.includes(nav.id),
                            }"
                            href="javascript:void(0)"
                            @click="toggleMenu(nav.id)"
                        >
                            <span class="group-label">
                                <span
                                    v-if="hasIcon(nav.icon)"
                                    class="sb-nav-link-icon"
                                >
                                    <i :class="nav.icon"></i>
                                </span>
                                <span class="menu-names">{{ nav.name }}</span>
                            </span>
                            <i
                                class="fas fa-angle-down sb-sidenav-collapse-arrow"
                                v-if="nav.children && nav.children.length"
                            ></i>
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
                                    class="nav-link child-link"
                                    active-class="active"
                                    :to="{ name: child.page }"
                                >
                                    <span
                                        v-if="hasIcon(child.icon)"
                                        class="sb-nav-link-icon"
                                    >
                                        <i :class="child.icon"></i>
                                    </span>
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
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap");

/* Sidebar container */
#layoutSidenav_nav {
    height: 100%;
    display: flex;
    flex-direction: column;
    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
    color: #1b2a2f;
}

/* Reset the legacy SB Admin flex-basis / transform so the sidebar rail
   sizes itself to its content inside the flex layout. */
#layoutSidenav #layoutSidenav_nav {
    flex: 0 0 auto;
    margin-left: 0;
    transform: none;
}

.sidebar-design {
    width: 264px;
    height: 100%;
    background-color: #ffffff;
    border-right: 1px solid #e3e9e7;
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 20px 14px 24px 14px;
    box-sizing: border-box;
    transition: width 0.15s ease-in-out;
}

/* Branding + app title + toggle */
.sidebar-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 2px 8px 16px 8px;
    flex-shrink: 0;
}

.sidebar-logo {
    height: 30px;
    width: 30px;
    object-fit: contain;
    border-radius: 8px;
    flex-shrink: 0;
}

.sidebar-title {
    flex: 1;
    color: #1b2a2f;
    font-size: 15px;
    font-weight: 800;
    letter-spacing: 0.04em;
    white-space: nowrap;
    overflow: hidden;
}

.sidebar-toggle {
    background: transparent;
    border: none;
    color: #5b6b70;
    font-size: 15px;
    padding: 6px 8px;
    border-radius: 8px;
    cursor: pointer;
    flex-shrink: 0;
    transition:
        background-color 0.15s ease-in-out,
        color 0.15s ease-in-out;
}

.sidebar-toggle:hover {
    background-color: #f3f6f5;
    color: #0e8a5f;
}

/* Employee identity */
.sidebar-identity {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 12px;
    background-color: #f3f6f5;
    border-radius: 12px;
    margin-bottom: 8px;
    flex-shrink: 0;
}

.identity-name {
    color: #1b2a2f;
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.identity-designation {
    color: #5b6b70;
    font-size: 11px;
    font-weight: 400;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sb-sidenav-menu {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
}

.sb-sidenav-menu::-webkit-scrollbar {
    width: 6px;
}

.sb-sidenav-menu::-webkit-scrollbar-track {
    background: transparent;
}

.sb-sidenav-menu::-webkit-scrollbar-thumb {
    background: #d5dfdc;
    border-radius: 999px;
}

.sb-sidenav-menu::-webkit-scrollbar-thumb:hover {
    background: #c2cfcb;
}

/* Base navigation item.
   Prefixed with #layoutSidenav_nav so these beat the global SB Admin
   selectors (.sb-sidenav .sb-sidenav-menu .nav .nav-link) without !important
   everywhere. */
#layoutSidenav_nav :deep(.nav-link) {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border-radius: 10px;
    background-color: transparent;
    color: #33444a;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0;
    text-decoration: none !important;
    cursor: pointer;
    border: none;
    transition:
        background-color 0.15s ease-in-out,
        color 0.15s ease-in-out;
}

/* Gap between items */
#layoutSidenav_nav :deep(.sb-sidenav-menu > .nav.flex-column) {
    gap: 2px;
}

/* Icon-to-text gap (reset the legacy SB Admin margin-right) */
#layoutSidenav_nav :deep(.sb-nav-link-icon) {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    color: inherit;
    font-size: 15px;
    margin-right: 0;
    flex-shrink: 0;
}

/* Hover state */
#layoutSidenav_nav :deep(.nav-link:hover) {
    background-color: #f3f6f5;
    color: #1b2a2f;
}

/* Active / selected state */
#layoutSidenav_nav :deep(.nav-link.active) {
    background-color: #ddf1e8;
    color: #096645;
    font-weight: 700;
}

/* Group headers (database icon treatment) */
.nav-group {
    margin-top: 6px;
}

#layoutSidenav_nav :deep(.nav-group-header) {
    justify-content: space-between;
    color: #1b2a2f;
    font-weight: 700;
    font-size: 13px;
}

.group-label {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

/* Brand accent for the group's database icon. */
.group-label .sb-nav-link-icon {
    color: #0e8a5f;
}

/* Collapse arrow */
#layoutSidenav_nav :deep(.sb-sidenav-collapse-arrow) {
    color: #5b6b70;
    font-size: 12px;
    transition: transform 0.2s ease;
}

#layoutSidenav_nav :deep(.nav-group-header.group-open .sb-sidenav-collapse-arrow) {
    transform: rotate(180deg);
}

/* Child list indentation with guide border */
#layoutSidenav_nav :deep(.collapse-menu) {
    display: flex;
    flex-direction: column;
    gap: 2px;
    margin-top: 2px;
}

#layoutSidenav_nav :deep(.sb-sidenav-menu-nested) {
    display: flex;
    flex-direction: column;
    margin-left: 18px;
    padding-left: 12px;
    border-left: 1px solid #e3e9e7;
}

/* Child navigation items */
#layoutSidenav_nav :deep(.sb-sidenav-menu-nested .nav-link) {
    padding: 6px 12px;
    font-size: 12.5px;
    font-weight: 400;
    color: #4a5c62;
    border-radius: 6px;
}

#layoutSidenav_nav :deep(.sb-sidenav-menu-nested .nav-link:hover) {
    background-color: #f3f6f5;
    color: #1b2a2f;
}

#layoutSidenav_nav :deep(.sb-sidenav-menu-nested .nav-link.active) {
    color: #0e8a5f;
    font-weight: 700;
    background-color: #f3f6f5;
}

/* Desktop: collapsed icon rail */
@media (min-width: 992px) {
    #layoutSidenav_nav.sidebar-collapsed .sidebar-design {
        width: 72px;
        padding: 20px 12px;
    }

    #layoutSidenav_nav.sidebar-collapsed .sidebar-header {
        flex-direction: column;
        gap: 10px;
        padding: 2px 0 12px 0;
    }

    #layoutSidenav_nav.sidebar-collapsed .sidebar-title,
    #layoutSidenav_nav.sidebar-collapsed .sidebar-identity,
    #layoutSidenav_nav.sidebar-collapsed :deep(.footer) {
        display: none;
    }

    #layoutSidenav_nav.sidebar-collapsed :deep(.nav-link) {
        justify-content: center;
        padding: 9px 0;
        gap: 0;
    }

    #layoutSidenav_nav.sidebar-collapsed .menu-names {
        display: none;
    }

    #layoutSidenav_nav.sidebar-collapsed :deep(.sb-sidenav-collapse-arrow),
    #layoutSidenav_nav.sidebar-collapsed :deep(.collapse-menu) {
        display: none !important;
    }

    #layoutSidenav_nav.sidebar-collapsed .nav-group {
        margin-top: 2px;
    }
}

/* Mobile / tablet: off-canvas drawer */
@media (max-width: 991.98px) {
    #layoutSidenav #layoutSidenav_nav {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: min(264px, 86vw);
        z-index: 1050;
        transform: translateX(-100%);
        transition: transform 0.2s ease-in-out;
        /* Fully out of the way when closed: never intercept taps. */
        pointer-events: none;
    }

    #layoutSidenav #layoutSidenav_nav.sidebar-open {
        transform: translateX(0);
        pointer-events: auto;
        box-shadow: 0 0 24px rgba(27, 42, 47, 0.18);
    }

    .sidebar-design {
        width: 100%;
    }
}
</style>
