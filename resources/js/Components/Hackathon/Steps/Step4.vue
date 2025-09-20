<script setup>
import IconsCheck from "@/Components/Icons/Check.vue";
import {computed, nextTick, onMounted, ref, watch} from "vue";
import {useToast} from "vue-toastification";

const props = defineProps({
    hackathonSlug: { type: String, required: true },
    project: { type: Object, required: true },
    oneProject: { type: Object, required: true },
    teamId:        { type: Number, required: true },
})

const emit = defineEmits(['cancel'])

const previewUrl = ref(null);

const agree = ref(false);
const pending = ref(false)
const disabled = computed(() => !agree.value || pending.value)
function toggleAgree () { agree.value = !agree.value }

const loadedProject = ref(null);
const loadingProject = ref(false);

async function fetchProject () {
    loadingProject.value = true;
    try {
        const { data } = await axios.get(
            route('hackathons.projects.show', {
                hackathon: props.hackathonSlug,
                project:   props.project.slug,
            })
        );
        // сервер возвращает { project: ... }
        loadedProject.value = data.project;
        console.log(data)
    } catch (e) {
        console.error('project-fetch', e?.response ?? e);
        // по желанию: показать тост
        // const toast = useToast(); toast.error('Не удалось загрузить проект');
    } finally {
        loadingProject.value = false;
    }
}

const projectData = computed(() => loadedProject.value ?? props.oneProject);
const teamUsers   = computed(() => projectData.value?.team?.users ?? []);

function cancel () {
    agree.value = false
    pending.value = false
    emit('cancel')
}

async function getPreview() {
    try{
        const { data: blob } = await axios.get(
            route('hackathons.projects.image', { hackathon: props.hackathonSlug, project: props.project.slug }),
            { responseType: 'blob' }
        )
        previewUrl.value = URL.createObjectURL(blob)
    }catch(e){
        console.error('hackathon-load',e?.response??e)
    }
}
onMounted(async () => {
    await nextTick();
    previewUrl.value = null;
    await Promise.all([ fetchProject(), getPreview() ]); // <-- загружаем проект при входе
});

watch(() => props.project, async () => {
    previewUrl.value = null;
    await Promise.all([ fetchProject(), getPreview() ]);
});

async function publishProject() {
    if (disabled.value) return;
    pending.value = true;
    try {
        const url = route('hackathons.teams.projects.publish', {
            hackathon: props.hackathonSlug,
            team     : props.teamId,
            project  : props.project.slug,
        });
        await axios.post(url, {});
        // emit('cancel');
        const toast = useToast(); toast.success('Проект отправлен на модерацию!');
    } catch (e) {
        console.error('publish-project', e?.response ?? e);
        const toast = useToast(); toast.error('Не удалось отправить проект');
    } finally {
        pending.value = false;
        agree.value = false;
    }
}

const PLACEHOLDER = '/profile.jpg';

function avatarSrc(photo) {
    console.log(photo)
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
</script>

<template>
    <div class="project__form">
        <div class="dialog__component">
            <p class="dialog__title">Превью проекта</p>
            <div class="project__form_preview">
                <div class="hackathon__my-project__item">
                    <div class="hackathon__my-project__item_header">
                        <div class="skeleton-loader" v-if="!previewUrl"></div>
                        <img v-if="previewUrl" :src="previewUrl" alt="Превью проекта" />
                    </div>
                    <div class="hackathon__my-project__item_content">
                        <div>
                            <p class="hackathon__my-project__item_title">{{ projectData?.title }}</p>
                            <p class="hackathon__my-project__item_text">{{ projectData?.description }}</p>
                        </div>
                        <ul class="hackathon__my-project__item_avatar" v-if="teamUsers.length">
                            <li v-for="user in teamUsers" :key="user.id">
                                <img :src="avatarSrc(user.user?.photo)" @error="imgFallback" alt="Avatar">
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <pre>>{{props.oneProject}}</pre>
        <div class="dialog__component">
            <p class="dialog__title">Правила и условия</p>
            <div class="dialog__checkbox" style="margin-top: 10px">
                <div>
                    <div @click="toggleAgree" class="custom-checkbox" :class="agree ? 'active' : ''">
                        <IconsCheck />
                    </div>
                </div>
                <p>Я и все члены моей команды ознакомились с Официальными правилами и Условиями предоставления услуг и согласны с ними</p>
            </div>
        </div>
        <div class="project__form_btns">
            <button class="main__btn dialog__btn"
                    :class="{ blocked: disabled }"
                    :disabled="disabled"
                    @click="publishProject"
            >
                <span v-if="pending">Отправка…</span>
                <span v-else>Отправить</span>
            </button>
            <button class="main__btn main__btn_white dialog__btn" @click="cancel">
                Отменить
            </button>
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
