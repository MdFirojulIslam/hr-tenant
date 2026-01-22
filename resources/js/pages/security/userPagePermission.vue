<script setup>
import { ref, onMounted, watch } from "vue";
import api from "@/api";
import Swal from "sweetalert2";

const error = ref([]);
const department = ref([]);
const empp = ref([]);
const nav_tree = ref([]);

const form = ref({
    department: "",
    employee_Id: "",
    permissions: [],
});
const allPermissionsChecked = ref(false);

// Filters and structures navigation data with active nodes only
const filterActiveNodes = (nodes) => {
    return nodes
        .filter((node) => node.active === 1)
        .map((node) => ({
            ...node,
            checked: false,
            children: node.children ? filterActiveNodes(node.children) : [],
        }));
};

// Fetch department and navigation data
const getData = async () => {
    try {
        const responseDept = await api.get("/department");
        department.value = responseDept.data;

        let { data } = await api.get("/getNavigation");
        if (data && data.tree) {
            nav_tree.value = filterActiveNodes(data.tree);
        }
    } catch (err) {
        if (err.response && err.response.status === 401) {
            router.push({ name: "Login" });
        } else {
            error.value = err.message || "Error fetching data";
        }
    }
};

// Fetch employees based on department selection
const getEmployee = async (id) => {
    try {
        const response = await api.get(`/user/${id}`);
        empp.value = response.data;
    } catch (error) {
        console.error("Error fetching employees:", error);
    }
};

// Fetch permissions based on employee selection
const getPermission = async (id) => {
    try {
        const response = await api.get(`/get-user-permission/${id}`);

        // Directly assign the array of IDs to form.permissions
        form.value.permissions = response.data;

        // Reset all checkboxes
        nav_tree.value.forEach((parent) => {
            parent.checked = false;
            parent.children.forEach((child) => {
                child.checked = false;
            });
        });

        // Apply permissions to the tree
        applyPermissions(form.value.permissions);
    } catch (error) {
        console.error("Error fetching permissions:", error);
    }
};

// Apply permissions to the tree
const applyPermissions = (permissions) => {
    nav_tree.value.forEach((parent) => {
        // Check if parent ID is in permissions
        if (permissions.includes(parent.id)) {
            parent.checked = true;
        }
        parent.children.forEach((child) => {
            // Check if child ID is in permissions
            if (permissions.includes(child.id)) {
                child.checked = true;
            }
        });
    });

    // Update the permissions list in the form
    updatePermissions();
    checkAllPermissionsState();
};

// Reset form data
const resetForm = () => {
    Object.keys(form.value).forEach((key) => {
        if (typeof form.value[key] === "string") {
            form.value[key] = "";
        }
    });
    allPermissionsChecked.value = false;
    nav_tree.value.forEach((parent) => {
        parent.checked = false;
        parent.children.forEach((child) => {
            child.checked = false;
        });
    });
};

// Handle parent checkbox toggle
const toggleParent = (parent) => {
    parent.children.forEach((child) => {
        child.checked = parent.checked;
    });
    updatePermissions();
    checkAllPermissionsState();
};

// Handle child checkbox toggle
const toggleChild = (parent) => {
    parent.checked = parent.children.some((child) => child.checked);
    updatePermissions();
    checkAllPermissionsState();
};

// Update permissions list based on checked state
const updatePermissions = () => {
    form.value.permissions = nav_tree.value.flatMap((parent) => {
        const parentPermission = parent.checked ? [parent.id] : [];
        const childPermissions = parent.children
            .filter((child) => child.checked)
            .map((child) => child.id);
        return [...parentPermission, ...childPermissions];
    });
};

// Check if all permissions are selected
const checkAllPermissionsState = () => {
    allPermissionsChecked.value = nav_tree.value.every((parent) => {
        return (
            parent.checked && parent.children.every((child) => child.checked)
        );
    });
};

