<script setup>
import { ref, defineProps, computed, watch, getCurrentInstance } from "vue";
import Swal from "sweetalert2";
import api from "@/api";

const props = defineProps([
    "isOpen",
    "editStore",
    "updateinfo",
    "leaveInformation",
    "leaveType",
]);

const instance = getCurrentInstance();

const error = ref([]);
const existMsg = ref("");
const fileSizeWarning = ref(false);
const loading = ref(false);

const form = ref({
    department_id: "",
    employee_id: "",
    from_date: "",
    to_date: "",
    purpose: "",
    Leave_Type_Id: "",
    Status: "",
    file: null,
});

// Pre-fill employee ID
watch(
    () => props.leaveInformation,
    (newVal) => {
        if (newVal && newVal.employee) {
            form.value.employee_id = newVal.employee.id;
        }
    },
    { immediate: true }
);

const totalDays = computed(() => {
    if (form.value.from_date && form.value.to_date) {
        const start = new Date(form.value.from_date);
        const end = new Date(form.value.to_date);
        const diff = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
        return diff;
    }
    return 0;
});

const getImage = (e) => {
    const file = e.target.files[0];
    if (file && file.size > 512000) {
        fileSizeWarning.value = true;
        e.target.value = null;
    } else {
        fileSizeWarning.value = false;
        form.value.file = file;
    }
};

const closeModal = () => {
    instance.emit("modal-close");
};

const submit = async () => {
    form.value.Status = "Pending";
    loading.value = true;

    const selectedLeave = props.leaveType.find(
        (l) => l.id === form.value.Leave_Type_Id
    );

    if (
        selectedLeave &&
        selectedLeave.Name === "Sick" &&
        totalDays.value >= 3 &&
        !form.value.file
    ) {
        Swal.fire({
            icon: "error",
            title: "Validation Error",
            text: "For sick leave of 3 days or more, you must attach a document.",
        });
        loading.value = false;
        return;
    }

    const formData = new FormData();
    for (const key in form.value) {
        if (form.value.hasOwnProperty(key)) {
            formData.append(key, form.value[key]);
        }
    }

    try {
        const response = await api.post("/leave", formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        existMsg.value = response.data.message;

        if (response.data.success) {
            Swal.fire({
                position: "middle",
                icon: "success",
                title: "Application has been saved",
                showConfirmButton: false,
                timer: 1500,
            });
            await api.get(`/leave-req-mail/${response.data.id}`);
            closeModal();
        }
    } catch (err) {
        error.value = err.response?.data?.errors || [];
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div v-if="isOpen" class="modal-mask">
        <div class="card modal-card">
            <div class="card-body">
                <div class="card mb-2">
                    <div class="card-header mb-1 bg-grey">
                        <h5 class="mb-0">
                            {{ updateinfo === "Save" ? "New Leave Application" : "Update Leave Application" }}
                        </h5>
                    </div>

                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <!-- Employee Info -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Employee ID</label>
                                    <input type="text" class="form-control"
                                        :value="props.leaveInformation?.employee?.employee_id || ''" readonly />
                                </div>
                                <div class="col-md-6">
                                    <label>Employee Name</label>
                                    <input type="text" class="form-control"
                                        :value="props.leaveInformation?.employee?.Full_Name || ''" readonly />
                                </div>
                            </div>

                            <!-- Dates -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>From Date</label>
                                    <input type="date" class="form-control" v-model="form.from_date" />
                                </div>
                                <div class="col-md-6">
                                    <label>To Date</label>
                                    <input type="date" class="form-control" v-model="form.to_date" />
                                </div>
                            </div>

                            <div v-if="form.from_date && form.to_date" class="mb-3">
                                <label>Total Duration: {{ totalDays }} days</label>
                            </div>

                            <!-- Purpose -->
                            <div class="mb-3">
                                <label>Purpose / Reason</label>
                                <textarea class="form-control" v-model="form.purpose"></textarea>
                            </div>

                            <!-- Leave Type & File -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Leave Type</label>
                                    <select class="form-control" v-model="form.Leave_Type_Id">
                                        <option disabled selected>Select</option>
                                        <option v-for="l in props.leaveType" :key="l.id" :value="l.id">
                                            {{ l.Name }} Leave
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>Documents (If Sick Leave ≥ 3 days)</label>
                                    <input type="file" class="form-control" @change="getImage" />
                                    <div v-if="fileSizeWarning" class="text-danger mt-1">
                                        File size exceeds 500 KB.
                                    </div>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="d-flex justify-content-between mt-3">
                                <button type="submit" class="btn btn-success" :disabled="loading">
                                    {{ loading ? "Submitting..." : "Save" }}
                                </button>
                                <button type="button" class="btn btn-danger" @click="closeModal">
                                    Cancel
                                </button>
                            </div>
                            <span class="text-danger ms-2">{{ existMsg }}</span>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal-mask {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 2000;
}

.modal-card {
    background: #fff;
    border-radius: 8px;
    width: 90%;
    max-width: 800px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.5);
}
</style>
