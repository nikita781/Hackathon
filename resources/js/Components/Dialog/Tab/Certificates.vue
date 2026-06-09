<script setup>
import {ref, computed, onMounted} from 'vue'
import DropFileHtml from '@/Components/DropFileHtml.vue'
import {router} from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'
import {useLangStore} from '@/store/lang.js'
import InfoCertificates from "@/Components/Dialog/InfoCertificates.vue";
import axios from "axios";

const sealInput = ref(null)
const sealFile = ref(null)

function pickSeal() {
  form.clearErrors('seal')
  sealInput.value?.click()
}

async function onSealPicked(e) {
    const f = e.target.files?.[0]
    if (!f) return

    sealFile.value = f
    form.seal = f
    form.clearErrors('seal')
    emit('dirty', true)
    e.target.value = ''
}

const props = defineProps({
    hackathonSlug: { type: String, required: true },
    admin:        { type: Boolean, default: () => false },
    readonly:     { type: Boolean, default: () => false },
})
const emit = defineEmits(['saved','cancel','dirty','saving'])

const isAdmin = computed(() => !!props.admin)
const isReadOnly = computed(() => !!props.readonly)
const langStore = useLangStore()

const form = useForm({
    template: null,
    width: '',
    height: '',
    seal: null,
})

const file = ref(null)
const pending = ref(false)

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

function onFileUpdate(f) {
    file.value = f
    form.template = f
    form.clearErrors('template')
    emit('dirty', !!f)
}

function clearFieldError(field) {
    form.clearErrors(field)
}

async function uploadTemplate() {
    form.clearErrors()

    const widthEmpty  = form.width === ''  || form.width === null  || typeof form.width === 'undefined'
    const heightEmpty = form.height === '' || form.height === null || typeof form.height === 'undefined'

    const hasTemplate = !!file.value
    const hasSeal = !!sealFile.value

    if (!hasTemplate && !hasSeal && widthEmpty && heightEmpty) {
        emit('saved', { slug: props.hackathonSlug })
        return
    }

    if (!hasTemplate && (!widthEmpty || !heightEmpty)) {
        form.setError('template', capitalizeFirstLetter(langStore.translations.upload_template_file))
        return
    }
    pending.value = true
    emit('saving', true)
    try {
        if (hasTemplate) {
            const fd = new FormData()
            fd.append('template', file.value)
            fd.append('_method', 'patch')

            if (!widthEmpty)  fd.append('width', form.width)
            if (!heightEmpty) fd.append('height', form.height)
            await axios.post(
                route('hackathons.upload-template', { hackathon: props.hackathonSlug }),
                fd,
                { headers: { 'Content-Type': 'multipart/form-data' } }
            )
        }

        if (hasSeal) {
            const fd2 = new FormData()
            fd2.append('seal', sealFile.value)
            fd2.append('_method', 'patch')

            await axios.post(
                route('hackathons.upload-seal', { hackathon: props.hackathonSlug }),
                fd2,
                { headers: { 'Content-Type': 'multipart/form-data' } }
            )
        }

        emit('dirty', false)
        emit('saved', { slug: props.hackathonSlug })
    } catch (e) {
        console.error('upload-certs', e?.response ?? e)
        if (e?.response?.status === 403) {
            form.setError('seal', 'Нет прав (403)')
            return
        }
        if (e?.response?.status === 404) {
            form.setError('seal', 'Маршрут не найден (404)')
            return
        }

        if (e?.response?.status === 422 && e.response.data?.errors) {
            const errors = e.response.data.errors
            Object.entries(errors).forEach(([field, messages]) => {
                form.setError(field, (messages || []).join(' '))
            })
        } else {
            form.setError('seal', 'Ошибка сохранения')
        }
    } finally {
        pending.value = false
        emit('saving', false)
    }
}

function downloadPreview() {
    const url = route('hackathons.previewSertificate', { hackathon: props.hackathonSlug })
    window.open(url, '_blank')
}

