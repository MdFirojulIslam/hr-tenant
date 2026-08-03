<script setup>
import { ref, onMounted, computed } from "vue";
import api from "@/api";
import { useStore } from "vuex";

const error = ref([]);
const nav_tree = ref([]);
const dashboard = ref("");

const openMenus = ref([]);

const store = useStore();
const user = store.getters.getUser;

const sidebarOpen = computed(() => store.getters.getSidebarOpen);

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
    <div id="layoutSidenav_nav" v-if="sidebarOpen">
        <nav class="sb-sidenav accordion sidebar-design" id="sidenavAccordion">
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
                            <div class="sb-nav-link-icon text-white">
                                <i class="fas fa-tachometer-alt"></i>
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
                            <div class="sb-nav-link-icon text-white">
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
                            <div class="sb-nav-link-icon text-white">
                                <i :class="nav.icon + ''"></i>
                            </div>
                            <span class="menu-names">{{ nav.name }}</span>
                            <div
                                class="sb-sidenav-collapse-arrow"
                                v-if="nav.children && nav.children.length"
                            >
                                <i
                                    class="fas fa-angle-down text-white arrow-icon"
                                    :class="{
                                        'rotate-arrow': openMenus.includes(
                                            nav.id,
                                        ),
                                    }"
                                ></i>
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
        </nav>
    </div>
</template>
<style scoped>
/* Sidebar Container Fixes */
#layoutSidenav_nav {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.sidebar-design {
    width: 260px;
    height: 100%;
    /* background-color: #198754; */
    background-color: #212529;
    display: flex;
    flex-direction: column;
}

.sb-sidenav-menu {
    flex: 1;
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
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
}

.sb-sidenav-menu::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.4);
}

.menu-names {
    color: rgba(255, 255, 255, 0.906);
    font-size: 16px;
    letter-spacing: 1px;
    margin-left: 5px;
}

.child-names {
    color: rgba(255, 255, 255, 0.906);
    font-size: 14px;
    letter-spacing: 1px;
}

.arrow-icon {
    transition: transform 0.3s ease;
}

.rotate-arrow {
    transform: rotate(-180deg);
}

:deep(.nav-link) {
    border-radius: 8px;
    background-color: transparent;
    cursor: pointer;

    transition:
        color 0.2s ease-in-out,
        background-color 0.2s ease-in-out,
        text-shadow 0.2s ease-in-out !important;

    padding-top: 8px !important;
    padding-bottom: 8px !important;
    padding-left: 13px !important;
    padding-right: 13px !important;
}

:deep(.nav-link:hover) {
    color: aliceblue;
    background-color: rgba(255, 255, 255, 0.1);
    text-shadow: 0 0 0.6px aliceblue;
}

:deep(.nav-link.active) {
    background-color: #838383;
    /* margin: 5px; */
    color: #fff;
    font-size: 16px;
}

:deep(.sb-sidenav-menu-nested) {
    padding-left: 13px !important;
    padding-right: 13px !important;
}
@media (max-width: 768px) {
    .menu-names {
        font-size: 12px;
    }
    .child-names {
        font-size: 10px;
    }
    .sidebar-design {
        width: 230px;
    }
    /* :deep(.nav-link.active) {
       font-size: 12px;
   } */
}
</style>
