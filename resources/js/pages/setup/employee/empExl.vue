<template>
    <div class="text-center">
        <h1>Artisan All Employee</h1>
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
</template>

<script setup>
import { ref, onMounted } from "vue";
import { HotTable } from "@handsontable/vue3";
import { registerAllModules } from "handsontable/registry";
import "handsontable/dist/handsontable.full.css";
import api from '@/api';

// Register Handsontable's modules
registerAllModules();

const employee = ref([]);
const hotTableComponent = ref(null);
const loading = ref(true);

const hotSettings = ref({
    data: [],
    height: "auto",
    autoWrapRow: true,
    autoWrapCol: true,
    licenseKey: "non-commercial-and-evaluation",
    height: window.innerHeight - 250,

    fixedColumnsStart: 3,
    rowHeaders: true,
    colHeaders: [
        "Employee ID",
        "Employee Name",
        "Division",
        "Department",
        "Designation",
        "Father Name",
        "Mother Name",
        "Spuose Name",
        "Present Address",
        "Permanent Address",
        "Marital Status",
        "Date of Birth",
        "Place of Birth",
        "Contact No",
        "Emergency Contact",
        "Official Email",
        "Personal Email",
        "Blood Group",
        "Gender",
        "Religion",
        "Employee Grade",
        "Date of Joining",
        "Date of Leaving",
        "Job Location",
        "Provation Period",
        "Employee Type",
        "NID",
        "Nationality",
        "Status",
    ],
    columns: [
        { type: "text", data: "Employee_Id", readOnly: true },
        { type: "text", data: "Full_Name", readOnly: true },
        { type: "text", data: "division", readOnly: true },
        { type: "text", data: "department", readOnly: true },
        { type: "text", data: "designation", readOnly: true },
        { type: "text", data: "Father_Name", readOnly: true },
        { type: "text", data: "Mother_Name", readOnly: true },
        { type: "text", data: "Spouse_Name", readOnly: true },
        { type: "text", data: "Present_Address", readOnly: true },
        { type: "text", data: "Permanent_Address", readOnly: true },
        { type: "text", data: "Marital_Status", readOnly: true },
        { type: "text", data: "DOB", readOnly: true },
        { type: "text", data: "Place_of_Birth", readOnly: true },
        { type: "text", data: "Contact_No", readOnly: true },
        { type: "text", data: "Emergency_Contact", readOnly: true },
        { type: "text", data: "Official_Email", readOnly: true },
        { type: "text", data: "Personal_Email", readOnly: true },
        { type: "text", data: "group", readOnly: true },
        { type: "text", data: "Gender", readOnly: true },
        { type: "text", data: "religion", readOnly: true },
        { type: "text", data: "Employee_Grade", readOnly: true },
        { type: "text", data: "DOJ", readOnly: true },
        { type: "text", data: "DOL", readOnly: true },
        { type: "text", data: "branch_name", readOnly: true },
        { type: "text", data: "Provation_period", readOnly: true },
        { type: "text", data: "employee_type", readOnly: true },
        { type: "text", data: "NID", readOnly: true },
        { type: "text", data: "Nationality", readOnly: true },
        { type: "text", data: "status", readOnly: true },
    ],
    // hiddenColumns: {
    //     columns: [0],
    //     indicators: false,
    // },
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
    afterChange(changes, source) {
        if (source !== "loadData") {
            hotTableComponent.value.hotInstance.render();
        }
    },
});

const getData = async () => {
    loading.value = true;
    try {
        const response = await api.get("/employee-exl");

        employee.value = response.data.map((emp) => ({
            ...emp,
            Employee_Id: "ABNB"+emp.Employee_Id,
            status: emp.status === "N" ? "Active" : "Inactive",
            Marital_Status:
                emp.Marital_Status === "M" ? "Married" : "Unmarried",
            Gender: emp.Gender === "M" ? "Male" : "Female",
        }));

        hotSettings.value.data = employee.value;
        hotTableComponent.value.hotInstance.loadData(employee.value);
    } catch (error) {
        console.error("Error fetching data:", error);
    } finally {
        loading.value = false;
    }
};

function exportToExcel() {
    // Get the data from the Handsontable instance
    const data = hotTableComponent.value.hotInstance.getData();
    const headers = hotTableComponent.value.hotInstance.getColHeader();

    // Combine headers with data
    const sheetData = [headers, ...data];

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
    link.download = "Employee_List.xlsx";
    link.click();

    // Clean up the URL object
    URL.revokeObjectURL(link.href);
}

onMounted(() => getData());
</script>
