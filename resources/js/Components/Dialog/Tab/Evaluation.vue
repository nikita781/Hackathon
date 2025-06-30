<script setup>
import { ref } from 'vue'
import CreateEvaluation from '@/Components/Dialog/CreateEvaluation.vue'
import IconsCancel from "@/Components/Icons/Cancel.vue";
import IconsPencil from "@/Components/Icons/Pencil.vue";

const groups = ref([])

const dlgShown   = ref(false)
const editingIdx = ref(null)

function openAdd ()           { editingIdx.value=null; dlgShown.value=true }
function openEdit(idx)        { editingIdx.value=idx; dlgShown.value=true }
function removeGroup(idx)     { groups.value.splice(idx,1) }

function addGroup(g){ groups.value.push(g) }
function updateGroup(g){ if(editingIdx.value!==null) groups.value[editingIdx.value]=g }
</script>

<template>
    <div class="dialog__component">
        <p class="dialog__title">Время проверки</p>
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
    <div class="dialog__prize">
        <div class="dialog__title_header">
            <p class="dialog__title">Критерии оценки</p>
            <div class="dialog__plus" @click="openAdd">
                <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.1665 7.33317H9.1665V3.33317C9.1665 3.15636 9.09627 2.98679 8.97124 2.86177C8.84622 2.73674 8.67665 2.6665 8.49984 2.6665C8.32303 2.6665 8.15346 2.73674 8.02843 2.86177C7.90341 2.98679 7.83317 3.15636 7.83317 3.33317V7.33317H3.83317C3.65636 7.33317 3.48679 7.40341 3.36177 7.52843C3.23674 7.65346 3.1665 7.82303 3.1665 7.99984C3.1665 8.17665 3.23674 8.34622 3.36177 8.47124C3.48679 8.59627 3.65636 8.6665 3.83317 8.6665H7.83317V12.6665C7.83317 12.8433 7.90341 13.0129 8.02843 13.1379C8.15346 13.2629 8.32303 13.3332 8.49984 13.3332C8.67665 13.3332 8.84622 13.2629 8.97124 13.1379C9.09627 13.0129 9.1665 12.8433 9.1665 12.6665V8.6665H13.1665C13.3433 8.6665 13.5129 8.59627 13.6379 8.47124C13.7629 8.34622 13.8332 8.17665 13.8332 7.99984C13.8332 7.82303 13.7629 7.65346 13.6379 7.52843C13.5129 7.40341 13.3433 7.33317 13.1665 7.33317Z" fill="#E80024"/>
                </svg>
                <p>Добавить еще</p>
            </div>
        </div>
        <div class="dialog__prize" v-for="(grp,idx) in groups" :key="idx">
            <div class="dialog__eva_container">
                <p class="dialog__eva">{{ grp.title }}</p>
                <div class="dialog__prize_btns">
                    <IconsPencil style="padding: 5px" class="clickable" @click="openEdit(idx)" />
                    <IconsCancel class="clickable" @click="removeGroup(idx)" />
                </div>
            </div>

            <div class="dialog__eva_item" v-for="(it,i) in grp.items" :key="i">
                <p class="dialog__eva_title">{{ it }}</p>
                <div class="dialog__eva_number">
                    <p v-for="n in 10" :key="n">{{ n }}</p>
                </div>
            </div>
        </div>
    </div>
    <CreateEvaluation
        v-if="dlgShown"
        v-model="dlgShown"
        :initial="editingIdx!==null ? groups[editingIdx] : null"
        @add="addGroup"
        @update="updateGroup"
    />
</template>

<style scoped>

</style>
