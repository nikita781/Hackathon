<script setup>
import { reactive, watch, onBeforeUnmount, toRaw } from 'vue'
import DropFile from "@/Components/DropFile.vue";
import {ref} from "vue";

const props = defineProps({
    modelValue : Boolean,
    initial    : { type:Object, default:null }
})
const emit = defineEmits(['update:modelValue','add','update'])

const empty = { title:'', text:'', imageUrl:'', file:null }
const form  = reactive({ ...empty })

function revoke() {
    if (form.imageUrl && form.imageUrl.startsWith('blob:'))
        URL.revokeObjectURL(form.imageUrl)
}

watch(
    () => props.initial,
    n => {
        revoke()
        Object.assign(form, n ? toRaw(n) : empty)
    },
    { immediate:true }
)
function close(){
    emit('update:modelValue', false)
    revoke()
}

function handleFile(file){
    if(!file) return
    if(!file.type.startsWith('image/')) return
    revoke()
    form.file = file
    form.imageUrl = URL.createObjectURL(file)
}

function submit(){
    const payload = {
        title    : form.title.trim(),
        text     : form.text.trim(),
        file     : form.file,
        imageUrl : form.imageUrl
    }
    emit(props.initial ? 'update' : 'add', payload)

    Object.assign(form, empty)
    close()
}

onBeforeUnmount(revoke)
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:3" @click.self="close">
        <div class="dialog__container dialog__container_small" @click.stop>
            <div class="dialog__header">
                <p>{{ props.initial ? 'Редактировать награду' : 'Добавить награду' }}</p>
                <div class="dialog__close" @click="close">✕</div>
            </div>
            <div class="dialog__component">
                <p class="dialog__title">Название награды</p>
                <input v-model="form.title" class="dialog__input" placeholder="Введите название"/>
            </div>
            <div class="dialog__component">
                <p class="dialog__title">Описание награды</p>
                <textarea v-model="form.text" class="dialog__textarea" placeholder="Введите описание"/>
            </div>
            <div class="dialog__component">
                <p class="dialog__title">Изображение</p>
                <DropFile :file="form.file" @update:file="handleFile"/>
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

<style scoped>

</style>
