import { ref } from "vue";
import { getEmployeesByDept } from "@/pages/services/apiServices";

export function useEmployees() {
    const employees = ref([]);
    const loading = ref(false);
    const error = ref(null);

    async function loadEmployees(deptID) {
        loading.value = true;

        try {
            const res = await getEmployeesByDept(deptID);
            employees.value = res.data;
        } catch (err) {
            error.value = err;
        } finally {
            loading.value = false;
        }
    }

    return { employees, loading, error, loadEmployees };
}
