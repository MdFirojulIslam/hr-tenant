<script setup>
import { ref } from "vue";
import api from "@/api";

const props = defineProps({
    dailyAttendance: Boolean,
});

const form = ref({
    startDate: "",
    endDate: "",
});

const syncDailyAttendance = async () => {
    try {
        const res = await api.get("/daily-attendance-sync", {
            params: {
                startDate: form.value.startDate,
                endDate: form.value.endDate,
            },
        });
    } catch (err) {
        console.error("Error syncing daily attendance:", err);
    } finally {
        closeModal();
    }
};

const emit = defineEmits(["close"]);

const startDate = ref("");
const endDate = ref("");

const closeModal = () => {
    emit("close");
};
</script>

<template>
    <div
        v-if="props.dailyAttendance"
        class="modal-overlay"
        @click.self="closeModal"
    >
        <div class="modal-content">
            <div class="modal-header">
                <h4>Select Date Range</h4>
                <button
                    class="btn-close-x"
                    @click="closeModal"
                    aria-label="Close modal"
                >
                    &times;
                </button>
            </div>

            <div class="modal-body">
                <div class="input-group">
                    <label for="start-date">Start Date</label>
                    <input
                        type="date"
                        id="start-date"
                        v-model="form.startDate"
                    />
                </div>

                <div class="input-group">
                    <label for="end-date">End Date</label>
                    <input type="date" id="end-date" v-model="form.endDate" />
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" @click="closeModal">
                    Cancel
                </button>
                <button @click="syncDailyAttendance" class="btn btn-primary">
                    Submit
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Overlay Backdrop */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    /* Modern slate backdrop with slight blur */
    backdrop-filter: blur(4px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    padding: 16px;
}

/* Modal Container */
.modal-content {
    background: #ffffff;
    width: 100%;
    max-width: 440px;
    border-radius: 12px;
    box-shadow:
        0 20px 25px -5px rgba(0, 0, 0, 0.1),
        0 10px 10px -5px rgba(0, 0, 0, 0.04);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: modal-appear 0.2s ease-out;
}

/* Header Section */
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
}

.modal-header h4 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: #0f172a;
}

.btn-close-x {
    background: transparent;
    border: none;
    font-size: 1.5rem;
    color: #94a3b8;
    cursor: pointer;
    line-height: 1;
    padding: 4px;
    border-radius: 6px;
    transition: all 0.2s;
}

.btn-close-x:hover {
    background: #f1f5f9;
    color: #475569;
}

/* Body / Form Section */
.modal-body {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.input-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.input-group label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #475569;
}

.input-group input[type="date"] {
    font-family: inherit;
    font-size: 0.95rem;
    padding: 10px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    color: #0f172a;
    outline: none;
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}

.input-group input[type="date"]:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

/* Footer Section */
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
}

/* Buttons UI */
.btn {
    font-family: inherit;
    font-size: 0.875rem;
    font-weight: 500;
    padding: 10px 18px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;
}

.btn-secondary {
    background: #ffffff;
    border-color: #e2e8f0;
    color: #475569;
}

.btn-secondary:hover {
    background: #f8fafc;
    color: #0f172a;
}

.btn-primary {
    background: #3b82f6;
    color: #ffffff;
}

.btn-primary:hover {
    background: #2563eb;
}

/* Smooth Entrance Animation */
@keyframes modal-appear {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(10px);
    }

    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}
</style>
