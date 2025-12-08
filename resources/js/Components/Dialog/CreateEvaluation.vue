<script setup>
import {onMounted, reactive, watch, ref} from 'vue'
import IconsCancel from '@/Components/Icons/Cancel.vue'
import {router} from "@inertiajs/vue3"
import {useLangStore} from "@/store/lang.js"

const props = defineProps({
    modelValue: Boolean,
    hackathonSlug: {type: String, default: null},
    initial: {type: Object, default: null},
})
const emit = defineEmits(['update:modelValue', 'saved'])

const langStore = useLangStore()

const uid = () => Math.random().toString(36).slice(2)

const empty = () => ({
    id: null,
    title: '',
    items: [{id: null, title: '', _key: uid()}],
})
const form = reactive(empty())

const saving = ref(false)

const errors = reactive({})
function setErrors(obj) {
    Object.keys(errors).forEach(k => delete errors[k])
    if (!obj) return
    for (const [k, v] of Object.entries(obj)) {
        errors[k] = Array.isArray(v) ? (v[0] ?? '') : v
    }
}
function clearError(field) { if (errors[field]) delete errors[field] }
const critKey = (idx) => `criteria.${idx}.title`

watch(() => props.initial, v => {
    if (!v) {
        Object.assign(form, empty());
        return
    }
    const items = (v.criteria ?? []).map(c => ({
        id: c.id ?? null,
        title: c.title ?? '',
        _key: c.id ?? uid(),
    }))
    Object.assign(form, {
        id: v.id,
        title: v.title ?? '',
        items: items.length ? items : [{id: null, title: '', _key: uid()}],
    })
    Object.keys(errors).forEach(k => delete errors[k])
}, {immediate: true})

function close() {
    emit('update:modelValue', false)
}

function addItem() {
    form.items.push({id: null, title: '', _key: uid()})
}

function removeItem(key) {
    form.items = form.items.filter(x => (x.id ?? x._key) !== key)
}

async function submit() {
    const titles = form.items
        .map(c => typeof c === 'string' ? c : c.title)
        .map(t => (t ?? '').trim())
        .filter(Boolean)

    const payload = {
        title: (form.title ?? '').trim(),
        criteria: titles.map(t => ({title: t, max_score: 10})),
    }

    Object.keys(errors).forEach(k => delete errors[k])

    const opts = {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onStart: () => {
            saving.value = true
        },
        onSuccess: () => {
            emit('saved')
            Object.assign(form, empty())
            close()
        },
        onError: (err) => {
            setErrors(err)
        },
        onFinish: () => {
            saving.value = false
        },
    }

    try {
        if (form.id) {
            await router.patch(
                route('hackathons.criteria.update', {hackathon: props.hackathonSlug, criterionGroup: form.id}),
                payload,
                opts
            )
        } else {
            await router.post(
                route('hackathons.criteria.store', {hackathon: props.hackathonSlug}),
                payload,
                opts
            )
        }
    } catch (err) {
        console.error('criteria-save-error', err?.response ?? err)
        saving.value = false
    }
}

