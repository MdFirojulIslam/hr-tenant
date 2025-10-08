<script setup>
import { ref, onMounted } from "vue";
import api from "@/api";
import axios from "axios";

const employees = ref([]);
const isLoading = ref(true);
const error = ref(null);
const departments = ref([]);
const selectedDepartments = ref([]);
const designations = ref([]);
const selectedDesignations = ref([]);
const filteredData = ref([]);

// Fetch data for departments, designations, and employees
const getData = async () => {
    try {
        const [responseDept, responseEmp] = await axios.all([
            api.get("/department"),
            api.get("/employee-exl"),
        ]);
        departments.value = responseDept.data;
        employees.value = responseEmp.data;

        // Populate unique designations based on employee data
        designations.value = [
            ...new Set(employees.value.map(emp => emp.designation))
        ];

        // Initially, show all employees in filteredData
        filteredData.value = employees.value;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    } finally {
        isLoading.value = false;
    }
};

// Filter employees based on the selected departments and designations
const filterEmployees = () => {
    filteredData.value = employees.value.filter(item => {
        const deptFilter = selectedDepartments.value.length === 0 || selectedDepartments.value.includes(item.department);
        const designationFilter = selectedDesignations.value.length === 0 || selectedDesignations.value.includes(item.designation);
        return deptFilter && designationFilter;
    });
};

// Handle department checkbox change
const toggleDepartment = (deptName) => {
    const index = selectedDepartments.value.indexOf(deptName);
    if (index > -1) {
        selectedDepartments.value.splice(index, 1);
    } else {
        selectedDepartments.value.push(deptName);
    }
    filterEmployees();
};

// Handle designation checkbox change
const toggleDesignation = (designation) => {
    const index = selectedDesignations.value.indexOf(designation);
    if (index > -1) {
        selectedDesignations.value.splice(index, 1);
    } else {
        selectedDesignations.value.push(designation);
    }
    filterEmployees();
};

onMounted(() => getData());
</script>

<template>
    <div class="row d-flex justify-space-between">
        <div class="col-lg-3">
            <div class="card dept-card">
                <div class="card-header">Department</div>
                <div class="card-body">
                    <table class="table">
                        <tbody>
                            <tr v-for="item in departments" :key="item.id">
                                <td class="checkbox-container">
                                    <input
                                        type="checkbox"
                                        :id="item.Name"
                                        :value="item.Name"
                                        @change="toggleDepartment(item.Name)"
                                    />
                                    <label :for="item.Name">{{ item.Name }}</label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card dept-card">
                <div class="card-header">Designation</div>
                <div class="card-body">
                    <table class="table">
                        <tbody>
                            <tr v-for="designation in designations" :key="designation">
                                <td>
                                    <input
                                        type="checkbox"
                                        :id="designation"
                                        :value="designation"
                                        @change="toggleDesignation(designation)"
                                    />
                                    <label :for="designation">{{ designation }}</label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-12 mt-3">
            <div class="card">
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Employee Name</th>
                                <th>Department</th>
                                <th>Designation</th>
                                <th>Father Name</th>
                                <th>Mother Name</th>
                                <th>Spouse Name</th>
                                <th>Present Address</th>
                                <th>Date of Birth</th>
                                <th>Contact No</th>
                                <th>Emergency Contact</th>
                                <th>Official Email</th>
                                <th>Blood Group</th>
                                <th>Religion</th>
                                <th>Employee Grade</th>
                                <th>Date of Joining</th>
                                <th>Probation Period</th>
                                <th>Employee Type</th>
                                <th>NID</th>
                                <th>Nationality</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in filteredData" :key="item.id">
                                <td>ABNB{{ item.Employee_Id }}</td>
                                <td>{{ item.Full_Name }}</td>
                                <td>{{ item.department }}</td>
                                <td>{{ item.designation }}</td>
                                <td>{{ item.Father_Name }}</td>
                                <td>{{ item.Mother_Name }}</td>
                                <td>{{ item.Spouse_Name }}</td>
                                <td>{{ item.Present_Address }}</td>
                                <td>{{ item.DOB }}</td>
                                <td>{{ item.Contact_No }}</td>
                                <td>{{ item.Emergency_Contact }}</td>
                                <td>{{ item.Official_Email }}</td>
                                <td>{{ item.group }}</td>
                                <td>{{ item.religion }}</td>
                                <td>{{ item.Employee_Grade }}</td>
                                <td>{{ item.DOJ }}</td>
                                <td>{{ item.Provation_period }}</td>
                                <td>{{ item.employee_type }}</td>
                                <td>{{ item.NID }}</td>
                                <td>{{ item.Nationality }}</td>
                                <td>{{ item.status }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
*{
    font-size: 12px;
}
.card {
    height: 400px;
    overflow: auto;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}

.dept-card {
    height: 200px;
    overflow: auto;
}

.table {
    width: 100%;
    margin-bottom: 0;
}

.checkbox-container {
    display: flex;
    align-items: center;
    padding: 10px;
}

/* Styling the checkbox */
input[type="checkbox"] {
    margin-right: 5px;
    width: 12px;
    height: 12px;
    accent-color: #000000;
    border-radius: 4px;
    cursor: pointer;
}

/* Styling the label to align with the checkbox */
label {
    font-size: 12px;
    color: #333;
    cursor: pointer;
}

/* Add some hover effect to make it interactive */
td:hover {
    background-color: #f7f7f7;
    cursor: pointer;
    transition: background-color 0.2s ease-in-out;
}

/* General table styling */

</style>
