<script setup>
import { router } from '@inertiajs/vue3'
import { defineProps } from 'vue'

const props = defineProps({
    users: Array,
})

const loginAs = (userId) => {
    router.visit(route('auth.redirect', { code: userId }))
}
</script>

<template>
    <div class="container">
        <h1 class="title">Выберите пользователя</h1>
        <ul class="user-list">
            <li
                v-for="user in users"
                :key="user.id"
                class="user-card"
                @click="loginAs(user.id)"
            >
                <div class="user-info">
                    <p class="user-name">{{ user.name }}</p>
                    <p class="user-email">{{ user.email }}</p>
                    <p class="user-role">Роли: {{ user.roles.map(role => role.title).join(', ') }}</p>
                </div>
                <span class="user-action">Войти →</span>
            </li>
        </ul>
    </div>
</template>

<style>
.container {
    max-width: 960px;
    margin: 80px auto;
    padding: 0 16px;
    font-family: sans-serif;
}

.title {
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 24px;
    text-align: center;
}

.user-list {
    list-style: none;
    padding: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
}

.user-card {
    background: #f5f5f5;
    border-radius: 12px;
    padding: 20px;
    width: 260px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    transition: background 0.2s ease;
    border: 1px solid #ddd;
}

.user-card:hover {
    background: #eaeaea;
}

.user-info {
    max-width: 70%;
}

.user-name {
    font-weight: 600;
    font-size: 16px;
    margin-bottom: 4px;
}

.user-email {
    font-size: 14px;
    color: #666;
    margin-bottom: 6px;
}

.user-role {
    font-size: 13px;
    color: #0077cc;
}

.user-action {
    color: #0077cc;
    font-weight: 500;
    white-space: nowrap;
}
</style>
