<template>
    <div class="wrapper">
        <div class="content-wrapper">
            <div class="card pb-3 mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-center mb-3">
                        <h2>Fetch Attendance data</h2>
                    </div>
                    <div
                        class="row d-flex justify-content-center align-items-center"
                    >
                        <div class="col-auto">
                            <button
                                class="custom-btn btn-12 p-2"
                                @click="fetchMachineAttendence"
                            >
                                Fetch Machine Attendence
                            </button>
                        </div>
                        <div class="col-auto">
                            <button
                                class="custom-btn btn-13 p-2"
                                @click="fetchAttendence"
                            >
                                Fetch Attendence
                            </button>
                        </div>

                        <div v-if="loading" class="overlay-loading">
                            <div class="loader"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card pb-3 mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-center mb-3">
                        <h2>Daily Attendance Email</h2>
                    </div>
                    <div
                        class="row d-flex justify-content-center align-items-center"
                    >
                        <div class="col-auto">
                            <input
                                type="date"
                                class="form-control"
                                v-model="date"
                                required
                            />
                            <small v-if="!date" class="text-danger"
                                >* You must select a date</small
                            >
                        </div>
                        <div class="col-auto">
                            <button
                                class="custom-btn btn-15 p-2"
                                @click="sendAttendenceMail"
                                :disabled="!date"
                            >
                                Send Attendence Mail
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card pb-3 mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-center mb-3">
                        <h2>Monthly Attendance Email</h2>
                    </div>
                    <div
                        class="row d-flex justify-content-center align-items-center mb-3"
                    >
                        <div class="col-auto">
                            <label for="">Select a Year</label>
                            <select class="form-control" v-model="selectedYear">
                                <option
                                    v-for="(year, index) in years"
                                    :key="year"
                                    :value="year"
                                >
                                    {{ year }}
                                </option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <label for="">Select a Month</label>
                            <select
                                class="form-control"
                                v-model="selectedMonth"
                            >
                                <option
                                    v-for="(month, index) in months"
                                    :key="month"
                                    :value="index"
                                >
                                    {{ month }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div
                        class="row d-flex justify-content-center align-items-center"
                    >
                        <div class="col-auto">
                            <button
                                class="custom-btn btn-13 p-2"
                                @click="sendMonthlyAttendence"
                            >
                                Monthly Attendence
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import Swal from "sweetalert2";
import api from "@/api";
const loading = ref(false);
const date = ref(new Date().toISOString().split("T")[0]);
const selectedMonth = ref(new Date().getMonth());
const selectedYear = ref(new Date().getFullYear());

const months = ref([
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
]);

const years = ref([
    "2022",
    "2023",
    "2024",
    "2025",
    "2026",
    "2027",
    "2028",
    "2029",
    "2030",
]);

const fetchAttendence = async () => {
    loading.value = true;
    try {
        const response = await api.get("/fetch-attendence");
        // window.open(`/api/noc/${216}`, "_blank");
    } catch (error) {
        console.error("Error fetching data:", error);
    } finally {
        loading.value = false;
        Swal.fire({
            position: "middle",
            icon: "success",
            title: "Attendence Successfully Fetched",
            showConfirmButton: false,
            timer: 1500,
        });
    }
};

const fetchMachineUser = async () => {
    loading.value = true;
    try {
        const response = await api.get("/fetch-zkt-user");
    } catch (error) {
        console.error("Error fetching data:", error);
    } finally {
        loading.value = false;
    }
};

const sendAttendenceMail = async () => {
    try {
        const result = await Swal.fire({
            title: "Are you sure?",
            text: "Do you want to send this email?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes",
            cancelButtonText: "No",
            reverseButtons: true,
        });

        if (result.isConfirmed) {
            const response = await api.get("/attendance-mail", {
                params: { date: date.value },
            });
            if (response.data.success) {
                Swal.fire({
                    position: "middle",
                    icon: "success",
                    title: "Email Send Successfully",
                    showConfirmButton: false,
                    timer: 1500,
                });
            }
        }
    } catch (error) {
        console.error("Error sending mail", error);
    }
};

const sendMonthlyAttendence = async () => {
    try {
        const result = await Swal.fire({
            title: "Are you sure?",
            text: "Do you want to send this email?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes",
            cancelButtonText: "No",
            reverseButtons: true,
        });

        if (result.isConfirmed) {
            const response = await api.get("/monthly-attendance", {
                params: {
                    month: selectedMonth.value,
                    year: selectedYear.value,
                },
            });
            if (response.data.success) {
                Swal.fire({
                    position: "middle",
                    icon: "success",
                    title: "Email Send Successfully",
                    showConfirmButton: false,
                    timer: 1500,
                });
            }
        }
    } catch (error) {
        console.error("Error sending mail", error);
    }
};

const fetchMachineAttendence = async () => {
    loading.value = true;
    try {
        const response = await api.get("/fetch-zkt-attendence");
        if (response.data.success) {
            Swal.fire({
                position: "middle",
                icon: "success",
                title: "Machine Attendence Successfully Fetched",
                showConfirmButton: false,
                timer: 1500,
            });
        }
    } catch (error) {
        console.error("Error fetching data:", error);
        Swal.fire({
            position: "middle",
            icon: "error",
            title: "Error fetching attendance data",
            text: error.message || "An unexpected error occurred.",
            showConfirmButton: true,
        });
    } finally {
        loading.value = false; // Ensure loading is stopped
    }
};
</script>

<style scoped>
.wrapper {
    display: flex;
    justify-content: center;
    height: 100vh;
    /* Full viewport height to center vertically */
}

.content-wrapper {
    width: 100%;
    /* Optional: can keep the width or remove */
    max-width: 400px;
    /* Define max width to control the card width */
}
</style>
