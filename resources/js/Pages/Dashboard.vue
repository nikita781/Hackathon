<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmDialog from "@/Components/Dialog/ConfirmDialog.vue";
import CreateProfileTeam from "@/Components/Dialog/CreateProfileTeam.vue";
import EditTeam from "@/Components/Dialog/EditTeam.vue";
import InvitationToTheTeam from "@/Components/Dialog/InvitationToTheTeam.vue";
import {Head, usePage} from '@inertiajs/vue3';
import {computed, nextTick, onMounted, ref} from "vue";
import {useToast} from "vue-toastification";
import {useLangStore} from "@/store/lang.js";
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
    user: Object,
    awards: Object,
    projects: Object,
    positions: {
        type: Array,
        default: () => [],
    },
    createdTeams: {
        type: Array,
        default: () => [],
    },
    memberTeams: {
        type: Array,
        default: () => [],
    },
    auth : { type:Object, required:true },
    notifications : { type:Object, required:true },
})

const langStore = useLangStore()
const toast = useToast()

function normalizeCollection(collection) {
    if (Array.isArray(collection)) return collection
    if (Array.isArray(collection?.data)) return collection.data
    return []
}

function cloneTeams(list) {
    return JSON.parse(JSON.stringify(normalizeCollection(list)))
}

const createdTeamsState = ref(cloneTeams(props.createdTeams))
const memberTeamsState = ref(cloneTeams(props.memberTeams))

const showCreateTeam = ref(false)
const showEditTeam = ref(false)
const showInvitation = ref(false)
const showDeleteTeam = ref(false)
const showLeaveTeam = ref(false)
const selectedTeam = ref(null)
const teamToDelete = ref(null)
const teamToLeave = ref(null)
const deletingTeam = ref(false)
const leavingTeam = ref(false)

const activeTab = ref(usePage().props.query?.tab === 'past' ? 1 : 0)

function setActiveTab(idx) {
    if (activeTab.value === idx) return
    activeTab.value = idx
}

const tabBodies = [
    'awards',
    'certificates',
]

const currentTabBody = computed(() => tabBodies[activeTab.value])

const tabsRef = ref([]);
const sliderStyle = computed(() => {
    if (!tabsRef.value.length) return {};

    const activeTabElement = tabsRef.value[activeTab.value];
    const left = activeTabElement?.offsetLeft || 0;
    const width = activeTabElement?.offsetWidth || 0;

    return {
        left: `${left}px`,
        width: `${width}px`,
    };
});

const isOwnProfile = computed(() => props.auth?.user?.id === props.user?.id)

const allTeams = computed(() => ([
    ...createdTeamsState.value.map(team => ({ team, profileRole: 'captain' })),
    ...memberTeamsState.value.map(team => ({ team, profileRole: 'member' })),
]))

const hasTeams = computed(() => allTeams.value.length > 0)

const deleteTeamText = computed(() => {
    if (!teamToDelete.value?.team?.title) return 'Удалить команду?'
    return `Удалить команду «${teamToDelete.value.team.title}»?`
})

const leaveTeamText = computed(() => {
    if (!teamToLeave.value?.team?.title) return 'Покинуть команду?'
    return `Покинуть команду «${teamToLeave.value.team.title}»?`
})

onMounted(async () => {
    await langStore.fetchTranslations()
    await nextTick(() => {
        tabsRef.value = document.querySelectorAll('.my-hackathon__tabs_item');
    });

    const phone = document.getElementById('phone');
    if (!phone) return

    phone.addEventListener('input', e => {
        let digits = e.target.value.replace(/\D/g, '');

        if (digits[0] === '8') digits = '7' + digits.slice(1);
        if (digits[0] !== '7') digits = '7' + digits;

        const parts = [
            digits.slice(0, 1),
            digits.slice(1, 4),
            digits.slice(4, 7),
            digits.slice(7, 9),
            digits.slice(9, 11)
        ];

        let formatted = '+';
        if (parts[0]) formatted += parts[0];
        if (parts[1]) formatted += ' (' + parts[1];
        if (parts[1].length === 3) formatted += ') ';
        if (parts[2]) formatted += parts[2];
        if (parts[3]) formatted += '-' + parts[3];
        if (parts[4]) formatted += '-' + parts[4];

        e.target.value = formatted;
    });
});

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

