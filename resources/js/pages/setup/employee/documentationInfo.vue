<script setup>
import { ref, computed, onMounted } from "vue";
import { useStore } from "vuex";
import { useRoute } from "vue-router";
import Swal from "sweetalert2";
import api from "@/api";

const route = useRoute();
const store = useStore();
const empId = parseInt(route.params.id);
const eid = empId || store.state.employeeId;

const loading = ref(false);
const saving = ref(false);
const existingRecord = ref(false);
const error = ref([]);
const employeeName = ref("");

const documentationFields = ref({
    job_circular: "",
    interview_evalation: "",
    appointment_letter: "",
    updated_resume: "",
    nid_passport: "",
    academic_cert: "",
    post_graduate_cert: "",
    prev_cmp_exp_letter: "",
    prev_cmp_release_letter: "",
    prev_cmp_salary_cert: "",
    recent_passport_pic: "",
    two_emegency_info: "",
    nid_verified: "",
    job_description: "",
    joining_letter: "",
    kye_form: "",
    emp_declation_letter: "",
    pa_pp_address: "",
});

const requirements = [
    { key: "job_circular", label: "Was there any Job circular", hasNA: true },
    {
        key: "interview_evalation",
        label: "Interview Evaluation/Interview Notes",
        hasNA: true,
    },
    { key: "appointment_letter", label: "Appointment Letter", hasNA: false },
    {
        key: "updated_resume",
        label: "Updated Resume/Curriculum Vitae",
        hasNA: false,
    },
    {
        key: "nid_passport",
        label: "Copy of National ID (NID) or Passport",
        hasNA: false,
    },
    { key: "academic_cert", label: "Academic Certificates", hasNA: false },
    {
        key: "post_graduate_cert",
        label: "Graduation/Postgraduate Certificates",
        hasNA: false,
    },
    {
        key: "prev_cmp_exp_letter",
        label: "Experience Letters of Previous Employment",
        hasNA: false,
    },
    {
        key: "prev_cmp_release_letter",
        label: "Release Letters of Previous Company",
        hasNA: false,
    },
    {
        key: "prev_cmp_salary_cert",
        label: "Salary Certificate Previous Company",
        hasNA: false,
    },
    {
        key: "recent_passport_pic",
        label: "Recent Passport-size Photograph",
        hasNA: false,
    },
    {
        key: "two_emegency_info",
        label: "Two Emergency Contact Information",
        hasNA: false,
    },
    { key: "nid_verified", label: "Do you verify NID?", hasNA: false },
    { key: "job_description", label: "Job Description", hasNA: false },
    { key: "joining_letter", label: "Joining Letter", hasNA: false },
    { key: "kye_form", label: "Know Your Employee (KYE) Form", hasNA: false },
    {
        key: "emp_declation_letter",
        label: "Employee declaration Letter",
        hasNA: false,
    },
    {
        key: "pa_pp_address",
        label: "Present address and permanent address declaration",
        hasNA: false,
    },
];

const TOTAL = 18;

const completedCount = computed(() => {
    let count = 0;
    for (const req of requirements) {
        const val = documentationFields.value[req.key];
        if (val === "Yes" || val === "Not Applicable") {
            count++;
        }
    }
    return count;
});

const incompleteCount = computed(() => TOTAL - completedCount.value);

const completedPercent = computed(() => {
    return parseFloat(((completedCount.value / TOTAL) * 100).toFixed(2));
});

const incompletePercent = computed(() => {
    return parseFloat(((incompleteCount.value / TOTAL) * 100).toFixed(2));
});

const leftColumn = computed(() => requirements.slice(0, 9));
const rightColumn = computed(() => requirements.slice(9, 18));

const getStatusClass = (key) => {
    const val = documentationFields.value[key];
    if (val === "Yes") return "status-yes";
    if (val === "No") return "status-no";
    if (val === "Not Applicable") return "status-na";
    return "status-empty";
};

