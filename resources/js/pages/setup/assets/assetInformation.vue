<script setup>
import { ref, onMounted, defineAsyncComponent, watch } from "vue";
import api from "@/api";
import Swal from "sweetalert2";
import { Bootstrap4Pagination } from "laravel-vue-pagination";

const assetComponent = defineAsyncComponent(() =>
    import("../assets/components/assetsComponent.vue")
);

const isLoading = ref(true);
const error = ref(null);
const selectedStore = ref(null);
const heading = ref(null);
const filter = ref("");
const uniqueFileds = ref("");

//filter variables
const deviceCodeFilter = ref("all");
const deviceNameFilter = ref("all");
const deviceTypeFilter = ref("all");
const purchaseDateFilter = ref("all");
const serialNumberFilter = ref("all");
const warrantyExpirationFilter = ref("all");
const statusFilter = ref("all");

const assets = ref([]);
const assetId = ref();
const assetModel = ref(false);

const assetOpen = (title) => {
    heading.value = title;
    assetModel.value = true;
};
const holidayClose = () => {
    assetModel.value = false;
    getData();
};

const searchAsset = async () => {
    if (filter.value.length > 0) {
        try {
            const response = await api.get("/search-asset", {
                params: {
                    query: filter.value,
                },
            });
            assets.value = response.data;
        } catch (error) {
            console.error(error);
        }
    } else {
        getData();
    }
};


const getData = async (page = 1) => {
    try {
        const response = await api.get("/asset", {
            params: {
                page,
                device_code: deviceCodeFilter.value,
                device_name: deviceNameFilter.value,
                device_type: deviceTypeFilter.value,
                purchase_date: purchaseDateFilter.value,
                serial_number: serialNumberFilter.value,
                warranty_expiration: warrantyExpirationFilter.value,
                status: statusFilter.value,
            },
        });
        assets.value = response.data;

        const filterParams = {
            device_code: deviceCodeFilter.value,
            device_name: deviceNameFilter.value,
            device_type: deviceTypeFilter.value,
            purchase_date: purchaseDateFilter.value,
            serial_number: serialNumberFilter.value,
            warranty_expiration: warrantyExpirationFilter.value,
            status: statusFilter.value,
        };

        const responsedata = await api.get("/get-column-data", { params: filterParams });
        uniqueFileds.value = responsedata.data
        
    } catch (err) {
        error.value = err.message || "Error fetching data";
    } finally {
        isLoading.value = false;
    }
};

watch(
    [
        deviceCodeFilter,
        deviceNameFilter,
        deviceTypeFilter,
        purchaseDateFilter,
        serialNumberFilter,
        warrantyExpirationFilter,
        statusFilter,
    ],
    () => getData(1)
);

const editAsset = async (id) => {
    try {
        selectedStore.value = assets.value.data.find((asset) => asset.id === id);
        // const response = await api.get(`/asset/${id}`);
        // selectedStore.value = response.data;
        assetId.value = id;
        assetOpen("Update");
    } catch (err) {
        console.error("Error fetching store data for editing:", err);
    }
};

const deleteAsset = async (id) => {
    try {
        const response = await api.delete(`/delete-asset/${id}`);
        console.log(response.data.success);
        if (response.data.success) {
            Swal.fire({
                position: "middle",
                icon: "success",
                title: "Information has been Deleted",
                showConfirmButton: false,
                timer: 1500,
            });
            await getData();
        }
    } catch (err) {
        console.error("Error fetching store data for editing:", err);
    }
};

const submitHandler = async () => {
    await getData();
};
onMounted(() => getData());
</script>

<template>
    <div class="p-5">
        <component v-if="assetModel" :is="assetComponent" :isOpen="assetModel" :editStore="selectedStore"
            :updateinfo="heading" :assetId="assetId" @modal-close="holidayClose" @submit="submitHandler"
            name="first-modal" />
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="mr-auto p-1">
                            <h4 class="card-title m-2">Asset List</h4>
                        </div>
                        <div class="p-2">
                            <button @click="assetOpen('Save')" type="button" class="btn-plus">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>

                        <div class="p-2">
                            <input type="text" class="form-control" placeholder="Search..." v-model.trim="filter"
                                @input="searchAsset" />
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Serial</th>
                                    <th>
                                        <select class="form-control" v-model="deviceCodeFilter">
                                            <option value="all">Device Code</option>
                                            <option v-for="item in uniqueFileds.device_code" :key="item" :value="item">
                                                AD{{ item }}
                                            </option>
                                        </select>
                                    </th>
                                    <th>
                                        <select class="form-control" v-model="deviceNameFilter">
                                            <option value="all">Device Name</option>
                                            <option v-for="item in uniqueFileds.device_name" :key="item" :value="item">
                                                {{ item }}
                                            </option>
                                        </select>
                                    </th>
                                    <th>
                                        <select class="form-control" v-model="deviceTypeFilter">
                                            <option value="all">Device Type</option>
                                            <option v-for="item in uniqueFileds.device_type" :key="item" :value="item">
                                                {{ item }}
                                            </option>
                                        </select>
                                    </th>
                                    <th>
                                        <select class="form-control" v-model="purchaseDateFilter">
                                            <option value="all">Purchase Date</option>
                                            <option v-for="item in uniqueFileds.purchase_date" :key="item" :value="item">
                                                {{ item }}
                                            </option>
                                        </select>
                                    </th>
                                    <th>
                                        <select class="form-control" v-model="serialNumberFilter">
                                            <option value="all">Serial Number</option>
                                            <option v-for="item in uniqueFileds.serial_number" :key="item" :value="item">
                                                {{ item }}
                                            </option>
                                        </select>
                                    </th>
                                    <th>
                                        <select class="form-control" v-model="warrantyExpirationFilter">
                                            <option value="all">Warranty Expiration</option>
                                            <option v-for="item in uniqueFileds.warranty_expiration" :key="item" :value="item">
                                                {{ item }}
                                            </option>
                                        </select>
                                    </th>
                                    <th>
                                        <select class="form-control" v-model="statusFilter">
                                            <option value="all" selected>Status</option>
                                            <option v-for="item in uniqueFileds.status" :key="item" :value="item">
                                                {{ item }}
                                            </option>
                                        </select>
                                    </th>
                                    <th>Operations</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(asset, index) in assets.data" :key="asset.id">
                                    <td>{{ assets.from + index }}</td>
                                    <td>
                                        AD{{ asset ? asset.Device_Code : "" }}
                                    </td>
                                    <td>
                                        {{ asset ? asset.Device_Name : "" }}
                                    </td>
                                    <td>
                                        {{
                                            asset ? asset.device_type.Name : ""
                                        }}
                                    </td>
                                    <td>
                                        {{ asset ? asset.Purchase_Date : "" }}
                                    </td>
                                    <td>
                                        {{ asset ? asset.Serial_Number : "" }}
                                    </td>
                                    <td>
                                        {{ asset ? asset.Warranty_Expiration : "" }}
                                    </td>
                                    <td>
                                        {{ asset ? asset.Status : "" }}
                                    </td>
                                    <td>
                                        <button class="custom-btn btn-15" @click="editAsset(asset.id)">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                        <button class="custom-btn btn-12 mx-2" @click="deleteAsset(asset.id)">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center">
                        <Bootstrap4Pagination :data="assets" :limit="2" @pagination-change-page="getData" />
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>


