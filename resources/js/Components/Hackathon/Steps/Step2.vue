<script setup>
import { ref }  from 'vue'
import axios    from 'axios'

const props = defineProps({
    hackathonSlug: { type: String, required: true },
    project:       { type: Object, required: true }, // { id, slug, title }
})

const emit = defineEmits(['success', 'cancel'])

const about        = ref('')
const stack        = ref('')
const projectLink  = ref('')

const pending = ref(false)
const errors  = ref({})

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
</script>

<template>
    <div class="project__form">
        <div class="dialog__component">
            <p class="dialog__title">О проекте</p>
            <textarea
                v-model="about"
                style="min-height: 208px"
                class="dialog__textarea"
                placeholder="Расскажите о своем проекте"
            />
            <span v-if="errors.about" class="error">{{ errors.about[0] }}</span>
        </div>
        <div class="dialog__component">
            <p class="dialog__title">Технологический стек проекта</p>
            <input
                v-model="stack"
                type="text"
                class="dialog__input"
                placeholder="Укажите языки программирования, фреймворки, библиотеки, сторонние программы и другие инструменты"
            >
            <span v-if="errors.stack" class="error">{{ errors.stack[0] }}</span>
        </div>
        <div class="dialog__component">
            <p class="dialog__title">Ссылка на проект</p>
            <input
                v-model="projectLink"
                type="text"
                class="dialog__input"
                placeholder="Укажите ссылку на проект (демо-ссылка на сайт, GitHub, магазин приложений и т.д.)"
            >
            <span v-if="errors.project_link" class="error">{{ errors.project_link[0] }}</span>
        </div>
        <div class="project__form_btns">
            <button
                class="main__btn dialog__btn"
                :disabled="pending"
                @click.prevent="submit"
            >
                Далее
            </button>
            <button
                class="main__btn main__btn_white dialog__btn"
                type="button"
                @click="emit('cancel')"
            >
                Отменить
            </button>
        </div>
    </div>
</template>

<style scoped>

</style>
