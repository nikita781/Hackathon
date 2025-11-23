<script setup>
import EditorField from "@/Components/EditorField.vue";
import {computed, nextTick, onMounted, ref, watch} from "vue";
import DropPDFs from "@/Components/DropPDFs.vue";
import {router, useForm} from "@inertiajs/vue3";
import {useLangStore} from "@/store/lang.js";

const props = defineProps({
    hackathonSlug : { type:String, required:true },
    draft         : Object,
    allTags  : { type:Array, default:() => [] },
    isEdit   : { type:Boolean, default:false },
    admin      : { type:Boolean, default:() => false },
})
const emit = defineEmits(['saved', 'cancel', 'dirty', 'saving'])

const isAdmin = computed(() => !!props.admin)

const rulesText = ref(null)
const rulesFiles = ref([])
const deletedMediaIds  = ref([])

const loaded = ref(false)
const rulesKey = ref(0)

async function fetchResources() {
    try {
        const { data } = await axios.get(
            route('hackathons.show', { hackathon: props.hackathonSlug }),
            { headers: { Accept: 'application/json' } }
        );
        if (props.isEdit) {
            const hackathon = data.tabs.original[2];

            const lang = langStore.currentLanguage
            const titlesByLang = {
                ru: 'Правила',
                en: 'Rules',
                es: 'Reglas',
                zh: '规则',
                fr: 'Règles',
                de: 'Regeln',
                pt: 'Regras',
            }
            const targetTitle = titlesByLang[lang] || titlesByLang.ru

            const section =
                hackathon.sections.find(s => s.title === targetTitle)
                || hackathon.sections.find(s => s.title === 'Правила')
                || hackathon.sections.find(s => s.title === 'Rules')

            rulesText.value  = section?.content || ''
            rulesFiles.value = hackathon.files
            rulesKey.value++
        }
        await nextTick()
        loaded.value = true
    } catch (err) {
        console.error('fetch-resources-error', err?.response ?? err);
    }
}

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

const filesErrors = computed(() => {
        const out = []
        for (const [k, v] of Object.entries(form.errors ?? {})) {
            if (k.startsWith('files') || k.startsWith('delete_media_ids')) {
                out.push(Array.isArray(v) ? v.join(' ') : String(v))
            }
        }
        return out
    })

function applyBackendErrors(errs) {
    form.clearErrors()
    if (!errs || typeof errs !== 'object') return
    Object.entries(errs).forEach(([field, messages]) => {
        form.setError(field, Array.isArray(messages) ? messages.join(' ') : String(messages))
    })
}

watch(rulesText, () => form.clearErrors('sections.0.content'))

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
    emit('saving', true)
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

        await fetchResources()
        deletedMediaIds.value = []
        form.files = []

        dirty.value = false
        emit('dirty', false)
        emit('saved', { slug : props?.hackathonSlug })
    } catch (err) {
        if (err?.response?.status === 422) {
            applyBackendErrors(err.response.data?.errors || {})
            return
        }
        console.log('rules-errors', err?.response ?? err)
    } finally {
        emit('saving', false)
    }
}

function clearFilesErrors() {
    Object.keys(form.errors).forEach(k => {
        if (k.startsWith('files') || k.startsWith('delete_media_ids')) form.clearErrors(k)
    })
}

defineExpose({ save })

const resetState = () => {
    rulesText.value   = null
    rulesFiles.value  = []
    form.sections[0].content = ''
    form.files               = []
    form.delete_media_ids    = []
    rulesKey.value++
}

function cancel () {
    resetState()
    emit('cancel')
}

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

function dedupMixed(mixed) {
    if (!Array.isArray(mixed)) return []
    const seen = new Set()
    return mixed.filter(x => {
            if (x instanceof File) {
                const key = `${x.name}|${x.size}|${x.lastModified ?? 0}`
                if (seen.has(key)) return false
                seen.add(key)
                return true
            }
            return true
        })
}
const handleFilesUpdate = (mixed) => {
    const deduped = dedupMixed(mixed)
    rulesFiles.value = deduped
    form.files = deduped.filter(x => x instanceof File)
    clearFilesErrors()
}

const handleDeletingIds = (ids) => { deletedMediaIds.value = ids; clearFilesErrors() }

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
        <div class="dialog__title_container">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.rules) }}</p>
            <div class="help-tt" aria-label="help">
                <svg class="help-tt__icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="#000" />
                    <path d="M12 17v-5" stroke="#000" stroke-linecap="round"/>
                    <circle cx="12" cy="8" r="1" fill="#000"/>
                </svg>
                <div class="tooltipSquare"></div>
                <div class="tooltip">
                    <p>Это блок правил мероприятия</p>
                </div>
            </div>
        </div>
        <EditorField v-model="rulesText" :placeholder="capitalizeFirstLetter(langStore.translations.enterDescription)"/>
        <small v-if="form.errors['sections.0.content']" class="error__text">
          {{ form.errors['sections.0.content'] }}
        </small>
    </div>
    <div class="dialog__component">
        <div class="dialog__title_container">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.files) }}</p>
            <div class="help-tt" aria-label="help">
                <svg class="help-tt__icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="#000" />
                    <path d="M12 17v-5" stroke="#000" stroke-linecap="round"/>
                    <circle cx="12" cy="8" r="1" fill="#000"/>
                </svg>
                <div class="tooltipSquare"></div>
                <div class="tooltip">
                    <p>Это блок для добавления файлов и дальнейшего скачивания на вкладке Правила</p>
                </div>
            </div>
        </div>
        <DropPDFs
            :key="rulesKey"
            :files="rulesFiles"
            @update:files="handleFilesUpdate"
            @deleting-ids="handleDeletingIds"
        />
        <div v-if="filesErrors.length" style="margin-top:6px">
          <small v-for="(msg,i) in filesErrors" :key="i" class="error__text">{{ msg }}</small>
        </div>
    </div>
    <div class="dialog__btns" v-if="!isAdmin">
        <button class="main__btn main__btn_white" @click="cancel">{{ capitalizeFirstLetter(langStore.translations.cansel) }}</button>
        <button class="main__btn" @click="save">{{ capitalizeFirstLetter(langStore.translations.save) }}</button>
    </div>
</template>

<style scoped>

</style>
