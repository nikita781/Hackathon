<script setup>

import {Head, useForm, usePage} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const page = usePage()
const props = defineProps({
    hackathons: Object,
    can: Object,
    tags: Object,
})

const form = useForm({
    title: '',
    format: '',
    type: '',
    // registration_start: '',
    registration_end: '',
    event_start: '',
    event_end: '',
    prize_pool: '',
    min_team_size: '',
    max_team_size: '',
    image_path: null,
    tags: [],
})

function submit() {
    form.post(route('hackathons.store'))
}

console.log(props.hackathons);
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
                    <div v-if="can.create">

                        <form @submit.prevent="submit" class="space-y-4 max-w-xl mx-auto p-4" enctype="multipart/form-data">

                            <div>
                                <label class="block">Название хакатона</label>
                                <input v-model="form.title" type="text" class="border px-2 py-1 w-full" />
                                <div v-if="form.errors.title" class="text-red-500 text-sm">{{ form.errors.title }}</div>
                            </div>

                            <div>
                                <label class="block">Формат</label>
                                <select v-model="form.format" class="border px-2 py-1 w-full">
                                    <option value="">Выберите</option>
                                    <option value="online">Онлайн</option>
                                    <option value="offline">Офлайн</option>
                                    <option value="hybrid">Гибрид</option>
                                </select>
                                <div v-if="form.errors.format" class="text-red-500 text-sm">{{ form.errors.format }}</div>
                            </div>

                            <div>
                                <label class="block">Тип</label>
                                <select v-model="form.type" class="border px-2 py-1 w-full">
                                    <option value="">Выберите</option>
                                    <option value="team">Командный</option>
                                    <option value="individual">Индивидуальный</option>
                                </select>
                                <div v-if="form.errors.type" class="text-red-500 text-sm">{{ form.errors.type }}</div>
                            </div>

                            <div class="flex gap-4">
                                <div class="w-1/2">
                                    <label>Мин. участников</label>
                                    <input v-model="form.min_team_size" :disabled="form.type === 'individual'" type="number" min="1" class="border px-2 py-1 w-full" />
                                    <div v-if="form.errors.min_team_size" class="text-red-500 text-sm">{{ form.errors.min_team_size }}</div>
                                </div>

                                <div class="w-1/2">
                                    <label>Макс. участников</label>
                                    <input v-model="form.max_team_size" :disabled="form.type === 'individual'" type="number" min="1" class="border px-2 py-1 w-full" />
                                    <div v-if="form.errors.max_team_size" class="text-red-500 text-sm">{{ form.errors.max_team_size }}</div>
                                </div>
                            </div>
<!--                                <div>-->
<!--                                    <label>Начало регистрации</label>-->
<!--                                    <input v-model="form.registration_start" type="date" class="border px-2 py-1 w-full" />-->
<!--                                </div>-->
                            <div>
                                <label>Конец регистрации</label>
                                <input v-model="form.registration_end" type="date" class="border px-2 py-1 w-full" />
                                <div v-if="form.errors.registration_end" class="text-red-500 text-sm">{{ form.errors.registration_end }}</div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">


                                <div>
                                    <label>Начало мероприятия</label>
                                    <input v-model="form.event_start" type="date" class="border px-2 py-1 w-full" />
                                    <div v-if="form.errors.event_start" class="text-red-500 text-sm">{{ form.errors.event_start }}</div>
                                </div>
                                <div>
                                    <label>Конец мероприятия</label>
                                    <input v-model="form.event_end" type="date" class="border px-2 py-1 w-full" />
                                    <div v-if="form.errors.event_end" class="text-red-500 text-sm">{{ form.errors.event_end }}</div>
                                </div>
                            </div>

                            <div>
                                <label>Призовой фонд (₽)</label>
                                <input v-model="form.prize_pool" type="number" class="border px-2 py-1 w-full" />
                                <div v-if="form.errors.prize_pool" class="text-red-500 text-sm">{{ form.errors.prize_pool }}</div>
                            </div>

                            <div>
                                <label>Картинка</label>
                                <input type="file" @change="e => form.image_path = e.target.files[0]" accept="image/*" class="border px-2 py-1 w-full">
                                <div v-if="form.errors.image_path" class="text-red-500 text-sm mt-1">{{ form.errors.image_path }}</div>
                            </div>

                            <div>
                                <label>Теги</label>
                                <select v-model="form.tags" multiple class="border px-2 py-1 w-full">
                                    <option v-for="tag in tags" :key="tag.id" :value="tag.id">{{ tag.title }}</option>
                                </select>
                                <div v-if="form.errors.tags" class="text-red-500 text-sm">{{ form.errors.tags }}</div>
                            </div>

                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">
                                Создать хакатон
                            </button>
                        </form>
                    </div>
                    <div v-for="hackathon in hackathons" :key="hackathon.id" class="p-6 text-gray-900">
                        <a :href="route('hackathons.show', hackathon.slug)">Show</a>
                        <p v-if="hackathon.can_update">Можно изменить</p>
                        <p>title: {{ hackathon.title }}</p>
                        <img :src="hackathon.image_path" alt="preview" class="w-1/4">
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
