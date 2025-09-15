<script setup>
import IconsCancel from "@/Components/Icons/Cancel.vue"
import { reactive, toRefs, watch } from "vue"

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    order: { type: String, default: 'dateD' },
    selected: { type: Object, default: () => ({ status: [] }) },
})
const emit = defineEmits(['update:modelValue','apply','reset'])

const state = reactive({
    shown: props.modelValue,
    order: props.order,
    status: Array.isArray(props.selected?.status) ? [...props.selected.status] : [],
})

watch(() => props.modelValue, v => state.shown = v)
watch(() => props.order, v => state.order = v)
watch(() => props.selected, v => state.status = Array.isArray(v?.status) ? [...v.status] : [])

function close(){ emit('update:modelValue', false) }
function toggleStatus(val){
    const i = state.status.indexOf(val)
    if (i === -1) state.status.push(val); else state.status.splice(i,1)
}
function apply(){
    emit('apply', { order: state.order, selected:{ status: state.status } })
    close()
}
function reset(){
    state.order = 'dateD'
    state.status = []
    emit('reset')
    close()
}
</script>

<template>
    <div v-if="state.shown" class="dialog" style="z-index:2" @click.self="close">
        <div class="dialog__container dialog__container_x-small" @click.stop>
            <div class="dialog__header">
                <p>Фильтр</p>
                <div class="dialog__close" @click="close">
                    <svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7.91 6l4.3-4.29A1 1 0 0011.5-0c-.27 0-.53.11-.71.29L6.5 4.59 2.21.29A1 1 0 10.79 1.71L5.09 6 .79 10.29a1 1 0 101.42 1.42L6.5 7.41l4.29 4.3a1 1 0 001.42-1.42L7.91 6z" fill="#999"/>
                    </svg>
                </div>
            </div>

            <div class="dialog__component">
                <p class="main__filter_title">Сортировка</p>
                <select v-model="state.order" class="main__cards_select dialog__select_black dialog__select">
                    <option value="dateD">По дате создания ↓</option>
                    <option value="dateA">По дате создания ↑</option>
                    <option value="titleA">По названию A–Z</option>
                    <option value="titleD">По названию Z–A</option>
                </select>
            </div>

            <div class="dialog__component">
                <div class="main__filter">
                    <div class="main__filter_item">
                        <p class="main__filter_title">Статус</p>

                        <div
                            class="main__filter_input"
                            :class="{ active: state.status.includes('2') }"
                            @click="toggleStatus('2')"
                        >
                            <div class="custom-checkbox"></div>
                            <p>На рассмотрении</p>
                        </div>

                        <div
                            class="main__filter_input"
                            :class="{ active: state.status.includes('3') }"
                            @click="toggleStatus('3')"
                        >
                            <div class="custom-checkbox"></div>
                            <p>Принят</p>
                        </div>

                        <div
                            class="main__filter_input"
                            :class="{ active: state.status.includes('4') }"
                            @click="toggleStatus('4')"
                        >
                            <div class="custom-checkbox"></div>
                            <p>Отклонен</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dialog__btns">
                <button class="main__btn main__btn_white dialog__btn" @click="reset">Сбросить</button>
                <button class="main__btn dialog__btn" @click="apply">Применить</button>
            </div>
        </div>
    </div>
</template>
