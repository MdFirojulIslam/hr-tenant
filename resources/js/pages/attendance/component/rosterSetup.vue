<script setup>
import {
    ref,
    reactive,
    defineProps,
    getCurrentInstance,
    onMounted,
    watch,
} from "vue";
import Swal from "sweetalert2";
import api from "@/api";
import { useStore } from "vuex";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

// ----------------------------
// BASIC SETUP
// ----------------------------
const store = useStore();
const user = store.getters.getUser;

const props = defineProps({
    isOpen: Boolean,
    editData: Object,
});

console.log("Edit Data:", props.editData);

const error = ref([]);
const department = ref([]);
const employee = ref([]);
const emp_img = ref(null);
const selectedEmployee = ref(null);




const form = reactive({
    department: "",
    eid: "",
    effective_from: "",
    time_in: "",
    time_out: "",
    work_from: "office",
    override: false
});

const instance = getCurrentInstance();

// -------------------------
// FETCH DATA
// -------------------------
const getData = async () => {
    try {
        const departments = await api.get(`/department-list/${user.id}`);
        department.value = departments.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};



// FETCH EMPLOYEES
const getEmployee = async (deptID) => {
    if (!deptID) {
        employee.value = [];
        selectedEmployee.value = null;
        form.eid = "";
        emp_img.value = null;
        return;
    }
    try {
        const res = await api.get(`/emp/${deptID}`);
        employee.value = res.data;
        selectedEmployee.value = null;
        form.eid = "";
        emp_img.value = null;
    } catch (err) {
        console.error("Error fetching employees:", err);
    }
};



// FETCH EMPLOYEE IMAGE
const getEmployeeImg = async (eid) => {
    if (!eid) return;
    try {
        const res = await api.get(`/empimg/${eid}`);
        emp_img.value = res.data?.img_url || null;
    } catch (err) {
        console.error("Error fetching employee image:", err);
    }
};

// HANDLE EMPLOYEE SELECT
const onEmployeeSelect = (emp) => {
    if (!emp) {
        form.eid = "";
        emp_img.value = null;
        return;
    }
    form.eid = emp.id;
    getEmployeeImg(emp.id);
};

// -------------------------
// PREFILL FORM ON EDIT
// -------------------------

// -------------------------
// RESET FORM
// -------------------------
watch(
    () => props.editData,
    async (data) => {
        if (data) {
            form.department = data.department_id || "";

            await getEmployee(data.department_id);

            form.eid = data.employee_id || "";
            form.effective_from = data.effective_from || "";
            form.time_in = data.time_in || "";
            form.time_out = data.time_out || "";
            form.work_from = data.work_from || "office";

            selectedEmployee.value = employee.value.find(
                (emp) => emp.id == data.employee_id
            );

            getEmployeeImg(data.employee_id);
        } else {
            resetForm(); // 🔥 important
        }
    },
    { immediate: true }
);
const resetForm = () => {
    form.department = "";
    form.eid = "";
    form.effective_from = "";
    form.time_in = "";
    form.time_out = "";
    form.work_from = "office";
};

// -------------------------
// CLOSE MODAL
// -------------------------
const closeModal = () => {
    resetForm();
    instance.emit("close");
};

// -------------------------
// CREATE & UPDATE
// -------------------------
const create = async () => {
    try {
        const res = await api.post("/roster-setup", form);
        if (res.data.success) {
            Swal.fire({
                icon: "success",
                title: res.data.message,
                showConfirmButton: false,
                timer: 1500,
            });
            resetForm();
            instance.emit("refresh");
            closeModal();
        } else
            Swal.fire({
                icon: "error",
                title: res.data.message,
                showConfirmButton: true,
            });
    } catch (err) {
        console.error("Error creating adjustment:", err);
    }
};

const update = async () => {
    try {
        const res = await api.put(
            `/roster-setup/${props.editData.roster_id}`,
            form
        );
        if (res.data.success) {
            Swal.fire({
                icon: "success",
                title: res.data.message,
                showConfirmButton: false,
                timer: 1500,
            });
            resetForm();
            instance.emit("refresh");
            closeModal();
        } else
            Swal.fire({
                icon: "error",
                title: res.data.message,
                showConfirmButton: true,
            });
    } catch (err) {
        console.error("Error updating adjustment:", err);
    }
};

// SUBMIT
const submit = () => {
    if (props.editData) {
         update();
    } else {
        create();
    }
};

// -------------------------
// DRAG MODAL
// -------------------------
const enableDrag = () => {
    const dialog = document.querySelector(".modal-dialog");
    const header = document.querySelector(".modal-header");
    if (!dialog || !header) return;

    let isDragging = false,
        offsetX = 0,
        offsetY = 0,
        originalWidth = 0;

    header.style.cursor = "move";

    header.addEventListener("mousedown", (e) => {
        isDragging = true;
        originalWidth = dialog.offsetWidth;
        dialog.style.width = originalWidth + "px";

        const rect = dialog.getBoundingClientRect();
        dialog.style.position = "absolute";
        dialog.style.left = rect.left + "px";
        dialog.style.top = rect.top + "px";
        dialog.style.margin = 0;
        dialog.style.transform = "none";

        offsetX = e.clientX - rect.left;
        offsetY = e.clientY - rect.top;
    });

    document.addEventListener("mousemove", (e) => {
        if (!isDragging) return;
        dialog.style.left = e.clientX - offsetX + "px";
        dialog.style.top = e.clientY - offsetY + "px";
    });

    document.addEventListener("mouseup", () => {
        isDragging = false;
    });
};

// -------------------------
// ON MOUNT
// -------------------------
onMounted(() => {
    getData();
    enableDrag();
});
</script>

<template>
    <div v-if="isOpen" class="modal fade show d-block" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-header gradient text-white">
                    <h5 v-if="!editData" class="modal-title">
                        <i class="bi bi-sliders me-2"></i>
                        Roster Setup
                    </h5>
                    <h5 v-else class="modal-title">
                        <i class="bi bi-sliders me-2"></i>
                        Roster Setup Update
                    </h5>
                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        @click="closeModal"
                    ></button>
                </div>

                <!-- Body -->
                <div class="modal-body overflow-auto" style="max-height: 80vh">
                    <!-- Employee Info -->
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-light fw-bold">
                            EMPLOYEE INFORMATION
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-9">
                                    <!-- Department -->
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-3 d-flex">
                                            Department :
                                        </div>
                                        <div class="col-md-6">
                                            <select
                                                class="form-select border-color"
                                                v-model="form.department"
                                                @change="
                                                    getEmployee(form.department)
                                                "
                                            >
                                                <option value="" disabled>
                                                    Select Department
                                                </option>
                                                <option
                                                    v-for="dept in department"
                                                    :key="dept.department_id"
                                                    :value="
                                                        Number(
                                                            dept.department_id,
                                                        )
                                                    "
                                                >
                                                    {{ dept.department_name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Employee -->
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-3 d-flex">
                                            Employee :
                                        </div>
                                        <div class="col-md-6 position-relative">
                                            <Multiselect
                                                v-model="selectedEmployee"
                                                :options="employee"
                                                label="Full_Name"
                                                track-by="id"
                                                placeholder="Select or search employee"
                                                :searchable="true"
                                                :close-on-select="true"
                                                :allow-empty="true"
                                                @update:modelValue="
                                                    onEmployeeSelect
                                                "
                                                :clear-on-select="false"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Employee Image -->
                                <div class="col-md-3 text-center">
                                    <img
                                        :src="emp_img || ''"
                                        alt="Employee"
                                        class="rounded border"
                                        height="120"
                                        width="120"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Adjustment Details -->
                    <div v-if="!form.override" class="card mb-3 shadow-sm">
                        <div class="card-header bg-light fw-bold">
                            ROSTER DETAILS
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label"
                                        >Effective From</label
                                    >
                                    <input
                                        type="date"
                                        class="form-control border-color custom-input"
                                        v-model="form.effective_from"
                                        placeholder="Effective From"
                                    />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Time In</label>
                                    <input
                                        type="time"
                                        v-model="form.time_in"
                                        class="form-control border-color custom-input"
                                    />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Time Out</label>
                                    <input
                                        type="time"
                                        v-model="form.time_out"
                                        class="form-control border-color custom-input"
                                    />
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Work From</label>
                                    <select
                                        v-model="form.work_from"
                                        class="form-select border-color custom-input"
                                    >
                                        <option value="office">Office</option>
                                        <option value="home">Home</option>
                                    </Select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="editData" class="card mb-3 shadow-sm">
                        <div class="card-header bg-light fw-bold d-flex align-items-center gap-2">
                            <span>If Wants To Override Effective Date</span>
                            <input type="checkbox" v-model="form.override">
                            <label for="overrideCheck" class="mb-0">Yes</label>
                        </div>
                        <div v-if="form.override" class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label"
                                        >Effective From</label
                                    >
                                    <input
                                        type="date"
                                        class="form-control border-color custom-input"
                                        v-model="form.effective_from"
                                        placeholder="Effective From"
                                    />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Time In</label>
                                    <input
                                        type="time"
                                        v-model="form.time_in"
                                        class="form-control border-color custom-input"
                                    />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Time Out</label>
                                    <input
                                        type="time"
                                        v-model="form.time_out"
                                        class="form-control border-color custom-input"
                                    />
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Work From</label>
                                    <select
                                        v-model="form.work_from"
                                        class="form-select border-color custom-input"
                                    >
                                        <option value="office">Office</option>
                                        <option value="home">Home</option>
                                    </Select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button v-if="!editData" class="btn btn-success" @click="submit()">
                        <i class="bi bi-check-circle"></i> Submit
                    </button>
                    <button v-else class="btn btn-success" @click="submit()">
                        <i class="bi bi-check-circle"></i> Update
                    </button>
                    <button class="btn btn-secondary" @click="closeModal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-input {
    background: linear-gradient(
        to right,
        white 85%,
        #059223d5 85%,
        #02861fd5 100%
    );
}

.border-color {
    border: 1.8px solid #059223d5;
}

.gradient {
    background: linear-gradient(
        90deg,
        rgb(28, 105, 9) 27%,
        rgb(14, 119, 58) 100%,
        rgb(7, 134, 24) 91%
    );
}

.modal {
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content label,
.modal-content .card-header,
.modal-content option,
.modal-content select,
.modal-content input,
.modal-content textarea {
    text-transform: capitalize !important;
}
</style>
