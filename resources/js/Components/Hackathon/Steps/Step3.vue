<script setup>
import {onMounted, ref, watch} from "vue";
import DropPPTX from "@/Components/DropPPTX.vue";
import DropFiles from "@/Components/DropFiles.vue";
import axios from "axios";
import {useLangStore} from "@/store/lang.js";

const props = defineProps({
    hackathonSlug: { type: String, required: true },
    project: { type: Object, required: true },
    oneProject: { type: Object, required: true },
})

const emit = defineEmits(['success', 'cancel'])

watch(() => props.oneProject, () => {
    if (props.oneProject.slug) {
        if (props.oneProject.video_link) {
            videoLink.value = props.oneProject.video_link
        }
        pptx.value = props.oneProject.presentation_path
            ? getPresentation(props.oneProject.presentation_path)
            : null
    }
});

function getPresentation(presentation_path) {
    if (typeof presentation_path !== 'string' || !presentation_path) return null
    const i = presentation_path.lastIndexOf('/')
    return i >= 0 ? presentation_path.slice(i + 1) : presentation_path
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

        existingGallery.value = response.data.gallery ?? []
        newGalleryFiles.value = []
        deletedMediaIds.value = []
    } catch (e) {
        console.error('hackathon-load', e?.response ?? e);
    }
}

const pptx = ref(null);
const existingGallery = ref([])
const newGalleryFiles = ref([])
const videoLink = ref('')
const deletedMediaIds = ref([]);

const pending = ref(false)
const errors = ref({})


const handleFilesUpdate = (newFiles) => {
    newGalleryFiles.value = Array.isArray(newFiles) ? newFiles : []
};

const handleDeletingIds = (deletedIds) => {
    deletedMediaIds.value = Array.isArray(deletedIds) ? deletedIds : []
};

function cancel () {
    resetState()
    emit('cancel')
}

const resetState = () => {
    pptx.value = null
    existingGallery.value = []
    newGalleryFiles.value = []
    videoLink.value = ''
    errors.value      = {}
    pending.value     = false
    deletedMediaIds.value = []
}

onMounted(() => {
    if (props.oneProject.slug) {
        if (props.oneProject.video_link) {
            videoLink.value = props.oneProject.video_link
        }
        pptx.value = props.oneProject.presentation_path
            ? getPresentation(props.oneProject.presentation_path)
            : null
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
        for (const file of newGalleryFiles.value) {
            if (file instanceof File) fd.append('gallery[]', file)
        }
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

const langStore = useLangStore()

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

onMounted(async () => {
    await langStore.fetchTranslations()
});
</script>

<template>
    <div class="project__form">
        <div class="dialog__component">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.presentation) }}</p>
            <DropPPTX v-model:file="pptx" />
            <span v-if="errors.presentation" class="error">{{ errors.presentation[0] }}</span>
        </div>
        <div class="dialog__component">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.project_gallery) }}</p>
            <DropFiles
                :files="existingGallery"
                @update:files="handleFilesUpdate"
                @deleting-ids="handleDeletingIds"
            />
            <span v-if="errors.gallery" class="error">{{ errors.gallery[0] }}</span>
        </div>
        <div class="dialog__component">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.projectLink) }}</p>
            <input
                v-model="videoLink"
                type="text"
                class="dialog__input"
                :placeholder="capitalizeFirstLetter(langStore.translations.videoLinkHint)"
            >
            <span v-if="errors.video_link" class="error">{{ errors.video_link[0] }}</span>
        </div>
        <div class="project__form_btns">
            <button
                class="main__btn dialog__btn"
                :disabled="pending"
                @click.prevent="submit"
            >
                {{ capitalizeFirstLetter(langStore.translations.next) }}
            </button>
            <button
                class="main__btn main__btn_white dialog__btn"
                type="button"
                @click="cancel"
            >
                {{ capitalizeFirstLetter(langStore.translations.cansel) }}
            </button>
        </div>
    </div>
</template>

<style scoped>

</style>
