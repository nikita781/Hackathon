<script setup>
import {reactive, watch, toRaw, ref, onMounted} from 'vue'
import ConfirmDialog from '@/Components/Dialog/ConfirmDialog.vue'
import {router} from "@inertiajs/vue3";
import {useLangStore} from "@/store/lang.js";

const props = defineProps({
    modelValue   : Boolean,
    hackathonSlug: String,
    initial      : { type:Object, default:null }
})
const emit = defineEmits(['update:modelValue','saved'])

const blank = { id:null, title:'', prize:'', winners:1, prizes:[''] }
const form  = reactive(structuredClone(blank))
const winnersInput    = ref(1)
const pendingWinners  = ref(null)
const showConfirmDlg  = ref(false)

const saving = ref(false)

const errors = reactive({})
function setErrors(obj) {
    Object.keys(errors).forEach(k => delete errors[k])
    if (!obj) return
    for (const [k, v] of Object.entries(obj)) {
        errors[k] = Array.isArray(v) ? (v[0] ?? '') : v
    }
}
function clearError(field) { if (errors[field]) delete errors[field] }
const placeKey = (idx) => `places.${idx}.prize`

function close(){ emit('update:modelValue',false); Object.keys(errors).forEach(k => delete errors[k]) }

watch(
    () => props.initial,
    (value) => {
        if (value) {
            form.id      = value.id
            form.title   = value.title
            form.prize   = value.prize
            form.winners = value.distribution.length
            form.prizes  = value.distribution.map(d => d.prize)
            winnersInput.value = form.winners
        } else {
            Object.assign(form, structuredClone(blank))
            winnersInput.value = 1
        }
        Object.keys(errors).forEach(k => delete errors[k])
    },
    { immediate: true }
)

watch(winnersInput, (val) => {
    if (val === null || val === '' || Number.isNaN(val)) {
        return
    }

    if (val < 1)   { winnersInput.value = 1;   return }
    if (val > 100) { winnersInput.value = 100; return }

    if (val > form.winners) { applyWinners(val); return }

    const willLoseData = form.prizes.slice(val).some(p => p.trim() !== '')
    if (!willLoseData) {
        applyWinners(val)
    } else {
        pendingWinners.value = val
        showConfirmDlg.value = true
    }
})

function applyWinners(n){
    form.winners = n
    if (n > form.prizes.length) {
        while (form.prizes.length < n) form.prizes.push('')
    } else {
        form.prizes.splice(n)
    }
}

function onConfirm(){ applyWinners(pendingWinners.value); resetConfirm() }
function onCancel (){ winnersInput.value = form.winners;  resetConfirm() }
function resetConfirm(){
    showConfirmDlg.value = false
    pendingWinners.value = null
}

const resetForm = () => {
    form.id         = null
    form.title      = ''
    form.prize      = ''
    form.winners    = 1
    form.prizes     = ['']
    winnersInput.value = 1
    Object.keys(errors).forEach(k => delete errors[k])
}

function submit(){
    const payload = {
        title : form.title.trim(),
        prize : form.prize.trim(),
        places: form.prizes.slice(0, form.winners)
            .map((p,i)=>({ place:i+1, prize:p.trim() }))
    }

    Object.keys(errors).forEach(k => delete errors[k])

    router[form.id ? 'patch' : 'post'](
        route(
            form.id
                ? 'hackathons.nominations.update'
                : 'hackathons.nominations.store',
            form.id
                ? { hackathon: props.hackathonSlug, nomination: form.id }
                : { hackathon: props.hackathonSlug }
        ),
        payload,
        {
            preserveScroll: true,
            onStart:   () => { saving.value = true },
            onSuccess: () => { emit('saved'); resetForm(); close() },
            onError:   (err) => setErrors(err),
            onFinish:  () => { saving.value = false }
        }
    )
}

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

