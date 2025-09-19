<script setup>
import {computed, nextTick, onMounted, ref, watch} from 'vue'
import CreateEvaluation from '@/Components/Dialog/CreateEvaluation.vue'
import IconsCancel from "@/Components/Icons/Cancel.vue";
import IconsPencil from "@/Components/Icons/Pencil.vue";
import {router} from "@inertiajs/vue3";
import {useLangStore} from "@/store/lang.js";

const props = defineProps({
    hackathonSlug : { type:String, required:true },
    draft         : Object,
    allTags       : { type:Array, default:() => [] },
    admin      : { type:Boolean, default:() => false },
})
const emit = defineEmits(['saved', 'cancel', 'dirty'])

const isAdmin = computed(() => !!props.admin)

const langStore = useLangStore()

const loaded = ref(false)
const dirty  = ref(false)

const evaluationStart = ref('')
const evaluationEnd   = ref('')
const groups   = ref([])
const dlgShown = ref(false)
const editingIdx = ref(null)
const deleted = []
const editingId = ref(null)

const errors = ref({ evaluation_start: '', evaluation_end: '' })

function openAdd () {
    editingId.value = null
    dlgShown.value = true
    fetchData()
}
function openEdit(id) {
    editingId.value = Number(id)
    dlgShown.value = true
    fetchData()
}
async function removeGroup(id) {
    const gid = Number(id)
    const idx = groups.value.findIndex(g => Number(g.id) === gid)
    if (idx === -1) return

    try {
        await router.delete(
            route('hackathons.criteria.destroy', {
                hackathon: props.hackathonSlug,
                criterionGroup: gid,
            }),
            { preserveScroll: true }
        )
        groups.value.splice(idx, 1)
    } catch (err) {
        console.error('nomination-delete-error', err?.response ?? err)
    }

    await fetchData()
}

function onSaved () {
    dlgShown.value = false
    fetchData()
}

/* ===== ЗАГРУЗКА: подхватываем даты из таба "Оценка" ===== */
const fetchData = async () => {
    if (!props.hackathonSlug) return
    try{
        const { data } = await axios.get(
            route('hackathons.show', { hackathon:props.hackathonSlug }),
            { headers:{Accept:'application/json'} }
        )

        // группы критериев (как и было)
        groups.value = data?.hackathon?.original?.criteria_groups ?? []

        const h = data?.hackathon?.original
        evaluationStart.value = h?.evaluation_start ? h.evaluation_start.slice(0,16) : ''
        evaluationEnd.value   = h?.evaluation_end   ? h.evaluation_end.slice(0,16)   : ''

        await nextTick()
        loaded.value = true
    }catch(e){
        console.error('evaluation-fetch', e?.response ?? e)
        loaded.value = true
    }
}
onMounted(fetchData)

/* dirty только после загрузки */
watch([evaluationStart, evaluationEnd], () => {
    if (!loaded.value) return
    if (!dirty.value) {
        dirty.value = true
        emit('dirty', true)
    }
})

async function save() {
    // чистим старые ошибки
    errors.value.evaluation_start = ''
    errors.value.evaluation_end   = ''

    const fd = new FormData()
    fd.append('evaluation_start', evaluationStart.value || '')
    fd.append('evaluation_end',   evaluationEnd.value   || '')
    fd.append('_method','PATCH')

    try {
        await axios.post(
            route('hackathons.update', { hackathon: props.hackathonSlug }),
            fd,
            { headers:{ 'Content-Type':'multipart/form-data', Accept:'application/json' } }
        )
        dirty.value = false
        emit('dirty', false)
        emit('saved', { slug: props.hackathonSlug })
    } catch (e) {
        if (e?.response?.status === 422) {
            const err = e.response.data?.errors || {}
            errors.value.evaluation_start = Array.isArray(err.evaluation_start) ? err.evaluation_start[0] : (err.evaluation_start || '')
            errors.value.evaluation_end   = Array.isArray(err.evaluation_end)   ? err.evaluation_end[0]   : (err.evaluation_end   || '')
        } else {
            console.error('evaluation-save', e?.response ?? e)
        }
        // если валидация не прошла — не продолжаем
        return
    }
}

const reset = () => {
    evaluationStart.value = ''
    evaluationEnd.value   = ''
    groups.value = []
    deleted.splice(0)
    dlgShown.value   = false
    editingIdx.value = null
}
function cancel () { reset(); emit('cancel') }

defineExpose({ save })

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

onMounted(async () => { await langStore.fetchTranslations() })
</script>

<template>
    <div class="dialog__component">
        <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.reviewTime) }}</p>
        <div class="dialog__horizontal">
            <div class="dialog__info" style="width: 100%">
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.from) }}</p>
                <input
                    type="datetime-local"
                    v-model="evaluationStart"
                    class="dialog__input"
                    :class="{ error: !!errors.evaluation_start }"
                    @input="errors.evaluation_start=''"
                    style="width: 100%"
                >
            </div>

            <div class="dialog__info" style="width: 100%">
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.to) }}</p>
                <input
                    type="datetime-local"
                    v-model="evaluationEnd"
                    class="dialog__input"
                    :class="{ error: !!errors.evaluation_end }"
                    @input="errors.evaluation_end=''"
                    style="width: 100%"
                >
            </div>
        </div>
        <small v-if="errors.evaluation_start" class="error__text">{{ errors.evaluation_start }}</small>
        <small v-if="errors.evaluation_end" class="error__text">{{ errors.evaluation_end }}</small>
    </div>

    <div class="dialog__prize">
        <div class="dialog__title_header">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.evaluationCriteria) }}</p>
            <div class="dialog__plus" @click="openAdd">
                <svg width="17" height="16" viewBox="0 0 17 16"><path d="M13.17 7.33H9.17V3.33a.67.67 0 0 0-1.34 0v4H3.83a.67.67 0 0 0 0 1.34h4v4a.67.67 0 0 0 1.34 0v-4h4a.67.67 0 0 0 0-1.34Z" fill="#E80024"/></svg>
                <p>{{ capitalizeFirstLetter(langStore.translations.addMore) }}</p>
            </div>
        </div>

<!--        <pre>{{groups}}</pre>-->

        <div class="dialog__prize" v-for="grp in groups" :key="grp.id">
            <div class="dialog__eva_container">
                <p class="dialog__eva">{{ grp.title }}</p>
                <div class="dialog__prize_btns">
                    <IconsPencil style="padding: 5px" class="clickable" @click="openEdit(grp.id)" />
                    <IconsCancel class="clickable" @click="removeGroup(grp.id)" />
                </div>
            </div>

            <div class="dialog__eva_item" v-for="it in (grp.criteria || [])" :key="it.id || it.title">
                <p class="dialog__eva_title">{{ it.title }}</p>
                <div class="dialog__eva_number">
                    <p v-for="n in 10" :key="n">{{ n }}</p>
                </div>
            </div>
        </div>
    </div>

    <CreateEvaluation
        v-model="dlgShown"
        :initial="editingId!==null ? groups.find(g => Number(g.id) === Number(editingId)) : null"
        :hackathonSlug="props.hackathonSlug"
        @saved="onSaved"
    />

    <div class="dialog__btns" v-if="!isAdmin">
        <button class="main__btn main__btn_white" @click="cancel">{{ capitalizeFirstLetter(langStore.translations.cansel) }}</button>
        <button class="main__btn" @click="save">{{ capitalizeFirstLetter(langStore.translations.save) }}</button>
    </div>
</template>

<style scoped>
/* твои стили */
</style>
