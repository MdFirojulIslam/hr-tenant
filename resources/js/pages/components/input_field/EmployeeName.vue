<script setup>
import { onMounted, computed, ref, onBeforeUnmount } from "vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

import { employeeInfo } from "@/pages/composables/employees";

const props = defineProps({
    employee_id: {
        type: [String, Number],
        default: null,
    },
});

const emit = defineEmits(["update:employee_id"]);

// const { departments, loadDepartments, destroy } = useDepartment();
const { employee_info, loading, destroy, loadEmployeeInfo } = employeeInfo();

const employee_proxy_info = computed({
    get: () => {
        return (
            employee_info.value.find(
                (d) => Number(d.employee_id) === Number(props.employee_id),
            ) || null
        );
    },
    set: (value) => {
        emit("update:employee_id", value ? value.employee_id : null);
    },
});

const clearSelection = () => {
    employee_proxy_info.value = null;
};

// const distributor_proxy_info = computed({
//     get: () => {
//         return (
//             distributors_info.value.find(
//                 (d) => Number(d.id) === Number(props.distributor_id),
//             ) || null
//         );
//     },

//     set: (value) => {
//         emit("update:distributor_id", value ? value.id : null);
//     },
// });

onMounted(() => {
    loadEmployeeInfo();
});

onBeforeUnmount(() => {
    destroy();
});
</script>

<template>
    <div class="multiselect-department-container">
        <Multiselect
            v-model="employee_proxy_info"
            :options="employee_info"
            label="employee_name"
            track-by="employee_id"
            :searchable="true"
            :close-on-select="true"
            :clear-on-select="false"
            :show-labels="false"
            :allow-empty="true"
            :loading="loading"
            placeholder="Employee Name"
            class="multiselect-employee-name"
        />
        <button
            v-if="employee_proxy_info"
            @click="clearSelection"
            type="button"
            class="clear-btn"
        >
            ✕
        </button>
    </div>
</template>

<style scoped>
.multiselect-department-container {
    position: relative;
    display: flex;
    align-items: center;
    /* gap: 5px; */
}

.multiselect-employee-name {
    /* flex: 1; */
    width: 290px;
}

:deep(.multiselect__tags) {
    min-height: 33px !important;
    height: 33px !important;
    padding: 5px 40px 0 8px !important;
}

:deep(.multiselect__single) {
    margin-bottom: 0 !important;
    line-height: 22px !important;
}

:deep(.multiselect__input) {
    margin-bottom: 0 !important;
    padding: 0 !important;
}

:deep(.multiselect__content-wrapper) {
    max-height: 300px !important;
}
:deep(.multiselect__placeholder) {
    color: #000000 !important;
    font-size: 14px;
    padding-top: 0px;
}

.clear-btn {
    position: absolute;
    right: 10px;
    background: #949894;
    padding: 3px;
    cursor: pointer;
    border-radius: 4px;
    font-size: 14px;
    color: #ffffff;
    height: 25px;
    top: 3px;
}

.clear-btn:hover {
    background: #ac0000;
    color: rgb(244, 238, 238);
}

:deep(.multiselect__select::before) {
    display: none;
}
</style>
