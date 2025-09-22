<script setup>
import IconsPencilMyProject from "@/Components/Icons/PencilMyProject.vue";
import InvitationToTheTeam from "@/Components/Dialog/InvitationToTheTeam.vue";
import EditTeam from "@/Components/Dialog/EditTeam.vue";
import CreateProject from "@/Components/Hackathon/CreateProject.vue";
import {nextTick, onMounted, ref, watch} from "vue";
import {useLangStore} from "@/store/lang.js";

const props = defineProps({
    positions : { type: Array,   default : () => [] },
    ownTeam : { type: Array,   default : () => [] },
    hackathon : { type: Array,   default : () => [] },
    tabs: { type: Array,   default : () => [] },
    allProjects: { type: Array,   default : () => [] },
    can: { type: Array,   default : () => [] },
})

const showEditTeam = ref(false)
const showInvitation = ref(false);
const projects = ref({})

async function fetchProjects() {
    try {
        const response = await axios.get(
            route('hackathons.teams.projects.show-team-projects', {
                hackathon: props.hackathon.slug,
                team: props.ownTeam.id
            }),
            {
                headers: {
                    'Accept': 'application/json'
                }
            }
        );
        projects.value = response.data.projects;
    } catch (error) {
        console.error("Ошибка при получении проектов:", error);
    }
}

const isForm = ref(false);
const previews = ref({});
const oneProject = ref({});

async function getPreview(slugId) {
    try {
        const { data: blob } = await axios.get(
            route('hackathons.projects.image', { hackathon: props.hackathon.slug, project: slugId }),
            { responseType: 'blob' }
        )
        return URL.createObjectURL(blob);
    } catch (e) {
        console.error('hackathon-load', e?.response ?? e);
    }
}

const updateProject = (project) => {
    oneProject.value = project;
    isForm.value = true;
}

watch(() => isForm.value, async () => {
    if (!isForm.value) {
        oneProject.value = {};
        await fetchProjects()
        await loadProjectPreviews();
    }
});

const loadProjectPreviews = async () => {
    const previewsData = {};
    for (const project of projects.value) {
        const previewUrl = await getPreview(project.slug);
        previewsData[project.slug] = previewUrl;
    }
    previews.value = previewsData;
};

onMounted(async () => {
    await fetchProjects();
    await loadProjectPreviews();
});

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

const langStore = useLangStore()

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

onMounted(async () => {
    await langStore.fetchTranslations()
});
</script>

<template>
<div class="hackathon__tab">
    <div class="hackathon__my-project" v-if="!isForm">
        <div class="hackathon__my-project__create">
            <div class="hackathon__my-project__header">
                <p class="hackathon__my-project__title">{{ capitalizeFirstLetter(langStore.translations.myProject) }}</p>
                <button
                    type="button"
                    class="main__btn_main hackathon__btn"
                    @click="isForm = true"
                    v-if="props.can.project.createProject"
                >
                    {{ capitalizeFirstLetter(langStore.translations.create) }}
                </button>
            </div>
<!--            <pre>{{projects}}</pre>-->
            <div class="hackathon__my-project__project">
                <div class="hackathon__my-project__item" v-for="(project,idx) in projects" :key="idx">
                    <div class="hackathon__my-project__item_header">
                        <img :src="previews[project.slug]" v-if="previews[project.slug]" alt="">
                        <div class="skeleton-loader" v-if="!previews[project.slug]"></div>
                        <button
                            type="button"
                            class="main__btn_main hackathon__my-project__team_svg"
                            @click="updateProject(project)"
                            v-if="project.can.update"
                        >
                            <IconsPencilMyProject/>
                        </button>
                    </div>
                    <div class="hackathon__my-project__item_content">
                        <div>
                            <p class="hackathon__my-project__item_title">{{ project.title }}</p>
                            <p class="hackathon__my-project__item_text">{{ project.description }}</p>
                        </div>
                        <ul class="hackathon__my-project__item_avatar" v-if="project?.team?.users">
                            <li v-for="user in project.team.users"><img :src="avatarSrc(user.user.photo)" @error="imgFallback" alt="Avatar"></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="hackathon__my-project__team">
            <div class="hackathon__my-project__header">
                <p class="hackathon__my-project__title">{{ capitalizeFirstLetter(langStore.translations.myTeam) }}</p>
                <button
                    type="button"
                    class="main__btn_main hackathon__my-project__team_svg"
                    @click="showEditTeam = true"
                    v-if="props.can.team.update"
                >
                    <IconsPencilMyProject
                    />
                </button>
                <EditTeam
                    v-model="showEditTeam"
                    :team="props.ownTeam"
                    :positions="props.positions"
                    :hackathon="props.hackathon"
                />
            </div>
<!--            <pre>{{props.ownTeam}}</pre>-->
            <div class="hackathon__my-project__list">
                <div class="hackathon__my-project__list_item" v-for="(person,idx) in props.ownTeam.users" :key="idx">
                    <div class="hackathon__my-project__list_container">
                        <img :src="avatarSrc(person.user.photo)" @error="imgFallback" alt="Avatar">
                        <p class="hackathon__my-project__list_text">{{ person.user.nickname }}</p>
                    </div>
                    <p class="hackathon__my-project__list_text">{{ person.position.name }}</p>
                </div>
            </div>
            <button
                type="button"
                class="main__btn_main hackathon__btn"
                @click="showInvitation = true"
                v-if="props.can.team.invite"
            >
                {{ capitalizeFirstLetter(langStore.translations.invite_to_team) }}
            </button>
            <InvitationToTheTeam
                v-model="showInvitation"
                :positions="props.positions"
                :ownTeam="props.ownTeam"
                :hackathon="props.hackathon"
            />
        </div>
    </div>
    <CreateProject
        v-else
        v-model="isForm"
        :hackathonSlug="props.hackathon.slug"
        :teamId="props.ownTeam.id"
        :oneProject="oneProject ?? ''"
    />
</div>
</template>

<style scoped>
.skeleton-loader {
    width: 100%;
    height: 200px;
    background-color: #e0e0e0;
    animation: skeleton 1.2s ease-in-out infinite;
    border-radius: 4px;
}

@keyframes skeleton {
    0% {
        background-color: #e0e0e0;
    }
    50% {
        background-color: #f0f0f0;
    }
    100% {
        background-color: #e0e0e0;
    }
}
</style>
