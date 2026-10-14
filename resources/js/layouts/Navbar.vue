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
    <nav class="sb-topnav navbar navbar-expand navbar-minimal">
        <div class="d-flex align-items-center w-100 navbar-content">
            <button
                class="nav-toggle d-lg-none"
                type="button"
                @click="toggleSidebar"
                aria-label="Toggle navigation"
            >
                <i class="fas fa-bars"></i>
            </button>

            <div class="ms-auto nav-profile">
                <div
                    class="ms-auto d-flex align-items-center ms-md-0 me-3 me-lg-4"
                >
                    <div
                        class="nav-item dropdown position-relative p-2 notification-item"
                    >
                        <a
                            class="nav-link position-relative"
                            id="navbarDropdown"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            <i class="fa-solid fa-bell notification-bell"></i>

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
.navbar-minimal {
    background-color: transparent;
    flex-shrink: 0;
}

.nav-toggle {
    background: transparent;
    border: none;
    color: #242424;
    font-size: 20px;
    line-height: 1;
    padding: 6px 10px;
    margin-left: 4px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.15s ease-in-out;
}

.nav-toggle:hover {
    background-color: #f0f0f0;
}

.notification-item {
    margin-right: 8px;
}

.notification-bell {
    font-size: 20px;
    color: #0078d4;
    transition: color 0.15s ease-in-out;
}

.notification-bell:hover {
    color: #005a9e;
}

@media (max-width: 768px) {
    .nav-profile {
        margin-left: 10px;
    }
}
</style>
