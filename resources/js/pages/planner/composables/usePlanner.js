import { ref } from "vue";
import api from "@/api";
import store from "@/store/store";

export function usePlanner() {
    const tasks = ref([]);
    const employees = ref([]);
    const loading = ref(false);
    const saving = ref(false);
    const savingError = ref(null);

    const currentEid = ref(
        store.getters.getUser?.id ?? store.getters.getUser?.EID ?? null,
    );

    const VALUES = {
        statuses: ["Not started", "In progress", "Completed"],
        repeats: ["Do not repeat", "Daily", "Custom"],
        buckets: [
            "No bucket",
            "Backlog",
            "Up next",
            "In progress",
            "Blocked",
            "Completed",
        ],
        priorities: ["Urgent", "Important", "Medium", "Low"],
        privacies: ["Public", "Private", "Shared", "Others"],
    };

    function initials(name) {
        if (!name) return "";
        const parts = name.trim().split(/\s+/);
        if (parts.length >= 2) {
            return (parts[0][0] || "") + (parts[1][0] || "");
        }
        return (name.trim()[0] || "") + (name.trim()[1] || "");
    }

    function formatDate(value) {
        if (!value) return "";
        return value;
    }

    function isOverdue(task) {
        if (!task.due_date) return false;
        if (task.status === "Completed") return false;
        const today = new Date().toISOString().slice(0, 10);
        return task.due_date < today;
    }

    async function loadTasks(params = {}) {
        loading.value = true;
        savingError.value = null;
        try {
            const res = await api.get("/planner/tasks", { params });
            tasks.value = res.data?.data || [];
            return tasks.value;
        } catch (err) {
            savingError.value = err.response?.data?.message || err.message;
            return [];
        } finally {
            loading.value = false;
        }
    }

    async function loadEmployees() {
        try {
            const res = await api.get("/planner/tasks/employees");
            employees.value = res.data?.data || [];
            return employees.value;
        } catch (err) {
            savingError.value = err.response?.data?.message || err.message;
            return [];
        }
    }

    function normalizeTask(payload) {
        return payload;
    }

    async function createTask(data) {
        saving.value = true;
        savingError.value = null;
        try {
            const res = await api.post("/planner/tasks", data);
            const created = normalizeTask(res.data?.data);
            return created;
        } catch (err) {
            savingError.value = err.response?.data?.message || err.message;
            throw err;
        } finally {
            saving.value = false;
        }
    }

    async function updateTask(id, data) {
        saving.value = true;
        savingError.value = null;
        try {
            const res = await api.patch(`/planner/tasks/${id}`, data);
            const updated = normalizeTask(res.data?.data);
            const idx = tasks.value.findIndex((t) => t.id === id);
            if (idx !== -1) {
                tasks.value[idx] = updated;
            }
            return updated;
        } catch (err) {
            savingError.value = err.response?.data?.message || err.message;
            throw err;
        } finally {
            saving.value = false;
        }
    }

    async function deleteTask(id) {
        saving.value = true;
        savingError.value = null;
        try {
            await api.delete(`/planner/tasks/${id}`);
            tasks.value = tasks.value.filter((t) => t.id !== id);
            return true;
        } catch (err) {
            savingError.value = err.response?.data?.message || err.message;
            throw err;
        } finally {
            saving.value = false;
        }
    }

    async function saveAssignees(taskId, assignees) {
        const res = await api.post(`/planner/tasks/${taskId}/assignees`, {
            assignees,
        });
        const resmail = await api.post(
            `/planner/tasks/mail/${taskId}/assignees`,
            { assignees },
        );
        return res.data?.data || [];
    }

    async function removeAssignee(taskId, userId) {
        await api.delete(`/planner/tasks/${taskId}/assignees/${userId}`);
        return true;
    }

    async function addChecklist(taskId, checklistName) {
        const res = await api.post(`/planner/tasks/${taskId}/checklists`, {
            checklist_name: checklistName,
        });
        return res.data?.data;
    }

    async function updateChecklist(taskId, checklistId, data) {
        const res = await api.patch(
            `/planner/tasks/${taskId}/checklists/${checklistId}`,
            data,
        );
        return res.data?.data;
    }

    async function deleteChecklist(taskId, checklistId) {
        await api.delete(`/planner/tasks/${taskId}/checklists/${checklistId}`);
        return true;
    }

    async function markRead(taskId) {
        try {
            await api.post(`/planner/tasks/${taskId}/read`);
        } catch (err) {
            // non-fatal
        }
    }

    function buildNewTask() {
        const today = new Date().toISOString().slice(0, 10);
        const week = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000)
            .toISOString()
            .slice(0, 10);
        return {
            id: null,
            task_name: "",
            start_date: today,
            due_date: week,
            new_due_date: null,
            status: "Not started",
            repeat: "Do not repeat",
            bucket: "No bucket",
            priority: "Medium",
            privacy: "Private",
            notes: null,
            assignees: [],
            checklists: [],
            checklist_progress: { completed: 0, total: 0 },
            my_read_status: false,
            is_new: true,
            saving_state: "",
        };
    }

    return {
        tasks,
        employees,
        loading,
        saving,
        savingError,
        currentEid,
        VALUES,
        initials,
        formatDate,
        isOverdue,
        loadTasks,
        loadEmployees,
        createTask,
        updateTask,
        deleteTask,
        saveAssignees,
        removeAssignee,
        addChecklist,
        updateChecklist,
        deleteChecklist,
        markRead,
        buildNewTask,
        normalizeTask,
    };
}
