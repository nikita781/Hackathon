<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import {Head, Link} from '@inertiajs/vue3';
import Header from "@/Components/Header.vue";
import Footer from "@/Components/Footer.vue";

const showingNavigationDropdown = ref(false);

const props = defineProps({
    auth: { type: Object, required: true },
    notifications: { type: Object, required: true },
})
</script>

<template>
    <Head title="FONCODE" />

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

                <Footer style="z-index: 2"/>
            </div>
        </div>
    </div>
</template>

<style lang="scss">
.layout {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.layout__main {
    flex: 1 0 auto;
}
</style>
