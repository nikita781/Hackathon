<script setup>
import IconsPencilMyProject from "@/Components/Icons/PencilMyProject.vue";
import InvitationToTheTeam from "@/Components/Dialog/InvitationToTheTeam.vue";
import EditTeam from "@/Components/Dialog/EditTeam.vue";
import CreateProject from "@/Components/Hackathon/CreateProject.vue";
import {computed, nextTick, onMounted, ref, watch} from "vue";
import {useLangStore} from "@/store/lang.js";
import Hyperlink from "@/Components/Icons/Hyperlink.vue";
import Document from "@/Components/Icons/Document.vue";

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
const localTeamManagementEnabled = false
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
const oneProject = ref(null);
const isOne = computed(() => !!oneProject.value);
const oneGallery = ref([]);
const galleryLoading = ref(false);
const groupCriteries = ref([]);
const presentationUrl = ref('');
const getTitle = (p) => p?.title ?? p?.name ?? 'Без названия';
const oneTitle = computed(() => getTitle(oneProject.value || {}));
const oneShortDesc = computed(() => oneProject.value?.description || '');
const oneDesc = computed(() => oneProject.value?.about || '');
const oneStack = computed(() => oneProject.value?.stack || '');
const onePreview = computed(() => {
    if (!oneProject.value) return '/project.jpg';
    const key = oneProject.value.slug ?? oneProject.value.id;
    return previews.value[key] || '/project.jpg';
});
const links = computed(() => ({
    project     : oneProject.value?.project_link || '',
    presentation: presentationUrl.value
    || oneProject.value?.presentation_path
    || oneProject.value?.presentation_url
    || '',
    video       : oneProject.value?.video_link || '',
}));

const getReadonlyScore = (criterion) => {
    const arr = Array.isArray(criterion?.evaluations) ? criterion.evaluations : [];
    if (!arr.length) return 0;
    const avg = arr.reduce((s, e) => s + Number(e?.score || 0), 0) / arr.length;
    return Math.round(avg);
};

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

async function fetchOneProject (slugOrId) {
    if (!slugOrId) return;
    try {
        const { data } = await axios.get(
            route('hackathons.projects.show', { hackathon: props.hackathon.slug, project: slugOrId }),
            { headers: { Accept: 'application/json' } }
        );
        if (data?.project) oneProject.value = data.project;
        groupCriteries.value = Array.isArray(data?.groupCriteries) ? data.groupCriteries : [];
        await Promise.all([
            fetchPresentation(data?.project?.slug ?? slugOrId),
            loadProjectGallery({ slug: data?.project?.slug ?? slugOrId }),
        ]);
    } catch (e) {
        console.error('project-show', e?.response ?? e);
    }
}
async function loadProjectGallery (project) {
    if (!project?.slug) { oneGallery.value = []; return }
    galleryLoading.value = true;
    try {
        const { data } = await axios.get(
            route('hackathons.projects.gallery', { hackathon: props.hackathon.slug, project: project.slug }),
            { headers: { Accept: 'application/json' } }
        );
        const raw = data?.gallery ?? [];
        oneGallery.value = raw.map(it => ({
                id  : typeof it === 'object' ? (it.id ?? it.media_id ?? it.key ?? null) : null,
                url : typeof it === 'string' ? it : (it.url ?? it.original_url ?? it.preview_url ?? it.path ?? ''),
            name: typeof it === 'object' ? (it.name ?? it.filename ?? '') : '',
        })).filter(it => it.url);
    } catch (e) {
        console.error('project-gallery', e?.response ?? e);
        oneGallery.value = [];
    } finally {
        galleryLoading.value = false;
    }
}

async function fetchPresentation(slug) {
    presentationUrl.value = '';
    if (!slug) return;
    try {
        const { data } = await axios.get(
            route('hackathons.projects.presentation', { hackathon: props.hackathon.slug, project: slug }),
            { headers: { Accept: 'application/json' } }
        );
        presentationUrl.value = data?.url || '';
    } catch (e) {
        if (e?.response?.status !== 404) console.error('project-presentation', e?.response ?? e);
    }
}

const updateProject = (project) => {
    oneProject.value = project;
    isForm.value = true;
}

function openProject(project) {
    oneProject.value = project;
    fetchOneProject(project?.slug ?? project?.id);
}

function closeProject() {
    oneProject.value = null;
    oneGallery.value = [];
    groupCriteries.value = [];
    presentationUrl.value = '';
}