const PLACEHOLDER = '/profile.jpg';

function avatarSrc(photo) {
    if (!photo) return PLACEHOLDER;
    const url = String(photo).trim();

    const hasFileName = /[^/]+\.[a-z0-9]+(?:\?.*)?$/i.test(url);
    if (!hasFileName) return PLACEHOLDER;

    return url;
}

function imgFallback(e) {
    e.target.onerror = null;
    e.target.src = PLACEHOLDER;
}

function previewSrc(project) {
    const hackSlug = project?.hackathon?.slug
    if (hackSlug && typeof route === "function") {
        try {
            return route("hackathons.image", { hackathon: hackSlug })
        } catch (_) {}
    }
    return "/project.jpg"
}

function resolveTeamRole(team) {
    const currentUserId = props.user?.id
    const membership = (team?.users ?? []).find(item => item?.user?.id === currentUserId)
    if (membership?.position?.id === 1) return 'Капитан'
    return 'Участник'
}

function ownerLabel(team) {
    if (!team?.owner?.nickname) return ''
    return `Создатель: @${team.owner.nickname}`
}

function openEditTeam(team) {
    selectedTeam.value = team
    showEditTeam.value = true
}

function openInvitation(team) {
    selectedTeam.value = team
    showInvitation.value = true
}

function openDeleteTeam(team) {
    teamToDelete.value = team
    showDeleteTeam.value = true
}

function openLeaveTeam(team) {
    teamToLeave.value = team
    showLeaveTeam.value = true
}

function handleTeamCreated(team) {
    createdTeamsState.value.unshift(team)
}

async function deleteTeam() {
    if (!teamToDelete.value?.team?.id || deletingTeam.value) return

    deletingTeam.value = true

    try {
        const targetId = teamToDelete.value.team.id

        await axios.delete(route('profile.teams.destroy', { team: targetId }))
        createdTeamsState.value = createdTeamsState.value.filter(team => team.id !== targetId)
        memberTeamsState.value = memberTeamsState.value.filter(team => team.id !== targetId)
        toast.success('Команда удалена', {
            position: 'top-right',
            timeout: 5000,
        })
    } catch (e) {
        toast.error('Не удалось удалить команду.', {
            position: 'top-right',
            timeout: 5000,
        })
    } finally {
        deletingTeam.value = false
        teamToDelete.value = null
    }
}

async function leaveTeam() {
    if (!teamToLeave.value?.team?.id || leavingTeam.value) return

    leavingTeam.value = true

    try {
        const targetId = teamToLeave.value.team.id

        await axios.delete(route('profile.teams.leave', { team: targetId }))
        memberTeamsState.value = memberTeamsState.value.filter(team => team.id !== targetId)
        toast.success('Вы вышли из команды', {
            position: 'top-right',
            timeout: 5000,
        })
    } catch (e) {
        toast.error('Не удалось покинуть команду.', {
            position: 'top-right',
            timeout: 5000,
        })
    } finally {
        leavingTeam.value = false
        teamToLeave.value = null
    }
}

