import { ref } from "vue";
import { getEmployee } from "@/pages/services/apiServices";

export function employeeInfo() {
    const employee_info = ref([]);
    const loading = ref(false);
    const error = ref(null);

    let is_active = true;

    function destroy() {
        is_active = false;
    }

    async function loadEmployeeInfo() {
        loading.value = true;
        error.value = null;

        try {
            const res = await getEmployee();

            if (!is_active) return;

            employee_info.value = res.data;
        } catch (err) {
            if (!is_active) return;

            error.value = err;
            employee_info.value = [];
        } finally {
            if (!is_active) return;

            loading.value = false;
        }
    }

    return {
        employee_info,
        loading,
        error,
        destroy,
        loadEmployeeInfo,
    };
}
