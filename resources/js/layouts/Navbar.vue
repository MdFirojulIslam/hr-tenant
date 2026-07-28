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

const toggleSidebar = () => {
    store.commit("TOGGLE_SIDEBAR");
};

const goToSupervisorApproval = (item) => {
    router.push({
        path: "/leave/supervisor-approval",
        query: {
            full_name: item.Full_Name,
            leave_type: item.Leave_Type,
            department_name: item.department_name,
            leave_id: item.leave_id,
            purpose: item.purpose,
            status: item.status,
        },
    });
};

const goToHrApproval = (item) => {
    router.push({
        path: "/leave/hr-approval",
        query: {
            full_name: item.Full_Name,
            leave_type: item.Leave_Type,
            department_name: item.department_name,
            leave_id: item.leave_id,
            purpose: item.purpose,
            status: item.status,
        },
    });
};

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
});
</script>

<template>
    <nav class="sb-topnav navbar navbar-expand navbar-color">
        <div class="d-flex align-items-center w-100 navbar-content">
            <div class="d-flex align-items-center navbar-logo">
                <div>
                    <img
                        src="/public/storage/uploads/1718087744_artisan.png"
                        height="35px"
                        width="35px"
                        class="mx-3"
                    />
                </div>
                <h3 class="text-white mb-0">ARTISAN HRM</h3>
            </div>

            <div class="ms-5">
                <button
                    class="btn btn-link btn-sm text-white"
                    id="sidebarToggle"
                    @click="toggleSidebar"
                >
                    <i class="fas fa-bars fa-2x"></i>
                </button>
            </div>

            <div class="ms-auto nav-profile">
                <div
                    class="ms-auto d-flex align-items-center ms-md-0 me-3 me-lg-4"
                >
                    <div class="nav-item dropdown position-relative p-2">
                        <a
                            class="nav-link position-relative"
                            id="navbarDropdown"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            <i
                                class="fa-solid fa-bell"
                                style="font-size: large; color: white"
                            ></i>

                            <span
                                v-if="totalNotifications > 0"
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            >
                                {{ totalNotifications }}
                                <span class="visually-hidden"
                                    >unread messages</span
                                >
                            </span>
                        </a>
                        <ul
                            class="dropdown-menu dropdown-menu-end"
                            aria-labelledby="navbarDropdown"
                            style="background-color: #c7d1cb"
                        >
                            <div v-for="item in notification" :key="item.id">
                                <li
                                    class="dropdown-item"
                                    v-if="item.status === 'Pending'"
                                    @click="goToSupervisorApproval(item)"
                                >
                                    {{ item.Full_Name }} Requested a Leave
                                </li>
                                <li
                                    class="dropdown-item"
                                    v-if="item.status === 'Approved (SV)'"
                                    @click="goToHrApproval(item)"
                                >
                                    {{ item.Full_Name }} leave has been accepted
                                    by his Supervisor.
                                </li>
                            </div>
                        </ul>
                    </div>
                    <div class="nav-item dropdown nav-profile">
                        <a
                            class="nav-link dropdown-toggle"
                            id="userDropdown"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            <span
                                class="text-white fs-4 m-3 nav-profile-name"
                                >{{ user.Full_Name }}</span
                            >
                            <img
                                :src="user.img_url"
                                alt="User Image"
                                height="40px"
                                width="40px"
                                style="border-radius: 50%"
                            />
                        </a>
                        <ul
                            class="dropdown-menu dropdown-menu-end"
                            aria-labelledby="userDropdown"
                        >
                            <li class="dropdown-item">Settings</li>
                            <li class="dropdown-item">Activity Log</li>
                            <li>
                                <hr class="dropdown-divider" />
                            </li>
                            <li class="dropdown-item" @click="logout">
                                Logout
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</template>

<style scoped>
.navbar-color {
    /* background-color: rgb(39, 106, 70); */
    /* background-color: #198754; */
    background-color: #212529;
    /* border-bottom: 0.1px solid #fbfffd; */
}
@media (max-width: 768px) {
    .navbar-logo {
        display: none !important;
    }
    /* .navbar-content{


   } */
    .nav-profile-name {
        display: none;
    }
    .nav-profile {
        margin-left: 10px;
    }
}
</style>
