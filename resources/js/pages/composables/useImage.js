import { ref, watch } from "vue";
import { getEmployeeImage } from "@/pages/services/apiServices";

export function useEmployeeImage(employeeIdRef) {
    const image = ref("/default-avatar.png");

    watch(
        employeeIdRef,
        async (id) => {
            if (!id) {
                image.value = "/default-avatar.png";
                return;
            }

            try {
                image.value = await getEmployeeImage(id);
            } catch (e) {
                console.error(e);
                image.value = "/default-avatar.png";
            }
        },
        {
            immediate: true,
        },
    );

    return {
        image,
    };
}
