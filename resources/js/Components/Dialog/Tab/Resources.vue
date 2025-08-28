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

const description = ref(null)
const resourcesFiles = ref([])

const form = useForm({
    title   : 'Ресурсы',
    sections: [
        { title:'Ресурсы', content:'' }
    ],
    files            : [],
    delete_media_ids : []
})

watch(description,     v  => { form.sections[0].content = v ?? '' })
watch(resourcesFiles,  arr => { form.files = arr }, { deep:true })

watch(
    [description, resourcesFiles],
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
        const fd = new FormData()

        fd.append('title', form.title)

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

        /* файлы */
        form.files.forEach((f,i)        => fd.append(`files[${i}]`, f))
        form.delete_media_ids.forEach((id,i)=> fd.append(`delete_media_ids[${i}]`, id))

        fd.append('_method', 'PATCH')

        /* запрос */
        await axios.post(
            route('hackathons.tabs.update', { hackathon: props.hackathonSlug }),
            fd,
            { headers:{ 'Content-Type':'multipart/form-data' } }
        )
        dirty.value = false
        emit('dirty', false)
        emit('saved', { slug : props?.hackathonSlug })
    } catch (err) {
        console.error('tab-errors', err?.response ?? err)
    }
}

defineExpose({ save })

const resetState = () => {
    description.value   = null
    resourcesFiles.value = []
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
});
</script>

<template>
    <div class="dialog__component">
        <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.resources) }}</p>
        <EditorField v-model="description" :placeholder="capitalizeFirstLetter(langStore.translations.enterDescription)"/>
    </div>
    <div class="dialog__component">
        <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.files) }}</p>
        <DropPDFs v-model:files="resourcesFiles" />
    </div>
    <div class="dialog__btns">
        <button class="main__btn main__btn_white" @click="cancel">{{ langStore.translations.cansel }}</button>
        <button class="main__btn" @click="save">{{ langStore.translations.save }}</button>
    </div>
</template>

<style scoped>

</style>
