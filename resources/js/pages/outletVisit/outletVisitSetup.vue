<script setup>
import { ref, computed, watch, onMounted } from "vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";
import Label from "@/pages/components/label/Label.vue";
import api from "@/api";
import Swal from "sweetalert2";

const towns = ref([]);
const employees = ref([]);
const outlets = ref([]);
const assignments = ref([]);
const outletMeta = ref({ current_page: 1, last_page: 1, total: 0 });

const selectedTown = ref(null);
const selectedEmployee = ref(null);
const selectedYear = ref(new Date().getFullYear());
const selectedMonth = ref(new Date().getMonth() + 1);

const outletSearch = ref("");
const assignWeek = ref(1);
const assignActivity = ref("visit");
const selectedOutlet = ref(null);

const loadingTowns = ref(false);
const loadingEmployees = ref(false);
const loadingOutlets = ref(false);
const loadingAssignments = ref(false);
const saving = ref(false);

const years = computed(() => {
    const y = new Date().getFullYear();
    return [y - 1, y, y + 1];
});

const months = [
    { id: 1, name: "January" },
    { id: 2, name: "February" },
    { id: 3, name: "March" },
    { id: 4, name: "April" },
    { id: 5, name: "May" },
    { id: 6, name: "June" },
    { id: 7, name: "July" },
    { id: 8, name: "August" },
    { id: 9, name: "September" },
    { id: 10, name: "October" },
    { id: 11, name: "November" },
    { id: 12, name: "December" },
];

const weekSummary = computed(() => {
    const base = { 1: 0, 2: 0, 3: 0, 4: 0, delivery: 0 };
    for (const a of assignments.value) {
        if (a.activity_type === "delivery") base.delivery += 1;
        else if (base[a.week_no] !== undefined) base[a.week_no] += 1;
    }
    return base;
});

const fetchTowns = async () => {
    loadingTowns.value = true;
    try {
        const { data } = await api.get("outlet-visited/towns");
        towns.value = data.data || [];
    } catch (e) {
        Swal.fire(
            "Error",
            e?.response?.data?.message || "Failed to load towns",
            "error",
        );
    } finally {
        loadingTowns.value = false;
    }
};

const fetchEmployees = async () => {
    employees.value = [];
    selectedEmployee.value = null;
    if (!selectedTown.value) return;
    loadingEmployees.value = true;
    try {
        const { data } = await api.get("outlet-visited/employees", {
            params: { town_id: selectedTown.value.town_id },
        });
        employees.value = data.data || [];
    } catch (e) {
        Swal.fire(
            "Error",
            e?.response?.data?.message || "Failed to load employees",
            "error",
        );
    } finally {
        loadingEmployees.value = false;
    }
};

const fetchOutlets = async (page = 1) => {
    outlets.value = [];
    selectedOutlet.value = null;
    if (!selectedTown.value) return;
    loadingOutlets.value = true;
    try {
        const { data } = await api.get("outlet-visited/outlets", {
            params: {
                town_id: selectedTown.value.town_id,
                q: outletSearch.value || undefined,
                page,
                per_page: 25,
                active: 1,
            },
        });
        outlets.value = data.data || [];
        outletMeta.value = data.meta || outletMeta.value;
    } catch (e) {
        Swal.fire(
            "Error",
            e?.response?.data?.message || "Failed to load outlets from DMS",
            "error",
        );
    } finally {
        loadingOutlets.value = false;
    }
};

const fetchAssignments = async () => {
    assignments.value = [];
    if (!selectedTown.value || !selectedEmployee.value) return;
    loadingAssignments.value = true;
    try {
        const { data } = await api.get("outlet-visited/assignments", {
            params: {
                town_id: selectedTown.value.town_id,
                employee_id: selectedEmployee.value.employee_id,
                year: selectedYear.value,
                month: selectedMonth.value,
            },
        });
        assignments.value = data.data || [];
    } catch (e) {
        Swal.fire(
            "Error",
            e?.response?.data?.message || "Failed to load assignments",
            "error",
        );
    } finally {
        loadingAssignments.value = false;
    }
};

