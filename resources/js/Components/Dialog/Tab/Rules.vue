<script setup>
import EditorField from "@/Components/EditorField.vue";
import {ref} from "vue";
import DropPDFs from "@/Components/DropPDFs.vue";

const rules = ref(null)
const rulesFiles = ref([])
</script>

<template>
    <div class="dialog__component">
        <p class="dialog__title">Правила</p>
        <EditorField v-model="rules" placeholder="Введите описание"/>
    </div>
    <div class="dialog__component">
        <p class="dialog__title">Файлы</p>
        <DropPDFs v-model:files="rulesFiles" />
    </div>
</template>

<style scoped>

</style>
