<script setup>
import {onMounted, ref, watch} from "vue";
import DropPPTX from "@/Components/DropPPTX.vue";
import DropFiles from "@/Components/DropFiles.vue";
import axios from "axios";

const props = defineProps({
    hackathonSlug: { type: String, required: true },
    project: { type: Object, required: true },
    oneProject: { type: Object, required: true },
})

const emit = defineEmits(['success', 'cancel'])

watch(() => props.oneProject, () => {
    if (props.oneProject.slug) {
        videoLink.value = props.oneProject.video_link
        pptx.value = getPresentation(props.oneProject.presentation_path)
    }
});

function getPresentation(presentation_path) {
    return presentation_path.split('/').pop();
}

async function getGallery(slugId) {
    try {
        const response = await axios.get(
            route('hackathons.projects.gallery', { hackathon: props.hackathonSlug, project: slugId }),
            {
                headers: {
                    'Accept': 'application/json'
                }
            }
        );

        projectImages.value = response.data.gallery
        console.log(projectImages.value)
    } catch (e) {
        console.error('hackathon-load', e?.response ?? e);
    }
}

const pptx = ref(null);
const projectImages = ref([])
const videoLink = ref('')
const deletedMediaIds = ref([]);

const pending = ref(false)
const errors = ref({})


const handleFilesUpdate = (newFiles) => {
    projectImages.value = newFiles;
};

const handleDeletingIds = (deletedIds) => {
    deletedMediaIds.value = deletedIds;
};

function cancel () {
    resetState()
    emit('cancel')
}

const resetState = () => {
    pptx.value = null
    projectImages.value = []
    videoLink.value = ''
    errors.value      = {}
    pending.value     = false
}

onMounted(() => {
    if (props.oneProject.slug) {
        videoLink.value = props.oneProject.video_link
        pptx.value = getPresentation(props.oneProject.presentation_path)
        getGallery(props.oneProject.slug)
    }
})

async function submit() {
    pending.value = true
    errors.value = {}

    try {
        const fd = new FormData()
        fd.append('_method', 'PATCH')
        if (pptx.value && pptx.value instanceof File) {
            fd.append('presentation', pptx.value)
        }
        projectImages.value.forEach(file => {
            if (file instanceof File) {
                fd.append('gallery[]', file);
            }
        });
        // Если есть удалённые медиафайлы, передаём их
        // if (deletedMediaIds.value.length) {
        //     fd.append('delete_media_ids', deletedMediaIds.value); // передаем массив удалённых файлов
        //     console.log(deletedMediaIds.value)
        // }
        if (deletedMediaIds.value.length) {
            deletedMediaIds.value.forEach(id => {
                fd.append('delete_media_ids[]', id);
            });
        }
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
            <DropFiles :files="projectImages" @update:files="handleFilesUpdate" @deleting-ids="handleDeletingIds" />
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
                @click="cancel"
            >
                Отменить
            </button>
        </div>
    </div>
</template>

<style scoped>

</style>
