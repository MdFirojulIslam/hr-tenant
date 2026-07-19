<script setup>
import { onMounted, computed, ref, onBeforeUnmount } from "vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.min.css";

import { useDepartment } from "@/pages/composables/useDepartment";

const props = defineProps({
    departmentID: [String, Number, null],
    userID: [String, Number],
});

const emit = defineEmits(["update:departmentID"]);

const { departments, loadDepartments, destroy } = useDepartment();

const proxyDepartmentId = computed({
    get: () => {
        return (
            departments.value.find(
                (d) => d.department_id === props.departmentID,
            ) || null
        );
    },
    set: (value) => {
        emit("update:departmentID", value ? value.department_id : null);
    },
});

const clearSelection = () => {
    proxyDepartmentId.value = null;
};

onMounted(() => {
    loadDepartments(props.userID);
});

onBeforeUnmount(() => {
    destroy();
});
</script>

<template>
    <div class="multiselect-department-container">
        <Multiselect
            v-model="proxyDepartmentId"
            :options="departments"
            label="department_name"
            track-by="department_id"
            :searchable="true"
            :close-on-select="true"
            :clear-on-select="false"
            :show-labels="false"
            :allow-empty="true"
            placeholder="Select Department Name"
            class="multiselect-input"
        />
    </div>
</template>
<!-- v-model="selectedDepartments" :options="departments" :multiple="true"
label="department_name" track-by="department_id" :searchable="true"
:close-on-select="false" :clear-on-select="false" :preserve-search="true"
:placeholder=" isLoadingDepartments ? 'Loading departments...' : 'Select
departments' " :disabled="isLoadingDepartments" class="multiselect-design" -->
<style scoped>
.multiselect-department-container {
    position: relative;
    display: flex;
    align-items: center;
    /* gap: 5px; */
}

.multiselect-input {
    /* flex: 1; */
    width: 100%;
}

:deep(.multiselect__select::before) {
    display: none;
}
</style>
