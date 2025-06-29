<script setup>
import EditorField from "@/Components/EditorField.vue";
import {ref} from "vue";
import DropPDFs from "@/Components/DropPDFs.vue";

const resources = ref(null)
const resourcesFiles = ref([])
</script>

<template>
    <div class="dialog__component">
        <p class="dialog__title">Ресурсы</p>
        <EditorField v-model="resources" placeholder="Введите описание"/>
    </div>
    <div class="dialog__component">
        <p class="dialog__title">Файлы</p>
        <DropPDFs v-model:files="resourcesFiles" />
    </div>
</template>

<style scoped>

</style>
