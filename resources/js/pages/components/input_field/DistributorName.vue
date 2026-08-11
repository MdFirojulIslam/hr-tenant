<script setup>
import { onMounted, computed, onBeforeUnmount } from "vue";

import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

import { dmsDistributor } from "@/pages/composables/distributor";

const props = defineProps({
    distributor_id: {
        type: [String, Number],
        default: null,
    },
});

const emit = defineEmits(["update:distributor_id"]);

const { distributors_info, loading, destroy, loadDmsDistributorInfo } =
    dmsDistributor();

const distributor_proxy_info = computed({
    get: () => {
        return (
            distributors_info.value.find(
                (d) =>
                    Number(d.distributor_id) === Number(props.distributor_id),
            ) || null
        );
    },

    set: (value) => {
        emit("update:distributor_id", value ? value.distributor_id : null);
    },
});

onMounted(() => {
    loadDmsDistributorInfo();
});

onBeforeUnmount(() => {
    destroy();
});
</script>

<template>
    <div class="multiselect-department-container">
        <Multiselect
            v-model="distributor_proxy_info"
            :options="distributors_info"
            label="name"
            track-by="distributor_id"
            :searchable="true"
            :close-on-select="true"
            :clear-on-select="false"
            :show-labels="false"
            :allow-empty="true"
            :loading="loading"
            placeholder="Distributor Name"
            class="multiselect-distributor-name"
        />
    </div>
</template>

<style scoped>
.multiselect-department-container {
    position: relative;
    display: flex;
    align-items: center;
    /* gap: 5px; */
}

.multiselect-distributor-name {
    /* flex: 1; */
    width: 280px;
}

.multiselect-distributor-name :deep(.multiselect__select) {
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
.multiselect-distributor-name :deep(.multiselect__placeholder) {
    color: #6c757d;
    font-size: 12px;
    margin-bottom: 0;
    padding-top: 2px;
}
.multiselect-distributor-name :deep(.multiselect__tags) {
    min-height: 35px !important;
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
