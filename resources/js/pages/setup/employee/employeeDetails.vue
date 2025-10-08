<script setup>
import { ref, onMounted } from "vue";
import api from '@/api';

import { useRoute } from "vue-router";
import BackButton from '../../commonComponent/back.vue';

const route = useRoute();

const empId = parseInt(route.params.id);
const emp = ref([]);
const error = ref([]);

const getData = async () => {
    try {
        const response = await api.get(`/employee/${empId}/edit`);
        emp.value = response.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    } finally {
    }
};

const mapGender = (status) => {
    const statusMap = {
        M: "Male",
        F: "Female",
        O: "Other",
    };

    return statusMap[status] || "N/A";
};

const mapStatus = (status) => {
    const statusMap = {
        M: "Married",
        U: "Unmarried",
        S: "Single",
    };

    return statusMap[status] || "N/A";
};

const mapShift = (status) => {
    const statusMap = {
        D: "Day",
        N: "Night",
    };

    return statusMap[status] || "N/A";
};

onMounted(() => getData());
</script>

<template>
    <BackButton />
    <div class="row">
        <div class="col-lg-4 col-md-4 col-sm-6 border">
            <div class="card mb-3 mt-3">
                <div class="card-body text-center">
                    <img :src="emp.emp_img ? emp.emp_img.img_url : ''" height="50%" width="50%" />
                    <h4 class="pt-3 text-success">{{ emp.Full_Name }}</h4>
                    <p class="m-0">{{ emp.official?.department?.Name || 'Loading...' }} </p>
                    <p class="text-muted mb-0">{{ emp.Present_Address }}</p>
                    <p class="text-muted mb-0">{{ emp.Contact_No }}</p>
                    <p class="text-muted mb-0">{{ emp.Official_Email }}</p>
                </div>
            </div>
            <div class="card pi mb-3">
                <div class="card-body p-2">
                    <h4 class="text-center text-success">
                        Nominee Information
                    </h4>
                    <hr>
                    <table width="100%" v-for="nom in emp.nominee" :key="nom.id">
                        <tr>
                            <th>Full Name</th>
                            <td>{{ nom.Nominee_Name }}</td>
                        </tr>
                        <tr>
                            <th>Date of Birth</th>
                            <td>{{ nom.DOB }}</td>
                        </tr>
                        <tr>
                            <th>Contact No</th>
                            <td>{{ nom.Contact_No }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ nom.Email }}</td>
                        </tr>
                        <tr>
                            <th>National ID</th>
                            <td>{{ nom.NID }}</td>
                        </tr>
                        <tr>
                            <th>Share</th>
                            <td>{{ nom.Share }}</td>
                        </tr>
                        <tr>
                            <th>Present Address</th>
                            <td>{{ nom.Personal_Address }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="card pi mb-3">
                <div class="card-body p-2">
                    <h4 class="text-center text-success">Child Information</h4>
                    <hr>
                    <table width="100%" v-for="ch in emp.child" :key="ch.id">
                        <tr>
                            <th>Child Name</th>
                            <td>{{ ch.Child_Name }}</td>
                        </tr>
                        <tr>
                            <th>NID No</th>
                            <td>{{ ch.NID }}</td>
                        </tr>
                        <tr>
                            <th>Date of Birth</th>
                            <td>{{ ch.DOB }}</td>
                        </tr>
                        <tr>
                            <th>Contact No</th>
                            <td>{{ ch.Contact_No }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ ch.Email }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-8 col-md-8 col-sm-6">
            <div class="card mb-3">
                <div class="card-body p-2">
                    <table width="100%">
                        <tr>
                            <th class="p-2 text-center text-success" colspan="4">
                                <h4>
                                    Employee Information
                                </h4>
                            </th>
                        </tr>
                        <tr>
                            <th class="p-2">Full Name</th>
                            <td class="p-2">{{ emp.Full_Name }}</td>
                            <th class="p-2">Father's Name</th>
                            <td class="p-2">{{ emp.Father_Name }}</td>
                        </tr>
                        <tr>
                            <th class="p-2">Mother's Name</th>
                            <td class="p-2">{{ emp.Mother_Name }}</td>
                            <th class="p-2">Spouse Name</th>
                            <td class="p-2">{{ emp.Spouse_Name }}</td>
                        </tr>
                        <tr>
                            <th class="p-2">Marital Status</th>
                            <td class="p-2">
                                {{ mapStatus(emp.Marital_Status) }}
                            </td>
                            <th class="p-2">Date of Birth</th>
                            <td class="p-2">{{ emp.DOB }}</td>
                        </tr>
                        <tr>
                            <th class="p-2">Place of Birth</th>
                            <td class="p-2">{{ emp.Place_of_Birth }}</td>
                            <th class="p-2">Emergency Contact</th>
                            <td class="p-2">{{ emp.Emergency_Contact }}</td>
                        </tr>
                        <tr>
                            <th class="p-2">Present Address</th>
                            <td class="p-2">{{ emp.Present_Address }}</td>
                            <th class="p-2">Permanent Address</th>
                            <td class="p-2">{{ emp.Permanent_Address }}</td>
                        </tr>
                        <tr v-if="emp && emp.religion">
                            <th class="p-2">Religion</th>
                            <td class="p-2">{{ emp.religion.Name }}</td>
                            <th class="p-2">Gender</th>
                            <td class="p-2">{{ mapGender(emp.Gender) }}</td>
                        </tr>
                        <tr v-if="emp && emp.blood">
                            <th class="p-2">Blood Group</th>
                            <td class="p-2">{{ emp.blood.Name }}</td>
                            <th class="p-2">Nationality</th>
                            <td class="p-2">{{ emp.Nationality }}</td>
                        </tr>
                        <tr>
                            <th class="p-2">NID</th>
                            <td class="p-2">{{ emp.NID }}</td>
                        </tr>
                    </table>
                </div>
            </div>


            <div class="card mb-3">
                <div class="card-body p-2">
                    <table width="100%">
                        <tr>
                            <th class="p-2 text-center text-success" colspan="4">
                                <h4>
                                    Official Information
                                </h4>
                            </th>
                        </tr>
                        <tr>
                            <th class="p-2">Department</th>
                            <td class="p-2">{{ emp.official?.department?.Name }}</td>
                            <th class="p-2">Designation</th>
                            <td class="p-2">{{ emp.official?.designation?.Name }}</td>
                        </tr>
                        <tr>
                            <th class="p-2">Employee Grade</th>
                            <td class="p-2">{{ emp.official?.Employee_Grade }}</td>
                            <th class="p-2">Employee Type</th>
                            <td class="p-2">{{ emp.official?.employee_type?.Name }}</td>
                        </tr>
                        <tr>
                            <th class="p-2">Supervisor</th>
                            <td class="p-2">
                                {{
                                    emp.official?.supervisor
                                        ? emp.official?.supervisor?.Full_Name
                                        : ""
                                }}
                            </td>
                            <!-- <th class="p-2">Supervisor</th> -->
                            <th class="p-2">Provation Period</th>
                            <td class="p-2">{{ emp.official?.Provation_period }}</td>
                        </tr>
                        <tr>
                            <th class="p-2">Date of joining</th>
                            <td class="p-2">{{ emp.official?.DOJ }}</td>
                            <th class="p-2">Date of confirmation</th>
                            <td class="p-2">{{ emp.official?.DOC }}</td>
                        </tr>
                        <tr>
                            <th class="p-2">Job Location</th>
                            <td class="p-2">
                                {{ emp.official?.branch ? emp.official?.branch?.Name : "" }}
                            </td>
                            <th class="p-2">Shift</th>
                            <td class="p-2">{{ mapShift(emp.official?.Shift) }}</td>
                        </tr>
                    </table>
                </div>
            </div>


            <div class="card mb-3">
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="p-2 text-center text-success" colspan="6">
                                        <h4>
                                            Work Experience
                                        </h4>
                                    </th>
                                </tr>
                                <tr>
                                    <th>Company Name</th>
                                    <th>Department</th>
                                    <th>Designation</th>
                                    <th>From Date</th>
                                    <th>To Date</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="work in emp.experience" :key="work.id">
                                    <td>{{ work.Company_Name }}</td>
                                    <td>{{ work.Department }}</td>
                                    <td>{{ work.Designation }}</td>
                                    <td>{{ work.From_Date }}</td>
                                    <td>{{ work.To_Date }}</td>
                                    <td>{{ work.Duration }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="p-2 text-center text-success" colspan="6">
                                        <h4>
                                            Training Information
                                        </h4>
                                    </th>
                                </tr>
                                <tr>
                                    <th>Training Title</th>
                                    <th>Organized By</th>
                                    <th>Duration</th>
                                    <th>Year</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="train in emp.training" :key="train.id">
                                    <td>{{ train.Training_Title }}</td>
                                    <td>{{ train.Organized_By }}</td>
                                    <td>{{ train.Duration }}</td>
                                    <td>{{ train.Year }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="p-2 text-center text-success" colspan="6">
                                        <h4>
                                            Academic Information
                                        </h4>
                                    </th>
                                </tr>
                                <tr>
                                    <th>Degree</th>
                                    <th>Institude</th>
                                    <th>Board</th>
                                    <th>Result</th>
                                    <th>Year of Passing</th>
                                    <th>Acheivement</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="aca in emp.academic" :key="aca.id">
                                    <td>
                                        {{
                                            aca.education
                                                ? aca.education.Name
                                                : ""
                                        }}
                                    </td>
                                    <td>
                                        {{
                                            aca.Institute_Name
                                                ? aca.Institute_Name
                                                : ""
                                        }}
                                    </td>
                                    <td>
                                        {{ aca.board ? aca.board.Name : "" }}
                                    </td>
                                    <td>{{ aca.Result ? aca.Result : "" }}</td>
                                    <td>
                                        {{ aca.scale ? aca.scale.Name : "" }}
                                    </td>
                                    <td>
                                        {{
                                            aca.Year_of_Passing
                                                ? aca.Year_of_Passing
                                                : ""
                                        }}
                                    </td>
                                    <td>
                                        {{
                                            aca.Acheivement
                                                ? aca.Acheivement
                                                : ""
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.pi table tr th {
    padding: 8px;
    width: 40%;
}

.pi table tr td {
    padding: 8px;
    width: 60%;
}
</style>
