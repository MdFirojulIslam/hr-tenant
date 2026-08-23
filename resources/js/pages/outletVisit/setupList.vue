<script>
import axios from "axios";
import api from "@/api";
import { useStore } from "vuex";
import BaseDept from "@/pages/components/input_field/BaseDept.vue";
import BaseDeptEmp from "@/pages/components/input_field/BaseDeptEmp.vue";

const store = useStore();
const user = store.getters.getUser;
const userID = user ? user.id : null;

const baseAdapter = axios.getAdapter(api.defaults.adapter);
const outletCodeInflight = new Map();
let outletCodeDedupeInstalled = false;

const installOutletCodeDedupe = () => {
    if (outletCodeDedupeInstalled) return;
    outletCodeDedupeInstalled = true;

    api.interceptors.request.use((config) => {
        const method = String(config.method || "get").toLowerCase();
        const url = String(config.url || "");

        if (method !== "get" || !url.includes("/dms-outlet-codes/")) {
            return config;
        }

        config.adapter = (requestConfig) => {
            const key = api.getUri(requestConfig);

            if (outletCodeInflight.has(key)) {
                return outletCodeInflight.get(key);
            }

            const pending = baseAdapter(requestConfig).finally(() => {
                outletCodeInflight.delete(key);
            });

            outletCodeInflight.set(key, pending);
            return pending;
        };

        return config;
    });
};

installOutletCodeDedupe();
</script>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import api from "@/api";
import Swal from "sweetalert2";
import { getDmsOutletCode } from "@/pages/services/apiServices";

const router = useRouter();

const setups = ref([]);
const loading = ref(false);

const loadSetups = async () => {
    loading.value = true;
    try {
        const res = await api.get("/outlet-visit-setup-list");
        setups.value = res.data?.data ?? [];
    } catch (err) {
        const message =
            err.response?.data?.message || "Failed to load setup list.";
        Swal.fire("Error", message, "error");
    } finally {
        loading.value = false;
    }
};

onMounted(loadSetups);

const goToSingleWeekSetup = () => {
    router.push({ name: "SingleWeekSetup" });
};

const viewSetup = (row) => {
    router.push({
        name: "OutletVisitSetup",
        query: {
            employee_id: row.employee_id,
            distributor_id: row.distributor_id,
            mode: "view",
        },
    });
};

const editSetup = (row) => {
    getDmsOutletCode(row.distributor_id).catch(() => {});
    router.push({
        name: "OutletVisitSetup",
        query: {
            employee_id: row.employee_id,
            distributor_id: row.distributor_id,
        },
    });
};
</script>

<template>
    <div class="border rounded container-fluid p-3">
        <!-- PAGE HEADER -->
        <div
            class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap"
        >
            <h2 class="header-design p-1 d-flex align-items-center gap-2">
                <i class="fa-solid fa-store icon-design"></i>
                Outlet Visit Setup List
            </h2>

            <button
                type="button"
                class="btn btn-sm btn-success px-3"
                @click="goToSingleWeekSetup"
            >
                <i class="fa-solid fa-calendar-day me-1"></i>
                Single Week Setup
            </button>
        </div>
        <div>
            <div class="">
                <BaseDept
                    v-model:departmentID="selectedDepartmentId"
                    :userID="userID"
                />
            </div>
            <div class="">
                <!-- <EmployeeName
                                v-model:employee_id="selectedEmployeeId"
                            /> -->
                <BaseDeptEmp
                    v-model:employeeID="selectedEmployeeId"
                    :deptID="getId(selectedDepartmentId)"
                    :isEdit="false"
                />
            </div>
        </div>

        <!-- TABLE -->
        <div class="table-responsive shadow-sm table-wrapper">
            <table
                class="table table-bordered table-hover table-sm align-middle"
            >
                <thead class="table-light">
                    <tr>
                        <th>Employee Name</th>
                        <th>Department Name</th>
                        <th class="text-center">Distributor Name</th>
                        <th class="text-center">Completed Setup</th>
                        <th class="text-center">Unique Outlet Setup</th>
                        <th class="text-center">Total Outlet</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in setups"
                        :key="row.distributor_id + '_' + row.employee_id"
                    >
                        <td>{{ row.employee_name }}</td>
                        <td>{{ row.department_name }}</td>
                        <td class="text-center">{{ row.distributor_name }}</td>
                        <td class="text-center">
                            {{ row.completed_weeks }} / {{ row.total_weeks }}
                        </td>
                        <td class="text-center">
                            {{ row.unique_outlet_count }}
                        </td>
                        <td class="text-center">
                            {{ row.total_outlet_count }}
                        </td>
                        <td class="text-center">
                            <button
                                type="button"
                                class="btn btn-xs btn-outline-primary btn-icon me-1"
                                title="View"
                                @click="viewSetup(row)"
                            >
                                <i class="fa-solid fa-eye"></i>
                                View
                            </button>
                            <button
                                type="button"
                                class="btn btn-xs btn-outline-success btn-icon"
                                title="Edit"
                                @click="editSetup(row)"
                            >
                                <i class="fa-solid fa-pen"></i>
                                Edit
                            </button>
                        </td>
                    </tr>

                    <tr v-if="!loading && setups.length === 0">
                        <td colspan="5" class="text-center py-3">
                            No outlet visit setup found.
                        </td>
                    </tr>

                    <tr v-if="loading">
                        <td colspan="5" class="text-center py-3">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<style scoped>
.header-design {
    letter-spacing: 0.5px;
    font-weight: 700;
    font-size: 18px;
    color: #212529;
    margin: 0;
}

.icon-design {
    color: #059223d5;
    font-size: 16px;
}

.table thead th {
    background-color: #4e4e4e;
    font-weight: 700;
    color: #ffffff;
    font-size: 12px;
    white-space: nowrap;
}

.table tbody tr:hover > td {
    background-color: #717c73c7 !important;
}

.table-wrapper {
    overflow-x: auto;
}

.table-wrapper table {
    min-width: 720px;
}

.btn-xs {
    font-size: 11px;
    border-radius: 3px;
}

.btn-icon {
    padding: 3px 8px;
    line-height: 1.2;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    justify-content: center;
}
</style>
