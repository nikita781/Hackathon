<script setup>
import {computed, onMounted, ref, watch} from "vue";
import IconsCheck from '@/Components/Icons/Check.vue'
import {useLangStore} from "@/store/lang.js";
import {useToast} from "vue-toastification";

const props = defineProps({
    modelValue : Boolean,
    hackathonSlug: { type:String, required: true },
    hackathon: { type: Object, default: null },
    availableProfileTeams: { type: Array, default: () => [] },
    is_join: Boolean
})

const emit = defineEmits([
    'update:modelValue',
    'joined',
    'left'
])

const langStore = useLangStore()
const toast = useToast()

function close(){ emit('update:modelValue',false) }

const agree = ref(false)
const pending = ref(false)
const activeJoinMode = ref('solo')
const selectedTeamId = ref(null)

const availableTeams = computed(() => Array.isArray(props.availableProfileTeams) ? props.availableProfileTeams : [])
const hasTeamJoinTab = computed(() => !props.is_join && props.hackathon?.type === 'team')
const showJoinModeTabs = false
const selectedTeam = computed(() => availableTeams.value.find(team => team.id === selectedTeamId.value) ?? null)

const disabled = computed(() => {
    if (!agree.value || pending.value) return true

    if (props.is_join) return false

    if (hasTeamJoinTab.value && activeJoinMode.value === 'team') {
        return !selectedTeam.value || !selectedTeam.value.can_join_hackathon
    }

    return false
})

function toggleAgree () {
    agree.value = !agree.value
}

function selectJoinMode(mode) {
    activeJoinMode.value = mode
}

function selectTeam(team) {
    if (!team?.can_join_hackathon) return
    selectedTeamId.value = team.id
}

function resetState() {
    agree.value = false
    pending.value = false
    activeJoinMode.value = hasTeamJoinTab.value ? 'team' : 'solo'
    selectedTeamId.value = null
}

watch(() => props.modelValue, (opened) => {
    if (opened) {
        resetState()
    }
})

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

function teamMembersText(count) {
    return `${count} участник${count === 1 ? '' : count < 5 ? 'а' : 'ов'}`
}

const teamRequirementText = computed(() => {
    if (!hasTeamJoinTab.value) return ''
    return `Подходят команды от ${props.hackathon?.min_team_size} до ${props.hackathon?.max_team_size} участников.`
})

const teamAgreementText = computed(() => {
    if (activeJoinMode.value === 'team') {
        return 'Подтверждаю вступление выбранной командой и согласен с правилами участия'
    }

    return capitalizeFirstLetter(langStore.translations.agreeWithRules)
})

async function submit () {
    if (disabled.value) return

    pending.value = true

    const routeName = props.is_join ? 'hackathons.leave' : 'hackathons.join'
    const payload = {}

    if (!props.is_join && hasTeamJoinTab.value && activeJoinMode.value === 'team' && selectedTeam.value) {
        payload.team_id = selectedTeam.value.id
    }

    try {
        const { data } = await axios.post(
            route(routeName, { hackathon: props.hackathonSlug }),
            payload
        )

        if (data?.status) {
            toast.success(data.status, {
                position: 'top-right',
                timeout: 5000,
            })
        }

        if (!props.is_join && data?.joined) {
            emit('joined')
        }

        if (props.is_join && data?.left) {
            emit('left')
        }

        close()
    } catch (e) {
        const firstValidationError = Object.values(e?.response?.data?.errors ?? {})?.[0]
        const message = Array.isArray(firstValidationError)
            ? firstValidationError[0]
            : firstValidationError || e?.response?.data?.error || 'Не удалось выполнить действие.'

        toast.error(message, {
            position: 'top-right',
            timeout: 5000,
        })
    } finally {
        pending.value = false
    }
}

