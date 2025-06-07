<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
})

</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ user.name }}</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">{{ user.roles.map(role => role.title).join(', ') }}</div>
<!--                    <div v-if="can.create">-->
<!--                        <h2 class="px-6 pt-6">Мои хакатоны:</h2>-->
<!--                        <div v-for="hackathon in hackathons" :key="hackathon.id" class="p-6 text-gray-900">-->
<!--                            <a :href="route('hackathons.show', hackathon.slug)">Show</a>-->
<!--                            <p v-if="hackathon.is_published">Опубликовано</p>-->
<!--                            <p v-else>Не опубликовано</p>-->
<!--                            <p>title: {{ hackathon.title }}</p>-->
<!--                            <img :src="hackathon.image_path" alt="preview" class="w-1/4">-->
<!--                            <p>format: {{ hackathon.format }}</p>-->
<!--                            <p>type: {{ hackathon.type }}</p>-->
<!--                            <p>start: {{ hackathon.event_start }}</p>-->
<!--                            <p>end: {{ hackathon.event_end }}</p>-->
<!--                            <p>tags: {{ hackathon.tags.map(tag => tag.title).join(', ') }}</p>-->
<!--                        </div>-->
<!--                    </div>-->
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
