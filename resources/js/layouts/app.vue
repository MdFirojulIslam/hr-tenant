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

// 👉 Your Microsoft Forms link
const chatDocLink =
    "https://forms.office.com/Pages/DesignPageV2.aspx?prevorigin=shell&origin=NeoPortalPage&subpage=design&id=71-irbx1j0OV0-fAZS9sYjHhXYvQ5MBGuWLtmo0ArdRUMEk5ODQ1VjFTOUZGSUdVU0VOMTJJVTBVMiQlQCN0PWcu&analysis=false&topview=Preview";
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

    <!-- Floating Tech Assist Button -->
    <a
        v-if="$store.getters.getToken !== 0"
        :href="chatDocLink"
        target="_blank"
        title="Tech Assist"
        class="tech-assist-btn d-flex align-items-center justify-content-center position-fixed rounded-circle border border-success text-success shadow"
        style="bottom: 40px; right: 15px; z-index: 3; width: 60px; height: 60px"
    >
        <i class="fas fa-headset fa-2x"></i>
    </a>
</template>

<style scoped>
.tech-assist-btn:hover {
    background-color: #019d19f7;
    color: #fff !important;
}
</style>