onMounted(async () => {
    await langStore.fetchTranslations()
})

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:3">
        <div class="dialog__container_custom dialog__container_small" @click.stop>
            <div class="dialog__inner" :class="{ 'is-saving': saving }">
                <div class="dialog__header">
                    <p>{{
                            props.initial ? capitalizeFirstLetter(langStore.translations.edit_criteria_group) : capitalizeFirstLetter(langStore.translations.addCriteriaGroup)
                        }}</p>
                    <div class="dialog__close" @click="close">
                        <svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M7.90994 6.00019L12.2099 1.71019C12.3982 1.52188 12.504 1.26649 12.504 1.00019C12.504 0.733884 12.3982 0.478489 12.2099 0.290185C12.0216 0.101882 11.7662 -0.00390625 11.4999 -0.00390625C11.2336 -0.00390625 10.9782 0.101882 10.7899 0.290185L6.49994 4.59019L2.20994 0.290185C2.02164 0.101882 1.76624 -0.00390625 1.49994 -0.00390625C1.23364 -0.00390625 0.978243 0.101882 0.789939 0.290185C0.601635 0.478489 0.495847 0.733884 0.495847 1.00019C0.495847 1.26649 0.601635 1.52188 0.789939 1.71019L5.08994 6.00019L0.789939 10.2902C0.696211 10.3831 0.621816 10.4937 0.571048 10.6156C0.520279 10.7375 0.494141 10.8682 0.494141 11.0002C0.494141 11.1322 0.520279 11.2629 0.571048 11.3848C0.621816 11.5066 0.696211 11.6172 0.789939 11.7102C0.882902 11.8039 0.993503 11.8783 1.11536 11.9291C1.23722 11.9798 1.36793 12.006 1.49994 12.006C1.63195 12.006 1.76266 11.9798 1.88452 11.9291C2.00638 11.8783 2.11698 11.8039 2.20994 11.7102L6.49994 7.41019L10.7899 11.7102C10.8829 11.8039 10.9935 11.8783 11.1154 11.9291C11.2372 11.9798 11.3679 12.006 11.4999 12.006C11.632 12.006 11.7627 11.9798 11.8845 11.9291C12.0064 11.8783 12.117 11.8039 12.2099 11.7102C12.3037 11.6172 12.3781 11.5066 12.4288 11.3848C12.4796 11.2629 12.5057 11.1322 12.5057 11.0002C12.5057 10.8682 12.4796 10.7375 12.4288 10.6156C12.3781 10.4937 12.3037 10.3831 12.2099 10.2902L7.90994 6.00019Z"
                                fill="#999999"/>
                        </svg>
                    </div>
                </div>

                <div class="dialog__component">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.criteriaCategory) }}</p>
                    <input
                        v-model="form.title"
                        class="dialog__input"
                        :placeholder="capitalizeFirstLetter(langStore.translations.enterCriteriaCategory)"
                        :class="{ error: !!errors.title }"
                        @input="clearError('title')"
                    />
                    <small v-if="errors.title" class="error__text">{{ errors.title }}</small>
                </div>

                <div class="dialog__title_header">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.criterion) }}</p>
                    <div class="dialog__plus" @click="addItem">
                        <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M13.1665 7.33317H9.1665V3.33317C9.1665 3.15636 9.09627 2.98679 8.97124 2.86177C8.84622 2.73674 8.67665 2.6665 8.49984 2.6665C8.32303 2.6665 8.15346 2.73674 8.02843 2.86177C7.90341 2.98679 7.83317 3.15636 7.83317 3.33317V7.33317H3.83317C3.65636 7.33317 3.48679 7.40341 3.36177 7.52843C3.23674 7.65346 3.1665 7.82303 3.1665 7.99984C3.1665 8.17665 3.23674 8.34622 3.36177 8.47124C3.48679 8.59627 3.65636 8.6665 3.83317 8.6665H7.83317V12.6665C7.83317 12.8433 7.90341 13.0129 8.02843 13.1379C8.15346 13.2629 8.32303 13.3332 8.49984 13.3332C8.67665 13.3332 8.84622 13.2629 8.97124 13.1379C9.09627 13.0129 9.1665 12.8433 9.1665 12.6665V8.6665H13.1665C13.3433 8.6665 13.5129 8.59627 13.6379 8.47124C13.7629 8.34622 13.8332 8.17665 13.8332 7.99984C13.8332 7.82303 13.7629 7.65346 13.6379 7.52843C13.5129 7.40341 13.3433 7.33317 13.1665 7.33317Z"
                                fill="#E80024"/>
                        </svg>
                        <p>{{ capitalizeFirstLetter(langStore.translations.addMore) }}</p>
                    </div>
                </div>

                <div class="dialog__component" v-for="(it,idx) in form.items" :key="idx">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.criterionTitle) }}</p>
                    <div class="dialog__input_btns">
                        <input
                            v-model="it.title"
                            class="dialog__input"
                            :placeholder="capitalizeFirstLetter(langStore.translations.enterCriterionTitle)"
                            style="width:100%"
                            :class="{ error: !!errors[critKey(idx)] }"
                            @input="clearError(critKey(idx))"
                        />
                        <IconsCancel class="clickable" style="cursor:pointer" @click="removeItem(it.id ?? it._key)"/>
                    </div>
                    <small v-if="errors[critKey(idx)]" class="error__text">{{ errors[critKey(idx)] }}</small>
                </div>

                <div class="dialog__btns">
                    <button class="main__btn main__btn_white dialog__btn" @click="close">
                        {{ capitalizeFirstLetter(langStore.translations.cansel) }}
                    </button>
                    <button class="main__btn dialog__btn" @click="submit">{{
                            props.initial ? capitalizeFirstLetter(langStore.translations.update) : capitalizeFirstLetter(langStore.translations.add)
                        }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dialog {
    position: fixed;
    inset: 0;
}

.dialog__container {
    position: relative;
}

.dialog__inner.is-saving {
    filter: blur(3px);
    user-select: none;
    pointer-events: none;
}

.dialog__saving-overlay {
    position: absolute;
    inset: 0;
    z-index: 10;
    display: grid;
    place-items: center;
    background: rgba(255, 255, 255, 0.55);
    backdrop-filter: blur(2px);
    border-radius: inherit;
    pointer-events: all;
}

.dialog__saving-spinner {
    width: 150px;
    height: 150px;
    object-fit: contain;
    pointer-events: none;
}
</style>