const dateOnly = (s) => (s ? String(s).slice(0, 10) : '')
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout
        :auth="props.auth"
        :notifications="props.notifications"
    >
        <div class="profile">
            <div class="profile__header">
                <h2 class="profile__nickname">{{ props.user?.nickname }}</h2>
                <a
                    v-if="isOwnProfile"
                    type="button"
                    class="main__btn_main hackathon__btn"
                    style="max-width: unset"
                    href="https://foncode.ru/cabinet/profile"
                    target="_blank"
                >
                    {{ capitalizeFirstLetter(langStore.translations.editProfile) }}
                </a>
            </div>
            <div class="profile__role">
                <p v-for="role in props.user?.roles">{{ role?.title }}</p>
                <p>ID{{ props.user?.id }}</p>
            </div>
            <div class="profile__content">
                <div class="profile__content_form">
                    <div class="profile__content_row">
                        <div class="dialog__component">
                            <p class="dialog__title" style="text-transform: uppercase">{{ capitalizeFirstLetter(langStore.translations.fullName) }}</p>
                            <input type="text" readonly :value="props.user.name" class="dialog__input">
                        </div>
                        <div class="dialog__component">
                            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.birthDate) }}</p>
                            <input type="date" readonly :value="dateOnly(props.user.birthday)" class="dialog__input">
                        </div>
                    </div>
                    <div class="profile__content_row">
                        <div class="dialog__component">
                            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.email) }}</p>
                            <input type="email" readonly :value="props.user.email" class="dialog__input">
                        </div>
                        <div class="dialog__component">
                            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.phoneNumber) }}</p>
                            <input :value="props.user.phone_number" readonly class="dialog__input" id="phone" type="tel" placeholder="+7 (___) ___‑__‑__" maxlength="18" autocomplete="tel">
                        </div>
                    </div>
                </div>
                <div class="profile__content_image">
                    <div>
                        <img :src="avatarSrc(props.user.photo)" @error="imgFallback" alt="Profile" />
                    </div>
                </div>
            </div>

            <div class="profile__teams">
                <div class="profile__teams_header">
                    <div>
                        <p class="hackathon__my-project__title">Команды</p>
                        <p class="profile__teams_subtitle">Все команды, где пользователь состоит или является капитаном.</p>
                    </div>
                    <button
                        v-if="isOwnProfile"
                        type="button"
                        class="main__btn_main hackathon__btn"
                        style="max-width: unset"
                        @click="showCreateTeam = true"
                    >
                        Создать команду
                    </button>
                </div>

                <div v-if="hasTeams" class="profile__teams_grid">
                    <div
                        v-for="entry in allTeams"
                        :key="entry.team.id"
                        class="hackathon__participants_item profile__teams_item"
                    >
                        <div class="profile__teams_item-header">
                            <div class="profile__teams_meta">
                                <p class="hackathon__participants_title">{{ entry.team.title }}</p>
                                <div class="profile__teams_badges">
                                    <span class="profile__teams_badge">
                                        {{ resolveTeamRole(entry.team) }}
                                    </span>
                                    <span class="profile__teams_hint" v-if="entry.profileRole === 'member' && entry.team.owner?.nickname">
                                        {{ ownerLabel(entry.team) }}
                                    </span>
                                </div>
                            </div>
                            <div
                                v-if="isOwnProfile && (entry.team.can?.update_profile || entry.team.can?.invite_profile || entry.team.can?.delete_profile || entry.team.can?.leave_profile)"
                                class="profile__teams_actions"
                            >
                                <button
                                    v-if="entry.team.can?.update_profile"
                                    type="button"
                                    class="main__btn_main"
                                    style="width: fit-content; max-width: unset"
                                    @click="openEditTeam(entry.team)"
                                >
                                    {{ capitalizeFirstLetter(langStore.translations.edit) }}
                                </button>
                                <button
                                    v-if="entry.team.can?.invite_profile"
                                    type="button"
                                    class="main__btn_main"
                                    style="width: fit-content; max-width: unset"
                                    @click="openInvitation(entry.team)"
                                >
                                    {{ capitalizeFirstLetter(langStore.translations.invite) }}
                                </button>
                                <button
                                    v-if="entry.team.can?.delete_profile"
                                    type="button"
                                    class="main__btn main__btn_white"
                                    style="width: fit-content"
                                    @click="openDeleteTeam(entry)"
                                >
                                    Удалить
                                </button>
                                <button
                                    v-if="entry.team.can?.leave_profile"
                                    type="button"
                                    class="main__btn main__btn_white"
                                    style="width: fit-content"
                                    @click="openLeaveTeam(entry)"
                                >
                                    Покинуть
                                </button>
                            </div>
                        </div>

                        <div class="hackathon__my-project__list">
                            <div
                                v-for="(person, idx) in entry.team.users"
                                :key="`${entry.team.id}-${person.user.id}-${idx}`"
                                class="hackathon__my-project__list_item"
                            >
                                <div class="hackathon__my-project__list_container">
                                    <img :src="avatarSrc(person.user.photo)" @error="imgFallback" alt="Avatar">
                                    <p class="hackathon__my-project__list_text">{{ person.user.nickname }}</p>
                                </div>
                                <p class="hackathon__my-project__list_text">{{ person.position.name }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="profile__teams_empty">
                    Пока команд нет.
                </div>
            </div>
        </div>

        <CreateProfileTeam
            v-model="showCreateTeam"
            @created="handleTeamCreated"
        />
        <EditTeam
            v-if="selectedTeam"
            v-model="showEditTeam"
            :team="selectedTeam"
            :positions="normalizeCollection(props.positions)"
        />
        <InvitationToTheTeam
            v-if="selectedTeam"
            v-model="showInvitation"
            :positions="normalizeCollection(props.positions)"
            :ownTeam="selectedTeam"
        />
        <ConfirmDialog
            v-model="showDeleteTeam"
            :text="deleteTeamText"
            @confirm="deleteTeam"
            @cancel="teamToDelete = null"
        />
        <ConfirmDialog
            v-model="showLeaveTeam"
            :text="leaveTeamText"
            @confirm="leaveTeam"
            @cancel="teamToLeave = null"
        />

        <div class="my-hackathon__tabs_cont">
            <div class="my-hackathon__tabs">
                <p :class="['my-hackathon__tabs_item',{active:activeTab===0}]" @click="setActiveTab(0)">
                    {{ capitalizeFirstLetter(langStore.translations.myAwards) }}
                </p>
                <p :class="['my-hackathon__tabs_item',{active:activeTab===1}]" @click="setActiveTab(1)">
                    {{ capitalizeFirstLetter(langStore.translations.certificates) }}
                </p>
                <div
                    v-if="tabsRef.length"
                    class="slider"
                    :style="sliderStyle"
                ></div>
            </div>
            <div class="profile__tabs">
                <div v-if="currentTabBody === 'awards'" class="profile__tabs_awards">
                    <div v-for="(award, index) in props.awards" :key="index" class="profile__tabs_awards_item">
                        <img :src="award.image || '/default-award.jpg'" alt="Prize">
                        <div class="profile__tabs_awards_item_content">
                            <div class="profile__tabs_awards_item_header">
                                <p class="profile__tabs_awards_item_title">{{ award.title }}</p>
                                <p class="profile__tabs_awards_item_text">{{ award.description }}</p>
                            </div>
                            <p class="profile__tabs_awards_item_date">
                                {{ capitalizeFirstLetter(langStore.translations.received) }} {{ new Date(award.awarded_at).toLocaleDateString("ru-RU") }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-else>
                    <div class="hackathon__gallery_container">
                        <div
                            v-for="project in props.projects.data"
                            :key="project.slug || project.id"
                            class="hackathon__my-project__item"
                            style="cursor: pointer"
                        >
                            <div class="hackathon__my-project__item_header">
                                <img :src="previewSrc(project)" alt="">
                                <div
                                    v-if="Number(project?.place) > 0"
                                    class="hackathon__gallery_place"
                                    :class="Number(project.place) < 4 ? 'first' : 'second'"
                                >
                                    {{ project.place }}
                                </div>
                            </div>

                            <div class="hackathon__my-project__item_content">
                                <div>
                                    <p
                                        class="hackathon__my-project__item_title"
                                        :title="project.title"
                                    >
                                        {{ project.title }}
                                    </p>
                                    <p
                                        class="hackathon__my-project__item_text"
                                        :title="project.description"
                                    >
                                        {{ project.description }}
                                    </p>
                                </div>

                                <ul class="hackathon__my-project__item_avatar" v-if="project?.team?.users">
                                    <li v-for="user in project.team.users"><img :src="avatarSrc(user.user.photo)" @error="imgFallback" alt="Avatar"></li>
                                </ul>

                                <a :href="project.certificate_url" class="main__btn_main" style="width: fit-content; margin-top: -10px">{{ capitalizeFirstLetter(langStore.translations.certificate) }}</a>
                            </div>
                        </div>
                    </div>
                    <Pagination
                        style="margin-top: 20px;"
                        v-if="props.projects?.meta?.links?.length"
                        :links="props.projects.meta.links"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style lang="scss">

</style>
