<script setup>
import { ref, onMounted, onUnmounted, computed } from "vue";
import { Bootstrap4Pagination } from "laravel-vue-pagination";
import router from "../../../router";
import api from "@/api";

const employees = ref([]);
const isLoading = ref(true);
const error = ref(null);
const empEdit = ref(null);
const heading = ref(null);

const viewMode = ref("board");
const departments = ref([]);
const selectedDepartment = ref("");
const searchName = ref("");
const searchId = ref("");
const openMenuId = ref(null);
const menuAnchor = ref(null);
const GRID_MENU_WIDTH = 220;

const employeeRows = computed(() => employees.value?.data || []);
const totalEmployees = computed(() => employees.value?.total || 0);
const hasEmployees = computed(() => employeeRows.value.length > 0);

const defaultImage = "/default-avatar.png";

const getData = async (page = 1) => {
    isLoading.value = true;
    try {
        const response = await api.get("/employee", {
            params: {
                page,
                per_page: 20,
                ...(selectedDepartment.value
                    ? { department_id: selectedDepartment.value }
                    : {}),
            },
        });
        employees.value = response.data;
        error.value = null;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    } finally {
        isLoading.value = false;
    }
};

const searchEmployees = async (page = 1) => {
    isLoading.value = true;
    try {
        const response = await api.get("/employee-search", {
            params: {
                page,
                per_page: 20,
                ...(searchName.value ? { name: searchName.value } : {}),
                ...(searchId.value ? { employee_id: searchId.value } : {}),
                ...(selectedDepartment.value
                    ? { department_id: selectedDepartment.value }
                    : {}),
            },
        });
        employees.value = response.data;
        error.value = null;
    } catch (err) {
        error.value = err.message || "Error fetching data";
    } finally {
        isLoading.value = false;
    }
};

const loadEmployees = (page = 1) => {
    if (searchName.value.trim() || searchId.value.trim()) {
        return searchEmployees(page);
    }
    return getData(page);
};

let searchTimer = null;
const onSearchInput = () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        openMenuId.value = null;
        loadEmployees(1);
    }, 350);
};

const onDepartmentChange = () => {
    openMenuId.value = null;
    loadEmployees(1);
};

const onPageChange = (page) => {
    openMenuId.value = null;
    loadEmployees(page);
};

const fetchDepartments = async () => {
    try {
        const response = await api.get("/department");
        departments.value = response.data || [];
    } catch (err) {
        console.error("Error fetching departments:", err);
    }
};

const cvPdf = (id) => {
    window.open(`/cv-pdf/${id}`, "_blank");
};

const viewEmployee = (id) => router.push(`/setup/employee/empdetails/${id}`);
const editEmployee = (id) => router.push({ name: "Employee", params: { id } });
const addNewEmployee = () => router.push({ name: "Employee" });

