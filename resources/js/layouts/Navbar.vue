<template>
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <!-- Navbar Brand-->
        <Router-Link class="navbar-brand ps-3" to="#">
            <img src="/public/storage/uploads/1718087744_artisan.png" height="35px" width="35px" class="mx-3" /> Artisan
            HRM</Router-Link>
        <!-- Sidebar Toggle-->
        <button class="btn btn-link btn-sm mx-4" id="sidebarToggle" href="#!">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Navbar Search-->
        <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
            <!-- Search form here -->
        </form>
        <!-- Navbar-->

        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
            <li class="nav-item dropdown position-relative p-2">
                <a class="nav-link position-relative" id="navbarDropdown" href="#" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-bell" style="font-size: large"></i>
                    <!-- Notification count badge -->
                    <span v-if="totalNotifications > 0"
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        {{ totalNotifications }}
                        <span class="visually-hidden">unread messages</span>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown"
                    style="background-color: #c7d1cb">
                    <div v-for="item in notification" :key="item.id">
                        <li class="dropdown-item" v-if="
                            item.Status === 'Pending'
                        " @click="router.push('/leave/supervisor-approval')">
                            {{ item.Full_Name }} Requested a Leave
                        </li>
                        <li class="dropdown-item" v-if="item.Status === 'Approved (SV)'"
                            @click="router.push('/leave/hr-approval')">
                            {{ item.Full_Name }} leave has been accepted by his
                            Supervisor.
                        </li>
                    </div>
                </ul>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="userDropdown" href="#" role="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    {{ user.Full_Name }}
                    <img :src="user.img_url" alt="User Image" height="40px" width="40px" style="border-radius: 50%" />
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li class="dropdown-item">Settings</li>
                    <li class="dropdown-item">Activity Log</li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li class="dropdown-item" @click="logout">Logout</li>
                </ul>
            </li>
        </ul>
        <div class="logout-btn mx-2">
            <a class="nav-link position-relative" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown"
                aria-expanded="false">
                <i class="fa-solid fa-bell" style="font-size: large"></i>
                <!-- Notification count badge -->
                <span v-if="totalNotifications > 0"
                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    {{ totalNotifications }}
                    <span class="visually-hidden">unread messages</span>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown"
                style="background-color: #c7d1cb">
                <div v-for="item in notification" :key="item.id">
                    <li>{{ item.Status }}</li>
                    <li class="dropdown-item" v-if="item.Status === 'Pending'"
                        @click="router.push('/leave/supervisor-approval')">
                        {{ item.Full_Name }} Requested a Leave
                    </li>
                    <li class="dropdown-item" v-if="item.Status === 'Approved (SV)'"
                        @click="router.push('/leave/hr-approval')">
                        {{ item.Full_Name }} leave has been accepted by his
                        Supervisor.
                    </li>
                </div>
            </ul>
            <div class="mx-3" @click="logout">
                <i class="fa-solid fa-arrow-right-from-bracket" style="font-size: large"></i>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import api from "@/api";
import { useRouter } from "vue-router";
import Swal from "sweetalert2";

import { useStore } from "vuex";

const store = useStore();
const user = store.getters.getUser;

const router = useRouter();
const error = ref("");
const notification = ref([]);


// Compute the total number of notifications from both existing notifications and employee leaves
const totalNotifications = computed(() => {
    const notificationCount = notification.value.length;
    return notificationCount;
});

const getData = async () => {
    try {
        const responseNotification = await api.get("/get-notification");
        notification.value = responseNotification.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
    if (user.value === "logout") {
        logout();
    }
};

const logout = async () => {
    Swal.fire({
        title: "Do you want to Logout?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes",
    }).then(async (result) => {
        if (result.isConfirmed) {
            store.dispatch("removeToken", 0);
            localStorage.removeItem("token");
            try {
                await api.post("/logout");
            } catch (error) {
                console.error("Error logging out", error);
            }
            router.push({ name: "Login" });
        }
    });
};

onMounted(() => {
    getData();
    const sidebarToggle = document.querySelector("#sidebarToggle");
    const sidebar = document.querySelector("#layoutSidenav_nav");

    if (sidebarToggle) {
        sidebarToggle.addEventListener("click", (event) => {
            event.preventDefault();
            document.body.classList.toggle("sb-sidenav-toggled");
            localStorage.setItem("sb|sidebar-toggle", document.body.classList.contains("sb-sidenav-toggled"));
        });
    }

    document.addEventListener("click", (event) => {
        if (sidebar && !sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
            // If clicked outside of sidebar and toggle button, close sidebar
            if (document.body.classList.contains("sb-sidenav-toggled")) {
                document.body.classList.remove("sb-sidenav-toggled");
                localStorage.setItem("sb|sidebar-toggle", false);
            }
        }
    });

    // Restore sidebar state from localStorage
    if (localStorage.getItem("sb|sidebar-toggle") === "true") {
        document.body.classList.add("sb-sidenav-toggled");
    }
}
);
</script>
