<script setup>
import EditorField from '@/Components/EditorField.vue'
import {nextTick, onMounted, ref, watch} from "vue";
import {router, useForm} from '@inertiajs/vue3'
import DialogCreateNomination from "@/Components/Dialog/CreateNomination.vue";
import IconsCup from "@/Components/Icons/Cup.vue";
import IconsPencil from "@/Components/Icons/Pencil.vue";
import IconsCancel from "@/Components/Icons/Cancel.vue";
import DropFiles from "@/Components/DropFiles.vue";
import logs from "../../../../../vendor/laravel/telescope/resources/js/screens/logs/index.vue";
import {useLangStore} from "@/store/lang.js";

const props = defineProps({
    hackathonSlug : { type:String, required:true },
    draft         : Object,
    allTags  : { type:Array, default:() => [] }
})
const emit = defineEmits(['saved', 'cancel', 'dirty'])

const langStore = useLangStore()

const loaded = ref(false)
const dirty = ref(false)

const dlgShown     = ref(false)
const editingIndex = ref(null)

const taskStart = ref(null)
const taskEnd   = ref(null)
const description     = ref(null)
const plan            = ref(null)
const partnerLogos    = ref([])

const nominations = ref(null)

async function fetchHackathon () {
    try {
        const { data } = await axios.get(
            route('hackathons.show', { hackathon: props.hackathonSlug }),
            { headers: { Accept: 'application/json' } }
        )
        nominations.value = data.hackathon.original.nominations
        await nextTick()
        loaded.value = true
    } catch (err) {
        console.error('fetch-hackathon-error', err?.response ?? err)
    }
}
onMounted(fetchHackathon)

function openAdd()              { editingIndex.value = null; dlgShown.value = true ; fetchHackathon() }
function openEdit(idx)          { editingIndex.value = idx;  dlgShown.value = true }
function onSaved()              { dlgShown.value = false; fetchHackathon() }

async function removeNomination(idx){
    const id = nominations.value[idx].id
    try {
        await router.delete(
            route('hackathons.nominations.destroy', { hackathon: props.hackathonSlug, nomination: id }),
            { preserveScroll:true }
        )
        nominations.value.splice(idx,1)
    } catch (err){
        console.error('nomination-delete-error', err?.response ?? err)
    }
}

watch(description,  v => { form.sections[0].content = v ?? '' })
watch(plan,         v => { form.sections[1].content = v ?? '' })

const form = useForm({
    sections : [
        { title: 'Описание',        content: '', items: [] },
        { title: 'План проведения', content: '', items: [] },
    ],
    partners: [],
    files   : [],
    delete_media_ids: []
})

watch(
    [description, plan, partnerLogos, taskStart, taskEnd],
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
    form.sections[0].content = description.value ?? ''
    form.sections[1].content = plan.value        ?? ''
    form.sections[2] = {
        title: 'Время на выполнение',
        content: JSON.stringify({ start: taskStart.value, end: taskEnd.value })
    }

    const fd = new FormData()
    fd.append('title', 'Обзор')
    form.sections.forEach((s, si) => {
        fd.append(`sections[${si}][title]`,   s.title)
        fd.append(`sections[${si}][content]`, s.content ?? '')
    })
    partnerLogos.value.forEach((f,i)=>fd.append(`partners[${i}]`,f))
    fd.append('_method', 'PATCH')

    try {
        await axios.post(
            route('hackathons.tabs.update', { hackathon: props.hackathonSlug }),
            fd,
            {
                headers: {
                    'Accept': 'application/json'
                }
            }
        )
    } catch (e) {
        console.log('tab-errors', e)
        console.log(e.response?.data)
        return
    }
    dirty.value = false
    loaded.value = true
    emit('dirty', false)
    emit('saved', { slug : props?.hackathonSlug })
}

defineExpose({ save })