const exportDocumentation = async (id) => {
    openMenuId.value = null;
    try {
        const response = await api.get("/export-documentation-report", {
            params: { employee_id: id },
            responseType: "blob",
        });
        const blob = new Blob([response.data], {
            type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = `employee_documentation_${id}.xlsx`;
        link.click();
        URL.revokeObjectURL(link.href);
    } catch (error) {
        console.error("Error exporting documentation:", error);
    }
};

const toggleMenu = (id, event) => {
    event?.stopPropagation();
    if (openMenuId.value === id) {
        openMenuId.value = null;
        return;
    }
    openMenuId.value = id;
    if (event?.currentTarget) {
        const rect = event.currentTarget.getBoundingClientRect();
        menuAnchor.value = {
            id,
            top: rect.bottom + 8,
            left: Math.max(
                8,
                Math.min(
                    rect.right - GRID_MENU_WIDTH,
                    window.innerWidth - GRID_MENU_WIDTH - 8,
                ),
            ),
        };
    }
};

const closeMenus = () => {
    openMenuId.value = null;
};

const onWindowClose = () => {
    openMenuId.value = null;
};

const isEmployeeActive = (emp) => !(emp.emp_status === "Y");

const empImage = (emp) => emp.img_url || defaultImage;

const empDepartment = (emp) => emp.department_name || "—";

const empDesignation = (emp) => emp.designation_name || "Not assigned";

const empEmail = (emp) => emp.Official_Email || "—";

const empContact = (emp) => emp.Contact_No || "—";

const docPercent = (emp) => {
    const p = Number(emp.completed_percent);
    return Number.isFinite(p) ? Math.max(0, Math.min(100, p)) : 0;
};

const machineId = (emp) => emp.id;

const formatDate = (dateStr) => {
    if (!dateStr) return "—";
    const parts = String(dateStr).split(/[-\/]/);
    if (parts.length !== 3) return dateStr;
    let day, month, year;
    if (parts[0].length === 4) {
        [year, month, day] = parts;
    } else {
        [day, month, year] = parts;
    }
    const months = [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
    ];
    const m = parseInt(month, 10);
    if (m >= 1 && m <= 12 && day && year) {
        return `${String(parseInt(day, 10)).padStart(2, "0")} ${months[m - 1]} ${year}`;
    }
    return dateStr;
};

const editHandler = async (id) => {
    try {
        const response = await api.get(`/employee/${id}/edit`);
        empEdit.value = response.data;
        heading.value = "Update";
        openModal();
    } catch (err) {
        console.error("Error fetching store data for editing:", err);
    }
};

const submitHandler = async () => {
    await loadEmployees();
};

onMounted(() => {
    getData();
    fetchDepartments();
    document.addEventListener("click", closeMenus);
    window.addEventListener("scroll", onWindowClose, true);
    window.addEventListener("resize", onWindowClose);
});

onUnmounted(() => {
    document.removeEventListener("click", closeMenus);
    window.removeEventListener("scroll", onWindowClose, true);
    window.removeEventListener("resize", onWindowClose);
    if (searchTimer) clearTimeout(searchTimer);
});
</script>

<template>
    <div class="emp-page">
        <div class="emp-heading">
            <div class="emp-heading__text">
                <h1 class="emp-heading__title">Employee List</h1>
                <p class="emp-heading__subtitle">
                    Manage, search and organize your organisation's workforce.
                </p>
            </div>
            <button type="button" class="btn-add" @click="addNewEmployee">
                <i class="fa-solid fa-plus"></i>
                Add New Employee
            </button>
        </div>

        <div class="emp-toolbar">
            <div class="emp-filters">
                <div class="emp-filter">
                    <label class="emp-filter__label" for="filter-department">
                        Department
                    </label>
                    <select
                        id="filter-department"
                        class="emp-filter__select"
                        v-model="selectedDepartment"
                        @change="onDepartmentChange"
                    >
                        <option value="">All Departments</option>
                        <option
                            v-for="dept in departments"
                            :key="dept.id"
                            :value="dept.id"
                        >
                            {{ dept.Name }}
                        </option>
                    </select>
                </div>

                <div class="emp-filter emp-filter--grow">
                    <label class="emp-filter__label" for="filter-name">
                        Employee Name
                    </label>
                    <div class="emp-filter__input-wrap">
                        <i
                            class="fa-solid fa-magnifying-glass emp-filter__icon"
                        ></i>
                        <input
                            id="filter-name"
                            type="text"
                            class="emp-filter__input"
                            placeholder="Search by name..."
                            v-model.trim="searchName"
                            @input="onSearchInput"
                        />
                    </div>
                </div>

                <div class="emp-filter emp-filter--grow">
                    <label class="emp-filter__label" for="filter-id">
                        Employee ID
                    </label>
                    <div class="emp-filter__input-wrap">
                        <i class="fa-solid fa-hashtag emp-filter__icon"></i>
                        <input
                            id="filter-id"
                            type="text"
                            class="emp-filter__input"
                            placeholder="Search by employee ID..."
                            v-model.trim="searchId"
                            @input="onSearchInput"
                        />
                    </div>
                </div>
            </div>

            <div class="view-switch" role="tablist" aria-label="View mode">
                <button
                    type="button"
                    class="view-switch__btn"
                    :class="{ 'is-active': viewMode === 'grid' }"
                    @click="viewMode = 'grid'"
                    aria-label="Grid view"
                >
                    <i class="fa-solid fa-table-cells-large"></i>
                    Grid
                </button>
                <button
                    type="button"
                    class="view-switch__btn"
                    :class="{ 'is-active': viewMode === 'board' }"
                    @click="viewMode = 'board'"
                    aria-label="Board view"
                >
                    <i class="fa-solid fa-border-all"></i>
                    Board
                </button>
            </div>
        </div>

        <div class="emp-nav">
            <div class="emp-nav__total">
                <i class="fa-solid fa-users"></i>
                <span>
                    Total Employees:
                    <strong>{{ totalEmployees }}</strong>
                </span>
            </div>
        </div>

        <div class="emp-content">
            <div class="emp-state" v-if="isLoading">
                <template v-if="viewMode === 'board'">
                    <div class="board-skeleton">
                        <div
                            class="board-skeleton__card"
                            v-for="n in 8"
                            :key="n"
                        >
                            <div
                                class="board-skeleton__line board-skeleton__line--short"
                            ></div>
                            <div class="board-skeleton__avatar"></div>
                            <div class="board-skeleton__line"></div>
                            <div
                                class="board-skeleton__line board-skeleton__line--half"
                            ></div>
                            <div
                                class="board-skeleton__line board-skeleton__line--half"
                            ></div>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <div class="grid-skeleton">
                        <div
                            class="grid-skeleton__row"
                            v-for="n in 10"
                            :key="n"
                        >
                            <div
                                class="grid-skeleton__cell grid-skeleton__cell--sm"
                            ></div>
                            <div
                                class="grid-skeleton__cell grid-skeleton__cell--avatar"
                            ></div>
                            <div class="grid-skeleton__cell"></div>
                            <div class="grid-skeleton__cell"></div>
                            <div class="grid-skeleton__cell"></div>
                            <div class="grid-skeleton__cell"></div>
                            <div class="grid-skeleton__cell"></div>
                            <div class="grid-skeleton__cell"></div>
                            <div class="grid-skeleton__cell"></div>
                            <div
                                class="grid-skeleton__cell grid-skeleton__cell--sm"
                            ></div>
                            <div
                                class="grid-skeleton__cell grid-skeleton__cell--sm"
                            ></div>
                            <div
                                class="grid-skeleton__cell grid-skeleton__cell--sm"
                            ></div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="emp-error" v-else-if="error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <p>{{ error }}</p>
                <button class="btn-retry" @click="loadEmployees(1)">
                    Try Again
                </button>
            </div>

            <template v-else-if="hasEmployees">
                <div class="board-view" v-if="viewMode === 'board'">
                    <article
                        class="emp-card"
                        v-for="employee in employeeRows"
                        :key="employee.id"
                    >
                        <div class="emp-card__top">
                            <span
                                class="emp-status"
                                :class="{
                                    'is-active': isEmployeeActive(employee),
                                    'is-inactive': !isEmployeeActive(employee),
                                }"
                            >
                                <span class="emp-status__dot"></span>
                                {{
                                    isEmployeeActive(employee)
                                        ? "Active"
                                        : "Inactive"
                                }}
                            </span>

                            <div class="emp-card__menu">
                                <button
                                    type="button"
                                    class="icon-btn"
                                    aria-label="More actions"
                                    @click="toggleMenu(employee.id, $event)"
                                >
                                    <i
                                        class="fa-solid fa-ellipsis-vertical"
                                    ></i>
                                </button>

                                <transition name="menu">
                                    <div
                                        class="menu-dropdown"
                                        v-if="openMenuId === employee.id"
                                        @click.stop
                                    >
                                        <button
                                            type="button"
                                            class="menu-dropdown__item"
                                            @click="
                                                exportDocumentation(employee.id)
                                            "
                                        >
                                            <i
                                                class="fa-solid fa-file-export"
                                            ></i>
                                            Export Documentation
                                        </button>
                                        <button
                                            type="button"
                                            class="menu-dropdown__item"
                                            @click="
                                                editEmployee(employee.id);
                                                openMenuId = null;
                                            "
                                        >
                                            <i
                                                class="fa-regular fa-pen-to-square"
                                            ></i>
                                            Edit Profile
                                        </button>
                                        <button
                                            type="button"
                                            class="menu-dropdown__item"
                                            @click="
                                                viewEmployee(employee.id);
                                                openMenuId = null;
                                            "
                                        >
                                            <i class="fa-regular fa-user"></i>
                                            View Profile
                                        </button>
                                        <button
                                            type="button"
                                            class="menu-dropdown__item"
                                            @click="
                                                cvPdf(employee.id);
                                                openMenuId = null;
                                            "
                                        >
                                            <i class="fa-regular fa-file"></i>
                                            Download CV
                                        </button>
                                    </div>
                                </transition>
                            </div>
                        </div>

                        <div class="emp-card__avatar">
                            <img
                                :src="empImage(employee)"
                                :alt="employee.Full_Name"
                            />
                        </div>

                        <div class="emp-card__identity">
                            <h3 class="emp-card__name">
                                {{ employee.Full_Name }}
                            </h3>
                            <p class="emp-card__designation">
                                {{ empDesignation(employee) }}
                            </p>
                            <p class="emp-card__department">
                                {{ empDepartment(employee) }}
                            </p>
                        </div>

                        <div class="emp-card__contact">
                            <div class="emp-card__contact-row">
                                <i class="fa-regular fa-envelope"></i>
                                <span>{{ empEmail(employee) }}</span>
                            </div>
                            <div class="emp-card__contact-row">
                                <i class="fa-solid fa-phone"></i>
                                <span>{{ empContact(employee) }}</span>
                            </div>
                        </div>

                        <div class="emp-card__progress">
                            <div class="emp-card__progress-head">
                                <span class="emp-card__progress-label">
                                    Documentation
                                </span>
                                <span class="emp-card__progress-value">
                                    {{ docPercent(employee) }}%
                                </span>
                            </div>
                            <div class="progress-track">
                                <div
                                    class="progress-fill"
                                    :style="{
                                        width: docPercent(employee) + '%',
                                    }"
                                ></div>
                            </div>
                        </div>

                        <div class="emp-card__dates">
                            <div class="emp-card__date">
                                <span class="emp-card__date-label">DOB</span>
                                <span class="emp-card__date-value">
                                    {{ formatDate(employee.DOB) }}
                                </span>
                            </div>
                            <div class="emp-card__date">
                                <span class="emp-card__date-label">DOJ</span>
                                <span class="emp-card__date-value">
                                    {{ formatDate(employee.DOJ) }}
                                </span>
                            </div>
                        </div>

                        <div class="emp-card__footer">
                            <button
                                type="button"
                                class="edit-btn"
                                @click="editEmployee(employee.id)"
                            >
                                <i class="fa-regular fa-pen-to-square"></i>
                                Edit Profile
                            </button>
                        </div>
                    </article>
                </div>

                <div class="grid-view" v-else>
                    <div class="grid-table-wrap">
                        <table class="grid-table">
                            <thead>
                                <tr>
                                    <th>Employee ID</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Designation</th>
                                    <th>DOB</th>
                                    <th>DOJ</th>
                                    <th>Contact</th>
                                    <th>Mail</th>
                                    <th>Status</th>
                                    <th>Machine ID</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="employee in employeeRows"
                                    :key="employee.id"
                                >
                                    <td class="cell-mono">
                                        ABNB{{ employee.Employee_Id }}
                                    </td>
                                    <td>
                                        <img
                                            class="grid-avatar"
                                            :src="empImage(employee)"
                                            :alt="employee.Full_Name"
                                        />
                                    </td>
                                    <td class="cell-strong">
                                        {{ employee.Full_Name }}
                                    </td>
                                    <td>{{ empDepartment(employee) }}</td>
                                    <td>{{ empDesignation(employee) }}</td>
                                    <td>{{ formatDate(employee.DOB) }}</td>
                                    <td>{{ formatDate(employee.DOJ) }}</td>
                                    <td>{{ empContact(employee) }}</td>
                                    <td class="cell-mail">
                                        {{ empEmail(employee) }}
                                    </td>
                                    <td>
                                        <span
                                            class="status-badge"
                                            :class="{
                                                'is-active':
                                                    isEmployeeActive(employee),
                                                'is-inactive':
                                                    !isEmployeeActive(employee),
                                            }"
                                        >
                                            <span
                                                class="status-badge__dot"
                                            ></span>
                                            {{
                                                isEmployeeActive(employee)
                                                    ? "Active"
                                                    : "Inactive"
                                            }}
                                        </span>
                                    </td>
                                    <td class="cell-mono">
                                        {{ machineId(employee) }}
                                    </td>
                                    <td>
                                        <div class="row-actions">
                                            <button
                                                class="custom-btn btn-13"
                                                title="View Profile"
                                                @click="
                                                    viewEmployee(employee.id)
                                                "
                                            >
                                                <i
                                                    class="fa-regular fa-user"
                                                ></i>
                                            </button>
                                            <button
                                                class="custom-btn btn-15"
                                                title="Edit Profile"
                                                @click="
                                                    editEmployee(employee.id)
                                                "
                                            >
                                                <i
                                                    class="fa-regular fa-pen-to-square"
                                                ></i>
                                            </button>
                                            <button
                                                class="custom-btn btn-13"
                                                title="Download CV"
                                                @click="cvPdf(employee.id)"
                                            >
                                                <i
                                                    class="fa-regular fa-file"
                                                ></i>
                                            </button>

                                            <div class="row-actions__menu">
                                                <button
                                                    type="button"
                                                    class="icon-btn"
                                                    aria-label="More actions"
                                                    @click="
                                                        toggleMenu(
                                                            employee.id,
                                                            $event,
                                                        )
                                                    "
                                                >
                                                    <i
                                                        class="fa-solid fa-ellipsis-vertical"
                                                    ></i>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <teleport to="body">
                    <transition name="menu">
                        <div
                            class="menu-dropdown menu-floating"
                            v-if="
                                viewMode === 'grid' &&
                                menuAnchor &&
                                openMenuId === menuAnchor.id
                            "
                            :style="{
                                top: menuAnchor.top + 'px',
                                left: menuAnchor.left + 'px',
                            }"
                            @click.stop
                        >
                            <button
                                type="button"
                                class="menu-dropdown__item"
                                @click="exportDocumentation(menuAnchor.id)"
                            >
                                <i class="fa-solid fa-file-export"></i>
                                Export Documentation
                            </button>
                        </div>
                    </transition>
                </teleport>

                <div
                    class="pagination-wrap"
                    v-if="totalEmployees > 0 && employees.last_page > 1"
                >
                    <Bootstrap4Pagination
                        :data="employees"
                        :limit="3"
                        @pagination-change-page="onPageChange"
                    />
                </div>
            </template>

            <div class="emp-empty" v-else>
                <div class="emp-empty__icon">
                    <i class="fa-regular fa-user"></i>
                </div>
                <h3 class="emp-empty__title">No employees found</h3>
                <p class="emp-empty__text">
                    Try changing your search or department filter.
                </p>
                <button
                    type="button"
                    class="btn-ghost"
                    @click="
                        selectedDepartment = '';
                        searchName = '';
                        searchId = '';
                        loadEmployees(1);
                    "
                >
                    Clear Filters
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.emp-page {
    padding: 8px 2px 24px;
}

