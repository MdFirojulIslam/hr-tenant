<script setup>
import { reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { useStore } from "vuex";
import api from "@/api";
import Swal from "sweetalert2";


const router = useRouter();
const store = useStore();
const form = reactive({
    password: "",
    confirm_password: "",
});
const err = ref("");

const submit = async () => {
    try {
        const res = await api.put("/change-password", form);
        if (res.data.success) {
            Swal.fire({
                position: "middle",
                icon: "success",
                title: 'Password Changes Successfully',
                showConfirmButton: false,
                timer: 1500,
            });
            store.dispatch("removeToken", 0);
            router.push({ name: "Login" });
        } else {
            err.value = res.data.message || "An unknown error occurred";
        }
    } catch (error) {
        err.value =
            error.response?.data?.message || "An unexpected error occurred";
    }
};
</script>

<template>
    <div class="form-wrapper">
        <div class="form-container">
            <img
                src="/public/storage/uploads/1718087744_artisan.png"
                height="200px"
                width="200px"
            />
            <h3 class="mb-5">Change Password</h3>
            <form class="form" @submit.prevent="submit">
                <span class="text-danger">{{ err }}</span>
                <input
                    type="password"
                    class="input"
                    placeholder="New Password"
                    v-model="form.password"
                />
                <input
                    type="password"
                    class="input"
                    placeholder=" Confirm Password"
                    v-model="form.confirm_password"
                />
                <button class="form-btn">Change Password</button>
            </form>
        </div>
    </div>
</template>

<style scoped>
.form-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.form-container {
    width: 350px;
    height: 500px;
    background-color: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
    box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
    border-radius: 10px;
    box-sizing: border-box;
    padding: 20px 30px;
}

.form {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 18px;
    margin-bottom: 15px;
}

.input {
    border-radius: 20px;
    border: 1px solid #c0c0c0;
    outline: 0 !important;
    box-sizing: border-box;
    padding: 12px 15px;
}

h3 {
    color: #747474;
}

.form-btn {
    padding: 10px 15px;
    font-family: "Lucida Sans", "Lucida Sans Regular", "Lucida Grande",
        "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
    border-radius: 20px;
    border: 0 !important;
    outline: 0 !important;
    background: teal;
    color: white;
    cursor: pointer;
    box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
}
</style>
