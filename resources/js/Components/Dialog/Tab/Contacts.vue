<script setup>
import {onMounted, nextTick, ref, watch, computed, reactive} from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import DialogContact from '@/Components/Dialog/CreateContact.vue'

import IconsCancel from "@/Components/Icons/Cancel.vue";
import IconsPencil from "@/Components/Icons/Pencil.vue";
import { useLangStore } from "@/store/lang.js";

const props = defineProps({
    hackathonSlug : { type:String, required:true },
    draft         : Object,
    allTags       : { type:Array, default:() => [] },
    isEdit        : { type:Boolean, default:false },
    admin      : { type:Boolean, default:() => false },
})
const emit = defineEmits(['saved','cancel','dirty', 'saving'])

const errors = reactive({})

function setErrorsRaw(obj) {
    for (const k in errors) delete errors[k]
    if (obj && typeof obj === 'object') Object.assign(errors, obj)
}
function clearAllErrors() {
    for (const k in errors) delete errors[k]
}
function itemErr(list, idx, field = 'content') {
    const sec = list === 'contacts' ? 0 : 1
    const key = `sections.${sec}.items.${idx}.${field}`
    const v = errors[key]
    return Array.isArray(v) ? v[0] : v || ''
}
function clearItemErr(list, idx, field = 'content') {
    const sec = list === 'contacts' ? 0 : 1
    delete errors[`sections.${sec}.items.${idx}.${field}`]
}

const isAdmin = computed(() => !!props.admin)

const langStore = useLangStore()

const loaded = ref(false)
const dirty  = ref(false)

const contacts = ref([])  // [{ title, value }]
const socials  = ref([])  // [{ title, value }]

const dlgShown    = ref(false)
const dlgListName = ref('contacts')
const editingIdx  = ref(null)

function openAdd(list){ dlgListName.value = list; editingIdx.value = null; dlgShown.value = true; clearAllErrors() }
function openEdit(list, idx){ dlgListName.value = list; editingIdx.value = idx; dlgShown.value = true; clearAllErrors() }

function addItem(item){ (dlgListName.value === 'contacts' ? contacts : socials).value.push(item) }
function updateItem(item){
    const list = dlgListName.value === 'contacts' ? contacts : socials
    if (editingIdx.value !== null) list.value[editingIdx.value] = item
    editingIdx.value = null
}
function removeItem(list, idx){
    (list === 'contacts' ? contacts : socials).value.splice(idx,1)
    clearAllErrors()
}

/* ------------ форма для отправки ------------ */
const form = useForm({
    sections: [
        { title:'Контакты',        items: [] },
        { title:'Социальные сети', items: [] },
    ],
    delete_media_ids: []
})

/* синхронизация локального состояния -> form.sections */
watch(contacts, arr => {
    form.sections[0].items = arr.map(i => ({ title:i.title, content:i.value }))
}, { deep:true, immediate:true })

watch(socials, arr => {
    form.sections[1].items = arr.map(i => ({ title:i.title, content:i.value }))
}, { deep:true, immediate:true })

/* dirty только после загрузки */
watch([contacts, socials], () => {
    if (!loaded.value) return
    if (!dirty.value) {
        dirty.value = true
        emit('dirty', true)
    }
}, { deep:true })

/* ------------ загрузка для редактирования ------------ */
async function fetchContacts(){
    try{
        const { data } = await axios.get(
            route('hackathons.show', { hackathon: props.hackathonSlug }),
            { headers:{ Accept:'application/json' } }
        )

        // Берём таб «Контакты»
        const tab =
            data?.tabs?.original?.find(t => t.title === 'Контакты')
            ?? data?.tabs?.original?.[4] // запасной вариант по индексу

        const secContacts = tab?.sections?.find(s => s.title === 'Контакты')
        const secSocials  = tab?.sections?.find(s => s.title === 'Социальные сети')

        contacts.value = (secContacts?.items ?? []).map(it => ({ title: it.title, value: it.content }))
        socials.value  = (secSocials?.items  ?? []).map(it => ({ title: it.title, value: it.content }))

        await nextTick()
        loaded.value = true
    } catch (err){
        console.error('contacts-load-error', err?.response ?? err)
        loaded.value = true   // чтобы не зависнуть
    }
}

