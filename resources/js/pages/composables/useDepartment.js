// ref() computed() watch() loading debounce pagination search business rule

import { ref } from "vue";
import { getDepartments } from "@/pages/services/apiServices";

export function useDepartment() {
    const departments = ref([]);
    const loading = ref(false);
    const error = ref(null);

    let isActive = true;

    function destroy() {
        isActive = false;
    }

    async function loadDepartments(userID) {
        loading.value = true;

        try {
            const res = await getDepartments(userID);
            if (!isActive) return;
            departments.value = res.data;
        } catch (err) {
            if (!isActive) return;
            error.value = err;
        } finally {
            if (!isActive) return;
            loading.value = false;
        }
    }
    return { departments, loading, error, destroy, loadDepartments };
}
