<script setup>
import IconsCancel from "@/Components/Icons/Cancel.vue";
import {computed, onBeforeUnmount, onMounted, ref, watch} from "vue";
import { useClipboard } from '@vueuse/core'
import {useLangStore} from "@/store/lang.js";
import {useToast} from "vue-toastification";
import CustomSelect from "@/Components/CustomSelect.vue";

const props = defineProps({
    modelValue : Boolean,
    positions : { type: Array, default : () => [] },
    ownTeam : { type: Object, default : () => ({}) },
    hackathon : { type: Object, default : null },
})

const emit = defineEmits([
    'update:modelValue',
])

const previousBodyOverflow = ref('')
const toast = useToast();
const pending = ref(false)
const lookups = ref([{ loading:false, touched:false, found:false, canInvite:false, user:null, errors:[] }])
const debounceTimers = new Map()
const rowErrors = ref([])
const inviteLink = ref('')
const { copy, copied } = useClipboard()
const PLACEHOLDER = '/profile.jpg';
const langStore = useLangStore()
const isProfileTeam = computed(() => !!props.ownTeam?.is_profile_team)

watch(
    () => props.modelValue,
    (opened) => {
        if (opened) {
            previousBodyOverflow.value = document.body.style.overflow
            document.body.style.overflow = 'hidden'
        } else {
            document.body.style.overflow = previousBodyOverflow.value || ''
        }
    }
)

onBeforeUnmount(() => {
    document.body.style.overflow = previousBodyOverflow.value || ''
})

const blankLookup = () => ({ loading:false, touched:false, found:false, canInvite:false, user:null, errors:[] })
const ensureLookupRow = (i) => { if (!lookups.value[i]) lookups.value.splice(i, 0, blankLookup()) }
const clearTimer = (i) => { const t = debounceTimers.get(i); if (t) { clearTimeout(t); debounceTimers.delete(i) } }
const defaultPositionId = () => props.positions[0]?.id

function clearAllTimers(){ debounceTimers.forEach(t => clearTimeout(t)); debounceTimers.clear() }

function resetState(){
    clearAllTimers()
    userIds.value   = [{ user_id: '', position_id: defaultPositionId() }]
    lookups.value   = [blankLookup()]
    rowErrors.value = []
    pending.value   = false
    inviteLink.value = ''
}

function close(){ emit('update:modelValue',false) }

function avatarSrc(photo){
    if (!photo) return PLACEHOLDER
    const url = String(photo).trim()
    return /[^/]+\.[a-z0-9]+(?:\?.*)?$/i.test(url) ? url : PLACEHOLDER
}

function imgFallback(e){ e.target.onerror=null; e.target.src=PLACEHOLDER }

function onUserInput(i){
    rowErrors.value[i] && (rowErrors.value[i] = '')
    ensureLookupRow(i)
    clearTimer(i)
    const q = (userIds.value[i]?.user_id ?? '').toString().trim()
    if (!q){
        Object.assign(lookups.value[i], blankLookup())
        return
    }
    lookups.value[i].loading = true
    lookups.value[i].touched = true
    debounceTimers.set(i, setTimeout(() => doLookup(i, q), 350))
}

async function doLookup(i, q){
    try{
        const lookupRoute = isProfileTeam.value
            ? route('profile.teams.search', { team: props.ownTeam.id })
            : route('hackathons.teams.search', { hackathon: props.hackathon.slug, team: props.ownTeam.id })

        const { data } = await axios.get(lookupRoute, { params: { q } })
        ensureLookupRow(i)
        lookups.value[i].loading   = false
        lookups.value[i].found     = !!data?.user
        lookups.value[i].user      = data?.user ?? null
        lookups.value[i].canInvite = !!data?.canInvite
        lookups.value[i].errors    = data?.errors ?? []
    } catch(e){
        ensureLookupRow(i)
        lookups.value[i].loading = false
        lookups.value[i].found   = false
        lookups.value[i].errors  = ['Не удалось проверить пользователя']
        console.error('team-search-error', e)
    }
}

const userIds = ref([{ user_id: '', position_id: props.positions[0]?.id }])

async function getLink () {
    inviteLink.value = ''
    try {
        const inviteRoute = isProfileTeam.value
            ? route('profile.teams.create-invite', { team: props.ownTeam.id })
            : route('hackathons.teams.create-invite', {
                hackathon: props.hackathon.slug,
                team     : props.ownTeam.id
            })

        const { data } = await axios.post(inviteRoute)
        inviteLink.value = data.url
    } catch (e) {
        console.error('link-error', e)
    }
}

watch(() => [props.modelValue, props.ownTeam?.id], async ([opened]) => {
    if (!opened){
        resetState()
        return
    }
    resetState()
    await getLink()
}, { immediate: true })

const addUserField = () => {
    userIds.value.push({ user_id: '', position_id: defaultPositionId() })
    rowErrors.value.push('')
    lookups.value.push(blankLookup())
}

