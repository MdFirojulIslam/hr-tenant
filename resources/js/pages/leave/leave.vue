<script setup>
import { ref, onMounted } from "vue";
import Swal from "sweetalert2";
import api from "../../api";

import moment from "moment";

const employees = ref([]);
const error = ref([]);
const empselect = ref([]);
const emp = ref([]);
const officials = ref([]);

const getData = async () => {
    try {
        const response = await api.get("/employee");
        employees.value = response.data;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    }
};

const getEmployee = async (id) => {
    try {
        const response = await api.get(`/employee/${id}/edit`);
        // const responseoff = await axios.get(`/api/official/${id}/edit`);
        emp.value = response.data;
        officials.value = responseoff.data;
    } catch (error) {
        console.error("Error getting employee:", error);
    }
};

onMounted(() => getData());
</script>


<template>
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="row">
            <div class="text-center">
                <h2 class="m-0">ARTISAN BUSINESS NETWORK BANGLADESH</h2>
                <P class="m-0"
                    >Level # A-1, The Legend, House # 13 Rd 09,Gulshan-1, Dhaka
                    1212</P
                >
                <p class="p-0">
                    Tel: +88 02 22229198, info@artisanbn.com, www.artisanbn.com
                </p>
                <h3>LEAVE APPLICATION</h3>
                <h5>A. INFORMATION OF EMPLOYEE</h5>
            </div>
        </div>

        <div class="row">
            <table class="leave">
                <tr>
                    <th>Employee ID</th>
                    <td>{{ emp.Employee_Id }}</td>
                    <th colspan="4" class="text-center">LEAVE STATUS</th>
                </tr>
                <tr>
                    <th>Name</th>
                    <td>
                        <select
                            v-model="empselect"
                            @change="getEmployee(empselect)"
                        >
                            <option selected disabled>select</option>
                            <option
                                v-for="emp in employees.data"
                                :key="emp.id"
                                :value="emp.id"
                            >
                                {{ emp.Full_Name }}
                            </option>
                        </select>
                    </td>
                    <td>Leave Type</td>
                    <td>Entitled</td>
                    <td>Enjoyed</td>
                    <td>Balance</td>
                </tr>
                <tr>
                    <th>Designation</th>
                    <td v-for="off in emp.official" :key="off.id">
                        {{
                            off.designation && off.designation.Name
                                ? off.designation.Name
                                : ""
                        }}
                    </td>
                    <td>CL</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th>Department</th>
                    <td v-for="off in emp.official" :key="off.id">
                        {{
                            off.department && off.department.Name
                                ? off.department.Name
                                : ""
                        }}
                    </td>
                    <td>SL</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th>Division</th>
                    <td></td>
                    <td>EL</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th>Date of Joining</th>
                    <td>{{ officials.DOJ }}</td>
                    <td>Others</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th>Date</th>
                    <td>{{ moment(new Date()).format("DD-MM-YYYY") }}</td>
                    <td>Comments</td>
                    <td colspan="3"></td>
                </tr>
            </table>
        </div>

        <h5 class="text-center">B. APPLICATION DETALS</h5>

        <div class="row">
            <table class="leave">
                <tr>
                    <th>Leave Type</th>
                    <td>
                        <select name="" id="">
                            <option value="select"></option>
                            <option value="">Casual</option>
                            <option value="">Sick</option>
                            <option value="">Earned</option>
                        </select>
                    </td>
                    <td><input type="checkbox" name="" value="" /> Other</td>
                    <td colspan="4"><input type="text" /></td>
                </tr>
                <tr>
                    <th>Leave Period</th>
                    <td>From</td>
                    <td><input type="date" /></td>
                    <td>To</td>
                    <td><input type="date" /></td>
                    <td>Total Day(s)</td>
                    <td><input type="text" /></td>
                </tr>
                <tr>
                    <th>Purpose/Remarks</th>
                    <td colspan="6">
                        <input type="text" name="" id="" />
                    </td>
                </tr>
                <tr>
                    <th>Contact Address</th>
                    <td colspan="4">
                        <input type="text" name="" id="" />
                    </td>
                    <td>Phone:</td>
                    <td><input type="text" /></td>
                </tr>
                <tr>
                    <th>Arrangement during leave (person responsible)</th>
                    <td colspan="6">
                        <input type="text" name="" id="" />
                    </td>
                </tr>
                <tr>
                    <th colspan="3" class="text-center">
                        <br />Signature of responsible person
                    </th>
                    <th colspan="3" class="text-center">
                        <br />Signature of the applicant
                    </th>
                </tr>
            </table>
        </div>

        <h5 class="text-center">
            C. RECCOMMENDATION AND APPROVAL OF CONCERNED DEPARTMENT
        </h5>
        <div class="row">
            <table class="leave">
                <tr>
                    <th colspan="3" class="text-center">
                        Recommendation of immediate supervisor
                    </th>
                    <th colspan="3" class="text-center">
                        Approval of the Department Head/In-Charge
                    </th>
                </tr>
                <tr>
                    <th colspan="3" rowspan="3">Comments (if-any):</th>
                    <td>Leave application</td>
                    <td><input type="checkbox" /> Approved</td>
                    <td><input type="checkbox" /> Not Approved</td>
                </tr>
                <tr>
                    <td>Condition (if approved)</td>
                    <td><input type="checkbox" /> With pay</td>
                    <td><input type="checkbox" /> Without pay</td>
                </tr>
                <tr>
                    <td colspan="3">Comments:</td>
                </tr>

                <tr>
                    <td>Date:<input type="date" /></td>
                    <td colspan="2">
                        <br />Signature...........................
                    </td>
                    <td>Date:<input type="date" /></td>
                    <td colspan="2">
                        <br />Signature...........................
                    </td>
                </tr>
            </table>
        </div>
        <h5 class="text-center">C. FOR HUMAN RESOURCE USE</h5>
        <div class="row">
            <table class="leave">
                <tr>
                    <th colspan="2">Leave application</th>
                    <td>
                        <input type="checkbox" /> Approved (As granted in
                        section C)
                    </td>
                    <td><input type="checkbox" /> Not Approved</td>
                </tr>
                <tr>
                    <th colspan="4">Comments (if-any):</th>
                </tr>
                <tr>
                    <td colspan="2">Date: <input type="date" /></td>
                    <td colspan="2" class="text-center">
                        <br /><br />Authorized Signature:
                    </td>
                </tr>
            </table>
        </div>
    </div>
</template>

<style>
.leave th,
.leave td {
    padding-left: 7px;
}
.leave table,
.leave tr,
.leave td {
    border: 1px solid black;
}
.leave select {
    width: 100%;
    border: none;
}
.leave input[type="text"],
.leave input[type="date"] {
    width: 100%;
    border: none;
}
.leave input[type="checkbox"] {
    justify-content: space-between;
}
</style>
