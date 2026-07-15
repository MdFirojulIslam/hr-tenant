<script setup>
import { onMounted } from "vue";
import { useRouter } from "vue-router";
const router = useRouter();

import api from "@/api";

const newSetup = () => {
    const routeData = router.resolve({
        name: "incentiveSetupForm",
    });
    window.open(routeData.href, "_blank");
};

onMounted(() => {});
</script>

<template>
    <div
        class="border rounded container-fluid h-100 d-flex flex-column p-0 m-0"
    >
        <div class="p-2">
            <div class="d-flex justify-content-between">
                <div class="header"><h4>Incentive List</h4></div>
                <div>
                    <button
                        @click="newSetup()"
                        type="button"
                        class="btn btn-success d-flex align-items-center fw-medium shadow-sm"
                    >
                        <i class="fa-solid fa-plus me-2"></i> New Incentive
                        Setup
                    </button>
                </div>
            </div>

            <div class="mt-5">
                <div class="table-responsive shadow-sm bg-light">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr class="text-nowrap text-center">
                                <th>SL</th>
                                <th>INCENTIVE PERIOD</th>
                                <th>INCENTIVE AMOUNT</th>
                                <th>STATUS</th>
                                <th>EXPORT</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="text-nowrap text-center">
                                <td>1</td>
                                <td>June 2026</td>
                                <td>9999999</td>
                                <td>
                                    <button class="">SUBMIT</button
                                    ><button class="ms-1">APPROVED</button
                                    ><button class="ms-1">DISBURSED</button
                                    ><button class="ms-1">PAID</button>
                                </td>
                                <td>
                                    <button>PDF</button>
                                    <button class="ms-1">EXCEL</button>
                                    <Button class="ms-1">WORD</Button>
                                </td>
                                <td>
                                    <button>EDIT</button
                                    ><button class="ms-1">DELETE</button>
                                </td>
                            </tr>
                            <tr class="text-nowrap text-center">
                                <td>2</td>
                                <td>July 2026</td>
                                <td>9999999</td>
                                <td>
                                    <button class="">SUBMIT</button
                                    ><button class="ms-1">APPROVED</button
                                    ><button class="ms-1">DISBURSED</button
                                    ><button class="ms-1">PAID</button>
                                </td>
                                <td>
                                    <button>PDF</button>
                                    <button class="ms-1">EXCEL</button>
                                    <Button class="ms-1">WORD</Button>
                                </td>
                                <td>
                                    <button>EDIT</button
                                    ><button class="ms-1">DELETE</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
