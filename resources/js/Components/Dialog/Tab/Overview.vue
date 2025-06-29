<script setup>
import EditorField from '@/Components/EditorField.vue'
import {ref} from "vue";
import DialogCreateNomination from "@/Components/Dialog/CreateNomination.vue";
import IconsCup from "@/Components/Icons/Cup.vue";
import IconsPencil from "@/Components/Icons/Pencil.vue";
import IconsCancel from "@/Components/Icons/Cancel.vue";
import DropFiles from "@/Components/DropFiles.vue";

const showDialog = ref(false)
const editingIndex = ref(null)

const description = ref(null)
const descriptionPlan = ref(null)
const nominations = ref([])

const openAdd  = () => { editingIndex.value = null; showDialog.value = true }
const openEdit = (idx) => { editingIndex.value = idx; showDialog.value = true }

const addNomination = (n) => nominations.value.push(n)
const updateNomination = (n) => {
    if (editingIndex.value !== null) nominations.value[editingIndex.value] = n
    editingIndex.value = null
}
const removeNomination = (idx) => nominations.value.splice(idx,1)

const partnerLogos = ref([])
</script>

<template>
    <div class="dialog__component">
        <p class="dialog__title">Время на выполнения заданий</p>
        <div class="dialog__horizontal">
            <div class="dialog__info" style="width: 100%">
                <p class="dialog__title">От</p>
                <input type="date" class="dialog__input" placeholder="Кол-во" style="width: 100%">
            </div>
            <div class="dialog__info" style="width: 100%">
                <p class="dialog__title">До</p>
                <input type="date" class="dialog__input" placeholder="Кол-во" style="width: 100%">
            </div>
        </div>
    </div>
    <div class="dialog__component">
        <p class="dialog__title">Описание</p>
        <EditorField v-model="description" placeholder="Введите описание"/>
    </div>
    <div class="dialog__component">
        <p class="dialog__title">План проведения</p>
        <EditorField v-model="descriptionPlan" placeholder="Введите план проведения"/>
    </div>
    <div class="dialog__prize">
        <div class="dialog__title_header">
            <p class="dialog__title">Призовой фонд</p>
            <div class="dialog__plus" @click="openAdd">
                <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.1665 7.33317H9.1665V3.33317C9.1665 3.15636 9.09627 2.98679 8.97124 2.86177C8.84622 2.73674 8.67665 2.6665 8.49984 2.6665C8.32303 2.6665 8.15346 2.73674 8.02843 2.86177C7.90341 2.98679 7.83317 3.15636 7.83317 3.33317V7.33317H3.83317C3.65636 7.33317 3.48679 7.40341 3.36177 7.52843C3.23674 7.65346 3.1665 7.82303 3.1665 7.99984C3.1665 8.17665 3.23674 8.34622 3.36177 8.47124C3.48679 8.59627 3.65636 8.6665 3.83317 8.6665H7.83317V12.6665C7.83317 12.8433 7.90341 13.0129 8.02843 13.1379C8.15346 13.2629 8.32303 13.3332 8.49984 13.3332C8.67665 13.3332 8.84622 13.2629 8.97124 13.1379C9.09627 13.0129 9.1665 12.8433 9.1665 12.6665V8.6665H13.1665C13.3433 8.6665 13.5129 8.59627 13.6379 8.47124C13.7629 8.34622 13.8332 8.17665 13.8332 7.99984C13.8332 7.82303 13.7629 7.65346 13.6379 7.52843C13.5129 7.40341 13.3433 7.33317 13.1665 7.33317Z" fill="#E80024"/>
                </svg>
                <p>Добавить еще</p>
            </div>
        </div>
        <div class="dialog__prize_container" v-if="nominations.length">
            <div
                v-for="(n, idx) in nominations"
                :key="idx"
                class="dialog__prize_item"
            >
                <IconsCup/>
                <div class="dialog__prize_content">
                    <div class="dialog__prize_header">
                        <p class="dialog__prize_title">{{ n.title }}</p>
                        <div class="dialog__prize_btns">
                            <IconsPencil style="padding: 5px" class="clickable" @click="openEdit(idx)" />
                            <IconsCancel class="clickable" @click="removeNomination(idx)" />
                        </div>
                    </div>

                    <p class="dialog__prize_text">
                        {{ n.totalPrize || 'Без указания суммы' }}
                    </p>
                    <p class="dialog__prize_number">
                        Количество победителей: {{ n.winners }}
                    </p>
                </div>
            </div>
        </div>
    </div>
    <DialogCreateNomination
        v-model="showDialog"
        :initial="editingIndex !== null ? nominations[editingIndex] : null"
        @add="addNomination"
        @update="updateNomination"
    />
    <div class="dialog__component">
        <p class="dialog__title">Партнеры</p>
        <DropFiles v-model:files="partnerLogos" />
    </div>
</template>

<style scoped>

</style>