const getData = async () => {
    if (!eid) return;
    loading.value = true;
    try {
        const response = await api.get(`/get-documentation-info`, {
            params: { employee_id: eid },
        });
        if (response.data && response.data.message === "Empty") {
            existingRecord.value = false;
        } else if (response.data && response.data.id) {
            existingRecord.value = true;
            for (const key of Object.keys(documentationFields.value)) {
                documentationFields.value[key] = response.data[key] || "";
            }
        }
    } catch (err) {
        console.error("Error fetching documentation info:", err);
    } finally {
        loading.value = false;
    }
};

const fetchEmployeeName = async () => {
    if (!eid) return;
    try {
        const response = await api.get(`/employee/${eid}/edit`);
        if (response.data && response.data.Full_Name) {
            employeeName.value = response.data.Full_Name;
        }
    } catch (err) {
        console.error("Error fetching employee name:", err);
    }
};

const submitForm = async () => {
    try {
        const result = await Swal.fire({
            title: "Are you sure?",
            text: "Do you want to submit this form?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes",
            cancelButtonText: "No",
            reverseButtons: true,
        });

        if (result.isConfirmed) {
            saving.value = true;
            const payload = {
                employee_id: eid,
                ...documentationFields.value,
            };
            const response = await api.post("/get-documentation-info", payload);
            if (response.data.success) {
                existingRecord.value = true;
                Swal.fire({
                    position: "middle",
                    icon: "success",
                    title: "Information has been saved",
                    showConfirmButton: false,
                    timer: 1500,
                });
            }
        }
    } catch (err) {
        if (err.response && err.response.data && err.response.data.errors) {
            error.value = err.response.data.errors;
        } else if (err.response && err.response.data) {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: err.response.data.message || "Something went wrong",
            });
        }
    } finally {
        saving.value = false;
    }
};

const updateForm = async () => {
    try {
        const result = await Swal.fire({
            title: "Are you sure?",
            text: "Do you want to update the information?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes",
            cancelButtonText: "No",
            reverseButtons: true,
        });

        if (result.isConfirmed) {
            saving.value = true;
            const payload = {
                ...documentationFields.value,
            };
            const response = await api.put(
                `/get-documentation-info/${eid}`,
                payload,
            );
            if (response.data.success) {
                Swal.fire({
                    position: "middle",
                    icon: "success",
                    title: "Information has been Updated",
                    showConfirmButton: false,
                    timer: 1500,
                });
            }
        }
    } catch (err) {
        if (err.response && err.response.data && err.response.data.errors) {
            error.value = err.response.data.errors;
        } else if (err.response && err.response.data) {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: err.response.data.message || "Something went wrong",
            });
        }
    } finally {
        saving.value = false;
    }
};

const submit = () => {
    if (existingRecord.value) {
        updateForm();
    } else {
        submitForm();
    }
};

onMounted(() => {
    getData();
    fetchEmployeeName();
});
</script>

