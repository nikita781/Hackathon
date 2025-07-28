<script setup>
import {reactive, ref, onBeforeUnmount, watch, onMounted} from 'vue'

/** ===== props / emit ================================================== */
defineProps({               // v-model:files
    modelValue: { type: Array, default: () => [] }   // массив File
})
const emit = defineEmits(['update:files'])

/** ===== локальное состояние ========================================== */
const dragging = ref(false)               // подсветка «рамки»
const inputEl  = ref(null)

/* список { file, url }  */
const items = reactive([])

/** ===== служебное ---------------------------------------------------- */
function addFiles(fileList) {
    ;[...fileList].forEach(f => {
        if (!f.type.startsWith('image/')) return         // только картинки
        const url = URL.createObjectURL(f)
        items.push({ file: f, url })
    })
    emit('update:files', items.map(i => i.file))       // наружу
}

function remove(idx) {
    URL.revokeObjectURL(items[idx].url)
    items.splice(idx, 1)
    emit('update:files', items.map(i => i.file))
}

/* убрать все objectURL при уничтожении */
onBeforeUnmount(() => items.forEach(i => URL.revokeObjectURL(i.url)))

/** ===== события ------------------------------------------------------ */
function onInput(e)  { addFiles(e.target.files) }
function onDrop(e)   { e.preventDefault(); dragging.value=false; addFiles(e.dataTransfer.files) }
function onDrag(e)   { e.preventDefault(); dragging.value = (e.type==='dragenter'||e.type==='dragover') }

/** ===== синхронизация извне (если нужно) ============================ */
watch(() => items.length, n => { if (!n && inputEl.value) inputEl.value.value='' })

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

const translations = ref({})

const fetchTranslations = async (lang = 'ru') => {
    try {
        const response = await axios.get(`http://127.0.0.1:8000/lang/${lang}.json`)
        translations.value = response.data
    } catch (error) {
        console.error('Ошибка загрузки переводов:', error)
    }
}

onMounted(async () => {
    await fetchTranslations('ru')
});
</script>

<template>
    <!-- зона добавления -->
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
            accept="image/*"
            hidden
            multiple
            @change="onInput"
        />

        <template v-if="!items.length">
            <p class="hint">{{ capitalizeFirstLetter(translations.file_upload_hint) }}</p>
        </template>

        <!-- миниатюры -->
        <template v-else>
            <div class="thumb" v-for="(it,idx) in items" :key="idx">
                <img :src="it.url" :alt="it.file.name" />
                <div class="mask" @click.stop="remove(idx)"/>
            </div>
        </template>
    </label>
</template>

<style scoped>
.dropzone{
    min-height:150px;
    background:#f3f4f7;border-radius:8px;
    display:flex;justify-content:center;align-items:center;
    border:2px dashed transparent;transition:border-color .15s;
    cursor:pointer;user-select:none;padding:8px;flex-wrap:wrap;gap:12px
}
.dropzone.dragging{border-color:#E80024}

/* сообщение */
.hint{color:#999;text-align:center;line-height:1.3;font-size:15px}

/* превью-карточка */
.thumb{position:relative;width:120px;height:120px;border-radius:6px;overflow:hidden}
.thumb img{width:100%;height:100%;object-fit:contain;background:#fff}

/* красная маска-удалялка */
.mask{
    position:absolute;inset:0;
    background:rgba(232,0,36,0);transition:background .15s;
}
.thumb:hover .mask{background:rgba(232,0,36,.25);cursor:pointer}
</style>
