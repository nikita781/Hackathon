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
        console.error('EditorJS: holder is not found')
        return
    }

    // Если данные пришли как строка, преобразуем их в объект
    const initialData = props.modelValue ? JSON.parse(props.modelValue) : {}

    editor = new EditorJS({
        holder: holder.value,
        placeholder: props.placeholder,
        tools: {
            header: Header,
            list: {
                class: List,
                inlineToolbar: true,
                config: {
                    defaultStyle: 'unordered',
                },
                toolbar: ['unorderedList', 'checklist']
            },
            vkvideo: VkVideoTool
        },
        data: initialData, // Передаем правильно данные
        onChange: async () => {
            try {
                const data = await editor.save()
                emit('update:modelValue', structuredClone(data)) // Обновляем модель
            } catch (error) {
                console.error('Error during saving editor data:', error)
            }
        }
    })
})

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
    min-height:160px;
    padding:16px 20px;
    border-radius:8px;
    background:#f3f4f7;
    overflow-y:auto;
}

:deep(.codex-editor__redactor) {
    padding-bottom: 140px !important;
}
</style>
