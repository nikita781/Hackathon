<script setup>
import {ref, watch, computed, onMounted, onBeforeUnmount} from 'vue'
import axios from 'axios'
import ConfirmDialog from '@/Components/Dialog/ConfirmDialog.vue'
import IconsCancel from '@/Components/Icons/Cancel.vue'
import { useToast } from 'vue-toastification'
import {useLangStore} from "@/store/lang.js";
import CustomSelect from '@/Components/CustomSelect.vue'

const toast = useToast()

const props = defineProps({
    modelValue: Boolean,
    managers  : { type: Array,  default: () => [] },
    hackathon : { type: Object, required: true },
})

const emit = defineEmits([
    'update:modelValue',
    'update:managers',
    'removed',
])

const previousBodyOverflow = ref('')

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

function close () { emit('update:modelValue', false) }

const rolesResp = ref({ roles: [] })
async function getRoles () {
    try {
        const { data } = await axios.get(route('roles'))
        rolesResp.value = data ?? { roles: [] }
    } catch (e) { console.error('roles-error', e) }
}

const showConfirmDialog = ref(false)
const userToRemove = ref(null)

const list = ref([])
watch(
    () => props.managers,
    v => {
        // локальная копия менеджеров (без мутации props)
        list.value = Array.isArray(v) ? v.map(x => ({ ...x })) : []
    },
    { immediate: true, deep: true }
)

function confirmRemoveUser(id) {
    userToRemove.value = id
    showConfirmDialog.value = true
}
function closeConfirmDialog() {
    showConfirmDialog.value = false
    userToRemove.value = null
}

async function removeUser () {
    if (!userToRemove.value) return
    const url = route('hackathons.staff.kick', { hackathon: props.hackathon.slug })
    const staffIds = [ Number(userToRemove.value) ]
    try {
        await axios.post(url, { staff: staffIds }, { headers: { Accept: 'application/json' } })
        list.value = list.value.filter(u => !staffIds.includes(Number(u.id)))
        emit('update:managers', list.value)
        emit('removed', staffIds)
        closeConfirmDialog()
        toast.success(capitalizeFirstLetter(langStore.translations.user_removed_from_staff))
    } catch (err) {
        console.error('Ошибка при удалении пользователя', err?.response?.data || err)
        toast.error(capitalizeFirstLetter(langStore.translations.user_remove_failed))
    }
}

watch(() => props.modelValue, async (v) => { if (v) await getRoles() })

/* ---------- Сохранение ролей (POST + _method: 'patch') ---------- */
const pendingSave = ref(false)
const disabledSave = computed(() =>
    pendingSave.value ||
    list.value.length === 0 ||
    list.value.some(u => !u?.hackathon_role?.id)
)

async function save () {
    if (disabledSave.value) return
    pendingSave.value = true

    // backend ждёт: { staff: [ { user_id, role_id }, ... ] }
    const payload = {
        _method: 'patch',
        staff: list.value.map(u => ({
            user_id: Number(u.id),
            role_id: Number(u?.hackathon_role?.id),
        })),
    }

    const url = route('hackathons.staff.update', { hackathon: props.hackathon.slug })

    try {
        await axios.post(url, payload, { headers: { Accept: 'application/json' } })
        emit('update:managers', list.value)
        toast.success(capitalizeFirstLetter(langStore.translations.staff_updated))
        close()
    } catch (err) {
        console.error(capitalizeFirstLetter((langStore.translations.save_failed)), err?.response?.data || err)
        toast.error(capitalizeFirstLetter((langStore.translations.save_failed)))
    } finally {
        pendingSave.value = false
    }
}

const langStore = useLangStore()

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

onMounted(async () => {
    await langStore.fetchTranslations()
});

const PLACEHOLDER = '/profile.jpg';

function avatarSrc(photo) {
    if (!photo) return PLACEHOLDER;
    const url = String(photo).trim();

    const hasFileName = /[^/]+\.[a-z0-9]+(?:\?.*)?$/i.test(url);
    if (!hasFileName) return PLACEHOLDER;

    return url;
}

function imgFallback(e) {
    e.target.onerror = null;
    e.target.src = PLACEHOLDER;
}

