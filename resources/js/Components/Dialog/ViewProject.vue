<script setup>
import {computed, onMounted, ref, watch} from 'vue'
import DropFile from "@/Components/DropFile.vue";
import axios from "axios";
import DropPPTX from "@/Components/DropPPTX.vue";
import DropFiles from "@/Components/DropFiles.vue";

const props = defineProps({
    modelValue : { type: Boolean, default: false },
    project    : { type: Object,  default: () => ({}) },
    hackathonSlug: { type: String },
})

const emit = defineEmits(['update:modelValue', 'approve', 'reject'])

const tabs = ['Основная информация', 'Описание', 'Материалы']
const active = ref(0)

const title      = computed(() => props.project?.title ?? '—')
const shortDesc  = computed(() => props.project?.description ?? props.project?.short_description ?? '—')
const about      = computed(() => props.project?.about ?? '—')
const stack      = computed(() => props.project?.stack ?? '—')
const videoLink      = computed(() => props.project?.video_link ?? '—')
const projectLink      = computed(() => props.project?.project_link ?? '—')
const links = computed(() => ({
    project:      props.project?.project_link ?? '',
    presentation: props.project?.presentation_path ?? props.project?.presentation_url ?? '',
    video:        props.project?.video_link ?? ''
}))

const pptx = ref(null);
const projectImages = ref([])

const preview     = ref(null)

watch(() => props.modelValue, () => {
    getPreview(props.project.slug)
    getGallery(props.project.slug)
    pptx.value = getPresentation(props.project.presentation_path)
});

function getPresentation(presentation_path) {
    return presentation_path.split('/').pop();
}

async function getPreview(slugId) {
    try {
        const { data: blob } = await axios.get(
            route('hackathons.projects.image', { hackathon: props.hackathonSlug, project: slugId }),
            { responseType: 'blob' }
        )
        preview.value = URL.createObjectURL(blob);
    } catch (e) {
        console.error('hackathon-load', e?.response ?? e);
    }
}
async function getGallery(slugId) {
    try {
        const response = await axios.get(
            route('hackathons.projects.gallery', { hackathon: props.hackathonSlug, project: slugId }),
            {
                headers: {
                    'Accept': 'application/json'
                }
            }
        );

        projectImages.value = response.data.gallery
    } catch (e) {
        console.error('hackathon-load', e?.response ?? e);
    }
}

function close()   { emit('update:modelValue', false) }
function approve() { emit('approve', props.project) }
function rejectP() { emit('reject',  props.project) }
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index: 1">
        <div class="dialog__container" @click.stop>
            <div class="dialog__header">
                <p>Просмотр проекта</p>
                <div class="dialog__close" @click="close">
                    <svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M7.90994 6.00019L12.2099 1.71019C12.3982 1.52188 12.504 1.26649 12.504 1.00019C12.504 0.733884 12.3982 0.478489 12.2099 0.290185C12.0216 0.101882 11.7662 -0.00390625 11.4999 -0.00390625C11.2336 -0.00390625 10.9782 0.101882 10.7899 0.290185L6.49994 4.59019L2.20994 0.290185C2.02164 0.101882 1.76624 -0.00390625 1.49994 -0.00390625C1.23364 -0.00390625 0.978243 0.101882 0.789939 0.290185C0.601635 0.478489 0.495847 0.733884 0.495847 1.00019C0.495847 1.26649 0.601635 1.52188 0.789939 1.71019L5.08994 6.00019L0.789939 10.2902C0.696211 10.3831 0.621816 10.4937 0.571048 10.6156C0.520279 10.7375 0.494141 10.8682 0.494141 11.0002C0.494141 11.1322 0.520279 11.2629 0.571048 11.3848C0.621816 11.5066 0.696211 11.6172 0.789939 11.7102C0.882902 11.8039 0.993503 11.8783 1.11536 11.9291C1.23722 11.9798 1.36793 12.006 1.49994 12.006C1.63195 12.006 1.76266 11.9798 1.88452 11.9291C2.00638 11.8783 2.11698 11.8039 2.20994 11.7102L6.49994 7.41019L10.7899 11.7102C10.8829 11.8039 10.9935 11.8783 11.1154 11.9291C11.2372 11.9798 11.3679 12.006 11.4999 12.006C11.632 12.006 11.7627 11.9798 11.8845 11.9291C12.0064 11.8783 12.117 11.8039 12.2099 11.7102C12.3037 11.6172 12.3781 11.5066 12.4288 11.3848C12.4796 11.2629 12.5057 11.1322 12.5057 11.0002C12.5057 10.8682 12.4796 10.7375 12.4288 10.6156C12.3781 10.4937 12.3037 10.3831 12.2099 10.2902L7.90994 6.00019Z"
                            fill="#999999"
                        />
                    </svg>
                </div>
            </div>

            <!-- Табы -->
            <div class="dialog__tabs">
                <div
                    v-for="(t,i) in tabs"
                    :key="t"
                    :class="['dialog__tabs_item', { active: active === i }]"
                    @click="active = i"
                >
                    <p>{{ t }}</p>
                </div>
            </div>
<!--            <pre>{{props.project}}</pre>-->
            <!-- Контент -->
            <div class="dialog__content is-readonly">
                <div v-if="active === 0" class="view-block">
                    <div class="dialog__component">
                        <p class="dialog__title">Название проекта</p>
                        <input
                            :value="title"
                            type="text"
                            class="dialog__input"
                        >
                    </div>

                    <div class="dialog__component">
                        <p class="dialog__title">Краткое описание</p>
                        <textarea
                            :value="shortDesc"
                            style="min-height: 208px"
                            class="dialog__textarea"
                            readonly
                        />
                    </div>

                    <div class="dialog__component">
                        <p class="dialog__title">Превью проекта</p>
                        <DropFile v-model:file="preview" />
                    </div>
                </div>

                <!-- Описание -->
                <div v-else-if="active === 1" class="view-block">
                    <div class="dialog__component">
                        <p class="dialog__title">О проекте</p>
                        <textarea
                            :value="about"
                            style="min-height: 208px"
                            class="dialog__textarea"
                            readonly
                        />
                    </div>
                    <div class="dialog__component">
                        <p class="dialog__title">Технологический стек проекта</p>
                        <input
                            :value="stack"
                            type="text"
                            class="dialog__input"
                            readonly
                        >
                    </div>
                    <div class="dialog__component">
                        <p class="dialog__title">Ссылка на проект</p>
                        <input
                            v-model="projectLink"
                            type="text"
                            class="dialog__input"
                            readonly
                        >
                    </div>
                </div>

                <!-- Материалы -->
                <div v-else class="view-block">
                    <div class="dialog__component">
                        <p class="dialog__title">Презентация</p>
                        <DropPPTX v-model:file="pptx" />
                    </div>

                    <div class="dialog__component">
                        <p class="dialog__title">Галерея проекта</p>
                        <DropFiles :files="projectImages" />
                    </div>

                    <div class="dialog__component">
                        <p class="dialog__title">Ссылка на видео</p>
                        <input
                            v-model="videoLink"
                            type="text"
                            class="dialog__input"
                            readonly
                        >
                    </div>
                </div>
            </div>

            <!-- Кнопки решения -->
            <div class="dialog__footer" style="display:flex; gap:12px; justify-content:flex-end;">
                <button class="main__btn main__btn_white" @click="rejectP">Отклонить</button>
                <button class="main__btn_main" @click="approve">Принять</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.view-block {
    display: flex;
    flex-direction: column;
    gap: 30px
}
</style>
