<script setup>
import {Head} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
    hackathon: Object,
    canEdit: Boolean
})

console.log(props.canEdit)
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
                    <div class="p-6 text-gray-900">
                        <p>Hackathon title: {{ hackathon.title }}</p>
                        <p>format: {{ hackathon.format }}</p>
                        <p>type: {{ hackathon.type }}</p>
                        <p>start: {{ hackathon.event_start }}</p>
                        <p>end: {{ hackathon.event_end }}</p>
                        <p>tags: {{ hackathon.tags.map(tag => tag.title).join(', ') }}</p>
                        <br>
                        <div v-for="project in hackathon.projects">
                            <p>Project title: {{ project.title }}</p>
                            <p>description: {{ project.description }}</p>
                            <p>capitan: {{ project.capitan.name }}</p>
                            <div v-for="member in project.members">
                                <p>member: {{ member.name}}</p>
                                <p>pos: {{ member.pivot.position.name}}</p>
                            </div>
                            <br>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