const langStore = useLangStore()
onMounted(async () => { await langStore.fetchTranslations() })
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:2">
        <div class="dialog__container_custom dialog__container_small" @click.stop>
            <div class="dialog__inner" :class="{ 'is-saving': saving }">
                <div class="dialog__header">
                    <p>{{ form.id ? 'Изменить номинацию' : capitalizeFirstLetter(langStore.translations.addNomination) }}</p>
                    <div class="dialog__close" @click="close">
                        <svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7.90994 6.00019L12.2099 1.71019C12.3982 1.52188 12.504 1.26649 12.504 1.00019C12.504 0.733884 12.3982 0.478489 12.2099 0.290185C12.0216 0.101882 11.7662 -0.00390625 11.4999 -0.00390625C11.2336 -0.00390625 10.9782 0.101882 10.7899 0.290185L6.49994 4.59019L2.20994 0.290185C2.02164 0.101882 1.76624 -0.00390625 1.49994 -0.00390625C1.23364 -0.00390625 0.978243 0.101882 0.789939 0.290185C0.601635 0.478489 0.495847 0.733884 0.495847 1.00019C0.495847 1.26649 0.601635 1.52188 0.789939 1.71019L5.08994 6.00019L0.789939 10.2902C0.696211 10.3831 0.621816 10.4937 0.571048 10.6156C0.520279 10.7375 0.494141 10.8682 0.494141 11.0002C0.494141 11.1322 0.520279 11.2629 0.571048 11.3848C0.621816 11.5066 0.696211 11.6172 0.789939 11.7102C0.882902 11.8039 0.993503 11.8783 1.11536 11.9291C1.23722 11.9798 1.36793 12.006 1.49994 12.006C1.63195 12.006 1.76266 11.9798 1.88452 11.9291C2.00638 11.8783 2.11698 11.8039 2.20994 11.7102L6.49994 7.41019L10.7899 11.7102C10.8829 11.8039 10.9935 11.8783 11.1154 11.9291C11.2372 11.9798 11.3679 12.006 11.4999 12.006C11.632 12.006 11.7627 11.9798 11.8845 11.9291C12.0064 11.8783 12.117 11.8039 12.2099 11.7102C12.3037 11.6172 12.3781 11.5066 12.4288 11.3848C12.4796 11.2629 12.5057 11.1322 12.5057 11.0002C12.5057 10.8682 12.4796 10.7375 12.4288 10.6156C12.3781 10.4937 12.3037 10.3831 12.2099 10.2902L7.90994 6.00019Z" fill="#999999"/></svg>
                    </div>
                </div>

                <div class="dialog__component">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.nomination) }}</p>
                    <input
                        v-model="form.title"
                        class="dialog__input"
                        :placeholder="capitalizeFirstLetter(langStore.translations.enterNominationTitle)"
                        :class="{ error: !!errors.title }"
                        @input="clearError('title')"
                    >
                    <small v-if="errors.title" class="error__text">{{ errors.title }}</small>
                </div>

                <div class="dialog__component">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.totalPrize) }}</p>
                    <input
                        v-model="form.prize"
                        class="dialog__input"
                        :placeholder="capitalizeFirstLetter(langStore.translations.enterTotalPrize)"
                        :class="{ error: !!errors.prize }"
                        @input="clearError('prize')"
                    >
                    <small v-if="errors.prize" class="error__text">{{ errors.prize }}</small>
                </div>

                <div class="dialog__component">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.winnersCount) }}</p>
                    <input type="number" v-model.number="winnersInput" min="1" step="1" class="dialog__input" style="max-width:100px">
                    <small v-if="errors.places" class="error__text">{{ errors.places }}</small>
                </div>

                <div class="dialog__component" v-for="(p, idx) in form.prizes" :key="idx">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.winnerPrize) }} {{ idx + 1 }}</p>
                    <input
                        v-model="form.prizes[idx]" class="dialog__input"
                        :placeholder="capitalizeFirstLetter(langStore.translations.enterWinnerPrize)"
                        :class="{ error: !!errors[placeKey(idx)] }"
                        @input="clearError(placeKey(idx))"
                    >
                    <small v-if="errors[placeKey(idx)]" class="error__text">{{ errors[placeKey(idx)] }}</small>
                </div>

                <div class="dialog__btns">
                    <button class="main__btn main__btn_white dialog__btn" @click="close">
                        {{ capitalizeFirstLetter(langStore.translations.cansel) }}
                    </button>
                    <button class="main__btn dialog__btn" @click="submit">
                        {{ props.initial ? 'Изменить' : 'Добавить' }}
                    </button>
                </div>

                <ConfirmDialog
                    v-model="showConfirmDlg"
                    text="Некоторые введённые призы будут удалены. Продолжить?"
                    @confirm="onConfirm"
                    @cancel="onCancel"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.dialog { position: fixed; inset: 0; }

/* контейнер как область для абсолютного оверлея */
.dialog__container { position: relative; }

/* блюрим и блокируем ТОЛЬКО контент модалки */
.dialog__inner.is-saving {
    filter: blur(3px);
    user-select: none;
    pointer-events: none;
}

/* оверлей внутри модалки */
.dialog__saving-overlay {
    position: absolute;
    inset: 0;
    z-index: 10;
    display: grid;
    place-items: center;
    background: rgba(255, 255, 255, 0.55);
    backdrop-filter: blur(2px);
    border-radius: inherit;
    pointer-events: all; /* гасим клики */
}

.dialog__saving-spinner {
    width: 150px;  /* как в большом окне — можно поменять */
    height: 150px;
    object-fit: contain;
    pointer-events: none;
}
</style>