.emp-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.emp-heading__title {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    letter-spacing: -0.3px;
}

.emp-heading__subtitle {
    margin: 6px 0 0;
    color: #6b7280;
    font-size: 13px;
}

.btn-add {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: #1a7f5a;
    color: #ffffff;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 13.5px;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(16, 24, 40, 0.08);
    transition:
        background 0.2s ease,
        transform 0.15s ease,
        box-shadow 0.2s ease;
}

.btn-add:hover {
    background: #146a4a;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(26, 127, 90, 0.25);
}

.btn-add:active {
    transform: translateY(0);
}

.emp-toolbar {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    background: #ffffff;
    border: 1px solid #e6ebf0;
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.05);
}

.emp-filters {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    flex-wrap: wrap;
    flex: 1 1 auto;
}

.emp-filter {
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-width: 180px;
}

.emp-filter--grow {
    flex: 1 1 180px;
    max-width: 300px;
}

.emp-filter__label {
    font-size: 11.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #64748b;
}

.emp-filter__select,
.emp-filter__input {
    width: 100%;
    height: 38px;
    border: 1px solid #d3dbe4;
    border-radius: 9px;
    background: #ffffff;
    font-size: 13px;
    color: #1f2937;
    padding: 0 12px;
    outline: none;
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}

.emp-filter__select:focus,
.emp-filter__input:focus {
    border-color: #1a7f5a;
    box-shadow: 0 0 0 3px rgba(26, 127, 90, 0.12);
}

