<script setup>
import {ref, onBeforeUnmount, watch, onMounted} from 'vue'
import {useLangStore} from "@/store/lang.js";
import logs from "../../../vendor/laravel/telescope/resources/js/screens/logs/index.vue";

const props = defineProps({
    file: { type: [File, String, null], default: null }
})
const emit = defineEmits(['update:file'])

const langStore = useLangStore()

const previewSrc = ref('')
const dragging   = ref(false)
let   blobURL    = ''

function revoke () {
    if (blobURL) {
        URL.revokeObjectURL(blobURL)
        blobURL = ''
    }
    previewSrc.value = ''
}

watch(
    () => props.file,
    (f) => {
        revoke()

        if (!f) return

        if (f instanceof File) {
            blobURL = URL.createObjectURL(f)
            previewSrc.value = blobURL
        }
        else if (typeof f === 'string') {
            previewSrc.value = f
        }
    },
    { immediate: true }
)

function handleFiles (files) {
    if (!files?.length) return
    const file = files[0]
    if (!file.type.startsWith('image/')) return

    revoke()
    blobURL = URL.createObjectURL(file)
    previewSrc.value = blobURL

    emit('update:file', file)
}

function onInput (e)       { handleFiles(e.target.files) }
function onDrop  (e)       { e.preventDefault(); dragging.value=false; handleFiles(e.dataTransfer.files) }
function onDrag  (e)       { e.preventDefault(); dragging.value = e.type==='dragenter' || e.type==='dragover' }

onBeforeUnmount(revoke)

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

onMounted(async () => {
    await langStore.fetchTranslations()
});
</script>

<template>
    <label
        class="dropzone"
        :class="{ dragging }"
        @drop="onDrop"
        @dragover="onDrag"
        @dragenter="onDrag"
        @dragleave="onDrag"
    >
        <input type="file" accept="image/*" hidden @input="onInput" />

        <template v-if="!previewSrc">
            <p class="hint">
                {{ capitalizeFirstLetter(langStore.translations.file_upload_hint) }}
            </p>
        </template>

        <img v-else :src="previewSrc" class="preview" />
    </label>
</template>

<style scoped>
.dropzone {
    min-height: 150px;
    background: #f3f4f7;
    border-radius: 8px;
    border: 2px dashed transparent;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    user-select: none;
    transition: border-color .15s;
}

.dropzone.dragging { border-color: #E80024; }

.hint {
    text-align: center;
    font-size: 15px;
    color: #999;
    line-height: 1.3;
}

.preview {
    max-height: 150px;
    max-width : 100%;
    object-fit: contain;
}
</style>
