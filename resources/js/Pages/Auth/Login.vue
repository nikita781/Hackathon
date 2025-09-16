<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const form = useForm({
    login: '',
    password: '',
})

const submit = () => {
    form.post(route('login'))
}
</script>

<template>
    <div class="max-w-md mx-auto mt-10 p-6 bg-white rounded shadow">
        <h1 class="text-xl font-bold mb-4">Вход</h1>
        <form @submit.prevent="submit">
            <div class="mb-4">
                <label class="block mb-1">Логин или Email</label>
                <input
                    v-model="form.login"
                    type="text"
                    class="w-full border rounded px-3 py-2"
                />
                <div v-if="form.errors.login" class="text-red-500 text-sm mt-1">{{ form.errors.login }}</div>
            </div>

            <div class="mb-4">
                <label class="block mb-1">Пароль</label>
                <input
                    v-model="form.password"
                    type="password"
                    class="w-full border rounded px-3 py-2"
                />
                <div v-if="form.errors.password" class="text-red-500 text-sm mt-1">{{ form.errors.password }}</div>
            </div>

            <button
                type="submit"
                class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600"
                :disabled="form.processing"
            >
                Войти
            </button>
        </form>
    </div>
</template>
