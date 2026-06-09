<script setup>
import {reactive, ref, watch, onBeforeUnmount, onMounted, nextTick} from 'vue'
import {useLangStore} from '@/store/lang.js'

const props = defineProps({
    files: {type: Array, default: () => []},
    maxSizeMb: {type: Number, default: 5},
})
const emit = defineEmits(['update:files', 'deleting-ids'])

const langStore = useLangStore()
const inputEl = ref(null)
const dragging = ref(false)
const deletedIds = ref([])

const items = reactive([])
const seenKeys = reactive(new Set())

function keyOfExisting(x) {
    return `e:${x?.id ?? x?.url ?? x?.name ?? ''}`
}
function keyOfFile(f) {
    return `n:${f?.name ?? ''}#${f?.size ?? 0}#${f?.lastModified ?? 0}`
}

function revokeNewURLs() {
    items.forEach(i => {
        if (i.kind === 'new' && i.url) URL.revokeObjectURL(i.url)
    })
}

function rebuildFromProp(list) {
    revokeNewURLs()
    items.splice(0, items.length)
    seenKeys.clear()

    ;(list ?? []).forEach(x => {
        if (x instanceof File) {
            const k = keyOfFile(x)
            if (seenKeys.has(k)) return
            seenKeys.add(k)
            const url = URL.createObjectURL(x)
            items.push({kind: 'new', key: k, name: x.name, url, file: x})
        } else if (x && (x.url || x.download_url)) {
            const k = keyOfExisting(x)
            if (seenKeys.has(k)) return
            seenKeys.add(k)
            items.push({
                kind: 'existing',
                key: k,
                id: x.id,
                name: x.name || x.original_name || (x.url || '').split('/').pop(),
                url: x.url || x.download_url
            })
        }
    })
}

watch(() => props.files, rebuildFromProp, {immediate: true, deep: true})

function emitFiles() {
    const mixed = items.map(i => (i.kind === 'new' ? i.file : {id: i.id, name: i.name, url: i.url}))
    emit('update:files', mixed)
}

function emitDeletes() {
    emit('deleting-ids', [...deletedIds.value])
}

function addFiles(fileList) {
    ;[...fileList].forEach(f => {
        if (f.type !== 'application/pdf') return
        if (props.maxSizeMb && f.size > props.maxSizeMb * 1024 * 1024) return
        const k = keyOfFile(f)
        if (seenKeys.has(k)) return
        seenKeys.add(k)
        const url = URL.createObjectURL(f)
        items.push({kind: 'new', key: k, name: f.name, url, file: f})
    })
    emitFiles()
}

function onInput(e) {
    addFiles(e.target.files);
    e.target.value = ''
}

function remove(idx) {
    const it = items[idx]
    if (!it) return

    if (it.kind === 'existing' && it.id) {
        deletedIds.value.push(it.id)
        emitDeletes()
    }
    if (it.kind === 'new') URL.revokeObjectURL(it.url)

    if (it.key) seenKeys.delete(it.key)
    items.splice(idx, 1)
    emitFiles()
}

function onDrop(e) {
    e.preventDefault();
    dragging.value = false;
    addFiles(e.dataTransfer.files)
}

function onDrag(e) {
    e.preventDefault();
    dragging.value = e.type === 'dragenter' || e.type === 'dragover'
}
onBeforeUnmount(revokeNewURLs)

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
        <input
            ref="inputEl"
            type="file"
            accept="application/pdf"
            hidden
            multiple
            @change="onInput"
        />
        <template v-if="!items.length">
            <p class="hint">
                {{ capitalizeFirstLetter(langStore.translations.upload_files_hint) }}
            </p>
        </template>

        <template v-else>
            <div v-for="(it, idx) in items" :key="(it.kind==='existing' ? 'e:' + it.id : 'n:' + it.name + ':' + idx)"
                 class="pdf-card">
                <svg viewBox="0 0 24 24" width="40" height="40" fill="#E80024">
                    <path d="M6 2h7l5 5v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/>
                    <text x="7" y="17" font-size="7" fill="#fff" font-family="sans-serif">PDF</text>
                </svg>

                <a v-if="it.kind==='existing'" :href="it.url" target="_blank" rel="noopener" class="name"
                   :title="it.name">{{ it.name }}</a>
                <span v-else class="name" :title="it.name">{{ it.name }}</span>

                <div class="mask" @click.stop="remove(idx)"/>
            </div>
        </template>
    </label>
</template>

<style scoped>
.dropzone {
    min-height: 150px;
    padding: 8px;
    gap: 12px;
    flex-wrap: wrap;
    background: #f3f4f7;
    border-radius: 8px;
    border: 2px dashed transparent;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    transition: border-color .15s;
    user-select: none
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

.pdf-card {
    position: relative;
    width: 120px;
    height: 120px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border-radius: 6px;
    background: #fff;
    overflow: hidden;
    padding: 6px
}

.name {
    font-size: 11px;
    text-align: center;
    line-height: 1.2;
    word-break: break-all;
    color: #333;
    max-height: 32px;
    overflow: hidden
}

.mask {
    position: absolute;
    inset: 0;
    background: rgba(232, 0, 36, 0);
    transition: background .15s
}

.pdf-card:hover .mask {
    background: rgba(232, 0, 36, .25);
    cursor: pointer
}
</style>
