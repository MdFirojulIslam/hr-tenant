<script setup>
import { ref, onMounted, watch, reactive } from "vue";
import { useStore } from "vuex";
import Swal from "sweetalert2";
import api from "@/api";

import { useRoute } from "vue-router";

const route = useRoute();

const empId = parseInt(route.params.id);

const employee = ref({
    companyId: "",
    employeeId: "",
    cardNo: "",
    fullName: "",
    fatherName: "",
    motherName: "",
    spouseName: "",
    maritalStatus: "",
    dob: "",
    pob: "",
    presentAddress: "",
    permanentAddress: "",
    officialContact: "",
    emergencyContact: "",
    gender: "",
    personalEmail: "",
    officialEmail: "",
    religion: "",
    bloodGroup: "",
    nationality: "",
    nid: "",
    photo: null,
});

const bloods = ref([]);
const religions = ref([]);
const companies = ref([]);
const phones = ref([]);
const error = ref([]);
const empEdit = ref([]);
const fileSizeWarning = ref();
const selectedImage = ref();
const emp_img = ref();

watch(
    () => empEdit.value,
    (newEmpData) => {
        if (newEmpData) {
            newEmpData.company
                ? (employee.value.companyId = newEmpData.Company_Id)
                : (employee.value.companyId = "");
            newEmpData.Employee_Id
                ? (employee.value.employeeId = newEmpData.Employee_Id)
                : (employee.value.employeeId = "");
            newEmpData.Card_No
                ? (employee.value.cardNo = newEmpData.Card_No)
                : (employee.value.cardNo = "");
            newEmpData.Full_Name
                ? (employee.value.fullName = newEmpData.Full_Name)
                : (employee.value.fullName = "");
            newEmpData.Father_Name
                ? (employee.value.fatherName = newEmpData.Father_Name)
                : (employee.value.fatherName = "");
            newEmpData.Mother_Name
                ? (employee.value.motherName = newEmpData.Mother_Name)
                : (employee.value.motherName = "");
            newEmpData.Spouse_Name
                ? (employee.value.spouseName = newEmpData.Spouse_Name)
                : (employee.value.spouseName = "");
            newEmpData.Marital_Status
                ? (employee.value.maritalStatus = newEmpData.Marital_Status)
                : (employee.value.maritalStatus = "");
            newEmpData.DOB
                ? (employee.value.dob = newEmpData.DOB)
                : (employee.value.dob = "");
            newEmpData.Place_of_Birth
                ? (employee.value.pob = newEmpData.Place_of_Birth)
                : (employee.value.pob = "");
            newEmpData.Present_Address
                ? (employee.value.presentAddress = newEmpData.Present_Address)
                : (employee.value.presentAddress = "");
            newEmpData.Permanent_Address
                ? (employee.value.permanentAddress =
                    newEmpData.Permanent_Address)
                : (employee.value.permanentAddress = "");
            newEmpData.Contact_No
                ? (employee.value.officialContact = newEmpData.Contact_No)
                : (employee.value.officialContact = "");
            newEmpData.Emergency_Contact
                ? (employee.value.emergencyContact =
                    newEmpData.Emergency_Contact)
                : (employee.value.emergencyContact = "");
            newEmpData.Gender
                ? (employee.value.gender = newEmpData.Gender)
                : (employee.value.gender = "");
            newEmpData.Personal_Email
                ? (employee.value.personalEmail = newEmpData.Personal_Email)
                : (employee.value.personalEmail = "");
            newEmpData.Official_Email
                ? (employee.value.officialEmail = newEmpData.Official_Email)
                : (employee.value.officialEmail = "");
            newEmpData.blood
                ? (employee.value.bloodGroup = newEmpData.Blood_Group_Id)
                : (employee.value.bloodGroup = "");
            newEmpData.religion
                ? (employee.value.religion = newEmpData.Religion_Id)
                : (employee.value.religion = "");
            newEmpData.Nationality
                ? (employee.value.nationality = newEmpData.Nationality)
                : (employee.value.nationality = "");
            newEmpData.NID
                ? (employee.value.nid = newEmpData.NID)
                : (employee.value.nid = "");
        }
    }
);