onMounted(async () => {
    await langStore.fetchTranslations()
});
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:2">
        <div class="dialog__container dialog__container_small" @click.stop>
            <div class="dialog__header">
                <p>{{ props.is_join ? capitalizeFirstLetter(langStore.translations.cancelParticipation) : capitalizeFirstLetter(langStore.translations.participate) }}</p>
                <div class="dialog__close" @click="close"><svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M7.90994 6.00019L12.2099 1.71019C12.3982 1.52188 12.504 1.26649 12.504 1.00019C12.504 0.733884 12.3982 0.478489 12.2099 0.290185C12.0216 0.101882 11.7662 -0.00390625 11.4999 -0.00390625C11.2336 -0.00390625 10.9782 0.101882 10.7899 0.290185L6.49994 4.59019L2.20994 0.290185C2.02164 0.101882 1.76624 -0.00390625 1.49994 -0.00390625C1.23364 -0.00390625 0.978243 0.101882 0.789939 0.290185C0.601635 0.478489 0.495847 0.733884 0.495847 1.00019C0.495847 1.26649 0.601635 1.52188 0.789939 1.71019L5.08994 6.00019L0.789939 10.2902C0.696211 10.3831 0.621816 10.4937 0.571048 10.6156C0.520279 10.7375 0.494141 10.8682 0.494141 11.0002C0.494141 11.1322 0.520279 11.2629 0.571048 11.3848C0.621816 11.5066 0.696211 11.6172 0.789939 11.7102C0.882902 11.8039 0.993503 11.8783 1.11536 11.9291C1.23722 11.9798 1.36793 12.006 1.49994 12.006C1.63195 12.006 1.76266 11.9798 1.88452 11.9291C2.00638 11.8783 2.11698 11.8039 2.20994 11.7102L6.49994 7.41019L10.7899 11.7102C10.8829 11.8039 10.9935 11.8783 11.1154 11.9291C11.2372 11.9798 11.3679 12.006 11.4999 12.006C11.632 12.006 11.7627 11.9798 11.8845 11.9291C12.0064 11.8783 12.117 11.8039 12.2099 11.7102C12.3037 11.6172 12.3781 11.5066 12.4288 11.3848C12.4796 11.2629 12.5057 11.1322 12.5057 11.0002C12.5057 10.8682 12.4796 10.7375 12.4288 10.6156C12.3781 10.4937 12.3037 10.3831 12.2099 10.2902L7.90994 6.00019Z"
                        fill="#999999"
                    />
                </svg></div>
            </div>

            <div v-if="showJoinModeTabs && !props.is_join && hasTeamJoinTab" class="dialog__tabs">
                <p
                    class="dialog__tabs_item"
                    :class="{ active: activeJoinMode === 'solo' }"
                    @click="selectJoinMode('solo')"
                >
                    Самостоятельно
                </p>
                <p
                    class="dialog__tabs_item"
                    :class="{ active: activeJoinMode === 'team' }"
                    @click="selectJoinMode('team')"
                >
                    Командой
                </p>
            </div>

            <div v-if="props.is_join" class="dialog__component" style="margin-top: -10px">
                <div class="dialog__checkbox" style="margin-top: unset">
                    <div>
                        <div @click="toggleAgree" class="custom-checkbox" :class="agree ? 'active' : ''">
                            <IconsCheck />
                        </div>
                    </div>
                    <p>{{ capitalizeFirstLetter(langStore.translations.cancelParticipationConfirmation) }}</p>
                </div>
            </div>

            <template v-else-if="activeJoinMode === 'team'">
                <div class="take-part__intro">
                    <p class="dialog__text">Выберите одну из своих готовых команд для вступления в хакатон.</p>
                    <p class="take-part__caption">{{ teamRequirementText }}</p>
                </div>

                <div v-if="availableTeams.length" class="take-part__team-list">
                    <button
                        v-for="team in availableTeams"
                        :key="team.id"
                        type="button"
                        class="take-part__team"
                        :class="{
                            active: selectedTeamId === team.id,
                            disabled: !team.can_join_hackathon,
                        }"
                        :disabled="!team.can_join_hackathon"
                        @click="selectTeam(team)"
                    >
                        <div class="take-part__team-header">
                            <div>
                                <p class="take-part__team-title">{{ team.title }}</p>
                                <p class="take-part__team-meta">{{ teamMembersText(team.members_count) }}</p>
                            </div>
                            <span class="take-part__team-radio"></span>
                        </div>

                        <div class="take-part__team-users">
                            <div
                                v-for="(person, index) in team.users"
                                :key="`${team.id}-${person.user.id}-${index}`"
                                class="take-part__team-user"
                            >
                                <span>@{{ person.user.nickname }}</span>
                                <span>{{ person.position.name }}</span>
                            </div>
                        </div>

                        <ul v-if="team.join_errors?.length" class="take-part__team-errors">
                            <li v-for="(error, index) in team.join_errors" :key="index">
                                {{ error }}
                            </li>
                        </ul>
                    </button>
                </div>

                <div v-else class="take-part__empty">
                    У вас пока нет команд, которыми можно войти в хакатон.
                </div>

                <div class="dialog__component">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.rulesAndConditions) }}</p>
                    <div class="dialog__checkbox" style="margin-top: unset">
                        <div>
                            <div @click="toggleAgree" class="custom-checkbox" :class="agree ? 'active' : ''">
                                <IconsCheck />
                            </div>
                        </div>
                        <p>{{ teamAgreementText }}</p>
                    </div>
                </div>
            </template>

            <div v-else class="dialog__component" style="margin-top: -10px">
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.rulesAndConditions) }}</p>
                <div class="dialog__checkbox">
                    <div>
                        <div @click="toggleAgree" class="custom-checkbox" :class="agree ? 'active' : ''">
                            <IconsCheck />
                        </div>
                    </div>
                    <p>{{ teamAgreementText }}</p>
                </div>
            </div>

            <div class="dialog__btns">
                <button class="main__btn main__btn_white dialog__btn" @click="close">
                    {{ capitalizeFirstLetter(langStore.translations.cansel) }}
                </button>
                <button
                    class="main__btn dialog__btn"
                    :class="{ blocked: disabled }"
                    :disabled="disabled"
                    @click="submit"
                >
                    {{ pending
                    ? capitalizeFirstLetter(langStore.translations.sending)
                    : (props.is_join ? capitalizeFirstLetter(langStore.translations.cancelParticipation) : capitalizeFirstLetter(langStore.translations.participate)) }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.take-part__intro {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: -10px;
}

.take-part__caption {
    color: #7d8695;
    font-size: 14px;
}

.take-part__team-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.take-part__team {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    background: #fff;
    padding: 18px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    text-align: left;
    transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
}

.take-part__team:hover:not(.disabled) {
    border-color: #E80024;
}

.take-part__team.active {
    border-color: #E80024;
}

.take-part__team.disabled {
    background: #f8f9fb;
    color: #6b7280;
    cursor: not-allowed;
}

.take-part__team-header {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: flex-start;
}

.take-part__team-title {
    font-family: 'Cera';
    font-size: 20px;
    line-height: 1.2;
}

.take-part__team-meta {
    margin-top: 6px;
    color: #7d8695;
    font-size: 14px;
}

.take-part__team-radio {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid #d1d5db;
    flex: 0 0 22px;
    position: relative;
}

.take-part__team.active .take-part__team-radio {
    border-color: #E80024;
}

.take-part__team.active .take-part__team-radio::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #E80024;
    transform: translate(-50%, -50%);
}

.take-part__team-users {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.take-part__team-user {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    font-size: 15px;
}

.take-part__team-errors {
    margin: 0;
    padding-left: 18px;
    color: #E80024;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.take-part__empty {
    padding: 18px;
    border-radius: 14px;
    background: #f8f9fb;
    color: #7d8695;
}

@media (max-width: 767.98px) {
    .take-part__team {
        padding: 16px;
    }

    .take-part__team-title {
        font-size: 18px;
    }

    .take-part__team-user {
        flex-direction: column;
        gap: 4px;
    }
}
</style>
