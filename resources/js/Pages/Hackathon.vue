<script setup>

import {Head} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
    hackathons: Object
})
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Hackathons</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div v-for="hackathon in hackathons" class="p-6 text-gray-900">
                        <p>title: {{ hackathon.title }}</p>
                        <p>format: {{ hackathon.format }}</p>
                        <p>type: {{ hackathon.type }}</p>
                        <p>start: {{ hackathon.event_start }}</p>
                        <p>end: {{ hackathon.event_end }}</p>
                        <p>tags: {{ hackathon.tags.map(tag => tag.title).join(', ') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