/* ------------ сохранение ------------ */
async function save(){
    emit('saving', true)
    const fd = new FormData()
    fd.append('title', 'Контакты')

    form.sections.forEach((s, si) => {
        fd.append(`sections[${si}][title]`, s.title)
        s.items.forEach((it, ii) => {
            fd.append(`sections[${si}][items][${ii}][title]`,   it.title)
            fd.append(`sections[${si}][items][${ii}][content]`, it.content)
        })
    })

    fd.append('_method','PATCH')

    try{
        await axios.post(
            route('hackathons.tabs.update', { hackathon: props.hackathonSlug }),
            fd,
            { headers:{ 'Content-Type':'multipart/form-data' } }
        )
        dirty.value = false
        emit('dirty', false)
        emit('saved', { slug: props.hackathonSlug })
    } catch (err){
        if (err?.response?.status === 422) {
            setErrorsRaw(err.response.data?.errors || {})
        } else {
            console.error('contacts-tab-errors', err?.response ?? err)
        }
    } finally {
        emit('saving', false)
    }
}

defineExpose({ save })

const resetState = () => {
    contacts.value = []
    socials.value  = []
    dlgShown.value = false
    editingIdx.value = null
    dlgListName.value = 'contacts'
    form.sections.forEach(s => (s.items = []))
    clearAllErrors()
}

function cancel(){ resetState(); emit('cancel') }

/* ------------ i18n + init ------------ */
onMounted(async () => {
    await langStore.fetchTranslations()
    if (props.isEdit) {
        await fetchContacts()
    } else {
        loaded.value = true
    }
})

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}
</script>