<template>
    <div class="card mb-3">
        <div class="card-body">
            <div class="mt-3">
                <div class="doc-header mb-4">
                    <h2 class="fs-4 mb-1">
                        Employee Documentation Information
                    </h2>
                    <p class="doc-employee-name mb-0" v-if="employeeName">
                        Employee: {{ employeeName }}
                    </p>
                </div>

                <div v-if="loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading documentation data...</p>
                </div>

                <div v-else>
                    <div class="summary-section mb-4">
                        <h5 class="summary-title">Documentation Summary</h5>

                        <div class="summary-stats">
                            <div class="stat-card stat-total">
                                <div class="stat-label">Total Completion</div>
                                <div class="stat-value">
                                    {{ completedPercent }}%
                                </div>
                            </div>
                            <div class="stat-card stat-completed">
                                <div class="stat-label">Completed</div>
                                <div class="stat-value">
                                    {{ completedCount }} / {{ TOTAL }}
                                </div>
                            </div>
                            <div class="stat-card stat-incomplete">
                                <div class="stat-label">Incomplete</div>
                                <div class="stat-value">
                                    {{ incompleteCount }} / {{ TOTAL }}
                                </div>
                            </div>
                        </div>

                        <div class="progress-pair">
                            <div class="progress-item">
                                <div class="progress-label-row">
                                    <span class="progress-label-text"
                                        >Completed</span
                                    >
                                    <span
                                        class="progress-label-pct completed-pct"
                                        >{{ completedPercent }}%</span
                                    >
                                </div>
                                <div class="progress-track">
                                    <div
                                        class="progress-fill progress-fill-completed"
                                        :style="{
                                            width: completedPercent + '%',
                                        }"
                                    ></div>
                                </div>
                                <div class="progress-sub">
                                    {{ completedCount }} of {{ TOTAL }}
                                </div>
                            </div>
                            <div class="progress-item">
                                <div class="progress-label-row">
                                    <span class="progress-label-text"
                                        >Incomplete</span
                                    >
                                    <span
                                        class="progress-label-pct incomplete-pct"
                                        >{{ incompletePercent }}%</span
                                    >
                                </div>
                                <div class="progress-track">
                                    <div
                                        class="progress-fill progress-fill-incomplete"
                                        :style="{
                                            width: incompletePercent + '%',
                                        }"
                                    ></div>
                                </div>
                                <div class="progress-sub">
                                    {{ incompleteCount }} of {{ TOTAL }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="doc-requirements-section">
                        <h5 class="requirements-title">
                            Documentation Requirements
                        </h5>

                        <div class="two-column-grid">
                            <div class="req-column">
                                <div
                                    v-for="(req, index) in leftColumn"
                                    :key="req.key"
                                    class="req-row"
                                >
                                    <div class="req-info">
                                        <span class="req-sl">{{
                                            index + 1
                                        }}</span>
                                        <span class="req-label">{{
                                            req.label
                                        }}</span>
                                    </div>
                                    <select
                                        class="doc-select"
                                        :class="getStatusClass(req.key)"
                                        v-model="documentationFields[req.key]"
                                    >
                                        <option value="" disabled>
                                            Select
                                        </option>
                                        <option
                                            v-if="req.hasNA"
                                            value="Not Applicable"
                                        >
                                            Not Applicable
                                        </option>
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                            </div>

                            <div class="req-column">
                                <div
                                    v-for="(req, index) in rightColumn"
                                    :key="req.key"
                                    class="req-row"
                                >
                                    <div class="req-info">
                                        <span class="req-sl">{{
                                            index + 10
                                        }}</span>
                                        <span class="req-label">{{
                                            req.label
                                        }}</span>
                                    </div>
                                    <select
                                        class="doc-select"
                                        :class="getStatusClass(req.key)"
                                        v-model="documentationFields[req.key]"
                                    >
                                        <option value="" disabled>
                                            Select
                                        </option>
                                        <option
                                            v-if="req.hasNA"
                                            value="Not Applicable"
                                        >
                                            Not Applicable
                                        </option>
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button
                            type="button"
                            class="custom-btn btn-13 m-3 p-2"
                            @click="submit"
                            :disabled="saving"
                        >
                            <span
                                v-if="saving"
                                class="spinner-border spinner-border-sm me-1"
                                role="status"
                            ></span>
                            <i v-else class="fa-solid fa-check"></i>
                            |
                            {{
                                saving
                                    ? "Saving..."
                                    : existingRecord
                                      ? "Update Documentation"
                                      : "Save Documentation"
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.doc-header h2 {
    font-weight: 700;
    color: #212529;
}

.doc-employee-name {
    font-size: 1rem;
    font-weight: 600;
    color: #006a92;
}

.summary-section {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px 25px;
    border: 1px solid #e9ecef;
}

.summary-title {
    margin: 0 0 16px 0;
    font-weight: 700;
    color: #333;
    font-size: 1rem;
}

.summary-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}

.stat-card {
    text-align: center;
    padding: 14px 12px;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    background: #fff;
}

.stat-label {
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-bottom: 6px;
}

.stat-value {
    font-size: 1.4rem;
    font-weight: 700;
}

.stat-total .stat-label {
    color: #6c757d;
}

.stat-total .stat-value {
    color: #006a92;
}

.stat-completed .stat-label {
    color: #198754;
}

.stat-completed .stat-value {
    color: #198754;
}

.stat-incomplete .stat-label {
    color: #dc3545;
}

.stat-incomplete .stat-value {
    color: #dc3545;
}

.progress-pair {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.progress-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.progress-label-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.progress-label-text {
    font-size: 0.85rem;
    font-weight: 600;
    color: #495057;
}

.progress-label-pct {
    font-size: 0.95rem;
    font-weight: 700;
}

.completed-pct {
    color: #198754;
}

.incomplete-pct {
    color: #dc3545;
}

.progress-track {
    width: 100%;
    height: 14px;
    background: #e9ecef;
    border-radius: 7px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    border-radius: 7px;
    transition: width 0.4s ease;
}

.progress-fill-completed {
    background: linear-gradient(90deg, #198754, #20c997);
}

.progress-fill-incomplete {
    background: linear-gradient(90deg, #dc3545, #fd7e14);
}

.progress-sub {
    font-size: 0.75rem;
    color: #6c757d;
    font-weight: 500;
}

.doc-requirements-section {
    margin-top: 8px;
}

.requirements-title {
    margin: 0 0 16px 0;
    font-weight: 700;
    color: #333;
    font-size: 1rem;
}

.two-column-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.req-column {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.req-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 12px;
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.15s ease;
}

.req-row:hover {
    background-color: #f8f9fa;
}

.req-row:last-child {
    border-bottom: none;
}

.req-info {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    min-width: 0;
}

.req-sl {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #e9ecef;
    color: #495057;
    font-size: 0.75rem;
    font-weight: 700;
    flex-shrink: 0;
}

.req-label {
    font-size: 0.85rem;
    color: #333;
    line-height: 1.3;
    word-wrap: break-word;
}

.doc-select {
    width: 140px;
    flex-shrink: 0;
    padding: 6px 10px;
    border-radius: 6px;
    border: 2px solid #ced4da;
    font-size: 0.8rem;
    font-weight: 600;
    background-color: #fff;
    cursor: pointer;
    transition:
        border-color 0.2s ease,
        background-color 0.2s ease,
        color 0.2s ease;
    text-align: center;
    appearance: auto;
}

.doc-select:focus {
    outline: none;
}

.doc-select.status-yes {
    border-color: #198754;
    background-color: #d1e7dd;
    color: #0f5132;
}

.doc-select.status-no {
    border-color: #dc3545;
    background-color: #f8d7da;
    color: #842029;
}

.doc-select.status-na {
    border-color: #6c757d;
    background-color: #e9ecef;
    color: #495057;
}

.doc-select.status-empty {
    border-color: #ced4da;
    background-color: #fff;
    color: #6c757d;
}

.custom-btn {
    border: none;
    border-radius: 8px;
    padding: 8px 20px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-13 {
    background-color: #2bc3b9;
    color: #fff;
}

.btn-13:hover:not(:disabled) {
    background-color: #45a6a1;
}

.btn-13:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

@media (max-width: 992px) {
    .two-column-grid {
        grid-template-columns: 1fr;
    }

    .summary-stats {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .summary-section {
        padding: 15px;
    }

    .summary-stats {
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .progress-pair {
        grid-template-columns: 1fr;
        gap: 14px;
    }

    .req-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
        padding: 12px;
    }

    .doc-select {
        width: 100%;
    }
}
</style>