const removeUserField = (index) => {
    userIds.value.splice(index, 1)
    rowErrors.value.splice(index, 1)
    lookups.value.splice(index, 1)
}

const hasForbidden = computed(() => lookups.value.some(s => s.touched && s.found && s.canInvite === false))
const inviteBtnDisabled = computed(() => pending.value || hasForbidden.value)

const toId = (val) => {
    if (typeof val === 'number') return val
    const s = String(val ?? '').trim()
    return /^\d+$/.test(s) ? Number(s) : s
}

const inviteUsers = async () => {
    if (hasForbidden.value){
        toast.error(capitalizeFirstLetter(langStore.translations.some_users_cannot_be_invited))
        return
    }
    try {
        pending.value = true
        const inviteRoute = isProfileTeam.value
            ? route('profile.teams.invite-by-id', { team: props.ownTeam.id })
            : route('hackathons.teams.invite-by-id', {
                hackathon: props.hackathon.slug,
                team     : props.ownTeam.id,
            })

        await axios.post(
            inviteRoute,
            {
                users: userIds.value.map(({ user_id, position_id }, i) => {
                    const found = lookups.value[i]?.found && lookups.value[i]?.user?.id != null
                    const resolvedId = found ? Number(lookups.value[i].user.id) : null
                    const fallback   = resolvedId ?? toId(user_id)
                    return { user_id: fallback, position_id }
                })
            }
        )
        toast.success(capitalizeFirstLetter(langStore.translations.invitation_sent), {
            position: 'top-right',
            timeout: 5000,
        });
        resetState()
        close()
    } catch (error) {
        if (error?.response?.status === 422) {
            const errs = error.response.data?.errors ?? {}
            rowErrors.value = Object.entries(errs).reduce((acc, [k, v]) => {
                const i = +k.split('.')[1]
                acc[i] = Array.isArray(v) ? v[0] : v
                return acc
            }, [])
        } else {
            console.error(error)
        }
    } finally {
        pending.value = false
    }
}

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

onMounted(async () => {
    await langStore.fetchTranslations()
});

