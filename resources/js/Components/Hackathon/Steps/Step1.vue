<script setup>
import DropFile from "@/Components/DropFile.vue";
import {computed, onMounted, ref, watch} from "vue";
import axios    from 'axios'
import {useLangStore} from "@/store/lang.js";

const props = defineProps({
    hackathonSlug: { type: String, required: true },
    project: { type: Object, required: true },
    oneProject: { type: Object, required: true },
    teamId:        { type: Number, required: true },
})

const emit = defineEmits(['success', 'project', 'cancel'])

const title       = ref('')
const description = ref('')
const preview     = ref(null)
const isEdit      = ref(false)

watch(() => props.oneProject, () => {
    if (props.oneProject.slug) {
        isEdit.value = true
        title.value = props.oneProject.title
        description.value = props.oneProject.description
        getPreview(props.oneProject.slug)
    }
});

watch(() => props.project, () => {
    if (!!props.project) {
        isEdit.value = true
    }
});

const pending = ref(false)
const errors  = ref({})

function cancel () {
    resetState()
    emit('cancel')
}

const resetState = () => {
    title.value       = ''
    description.value = ''
    preview.value     = null
    errors.value      = {}
    pending.value     = false
}

onMounted(() => {
    if (props.oneProject.slug) {
        isEdit.value = true
        title.value = props.oneProject.title
        description.value = props.oneProject.description
        getPreview(props.oneProject.slug)
    }
    if (!!props.project) {
        isEdit.value = true
    }
})

async function getPreview(slugId) {
    try {
        const { data: blob } = await axios.get(
            route('hackathons.projects.image', { hackathon: props.hackathonSlug, project: slugId }),
            { responseType: 'blob' }
        )
        console.log(blob)
        preview.value = URL.createObjectURL(blob);
        console.log(preview.value)
    } catch (e) {
        console.error('hackathon-load', e?.response ?? e);
    }
}

async function submit () {
    pending.value = true
    errors.value  = {}

    try {
        const fd = new FormData()
        fd.append('title',       title.value)
        fd.append('description', description.value)
        if (preview.value && preview.value instanceof File) {
            fd.append('preview', preview.value)
        }

        let url, method
        let slug

        if (props.project) {
            slug = props.project.slug
        } else {
            slug = props.oneProject.slug
        }

        if (isEdit.value) {
            fd.append('_method', 'PATCH')
            url    = route('hackathons.projects.update', {
                hackathon: props.hackathonSlug,
                project:   slug,
            })
            method = 'post'
        } else {
            url    = route('hackathons.teams.projects.store', {
                hackathon: props.hackathonSlug,
                team:      props.teamId,
            })
            method = 'post'
        }

        const { data } = await axios[method](url, fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })
        const projectData = ref([])
        if (isEdit.value) {
            // console.log(data.projects.original)
            // console.log(data)

            const projects = ref({});

            try {
                const response = await axios.get(
                    route('hackathons.teams.projects.show-team-projects', {
                        hackathon: props.hackathonSlug,
                        team: props.teamId
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

            const updatedProject = projects.value.find(project => project.slug === slug);
            //
            // console.log(updatedProject)

            if (updatedProject) {
                if (preview.value !== null) {
                    updatedProject.updated_at = new Date().toISOString();
                }
                projectData.value = {
                    description: updatedProject.description,
                    id: updatedProject.id,
                    slug: updatedProject.slug,
                    title: updatedProject.title,
                    updated_at: updatedProject.updated_at,
                };

                // console.log(projectData.value);
            } else {
                console.error('Проект не найден');
            }
        }
        isEdit.value = false
        slug = '';
        console.log(data?.project ?? projectData.value)
        emit('success', data?.project ?? projectData.value)
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors
        } else {
            console.error(e)
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
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.projectTitle) }}</p>
            <input
                v-model="title"
                type="text"
                class="dialog__input"
                :placeholder="capitalizeFirstLetter(langStore.translations.enterProjectTitle)"
            >
            <span v-if="errors.title" class="error">{{ errors.title[0] }}</span>
        </div>
        <div class="dialog__component">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.shortDescription) }}</p>
            <textarea
                v-model="description"
                style="min-height: 208px"
                class="dialog__textarea"
                :placeholder="capitalizeFirstLetter(langStore.translations.enterProjectDescription)"
            />
            <span v-if="errors.description" class="error">{{ errors.description[0] }}</span>
        </div>
        <div class="dialog__component">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.projectPreview) }}</p>
            <DropFile v-model:file="preview" />
            <span v-if="errors.preview" class="error">{{ errors.preview[0] }}</span>
        </div>
        <div class="project__form_btns">
            <button
                class="main__btn dialog__btn"
                :disabled="pending"
                @click.prevent="submit"
            >
                {{ capitalizeFirstLetter(langStore.translations.next) }}
            </button>
            <button class="main__btn main__btn_white dialog__btn" @click="cancel">
                {{ capitalizeFirstLetter(langStore.translations.cancel) }}
            </button>
        </div>
    </div>
</template>

<style scoped>

</style>
