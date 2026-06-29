<script setup>
import { onMounted, computed, ref, watch } from "vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

import { useEmployees } from "@/pages/composables/useEmployee";

const selectedEmployee = ref(null);

const props = defineProps({
    deptID: {
        type: [String, Number, null],
        required: true,
    },
    employeeID: {
        type: [String, Number, null],
        default: false,
    },
});

const emit = defineEmits(["update:employeeID"]);

const { employees, loadEmployees } = useEmployees();

watch(
    () => props.deptID,
    (newDeptID) => {
        // selectedEmployee.value = null;
        // emit("update:employeeID", null);
        if (newDeptID) {
            loadEmployees(newDeptID);
        } else {
            employees.value = [];
        }
    },
);

const proxyEmployeeID = computed({
    get() {
        return (
            employees.value.find((d) => d.employee_id == props.employeeID) ||
            null
        );
    },
    set(value) {
        selectedEmployee.value = value;
        emit("update:employeeID", value ? value.employee_id : null);
    },
});

const clearSelection = () => {
    proxyEmployeeID.value = null;
};

onMounted(() => {
    if (props.deptID) {
        loadEmployees(props.deptID);
    }
});
</script>

<template>
    <div class="multiselect-employee-container">
        <Multiselect
            v-model="proxyEmployeeID"
            :options="employees"
            label="employee_name"
            track-by="employee_id"
            :searchable="true"
            :close-on-select="true"
            :show-labels="false"
            :clear-on-select="false"
            :allow-empty="true"
            placeholder="Custom Employee Name"
        />
        <button
            v-if="proxyEmployeeID"
            @click="clearSelection"
            type="button"
            class="clear-btn"
        >
            ✕
        </button>
    </div>
</template>

<style scoped>
.multiselect-employee-container {
    position: relative;
    display: flex;
    align-items: center;
    /* gap: 5px; */
}

.multiselect-input {
    /* flex: 1; */
    width: 100%;
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
}

.clear-btn:hover {
    background: #ac0000;
    color: rgb(244, 238, 238);
}

:deep(.multiselect__select::before) {
    display: none;
}
</style>
