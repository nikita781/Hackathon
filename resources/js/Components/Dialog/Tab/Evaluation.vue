<script setup>
import {nextTick, onMounted, reactive, ref, watch} from 'vue'
import CreateEvaluation from '@/Components/Dialog/CreateEvaluation.vue'
import IconsCancel from "@/Components/Icons/Cancel.vue";
import IconsPencil from "@/Components/Icons/Pencil.vue";
import {router} from "@inertiajs/vue3";
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

const evaluationStart = ref(null)
const evaluationEnd   = ref(null)
const groups = ref(null)
const dlgShown   = ref(false)
const editingIdx = ref(null)
const deleted = []

function openAdd ()           { editingIdx.value=null; dlgShown.value=true ; fetchData()}
function openEdit(idx)        { editingIdx.value=idx; dlgShown.value=true; fetchData()}
async function removeGroup(idx){
    const { id } = groups.value[idx]
    try {
        await router.delete(
            route('hackathons.criteria.destroy', { hackathon: props.hackathonSlug, criterionGroup: id }),
            { preserveScroll:true }
        )
        groups.value.splice(idx, 1)
    } catch (err){
        console.error('nomination-delete-error', err?.response ?? err)
    }
    await fetchData()
}
function onSaved()              { dlgShown.value = false; fetchData() }

const fetchData = async () => {
    if (!props.hackathonSlug) return
    try{
        const { data } = await axios.get(
            route('hackathons.show', { hackathon:props.hackathonSlug }),
            { headers:{Accept:'application/json'} }
        )
        groups.value = data.hackathon.original.criteria_groups ?? []
        await nextTick()
        loaded.value = true
    }catch(e){ console.error('evaluation-fetch', e?.response ?? e) }
}
onMounted(fetchData)

watch(
    [evaluationStart, evaluationEnd],
    () => {
        if (!loaded.value) return

        if (!dirty.value) {
            dirty.value = true
            emit('dirty', true)
        }
    },
    { deep:true }
)

async function save() {
    /* 1. сохраняем даты в таб */
    const fd = new FormData()
    fd.append('title','Оценка')
    fd.append('sections[0][title]','Даты проверки')
    fd.append('sections[0][content]', JSON.stringify({
        start:evaluationStart.value, end:evaluationEnd.value
    }))
    fd.append('_method','PATCH')

    await axios.post(
        route('hackathons.tabs.update', { hackathon: props.hackathonSlug }),
        fd, { headers:{'Content-Type':'multipart/form-data'} }
    )
    dirty.value = false
    loaded.value = true
    emit('dirty', false)
    emit('saved',{slug:props.hackathonSlug})
}

const reset = () => {
    evaluationStart.value = null
    evaluationEnd.value   = null
    groups.splice(0)
    deleted.splice(0)
    dlgShown.value        = false
    editingIdx.value      = null
}
function cancel () { reset(); emit('cancel') }

defineExpose({ save })

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
        <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.reviewTime) }}</p>
        <div class="dialog__horizontal">
            <div class="dialog__info" style="width: 100%">
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.from) }}</p>
                <input type="datetime-local" v-model="evaluationStart" class="dialog__input" placeholder="Кол-во" style="width: 100%">
            </div>
            <div class="dialog__info" style="width: 100%">
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.to) }}</p>
                <input type="datetime-local" v-model="evaluationEnd" class="dialog__input" placeholder="Кол-во" style="width: 100%">
            </div>
        </div>
    </div>
    <div class="dialog__prize">
        <div class="dialog__title_header">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.evaluationCriteria) }}</p>
            <div class="dialog__plus" @click="openAdd">
                <svg width="17" height="16" viewBox="0 0 17 16"><path d="M13.17 7.33H9.17V3.33a.67.67 0 0 0-1.34 0v4H3.83a.67.67 0 0 0 0 1.34h4v4a.67.67 0 0 0 1.34 0v-4h4a.67.67 0 0 0 0-1.34Z" fill="#E80024"/></svg>
                <p>{{ capitalizeFirstLetter(langStore.translations.addMore) }}</p>
            </div>
        </div>
        <div class="dialog__prize" v-for="(grp,idx) in groups" :key="idx">
            <div class="dialog__eva_container">
                <p class="dialog__eva">{{ grp.title }}</p>
                <div class="dialog__prize_btns">
                    <IconsPencil style="padding: 5px" class="clickable" @click="openEdit(idx)" />
                    <IconsCancel class="clickable" @click="removeGroup(idx)" />
                </div>
            </div>

            <div class="dialog__eva_item" v-for="(it,i) in grp.criteria" :key="i">
                <p class="dialog__eva_title">{{ it.title }}</p>
                <div class="dialog__eva_number">
                    <p v-for="n in 10" :key="n">{{ n }}</p>
                </div>
            </div>
        </div>
    </div>
<!--    <pre>{{groups}}</pre>-->
    <CreateEvaluation
        v-model="dlgShown"
        :initial="editingIdx!==null ? groups[editingIdx] : null"
        :hackathonSlug="props.hackathonSlug"
        @saved="onSaved"
    />
    <div class="dialog__btns">
        <button class="main__btn main__btn_white" @click="cancel">{{ capitalizeFirstLetter(langStore.translations.cansel) }}</button>
        <button class="main__btn" @click="save">{{ capitalizeFirstLetter(langStore.translations.save) }}</button>
    </div>
</template>

<style scoped>

</style>
