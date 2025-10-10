<script setup>
import EditorField from '@/Components/EditorField.vue'
import {computed, nextTick, onMounted, ref, watch} from "vue";
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
    allTags  : { type:Array, default:() => [] },
    isEdit   : { type:Boolean, default:false },
    admin      : { type:Boolean, default:() => false },
})
const emit = defineEmits(['saved', 'cancel', 'dirty'])

const isAdmin = computed(() => !!props.admin)

const langStore = useLangStore()

const form = useForm({
    sections: [
        { title: 'Описание', content: '', items: [] },
        { title: 'План проведения', content: '', items: [] },
        { title: 'Время на выполнение', content: '', items: [] },
    ],
    partners: [],
    files: [],
    delete_media_ids: []
});

const loaded = ref(false)
const dirty = ref(false)

const dlgShown     = ref(false)
const editingIndex = ref(null)

const description     = ref(null)
const plan            = ref(null)
const partnerLogos    = ref([])
const newPartnerFiles = ref([])   // File[]
const deletedMediaIds = ref([])   // number[]

const nominations = ref(null)

async function fetchHackathon ({ refreshDates = true } = {}) {
    try {
        const { data } = await axios.get(
            route('hackathons.show', { hackathon: props.hackathonSlug }),
            { headers: { Accept: 'application/json' } }
        )
        if (props.isEdit) {
            const hackathon = data.tabs.original[0];

            const overviewTab = data.tabs.original[0];      // сам таб «Обзор»
            const h = data.hackathon.original;              // <- сам хакатон

            form.sections[0].content = overviewTab.sections.find(s => s.title === 'Описание')?.content || '';
            form.sections[1].content = overviewTab.sections.find(s => s.title === 'План проведения')?.content || '';

            description.value = form.sections[0].content
            plan.value = form.sections[1].content

            await getPartner(hackathon.id);
        }

        nominations.value = data.hackathon.original.nominations
        await nextTick()
        loaded.value = true
    } catch (err) {
        console.error('fetch-hackathon-error', err?.response ?? err)
    }
}
async function getPartner(tabId) {
    try {
        const response = await axios.get(
            route('hackathons.tabs.partner-images', { hackathon: props.hackathonSlug, tab: tabId }),
            { headers: { Accept: 'application/json' } }
        );
        partnerLogos.value = response.data.partners || []
        newPartnerFiles.value = []
        deletedMediaIds.value = []
    } catch (e) {
        console.error('hackathon-load', e?.response ?? e);
    }
}
onMounted(() => {
    if (props.isEdit) {
        fetchHackathon({ refreshDates: true });
    }
})

function openAdd()              { editingIndex.value = null; dlgShown.value = true ; fetchHackathon({ refreshDates: false }) }
function openEdit(idx)          { editingIndex.value = idx;  dlgShown.value = true }
function onSaved()              { dlgShown.value = false; fetchHackathon({ refreshDates: false }) }

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

const handleFilesUpdate = (newFiles) => {
    newPartnerFiles.value = Array.isArray(newFiles) ? newFiles : []
}

const handleDeletingIds = (ids) => {
    deletedMediaIds.value = Array.isArray(ids)
        ? ids.filter(n => Number.isFinite(+n)).map(n => +n)
        : []
}

function clearFieldError(field) {
    form.clearErrors(field)
}

watch(
    [description, plan, partnerLogos],
    () => {
        if (!loaded.value) return
        if (!dirty.value) {
            dirty.value = true
            emit('dirty', true)
        }
    },
    { deep:true }
)

function pad2(n) { return String(n).padStart(2, '0') }

/** "2025-10-01T10:00" (локаль) → "2025-10-01T08:00:00.000Z" (UTC) */
function localDateTimeToUtcISO(localStr) {
    if (!localStr) return ''
    const [datePart, timePart] = localStr.split('T')
    if (!datePart || !timePart) return ''
    const [y, m, d] = datePart.split('-').map(Number)
    const [hh, mm]  = timePart.split(':').map(Number)
    const local = new Date(y, (m ?? 1) - 1, d ?? 1, hh ?? 0, mm ?? 0, 0)
    return isNaN(local.getTime()) ? '' : local.toISOString()
}

