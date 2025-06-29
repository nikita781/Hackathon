<!-- DropImage.vue -->
<script setup>
import { ref, onBeforeUnmount } from 'vue'

const emit = defineEmits(['update:file'])

const previewSrc = ref('')          // objectURL для <img>
const dragging   = ref(false)       // подсветка рамки при DnD
const fileRef = ref(null)

const revoke = () => {
    if (previewSrc.value) URL.revokeObjectURL(previewSrc.value)
    previewSrc.value = ''
}

function handleFiles(files) {
    if (!files?.length) return
    const file = files[0]
    if (!file.type.startsWith('image/')) return

    revoke()                           // очищаем прошлый objectURL
    previewSrc.value = URL.createObjectURL(file)
    fileRef.value = file

    emit('update:file', file)
}

/* <input type="file"> */
function onInput(e) { handleFiles(e.target.files) }

/* drag & drop */
function onDrop(e) {
    e.preventDefault()
    dragging.value = false
    handleFiles(e.dataTransfer.files)
}
function onDrag(e) {
    e.preventDefault()
    if (e.type === 'dragenter' || e.type === 'dragover') dragging.value = true
    else dragging.value = false
}

onBeforeUnmount(revoke)
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
        <!-- скрытый input -->
        <input type="file" accept="image/*" hidden @input="onInput" />

        <!-- когда файла ещё нет -->
        <template v-if="!previewSrc">
            <p class="hint">Перетащите или выберите файл<br>(JPG или PNG, &nbsp;5 MB&nbsp;максимальный размер файла)</p>
        </template>

        <!-- превью -->
        <img v-else :src="previewSrc" class="preview" />
    </label>
</template>

<style scoped>
.dropzone {
    width: 100%;
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

/* подсветка, когда тащат файл */
.dropzone.dragging { border-color: #E80024; }

.hint {
    text-align: center;
    font-size: 15px;
    color: #999;
    line-height: 1.3;
}

.preview {
    max-height: 150px;          /* главное ограничение по ТЗ */
    max-width : 100%;
    object-fit: contain;        /* серые поля по бокам / сверху */
}
</style>