.emp-filter__input-wrap {
    position: relative;
}

.emp-filter__icon {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: #94a3b8;
}

.emp-filter__input-wrap .emp-filter__input {
    padding-left: 32px;
}

.view-switch {
    display: inline-flex;
    align-items: center;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 4px;
    gap: 2px;
}

.view-switch__btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 16px;
    border: none;
    background: transparent;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
    transition:
        background 0.15s ease,
        color 0.15s ease,
        box-shadow 0.15s ease;
}

.view-switch__btn:hover {
    color: #1f2937;
}

.view-switch__btn.is-active {
    background: #ffffff;
    color: #1a7f5a;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.12);
}

.emp-nav {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
    margin: 16px 0 20px;
}

.emp-nav__total {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    color: #64748b;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 7px 14px;
    border-radius: 999px;
}

.emp-nav__total i {
    color: #1a7f5a;
}

.emp-nav__total strong {
    color: #1f2937;
}

.emp-content {
    min-height: 320px;
}

.board-view {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 18px;
}

.emp-card {
    position: relative;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border: 1px solid #e6ebf0;
    border-radius: 14px;
    padding: 16px 16px 14px;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    transition:
        box-shadow 0.2s ease,
        transform 0.2s ease,
        border-color 0.2s ease;
    animation: card-in 0.25s ease;
}

