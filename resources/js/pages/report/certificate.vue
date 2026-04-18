<template>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 grid-margin stretch-card">
                <div class="row">
                    <!-- Form Section -->
                    <div class="col-lg-2 col-md-12 col-sm-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="text-center">
                                    <h3 class="mb-5">Certificates</h3>
                                </div>
                                <form
                                    class="forms-sample"
                                    @submit.prevent="submit"
                                >
                                    <div class="row">
                                        <div
                                            class="col-lg-12 col-md-12 col-sm-12 mb-3"
                                        >
                                            <div class="form-group">
                                                <label for="exampleInputEmail1"
                                                    >Department</label
                                                >
                                                <select
                                                    class="form-control"
                                                    v-model="form.department"
                                                    @change="
                                                        getEmployee(
                                                            form.department,
                                                        )
                                                    "
                                                >
                                                    <option selected disabled>
                                                        select
                                                    </option>
                                                    <option
                                                        v-for="dept in department"
                                                        :key="dept.id"
                                                        :value="dept.id"
                                                    >
                                                        {{ dept.Name }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div
                                            class="col-lg-12 col-md-12 col-sm-12 mb-3"
                                        >
                                            <div class="form-group">
                                                <label for="exampleInputEmail1"
                                                    >Employee Name</label
                                                >
                                                <select
                                                    class="form-control"
                                                    v-model="form.Employee_Id"
                                                    @change="onEmployeeChange"
                                                >
                                                    <option
                                                        v-for="e in employee"
                                                        :key="e.id"
                                                        :value="e.id"
                                                    >
                                                        {{ e.Full_Name }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div
                                            class="col-lg-12 col-md-12 col-sm-12 mb-3"
                                        >
                                            <div class="form-group">
                                                <label for="exampleInputEmail1"
                                                    >Template</label
                                                >
                                                <select
                                                    class="form-control"
                                                    v-model="form.template"
                                                >
                                                    <option value="noc">
                                                        NOC
                                                    </option>
                                                    <option
                                                        value="release-form"
                                                    >
                                                        Release Form
                                                    </option>
                                                    <option
                                                        value="salary-certificate"
                                                    >
                                                        Salary Certificate
                                                    </option>
                                                    <option
                                                        value="salary-breakdown"
                                                    >
                                                        Salary Breakdown
                                                    </option>
                                                    <option value="req-account">
                                                        Request Bank Account
                                                    </option>
                                                    <option
                                                        value="separation-form"
                                                    >
                                                        Employee Separation Form
                                                    </option>
                                                    <!-- <option
                                                        value="salary-payslip"
                                                    >
                                                        Salary Payslip
                                                    </option> -->
                                                    <option
                                                        value="certificate-of-income"
                                                    >
                                                        Certificate of Income
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div
                                            v-if="
                                                form.template ==
                                                    'salary-certificate' ||
                                                form.template == 'req-account'
                                            "
                                            class="col-lg-12 col-md-12 col-sm-12 mb-3"
                                        >
                                            <div class="form-group">
                                                <label for="exampleInputEmail1"
                                                    >Salary Amount</label
                                                >
                                                <input
                                                    type="number"
                                                    class="form-control"
                                                    v-model="form.salary"
                                                    :readonly="true"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <button
                                                type="submit"
                                                class="btn btn-primary form-control"
                                            >
                                                Preview
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- PDF Preview Section -->
                    <div class="col-lg-10 col-md-12 col-sm-12 mb-4">
                        <div class="card">
                            <div class="card-title text-center">
                                <h2 class="mt-2">Report Viewer</h2>
                            </div>
                        </div>
                        <div
                            class="card-body"
                            style="height: 85vh; border: 2px solid #ddd"
                        >
                            <iframe
                                v-if="pdfUrl"
                                :src="pdfUrl"
                                width="100%"
                                style="height: 85vh"
                            ></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import api from "@/api";

const department = ref([]);
const employee = ref([]);
const pdfUrl = ref("");
const error = ref([]);

const form = ref({
    department: "",
    Employee_Id: "",
    template: "",
    salary: "",
});

const getData = async () => {
    try {
        const response = await api.get("/department");
        department.value = response.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

const getEmployee = async (id) => {
    try {
        const response = await api.get(`/all-employee/${id}`);
        employee.value = response.data;
    } catch (error) {
        console.error("Error updating store:", error);
    }
};

const getEmployeeSalary = async (employeeId) => {
    try {
        const res = await api.get(`/salary-certificate-amount/${employeeId}`);
        form.value.salary = res.data?.[0]?.net_pay || "";
    } catch (error) {
        console.error("Salary error:", error);
    }
};

watch(
    () => form.value.template,
    (newVal) => {
        if (newVal === "salary-certificate" && form.value.Employee_Id) {
            getEmployeeSalary(form.value.Employee_Id);
        }
    },
);

const onEmployeeChange = () => {
    if (form.value.template === "salary-certificate") {
        getEmployeeSalary(form.value.Employee_Id);
    }
};

const submit = async () => {
    if (form.value.template == "noc") {
        pdfUrl.value = `/noc/${form.value.Employee_Id}`;
        // window.open(`/noc/${form.value.Employee_Id}`, '_blank');
    } else if (form.value.template == "release-form") {
        pdfUrl.value = `/release/${form.value.Employee_Id}`;
        // window.open(`/release/${form.value.Employee_Id}`, '_blank');
    } else if (form.value.template == "salary-certificate") {
        pdfUrl.value = `/salary-certificate/${form.value.Employee_Id}?salary=${form.value.salary}`;
        // window.open(`/salary-certificate/${form.value.Employee_Id}?salary=${form.value.salary}`, '_blank');
    } else if (form.value.template == "salary-breakdown") {
        pdfUrl.value = `/salary-breakdown/${form.value.Employee_Id}`;
        // window.open(`/salary-breakdown/${form.value.Employee_Id}`, '_blank');
    } else if (form.value.template == "req-account") {
        pdfUrl.value = `/request-bank/${form.value.Employee_Id}?salary=${form.value.salary}`;
        // window.open(`/request-bank/${form.value.Employee_Id}?salary=${form.value.salary}`, '_blank');
    } else if (form.value.template == "separation-form") {
        pdfUrl.value = `/separation-form/${form.value.Employee_Id}`;
        // window.open(`/separation-form/${form.value.Employee_Id}`, '_blank');
    } else if (form.value.template == "salary-payslip") {
        pdfUrl.value = `/salary-payslip/${form.value.Employee_Id}`;
        // window.open(`/salary-payslip/${form.value.Employee_Id}`, '_blank');
    } else if (form.value.template == "certificate-of-income") {
        pdfUrl.value = `/certificate-of-income/${form.value.Employee_Id}`;
        // window.open(`/certificate-of-income/${form.value.Employee_Id}`, '_blank');
    }
};

onMounted(() => getData());
</script>
