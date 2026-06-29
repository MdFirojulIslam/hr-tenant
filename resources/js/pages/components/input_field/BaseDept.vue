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

        <button
            v-if="proxyDepartmentId"
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