.emp-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(16, 24, 40, 0.1);
    border-color: #dbe4ec;
}

@keyframes card-in {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.emp-card__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 4px;
}

.emp-status {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.3px;
    padding: 4px 11px;
    border-radius: 999px;
}

.emp-status.is-active {
    color: #15803d;
    background: #ecfdf3;
}

.emp-status.is-inactive {
    color: #667085;
    background: #f2f4f7;
}

.emp-status__dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.emp-status.is-active .emp-status__dot {
    background: #22c55e;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2);
}

.emp-status.is-inactive .emp-status__dot {
    background: #98a2b3;
}

.icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    border-radius: 8px;
    color: #64748b;
    cursor: pointer;
    transition:
        background 0.15s ease,
        color 0.15s ease;
}

.icon-btn:hover {
    background: #f1f5f9;
    color: #1f2937;
}

.emp-card__menu {
    position: absolute;
    top: 14px;
    right: 14px;
}

.menu-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    min-width: 220px;
    background: #ffffff;
    border: 1px solid #e6ebf0;
    border-radius: 12px;
    box-shadow: 0 12px 32px rgba(16, 24, 40, 0.16);
    padding: 6px;
    z-index: 30;
}

.menu-floating {
    position: fixed;
    min-width: 220px;
    z-index: 60;
}

