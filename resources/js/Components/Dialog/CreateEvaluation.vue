<script setup>
import { reactive, watch, toRaw } from 'vue'
import IconsCancel from '@/Components/Icons/Cancel.vue'

const props = defineProps({
    modelValue : Boolean,                // v-model
    initial    : { type:Object, default:null } // { title, items:[…] } | null
})
const emit = defineEmits(['update:modelValue','add','update'])

const empty = { title:'', items:[''] }
const form  = reactive({ ...empty })

watch(
    () => props.initial,
    n => Object.assign(form, n ? toRaw(n) : empty),
    { immediate:true }
)

function close(){ emit('update:modelValue',false) }

function addItem(){ form.items.push('') }

function removeItem(idx){ if (form.items.length>1) form.items.splice(idx,1) }

function submit(){
    const payload = {
        title : form.title.trim(),
        items : form.items.map(t=>t.trim()).filter(Boolean)
    }
    emit(props.initial ? 'update' : 'add', payload)
    Object.assign(form, empty)
    close()
}
</script>

<template>
    <div v-if="modelValue"
         class="dialog"
         style="z-index:3">
        <div class="dialog__container dialog__container_small" @click.stop>
            <div class="dialog__header">
                <p>{{ props.initial ? 'Редактировать группу критериев' : 'Добавить группу критериев' }}</p>
                <div class="dialog__close" @click="close">✕</div>
            </div>
            <div class="dialog__component">
                <p class="dialog__title">Категория критериев</p>
                <input v-model="form.title" class="dialog__input" placeholder="Введите название" />
            </div>
            <div class="dialog__title_header">
                <p class="dialog__title">Критерий</p>
                <div class="dialog__plus" @click="addItem">
                    <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M13.1665 7.33317H9.1665V3.33317C9.1665 3.15636 9.09627 2.98679 8.97124 2.86177C8.84622 2.73674 8.67665 2.6665 8.49984 2.6665C8.32303 2.6665 8.15346 2.73674 8.02843 2.86177C7.90341 2.98679 7.83317 3.15636 7.83317 3.33317V7.33317H3.83317C3.65636 7.33317 3.48679 7.40341 3.36177 7.52843C3.23674 7.65346 3.1665 7.82303 3.1665 7.99984C3.1665 8.17665 3.23674 8.34622 3.36177 8.47124C3.48679 8.59627 3.65636 8.6665 3.83317 8.6665H7.83317V12.6665C7.83317 12.8433 7.90341 13.0129 8.02843 13.1379C8.15346 13.2629 8.32303 13.3332 8.49984 13.3332C8.67665 13.3332 8.84622 13.2629 8.97124 13.1379C9.09627 13.0129 9.1665 12.8433 9.1665 12.6665V8.6665H13.1665C13.3433 8.6665 13.5129 8.59627 13.6379 8.47124C13.7629 8.34622 13.8332 8.17665 13.8332 7.99984C13.8332 7.82303 13.7629 7.65346 13.6379 7.52843C13.5129 7.40341 13.3433 7.33317 13.1665 7.33317Z" fill="#E80024"/>
                    </svg>
                    <p>Добавить еще</p>
                </div>
            </div>
            <div class="dialog__component"
                 v-for="(it,idx) in form.items"
                 :key="idx">
                <p class="dialog__title">Название критерия</p>

                <div class="dialog__input_btns">
                    <input v-model="form.items[idx]"
                           class="dialog__input"
                           placeholder="Введите название критерия"  style="width: 100%"/>
                    <IconsCancel class="clickable" style="cursor: pointer" @click="removeItem(idx)" />
                </div>
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
