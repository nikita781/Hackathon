<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
    hackathon: Object,
    tabs: Object,
    can: Object,
})

const form = useForm({
    title: '',
    content: '',
})

const submit = () => {
    form.put(route('hackathons.tabs.update', props.hackathon.slug), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        }
    })
}

console.log(props.hackathon)
console.log(props.tabs)
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
                        <img :src="hackathon.image_path" alt="preview" class="w-1/4">
                        <p>format: {{ hackathon.format }}</p>
                        <p>type: {{ hackathon.type }}</p>
                        <p>start: {{ hackathon.event_start }}</p>
                        <p>end: {{ hackathon.event_end }}</p>
                        <p>tags: {{ hackathon.tags.map(tag => tag.title).join(', ') }}</p>
                        <div>
                            <form v-if="props.can.update" @submit.prevent="submit">
                                <label for="title">Выберите вкладку</label>
                                <select v-model="form.title" class="mb-2">
                                    <option disabled value="">Выберите</option>
                                    <option v-for="tab in hackathon.tabs" :key="tab.title" :value="tab.title">{{ tab.title }}</option>
                                </select>

                                <label for="content">Содержимое</label>
                                <textarea v-model="form.content" rows="5" class="w-full border mb-2"></textarea>

                                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded" :disabled="form.processing">
                                    Сохранить
                                </button>

                                <p v-if="form.recentlySuccessful" class="text-green-600 mt-2">Сохранено!</p>
                            </form>
                        </div>
                        <br>
                        <div v-for="tab in hackathon.tabs">
                            <p>Tab title: {{ tab.title }}</p>
                            <p>description: {{ tab.content }}</p>
                            <p>count images: {{ tab.images.length }}</p>
                            <br>
                        </div>
                        <br>
                        <div v-for="project in hackathon.projects">
                            <p>Project title: {{ project.title }}</p>
                            <p>description: {{ project.description }}</p>
                            <p>Count images: {{ project.images.length }}</p>
                            <p>capitan: {{ project.capitan.name }}</p>
                            <div v-for="member in project.members">
                                <p>member: {{ member.name}}</p>
                                <p>pos: {{ member.position.name}}</p>
                            </div>
                            <br>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

