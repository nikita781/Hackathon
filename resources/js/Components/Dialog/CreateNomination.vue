<script setup>
import { reactive, watch, toRaw, ref } from 'vue'
import ConfirmDialog from '@/Components/Dialog/ConfirmDialog.vue'

const props = defineProps({
    modelValue : Boolean,
    initial    : { type: Object, default: null }
})
const emit = defineEmits(['update:modelValue', 'add', 'update'])

const empty = { title:'', totalPrize:'', winners:1, prizes:[''] }
const form  = reactive(structuredClone(empty))

const winnersInput  = ref(1)      // «то, что ввёл пользователь» (бинд к <input>)
const pendingWinners = ref(null)  // новое значение, дожидающееся подтверждения
const showConfirm   = ref(false)  // модалка «Подтвердить обрезание?»

watch(
    () => props.initial,
    (n) => {
        const src = n ? toRaw(n) : empty
        Object.assign(form, structuredClone(src))
        winnersInput.value = form.winners
        },
    { immediate:true }
)

watch(winnersInput, (val) => {
    /* границы ввода */
    if (val < 1) { winnersInput.value = 1; return }
    if (val > 100){ winnersInput.value = 100;return }

    /* увеличение – применяем сразу */
    if (val > form.winners) {
        applyWinners(val)
        return
    }

    /* уменьшение: проверяем, не потеряем ли данные */
    const lostData = form.prizes.slice(val).some(p => p.trim() !== '')
    if (!lostData) {
        applyWinners(val)             // ничего важного не теряем
    } else {
        pendingWinners.value = val    // ждём подтверждения
        showConfirm.value  = true
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

function onConfirm(){
    applyWinners(pendingWinners.value)
    showConfirm.value   = false
    pendingWinners.value= null
}
function onCancel(){
    winnersInput.value = form.winners
    showConfirm.value  = false
    pendingWinners.value = null
}

const resetForm = () => {
    form.title      = ''
    form.totalPrize = ''
    form.winners    = 1
    form.prizes     = ['']
}

const close = () => emit('update:modelValue', false)

function submit(){
    const payload = {
        title      : form.title.trim(),
        totalPrize : form.totalPrize.trim(),
        winners    : form.winners,
        prizes     : form.prizes.map(p=>p.trim())
    }
    emit(props.initial ? 'update' : 'add', payload)
    resetForm(); close()
}
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:2">
        <div class="dialog__container dialog__container_small" @click.stop>
            <div class="dialog__header">
                <p>Добавить номинацию</p>
                <div class="dialog__close" @click="close">✕</div>
            </div>
            <div class="dialog__component">
                <p class="dialog__title">Номинация</p>
                <input v-model="form.title" class="dialog__input" placeholder="Введите название номинации">
            </div>
            <div class="dialog__component">
                <p class="dialog__title">Общая сумма или приз</p>
                <input v-model="form.totalPrize" class="dialog__input" placeholder="Введите сумму или приз">
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
            <ConfirmDialog v-model="showConfirm"
                           text="Некоторые введённые призы будут удалены. Продолжить?"
                           @confirm="onConfirm"
                           @cancel="onCancel"/>
        </div>
    </div>
</template>

<style scoped>

</style>
