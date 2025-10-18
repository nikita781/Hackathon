<script setup>
import IconsCancel from "@/Components/Icons/Cancel.vue";
import IconsPencil from "@/Components/Icons/Pencil.vue";
import CreateAward from "@/Components/Dialog/CreateAward.vue";
import {computed, nextTick, onMounted, ref} from "vue";
import {router} from "@inertiajs/vue3";
import ConfirmDialog from '@/Components/Dialog/ConfirmDialog.vue'
import {useLangStore} from "@/store/lang.js";

const props = defineProps({
    hackathonSlug : { type:String, required:true },
    draft         : Object,
    allTags  : { type:Array, default:() => [] },
    admin      : { type:Boolean, default:() => false },
})
const emit = defineEmits(['saved', 'cancel', 'dirty'])

const isAdmin = computed(() => !!props.admin)
const langStore = useLangStore()

const awards = ref([])

async function fetchHackathon () {
    try {
        const { data } = await axios.get(
            route('hackathons.show', { hackathon: props.hackathonSlug }),
            { headers: { Accept: 'application/json' } }
        )
        awards.value = data.hackathon.original.awards
        // console.log(awards.value)
        // await nextTick()
        // loaded.value = true
    } catch (err) {
        console.error('fetch-hackathon-error', err?.response ?? err)
    }
}
onMounted(fetchHackathon)

function onSaved()              { dlgShown.value = false; fetchHackathon() }

const dlgShown    = ref(false)
const editingId  = ref(null)
const defaultType  = ref('forAll')

const showDoneDlg = ref(false)

const awardsForAll   = computed(() => awards.value.filter(a => a.for_all))
const awardsByPlace  = computed(() => awards.value.filter(a => !a.for_all))

function openAdd (type) {
    editingId.value = null
    defaultType.value = type
    dlgShown.value = true
}
function openEdit(aw)             { editingId.value = aw.id; dlgShown.value = true }

const getImgSrc = (aw) => {
    if (aw._localBlob) return aw._localBlob

    const ver = aw.updated_at ?? Date.now()
    return `${route('awards.image', { award: aw.id })}?v=${ver}`
}

async function removeAward (aw) {
    try {
        await router.delete(
            route('hackathons.awards.destroy',
                { hackathon: props.hackathonSlug, award: aw.id }),
            { preserveScroll:true }
        )
        await fetchHackathon()
        awards.value = awards.value.filter(a => a.id !== aw.id)
    } catch (err) {
        console.error('award-delete', err?.response ?? err)
    }
}

const resetState = () => {
    dlgShown.value   = false
    editingId.value  = null
    defaultType.value= 'forAll'
    awards.value     = []
}

function cancel () {
    resetState()
    emit('cancel')
}

function askDone () {
    showDoneDlg.value = true
}

async function confirmDone () {
    showDoneDlg.value = false
    emit('cancel')
}

onMounted(async () => {
    await langStore.fetchTranslations()
})

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}
</script>

