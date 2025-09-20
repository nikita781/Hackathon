<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import EditorJS from '@editorjs/editorjs'
import Header from '@editorjs/header'
import List from '@editorjs/list'
import VkVideoTool from './VkVideoTool.js'

const props = defineProps({
    /** plain-object (НЕ Proxy!) с initial data или null */
    modelValue: { type: Object, default: null },
    placeholder: { type: String, default: 'Введите описание' }
})

const emit = defineEmits(['update:modelValue'])

/* --- refs -------------------------------------------------------------- */
const holder = ref(null)
let editor = null

/* --- life-cycle -------------------------------------------------------- */
onMounted(async () => {
    if (!holder.value) {
        console.error('EditorJS: holder is not found');
        return;
    }

    let initialData = {};
    if (props.modelValue) {
        if (typeof props.modelValue === 'string') {
            try { initialData = JSON.parse(props.modelValue); }
            catch (e) { console.warn('Invalid JSON in modelValue', e); }
        } else if (typeof props.modelValue === 'object') {
            initialData = props.modelValue;
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
                    endpoints: {
                        byFile: '/editorjs/upload',  // путь на бэк
                        byUrl: '/api/uploads/images/by-url', // путь для загрузки по URL
                    },
                    uploader: {
                        async uploadByFile(file) {
                            const form = new FormData();
                            form.append('image', file); // имя поля должно совпадать с `image` в запросе

                            const res = await fetch('/editorjs/upload', { method: 'POST', body: form });
                            if (!res.ok) throw new Error('Upload failed');
                            const json = await res.json();

                            // EditorJS ожидает { success: 1, file: { url, id } }
                            return {
                                success: 1,
                                file: {
                                    url: json.url, // URL, полученный от сервера
                                    id: json.id,   // ID, полученный от сервера
                                }
                            };
                        },

                        async uploadByUrl(url) {
                            const res = await fetch('/api/uploads/images/by-url', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ url })
                            });

                            if (!res.ok) throw new Error('Upload failed');
                            const json = await res.json();

                            return {
                                success: 1,
                                file: {
                                    url: json.url, // URL изображения
                                    id: json.id,   // ID изображения
                                }
                            };
                        }
                    }
                }
            }
        },
        data: initialData,
        async onChange() {
            try {
                const data = await editor.save();
                emit('update:modelValue', structuredClone(data));
            } catch (e) {
                console.error('Error during saving editor data:', e);
            }
        }
    });
});

onBeforeUnmount(() => {
    editor?.destroy()
})
</script>

<template>
    <!-- key нужен только если вы хотите «перезагрузить» редактор целиком -->
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

:deep(.codex-editor__redactor) {
    padding-bottom: 140px !important;
}
</style>
