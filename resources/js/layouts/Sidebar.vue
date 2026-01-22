<script setup>
import { ref, onMounted, computed } from "vue";
import api from "@/api";
import { useStore } from "vuex";

const error = ref([]);
const nav_tree = ref([]);
const dashboard = ref("");

const store = useStore();
const user = store.getters.getUser;

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
    <div id="layoutSidenav_nav">
        <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
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
                                <i class="fas fa-tachometer-alt"></i>
                            </div>
                            Dashboard
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
                            Profile
                        </Router-Link>
                    </div>
                    <div v-for="nav in nav_tree" :key="nav.id">
                        <a
                            class="nav-link collapsed"
                            href="#"
                            data-bs-toggle="collapse"
                            :data-bs-target="'#nav-' + nav.id"
                            aria-expanded="false"
                            :aria-controls="'nav-' + nav.id"
                        >
                            <div class="sb-nav-link-icon">
                                <i :class="nav.icon + ''"></i>
                            </div>
                            {{ nav.name }}
                            <div
                                class="sb-sidenav-collapse-arrow"
                                v-if="nav.children"
                            >
                                <i class="fas fa-angle-down"></i>
                            </div>
                        </a>

                        <div
                            class="collapse"
                            :id="'nav-' + nav.id"
                            aria-labelledby="headingOne"
                            data-bs-parent="#sidenavAccordion"
                            v-if="nav.children"
                        >
                            <nav
                                class="sb-sidenav-menu-nested nav flex-column"
                                v-for="child in nav.children"
                                :key="child.id"
                            >
                                <Router-Link
                                    class="nav-link"
                                    active-class="active"
                                    :to="{ name: child.page }"
                                >
                                    {{ child.name }}
                                </Router-Link>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</template>
