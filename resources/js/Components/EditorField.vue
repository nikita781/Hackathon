<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import EditorJS from '@editorjs/editorjs'
import Header from '@editorjs/header'
import List from '@editorjs/list'
import VkVideoTool from './VkVideoTool.js'
import axios from 'axios'

const props = defineProps({
    modelValue: { type: Object, default: null },
    placeholder: { type: String, default: 'Введите описание' }
})

const emit = defineEmits(['update:modelValue'])

// axios: куки/CSRF как у Inertia
axios.defaults.withCredentials = true
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
const csrf = document.head?.querySelector('meta[name="csrf-token"]')?.content
if (csrf) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf

// Нормализуем ответ от бэка к формату EditorJS
function toEditorImageResponse(d) {
    // d может быть {id,url} или {file:{url,id}} или {data:{url,id}}
    const url = d?.file?.url ?? d?.url ?? d?.data?.url
    const id  = d?.file?.id  ?? d?.id  ?? d?.data?.id
    if (!url) throw new Error('Invalid upload response: no url')
    return { success: 1, file: id ? { url, id } : { url } }
}

// 1) из ответа достаём id и url и кладём их в карту
function rememberImageIdFromResponse(d) {
    const url = d?.file?.url ?? d?.url ?? d?.data?.url
    const id  = d?.file?.id  ?? d?.id  ?? d?.data?.id
    if (url && id) imageIdByUrl.set(url, id)
}

// 2) для самого Image-плагина возвращаем МИНИМАЛЬНЫЙ формат
function toEditorImageResponseForPlugin(d) {
    const url = d?.file?.url ?? d?.url ?? d?.data?.url
    if (!url) throw new Error('Invalid upload response: no url')
    return { success: 1, file: { url } }   // без id !
}

/** Кладём id рядом с url в JSON, даже если плагин его «забыл» */
const imageIdByUrl = new Map()
function rememberImageId(file) {
    if (file?.url && file?.id) imageIdByUrl.set(file.url, file.id)
}
function injectImageIds(editorData) {
    const data = structuredClone(editorData)
    for (const b of data?.blocks ?? []) {
        if (b.type !== 'image') continue
        const url = b.data?.file?.url ?? b.data?.url
        const knownId = imageIdByUrl.get(url) ?? b.data?.file?.id ?? b.data?.id
        if (url && knownId) {
            // гарантируем структуру file:{ url, id }
            b.data.file = { url, id: knownId }
            delete b.data.url
            delete b.data.id
        }
    }
    return data
}

/* --- refs -------------------------------------------------------------- */
const holder = ref(null)
let editor = null

onMounted(async () => {
    if (!holder.value) return

    let initialData = {}
    if (props.modelValue) {
        if (typeof props.modelValue === 'string') {
            try { initialData = JSON.parse(props.modelValue) } catch {}
        } else if (typeof props.modelValue === 'object') {
            initialData = props.modelValue
        }
    }

    editor = new EditorJS({
        holder: holder.value,
        placeholder: props.placeholder,
        tools: {
            header: Header,
            list: {
                class: List,
                inlineToolbar: true,
                config: { defaultStyle: 'unordered' },
                toolbar: ['unorderedList', 'checklist']
            },
            vkvideo: VkVideoTool,
            image: {
                class: (await import('@editorjs/image')).default,
                config: {
                    captionPlaceholder: 'Подпись',
                    // ВАЖНО: без endpoints — используем только кастомный uploader на axios
                    uploader: {
                        async uploadByFile(file) {
                            const form = new FormData()
                            form.append('image', file)
                            const res = await axios.post('/editorjs/upload', form, {
                                headers: { 'Content-Type': 'multipart/form-data' }
                            })
                            rememberImageIdFromResponse(res.data)
                            console.log(toEditorImageResponseForPlugin(res.data)  )// <-- сохраняем id
                            return toEditorImageResponseForPlugin(res.data)       // <-- отдаём только url
                        },

                        async uploadByUrl(url) {
                            const res = await axios.post('/api/uploads/images/by-url', { url })
                            rememberImageIdFromResponse(res.data)
                            return toEditorImageResponseForPlugin(res.data)
                        }
                    }
                }
            }
        },
        data: initialData,
        async onChange() {
            try {
                const raw = await editor.save()
                const withIds = injectImageIds(raw)
                emit('update:modelValue', withIds)
            } catch (e) {
                console.error('Error during saving editor data:', e)
            }
        }
    })
})

onBeforeUnmount(() => {
    editor?.destroy()
})
</script>

<template>
    <div ref="holder" class="editor-holder" />
</template>

<style scoped lang="scss">
.editor-holder{
    min-height:400px;
    padding:16px 20px;
    border-radius:8px;
    background:#f3f4f7;
    overflow-y:auto;
}
:deep(.codex-editor__redactor){ padding-bottom:140px !important; }
</style>
