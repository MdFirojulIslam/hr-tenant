<script setup>
import { ref, onMounted, watch, computed } from "vue";
import axios from "axios";
import { useStore } from "vuex";
import Swal from "sweetalert2";
import api from "@/api";
import Multiselect from "vue-multiselect";

import { useRoute } from "vue-router";

const route = useRoute();
const store = useStore();

const empId = parseInt(route.params.id);
const selectedSupervisor = ref(null);

const official = ref({
    eid: empId || store.state.employeeId,
    departmentId: "",
    designationId: "",
    employeeGrade: "",
    areaId: "",
    territoryId: "",
    employeeTypeId: "",
    supervisor: "",
    doj: "",
    dol: "",
    provationPeriod: "",
    doc: "",
    jobLocation: "",
    shift: "",
    townIds: [],
    status: "",
});

const designations = ref([]);
const officialInfo = ref([]);
const townData = ref([]);
const supervisorData = ref([]);
const inactive = ref(false);

const error = ref([]);
const empEdit = ref([]);
const townEdit = ref([]);

const getData = async () => {
    try {
        const [responseoff, responseTown, responseSup] = await axios.all([
            api.get("/official-info"),
            api.get("/town"),
            api.get("/get-supervisor"),
        ]);
        officialInfo.value = responseoff.data;
        townData.value = responseTown.data;
        supervisorData.value = responseSup.data;
        if (empId) {
            editHandler();
        }
    } catch (err) {
        error.value = err.message || "Error fetching data";
    } finally {
    }
};

const modifiedSupervisorData = computed(() => {
    return supervisorData.value.map(item => {
        return {
            ...item,
            label: `${item.Full_Name} - ABNB${item.Employee_Id}`, // Concatenate the string here
        };
    });
});

watch(empEdit, async (newEmpData) => {
    if (newEmpData) {
        newEmpData.department
            ? (official.value.departmentId = newEmpData.department.id)
            : (official.value.departmentId = "");
        await getDepartment(newEmpData.department.id);
        newEmpData.designation
            ? (official.value.designationId = newEmpData.designation.id)
            : (official.value.designationId = "");
        newEmpData.Employee_Grade
            ? (official.value.employeeGrade = newEmpData.Employee_Grade)
            : (official.value.employeeGrade = "");
        newEmpData.area
            ? (official.value.areaId = newEmpData.area.id)
            : (official.value.areaId = "");
        newEmpData.territory
            ? (official.value.territoryId = newEmpData.territory.id)
            : (official.value.territoryId = "");
        newEmpData.employee_type
            ? (official.value.employeeTypeId = newEmpData.employee_type.id)
            : (official.value.employeeTypeId = "");
        newEmpData.branch
            ? (official.value.jobLocation = newEmpData.branch.id)
            : (official.value.jobLocation = "");
        newEmpData.DOJ
            ? (official.value.doj = newEmpData.DOJ)
            : (official.value.doj = "");
        newEmpData.DOC
            ? (official.value.doc = newEmpData.DOC)
            : (official.value.doc = "");
        newEmpData.Provation_period
            ? (official.value.provationPeriod = newEmpData.Provation_period)
            : (official.value.provationPeriod = "");
        newEmpData.Shift
            ? (official.value.shift = newEmpData.Shift)
            : (official.value.shift = "");
        newEmpData.DOL
            ? (official.value.dol = newEmpData.DOL)
            : (official.value.dol = "");
        // newEmpData.supervisor
        //     ? (official.value.supervisor = newEmpData.supervisor)
        //     : (official.value.supervisor = "");
        
        if (newEmpData.supervisor) {
            // Find the supervisor object from the modified supervisor data
            const supervisor = supervisorData.value.find(
                (item) => item.id === newEmpData.supervisor.id
            );
            // If found, assign it to official.supervisor
            official.value.supervisor = supervisor
                ? {
                    ...supervisor,
                    label: `${supervisor.Full_Name} - ABNB${supervisor.Employee_Id}`, // Concatenate the label
                }
                : "";  // In case the supervisor data doesn't exist in the supervisorData array
        } else {
            official.value.supervisor = "";  // Reset if no supervisor
        }

        inactive.value = newEmpData.Status === "Y";
    }
});

watch(townEdit, (newTownData) => {
    if (newTownData) {
        official.value.townIds = newTownData.map(town => town.town);
    }
});


