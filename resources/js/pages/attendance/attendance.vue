<template>
    <div>
        <div class="row d-flex justify-content-start">
            <div class="col-lg-2 mb-3">
                <label for="">Select a Year</label>
                <select class="form-control" name="status" v-model="selectedYear" @change="generateDays">
                    <option v-for="(year, index) in years" :key="year" :value="year">
                        {{ year }}
                    </option>
                </select>
            </div>
            <div class="col-lg-2 mb-3">
                <label for="">Select a Month</label>
                <select class="form-control" name="status" v-model="selectedMonth" @change="generateDays">
                    <option v-for="(month, index) in months" :key="month" :value="index">
                        {{ month }}
                    </option>
                </select>
            </div>
            <div class="col-lg-2 mt-4">
                <button class="custom-btn btn-13 px-2 py-1"><i class="fa-solid fa-filter"></i></button>
            </div>
        </div>
        <div>
            <div v-if="loading" class="overlay-loading">
                <div class="loader"></div>
            </div>
            <hot-table ref="hotTableComponent" :settings="hotSettings"></hot-table>
        </div>
        <div>
            <button @click="exportToExcel" class="custom-btn btn-13 mt-3">
                Export to Excel
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { HotTable } from "@handsontable/vue3";
import { registerAllModules } from "handsontable/registry";
import "handsontable/dist/handsontable.full.css";
import Swal from "sweetalert2";
import api from "@/api";

// Register Handsontable's modules
registerAllModules();

const daysInMonth = ref([]);
const selectedMonth = ref(new Date().getMonth());
const selectedYear = ref(new Date().getFullYear());
const employee = ref([]);
const hotTableComponent = ref(null);
const loading = ref(true);
const months = ref([
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
]);

const years = ref([
    "2022",
    "2023",
    "2024",
    "2025",
    "2026",
    "2027",
    "2028",
    "2029",
    "2030",
]);

const generateDays = () => {
    const year = selectedYear.value;
    const month = selectedMonth.value;
    const days = new Date(year, month + 1, 0).getDate();

    daysInMonth.value = Array.from({ length: days }, (v, k) => k + 1);

    hotSettings.value.colHeaders = [
        "Id",
        "Employee Id",
        "Employee Name",
        "Department",
        "Designation",
        "Branch",
        ...daysInMonth.value.map((day) => `${day}`),
    ];

    hotSettings.value.columns = [
        { type: "text", data: "id", readOnly: true },
        {
            type: "text", data: "Employee_Id", readOnly: true, renderer: (instance, td, row, col, prop, value) => {
                td.innerText = `ABNB${value}`;
                return td;
            }
        },
        { type: "text", data: "Full_Name", readOnly: true },
        { type: "text", data: "department", readOnly: true },
        { type: "text", data: "designation", readOnly: true },
        { type: "text", data: "branch", readOnly: true },
        ...daysInMonth.value.map((day, index) => ({
            type: "dropdown",
            // source: ["", "P", "A", "L"],
            data: `attendance.${day}`,
            editor: "dropdown",
        })),
    ];

    hotTableComponent.value.hotInstance.updateSettings(hotSettings.value);
    getData();
};

const hotSettings = ref({
    data: [],
    height: "auto",
    autoWrapRow: true,
    autoWrapCol: true,
    licenseKey: "non-commercial-and-evaluation",
    height: window.innerHeight - 250,

    fixedColumnsStart: 5,
    rowHeaders: true,
    colHeaders: ["Id", "Employee Id", "Employee Name", "Department", "Designation", "Branch"],
    columns: [
        { type: "text", data: "id", readOnly: true },
        {
            type: "text", data: "Employee_Id", readOnly: true, renderer: (instance, td, row, col, prop, value) => {
                td.innerText = `ABNB${value}`;
                return td;
            }
        },
        { type: "text", data: "Full_Name", readOnly: true },
        { type: "text", data: "department", readOnly: true },
        { type: "text", data: "designation", readOnly: true },
        { type: "text", data: "branch", readOnly: true },
    ],
    hiddenColumns: {
        columns: [0],
        indicators: false,
    },
    minSpareRows: 1,
    minSpareCols: 1,
    manualColumnResize: true,
    useFormula: true,
    filters: true,
    draw: true,
    stretchH: "all",
    customBorders: true,
    columnSorting: true,
    sortIndicator: true,
    dropdownMenu: [
        "make_read_only",
        "alignment",
        "---------",
        "filter_by_condition",
        "filter_by_value",
        "filter_action_bar",
    ],
    contextMenu: [
        "undo",
        "redo",
        "---------",
        "make_read_only",
        "alignment",
        "borders",
        "remove_row",
    ],
    readOnly: true,
    afterChange(changes, source) {
        if (source !== "loadData") {
            hotTableComponent.value.hotInstance.render();
        }
    },
});

