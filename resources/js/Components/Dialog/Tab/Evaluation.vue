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
    readonly      : { type:Boolean, default:() => false },
})
const emit = defineEmits(['saved', 'cancel', 'dirty', 'saving'])

const isAdmin = computed(() => !!props.admin)
const isReadOnly = computed(() => !!props.readonly)

const langStore = useLangStore()

const loaded = ref(false)
const dirty  = ref(false)

const groups   = ref([])
const dlgShown = ref(false)
const editingIdx = ref(null)
const deleted = []
const editingId = ref(null)

function openAdd () {
    editingId.value = null
    dlgShown.value = true
    fetchData({ refreshDates: false })
}
function openEdit(id) {
    editingId.value = Number(id)
    dlgShown.value = true
    fetchData({ refreshDates: false })
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

    await fetchData({ refreshDates: false })
}

function onSaved () {
    dlgShown.value = false
    fetchData({ refreshDates: false })
}

/* ===== ЗАГРУЗКА: подхватываем даты из таба "Оценка" ===== */
const fetchData = async ({ refreshDates = true } = {}) => {
    if (!props.hackathonSlug) return
    try{
        const { data } = await axios.get(
            route('hackathons.show', { hackathon:props.hackathonSlug }),
            { headers:{Accept:'application/json'} }
        )

        // группы критериев (как и было)
        groups.value = data?.hackathon?.original?.criteria_groups ?? []

        await nextTick()
        loaded.value = true
    }catch(e){
        console.error('evaluation-fetch', e?.response ?? e)
        loaded.value = true
    }
}
onMounted(() => fetchData({ refreshDates: true }))

async function save() {
    dirty.value = false
    emit('dirty', false)
    emit('saved', { slug: props.hackathonSlug })
}

const reset = () => {
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
    <div class="dialog__prize">
        <div class="dialog__title_header">
            <div class="dialog__title_container">
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.evaluationCriteria) }}</p>
                <div class="help-tt" aria-label="help">
                    <svg class="help-tt__icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="#000" />
                        <path d="M12 17v-5" stroke="#000" stroke-linecap="round"/>
                        <circle cx="12" cy="8" r="1" fill="#000"/>
                    </svg>
                    <div class="tooltipSquare"></div>
                    <div class="tooltip">
                        <p>Это блок для добавления критериев оценки проектов мероприятия</p>
                    </div>
                </div>
            </div>
            <div class="dialog__plus" @click="openAdd" v-if="!isReadOnly">
                <svg width="17" height="16" viewBox="0 0 17 16"><path d="M13.17 7.33H9.17V3.33a.67.67 0 0 0-1.34 0v4H3.83a.67.67 0 0 0 0 1.34h4v4a.67.67 0 0 0 1.34 0v-4h4a.67.67 0 0 0 0-1.34Z" fill="#E80024"/></svg>
                <p>{{ capitalizeFirstLetter(langStore.translations.addMore) }}</p>
            </div>
        </div>

<!--        <pre>{{groups}}</pre>-->

        <div class="dialog__prize" v-for="grp in groups" :key="grp.id">
            <div class="dialog__eva_container">
                <p class="dialog__eva">{{ grp.title }}</p>
                <div class="dialog__prize_btns" v-if="!isReadOnly">
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

    <div class="dialog__btns" v-if="!isAdmin && !isReadOnly">
        <button class="main__btn main__btn_white" @click="cancel">{{ capitalizeFirstLetter(langStore.translations.cansel) }}</button>
        <button class="main__btn" @click="save">{{ capitalizeFirstLetter(langStore.translations.save) }}</button>
    </div>
</template>

<style scoped>
/* твои стили */
</style>
