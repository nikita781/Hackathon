<!--  DropPDFs.vue  -->
<script setup>
import { reactive, ref, onBeforeUnmount, watch } from 'vue'

/* ===== v-model ======================================================== */
defineProps({
    /* v-model:files → массив File */
    modelValue: { type: Array, default: () => [] }
})
const emit = defineEmits(['update:files'])

/* ===== локальное состояние =========================================== */
const dragging = ref(false)
const inputEl  = ref(null)

/* [{ file, url }] — url нужен, чтобы потом корректно revoke */
const items = reactive([])

/* ===== utils ========================================================== */
function addFiles(fileList) {
    ;[...fileList].forEach(f => {
        if (f.type !== 'application/pdf') return           // <-- только PDF
        const url = URL.createObjectURL(f)
        items.push({ file: f, url })
    })
    emit('update:files', items.map(i => i.file))
}

function remove(idx) {
    URL.revokeObjectURL(items[idx].url)
    items.splice(idx, 1)
    emit('update:files', items.map(i => i.file))
}

/* чистим objectURL при размонтировании */
onBeforeUnmount(() => items.forEach(i => URL.revokeObjectURL(i.url)))

/* ===== события DnD / input =========================================== */
function onInput(e) { addFiles(e.target.files) }

function onDrop(e)   { e.preventDefault(); dragging.value = false; addFiles(e.dataTransfer.files) }
function onDrag(e)   {
    e.preventDefault()
    dragging.value = (e.type === 'dragenter' || e.type === 'dragover')
}

/* сбрасываем <input> когда удалили всё */
watch(() => items.length, n => { if (!n && inputEl.value) inputEl.value.value = '' })
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
        <input
            ref="inputEl"
            type="file"
            accept="application/pdf"
            hidden
            multiple
            @change="onInput"
        />

        <!-- пустая зона -->
        <template v-if="!items.length">
            <p class="hint">
                Перетащите или выберите файлы<br>
                (PDF, 5 MB&nbsp;максимальный размер файла)
            </p>
        </template>

        <!-- список PDF -->
        <template v-else>
            <div v-for="(it, idx) in items" :key="idx" class="pdf-card">
                <svg viewBox="0 0 24 24" width="40" height="40" fill="#E80024">
                    <path d="M6 2h7l5 5v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/>
                    <text x="7" y="17" font-size="7" fill="#fff" font-family="sans-serif">PDF</text>
                </svg>
                <span class="name" :title="it.file.name">{{ it.file.name }}</span>

                <!-- маска-удалялка -->
                <div class="mask" @click.stop="remove(idx)"/>
            </div>
        </template>
    </label>
</template>

<style scoped>
.dropzone{
    min-height:150px;padding:8px;gap:12px;flex-wrap:wrap;
    background:#f3f4f7;border-radius:8px;border:2px dashed transparent;
    display:flex;justify-content:center;align-items:center;cursor:pointer;
    transition:border-color .15s;user-select:none
}
.dropzone.dragging{border-color:#E80024}

.hint{color:#999;text-align:center;line-height:1.3;font-size:15px}

/* карточка pdf */
.pdf-card{
    position:relative;width:120px;height:120px;
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    gap:6px;border-radius:6px;background:#fff;overflow:hidden;padding:6px
}
.name{
    font-size:11px;text-align:center;line-height:1.2;word-break:break-all;
    color:#333;max-height:32px;overflow:hidden
}

/* красная маска при ховере */
.mask{
    position:absolute;inset:0;background:rgba(232,0,36,0);
    transition:background .15s
}
.pdf-card:hover .mask{background:rgba(232,0,36,.25);cursor:pointer}
</style>
