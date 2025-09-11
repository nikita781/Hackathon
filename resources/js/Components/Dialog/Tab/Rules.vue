<script setup>
import EditorField from "@/Components/EditorField.vue";
import {nextTick, onMounted, ref, watch} from "vue";
import DropPDFs from "@/Components/DropPDFs.vue";
import {router, useForm} from "@inertiajs/vue3";
import {useLangStore} from "@/store/lang.js";

const props = defineProps({
    hackathonSlug : { type:String, required:true },
    draft         : Object,
    allTags  : { type:Array, default:() => [] },
    isEdit   : { type:Boolean, default:false }
})
const emit = defineEmits(['saved', 'cancel', 'dirty'])

const rulesText = ref(null)
const rulesFiles = ref([])
const deletedMediaIds  = ref([])

const loaded = ref(false)

async function fetchResources() {
    try {
        const { data } = await axios.get(
            route('hackathons.show', { hackathon: props.hackathonSlug }),
            { headers: { Accept: 'application/json' } }
        );
        // resourcesFiles.value = data.files || [];

        // form.sections[0].content = description.value;
        // form.files = resourcesFiles.value;

        if (props.isEdit) {
            const hackathon = data.tabs.original[2];

            rulesText.value = hackathon.sections.find(s => s.title === 'Правила')?.content || '';
            rulesFiles.value = hackathon.files
        }
        await nextTick()
        loaded.value = true
    } catch (err) {
        console.error('fetch-resources-error', err?.response ?? err);
    }
}

watch(rulesFiles.value, arr => {
    form.files = arr; // Обновляем файлы в форме
}, { deep: true });

onMounted(() => {
    if (props.isEdit) {
        fetchResources();
    }
});

const langStore = useLangStore()

const dirty = ref(false)

const form = useForm({
    sections : [
        { title:'Правила', content:'', items:[] },
    ],
    files : [],
    delete_media_ids: [],
})
watch(rulesFiles, files => { form.files = files })

watch(
    [rulesText, rulesFiles],
    () => {
        if (!loaded.value) return
        if (!dirty.value) {
            dirty.value = true
            emit('dirty', true)
        }
    },
    { deep:true }
)

async function save () {
    try {
        form.sections[0].content = rulesText.value ?? ''

        const fd = new FormData()
        fd.append('title', 'Правила')

        form.sections.forEach((s, si) => {
            fd.append(`sections[${si}][title]`, s.title)
            const content =
                s.content == null
                    ? ''
                    : (typeof s.content === 'object'
                        ? JSON.stringify(s.content)
                        : String(s.content))
            fd.append(`sections[${si}][content]`, content)
        })

        rulesFiles.value.forEach(f => {
            if (f instanceof File) fd.append('files[]', f)
        })
        deletedMediaIds.value.forEach(id => fd.append('delete_media_ids[]', id))

        fd.append('_method', 'PATCH')

        await axios.post(
            route('hackathons.tabs.update', { hackathon: props.hackathonSlug }),
            fd,
            { headers:{ 'Content-Type':'multipart/form-data' } }
        )
        dirty.value = false
        emit('dirty', false)
        emit('saved', { slug : props?.hackathonSlug })
    } catch (err) {
        console.log('rules-errors', err?.response ?? err)
    }
}

defineExpose({ save })

const resetState = () => {
    rulesText.value   = null
    rulesFiles.value  = []
    form.sections[0].content = ''
    form.files               = []
    form.delete_media_ids    = []
}

function cancel () {
    resetState()
    emit('cancel')
}

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

const handleFilesUpdate = (mixed) => {
    rulesFiles.value = mixed
    // если где-то используете form.files — оставьте только новые файлы:
    form.files = mixed.filter(x => x instanceof File)
}
const handleDeletingIds = (ids) => { deletedMediaIds.value = ids }

watch(
    [rulesFiles],
    () => {
        if (!loaded.value) return;

        if (!dirty.value) {
            dirty.value = true;
            emit('dirty', true);
        }
    },
    { deep: true }
);

onMounted(async () => {
    await langStore.fetchTranslations()
})
</script>

<template>
    <div class="dialog__component" v-if="!isEdit || loaded">
        <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.rules) }}</p>
        <EditorField v-model="rulesText" :placeholder="capitalizeFirstLetter(langStore.translations.enterDescription)"/>
    </div>
    <div class="dialog__component">
        <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.files) }}</p>
        <DropPDFs
            :files="rulesFiles"
            @update:files="handleFilesUpdate"
            @deleting-ids="handleDeletingIds"
        />
    </div>
    <div class="dialog__btns">
        <button class="main__btn main__btn_white" @click="cancel">{{ capitalizeFirstLetter(langStore.translations.cansel) }}</button>
        <button class="main__btn" @click="save">{{ capitalizeFirstLetter(langStore.translations.save) }}</button>
    </div>
</template>

<style scoped>

</style>
