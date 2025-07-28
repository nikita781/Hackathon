<script setup>
import {reactive, watch, toRaw, ref, onMounted} from 'vue'
import ConfirmDialog from '@/Components/Dialog/ConfirmDialog.vue'
import {router} from "@inertiajs/vue3";

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

function close(){ emit('update:modelValue',false) }

watch(
    () => props.initial,
    (value) => {
        if (value) {
            // заполняем поля для редактирования
            form.id      = value.id
            form.title   = value.title
            form.prize   = value.prize
            form.winners = value.distribution.length
            form.prizes  = value.distribution.map(d => d.prize)

            winnersInput.value = form.winners
        } else {
            // очищаем форму для «Создать»
            Object.assign(form, structuredClone(blank))
            winnersInput.value = 1
        }
    },
    { immediate: true }
)

watch(winnersInput, (val) => {
    /* границы */
    if (val < 1)   { winnersInput.value = 1;   return }
    if (val > 100) { winnersInput.value = 100; return }

    /* если увеличиваем — просто добавляем пустые поля */
    if (val > form.winners) { applyWinners(val); return }

    /* уменьшение: проверяем, потеряем ли введённые призы */
    const willLoseData = form.prizes.slice(val)
        .some(p => p.trim() !== '')
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
    form.prize = ''
    form.winners    = 1
    form.prizes     = ['']
    winnersInput.value = 1
}

async function submit(){
    const payload = {
        title : form.title.trim(),
        prize : form.prize.trim(),
        places: form.prizes.slice(0, form.winners)
            .map((p,i)=>({ place:i+1, prize:p.trim() }))
    }

    try{
        if (form.id){                                         /* UPDATE */
            await router.patch(
                route('hackathons.nominations.update',
                    { hackathon: props.hackathonSlug, nomination: form.id }),
                payload,
                { preserveScroll:true }
            )
        } else {                                              /* CREATE */
            await router.post(
                route('hackathons.nominations.store',
                    { hackathon: props.hackathonSlug }),
                payload,
                { preserveScroll:true }
            )
        }
        emit('saved')
        resetForm()
        close()
    } catch (err){
        console.error('nomination-save-error', err?.response ?? err)
    }
}

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

const translations = ref({})

const fetchTranslations = async (lang = 'ru') => {
    try {
        const response = await axios.get(`http://127.0.0.1:8000/lang/${lang}.json`)
        translations.value = response.data
    } catch (error) {
        console.error('Ошибка загрузки переводов:', error)
    }
}

onMounted(async () => {
    await fetchTranslations('ru')
});
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:2">
        <div class="dialog__container dialog__container_small" @click.stop>
            <div class="dialog__header">
                <p>{{ form.id ? capitalizeFirstLetter(translations.updateNomination) : capitalizeFirstLetter(translations.addNomination) }}</p>
                <div class="dialog__close" @click="close"><svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M7.90994 6.00019L12.2099 1.71019C12.3982 1.52188 12.504 1.26649 12.504 1.00019C12.504 0.733884 12.3982 0.478489 12.2099 0.290185C12.0216 0.101882 11.7662 -0.00390625 11.4999 -0.00390625C11.2336 -0.00390625 10.9782 0.101882 10.7899 0.290185L6.49994 4.59019L2.20994 0.290185C2.02164 0.101882 1.76624 -0.00390625 1.49994 -0.00390625C1.23364 -0.00390625 0.978243 0.101882 0.789939 0.290185C0.601635 0.478489 0.495847 0.733884 0.495847 1.00019C0.495847 1.26649 0.601635 1.52188 0.789939 1.71019L5.08994 6.00019L0.789939 10.2902C0.696211 10.3831 0.621816 10.4937 0.571048 10.6156C0.520279 10.7375 0.494141 10.8682 0.494141 11.0002C0.494141 11.1322 0.520279 11.2629 0.571048 11.3848C0.621816 11.5066 0.696211 11.6172 0.789939 11.7102C0.882902 11.8039 0.993503 11.8783 1.11536 11.9291C1.23722 11.9798 1.36793 12.006 1.49994 12.006C1.63195 12.006 1.76266 11.9798 1.88452 11.9291C2.00638 11.8783 2.11698 11.8039 2.20994 11.7102L6.49994 7.41019L10.7899 11.7102C10.8829 11.8039 10.9935 11.8783 11.1154 11.9291C11.2372 11.9798 11.3679 12.006 11.4999 12.006C11.632 12.006 11.7627 11.9798 11.8845 11.9291C12.0064 11.8783 12.117 11.8039 12.2099 11.7102C12.3037 11.6172 12.3781 11.5066 12.4288 11.3848C12.4796 11.2629 12.5057 11.1322 12.5057 11.0002C12.5057 10.8682 12.4796 10.7375 12.4288 10.6156C12.3781 10.4937 12.3037 10.3831 12.2099 10.2902L7.90994 6.00019Z"
                        fill="#999999"
                    />
                </svg></div>
            </div>
            <div class="dialog__component">
                <p class="dialog__title">{{ capitalizeFirstLetter(translations.nomination) }}</p>
                <input v-model="form.title" class="dialog__input" :placeholder="capitalizeFirstLetter(translations.enterNominationTitle)">
            </div>
            <div class="dialog__component">
                <p class="dialog__title">{{ capitalizeFirstLetter(translations.totalPrize) }}</p>
                <input v-model="form.prize" class="dialog__input" :placeholder="capitalizeFirstLetter(translations.enterTotalPrize)">
            </div>
            <div class="dialog__component">
                <p class="dialog__title">{{ capitalizeFirstLetter(translations.winnersCount) }}</p>
                <input  type="number"
                        v-model.number="winnersInput"
                        min="1" step="1"
                        class="dialog__input"
                        style="max-width:100px">
            </div>
            <div class="dialog__component" v-for="(p, idx) in form.prizes" :key="idx">
                <p class="dialog__title">{{ capitalizeFirstLetter(translations.winnerPrize) }} {{ idx + 1 }}</p>
                <input v-model="form.prizes[idx]"
                       class="dialog__input"
                       :placeholder="capitalizeFirstLetter(translations.enterWinnerPrize)">
            </div>
            <div class="dialog__btns">
                <button class="main__btn main__btn_white dialog__btn" @click="close">
                    {{ capitalizeFirstLetter(translations.cansel) }}
                </button>
                <button class="main__btn dialog__btn" @click="submit">
                    {{ props.initial ? 'Изменить' : 'Добавить' }}
                </button>
            </div>
            <ConfirmDialog v-model="showConfirmDlg"
                           text="Некоторые введённые призы будут удалены. Продолжить?"
                           @confirm="onConfirm"
                           @cancel="onCancel"/>
        </div>
    </div>
</template>

<style scoped>

</style>