const addAssignment = () => {
    if (!selectedOutlet.value) {
        Swal.fire(
            "Select outlet",
            "Choose an outlet before assigning.",
            "warning",
        );
        return;
    }
    const activity = assignActivity.value;
    const week = activity === "delivery" ? 0 : Number(assignWeek.value);
    const exists = assignments.value.some(
        (a) =>
            a.outlets_id === selectedOutlet.value.outlet_id &&
            a.activity_type === activity &&
            Number(a.week_no) === week,
    );
    if (exists) {
        Swal.fire(
            "Duplicate",
            "That outlet is already assigned for this week/activity.",
            "info",
        );
        return;
    }
    assignments.value.push({
        outlets_id: selectedOutlet.value.outlet_id,
        outlet_code: selectedOutlet.value.outlet_code,
        outlet_name: selectedOutlet.value.outlet_name,
        activity_type: activity,
        week_no: week,
        distributor_id: selectedOutlet.value.distributor_id,
    });
};

const removeAssignment = (idx) => {
    assignments.value.splice(idx, 1);
};

const saveChanges = async () => {
    if (!selectedTown.value || !selectedEmployee.value) {
        Swal.fire(
            "Missing selection",
            "Select town and employee first.",
            "warning",
        );
        return;
    }
    saving.value = true;
    try {
        const { data } = await api.post("outlet-visited/assignments", {
            town_id: selectedTown.value.town_id,
            employee_id: selectedEmployee.value.employee_id,
            year: selectedYear.value,
            month: selectedMonth.value,
            items: assignments.value.map((a) => ({
                outlets_id: a.outlets_id,
                outlet_code: a.outlet_code,
                outlet_name: a.outlet_name,
                activity_type: a.activity_type,
                week_no: a.week_no,
            })),
        });
        assignments.value = data.data || [];
        Swal.fire({
            icon: "success",
            title: "Saved",
            text: data.message || "Assignments saved.",
            timer: 1600,
            showConfirmButton: false,
        });
    } catch (e) {
        Swal.fire(
            "Error",
            e?.response?.data?.message || "Save failed",
            "error",
        );
    } finally {
        saving.value = false;
    }
};

watch(selectedTown, async () => {
    await fetchEmployees();
    await fetchOutlets(1);
    await fetchAssignments();
});

watch(selectedEmployee, fetchAssignments);
watch([selectedYear, selectedMonth], fetchAssignments);

onMounted(fetchTowns);
</script>