const roleOptions = computed(() =>
    (rolesResp.value.roles ?? []).map(r => ({
        value: r.id,
        label: r.title ?? `Роль #${r.id}`,
    }))
)
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:2">
        <div class="dialog__container dialog__container_small" @click.stop>
            <div class="dialog__header">
                <p>{{ capitalizeFirstLetter(langStore.translations.editManagers) }}</p>
                <div class="dialog__close" @click="close">
                    <svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7.90994 6.00019L12.2099 1.71019C12.3982 1.52188 12.504 1.26649 12.504 1.00019C12.504 0.733884 12.3982 0.478489 12.2099 0.290185C12.0216 0.101882 11.7662 -0.00390625 11.4999 -0.00390625C11.2336 -0.00390625 10.9782 0.101882 10.7899 0.290185L6.49994 4.59019L2.20994 0.290185C2.02164 0.101882 1.76624 -0.00390625 1.49994 -0.00390625C1.23364 -0.00390625 0.978243 0.101882 0.789939 0.290185C0.601635 0.478489 0.495847 0.733884 0.495847 1.00019C0.495847 1.26649 0.601635 1.52188 0.789939 1.71019L5.08994 6.00019L0.789939 10.2902C0.696211 10.3831 0.621816 10.4937 0.571048 10.6156C0.520279 10.7375 0.494141 10.8682 0.494141 11.0002C0.494141 11.1322 0.520279 11.2629 0.571048 11.3848C0.621816 11.5066 0.696211 11.6172 0.789939 11.7102C0.882902 11.8039 0.993503 11.8783 1.11536 11.9291C1.23722 11.9798 1.36793 12.006 1.49994 12.006C1.63195 12.006 1.76266 11.9798 1.88452 11.9291C2.00638 11.8783 2.11698 11.8039 2.20994 11.7102L6.49994 7.41019L10.7899 11.7102C10.8829 11.8039 10.9935 11.8783 11.1154 11.9291C11.2372 11.9798 11.3679 12.006 11.4999 12.006C11.632 12.006 11.7627 11.9798 11.8845 11.9291C12.0064 11.8783 12.117 11.8039 12.2099 11.7102C12.3037 11.6172 12.3781 11.5066 12.4288 11.3848C12.4796 11.2629 12.5057 11.1322 12.5057 11.0002C12.5057 10.8682 12.4796 10.7375 12.4288 10.6156C12.3781 10.4937 12.3037 10.3831 12.2099 10.2902L7.90994 6.00019Z" fill="#999999"/>
                    </svg>
                </div>
            </div>

            <div
                class="dialog__input_btns dialog__input_btns_small dialog__input_btns-phone"
                v-for="(person, idx) in list"
                :key="person.id ?? idx"
            >
                <div class="hackathon__my-project__list_container" style="width: 100%">
                    <img :src="avatarSrc(person.photo)" @error="imgFallback" alt="Avatar">
                    <p class="hackathon__my-project__list_text">{{ person.nickname }}</p>
                </div>

                <div class="dialog__input_reset">
                    <CustomSelect
                        v-model="person.hackathon_role.id"
                        :options="roleOptions"
                        full-width
                        close-by-scroll
                    />

                    <div>
                        <IconsCancel class="clickable" style="cursor: pointer" @click="confirmRemoveUser(person.id)" />
                    </div>
                </div>
            </div>

            <ConfirmDialog
                :modelValue="showConfirmDialog"
                :text="capitalizeFirstLetter(langStore.translations.confirm_user_delete)"
                @confirm="removeUser"
                @cancel="closeConfirmDialog"
            />

            <div class="dialog__btns">
                <button class="main__btn main__btn_white dialog__btn" @click="close">{{ capitalizeFirstLetter(langStore.translations.cansel) }}</button>
                <button
                    class="main__btn dialog__btn"
                    :disabled="disabledSave"
                    :class="{ blocked: disabledSave }"
                    @click="save"
                >
                    {{ pendingSave ? capitalizeFirstLetter(langStore.translations.saving) : capitalizeFirstLetter(langStore.translations.save) }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* при необходимости */
</style>
