<script setup>
import {ref} from "vue";
import DropPPTX from "@/Components/DropPPTX.vue";
import DropFiles from "@/Components/DropFiles.vue";

const props = defineProps({
    hackathonSlug: { type: String, required: true },
    project: { type: Object, required: true },
})

const emit = defineEmits(['success', 'cancel'])

const pptx = ref(null);
const projectImages = ref([])
const videoLink = ref('')

const pending = ref(false)
const errors = ref({})

async function submit() {
    pending.value = true
    errors.value = {}

    try {
        const fd = new FormData()
        fd.append('_method', 'PATCH')
        fd.append('presentation', pptx.value)
        projectImages.value.forEach(file => fd.append('gallery[]', file));
        fd.append('video_link', videoLink.value)

        const { data } = await axios.post(
            route('hackathons.projects.update', {
                hackathon: props.hackathonSlug,
                project: props.project.slug,
            }),
            fd,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        )
        emit('success')
    } catch (e) {
        console.error(e)
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors
        }
    } finally {
        pending.value = false
    }
}
</script>

<template>
    <div class="project__form">
        <div class="dialog__component">
            <p class="dialog__title">Презентация</p>
            <DropPPTX v-model:file="pptx" />
            <span v-if="errors.presentation" class="error">{{ errors.presentation[0] }}</span>
        </div>
        <div class="dialog__component">
            <p class="dialog__title">Галерея проекта</p>
            <DropFiles v-model:files="projectImages" />
            <span v-if="errors.gallery" class="error">{{ errors.gallery[0] }}</span>
        </div>
        <div class="dialog__component">
            <p class="dialog__title">Ссылка на видео</p>
            <input
                v-model="videoLink"
                type="text"
                class="dialog__input"
                placeholder="Укажите ссылку на видео (Rutube, VK видео)"
            >
            <span v-if="errors.video_link" class="error">{{ errors.video_link[0] }}</span>
        </div>
        <div class="project__form_btns">
            <button
                class="main__btn dialog__btn"
                :disabled="pending"
                @click.prevent="submit"
            >
                Далее
            </button>
            <button
                class="main__btn main__btn_white dialog__btn"
                type="button"
                @click="emit('cancel')"
            >
                Отменить
            </button>
        </div>
    </div>
</template>

<style scoped>

</style>
