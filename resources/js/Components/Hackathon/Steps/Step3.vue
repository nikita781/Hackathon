<script setup>
import {computed, onMounted, ref, watch} from "vue";
import DropPPTX from "@/Components/DropPPTX.vue";
import DropFiles from "@/Components/DropFiles.vue";
import axios from "axios";
import {useLangStore} from "@/store/lang.js";
import {router} from "@inertiajs/vue3";

const props = defineProps({
    hackathonSlug: { type: String, required: true },
    project: { type: Object, required: true },
    oneProject: { type: Object, required: true },
})

const emit = defineEmits(['success', 'cancel'])

watch(() => props.oneProject, () => {
    if (!props.oneProject.slug) return

    if (props.oneProject.video_link) {
        videoLink.value = props.oneProject.video_link
    }

    fetchPresentation(props.oneProject.slug)
    getGallery(props.oneProject.slug)
})

const galleryKey = ref(0)
const pptxKey    = ref(0)

const presentationUrl = ref(null)
const canDownloadServer = computed(
    () => hasServerPresentation.value && !isLocalFile.value && !!presentationUrl.value
)

async function fetchPresentation(projectSlug) {
    try {
        const { data } = await axios.get(
            route('hackathons.projects.presentation', {
                hackathon: props.hackathonSlug,
                project: projectSlug,
            }),
            { headers: { Accept: 'application/json' } }
        )
        hasServerPresentation.value   = true
        presentationUrl.value = data?.url ?? null
        serverPresentationFilename.value = data?.name || getPresentation(data?.url) || 'presentation'
        pptx.value = serverPresentationFilename.value
    } catch (e) {
        if (e?.response?.status === 404) {
            hasServerPresentation.value = false
            presentationUrl.value = null
            serverPresentationFilename.value = null
            if (typeof pptx.value === 'string') pptx.value = null
        } else {
            console.error('fetch-presentation', e?.response ?? e)
        }
    }
}

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

const hasServerPresentation = ref(false)
const serverPresentationFilename = ref(null)
const pendingDeleteServer = ref(false)

const isLocalFile      = computed(() => pptx.value instanceof File)
const canClearLocal    = computed(() => isLocalFile.value)
const canDeleteServer  = computed(() => hasServerPresentation.value && !isLocalFile.value)

const pptx = ref(null);
const existingGallery = ref([])
const newGalleryFiles = ref([])
const videoLink = ref('')
const deletedMediaIds = ref([]);

const pending = ref(false)
const errors = ref({})


function uniqFiles(files) {
    if (!Array.isArray(files)) return []
              const seen = new Set()
              return files.filter(f => {
                        if (!(f instanceof File)) return false
                        const key = `${f.name}|${f.size}|${f.lastModified ?? 0}`
                        if (seen.has(key)) return false
                        seen.add(key)
                        return true
                      })
}
const handleFilesUpdate = (newFiles) => {
    newGalleryFiles.value = uniqFiles(newFiles)
}

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
    if (!props.oneProject.slug) return

    if (props.oneProject.video_link) {
        videoLink.value = props.oneProject.video_link
    }

    fetchPresentation(props.oneProject.slug)
    getGallery(props.oneProject.slug)
})

function clearLocalPptx() {
    pptx.value = hasServerPresentation.value ? serverPresentationFilename.value : null
    if (errors.value.presentation) delete errors.value.presentation
    pptxKey.value++
}

async function deleteServerPresentation() {
    if (!hasServerPresentation.value) return
    pendingDeleteServer.value = true
    try {
        await router.delete(
            route('hackathons.projects.delete-presentation', {
                hackathon: props.hackathonSlug,
                project:   props.project.slug,
            }),
            { preserveScroll: true }
        )
        hasServerPresentation.value = false
        presentationUrl.value = null
        serverPresentationFilename.value = null
        if (typeof pptx.value === 'string') {
            pptx.value = null
        }
        if (errors.value.presentation) delete errors.value.presentation
        pptxKey.value++
    } catch (e) {
        console.error('delete-presentation', e?.response ?? e)
    } finally {
        pendingDeleteServer.value = false
    }
}

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
        if (pptx.value instanceof File) {
            await fetchPresentation(props.oneProject.slug)
            pptxKey.value++
        }
        await getGallery(props.oneProject.slug)
        galleryKey.value++
        newGalleryFiles.value  = []
        deletedMediaIds.value  = []

        emit('success')
    } catch (e) {
        console.error(e)
        if (e.response?.status === 422) {
            const errs = e.response.data?.errors || {}
            for (const k in errs) {
                const base = k.split('.')[0]
                if (!errs[base]) errs[base] = errs[k]
            }
            errors.value = errs
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
            <a
                v-if="canDownloadServer"
                class="main__btn dialog__btn"
                style="width: fit-content"
                :href="presentationUrl"
                download
                :title="serverPresentationFilename || 'presentation.pptx'"
            >
                Скачать
            </a>
            <DropPPTX :key="pptxKey" v-model:file="pptx" />
            <span v-if="errors.presentation" class="error__text">{{ errors.presentation[0] }}</span>
            <button
                v-if="canClearLocal"
                type="button"
                class="main__btn dialog__btn"
                style="width: fit-content"
                @click="clearLocalPptx"
            >
                Очистить файл
            </button>
            <button
                v-if="canDeleteServer"
                type="button"
                class="main__btn dialog__btn"
                style="width: fit-content"
                :disabled="pending || pendingDeleteServer"
                @click="deleteServerPresentation"
            >
                Удалить презентацию
            </button>
        </div>
        <div class="dialog__component">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.project_gallery) }}</p>
            <DropFiles
                :key="galleryKey"
                :files="existingGallery"
                @update:files="handleFilesUpdate"
                @deleting-ids="handleDeletingIds"
            />
            <span v-if="errors.gallery || errors['gallery.0']" class="error__text">
                {{ (errors.gallery ?? errors['gallery.0'])[0] }}
            </span>
        </div>
        <div class="dialog__component">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.projectLink) }}</p>
            <input
                v-model="videoLink"
                type="text"
                class="dialog__input"
                :placeholder="capitalizeFirstLetter(langStore.translations.videoLinkHint)"
            >
            <span v-if="errors.video_link" class="error__text">{{ errors.video_link[0] }}</span>
        </div>
        <div class="project__form_btns">
            <button
                class="main__btn dialog__btn"
                :disabled="pending"
                :aria-busy="pending"
                @click.prevent="submit"
            >
                {{ pending ? 'Отправка…' : capitalizeFirstLetter(langStore.translations.next) }}
            </button>
            <button class="main__btn main__btn_white dialog__btn" :disabled="pending" @click="cancel">
                {{ capitalizeFirstLetter(langStore.translations.cansel) }}
            </button>
        </div>
    </div>
</template>

<style scoped>

</style>