.menu-dropdown__item {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 9px 12px;
    border: none;
    background: transparent;
    border-radius: 8px;
    font-size: 13px;
    color: #334155;
    text-align: left;
    cursor: pointer;
    transition:
        background 0.15s ease,
        color 0.15s ease;
}

.menu-dropdown__item i {
    width: 16px;
    text-align: center;
    color: #94a3b8;
    font-size: 13px;
}

.menu-dropdown__item:hover {
    background: #f1f5f9;
    color: #1a7f5a;
}

.menu-dropdown__item:hover i {
    color: #1a7f5a;
}

.menu-enter-active,
.menu-leave-active {
    transition:
        opacity 0.15s ease,
        transform 0.15s ease;
}

.menu-enter-from,
.menu-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}

.emp-card__avatar {
    display: flex;
    justify-content: center;
    margin: 14px 0 12px;
}

.emp-card__avatar img {
    width: 86px;
    height: 86px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #ffffff;
    box-shadow:
        0 0 0 2px #e2e8f0,
        0 6px 16px rgba(16, 24, 40, 0.12);
    background: #f1f5f9;
}

.emp-card__identity {
    text-align: center;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f5f9;
}

.emp-card__name {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.3;
}

.emp-card__designation {
    margin: 4px 0 0;
    font-size: 12.5px;
    font-weight: 500;
    color: #475569;
}

.emp-card__department {
    margin: 2px 0 0;
    font-size: 12px;
    color: #94a3b8;
}

