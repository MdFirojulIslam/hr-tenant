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
    width: 300px;
}

.multiselect-employee-name :deep(.multiselect__select) {
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
.multiselect-employee-name :deep(.multiselect__placeholder) {
    color: #6c757d;
    font-size: 12px;
    margin-bottom: 0;
    padding-top: 2px;
}
.multiselect-employee-name :deep(.multiselect__tags) {
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
