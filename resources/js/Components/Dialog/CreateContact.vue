<script setup>
import { reactive, watch, toRaw } from 'vue'

const props = defineProps({
    modelValue : Boolean,
    initial    : { type:Object, default:null }
})
const emit = defineEmits(['update:modelValue','add','update'])

const empty = { title:'', value:'' }
const form  = reactive({ ...empty })

watch(
    () => props.initial,
    n => Object.assign(form, n ? toRaw(n) : empty),
    { immediate:true }
)

function close(){ emit('update:modelValue',false) }

function submit(){
    const payload = { title:form.title.trim(), value:form.value.trim() }
    emit(props.initial ? 'update' : 'add', payload)
    Object.assign(form, empty)
    close()
}
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:3">
        <div class="dialog__container dialog__container_small" @click.stop>
            <div class="dialog__header">
                <p>{{ props.initial ? 'Изменить контакт' : 'Добавить контакт' }}</p>
                <div class="dialog__close" @click="close">✕</div>
            </div>

            <div class="dialog__component">
                <p class="dialog__title">Название</p>
                <input v-model="form.title" class="dialog__input" placeholder="Введите название контакта" />
            </div>

            <div class="dialog__component">
                <p class="dialog__title">Контактная информация</p>
                <input v-model="form.value" class="dialog__input"
                       placeholder="Введите номер телефона или ссылку" />
            </div>

            <div class="dialog__btns">
                <button class="main__btn main__btn_white dialog__btn" @click="close">
                    Отменить
                </button>
                <button class="main__btn dialog__btn" @click="submit">
                    {{ props.initial ? 'Изменить' : 'Добавить' }}
                </button>
            </div>
        </div>
    </div>
</template>
