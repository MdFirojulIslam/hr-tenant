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
