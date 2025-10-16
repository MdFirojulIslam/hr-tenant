<script setup>
import { ref, onMounted, computed } from "vue";
import { useRoute } from "vue-router";
import api from "@/api";

const data = ref({});
const route = useRoute();
const fallbackImage = "/storage/uploads/test-image.jpg";

// Fetch employee data by ID
const getData = async (id) => {
    try {
        const response = await api.get(
            `/employee-salary-profile-information/${id}`
        );
        data.value = response.data;
    } catch (error) {
        console.error("Error fetching salary profile:", error);
    }
};

// Computed property for image source with fallback
const imgSrc = computed(() => {
    if (!data.value.img_url || data.value.img_url === "") return fallbackImage;
    return data.value.img_url.startsWith("/storage/")
        ? data.value.img_url
        : `/storage/${data.value.img_url}`;
});

// Handle broken image
const onImgError = (event) => {
    event.target.src = fallbackImage;
};

// Fetch data on mount
onMounted(() => {
    getData(route.params.id);
});
</script>

<template>
    <div class="container-fluid h-100 d-flex flex-column p-0 m-0">
        <!-- Header -->
        <div
            class="d-flex justify-content-between align-items-center py-2 px-3"
        >
            <h2 class="m-0">
                {{
                    data.Full_Name
                        ? data.Full_Name + "'s Salary Profile"
                        : "Employee Salary Profile"
                }}
            </h2>
        </div>

        <!-- Main Content -->
        <div class="col-lg-12 mt-3" v-if="data.Full_Name">
            <div class="p-3 mb-3 d-flex flex-wrap">
                <div
                    class="col-lg-6 col-md-6 col-sm-12 mb-3 d-flex align-items-center"
                >
                    <!-- Employee Image -->
                    <img
                        :src="imgSrc"
                        alt="Employee Image"
                        class="rounded-circle me-3"
                        style="width: 150px; height: 150px; object-fit: cover"
                        @error="onImgError"
                    />

                    <!-- Employee Info -->
                    <div class="d-flex flex-column">
                        <div class="d-flex mb-1">
                            <div class="fw-bold" style="width: 90px">
                                Full Name
                            </div>
                            <div style="width: 10px">:</div>
                            <div>{{ data.Full_Name || "-" }}</div>
                        </div>

                        <!-- Employee ID -->
                        <div class="d-flex mb-1">
                            <div class="fw-bold" style="width: 90px">
                                Employee ID
                            </div>
                            <div style="width: 10px">:</div>
                            <div>{{ data.Employee_Id || "-" }}</div>
                        </div>

                        <!-- Designation -->
                        <div class="d-flex mb-1">
                            <div class="fw-bold" style="width: 90px">
                                Designation
                            </div>
                            <div style="width: 10px">:</div>
                            <div>{{ data.designationName || "-" }}</div>
                        </div>

                        <!-- Department -->
                        <div class="d-flex mb-1">
                            <div class="fw-bold" style="width: 90px">
                                Department
                            </div>
                            <div style="width: 10px">:</div>
                            <div>{{ data.departmentName || "-" }}</div>
                        </div>
                    </div>
                </div>

                <!-- Employee Info -->
                <div class="col-lg-6 col-md-7 col-sm-6">
                    <div class="card mb-3">
                        <div class="card-body p-2">
                            <div class="row">
                                <!-- Full-width heading -->
                                <div class="col-12 text-center mb-3">
                                    <h4 class="text-success">
                                        Primary Information
                                    </h4>
                                </div>

                                <!-- Bank Account -->
                                <div class="col-md-6 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Bank Account No
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>{{ data.bank_account_no || "-" }}</div>
                                </div>

                                <div class="col-md-6 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Salary Type
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>{{ data.total_deduction || "-" }}</div>
                                </div>

                                <div class="col-md-6 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Gross Salary
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>{{ data.total_earning || "-" }}</div>
                                </div>

                                <div class="col-md-6 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Total Earnings
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>{{ data.total_earning || "-" }}</div>
                                </div>

                                <div class="col-md-6 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Basic
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>{{ data.basic || "-" }}</div>
                                </div>

                                <div class="col-md-6 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Total Deduction
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>{{ data.total_deduction || "-" }}</div>
                                </div>
                                <!-- Basic -->

                                <!-- Total Payment -->
                                <div class="col-md-6 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Total Payment
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>{{ data.total_payment || "-" }}</div>
                                </div>

                                <!-- Net Pay -->
                                <div class="col-md-6 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Net Pay
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>{{ data.net_pay || "-" }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="class=col-lg-10 col-md-10 col-sm-10 mx-auto">
                    <div class="mt-5">
                        <div class="p-2">
                            <div class="row">
                                <!-- Full-width heading -->
                                <div class="col-12 text-left mb-3">
                                    <h4 class="text-success">
                                        {{ data.Full_Name }}'s Other Information
                                    </h4>
                                </div>

                                <!-- Bank Account -->
                                <div class="col-md-4 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        House Rent
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>{{ data.house_rent || "-" }}</div>
                                </div>

                                <!-- Salary Type -->
                                <div class="col-md-4 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Conveyance
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>{{ data.conveyance || "-" }}</div>
                                </div>

                                <!-- Gross Salary -->
                                <div class="col-md-4 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Medical Allowance
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>
                                        {{ data.medical_allowance || "-" }}
                                    </div>
                                </div>

                                <!-- Total Earnings -->
                                <div class="col-md-4 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Consolidated
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>{{ data.consolidated || "-" }}</div>
                                </div>

                                <!-- Basic -->
                                <div class="col-md-4 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Festival Bonus
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>{{ data.festival_bonus || "-" }}</div>
                                </div>

                                <!-- Total Deduction -->
                                <div class="col-md-4 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Travel Allowance
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>
                                        {{ data.travel_allowance || "-" }}
                                    </div>
                                </div>

                                <!-- Total Payment -->
                                <div class="col-md-4 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Overtime
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>{{ data.overtime || "-" }}</div>
                                </div>

                                <!-- Net Pay -->
                                <div class="col-md-4 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Daily Allowance
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>{{ data.daily_allowance || "-" }}</div>
                                </div>

                                <div class="col-md-4 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Mobile Bill Allowance
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>{{ data.mobile_bill || "-" }}</div>
                                </div>
                                <div class="col-md-4 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Other Allowance
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>{{ data.other_allowance || "-" }}</div>
                                </div>
                                <div class="col-md-4 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Provident Fund
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>{{ data.provident_fund || "-" }}</div>
                                </div>
                                <div class="col-md-4 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Income Tax
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>{{ data.income_tax || "-" }}</div>
                                </div>
                                <div class="col-md-4 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Revenue Stamp
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>{{ data.revenue_stamp || "-" }}</div>
                                </div>
                                <div class="col-md-4 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Leave Without Pay
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>
                                        {{ data.leave_without_pay || "-" }}
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Penulty/Other deduction
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>
                                        {{
                                            data.penulty_other_deduction || "-"
                                        }}
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex mb-2">
                                    <div style="width: 140px" class="fw-bold">
                                        Advance
                                    </div>
                                    <div style="width: 10px">:</div>
                                    <div>{{ data.advance || "-" }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading state -->
        <div v-else class="text-center py-5">
            <p>Loading employee salary profile...</p>
        </div>
    </div>
</template>
