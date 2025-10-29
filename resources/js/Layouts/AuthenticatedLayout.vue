<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';
import Header from "@/Components/Header.vue";
import Footer from "@/Components/Footer.vue";

const showingNavigationDropdown = ref(false);

const props = defineProps({
    auth: { type: Object, required: true },
    notifications: { type: Object, required: true },
})
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <div class="layout">
                <Header
                    :auth="props.auth"
                    :notifications="props.notifications"
                    style="z-index: 2"
                />

                <main class="layout__main">
                    <slot/>
                </main>

<!--                <Footer style="z-index: 2"/>-->
                <Footer />
            </div>
        </div>
    </div>
</template>

<style lang="scss">
/* 1) колонка во всю высоту окна */
.layout {
    min-height: 100vh;          /* 100 % высоты вьюпорта */
    display: flex;
    flex-direction: column;
}

/* 2) «растяжка» центральной области */
.layout__main {
    flex: 1 0 auto;             /* grow = 1 → заполняем остаток */
}
</style>
