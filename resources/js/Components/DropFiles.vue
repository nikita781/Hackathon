<script setup>
import {reactive, ref, onBeforeUnmount, watch, onMounted} from 'vue'
import {useLangStore} from "@/store/lang.js";

/** ===== props / emit ================================================== */
const props = defineProps({               // v-model:files
    files: { type: Array, default: () => [] }   // массив File
})
const emit = defineEmits(['update:files', 'deleting-ids'])

/** ===== локальное состояние ========================================== */
const dragging = ref(false)               // подсветка «рамки»
const inputEl  = ref(null)
const langStore = useLangStore()

/* список { file, url }  */
const items = ref([])
const deletedFiles = ref([]);

/** ===== служебное ---------------------------------------------------- */
function addFiles(fileList) {
    ;[...fileList].forEach(f => {
        if (!f.type.startsWith('image/')) return         // только картинки
        const url = URL.createObjectURL(f)
        items.value.push({ file: f, url })
    })
    emit('update:files', items.value.map(i => i.file))       // наружу
}

function remove(idx) {
    const item = items.value[idx];
    item.isDeleted = true; // флаг, что изображение удалено
    deletedFiles.value.push(item.file.id); // добавляем id удалённого файла
    items.value.splice(idx, 1); // удаляем файл из отображения

    emit('update:files', items.value.map(i => i.file)); // отправляем родителю обновленный список
    emit('deleting-ids', deletedFiles.value); // отправляем список удалённых ID
}

/* убрать все objectURL при уничтожении */
onBeforeUnmount(() => items.value.forEach(i => URL.revokeObjectURL(i.url)))

/** ===== события ------------------------------------------------------ */
function onInput(e) {
    const fileList = e.target.files;
    ;[...fileList].forEach(f => {
        if (!f.type.startsWith('image/')) return; // только изображения
        const url = URL.createObjectURL(f); // создаём URL для локального отображения
        items.value.push({ file: f, url, isDeleted: false });
    });

    emit('update:files', items.value.map(i => i.file)); // отправляем родителю обновленный список
}
function onDrop(e) {
    e.preventDefault();
    const fileList = e.dataTransfer.files;
    ;[...fileList].forEach(f => {
        if (!f.type.startsWith('image/')) return;
        const url = URL.createObjectURL(f);
        items.value.push({ file: f, url, isDeleted: false });
    });

    emit('update:files', items.value.map(i => i.file));
}
function onDrag(e)   { e.preventDefault(); dragging.value = (e.type==='dragenter'||e.type==='dragover') }

/** ===== синхронизация извне (если нужно) ============================ */
watch(() => items.value.length, n => { if (!n && inputEl.value) inputEl.value.value='' })

watch(() => props.files, (newFiles) => {
    items.value = newFiles.map((file) => {
        return { file, isDeleted: false }; // флаг для отслеживания удалённых
    });
}, { immediate: true });

/** ===== синхронизация с пропсом files =============================== */
watch(() => props.files, (newFiles) => {
    items.value.splice(0, items.value.length);  // Очистить текущие элементы
    // Проходим по новым файлам и создаем их
    newFiles.forEach(file => {
        if (typeof file === 'object' && file.url) {
            // Если это объект с URL, то просто добавляем в items
            items.value.push({ file, url: file.url });
        } else {
            // Если это файл, то создаем URL
            const url = URL.createObjectURL(file);
            items.value.push({ file, url });
        }
    });
}, { immediate: true });

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

onMounted(async () => {
    await langStore.fetchTranslations()
});

onBeforeUnmount(() => {
    items.value.forEach(i => URL.revokeObjectURL(i.url)); // очищаем URL при удалении компонента
});
</script>

<template>
<!--    <pre>{{files}}</pre>-->
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
            <p class="hint">{{ capitalizeFirstLetter(langStore.translations.file_upload_hint) }}</p>
        </template>

        <!-- миниатюры -->
        <template v-else>
            <div class="thumb" v-for="(it,idx) in items" :key="idx">
                <img :src="it.url" alt="Photo" />
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