<template>
    <div class="dialog__prize">
        <div class="dialog__title_header">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.contact) }}</p>
            <div class="dialog__plus" @click="openAdd('contacts')">
                <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.1665 7.33317H9.1665V3.33317C9.1665 3.15636 9.09627 2.98679 8.97124 2.86177C8.84622 2.73674 8.67665 2.6665 8.49984 2.6665C8.32303 2.6665 8.15346 2.73674 8.02843 2.86177C7.90341 2.98679 7.83317 3.15636 7.83317 3.33317V7.33317H3.83317C3.65636 7.33317 3.48679 7.40341 3.36177 7.52843C3.23674 7.65346 3.1665 7.82303 3.1665 7.99984C3.1665 8.17665 3.23674 8.34622 3.36177 8.47124C3.48679 8.59627 3.65636 8.6665 3.83317 8.6665H7.83317V12.6665C7.83317 12.8433 7.90341 13.0129 8.02843 13.1379C8.15346 13.2629 8.32303 13.3332 8.49984 13.3332C8.67665 13.3332 8.84622 13.2629 8.97124 13.1379C9.09627 13.0129 9.1665 12.8433 9.1665 12.6665V8.6665H13.1665C13.3433 8.6665 13.5129 8.59627 13.6379 8.47124C13.7629 8.34622 13.8332 8.17665 13.8332 7.99984C13.8332 7.82303 13.7629 7.65346 13.6379 7.52843C13.5129 7.40341 13.3433 7.33317 13.1665 7.33317Z" fill="#E80024"/>
                </svg>
                <p>{{ capitalizeFirstLetter(langStore.translations.addMore) }}</p>
            </div>
        </div>

        <div v-for="(c,idx) in contacts" :key="`c-${idx}`" class="dialog__component">
            <p class="dialog__title">
                {{ c.title }}
            </p>
            <div class="dialog__input_btns">
                <input
                    v-model="c.value"
                    class="dialog__input"
                    :class="{ error: !!itemErr('contacts', idx, 'content') }"
                    readonly
                    style="width:100%"/>
                <div class="dialog__prize_btns">
                    <IconsPencil style="padding:5px" class="clickable" @click="openEdit('contacts',idx)" />
                    <IconsCancel class="clickable" @click="removeItem('contacts',idx)" />
                </div>
            </div>
            <small v-if="itemErr('contacts', idx, 'content')" class="error__text">
                {{ itemErr('contacts', idx, 'content') }}
            </small>
            <small v-if="itemErr('contacts', idx, 'title')" class="error__text">
                {{ itemErr('contacts', idx, 'title') }}
            </small>
        </div>
    </div>

    <div class="dialog__prize">
        <div class="dialog__title_header">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.socialLinks) }}</p>
            <div class="dialog__plus" @click="openAdd('socials')">
                <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.1665 7.33317H9.1665V3.33317C9.1665 3.15636 9.09627 2.98679 8.97124 2.86177C8.84622 2.73674 8.67665 2.6665 8.49984 2.6665C8.32303 2.6665 8.15346 2.73674 8.02843 2.86177C7.90341 2.98679 7.83317 3.15636 7.83317 3.33317V7.33317H3.83317C3.65636 7.33317 3.48679 7.40341 3.36177 7.52843C3.23674 7.65346 3.1665 7.82303 3.1665 7.99984C3.1665 8.17665 3.23674 8.34622 3.36177 8.47124C3.48679 8.59627 3.65636 8.6665 3.83317 8.6665H7.83317V12.6665C7.83317 12.8433 7.90341 13.0129 8.02843 13.1379C8.15346 13.2629 8.32303 13.3332 8.49984 13.3332C8.67665 13.3332 8.84622 13.2629 8.97124 13.1379C9.09627 13.0129 9.1665 12.8433 9.1665 12.6665V8.6665H13.1665C13.3433 8.6665 13.5129 8.59627 13.6379 8.47124C13.7629 8.34622 13.8332 8.17665 13.8332 7.99984C13.8332 7.82303 13.7629 7.65346 13.6379 7.52843C13.5129 7.40341 13.3433 7.33317 13.1665 7.33317Z" fill="#E80024"/>
                </svg>
                <p>{{ capitalizeFirstLetter(langStore.translations.addMore) }}</p>
            </div>
        </div>
        <div v-for="(s,idx) in socials" :key="idx" class="dialog__component">
            <p class="dialog__title">{{ s.title }}</p>
            <div class="dialog__input_btns">
                <input
                    v-model="s.value"
                    class="dialog__input"
                    :class="{ error: !!itemErr('socials', idx, 'content') }"
                    readonly
                    style="width: 100%"/>
                <div class="dialog__prize_btns">
                    <IconsPencil style="padding: 5px" class="clickable" @click="openEdit('socials',idx)" />
                    <IconsCancel class="clickable" @click="removeItem('socials',idx)" />
                </div>
            </div>
            <small v-if="itemErr('socials', idx, 'content')" class="error__text">
                {{ itemErr('socials', idx, 'content') }}
            </small>
            <small v-if="itemErr('socials', idx, 'title')" class="error__text">
                {{ itemErr('socials', idx, 'title') }}
            </small>
        </div>
    </div>
    <DialogContact
        v-model="dlgShown"
        :initial="editingIdx!==null
                ? (dlgListName==='contacts' ? contacts[editingIdx] : socials[editingIdx])
                : null"
        @add="addItem"
        @update="updateItem"
    />
    <div class="dialog__btns" v-if="!isAdmin">
        <button class="main__btn main__btn_white" @click="cancel">{{ capitalizeFirstLetter(langStore.translations.cansel) }}</button>
        <button class="main__btn" @click="save">{{ capitalizeFirstLetter(langStore.translations.save) }}</button>
    </div>
</template>

<style scoped>

</style>
