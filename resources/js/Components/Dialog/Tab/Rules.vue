<script setup>
import EditorField from "@/Components/EditorField.vue";
import {onMounted, ref, watch} from "vue";
import DropPDFs from "@/Components/DropPDFs.vue";
import {router, useForm} from "@inertiajs/vue3";
import {useLangStore} from "@/store/lang.js";

const props = defineProps({
    hackathonSlug : { type:String, required:true },
    draft         : Object,
    allTags  : { type:Array, default:() => [] }
})
const emit = defineEmits(['saved', 'cancel', 'dirty'])

const langStore = useLangStore()

const dirty = ref(false)

const rulesText  = ref(null)
const rulesFiles = ref([])

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
        fd.append('sections[0][title]',   form.sections[0].title)
        fd.append('sections[0][content]', form.sections[0].content)

        form.files.forEach((f,i)        => fd.append(`files[${i}]`, f))
        form.delete_media_ids.forEach((id,i)=> fd.append(`delete_media_ids[${i}]`, id))

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

onMounted(async () => {
    await langStore.fetchTranslations()
})
</script>

<template>
    <div class="dialog__component">
        <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.rules) }}</p>
        <EditorField v-model="rulesText" :placeholder="capitalizeFirstLetter(langStore.translations.enterDescription)"/>
    </div>
    <div class="dialog__component">
        <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.files) }}</p>
        <DropPDFs v-model:files="rulesFiles" />
    </div>
    <div class="dialog__btns">
        <button class="main__btn main__btn_white" @click="cancel">{{ capitalizeFirstLetter(langStore.translations.cansel) }}</button>
        <button class="main__btn" @click="save">{{ capitalizeFirstLetter(langStore.translations.save) }}</button>
    </div>
</template>

<style scoped>

</style>