<template>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Outlet Visited Setup</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item">Setup</li>
            <li class="breadcrumb-item active">Outlet Visited</li>
        </ol>
        <p class="text-muted mb-4">
            Assign outlets by town → employee → month → week / delivery. Towns
            map via DMS Town Id (distributor), never by name.
        </p>

        <div class="card mb-4">
            <div class="card-header">Selection</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <Label name="Town" />
                        <Multiselect
                            v-model="selectedTown"
                            :options="towns"
                            :loading="loadingTowns"
                            label="town_name"
                            track-by="town_id"
                            placeholder="Select town"
                        />
                        <small v-if="selectedTown" class="text-muted">
                            DMS distributor id:
                            {{ selectedTown.distributor_id }}
                        </small>
                    </div>
                    <div class="col-md-4">
                        <Label name="Employee" />
                        <Multiselect
                            v-model="selectedEmployee"
                            :options="employees"
                            :loading="loadingEmployees"
                            :disabled="!selectedTown"
                            label="full_name"
                            track-by="employee_id"
                            placeholder="Select employee"
                        />
                    </div>
                    <div class="col-md-2">
                        <Label name="Year" />
                        <select
                            v-model.number="selectedYear"
                            class="form-select"
                        >
                            <option v-for="y in years" :key="y" :value="y">
                                {{ y }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <Label name="Month" />
                        <select
                            v-model.number="selectedMonth"
                            class="form-select"
                        >
                            <option
                                v-for="m in months"
                                :key="m.id"
                                :value="m.id"
                            >
                                {{ m.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive mt-4" v-if="selectedEmployee">
                    <table
                        class="table table-sm table-bordered text-center mb-0"
                    >
                        <thead class="table-light">
                            <tr>
                                <th></th>
                                <th>Week 1</th>
                                <th>Week 2</th>
                                <th>Week 3</th>
                                <th>Week 4</th>
                                <th>Delivery</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-start">Assigned count</td>
                                <td>{{ weekSummary[1] }}</td>
                                <td>{{ weekSummary[2] }}</td>
                                <td>{{ weekSummary[3] }}</td>
                                <td>{{ weekSummary[4] }}</td>
                                <td>{{ weekSummary.delivery }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-4" v-if="selectedTown">
            <div
                class="card-header d-flex justify-content-between align-items-center"
            >
                <span>Assign outlet</span>
                <button
                    class="btn btn-sm btn-outline-secondary"
                    type="button"
                    :disabled="loadingOutlets"
                    @click="fetchOutlets(outletMeta.current_page || 1)"
                >
                    Refresh outlets
                </button>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <Label name="Search outlets" />
                        <div class="input-group">
                            <input
                                v-model="outletSearch"
                                type="text"
                                class="form-control"
                                placeholder="Code / name / address"
                                @keyup.enter="fetchOutlets(1)"
                            />
                            <button
                                class="btn btn-primary"
                                type="button"
                                @click="fetchOutlets(1)"
                            >
                                Search
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <Label name="Outlet" />
                        <Multiselect
                            v-model="selectedOutlet"
                            :options="outlets"
                            :loading="loadingOutlets"
                            :custom-label="
                                (o) =>
                                    `${o.outlet_code || ''} — ${o.outlet_name || ''}`
                            "
                            track-by="outlet_id"
                            placeholder="Select outlet"
                        />
                        <small class="text-muted">
                            Page {{ outletMeta.current_page }} /
                            {{ outletMeta.last_page }} ({{ outletMeta.total }}
                            total)
                        </small>
                    </div>
                    <div class="col-md-2">
                        <Label name="Activity" />
                        <select v-model="assignActivity" class="form-select">
                            <option value="visit">Visit</option>
                            <option value="delivery">Delivery</option>
                        </select>
                    </div>
                    <div class="col-md-1" v-if="assignActivity === 'visit'">
                        <Label name="Week" />
                        <select v-model.number="assignWeek" class="form-select">
                            <option :value="1">1</option>
                            <option :value="2">2</option>
                            <option :value="3">3</option>
                            <option :value="4">4</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button
                            class="btn btn-success w-100"
                            type="button"
                            @click="addAssignment"
                        >
                            + Add
                        </button>
                    </div>
                </div>
                <div class="mt-2" v-if="outletMeta.last_page > 1">
                    <button
                        class="btn btn-sm btn-outline-primary me-2"
                        type="button"
                        :disabled="
                            outletMeta.current_page <= 1 || loadingOutlets
                        "
                        @click="fetchOutlets(outletMeta.current_page - 1)"
                    >
                        Prev
                    </button>
                    <button
                        class="btn btn-sm btn-outline-primary"
                        type="button"
                        :disabled="
                            outletMeta.current_page >= outletMeta.last_page ||
                            loadingOutlets
                        "
                        @click="fetchOutlets(outletMeta.current_page + 1)"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div
                class="card-header d-flex justify-content-between align-items-center"
            >
                <span>Assigned outlets</span>
                <button
                    class="btn btn-primary"
                    type="button"
                    :disabled="saving || !selectedEmployee"
                    @click="saveChanges"
                >
                    {{ saving ? "Saving…" : "Save Changes" }}
                </button>
            </div>
            <div class="card-body">
                <div v-if="loadingAssignments" class="text-muted">Loading…</div>
                <div v-else-if="!assignments.length" class="text-muted">
                    No assignments for this selection.
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Outlet Code</th>
                                <th>Outlet</th>
                                <th>Week / Activity</th>
                                <th style="width: 100px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, idx) in assignments" :key="idx">
                                <td>{{ row.outlet_code || "—" }}</td>
                                <td>{{ row.outlet_name || "—" }}</td>
                                <td>
                                    <span
                                        v-if="row.activity_type === 'delivery'"
                                        >Delivery</span
                                    >
                                    <span v-else>Week {{ row.week_no }}</span>
                                </td>
                                <td>
                                    <button
                                        class="btn btn-sm btn-outline-danger"
                                        type="button"
                                        @click="removeAssignment(idx)"
                                    >
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
