// ref() computed() watch() loading debounce pagination search business rule

import { ref } from "vue";
import { getDmsOutletCode } from "@/pages/services/apiServices";

export function outletCodeInfo() {
    const dmsOutletCodeInfo = ref([]);
    const loading = ref(false);
    const error = ref(null);

    let isActive = true;

    function destroy() {
        isActive = false;
    }

    async function loadDmsOutletCode(distributor_id) {
        loading.value = true;
        error.value = null;

        try {
            const res = await getDmsOutletCode(distributor_id);

            if (!isActive) return;

            dmsOutletCodeInfo.value = res.data.data ?? [];
        } catch (err) {
            if (!isActive) return;

            error.value = err;
            dmsOutletCodeInfo.value = [];
        } finally {
            if (!isActive) return;

            loading.value = false;
        }
    }

    async function searchDmsOutletCode(distributor_id, search) {
        if (!distributor_id || !search) return;

        const query = String(search).trim().toLowerCase();
        if (!query) return;

        const alreadyLoaded = dmsOutletCodeInfo.value.some((outlet) =>
            String(outlet.outlet_code).toLowerCase().includes(query),
        );
        if (alreadyLoaded) return;

        try {
            const res = await getDmsOutletCode(distributor_id, search);

            if (!isActive) return;

            const existingCodes = new Set(
                dmsOutletCodeInfo.value.map((outlet) =>
                    String(outlet.outlet_code).toLowerCase(),
                ),
            );

            const matches = (res.data.data ?? []).filter(
                (outlet) =>
                    !existingCodes.has(
                        String(outlet.outlet_code).toLowerCase(),
                    ) &&
                    String(outlet.outlet_code).toLowerCase().includes(query),
            );

            if (matches.length > 0) {
                dmsOutletCodeInfo.value = [
                    ...dmsOutletCodeInfo.value,
                    ...matches,
                ];
            }
        } catch (err) {
            if (!isActive) return;

            error.value = err;
        }
    }
    return {
        dmsOutletCodeInfo,
        loading,
        error,
        destroy,
        loadDmsOutletCode,
        searchDmsOutletCode,
    };
}
