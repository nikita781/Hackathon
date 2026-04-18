<script setup>
import {onBeforeUnmount, onMounted, ref, watch} from "vue";
import {useLangStore} from "@/store/lang.js";
import {useToast} from "vue-toastification";

const props = defineProps({
    modelValue: Boolean,
})

const emit = defineEmits([
    'update:modelValue',
    'created',
])

const langStore = useLangStore()
const toast = useToast()
const previousBodyOverflow = ref('')
const pending = ref(false)
const title = ref('')
const error = ref('')

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

function close() {
    emit('update:modelValue', false)
}

function resetState() {
    title.value = ''
    error.value = ''
    pending.value = false
}

async function createTeam() {
    if (pending.value) return

    pending.value = true
    error.value = ''

    try {
        const { data } = await axios.post(route('profile.teams.store'), {
            title: title.value,
        })

        emit('created', data.team)
        toast.success('Команда создана', {
            position: 'top-right',
            timeout: 5000,
        })
        close()
        resetState()
    } catch (e) {
        error.value = e?.response?.data?.errors?.title?.[0] || 'Не удалось создать команду.'
    } finally {
        pending.value = false
    }
}

watch(
    () => props.modelValue,
    (opened) => {
        if (opened) {
            previousBodyOverflow.value = document.body.style.overflow
            document.body.style.overflow = 'hidden'
            error.value = ''
        } else {
            document.body.style.overflow = previousBodyOverflow.value || ''
            resetState()
        }
    }
)

onMounted(async () => {
    await langStore.fetchTranslations()
})

onBeforeUnmount(() => {
    document.body.style.overflow = previousBodyOverflow.value || ''
})
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index: 5">
        <div class="dialog__container dialog__container_small" @click.stop>
            <div class="dialog__header">
                <p>Создать команду</p>
                <div class="dialog__close" @click="close">
                    <svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M7.90994 6.00019L12.2099 1.71019C12.3982 1.52188 12.504 1.26649 12.504 1.00019C12.504 0.733884 12.3982 0.478489 12.2099 0.290185C12.0216 0.101882 11.7662 -0.00390625 11.4999 -0.00390625C11.2336 -0.00390625 10.9782 0.101882 10.7899 0.290185L6.49994 4.59019L2.20994 0.290185C2.02164 0.101882 1.76624 -0.00390625 1.49994 -0.00390625C1.23364 -0.00390625 0.978243 0.101882 0.789939 0.290185C0.601635 0.478489 0.495847 0.733884 0.495847 1.00019C0.495847 1.26649 0.601635 1.52188 0.789939 1.71019L5.08994 6.00019L0.789939 10.2902C0.696211 10.3831 0.621816 10.4937 0.571048 10.6156C0.520279 10.7375 0.494141 10.8682 0.494141 11.0002C0.494141 11.1322 0.520279 11.2629 0.571048 11.3848C0.621816 11.5066 0.696211 11.6172 0.789939 11.7102C0.882902 11.8039 0.993503 11.8783 1.11536 11.9291C1.23722 11.9798 1.36793 12.006 1.49994 12.006C1.63195 12.006 1.76266 11.9798 1.88452 11.9291C2.00638 11.8783 2.11698 11.8039 2.20994 11.7102L6.49994 7.41019L10.7899 11.7102C10.8829 11.8039 10.9935 11.8783 11.1154 11.9291C11.2372 11.9798 11.3679 12.006 11.4999 12.006C11.632 12.006 11.7627 11.9798 11.8845 11.9291C12.0064 11.8783 12.117 11.8039 12.2099 11.7102C12.3037 11.6172 12.3781 11.5066 12.4288 11.3848C12.4796 11.2629 12.5057 11.1322 12.5057 11.0002C12.5057 10.8682 12.4796 10.7375 12.4288 10.6156C12.3781 10.4937 12.3037 10.3831 12.2099 10.2902L7.90994 6.00019Z"
                            fill="#999999"
                        />
                    </svg>
                </div>
            </div>

            <div class="dialog__component">
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.team) }}</p>
                <input
                    v-model="title"
                    class="dialog__input"
                    :placeholder="capitalizeFirstLetter(langStore.translations.enter_title)"
                    maxlength="255"
                    @keyup.enter="createTeam"
                />
                <p v-if="error" class="error__text" style="margin-top: 8px">{{ error }}</p>
            </div>

            <div class="dialog__btns">
                <button class="main__btn main__btn_white dialog__btn" @click="close">
                    {{ capitalizeFirstLetter(langStore.translations.cansel) }}
                </button>
                <button
                    class="main__btn dialog__btn"
                    :class="{ blocked: pending }"
                    :disabled="pending"
                    @click="createTeam"
                >
                    {{ pending ? capitalizeFirstLetter(langStore.translations.saving) : 'Создать команду' }}
                </button>
            </div>
        </div>
    </div>
</template>