function cancel() { emit('cancel') }

const showInfo = ref(false)

onMounted(async () => { await langStore.fetchTranslations() })
</script>

<template>
    <div class="certs">
        <button class="main__btn" @click="showInfo = true" style="width: fit-content">
            {{ capitalizeFirstLetter(langStore.translations.instruction) }}
        </button>
        <small v-if="sealFile" class="hint small" style="text-align: end">
            Выбрано: {{ sealFile.name }}
        </small>
        <div class="dialog__title_header">
            <div class="dialog__title_container">
                <p class="dialog__title">
                    {{ capitalizeFirstLetter(langStore.translations.certificates || 'Сертификаты') }}
                </p>
                <InfoCertificates v-model="showInfo" />
            </div>

            <div class="dialog__actions">
                <input
                    ref="sealInput"
                    type="file"
                    accept="image/png"
                    style="display:none"
                    @change="onSealPicked"
                    />
                <button class="main__btn main__btn_white" @click="pickSeal" :disabled="isReadOnly">
                    Печать (PNG)
                </button>
                <small v-if="form.errors.seal" class="error__text">{{ form.errors.seal }}</small>
                <button class="main__btn main__btn_white" @click="downloadPreview">
                    {{ capitalizeFirstLetter(langStore.translations.downloadPreview || 'Скачать превью') }}
                </button>
            </div>
        </div>

        <DropFileHtml
            v-if="!isReadOnly"
            :file="file"
            @update:file="onFileUpdate"
        />
        <small v-if="form.errors.template" class="error__text">{{ form.errors.template }}</small>

        <div class="dialog__component" style="width: 100%">
            <div class="dialog__title_container">
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.height_width) }}</p>
                <div class="help-tt" aria-label="help">
                    <svg class="help-tt__icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="#000" />
                        <path d="M12 17v-5" stroke="#000" stroke-linecap="round"/>
                        <circle cx="12" cy="8" r="1" fill="#000"/>
                    </svg>
                    <div class="tooltipSquare"></div>
                    <div class="tooltip">
                        <p>{{
                                capitalizeFirstLetter(langStore.translations.certificate_dimensions_hint)
                            }}</p>
                    </div>
                </div>
            </div>

            <div class="dialog__horizontal">
                <input
                    v-model="form.height"
                    type="number"
                    class="dialog__input"
                    style="width: 100%"
                    :placeholder="capitalizeFirstLetter(langStore.translations.height_mm)"
                    min="50"
                    max="1000"
                    step="1"
                    :disabled="isReadOnly"
                    :class="{ 'error': form.errors.height }"
                    @input="clearFieldError('height')"
                >
                <input
                    v-model="form.width"
                    type="number"
                    class="dialog__input"
                    style="width: 100%"
                    :placeholder="capitalizeFirstLetter(langStore.translations.width_mm)"
                    min="50"
                    max="1000"
                    step="1"
                    :disabled="isReadOnly"
                    :class="{ 'error': form.errors.width }"
                    @input="clearFieldError('width')"
                >
            </div>

            <small
                v-if="form.errors.height || form.errors.width"
                class="error__text"
            >
                {{ form.errors.height }}<br v-if="form.errors.height && form.errors.width">
                {{ form.errors.width }}
            </small>
        </div>
    </div>

    <div class="dialog__btns" v-if="!isAdmin && !isReadOnly">
        <button class="main__btn main__btn_white" @click="cancel">
            {{ capitalizeFirstLetter(langStore.translations.cansel || 'Отмена') }}
        </button>
        <button class="main__btn" @click="uploadTemplate">
            {{ pending ? (langStore.translations.saving || 'Сохранение...') : (langStore.translations.save || 'Сохранить') }}
        </button>
    </div>
</template>

<style scoped>
.certs { display: grid; gap: 12px; }
.dialog__actions { display: flex; gap: 8px; align-items: center; }
.hint.small { font-size: 12px; color: #888; }
</style>
