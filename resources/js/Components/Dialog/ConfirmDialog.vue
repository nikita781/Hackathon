<script setup>
import {onMounted, ref} from "vue";
import {useLangStore} from "@/store/lang.js";

const props = defineProps({
    modelValue : Boolean,
    text       : { type:String, default:'Вы уверены?' }
})
const emit = defineEmits(['update:modelValue','confirm','cancel'])

const close = () => emit('update:modelValue', false)
const doConfirm = () => { emit('confirm'); close() }
const doCancel  = () => { emit('cancel');  close() }

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

const langStore = useLangStore()

onMounted(async () => {
    await langStore.fetchTranslations()
});
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:5">
        <div class="dialog__container dialog__container_small" @click.stop>
            <p style="font-size:20px;margin-bottom:20px">{{ text }}</p>
            <div class="dialog__btns" style="justify-content:center">
                <button class="main__btn main__btn_white dialog__btn" @click="doCancel">
                    {{ capitalizeFirstLetter(langStore.translations.cansel) }}
                </button>
                <button class="main__btn dialog__btn" @click="doConfirm">
                    {{ capitalizeFirstLetter(langStore.translations.confirm) }}
                </button>
            </div>
        </div>
    </div>
</template>