const getDepartment = async (id) => {
    try {
        const response = await api.get(`/designation/${id}`);
        designations.value = response.data;
    } catch (error) {
        console.error("Error updating store:", error);
    }
};

const resetForm = () => {
    Object.keys(official.value).forEach((key) => {
        if (typeof official.value[key] === "string") {
            official.value[key] = "";
        } else {
            official.value[key] = null; // or any other default value you prefer
        }
    });
};

const editHandler = async () => {
    try {
        const response = await api.get(`/official/${empId}/edit`);
        const responseTown = await api.get(`/edit-town/${empId}`);
        empEdit.value = response.data;
        townEdit.value = responseTown.data;
    } catch (err) {
        console.error("Error fetching store data for editing:", err);
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
            official.value.status = inactive.value ? "Y" : "N";
            const response = await api.post("/official", official.value);
            if (response.data.success) {
                Swal.fire({
                    position: "middle",
                    icon: "success",
                    title: "Information has been saved",
                    showConfirmButton: false,
                    timer: 1500,
                });
                resetForm();
            }
        }
    } catch (err) {
        error.value = err.response.data.errors;
    }
};

const update = async () => {
    try {
        official.value.status = inactive.value ? "Y" : "N";
        const response = await api.put(`/official/${empId}`, official.value);
        if (response.data.success) {
            Swal.fire({
                position: "middle",
                icon: "success",
                title: "Information has been Updated",
                showConfirmButton: false,
                timer: 1500,
            });
        }
    } catch (error) {
        console.error("Error updating store:", error);
    }
};

const submit = () => {
    if (empEdit.value == "") {
        submitForm();
    } else if (empId) {
        update();
    } else {
        submitForm();
    }
};

onMounted(() => getData());
</script>

