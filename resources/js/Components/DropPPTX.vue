<!-- DropFile.vue -->
<script setup>
import { ref, onBeforeUnmount, watch, onMounted } from 'vue'
import { useLangStore } from "@/store/lang.js"

const props = defineProps({
    file: { type: [File, String, null], default: null }
})
const emit = defineEmits(['update:file'])

const langStore = useLangStore()

const fileName = ref('')
const dragging = ref(false)
let blobURL = ''

function revoke() {
    if (blobURL) {
        URL.revokeObjectURL(blobURL)
        blobURL = ''
    }
    fileName.value = ''
}

watch(
    () => props.file,
    (f) => {
        revoke()

        if (!f) return

        if (f instanceof File) {
            fileName.value = f.name
        } else if (typeof f === 'string') {
            fileName.value = f
        }
    },
    { immediate: true }
)

function handleFiles(files) {
    if (!files?.length) return
    const file = files[0]
    // Проверка на допустимые типы файлов для презентаций
    const allowedTypes = ['application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/pdf', 'application/vnd.oasis.opendocument.presentation']
    if (!allowedTypes.includes(file.type)) return

    revoke()
    fileName.value = file.name

    emit('update:file', file)
}

function onInput(e) { handleFiles(e.target.files) }
function onDrop(e) { e.preventDefault(); dragging.value = false; handleFiles(e.dataTransfer.files) }
function onDrag(e) { e.preventDefault(); dragging.value = e.type === 'dragenter' || e.type === 'dragover' }

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
        <input type="file" accept=".ppt,.pptx,.pdf,.odp" hidden @input="onInput" />

        <template v-if="!fileName">
            <p class="hint">
                {{ capitalizeFirstLetter(langStore.translations.uploadFile) }}
            </p>
        </template>

        <div v-else>
            <p class="file-name">{{ fileName }}</p>
        </div>
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

.file-name {
    font-size: 16px;
    color: #333;
    text-align: center;
}
</style>
