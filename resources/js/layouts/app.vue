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
    <NavBar v-if="$store.getters.getToken !== 0"></NavBar>
    <div id="layoutSidenav">
        <SideBar v-if="$store.getters.getToken !== 0"></SideBar>
        <div id="layoutSidenav_content">
            <div class="main">
                <div class="container-fluid px-4 py-4">
                    <router-view></router-view>
                </div>
            </div>
            <Footer v-if="$store.getters.getToken !== 0" />
        </div>
    </div>
</template>

<style scoped>
.tech-assist-btn:hover {
    background-color: #019d19f7;
    color: #fff !important;
}
</style>
