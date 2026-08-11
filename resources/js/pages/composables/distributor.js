// import { ref } from "vue";
// import { getDistributors } from "@/pages/services/apiServices";

// export function dmsDistributor() {
//     const distributors_info = ref([]);
//     const loading = ref(false);
//     const error = ref(null);
//     let is_active = true;

//     function destroy() {
//         is_active = false;
//     }

//     async function loadDmsDistributorInfo() {
//         loading.value = true;
//         try {
//             const res = await getDistributors();
//             if (!is_active) return;
//             distributors_info.value = res.data;
//         } catch (err) {
//             if (!is_active) return;
//             error.value = err;
//         } finally {
//             if (!is_active) return;
//             loading.value = false;
//         }
//     }
//     return {
//         distributors_info,
//         loading,
//         error,
//         destroy,
//         loadDmsDistributorInfo,
//     };
// }

import { ref } from "vue";
import { getDistributors } from "@/pages/services/apiServices";

export function dmsDistributor() {
    const distributors_info = ref([]);
    const loading = ref(false);
    const error = ref(null);

    let is_active = true;

    function destroy() {
        is_active = false;
    }

    async function loadDmsDistributorInfo() {
        loading.value = true;
        error.value = null;

        try {
            const res = await getDistributors();

            if (!is_active) return;

            distributors_info.value = res.data.data ?? [];
        } catch (err) {
            if (!is_active) return;

            error.value = err;
            distributors_info.value = [];
        } finally {
            if (!is_active) return;

            loading.value = false;
        }
    }

    return {
        distributors_info,
        loading,
        error,
        destroy,
        loadDmsDistributorInfo,
    };
}