watch(() => isForm.value, async () => {
    if (!isForm.value) {
        oneProject.value = null;
        await fetchProjects();
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

    <CreateProject
        v-if="isForm"
        v-model="isForm"
        :hackathonSlug="props.hackathon.slug"
        :teamId="props.ownTeam.id"
        :oneProject="oneProject ?? ''"
    />

    <div v-else-if="isOne" class="hackathon__tab_main">
        <div class="hackathon__tab_container">
            <div style="display:flex; gap:12px; justify-content: space-between; flex-wrap: wrap; align-items:center; margin-bottom:12px;">
                <p class="hackathon__my-project__title" style="margin:0">{{ oneTitle }}</p>
                <button type="button" class="main__btn_main hackathon__tab_back" @click="closeProject">← {{ capitalizeFirstLetter(langStore.translations.back_to_projects) }}
                </button>
            </div>
            <div class="hackathon__oneProject_image">
                <img :src="onePreview" alt="">
            </div>
            <p class="">{{ oneShortDesc }}</p>
        </div>
        <div class="hackathon__tab_container" v-if="oneDesc">
            <p class="hackathon__my-project__title">{{ capitalizeFirstLetter(langStore.translations.description) }}</p>
            <p class="">{{ oneDesc }}</p>
        </div>
        <div class="hackathon__tab_container" v-if="oneStack">
            <p class="hackathon__my-project__title">{{ capitalizeFirstLetter(langStore.translations.techStack) }}</p>
            <p class="">{{ oneStack }}</p>
        </div>
        <div class="hackathon__tab_container">
            <p class="hackathon__my-project__title">{{ capitalizeFirstLetter(langStore.translations.project_gallery) }}</p>
            <div v-if="galleryLoading" class="hackathon__oneProject_gallery">
                <div v-for="i in 4" :key="'g'+i" class="skeleton-loader" style="height:160px"></div>
            </div>
            <div v-else-if="oneGallery.length" class="hackathon__oneProject_gallery">
                <a v-for="img in oneGallery"
                   :key="img.id ?? img.url"
                   :href="img.url"
                   class="hackathon__oneProject_gallery-item"
                   target="_blank" rel="noopener noreferrer"
                   :title="capitalizeFirstLetter(langStore.translations.open_in_new_tab)"
                ><img :src="img.url" :alt="img.name || 'Изображение проекта'">
                </a>
            </div>
        </div>
        <div class="hackathon__tab_container">
            <p class="hackathon__my-project__title">{{ capitalizeFirstLetter(langStore.translations.materials) }}</p>
            <div class="hackathon__oneProject_media" v-if="links.project">
                <p class="hackathon__oneProject_media-title">{{ capitalizeFirstLetter(langStore.translations.projectLink) }}</p>
                <div class="hackathon__oneProject_media-item"><Hyperlink /><a :href="links.project" target="_blank" rel="noopener noreferrer">
                    {{ capitalizeFirstLetter(langStore.translations.link) }}
                </a>
                </div>
            </div>
            <div class="hackathon__oneProject_media" v-if="links.presentation">
                <p class="hackathon__oneProject_media-title">{{ capitalizeFirstLetter(langStore.translations.presentation) }}</p>
                <div class="hackathon__oneProject_media-item"><Document /><a :href="links.presentation" download target="_blank" rel="noopener noreferrer">
                    {{ capitalizeFirstLetter(langStore.translations.file) }}
                </a>
                </div>
            </div>
            <div class="hackathon__oneProject_media" v-if="links.video">
                <p class="hackathon__oneProject_media-title">{{ capitalizeFirstLetter(langStore.translations.videoLink) }}</p>
                <div class="hackathon__oneProject_media-item"><Hyperlink /><a :href="links.video" target="_blank" rel="noopener noreferrer">
                    {{ capitalizeFirstLetter(langStore.translations.link) }}
                </a>
                </div>
            </div>
        </div>
        <div class="hackathon__tab_container" v-if="groupCriteries?.length">
            <p class="hackathon__my-project__title">{{ capitalizeFirstLetter(langStore.translations.final_score) }}</p>
            <div v-for="group in groupCriteries" :key="group.id" class="dialog__prize">
                <div class="dialog__eva_container"><p class="dialog__eva">{{ group.title }}</p>
                </div>
                <div v-for="criterion in group.criteria" :key="criterion.id" class="dialog__eva_item"><p class="dialog__eva_title">{{ criterion.title }}</p><div class="dialog__eva_number dialog__eva_number-active" style="cursor: unset">
                    <p v-for="n in criterion.max_score"
                       :key="n"
                       :class="{ active: n <= getReadonlyScore(criterion) }"
                       style="cursor: unset"
                    >{{ n }}</p>
                </div>
                </div>
            </div>
        </div>
    </div>

    <div class="hackathon__my-project" v-else>
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
                <div
                    class="hackathon__my-project__item"
                    v-for="(project,idx) in projects"
                    :key="idx"
                    @click="openProject(project)"
                    style="cursor:pointer"
                >
                    <div class="hackathon__my-project__item_header">
                        <img :src="previews[project.slug]" v-if="previews[project.slug]" alt="">
                        <div class="skeleton-loader" v-if="!previews[project.slug]"></div>
                        <p class="main__card_photo-status black" v-if="project.status === 1">{{ capitalizeFirstLetter(langStore.translations.draft) }}</p>
                        <p class="main__card_photo-status yellow" v-if="project.status === 2">{{ capitalizeFirstLetter(langStore.translations.pending) }}</p>
                        <p class="main__card_photo-status red" v-else-if="project.status === 4">{{ capitalizeFirstLetter(langStore.translations.rejected) }}</p>
                        <p class="main__card_photo-status green" v-else-if="project.status === 3">{{ capitalizeFirstLetter(langStore.translations.approved) }}</p>
                        <button
                            type="button"
                            class="main__btn_main hackathon__my-project__team_svg"
                            @click.stop="updateProject(project)"
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
                    v-if="localTeamManagementEnabled && props.can.team.update"
                >
                    <IconsPencilMyProject
                    />
                </button>
                <EditTeam
                    v-if="localTeamManagementEnabled"
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
                v-if="localTeamManagementEnabled && props.can.team.invite"
            >
                {{ capitalizeFirstLetter(langStore.translations.invite_to_team) }}
            </button>
            <InvitationToTheTeam
                v-if="localTeamManagementEnabled"
                v-model="showInvitation"
                :positions="props.positions"
                :ownTeam="props.ownTeam"
                :hackathon="props.hackathon"
            />
        </div>
    </div>
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
