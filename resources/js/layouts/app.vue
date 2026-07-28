<script setup>
import { useStore } from "vuex";
import SideBar from "./Sidebar.vue";
import NavBar from "./Navbar.vue";
import Footer from "./Footer.vue";
import { onMounted, ref } from "vue";

const store = useStore();

const visibility = ref(false);
const layoutStyle = ref("");

const getData = async () => {
    if (store.getters.getToken !== 0) {
        visibility.value = true;
    }
    if (!store.getters.getToken) {
        layoutStyle.value = { margin: "0" };
    }
};

onMounted(() => getData());
</script>

<template>
    <div class="layout-wrapper">
        <!-- Top Fixed Navbar -->
        <NavBar v-if="$store.getters.getToken !== 0"></NavBar>

        <div id="layoutSidenav">
            <!-- Fixed Sidebar -->
            <SideBar v-if="$store.getters.getToken !== 0"></SideBar>

            <!-- Content Area (Body + Footer) -->
            <div id="layoutSidenav_content">
                <!-- Main Body (Scrollable Area) -->
                <main class="main">
                    <div class="container-fluid px-2 py-3 body-background">
                        <router-view></router-view>
                    </div>
                </main>

                <!-- Fixed Bottom Footer -->
                <Footer v-if="$store.getters.getToken !== 0" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.body-background {
    background-color: #ffffff;
}

.layout-wrapper {
    display: flex;
    flex-direction: column;
    height: 100vh;
    overflow: hidden;
}

#layoutSidenav {
    display: flex;
    flex: 1;
    overflow: hidden;
}

#layoutSidenav_content {
    display: flex;
    flex-direction: column;
    flex: 1;
    overflow: hidden;
}

:deep(#layoutSidenav #layoutSidenav_nav) {
    transform: translateX(0) !important;
}

.main {
    flex: 1;
    overflow-y: auto;
}

.tech-assist-btn:hover {
    background-color: #019d19f7;
    color: #fff !important;
}

@media (max-width: 768px) {
    /* #layoutSidenav_content {
       margin-left: 0 !important;
   } */

    /* .sidebar-hidden #layoutSidenav_content {
       margin-left: 0 !important;
   } */
    /* :deep(#layoutSidenav #layoutSidenav_content) {
       margin-left: 0 !important;
   } */
    /* #layoutSidenav {
       display: block;
   } */

    :deep(#layoutSidenav #layoutSidenav_nav) {
        position: fixed;
        left: 0;
        top: 56px; /* Navbar height */
        width: 230px;
        height: calc(100vh - 56px);
        z-index: 1050;
    }

    #layoutSidenav_content {
        width: 100%;
        margin-left: 0 !important;
    }
}
</style>