const getData = async () => {
    try {
        const [
            responseBlood,
            responseReligion,
            responseCompany,
            responsePhone,
        ] = await axios.all([
            api.get("/blood"),
            api.get("/religion"),
            api.get("/company"),
            api.get("/phone"),
        ]);

        bloods.value = responseBlood.data;
        religions.value = responseReligion.data;
        companies.value = responseCompany.data;
        phones.value = responsePhone.data;

        if (empId) {
            editHandler();
        } else {
            const responseId = await api.get("/emp-id");
            employee.value.employeeId = responseId.data.Employee_Id + 1;
        }
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

const store = useStore();

const resetForm = () => {
    Object.keys(employee.value).forEach((key) => {
        if (typeof employee.value[key] === "string") {
            employee.value[key] = "";
        } else {
            employee.value[key] = null; // or any other default value you prefer
        }
    });
};

const editHandler = async () => {
    try {
        const response = await api.get(`/employee/${empId}/edit`);
        empEdit.value = response.data;
        const responseimg = await api.get(`/empimg/${empId}`);
        emp_img.value = responseimg.data;
    } catch (err) {
        console.error("Error fetching store data for editing:", err);
    }
};

const getImage = (e) => {
    const file = e.target.files[0];
    if (file.size > 512000) {
        fileSizeWarning.value = true;
        e.target.value = null;
    } else {
        fileSizeWarning.value = false;
        employee.photo = e.target.files[0];
        const reader = new FileReader();
        reader.onload = (event) => {
            selectedImage.value = event.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const submitForm = async () => {
    const config = {
        headers: {
            "Content-Type": "multipart/form-data",
        },
    };
    const formdata = new FormData();

    formdata.append("file", employee.photo);

    for (const key in employee.value) {
        if (employee.value.hasOwnProperty(key) && key !== "photo") {
            formdata.append(key, employee.value[key]);
        }
    }

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
            const response = await api.post("/employee", formdata, config);
            if (response.data.success) {
                store.dispatch("setEmployeeId", response.data.empid);
                resetForm();
                Swal.fire({
                    position: "middle",
                    icon: "success",
                    title: "Employee information has been saved",
                    showConfirmButton: false,
                    timer: 1500,
                });
            }
        }
    } catch (err) {
        error.value = err.response.data.errors;
    }
};

const update = async () => {
    const config = {
        headers: {
            "Content-Type": "multipart/form-data",
        },
    };
    let data = new FormData();

    data.append("file", employee.photo);
    for (const key in employee.value) {
        if (employee.value.hasOwnProperty(key) && key !== "photo") {
            data.append(key, employee.value[key]);
        }
    }

    try {
        const result = await Swal.fire({
            title: "Are you sure?",
            text: "Do you want to update this information?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes",
            cancelButtonText: "No",
            reverseButtons: true,
        });

        if (result.isConfirmed) {
            const response = await api.post(`/employee/${empId}`, data, config);
            if (response.data.success) {
                Swal.fire({
                    position: "middle",
                    icon: "success",
                    title: "Employee information updated",
                    showConfirmButton: false,
                    timer: 1500,
                });
            }
        }
    } catch (error) {
        console.error("Error updating employee:", error);
    }
};

const submit = () => {
    if (empId) {
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
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <label for="" class="">Company Name*
                                        <span class="text-danger">{{
                                            error.companyId
                                                ? error.companyId[0]
                                                : ""
                                        }}</span></label>
                                    <select v-model="employee.companyId" name="" id="" class="form-control">
                                        <option selected disabled>
                                            select
                                        </option>
                                        <option v-for="company in companies" :key="company.id" :value="company.id">
                                            {{ company.Name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <label for="exampleInputEmail1" class="">Employee ID*
                                        <span class="text-danger">{{
                                            error.employeeId
                                                ? error.employeeId[0]
                                                : ""
                                        }}</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">ABNB</span>
                                        </div>
                                        <input type="text" class="form-control" id="disabledTextInput"
                                            aria-describedby="emailHelp" v-model="employee.employeeId" readonly />
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <label for="exampleInputEmail1" class="z">Card No</label>
                                    <input type="text" class="form-control" id="disabledTextInput"
                                        aria-describedby="emailHelp" v-model="employee.cardNo" />
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <label for="exampleInputEmail1" class="">Full Name*
                                        <span class="text-danger">{{
                                            error.fullName
                                                ? error.fullName[0]
                                                : ""
                                        }}</span></label>
                                    <input type="text" class="form-control" id="disabledTextInput"
                                        aria-describedby="emailHelp" v-model="employee.fullName" />
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <label for="exampleInputEmail1" class="">Date of Birth*
                                        <span class="text-danger">{{
                                            error.dob ? error.dob[0] : ""
                                        }}</span></label>
                                    <input type="date" class="form-control" id="disabledTextInput"
                                        aria-describedby="emailHelp" v-model="employee.dob" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <label for="exampleInputEmail1" class="">Father's Name</label>
                                    <input type="text" class="form-control" id="disabledTextInput"
                                        aria-describedby="emailHelp" v-model="employee.fatherName" />
                                </div>

                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <label for="exampleInputEmail1" class="">Mother's Name</label>
                                    <input type="text" class="form-control" id="disabledTextInput"
                                        aria-describedby="emailHelp" v-model="employee.motherName" />
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <label for="exampleInputEmail1" class="">Place of Birth</label>
                                    <input type="text" class="form-control" id="disabledTextInput"
                                        aria-describedby="emailHelp" v-model="employee.pob" />
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <label for="exampleInputEmail1" class="">Present Address</label>
                                    <textarea class="form-control" id="exampleFormControlTextarea1" rows="2"
                                        v-model="employee.presentAddress"></textarea>
                                </div>
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <label for="exampleInputEmail1" class="">Permanent Address</label>
                                    <textarea class="form-control" id="exampleFormControlTextarea1" rows="2"
                                        v-model="employee.permanentAddress"></textarea>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <label for="exampleInputEmail1" class="">Marital Status</label>
                                    <select v-model="employee.maritalStatus" name="" id="" class="form-control">
                                        <option selected disabled>
                                            -select-
                                        </option>
                                        <option value="M">Married</option>
                                        <option value="U">Unmarried</option>
                                    </select>
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <label for="exampleInputEmail1" class="">Spouse Name</label>
                                    <input type="text" class="form-control" id="disabledTextInput"
                                        aria-describedby="emailHelp" v-model="employee.spouseName" />
                                </div>

                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <label for="" class="">Religion</label>
                                    <select v-model="employee.religion" name="" id="" class="form-control">
                                        <option selected disabled>
                                            select
                                        </option>
                                        <option v-for="religion in religions" :key="religion.id" :value="religion.id">
                                            {{ religion.Name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <label for="exampleInputEmail1" class="">Official Contact</label>
                                    <input type="text" class="form-control" id="disabledTextInput"
                                        aria-describedby="emailHelp" v-model="employee.officialContact" />
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <label for="exampleInputEmail1" class="">Emergency Contact</label>
                                    <input type="text" class="form-control" id="disabledTextInput"
                                        aria-describedby="emailHelp" v-model="employee.emergencyContact" />
                                </div>

                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <label for="" class="">Gender</label>
                                    <select v-model="employee.gender" name="" id="" class="form-control">
                                        <option selected disabled>
                                            -select-
                                        </option>
                                        <option value="M">Male</option>
                                        <option value="F">Female</option>
                                        <option value="O">Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <label for="exampleInputEmail1" class="">Personal Email</label>
                                    <input type="email" class="form-control" id="disabledTextInput"
                                        aria-describedby="emailHelp" v-model="employee.personalEmail" />
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <label for="exampleInputEmail1" class="">Official Email</label>
                                    <input type="email" class="form-control" id="disabledTextInput"
                                        aria-describedby="emailHelp" v-model="employee.officialEmail" />
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <label for="" class="">Blood Group</label>
                                    <select v-model="employee.bloodGroup" name="" id="" class="form-control">
                                        <option selected disabled>
                                            select
                                        </option>
                                        <option v-for="blood in bloods" :key="blood.id" :value="blood.id">
                                            {{ blood.Name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <label for="exampleInputEmail1" class="">Nationality</label>
                                    <input type="text" class="form-control" id="disabledTextInput"
                                        aria-describedby="emailHelp" v-model="employee.nationality" />
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <label for="exampleInputEmail1" class="">NID</label>
                                    <input type="text" class="form-control" id="disabledTextInput"
                                        aria-describedby="emailHelp" v-model="employee.nid" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-3 img-card">
                        <div class="card-body img-card-body">
                            <div class="col-lg-12 col-md-12 col-sm-12 mb-2">
                                <label for="exampleInputEmail1" class="">Upload Photo
                                    <span v-if="fileSizeWarning" class="text-danger">
                                        File size exceeds 500 KB. Please choose
                                        a smaller file.
                                    </span></label>
                                <input type="file" class="form-control" id="disabledTextInput" @change="getImage" />
                            </div>
                            <div v-if="selectedImage" class="row mb-3">
                                <div class="col-lg-12 col-md-12 col-sm-12">
                                    <img :src="selectedImage ? selectedImage : ''
                                        " alt="Image Preview" class="card-img" />
                                </div>
                            </div>
                            <div v-else>
                                <div v-if="empId">
                                    <img :src="emp_img ? emp_img.img_url : ''" alt="Image Preview" class="card-img" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end">
                    <button type="reset" class="custom-btn btn-12 m-3 p-2">
                        <i class="fa-solid fa-x"></i> | Reset
                    </button>
                    <button type="submit" class="custom-btn btn-13 m-3 p-2">
                        <i class="fa-solid fa-check"></i> | Save
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>

<style scoped>
.img-card {
    height: 563px;
    max-height: 563px;
}

.img-card-body {
    object-fit: contain;
}

img {
    aspect-ratio: 4/3;
    max-height: 560px;
    object-fit: contain;
}

/* Input fields */
.form-control {
    border-radius: 8px;
    border: 1px solid #ddd;
    font-size: 1rem;
    transition: border-color 0.2s ease;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(38, 143, 255, 0.25);
}

/* Select fields */
.form-control {
    padding-right: 2.5rem;
    /* Extra space for the dropdown arrow */
}

/* Dropdown arrow disable */
.form-control::-ms-expand {
    display: none;
}

/* Custom select styles */
.form-control {
    background: #f9f9f9;
    background-size: 1rem 1rem;
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
