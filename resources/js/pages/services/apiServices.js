import api from "@/api";

export const getDepartments = (userID) => {
    return api.get(`department-list/${userID}`);
};

export const getEmployeesByDept = (deptID) => {
    return api.get(`employee-list-by-department/${deptID}`);
};

export const getEmployeeImage = async (employeeId) => {
    const { data } = await api.get(`/empimg/${employeeId}`);
    return data?.img_url || data;
};

export const getDistributors = async () => {
    return api.get("/dms-distributors-info");
};

export const getEmployee = async () => {
    return api.get("/single-employee-info");
};

export const getDmsOutletCode = async (distributor_id, search) => {
    return api.get(`/dms-outlet-codes/${distributor_id}`, {
        params: search ? { q: search } : {},
    });
};
