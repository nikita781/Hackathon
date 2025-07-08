<script setup>
import { reactive, watch, toRaw, ref } from 'vue'
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
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:2">
        <div class="dialog__container dialog__container_small" @click.stop>
            <div class="dialog__header">
                <p>{{ form.id ? 'Редактировать номинацию' : 'Добавить номинацию' }}</p>
                <div class="dialog__close" @click="close">✕</div>
            </div>
            <div class="dialog__component">
                <p class="dialog__title">Номинация</p>
                <input v-model="form.title" class="dialog__input" placeholder="Введите название номинации">
            </div>
            <div class="dialog__component">
                <p class="dialog__title">Общая сумма или приз</p>
                <input v-model="form.prize" class="dialog__input" placeholder="Введите сумму или приз">
            </div>
            <div class="dialog__component">
                <p class="dialog__title">Количество победителей</p>
                <input  type="number"
                        v-model.number="winnersInput"
                        min="1" step="1"
                        class="dialog__input"
                        style="max-width:100px">
            </div>
            <div class="dialog__component" v-for="(p, idx) in form.prizes" :key="idx">
                <p class="dialog__title">Приз победителя {{ idx + 1 }}</p>
                <input v-model="form.prizes[idx]"
                       class="dialog__input"
                       :placeholder="`Введите сумму или название приза`">
            </div>
            <div class="dialog__btns">
                <button class="main__btn main__btn_white dialog__btn" @click="close">
                    Отменить
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