.emp-card__contact {
    padding: 12px 2px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.emp-card__contact-row {
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 12.5px;
    color: #475569;
    min-width: 0;
}

.emp-card__contact-row i {
    color: #94a3b8;
    width: 14px;
    text-align: center;
    flex-shrink: 0;
}

.emp-card__contact-row span {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.emp-card__progress {
    padding: 10px 2px;
}

.emp-card__progress-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 7px;
}

.emp-card__progress-label {
    font-size: 11.5px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.emp-card__progress-value {
    font-size: 12.5px;
    font-weight: 700;
    color: #1a7f5a;
}

.progress-track {
    height: 8px;
    border-radius: 999px;
    background: #eef2f6;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #1a7f5a, #34a976);
    transition: width 0.5s ease;
}

.emp-card__dates {
    display: flex;
    gap: 8px;
    margin-top: 4px;
    padding: 12px 2px;
    border-top: 1px solid #f1f5f9;
}

.emp-card__date {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.emp-card__date + .emp-card__date {
    border-left: 1px solid #f1f5f9;
    padding-left: 12px;
}

.emp-card__date-label {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #94a3b8;
}

.emp-card__date-value {
    font-size: 12.5px;
    font-weight: 600;
    color: #334155;
}

.emp-card__footer {
    display: flex;
    justify-content: flex-end;
    padding-top: 12px;
    margin-top: auto;
    border-top: 1px solid #f1f5f9;
}

.edit-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 14px;
    border: 1px solid #1a7f5a;
    background: #ffffff;
    color: #1a7f5a;
    border-radius: 9px;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    transition:
        background 0.15s ease,
        color 0.15s ease;
}

.edit-btn:hover {
    background: #1a7f5a;
    color: #ffffff;
}

.grid-view {
    background: #ffffff;
    border: 1px solid #e6ebf0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.05);
    overflow: hidden;
}

.grid-table-wrap {
    overflow-x: auto;
}

.grid-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
}

.grid-table thead th {
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    text-align: left;
    padding: 12px 14px;
    border-bottom: 1px solid #e6ebf0;
    white-space: nowrap;
}

.grid-table tbody td {
    padding: 11px 14px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    vertical-align: middle;
    white-space: nowrap;
}

.grid-table tbody tr:last-child td {
    border-bottom: none;
}

.grid-table tbody tr {
    transition: background 0.15s ease;
}

.grid-table tbody tr:hover {
    background: #f8fafc;
}

.cell-strong {
    font-weight: 600;
    color: #1f2937;
}

.cell-mono {
    font-family: "Courier New", monospace;
    font-size: 12px;
    color: #475569;
}

.cell-mail {
    max-width: 220px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.grid-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    background: #f1f5f9;
    box-shadow: 0 0 0 1px #e2e8f0;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 4px 11px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 700;
    letter-spacing: 0.3px;
}

.status-badge.is-active {
    color: #15803d;
    background: #ecfdf3;
}

.status-badge.is-inactive {
    color: #667085;
    background: #f2f4f7;
}

.status-badge__dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
}

.status-badge.is-active .status-badge__dot {
    background: #22c55e;
}

.status-badge.is-inactive .status-badge__dot {
    background: #98a2b3;
}

.row-actions {
    display: flex;
    align-items: center;
    gap: 4px;
}

.row-actions__menu {
    position: relative;
}

.pagination-wrap {
    display: flex;
    justify-content: center;
    margin-top: 22px;
}

.pagination-wrap :deep(.pagination) {
    margin: 0;
    justify-content: center;
    flex-wrap: wrap;
    gap: 2px;
}

.pagination-wrap :deep(.page-link) {
    border: 1px solid #e2e8f0;
    color: #475569;
    font-size: 13px;
    font-weight: 600;
    padding: 7px 13px;
    border-radius: 8px;
    margin: 0 2px;
    background: #ffffff;
    transition:
        border-color 0.15s ease,
        color 0.15s ease,
        background 0.15s ease;
}

.pagination-wrap :deep(.page-link:hover) {
    border-color: #1a7f5a;
    color: #1a7f5a;
    background: #ffffff;
}

