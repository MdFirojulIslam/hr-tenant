<script setup>
import { onMounted, computed, ref, onBeforeUnmount, watch } from "vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

import { outletCodeInfo } from "@/pages/composables/outlets";

// const props = defineProps({
//     distributor_id: [String, Number, null],
//     outlet_code: [String, Number],
// });

const props = defineProps({
    distributor_id: [String, Number, null],
    outlet_code: [String, Number, null],
    outlet_name: [String, null],
});

// const emit = defineEmits(["update:outlet_code"]);

const emit = defineEmits(["update:outlet_code", "update:outlet_name"]);

const { dmsOutletCodeInfo, loadDmsOutletCode, searchDmsOutletCode, destroy } =
    outletCodeInfo();

let searchTimer = null;

const handleSearchChange = (query) => {
    if (searchTimer) clearTimeout(searchTimer);

    if (!query || !props.distributor_id) return;

    searchTimer = setTimeout(() => {
        searchDmsOutletCode(props.distributor_id, query);
    }, 500);
};

// const proxy_outlet_code = computed({
//     get: () => {
//         return (
//             dmsOutletCodeInfo.value.find(
//                 (d) => d.outlet_code === props.outlet_code,
//             ) || null
//         );
//     },
//     set: (value) => {
//         emit("update:outlet_code", value ? value.outlet_code : null);
//     },
// });

const proxy_outlet = computed({
    get: () => {
        return (
            dmsOutletCodeInfo.value.find(
                (d) => String(d.outlet_code) === String(props.outlet_code),
            ) || null
        );
    },

    set: (value) => {
        emit("update:outlet_code", value ? value.outlet_code : null);

        emit("update:outlet_name", value ? value.outlet_name : null);
    },
});

// onMounted(() => {
//     loadDmsOutletCode(props.distributor_id);
// });

watch(
    () => props.distributor_id,
    (newDistributorId) => {
        if (newDistributorId) {
            loadDmsOutletCode(newDistributorId);
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    destroy();
    if (searchTimer) clearTimeout(searchTimer);
});
</script>

<template>
    <div class="multiselect-department-container">
        <Multiselect
            v-model="proxy_outlet"
            :options="dmsOutletCodeInfo"
            label="outlet_code"
            track-by="outlet_id"
            :searchable="true"
            :close-on-select="true"
            :clear-on-select="false"
            :show-labels="false"
            :allow-empty="true"
            :loading="loading"
            @search-change="handleSearchChange"
            placeholder="Outlet Code"
            class="multiselect-outlet-code"
        />
    </div>
</template>

<style scoped>
.multiselect-department-container {
    padding: 2px;
    position: relative;
    display: flex;
    align-items: center;
    /* gap: 5px; */
}

.multiselect-outlet-code {
    /* flex: 1; */
    width: 100%;
}

.multiselect-outlet-code :deep(.multiselect__select) {
    width: 30px;
    height: 30px;
    right: 1px;
    top: 1px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ৩. Placeholder Text */
.multiselect-outlet-code :deep(.multiselect__placeholder) {
    color: #6c757d;
    font-size: 12px;
    margin-bottom: 0;
    padding-top: 2px;
}
.multiselect-outlet-code :deep(.multiselect__tags) {
    min-height: 32px !important;
    padding: 4px 30px 0 10px;
    border-radius: 6px;
    background: #ffffff;
    font-size: 12px;
}

.clear-btn:hover {
    background: #ac0000;
    color: rgb(244, 238, 238);
}

:deep(.multiselect__select::before) {
    display: none;
}
</style>