const positionOptions = computed(() =>
    (props.positions ?? []).map(p => ({
        value: p.id,
        label: p.name,
    }))
)
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:2">
        <div class="dialog__container dialog__container_small" @click.stop>
            <div class="dialog__header">
                <p>{{ capitalizeFirstLetter(langStore.translations.inviteToTeam) }}</p>
                <div class="dialog__close" @click="close"><svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M7.90994 6.00019L12.2099 1.71019C12.3982 1.52188 12.504 1.26649 12.504 1.00019C12.504 0.733884 12.3982 0.478489 12.2099 0.290185C12.0216 0.101882 11.7662 -0.00390625 11.4999 -0.00390625C11.2336 -0.00390625 10.9782 0.101882 10.7899 0.290185L6.49994 4.59019L2.20994 0.290185C2.02164 0.101882 1.76624 -0.00390625 1.49994 -0.00390625C1.23364 -0.00390625 0.978243 0.101882 0.789939 0.290185C0.601635 0.478489 0.495847 0.733884 0.495847 1.00019C0.495847 1.26649 0.601635 1.52188 0.789939 1.71019L5.08994 6.00019L0.789939 10.2902C0.696211 10.3831 0.621816 10.4937 0.571048 10.6156C0.520279 10.7375 0.494141 10.8682 0.494141 11.0002C0.494141 11.1322 0.520279 11.2629 0.571048 11.3848C0.621816 11.5066 0.696211 11.6172 0.789939 11.7102C0.882902 11.8039 0.993503 11.8783 1.11536 11.9291C1.23722 11.9798 1.36793 12.006 1.49994 12.006C1.63195 12.006 1.76266 11.9798 1.88452 11.9291C2.00638 11.8783 2.11698 11.8039 2.20994 11.7102L6.49994 7.41019L10.7899 11.7102C10.8829 11.8039 10.9935 11.8783 11.1154 11.9291C11.2372 11.9798 11.3679 12.006 11.4999 12.006C11.632 12.006 11.7627 11.9798 11.8845 11.9291C12.0064 11.8783 12.117 11.8039 12.2099 11.7102C12.3037 11.6172 12.3781 11.5066 12.4288 11.3848C12.4796 11.2629 12.5057 11.1322 12.5057 11.0002C12.5057 10.8682 12.4796 10.7375 12.4288 10.6156C12.3781 10.4937 12.3037 10.3831 12.2099 10.2902L7.90994 6.00019Z"
                        fill="#999999"
                    />
                </svg></div>
            </div>
            <p class="dialog__text" style="margin-top: -18px">{{
                    capitalizeFirstLetter(langStore.translations.inviteDescription)
                }}</p>
            <div class="dialog__component dialog__line">
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.inviteLink) }}</p>
                <div class="dialog__input_btns dialog__input_btns_small dialog__input_btns-phone">
                    <input
                        class="dialog__input"
                        :value="inviteLink"
                        readonly
                        :placeholder="capitalizeFirstLetter(langStore.translations.linkText)"
                        style="width: 100%"
                    />
                    <button class="main__btn dialog__btn"
                            :class="{ blocked: !inviteLink }"
                            :disabled="!inviteLink"
                            @click="copy(inviteLink)">
                        {{ copied ? capitalizeFirstLetter(langStore.translations.copied) : capitalizeFirstLetter(langStore.translations.copy) }}
                    </button>
                </div>
            </div>
            <div v-for="(user, index) in userIds" :key="index" class="dialog__component">
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.addMemberById) }} или nickname</p>
                <div class="dialog__input_btns dialog__input_btns_small dialog__input_btns-phone">
                    <input
                        v-model="user.user_id"
                        class="dialog__input"
                        :placeholder="capitalizeFirstLetter(langStore.translations.enterMemberId)"
                        style="width: 100%"
                        @input="onUserInput(index)"
                    />
                    <div class="dialog__input_reset">
                        <CustomSelect
                            v-model="user.position_id"
                            :options="positionOptions"
                            placeholder="Выберите роль"
                            full-width
                            min-width="200px"
                            close-by-scroll
                        />
                        <div>
                            <IconsCancel class="clickable" style="cursor: pointer" @click="removeUserField(index)"/>
                        </div>
                    </div>
                </div>
                <p v-if="rowErrors[index]" style="color:#E80024; margin-top:6px">
                    {{ rowErrors[index] }}
                </p>
                <div v-if="lookups[index]?.touched" class="dialog__hint" style="margin-top:6px">
                    <template v-if="lookups[index].loading">
                        {{ capitalizeFirstLetter(langStore.translations.searching) }}
                    </template>

                    <template v-else-if="lookups[index].found">
                        <div class="found-user">
                            <img
                                :src="avatarSrc(lookups[index].user?.photo)"
                                @error="imgFallback"
                                alt="Avatar"
                                class="found-user__avatar"
                            />
                            <div class="found-user__meta">
                                <div>
                                    <b>@{{ lookups[index].user.nickname }}</b>
                                    (ID {{ lookups[index].user.id }})
                                    <span v-if="lookups[index].canInvite" class="found-user__ok"> — {{ langStore.translations.can_invite }}</span>
                                    <span v-else class="error__text"> — {{ langStore.translations.cannot_invite }}</span>
                                </div>
                                <ul v-if="!lookups[index].canInvite && lookups[index].errors?.length" class="error__text" style="margin:4px 0 0 0;padding-left:16px">
                                    <li v-for="(e,i2) in lookups[index].errors" :key="i2">{{ e }}</li>
                                </ul>
                            </div>
                        </div>
                    </template>

                    <template v-else>
                        <p class="error__text">{{ capitalizeFirstLetter(langStore.translations.user_not_found) }}</p>
                    </template>
                </div>
            </div>
            <div class="dialog__plus" style="margin-top: -10px" @click="addUserField">
                <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.1665 7.33317H9.1665V3.33317C9.1665 3.15636 9.09627 2.98679 8.97124 2.86177C8.84622 2.73674 8.67665 2.6665 8.49984 2.6665C8.32303 2.6665 8.15346 2.73674 8.02843 2.86177C7.90341 2.98679 7.83317 3.15636 7.83317 3.33317V7.33317H3.83317C3.65636 7.33317 3.48679 7.40341 3.36177 7.52843C3.23674 7.65346 3.1665 7.82303 3.1665 7.99984C3.1665 8.17665 3.23674 8.34622 3.36177 8.47124C3.48679 8.59627 3.65636 8.6665 3.83317 8.6665H7.83317V12.6665C7.83317 12.8433 7.90341 13.0129 8.02843 13.1379C8.15346 13.2629 8.32303 13.3332 8.49984 13.3332C8.67665 13.3332 8.84622 13.2629 8.97124 13.1379C9.09627 13.0129 9.1665 12.8433 9.1665 12.6665V8.6665H13.1665C13.3433 8.6665 13.5129 8.59627 13.6379 8.47124C13.7629 8.34622 13.8332 8.17665 13.8332 7.99984C13.8332 7.82303 13.7629 7.65346 13.6379 7.52843C13.5129 7.40341 13.3433 7.33317 13.1665 7.33317Z" fill="#E80024"/>
                </svg>
                <p>{{ capitalizeFirstLetter(langStore.translations.addMore) }}</p>
            </div>
            <div class="dialog__btns">
                <button class="main__btn main__btn_white dialog__btn" @click="close">
                    {{ capitalizeFirstLetter(langStore.translations.cansel) }}
                </button>
                <button
                    class="main__btn dialog__btn"
                    :class="{ blocked: inviteBtnDisabled }"
                    :disabled="inviteBtnDisabled"
                    @click="inviteUsers"
                >
                    {{ capitalizeFirstLetter(langStore.translations.invite) }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.found-user { display:flex; align-items:center; gap:8px; }
.found-user__avatar { width:28px; height:28px; border-radius:50%; object-fit:cover; flex:0 0 28px; }
.found-user__ok { color:#1c7430; }
</style>
