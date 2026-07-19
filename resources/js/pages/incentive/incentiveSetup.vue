<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import api from "@/api";
import Swal from "sweetalert2";

const router = useRouter();

const incentives = ref([]);
const isLoading = ref(true);

const fetchIncentives = async () => {
    isLoading.value = true;
    try {
        const res = await api.get("/incentive-list");
        incentives.value = res.data;
    } catch (err) {
        console.error("Error fetching incentives:", err);
    } finally {
        isLoading.value = false;
    }
};

const newSetup = () => {
    const routeData = router.resolve({
        name: "incentiveSetupForm",
    });
    window.open(routeData.href, "_blank");
};

const editIncentive = (id) => {
    const routeData = router.resolve({
        name: "incentiveSetupFormEdit",
        query: { id },
    });
    window.open(routeData.href, "_blank");
};

const exportFile = (id, format) => {
    const token = localStorage.getItem("token");
    const url = `/api/incentive-export/${id}/${format}`;
    fetch(url, {
        headers: {
            Authorization: `Bearer ${token}`,
        },
    })
        .then((res) => {
            if (!res.ok) throw new Error("Export failed");
            return res.blob();
        })
        .then((blob) => {
            const ext = format === "word" ? "docx" : format;
            const a = document.createElement("a");
            a.href = URL.createObjectURL(blob);
            a.download = `Incentive_Report.${ext}`;
            a.click();
            URL.revokeObjectURL(a.href);
        })
        .catch(() => {
            Swal.fire({
                icon: "error",
                title: "Export Failed",
                text: "Could not generate the file. Please try again.",
            });
        });
};

const statusBadgeClass = (status) => {
    const map = {
        Pending: "bg-warning text-dark",
        Approved: "bg-info text-dark",
        Disbursed: "bg-primary",
        Paid: "bg-success",
    };
    return map[status] || "bg-secondary";
};

onMounted(() => {
    fetchIncentives();
});
</script>

<template>
    <div
        class="border rounded container-fluid h-100 d-flex flex-column p-0 m-0"
    >
        <div class="p-3">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="m-0 header-design d-flex align-items-center">
                    <i
                        class="doller-icon fa-solid fa-hand-holding-dollar me-2"
                    ></i>
                    Incentive List
                </h2>
                <button
                    @click="newSetup()"
                    type="button"
                    class="btn btn-success d-flex align-items-center fw-medium shadow-sm"
                >
                    <i class="fa-solid fa-plus me-2"></i> New Incentive Setup
                </button>
            </div>

            <div class="mt-4">
                <div v-if="isLoading" class="text-center py-5">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading incentives...</p>
                </div>

                <div
                    v-else-if="incentives.length === 0"
                    class="text-center py-5 text-muted"
                >
                    <i class="fa-solid fa-inbox fa-3x mb-3 d-block"></i>
                    <p>No incentive records found.</p>
                </div>

                <div v-else class="table-responsive shadow-sm bg-light">
                    <table class="table table-bordered table-sm table-hover">
                        <thead class="table-dark">
                            <tr class="text-nowrap text-center">
                                <th style="width: 4%">SL</th>
                                <th>INCENTIVE PERIOD</th>
                                <th>TOTAL AMOUNT</th>
                                <th>EMPLOYEES COUNT</th>
                                <th>STATUS</th>
                                <th style="width: 14%">EXPORT</th>
                                <th style="width: 10%">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(incentive, index) in incentives"
                                :key="incentive.id"
                                class="text-nowrap text-center"
                            >
                                <td>{{ index + 1 }}</td>
                                <td class="fw-medium">
                                    {{ incentive.incentive_periods }}
                                </td>
                                <td>
                                    {{
                                        Number(
                                            incentive.incentive_amount,
                                        ).toLocaleString()
                                    }}
                                </td>
                                <td>{{ incentive.details_count }}</td>
                                <td>
                                    <span
                                        class="badge px-3 py-2"
                                        :class="
                                            statusBadgeClass(incentive.status)
                                        "
                                    >
                                        {{ incentive.status }}
                                    </span>
                                </td>
                                <td>
                                    <div
                                        class="d-flex justify-content-center gap-1"
                                    >
                                        <button
                                            class="btn btn-sm btn-outline-danger"
                                            title="Download PDF"
                                            @click="
                                                exportFile(incentive.id, 'pdf')
                                            "
                                        >
                                            PDF
                                        </button>
                                        <button
                                            class="btn btn-sm btn-outline-primary"
                                            title="Download Word"
                                            @click="
                                                exportFile(incentive.id, 'word')
                                            "
                                        >
                                            Word
                                        </button>
                                        <button
                                            class="btn btn-sm btn-outline-success"
                                            title="Download Excel"
                                            @click="
                                                exportFile(incentive.id, 'xlsx')
                                            "
                                        >
                                            Excel
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <div
                                        class="d-flex justify-content-center gap-1"
                                    >
                                        <button
                                            class="btn btn-sm btn-outline-secondary"
                                            title="View Details"
                                        >
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button
                                            class="btn btn-sm btn-outline-dark"
                                            title="Edit"
                                            @click="editIncentive(incentive.id)"
                                        >
                                            <i
                                                class="fa-solid fa-pen-to-square"
                                            ></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.doller-icon {
    font-size: 18px;
    line-height: 1;
    margin-right: 4px;
}
.header-design {
    display: inline-flex;
    align-items: center;
    letter-spacing: 1px;
    padding-top: 5px;
    color: #000000;
}

.table-responsive .table,
.table-responsive .table tbody,
.table-responsive .table tr,
.table-responsive .table td {
    background: #ffffffd5 !important;
    color: #000000;
    border: 0.8px solid #7e7c7c63;
    letter-spacing: 0.3px;
}

.table tbody tr:hover > td {
    background-color: #717c73c7 !important;
}

.table thead th {
    font-weight: 700;
}

.table thead {
    height: 30px;
}
</style>
