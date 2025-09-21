<script setup>
import {onMounted, ref, watch} from 'vue'
import axios    from 'axios'
import {useLangStore} from "@/store/lang.js";

const props = defineProps({
    hackathonSlug: { type: String, required: true },
    project:       { type: Object, required: true },
    oneProject: { type: Object, required: true },
})

const emit = defineEmits(['success', 'cancel'])

const about        = ref('')
const stack        = ref('')
const projectLink  = ref('')

watch(() => props.oneProject, () => {
    if (props.oneProject.slug) {
        about.value = props.oneProject.about
        stack.value = props.oneProject.stack
        projectLink.value = props.oneProject.project_link
    }
});

const pending = ref(false)
const errors  = ref({})

function cancel () {
    resetState()
    emit('cancel')
}

const resetState = () => {
    about.value       = ''
    stack.value       = ''
    projectLink.value = ''
    errors.value      = {}
    pending.value     = false
}

onMounted(() => {
    if (props.oneProject.slug) {
        about.value = props.oneProject.about
        stack.value = props.oneProject.stack
        projectLink.value = props.oneProject.project_link
    }
})

async function submit () {
    pending.value = true
    errors.value  = {}

    try {
        const fd = new FormData()
        fd.append('_method',      'PATCH')
        fd.append('about',        about.value)
        fd.append('stack',        stack.value)
        fd.append('project_link', projectLink.value)

        const { data } = await axios.post(
            route('hackathons.projects.update', {
                hackathon: props.hackathonSlug,
                project:   props.project.slug,
            }),
            fd,
        )
        emit('success')
    } catch (e) {
        console.error(e)
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors
        }
    } finally {
        pending.value = false
    }
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
    <div class="project__form">
        <div class="dialog__component">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.aboutProject) }}</p>
            <textarea
                v-model="about"
                style="min-height: 208px"
                class="dialog__textarea"
                :placeholder="capitalizeFirstLetter(langStore.translations.projectStory)"
            />
            <span v-if="errors.about" class="error">{{ errors.about[0] }}</span>
        </div>
        <div class="dialog__component">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.techStack) }}</p>
            <input
                v-model="stack"
                type="text"
                class="dialog__input"
                :placeholder="capitalizeFirstLetter(langStore.translations.techStackHint)"
            >
            <span v-if="errors.stack" class="error">{{ errors.stack[0] }}</span>
        </div>
        <div class="dialog__component">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.projectLink) }}</p>
            <input
                v-model="projectLink"
                type="text"
                class="dialog__input"
                :placeholder="capitalizeFirstLetter(langStore.translations.projectLinkHint)"
            >
            <span v-if="errors.project_link" class="error">{{ errors.project_link[0] }}</span>
        </div>
        <div class="project__form_btns">
            <button
                class="main__btn dialog__btn"
                :disabled="pending"
                @click.prevent="submit"
            >
                {{ capitalizeFirstLetter(langStore.translations.next) }}
            </button>
            <button
                class="main__btn main__btn_white dialog__btn"
                type="button"
                @click="cancel"
            >
                {{ capitalizeFirstLetter(langStore.translations.cansel) }}
            </button>
        </div>
    </div>
</template>

<style scoped>

</style>
