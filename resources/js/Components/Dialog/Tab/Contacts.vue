<script setup>
import { ref } from 'vue'
import DialogContact from '@/Components/Dialog/CreateContact.vue'

import IconsCancel from "@/Components/Icons/Cancel.vue";
import IconsPencil from "@/Components/Icons/Pencil.vue";

const contacts = ref([
    { title: 'Ментор',       value: '+7 (919) 999-99-99' },
    { title: 'Telegram-чат', value: 'Ссылка на тг чат' },
])

const socials = ref([
    { title: 'Telegram-канал', value: 'Ссылка на тг канал' },
])

const dlgShown   = ref(false)
const dlgList    = ref('contacts')
const editingIdx = ref(null)

function openAdd(listName){
    dlgList.value   = listName
    editingIdx.value = null
    dlgShown.value   = true
}

function openEdit(listName, idx){
    dlgList.value    = listName
    editingIdx.value = idx
    dlgShown.value   = true
}

function addItem(item){
    (dlgList.value === 'contacts' ? contacts : socials).value.push(item)
}
function updateItem(item){
    const arr = dlgList.value === 'contacts' ? contacts : socials
    if (editingIdx.value !== null) arr.value[editingIdx.value] = item
    editingIdx.value = null
}
function removeItem(listName, idx){
    (listName === 'contacts' ? contacts : socials).value.splice(idx,1)
}
</script>

<template>
    <div class="dialog__prize">
        <div class="dialog__title_header">
            <p class="dialog__title">Контакт</p>
            <div class="dialog__plus" @click="openAdd('contacts')">
                <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.1665 7.33317H9.1665V3.33317C9.1665 3.15636 9.09627 2.98679 8.97124 2.86177C8.84622 2.73674 8.67665 2.6665 8.49984 2.6665C8.32303 2.6665 8.15346 2.73674 8.02843 2.86177C7.90341 2.98679 7.83317 3.15636 7.83317 3.33317V7.33317H3.83317C3.65636 7.33317 3.48679 7.40341 3.36177 7.52843C3.23674 7.65346 3.1665 7.82303 3.1665 7.99984C3.1665 8.17665 3.23674 8.34622 3.36177 8.47124C3.48679 8.59627 3.65636 8.6665 3.83317 8.6665H7.83317V12.6665C7.83317 12.8433 7.90341 13.0129 8.02843 13.1379C8.15346 13.2629 8.32303 13.3332 8.49984 13.3332C8.67665 13.3332 8.84622 13.2629 8.97124 13.1379C9.09627 13.0129 9.1665 12.8433 9.1665 12.6665V8.6665H13.1665C13.3433 8.6665 13.5129 8.59627 13.6379 8.47124C13.7629 8.34622 13.8332 8.17665 13.8332 7.99984C13.8332 7.82303 13.7629 7.65346 13.6379 7.52843C13.5129 7.40341 13.3433 7.33317 13.1665 7.33317Z" fill="#E80024"/>
                </svg>
                <p>Добавить еще</p>
            </div>
        </div>
        <div v-for="(c,idx) in contacts" :key="idx" class="dialog__component">
            <p class="dialog__title">{{ c.title }}</p>

            <div class="dialog__input_btns">
                <input v-model="c.value" class="dialog__input" readonly style="width: 100%"/>

                <div class="dialog__prize_btns">
                    <IconsPencil style="padding: 5px" class="clickable" @click="openEdit('contacts',idx)" />
                    <IconsCancel class="clickable" @click="removeItem('contacts',idx)" />
                </div>
            </div>
        </div>
    </div>
    <div class="dialog__prize">
        <div class="dialog__title_header">
            <p class="dialog__title">Ссылки на социальные сети</p>
            <div class="dialog__plus" @click="openAdd('socials')">
                <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.1665 7.33317H9.1665V3.33317C9.1665 3.15636 9.09627 2.98679 8.97124 2.86177C8.84622 2.73674 8.67665 2.6665 8.49984 2.6665C8.32303 2.6665 8.15346 2.73674 8.02843 2.86177C7.90341 2.98679 7.83317 3.15636 7.83317 3.33317V7.33317H3.83317C3.65636 7.33317 3.48679 7.40341 3.36177 7.52843C3.23674 7.65346 3.1665 7.82303 3.1665 7.99984C3.1665 8.17665 3.23674 8.34622 3.36177 8.47124C3.48679 8.59627 3.65636 8.6665 3.83317 8.6665H7.83317V12.6665C7.83317 12.8433 7.90341 13.0129 8.02843 13.1379C8.15346 13.2629 8.32303 13.3332 8.49984 13.3332C8.67665 13.3332 8.84622 13.2629 8.97124 13.1379C9.09627 13.0129 9.1665 12.8433 9.1665 12.6665V8.6665H13.1665C13.3433 8.6665 13.5129 8.59627 13.6379 8.47124C13.7629 8.34622 13.8332 8.17665 13.8332 7.99984C13.8332 7.82303 13.7629 7.65346 13.6379 7.52843C13.5129 7.40341 13.3433 7.33317 13.1665 7.33317Z" fill="#E80024"/>
                </svg>
                <p>Добавить еще</p>
            </div>
        </div>
        <div v-for="(s,idx) in socials" :key="idx" class="dialog__component">
            <p class="dialog__title">{{ s.title }}</p>

            <div class="dialog__input_btns">
                <input v-model="s.value" class="dialog__input" readonly style="width: 100%"/>

                <div class="dialog__prize_btns">
                    <IconsPencil style="padding: 5px" class="clickable" @click="openEdit('socials',idx)" />
                    <IconsCancel class="clickable" @click="removeItem('socials',idx)" />
                </div>
            </div>
        </div>
    </div>
    <DialogContact
        v-model="dlgShown"
        :initial="editingIdx!==null
                ? (dlgList==='contacts' ? contacts[editingIdx] : socials[editingIdx])
                : null"
        @add="addItem"
        @update="updateItem"
    />
</template>

<style scoped>

</style>