/** Приводим всё, что похоже на UTC без суффикса, к ISO с Z */
function normalizeToIsoZ(s) {
    const t = s.trim()
    // "YYYY-MM-DD HH:mm[:ss[.ms]]" или "YYYY-MM-DDTHH:mm[:ss[.ms]]"
    const re = /^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2}(\.\d+)?)?$/
    if (re.test(t) && !/[zZ]|[+\-]\d{2}:\d{2}$/.test(t)) {
        return t.replace(' ', 'T') + 'Z'
    }
    return t
}

/** "2025-10-01T08:00:00Z" (UTC) → "2025-10-01T10:00" (локаль для <input type="datetime-local">) */
function utcToLocalInputValue(utcStr) {
    if (!utcStr) return ''
    const d = new Date(normalizeToIsoZ(utcStr))
    if (isNaN(d.getTime())) return ''
    const y = d.getFullYear()
    const m = pad2(d.getMonth() + 1)
    const day = pad2(d.getDate())
    const hh = pad2(d.getHours())
    const mm = pad2(d.getMinutes())
    return `${y}-${m}-${day}T${hh}:${mm}`
}

async function save () {
    form.sections[0].content = description.value ?? ''
    form.sections[1].content = plan.value        ?? ''

    const fd = new FormData();
    fd.append('title', 'Обзор');
    form.sections.forEach((s, si) => {
        if (s.title === 'Время на выполнение') return;
        fd.append(`sections[${si}][title]`, s.title);
        const content = s.content == null ? '' : (typeof s.content === 'object' ? JSON.stringify(s.content) : String(s.content));
        fd.append(`sections[${si}][content]`, content);
    });
    newPartnerFiles.value.forEach(f => {
        if (f instanceof File) fd.append('partners[]', f)
    })
    deletedMediaIds.value.forEach(id => {
        fd.append('delete_media_ids[]', String(id))
    })

    fd.append('_method', 'PATCH');

    console.log('FD →', [...fd.entries()].map(([k, v]) => [k, v instanceof File ? v.name : v]));

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
        newPartnerFiles.value = [];
        deletedMediaIds.value = [];
    } catch (e) {
        const errs = e?.response?.data?.errors
        if (errs) console.error(humanizeErrors(errs, newPartnerFiles.value))
        console.log('tab-errors', e)
        console.log(e.response?.data)
        return
    }
    dirty.value = false
    loaded.value = true
    emit('dirty', false)
    emit('saved', { slug : props?.hackathonSlug })
}

function humanizeErrors(errors, files) {
    if (!errors || typeof errors !== 'object') return 'Ошибка валидации'
    const out = []
    for (const [k, msgs] of Object.entries(errors)) {
        const msg = Array.isArray(msgs) ? msgs[0] : String(msgs)
        const m = k.match(/^partners\.(\d+)/)
        if (m) {
            const i = +m[1]
            const name = files?.[i]?.name || `файл №${i+1}`
            out.push(`«${name}»: ${msg}`)
        } else if (k.startsWith('delete_media_ids')) {
            out.push(`Удаление файла: ${msg}`)
        } else {
            out.push(msg)
        }
    }
    return out.join('\n')
}

defineExpose({ save })

const resetState = () => {
    description.value  = null
    plan.value         = null
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
    <div class="dialog__component" v-if="!isEdit || loaded">
        <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.description) }}</p>
        <EditorField v-model="description" :placeholder="capitalizeFirstLetter(langStore.translations.enterDescription)"/>
    </div>
    <div class="dialog__component" v-if="!isEdit || loaded">
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
                    <p class="dialog__prize_text">{{ n.prize || capitalizeFirstLetter(langStore.translations.no_amount) }}</p>
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
        <DropFiles
            :files="partnerLogos"
            @update:files="handleFilesUpdate"
            @deleting-ids="handleDeletingIds"
        />
    </div>
    <div class="dialog__btns" v-if="!isAdmin">
        <button class="main__btn main__btn_white" @click="cancel">{{ capitalizeFirstLetter(langStore.translations.cansel) }}</button>
        <button class="main__btn" @click="save">{{ capitalizeFirstLetter(langStore.translations.save) }}</button>
    </div>
</template>

<style scoped>

</style>
