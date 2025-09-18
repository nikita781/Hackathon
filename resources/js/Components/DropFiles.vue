<script setup>
import { ref, watch, onBeforeUnmount, onMounted } from 'vue'
import { useLangStore } from '@/store/lang.js'

const props = defineProps({
    // существующие медиа: [{ id:number, url:string }, ...]
    files: {type: Array, default: () => []},
    // ограничения (опционально)
    maxCount: {type: Number, default: 0}, // 0 = без лимита
    maxSizeMB: {type: Number, default: 10},
})
const emit = defineEmits(['update:files', 'deleting-ids', 'error'])

const langStore = useLangStore()

const dragging = ref(false)
const inputEl = ref(null)

const uid = () => Math.random().toString(36).slice(2)
const objectURLs = new Set()

// Унифицированные элементы:
// { id?:number, file?:File, url:string, isNew:boolean, _key:string }
const items = ref([])
const deletedIds = ref([])

function toItem(x) {
    if (x && typeof x === 'object' && 'id' in x && 'url' in x) {
        return {id: Number(x.id), url: String(x.url), isNew: false, _key: String(x.id)}
    }
    if (x instanceof File) {
        const url = URL.createObjectURL(x)
        objectURLs.add(url)
        return {file: x, url, isNew: true, _key: uid()}
    }
    if (x && typeof x === 'object' && 'url' in x) {
        return {url: String(x.url), isNew: false, _key: uid()}
    }
    return null
}

// синхронизация входящих файлов (существующие с сервера)
watch(() => props.files, (arr) => {
    // сохраняем уже добавленные новые, чтобы не терять их при внешнем обновлении
    const keepNew = items.value.filter(i => i.isNew)
    items.value = []
    if (Array.isArray(arr)) {
        for (const el of arr) {
            const it = toItem(el)
            if (it) items.value.push(it)
        }
    }
    items.value.push(...keepNew)
    syncEmits()
}, {immediate: true, deep: true})

function addFiles(fileList) {
    const errs = []
    const maxBytes = props.maxSizeMB * 1024 * 1024

    for (const f of fileList) {
        if (!f.type.startsWith('image/')) continue
        if (props.maxCount && (items.value.length + 1) > props.maxCount) {
            errs.push(`Лимит изображений: ${props.maxCount}`)
            break
        }
        if (f.size > maxBytes) {
            errs.push(`«${f.name}» больше ${props.maxSizeMB} МБ`)
            continue
        }
        const it = toItem(f)
        if (it) items.value.push(it)
    }

    syncEmits()
    if (errs.length) emit('error', errs.join('\n'))
}

function remove(idx) {
    const it = items.value[idx]
    if (!it) return

    if (!it.isNew && typeof it.id === 'number') {
        if (!deletedIds.value.includes(it.id)) deletedIds.value.push(it.id)
    }

    if (it.isNew && it.url && objectURLs.has(it.url)) {
        URL.revokeObjectURL(it.url)
        objectURLs.delete(it.url)
    }

    items.value.splice(idx, 1)
    syncEmits()
}

function syncEmits() {
    const newFiles = items.value.filter(i => i.isNew && i.file instanceof File).map(i => i.file)
    emit('update:files', newFiles)
    emit('deleting-ids', deletedIds.value.slice())
}

function onInput(e) {
    addFiles(e.target.files || []);
    e.target.value = ''
}

function onDrop(e) {
    e.preventDefault();
    dragging.value = false;
    addFiles(e.dataTransfer.files || [])
}

function onDrag(e) {
    e.preventDefault();
    dragging.value = (e.type === 'dragenter' || e.type === 'dragover')
}

onMounted(async () => {
    await langStore.fetchTranslations()
})
onBeforeUnmount(() => {
    for (const url of objectURLs) URL.revokeObjectURL(url)
    objectURLs.clear()
})

function capitalizeFirstLetter(str) {
    if (!str) return str
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase()
}
</script>

<template>
    <label class="dropzone" :class="{ dragging }"
           @drop="onDrop" @dragover="onDrag" @dragenter="onDrag" @dragleave="onDrag">
        <input ref="inputEl" type="file" hidden multiple accept="image/*" @change="onInput"/>

        <template v-if="!items.length">
            <p class="hint">{{ capitalizeFirstLetter(langStore.translations.file_upload_hint) }}</p>
        </template>

        <template v-else>
            <div class="thumb" v-for="(it,idx) in items" :key="it.id ?? it._key">
                <img :src="it.url" alt="Photo"/>
                <div class="mask" @click.stop="remove(idx)"/>
            </div>
        </template>
    </label>
</template>

<style scoped>
.dropzone {
    min-height: 150px;
    background: #f3f4f7;
    border-radius: 8px;
    display: flex;
    justify-content: center;
    align-items: center;
    border: 2px dashed transparent;
    transition: border-color .15s;
    cursor: pointer;
    user-select: none;
    padding: 8px;
    flex-wrap: wrap;
    gap: 12px
}

.dropzone.dragging {
    border-color: #E80024
}

.hint {
    color: #999;
    text-align: center;
    line-height: 1.3;
    font-size: 15px
}

.thumb {
    position: relative;
    width: 120px;
    height: 120px;
    border-radius: 6px;
    overflow: hidden
}

.thumb img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: #fff
}

.mask {
    position: absolute;
    inset: 0;
    background: rgba(232, 0, 36, 0);
    transition: background .15s;
}

.thumb:hover .mask {
    background: rgba(232, 0, 36, .25);
    cursor: pointer
}
</style>
