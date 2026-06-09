<script setup>
import {computed, reactive, watch} from "vue"
import CustomSelect from "@/Components/CustomSelect.vue";

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    order: { type: String, default: "dateD" },
    selected: { type: Object, default: () => ({ types: [] }) },
})
const emit = defineEmits(["update:modelValue","apply","reset"])

const state = reactive({
    shown: props.modelValue,
    order: props.order,
    types: Array.isArray(props.selected?.types) ? [...props.selected.types] : [],
})

watch(() => props.modelValue, v => state.shown = v)
watch(() => props.order, v => state.order = v)
watch(() => props.selected, v => state.types = Array.isArray(v?.types) ? [...v.types] : [])

function close(){ emit("update:modelValue", false) }
function toggleType(val){
    const i = state.types.indexOf(val)
    if (i === -1) state.types.push(val); else state.types.splice(i,1)
}
function apply(){
    emit("apply", { order: state.order, selected:{ types: state.types } })
    close()
}
function reset(){
    state.order = "dateD"
    state.types = []
    emit("reset")
    close()
}

const sortOptions = computed(() => [
    {
        value: 'dateD',
        label: `По дате создания ↓`,
    },
    {
        value: 'dateA',
        label: `По дате создания ↑`,
    },
])
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
                <CustomSelect
                    v-model="state.order"
                    :options="sortOptions"
                    full-width
                    close-by-scroll
                />
            </div>

            <div class="dialog__btns">
                <button class="main__btn main__btn_white dialog__btn" @click="reset">Сбросить</button>
                <button class="main__btn dialog__btn" @click="apply">Применить</button>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