.pagination-wrap :deep(.active .page-link),
.pagination-wrap :deep(.page-item.active .page-link) {
    background: #1a7f5a;
    border-color: #1a7f5a;
    color: #ffffff;
}

.pagination-wrap :deep(.disabled .page-link) {
    color: #cbd5e1;
    background: #f8fafc;
}

.emp-state,
.emp-error,
.emp-empty {
    padding: 48px 20px;
}

.board-skeleton {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 18px;
}

.board-skeleton__card {
    background: #ffffff;
    border: 1px solid #e6ebf0;
    border-radius: 14px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 13px;
}

.board-skeleton__avatar {
    width: 86px;
    height: 86px;
    border-radius: 50%;
    background: #eef2f6;
}

.board-skeleton__line {
    height: 13px;
    border-radius: 999px;
    background: #eef2f6;
    width: 100%;
}

.board-skeleton__line--short {
    width: 40%;
    align-self: flex-start;
}

.board-skeleton__line--half {
    width: 60%;
}

.board-skeleton__avatar,
.board-skeleton__line {
    background: linear-gradient(90deg, #eef2f6 25%, #f7fafc 50%, #eef2f6 75%);
    background-size: 200% 100%;
    animation: shimmer 1.4s infinite;
}

@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

.grid-skeleton {
    background: #ffffff;
    border: 1px solid #e6ebf0;
    border-radius: 14px;
    overflow: hidden;
}

.grid-skeleton__row {
    display: grid;
    grid-template-columns:
        1.2fr 0.7fr 1.6fr 1.6fr 1.5fr 1fr 1fr 1.4fr 1.8fr 1fr
        1fr 1fr;
    gap: 10px;
    align-items: center;
    padding: 14px;
    border-bottom: 1px solid #f1f5f9;
}

.grid-skeleton__cell {
    height: 14px;
    border-radius: 999px;
    background: linear-gradient(90deg, #eef2f6 25%, #f7fafc 50%, #eef2f6 75%);
    background-size: 200% 100%;
    animation: shimmer 1.4s infinite;
}

.grid-skeleton__cell--sm {
    height: 12px;
}

.grid-skeleton__cell--avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    justify-self: center;
}

.emp-error {
    text-align: center;
    color: #b91c1c;
}

.emp-error i {
    font-size: 34px;
    margin-bottom: 10px;
}

.emp-error p {
    margin: 0 0 16px;
    font-size: 14px;
}

.btn-retry {
    padding: 9px 22px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #334155;
    border-radius: 9px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition:
        border-color 0.15s ease,
        color 0.15s ease;
}

.btn-retry:hover {
    border-color: #1a7f5a;
    color: #1a7f5a;
}

.emp-empty {
    text-align: center;
    background: #ffffff;
    border: 1px dashed #d3dbe4;
    border-radius: 14px;
}

.emp-empty__icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #f1f5f9;
    color: #94a3b8;
    font-size: 24px;
}

.emp-empty__title {
    margin: 0;
    font-size: 17px;
    color: #1f2937;
}

.emp-empty__text {
    margin: 6px 0 18px;
    font-size: 13px;
    color: #64748b;
}

.btn-ghost {
    padding: 9px 20px;
    border: 1px solid #d3dbe4;
    background: #ffffff;
    color: #475569;
    border-radius: 9px;
    font-weight: 600;
    font-size: 12.5px;
    cursor: pointer;
    transition:
        border-color 0.15s ease,
        color 0.15s ease,
        background 0.15s ease;
}

.btn-ghost:hover {
    border-color: #1a7f5a;
    color: #1a7f5a;
}

@media (max-width: 992px) {
    .emp-filter {
        min-width: 160px;
    }
}

@media (max-width: 768px) {
    .emp-toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .view-switch {
        align-self: flex-start;
    }

    .emp-filters {
        width: 100%;
    }

    .emp-filter,
    .emp-filter--grow {
        flex: 1 1 100%;
        max-width: none;
        width: 100%;
    }

    .emp-nav__total {
        margin-left: 0;
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .emp-heading {
        flex-direction: column;
    }

    .btn-add {
        width: 100%;
        justify-content: center;
    }

    .view-switch__btn {
        flex: 1;
        justify-content: center;
    }
}
</style>