<template>
    <div class="mt-5">
        <form @submit.prevent="submit">
            <div class="row mb-3">
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <label for="" class="">Department*
                        <span class="text-danger">{{
                            error.departmentId ? error.departmentId[0] : ""
                            }}</span>
                    </label>
                    <select class="form-control" name="status" id="" v-model="official.departmentId"
                        @change="getDepartment(official.departmentId)">
                        <option selected disabled>select</option>
                        <option v-for="dept in officialInfo.department" :key="dept.id" :value="dept.id">
                            {{ dept.Name }}
                        </option>
                    </select>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <label for="" class="">Designation*
                        <span class="text-danger">{{
                            error.designationId ? error.designationId[0] : ""
                            }}</span></label>
                    <select class="form-control" name="status" id="" v-model="official.designationId">
                        <option selected disabled>select</option>
                        <option v-for="des in designations" :key="des.id" :value="des.id">
                            {{ des.Name }}
                        </option>
                    </select>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <label for="" class="">Employee Grade*
                        <span class="text-danger">{{
                            error.employeeGrade ? error.employeeGrade[0] : ""
                            }}</span></label>
                    <select name="" id="" class="form-control" v-model="official.employeeGrade">
                        <option selected disabled value="">select</option>
                        <option value="1">Grade 1</option>
                        <option value="2">Grade 2</option>
                        <option value="3">Grade 3</option>
                        <option value="4">Grade 4</option>
                        <option value="5">Grade 5</option>
                        <option value="6">Grade 6</option>
                        <option value="7">Grade 7</option>
                        <option value="8">Grade 8</option>
                        <option value="9">Grade 9</option>
                        <option value="10">Grade 10</option>
                        <option value="11">Grade 11</option>
                        <option value="12">Grade 12</option>
                        <option value="13">Grade 13</option>
                        <option value="14">Grade 14</option>
                        <option value="15">Grade 15</option>
                        <option value="16">Grade 16</option>
                        <option value="17">Grade 17</option>
                        <option value="18">Grade 18</option>
                        <option value="19">Grade 19</option>
                        <option value="20">Grade 20</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <label for="" class="">Area</label>
                    <select class="form-control" name="status" id="" v-model="official.areaId">
                        <option selected disabled>select</option>
                        <option v-for="area in officialInfo.area" :key="area.id" :value="area.id">
                            {{ area.Name }}
                        </option>
                    </select>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <label for="" class="">Territory</label>
                    <select class="form-control" name="status" id="" v-model="official.territoryId">
                        <option selected disabled>select</option>
                        <option v-for="ter in officialInfo.territory" :key="ter.id" :value="ter.id">
                            {{ ter.Name }}
                        </option>
                    </select>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <label for="" class="">Employee Type*
                        <span class="text-danger">{{
                            error.employeeTypeId ? error.employeeTypeId[0] : ""
                            }}</span></label>
                    <select class="form-control" name="status" id="" v-model="official.employeeTypeId">
                        <option selected disabled>select</option>
                        <option v-for="typ in officialInfo.emptype" :key="typ.id" :value="typ.id">
                            {{ typ.Name }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <label for="" class="">Date of Joining</label>
                    <input type="date" class="form-control" id="disabledTextInput" aria-describedby="emailHelp"
                        v-model="official.doj" />
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <label for="" class="">Date of Confirmation</label>
                    <input type="date" class="form-control" id="disabledTextInput" aria-describedby="emailHelp"
                        v-model="official.doc" />
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <label for="" class="">Date of Leaving</label>
                    <input type="date" class="form-control" id="disabledTextInput" aria-describedby="emailHelp"
                        v-model="official.dol" />
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <label for="exampleInputEmail1" class="">Probation Period</label>
                    <input type="text" class="form-control" id="disabledTextInput" aria-describedby="emailHelp"
                        v-model="official.provationPeriod" />
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <label for="" class="">Job Location</label>
                    <select class="form-control" name="status" id="" v-model="official.jobLocation">
                        <option selected disabled>select</option>
                        <option v-for="branch in officialInfo.branch" :key="branch.id" :value="branch.id">
                            {{ branch.Name }}
                        </option>
                    </select>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <label for="exampleInputEmail1" class="">Shift</label>
                    <select name="" id="" class="form-control" v-model="official.shift">
                        <option selected disabled>select</option>
                        <option value="D">Day</option>
                        <option value="N">Night</option>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <label for="multiSelect">Supervisor</label>
                    <multiselect class="custom-multiselect" v-model="official.supervisor"
                        :options="modifiedSupervisorData" :close-on-select="true" :clear-on-select="false"
                        :preserve-search="true" placeholder="Select Towns" label="label" track-by="id">
                    </multiselect>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <label for="multiSelect">Town</label>
                    <multiselect class="custom-multiselect" v-model="official.townIds" :options="townData"
                        :multiple="true" :close-on-select="false" :clear-on-select="false" :preserve-search="true"
                        placeholder="Select Towns" label="Name" track-by="id"></multiselect>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <input type="checkbox" v-model="inactive" />
                <label class="px-2 pt-0">Inactive</label>
            </div>

            <div class="d-flex justify-content-end">
                <button type="reset" class="custom-btn btn-12 m-3 p-2">
                    <i class="fa-solid fa-x"></i> | Reset
                </button>
                <button type="submit" class="custom-btn btn-13 m-3 p-2">
                    <i class="fa-solid fa-check"></i> | Save
                </button>
            </div>
        </form>
    </div>
</template>

<style>
.custom-multiselect {
    font-size: 2px;
    border-radius: 5px;
}

.custom-multiselect .multiselect__input {
    color: #333;
    font-size: 12px;
}

.custom-multiselect .multiselect__placeholder {
    font-style: italic;
    color: #666;
}

.custom-multiselect .multiselect__content {
    background-color: #f8f9fa;
}

/* Input fields */
.form-control {
    border-radius: 8px;
    border: 1px solid #ddd;
    font-size: 1rem;
    transition: border-color 0.2s ease;
    background: #f9f9f9;
    background-size: 1rem 1rem;
    padding-right: 2.5rem;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(38, 143, 255, 0.25);
}

/* Dropdown arrow disable */
.form-control::-ms-expand {
    display: none;
}

/* File upload styling */
.form-control[type="file"] {
    border: 1px solid #ddd;
    padding: 0.75rem;
    border-radius: 8px;
    cursor: pointer;
    background-color: #f8f9fa;
}

/* File upload hover effect */
.form-control[type="file"]:hover {
    border-color: #007bff;
}

/* Textarea */
textarea.form-control {
    border-radius: 8px;
    border: 1px solid #ddd;
    padding: 0.75rem 1.25rem;
    font-size: 1rem;
    resize: vertical;
}
</style>