const resetState = () => {
    description.value  = null
    plan.value         = null
    taskStart.value    = null
    taskEnd.value      = null
    partnerLogos.value = []
    nominations.value  = []
    dlgShown.value     = false
    editingIndex.value = null

    form.sections[0].content = ''
    form.sections[1].content = ''
    form.files               = []
    form.partners            = []
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
        <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.taskTime) }}</p>
        <div class="dialog__horizontal">
            <div class="dialog__info" style="width: 100%">
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.from) }}</p>
                <input type="datetime-local" v-model="taskStart" class="dialog__input" placeholder="Кол-во" style="width: 100%">
            </div>
            <div class="dialog__info" style="width: 100%">
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.to) }}</p>
                <input type="datetime-local" v-model="taskEnd" class="dialog__input" placeholder="Кол-во" style="width: 100%">
            </div>
        </div>
    </div>
    <div class="dialog__component">
        <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.description) }}</p>
        <EditorField v-model="description" :placeholder="capitalizeFirstLetter(langStore.translations.enterDescription)"/>
    </div>
    <div class="dialog__component">
        <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.plan) }}</p>
        <EditorField v-model="plan" :placeholder="capitalizeFirstLetter(langStore.translations.enterPlan)"/>
    </div>
    <div class="dialog__prize">
        <div class="dialog__title_header">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.prize_fund) }}</p>
            <div class="dialog__plus" @click="openAdd">
                <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.1665 7.33317H9.1665V3.33317C9.1665 3.15636 9.09627 2.98679 8.97124 2.86177C8.84622 2.73674 8.67665 2.6665 8.49984 2.6665C8.32303 2.6665 8.15346 2.73674 8.02843 2.86177C7.90341 2.98679 7.83317 3.15636 7.83317 3.33317V7.33317H3.83317C3.65636 7.33317 3.48679 7.40341 3.36177 7.52843C3.23674 7.65346 3.1665 7.82303 3.1665 7.99984C3.1665 8.17665 3.23674 8.34622 3.36177 8.47124C3.48679 8.59627 3.65636 8.6665 3.83317 8.6665H7.83317V12.6665C7.83317 12.8433 7.90341 13.0129 8.02843 13.1379C8.15346 13.2629 8.32303 13.3332 8.49984 13.3332C8.67665 13.3332 8.84622 13.2629 8.97124 13.1379C9.09627 13.0129 9.1665 12.8433 9.1665 12.6665V8.6665H13.1665C13.3433 8.6665 13.5129 8.59627 13.6379 8.47124C13.7629 8.34622 13.8332 8.17665 13.8332 7.99984C13.8332 7.82303 13.7629 7.65346 13.6379 7.52843C13.5129 7.40341 13.3433 7.33317 13.1665 7.33317Z" fill="#E80024"/>
                </svg>
                <p>{{ capitalizeFirstLetter(langStore.translations.addMore) }}</p>
            </div>
        </div>
        <div class="dialog__prize_container" v-if="nominations?.length">
            <div v-for="(n, idx) in nominations" :key="n.id" class="dialog__prize_item">
                <IconsCup />
                <div class="dialog__prize_content">
                    <div class="dialog__prize_header">
                        <p class="dialog__prize_title">{{ n.title }}</p>
                        <div class="dialog__prize_btns">
                            <IconsPencil style="padding: 5px" class="clickable" @click="openEdit(idx)" />
                            <IconsCancel class="clickable" @click="removeNomination(idx)" />
                        </div>
                    </div>
                    <p class="dialog__prize_text">{{ n.prize || 'Без указания суммы' }}</p>
                    <p class="dialog__prize_number">{{ capitalizeFirstLetter(langStore.translations.winnersCount) }}: {{ n.distribution.length }}</p>
                </div>
            </div>
        </div>
    </div>
    <DialogCreateNomination
        v-model="dlgShown"
        :hackathon-slug="props.hackathonSlug"
        :initial="editingIndex !== null ? nominations[editingIndex] : null"
        @saved="onSaved"
    />
    <div class="dialog__component">
        <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.partners) }}</p>
        <DropFiles v-model:files="partnerLogos" />
    </div>
    <div class="dialog__btns">
        <button class="main__btn main__btn_white" @click="cancel">{{ capitalizeFirstLetter(langStore.translations.cansel) }}</button>
        <button class="main__btn" @click="save">{{ capitalizeFirstLetter(langStore.translations.save) }}</button>
    </div>
</template>

<style scoped>

</style>