// Handle "All Permissions" checkbox toggle
const toggleAllPermissions = () => {
    const checked = allPermissionsChecked.value;
    nav_tree.value.forEach((parent) => {
        parent.checked = checked;
        parent.children.forEach((child) => {
            child.checked = checked;
        });
    });
    updatePermissions();
};

// Handle form submission
const submit = async () => {
    try {
        const response = await api.post(`/set-permission`, form.value);
        if (response.data.success) {
            resetForm();
            Swal.fire({
                position: "middle",
                icon: "success",
                title: "Employee permission has been saved",
                showConfirmButton: false,
                timer: 1500,
            });
        }
    } catch (error) {
        console.error("Error submitting attendance:", error);
    }
};

onMounted(() => getData());
</script>

<template>
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="container">
            <div class="card">
                <div class="card-body">
                    <div class="text-center">
                        <h1 class="mb-5">User Page Permissions</h1>
                    </div>
                    <div class="row">
                        <div class="col-lg-12 col-sm-12 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <form @submit.prevent="submit">
                                        <div class="row">
                                            <div class="col-lg-5">
                                                <div class="form-group">
                                                    <label>Department</label>
                                                    <select
                                                        class="form-control"
                                                        v-model="
                                                            form.department
                                                        "
                                                        @change="
                                                            getEmployee(
                                                                form.department,
                                                            )
                                                        "
                                                    >
                                                        <option
                                                            selected
                                                            disabled
                                                        >
                                                            select
                                                        </option>
                                                        <option
                                                            v-for="dept in department"
                                                            :key="dept.id"
                                                            :value="dept.id"
                                                        >
                                                            {{ dept.Name }}
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-5">
                                                <div class="form-group">
                                                    <label>Employee Name</label>
                                                    <select
                                                        class="form-control"
                                                        v-model="
                                                            form.employee_Id
                                                        "
                                                        @change="
                                                            getPermission(
                                                                form.employee_Id,
                                                            )
                                                        "
                                                    >
                                                        <option
                                                            v-for="emp in empp"
                                                            :key="emp.id"
                                                            :value="emp.EID"
                                                        >
                                                            {{ emp.name }} -
                                                            {{ emp.Full_Name }}
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-lg-2 mt-4">
                                                <input
                                                    type="submit"
                                                    class="btn-submit"
                                                />
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12 col-sm-12 mb-3">
                            <div class="card permission">
                                <div class="card-body overflow-auto">
                                    <h3 class="text-center mb-4">Pages</h3>
                                    <div class="checkbox-container">
                                        <div class="all-permissions">
                                            <label>
                                                <input
                                                    type="checkbox"
                                                    v-model="
                                                        allPermissionsChecked
                                                    "
                                                    @change="
                                                        toggleAllPermissions
                                                    "
                                                />
                                                All Permissions
                                            </label>
                                        </div>
                                        <hr />
                                        <div
                                            v-for="parent in nav_tree"
                                            :key="parent.id"
                                            class="parent-checkbox"
                                        >
                                            <label>
                                                <input
                                                    type="checkbox"
                                                    v-model="parent.checked"
                                                    @change="
                                                        toggleParent(parent)
                                                    "
                                                />
                                                {{ parent.name }}
                                            </label>
                                            <div
                                                v-for="child in parent.children"
                                                :key="child.id"
                                                class="child-checkbox"
                                            >
                                                <label>
                                                    <input
                                                        type="checkbox"
                                                        v-model="child.checked"
                                                        @change="
                                                            toggleChild(parent)
                                                        "
                                                    />
                                                    {{ child.name }}
                                                </label>
                                            </div>
                                            <hr />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.parent-checkbox {
    margin-bottom: 10px;
}
.child-checkbox {
    margin-left: 20px;
}
.permission {
    max-height: 600px;
}

.checkbox-container {
    padding: 15px;
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.all-permissions {
    margin-bottom: 15px;
    font-weight: bold;
    font-size: 16px;
}

.parent-checkbox {
    margin-bottom: 10px;
    font-weight: bold;
}

.child-checkbox {
    margin-left: 20px;
    margin-bottom: 5px;
}
</style>
