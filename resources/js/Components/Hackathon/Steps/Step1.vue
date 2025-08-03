<script setup>
import DropFile from "@/Components/DropFile.vue";
import {ref} from "vue";
import axios    from 'axios'

const props = defineProps({
    hackathonSlug: { type: String, required: true },
    teamId:        { type: Number, required: true },
})

const emit = defineEmits(['success', 'cancel'])

const title       = ref('')
const description = ref('')
const preview     = ref(null)

const pending = ref(false)
const errors  = ref({})

async function submit () {
    pending.value = true
    errors.value  = {}

    try {
        const fd = new FormData()
        fd.append('title',       title.value)
        fd.append('description', description.value)
        if (preview.value) fd.append('preview', preview.value)

        const { data } = await axios.post(
            route('hackathons.teams.projects.store', {
                hackathon: props.hackathonSlug,
                team:      props.teamId,
            }),
            fd,
            { headers: { 'Content-Type': 'multipart/form-data' } },
        )

        emit('success', data.project)
        title.value = description.value = ''
        preview.value = null
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors
        } else {
            console.error(e)
        }
    } finally {
        pending.value = false
    }
}
</script>

<template>
    <div class="project__form">
        <div class="dialog__component">
            <p class="dialog__title">Название проекта</p>
            <input
                v-model="title"
                type="text"
                class="dialog__input"
                placeholder="Введите название проекта"
            >
            <span v-if="errors.title" class="error">{{ errors.title[0] }}</span>
        </div>
        <div class="dialog__component">
            <p class="dialog__title">Краткое описание</p>
            <textarea
                v-model="description"
                style="min-height: 208px"
                class="dialog__textarea"
                placeholder="Введите краткое описание"
            />
            <span v-if="errors.description" class="error">{{ errors.description[0] }}</span>
        </div>
        <div class="dialog__component">
            <p class="dialog__title">Превью проекта</p>
            <DropFile v-model:file="preview" />
            <span v-if="errors.preview" class="error">{{ errors.preview[0] }}</span>
        </div>
        <div class="project__form_btns">
            <button
                class="main__btn dialog__btn"
                :disabled="pending"
                @click.prevent="submit"
            >
                Далее
            </button>
            <button class="main__btn main__btn_white dialog__btn" @click="$emit('cancel')">
                Отменить
            </button>
        </div>
    </div>
</template>

<style scoped>

</style>