const getData = async () => {
    loading.value = true;
    try {
        const response = await api.get("/emp-attendence");
        const responseData = await api.get("/attendence", {
            params: {
                month: selectedMonth.value,
                year: selectedYear.value
            },
        });

        employee.value = response.data.map((emp) => {
            const attendance = {};
            daysInMonth.value.forEach((day) => {
                const attendanceRecord = responseData.data.find((record) => {
                    const recordDate = new Date(record.Date);
                    const recordDay = recordDate.getDate();
                    const isMatching =
                        record.EID === emp.id && recordDay === day;
                    return isMatching;
                });
                attendance[day] = attendanceRecord
                    ? attendanceRecord.Time_In
                    : "";
            });

            return {
                ...emp,
                attendance,
            };
        });

        hotSettings.value.data = employee.value;
        hotTableComponent.value.hotInstance.loadData(employee.value);
    } catch (error) {
        console.error("Error fetching data:", error);
    } finally {
        loading.value = false;
    }
};

const fetchAttendence = async () => {
    loading.value = true;
    try {
        const response = await api.get("/fetch-attendence");
    } catch (error) {
        console.error("Error fetching data:", error);
    } finally {
        loading.value = false;
    }
};

const fetchMachineUser = async () => {
    loading.value = true;
    try {
        const response = await api.get("/fetch-zkt-user");
    } catch (error) {
        console.error("Error fetching data:", error);
    } finally {
        loading.value = false;
    }
};

const fetchMachineAttendence = async () => {
    loading.value = true;
    try {
        const response = await api.get("/fetch-zkt-attendence");
    } catch (error) {
        console.error("Error fetching data:", error);
    } finally {
        loading.value = false;
    }
};

const saveData = async () => {
    const data = hotTableComponent.value.hotInstance.getData();
    const attendanceData = data.map((row) => {
        const attendance = row.slice(5); // only attendance status values
        return {
            id: row[0],
            attendance: attendance,
        };
    });

    try {
        const result = await Swal.fire({
            title: "Are you sure?",
            text: "Do you want to submit this form?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, submit it!",
            cancelButtonText: "No, cancel!",
            reverseButtons: true,
        });

        if (result.isConfirmed) {
            const response = await api.post("/attendence", {
                attendanceData,
                month: selectedMonth.value,
            });
            Swal.fire({
                title: "Success!",
                text: "Attendence Updated",
                icon: "success",
                confirmButtonText: "OK",
            });
        }
    } catch (error) {
        console.error("Error saving data:", error);
    }
};

function exportToExcel() {
    // Get the data from the Handsontable instance
    const data = hotTableComponent.value.hotInstance.getData();
    const headers = hotTableComponent.value.hotInstance.getColHeader();

    // Get the current date and time
    const now = new Date();
    const timestamp = now.toLocaleString(); // Format as per your locale

    // Add additional headings
    const title = ["Attendance Report"];
    const subtitle = ["Artisan Business Network Bangladesh"];
    const printTime = [`Printed on: ${timestamp}`];

    // Combine headers with data
    const sheetData = [
        title,
        subtitle,
        printTime,
        [],
        headers,
        ...data,
    ];

    // Create a new workbook and a sheet
    const worksheet = XLSX.utils.aoa_to_sheet(sheetData);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Sheet1");

    // Create a binary string representation of the workbook
    const workbookBinary = XLSX.write(workbook, {
        bookType: "xlsx",
        type: "array",
    });

    // Create a Blob from the binary data
    const blob = new Blob([workbookBinary], {
        type: "application/octet-stream",
    });

    // Create a link element and trigger the download
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "Attendence Report.xlsx";
    link.click();

    // Clean up the URL object
    URL.revokeObjectURL(link.href);
}

onMounted(() => generateDays());
</script>
