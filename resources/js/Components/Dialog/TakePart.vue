<script setup>
import {computed, ref} from "vue";

const props = defineProps({
    modelValue : Boolean,
    hackathonSlug: { type:String, required: true },
    is_join: Boolean
})
const emit = defineEmits([
    'update:modelValue',
    'joined',
    'left'
])

function close(){ emit('update:modelValue',false) }

const agree = ref(false);
const pending = ref(false)
const disabled = computed(() => !agree.value || pending.value)

function toggleAgree () { agree.value = !agree.value }
async function submit () {
    if (disabled.value) return
    pending.value = true

    const routeName = props.is_join ? 'hackathons.leave' : 'hackathons.join'

    try {
        await axios.post(
            route(routeName, { hackathon: props.hackathonSlug })
        )
        routeName === 'hackathons.join' ? emit('joined') : emit('left')
        agree.value = false;
        close()
    } catch (e) {
        console.error(`${props.is_join ? 'leave' : 'join'}-error`, e?.response ?? e)
    } finally {
        pending.value = false
    }
}
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:2">
        <div class="dialog__container dialog__container_small" @click.stop>
            <div class="dialog__header">
                <p>{{ props.is_join ? 'Отменить участие' : 'Принять участие' }}</p>
                <div class="dialog__close" @click="close">✕</div>
            </div>
            <div class="dialog__component" style="margin-top: -10px">
                <p v-if="!props.is_join" class="dialog__title">Правила и условия</p>
                <div v-if="props.is_join" class="dialog__checkbox" style="margin-top: unset">
                    <div>
                        <div @click="toggleAgree" class="custom-checkbox" :class="agree ? 'active' : ''"></div>
                    </div>
                    <p>Я хочу отменить участие в хакатоне</p>
                </div>
                <div v-else class="dialog__checkbox">
                    <div>
                        <div @click="toggleAgree" class="custom-checkbox" :class="agree ? 'active' : ''"></div>
                    </div>
                    <p>Я ознакомлен с Официальными правилами и Условиями предоставления услуг и согласен с ними</p>
                </div>
            </div>
            <div class="dialog__btns">
                <button class="main__btn main__btn_white dialog__btn" @click="close">
                    Отменить
                </button>
                <button
                    class="main__btn dialog__btn"
                    :class="{ blocked: disabled }"
                    :disabled="disabled"
                    @click="submit"
                >
                    {{ pending
                    ? 'Отправляем…'
                    : (props.is_join ? 'Отменить участие' : 'Принять участие') }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
