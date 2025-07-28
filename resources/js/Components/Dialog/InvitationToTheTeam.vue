<script setup>
import IconsCancel from "@/Components/Icons/Cancel.vue";
import {ref, watch} from "vue";
import { useClipboard } from '@vueuse/core'
import {useForm} from "@inertiajs/vue3";

const props = defineProps({
    modelValue : Boolean,
    positions : { type: Array,   default : () => [] },
    ownTeam : { type: Array,   default : () => [] },
    hackathon : { type: Array,   default : () => [] },
})
const emit = defineEmits([
    'update:modelValue',
])

function close(){ emit('update:modelValue',false) }

const inviteLink = ref('')
const { copy, copied } = useClipboard()

const userIds = ref([{ user_id: '', position_id: props.positions[0]?.id }])
const form = useForm({
    users: []
})

async function getLink () {
    inviteLink.value = ''
    try {
        const { data } = await axios.post(
            route('hackathons.teams.create-invite', {
                hackathon: props.hackathon.slug,
                team     : props.ownTeam.id
            })
        )
        inviteLink.value = data.url
    } catch (e) { console.error('link-error', e) }
}

watch(() => props.modelValue, v => { if (v) getLink() })

const addUserField = () => {
    userIds.value.push({ user_id: '', position_id: props.positions[0]?.id }) // Добавляем новое поле
}

const removeUserField = (index) => {
    userIds.value.splice(index, 1) // Удаляем поле
}

const inviteUsers = async () => {
    try {
        await axios.post(
            route('hackathons.teams.invite-by-id', {
                hackathon: props.hackathon.slug,
                team     : props.ownTeam.id,
            }),
            { users: userIds.value }      // тело запроса
        )
        close()                         // закрываем модалку после успеха
    } catch (error) {
        console.error(error)
    }
}
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:2">
        <div class="dialog__container dialog__container_small" @click.stop>
            <div class="dialog__header">
                <p>Пригласить людей в команду</p>
                <div class="dialog__close" @click="close"><svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M7.90994 6.00019L12.2099 1.71019C12.3982 1.52188 12.504 1.26649 12.504 1.00019C12.504 0.733884 12.3982 0.478489 12.2099 0.290185C12.0216 0.101882 11.7662 -0.00390625 11.4999 -0.00390625C11.2336 -0.00390625 10.9782 0.101882 10.7899 0.290185L6.49994 4.59019L2.20994 0.290185C2.02164 0.101882 1.76624 -0.00390625 1.49994 -0.00390625C1.23364 -0.00390625 0.978243 0.101882 0.789939 0.290185C0.601635 0.478489 0.495847 0.733884 0.495847 1.00019C0.495847 1.26649 0.601635 1.52188 0.789939 1.71019L5.08994 6.00019L0.789939 10.2902C0.696211 10.3831 0.621816 10.4937 0.571048 10.6156C0.520279 10.7375 0.494141 10.8682 0.494141 11.0002C0.494141 11.1322 0.520279 11.2629 0.571048 11.3848C0.621816 11.5066 0.696211 11.6172 0.789939 11.7102C0.882902 11.8039 0.993503 11.8783 1.11536 11.9291C1.23722 11.9798 1.36793 12.006 1.49994 12.006C1.63195 12.006 1.76266 11.9798 1.88452 11.9291C2.00638 11.8783 2.11698 11.8039 2.20994 11.7102L6.49994 7.41019L10.7899 11.7102C10.8829 11.8039 10.9935 11.8783 11.1154 11.9291C11.2372 11.9798 11.3679 12.006 11.4999 12.006C11.632 12.006 11.7627 11.9798 11.8845 11.9291C12.0064 11.8783 12.117 11.8039 12.2099 11.7102C12.3037 11.6172 12.3781 11.5066 12.4288 11.3848C12.4796 11.2629 12.5057 11.1322 12.5057 11.0002C12.5057 10.8682 12.4796 10.7375 12.4288 10.6156C12.3781 10.4937 12.3037 10.3831 12.2099 10.2902L7.90994 6.00019Z"
                        fill="#999999"
                    />
                </svg></div>
            </div>
            <p class="dialog__text" style="margin-top: -18px">Ваши товарищи получат приглашение для вступления в команду, которое необходимо принять и подтвердить</p>
            <div class="dialog__component dialog__line">
                <p class="dialog__title">Ссылка для приглашения</p>
                <div class="dialog__input_btns dialog__input_btns_small">
                    <input
                        class="dialog__input"
                        :value="inviteLink"
                        readonly
                        placeholder="Текст ссылки"
                        style="width: 100%"
                    />
                    <button class="main__btn dialog__btn"
                            :class="{ blocked: !inviteLink }"
                            :disabled="!inviteLink"
                            @click="copy(inviteLink)">
                        {{ copied ? 'Скопировано' : 'Копировать' }}
                    </button>
                </div>
            </div>
            <div v-for="(user, index) in userIds" :key="index" class="dialog__component">
                <p class="dialog__title">Добавить участника по ID</p>
                <div class="dialog__input_btns dialog__input_btns_small">
                    <input
                        v-model="user.user_id"
                        class="dialog__input"
                        placeholder="Введите ID участника"
                        style="width: 100%"
                    />
                    <select v-model="user.position_id" class="main__cards_select dialog__select" style="width: 100%; max-width: 230px">
                        <option v-for="p in positions" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                    <div>
                        <IconsCancel class="clickable" style="cursor: pointer" @click="removeUserField(index)"/>
                    </div>
                </div>
            </div>
            <div class="dialog__plus" style="margin-top: -10px" @click="addUserField">
                <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.1665 7.33317H9.1665V3.33317C9.1665 3.15636 9.09627 2.98679 8.97124 2.86177C8.84622 2.73674 8.67665 2.6665 8.49984 2.6665C8.32303 2.6665 8.15346 2.73674 8.02843 2.86177C7.90341 2.98679 7.83317 3.15636 7.83317 3.33317V7.33317H3.83317C3.65636 7.33317 3.48679 7.40341 3.36177 7.52843C3.23674 7.65346 3.1665 7.82303 3.1665 7.99984C3.1665 8.17665 3.23674 8.34622 3.36177 8.47124C3.48679 8.59627 3.65636 8.6665 3.83317 8.6665H7.83317V12.6665C7.83317 12.8433 7.90341 13.0129 8.02843 13.1379C8.15346 13.2629 8.32303 13.3332 8.49984 13.3332C8.67665 13.3332 8.84622 13.2629 8.97124 13.1379C9.09627 13.0129 9.1665 12.8433 9.1665 12.6665V8.6665H13.1665C13.3433 8.6665 13.5129 8.59627 13.6379 8.47124C13.7629 8.34622 13.8332 8.17665 13.8332 7.99984C13.8332 7.82303 13.7629 7.65346 13.6379 7.52843C13.5129 7.40341 13.3433 7.33317 13.1665 7.33317Z" fill="#E80024"/>
                </svg>
                <p>Добавить еще</p>
            </div>
            <div class="dialog__btns">
                <button class="main__btn main__btn_white dialog__btn" @click="close">
                    Отменить
                </button>
                <button
                    class="main__btn dialog__btn"
                    @click="inviteUsers"
                >
                    Пригласить
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