<template>
    <div class="dialog__prize">
        <div class="dialog__title_header">
            <div class="dialog__title_container">
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.awards_all_participants) }}</p>
                <div class="help-tt" aria-label="help">
                    <svg class="help-tt__icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="#000" />
                        <path d="M12 17v-5" stroke="#000" stroke-linecap="round"/>
                        <circle cx="12" cy="8" r="1" fill="#000"/>
                    </svg>
                    <div class="tooltipSquare"></div>
                    <div class="tooltip">
                        <p>Это блок достижений, получаемых после завершения хакатона</p>
                    </div>
                </div>
            </div>
            <div class="dialog__plus" @click="openAdd('forAll')">
                <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.1665 7.33317H9.1665V3.33317C9.1665 3.15636 9.09627 2.98679 8.97124 2.86177C8.84622 2.73674 8.67665 2.6665 8.49984 2.6665C8.32303 2.6665 8.15346 2.73674 8.02843 2.86177C7.90341 2.98679 7.83317 3.15636 7.83317 3.33317V7.33317H3.83317C3.65636 7.33317 3.48679 7.40341 3.36177 7.52843C3.23674 7.65346 3.1665 7.82303 3.1665 7.99984C3.1665 8.17665 3.23674 8.34622 3.36177 8.47124C3.48679 8.59627 3.65636 8.6665 3.83317 8.6665H7.83317V12.6665C7.83317 12.8433 7.90341 13.0129 8.02843 13.1379C8.15346 13.2629 8.32303 13.3332 8.49984 13.3332C8.67665 13.3332 8.84622 13.2629 8.97124 13.1379C9.09627 13.0129 9.1665 12.8433 9.1665 12.6665V8.6665H13.1665C13.3433 8.6665 13.5129 8.59627 13.6379 8.47124C13.7629 8.34622 13.8332 8.17665 13.8332 7.99984C13.8332 7.82303 13.7629 7.65346 13.6379 7.52843C13.5129 7.40341 13.3433 7.33317 13.1665 7.33317Z" fill="#E80024"/>
                </svg>
                <p>{{ capitalizeFirstLetter(langStore.translations.addMore) }}</p>
            </div>
        </div>
    </div>
    <div v-for="aw in awardsForAll" :key="aw.id" class="dialog__eva_container">
        <div class="dialog__award">
            <img :src="getImgSrc(aw)" class="dialog__award_img" />
            <div class="dialog__award_content">
                <p class="dialog__award_title">{{ aw.title }}</p>
                <p class="dialog__award_text">{{ aw.description }}</p>
            </div>
        </div>
        <div class="dialog__prize_btns">
            <IconsPencil style="padding: 5px" class="clickable" @click="openEdit(aw)" />
            <IconsCancel class="clickable" @click="removeAward(aw)" />
        </div>
    </div>
    <div class="dialog__prize">
        <div class="dialog__title_header">
            <div class="dialog__title_container">
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.awards_prize_places) }}</p>
                <div class="help-tt" aria-label="help">
                    <svg class="help-tt__icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="#000" />
                        <path d="M12 17v-5" stroke="#000" stroke-linecap="round"/>
                        <circle cx="12" cy="8" r="1" fill="#000"/>
                    </svg>
                    <div class="tooltipSquare"></div>
                    <div class="tooltip">
                        <p>Это блок достижений, получаемых после завершения хакатона</p>
                    </div>
                </div>
            </div>
            <div class="dialog__plus" @click="openAdd('forPrize')">
                <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.1665 7.33317H9.1665V3.33317C9.1665 3.15636 9.09627 2.98679 8.97124 2.86177C8.84622 2.73674 8.67665 2.6665 8.49984 2.6665C8.32303 2.6665 8.15346 2.73674 8.02843 2.86177C7.90341 2.98679 7.83317 3.15636 7.83317 3.33317V7.33317H3.83317C3.65636 7.33317 3.48679 7.40341 3.36177 7.52843C3.23674 7.65346 3.1665 7.82303 3.1665 7.99984C3.1665 8.17665 3.23674 8.34622 3.36177 8.47124C3.48679 8.59627 3.65636 8.6665 3.83317 8.6665H7.83317V12.6665C7.83317 12.8433 7.90341 13.0129 8.02843 13.1379C8.15346 13.2629 8.32303 13.3332 8.49984 13.3332C8.67665 13.3332 8.84622 13.2629 8.97124 13.1379C9.09627 13.0129 9.1665 12.8433 9.1665 12.6665V8.6665H13.1665C13.3433 8.6665 13.5129 8.59627 13.6379 8.47124C13.7629 8.34622 13.8332 8.17665 13.8332 7.99984C13.8332 7.82303 13.7629 7.65346 13.6379 7.52843C13.5129 7.40341 13.3433 7.33317 13.1665 7.33317Z" fill="#E80024"/>
                </svg>
                <p>{{ capitalizeFirstLetter(langStore.translations.addMore) }}</p>
            </div>
        </div>
    </div>
    <div v-for="aw in awardsByPlace" :key="aw.id" class="dialog__eva_container">
        <div class="dialog__award">
            <img :src="getImgSrc(aw)" class="dialog__award_img" />
            <div class="dialog__award_content">
                <p class="dialog__award_title">({{ capitalizeFirstLetter(langStore.translations.place) }} {{ aw.place }}) {{ aw.title }}</p>
                <p class="dialog__award_text">{{ aw.description }}</p>
            </div>
        </div>
        <div class="dialog__prize_btns">
            <IconsPencil style="padding: 5px" class="clickable" @click="openEdit(aw)" />
            <IconsCancel class="clickable" @click="removeAward(aw)" />
        </div>
    </div>
    <CreateAward
        v-model="dlgShown"
        :hackathonSlug="props.hackathonSlug"
        :initial="editingId ? awards.find(a => a.id === editingId) : null"
        :default-type="defaultType"
        @saved="onSaved"
    />
    <div class="dialog__btns" v-if="!isAdmin">
        <button class="main__btn main__btn_white" @click="cancel">{{ capitalizeFirstLetter(langStore.translations.cansel) }}</button>
        <button class="main__btn" @click="askDone">{{ capitalizeFirstLetter(langStore.translations.save) }}</button>
    </div>

    <ConfirmDialog
        v-model="showDoneDlg"
        text="Вы закончили?"
        @confirm="confirmDone"
        @cancel="showDoneDlg = false"
    />
</template>

<style scoped>

</style>
