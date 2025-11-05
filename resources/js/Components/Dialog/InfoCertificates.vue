<script setup>
const props = defineProps({
    modelValue : Boolean,
})
const emit = defineEmits(['update:modelValue'])

const close = () => emit('update:modelValue', false)
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:5">
        <div class="dialog__container dialog__container_small" style="gap: 10px" @click.stop>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Blanditiis corporis cum dolore dolorem, doloribus earum eligendi esse ex excepturi explicabo labore maxime, non pariatur, sequi sint sunt ullam! Nesciunt, nisi.</p>
            <p>Тест тест</p>
            <p>Тест тест</p>
            <ul>
                <li><p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Blanditiis corporis cum dolore dolorem, doloribus earum eligendi esse ex excepturi explicabo labore maxime, non pariatur, sequi sint sunt ullam! Nesciunt, nisi.</p></li>
            </ul>
            <ol>
                <li><p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Blanditiis corporis cum dolore dolorem, doloribus earum eligendi esse ex excepturi explicabo labore maxime, non pariatur, sequi sint sunt ullam! Nesciunt, nisi.</p></li>
            </ol>

            <div class="dialog__btns" style="justify-content:center">
                <button class="main__btn main__btn_white dialog__btn" @click="close">
                    Назад
                </button>
            </div>
        </div>
    </div>
</template>

<style lang="scss">
    li {
        margin-left: 20px;
    }
</style>
