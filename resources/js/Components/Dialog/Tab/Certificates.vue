<script setup>
import {ref, computed, onMounted} from 'vue'
import DropFileHtml from '@/Components/DropFileHtml.vue'
import {router} from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'
import {useLangStore} from '@/store/lang.js'
import InfoCertificates from "@/Components/Dialog/InfoCertificates.vue";

const props = defineProps({
    hackathonSlug: { type: String, required: true },
    admin:        { type: Boolean, default: () => false },
})
const emit = defineEmits(['saved','cancel','dirty','saving'])

const isAdmin = computed(() => !!props.admin)
const langStore = useLangStore()

const form = useForm({ template: null })
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

async function uploadTemplate() {
    if (!file.value) return
    pending.value = true
    emit('saving', true)
    try {
        const form = new FormData()
        form.append('template', file.value)
        form.append('_method', 'patch')

        await axios.post(
            route('hackathons.upload-template', { hackathon: props.hackathonSlug }),
            form,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        )

        emit('dirty', false)
        emit('saved', { slug: props.hackathonSlug })
    } catch (e) {
        console.error('upload-template', e?.response ?? e)
        if (e?.response?.status === 422 && e.response.data?.errors) {
            const errors = e.response.data.errors
            Object.entries(errors).forEach(([field, messages]) => {
                form.setError(field, (messages || []).join(' '))
            })
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
        <div class="dialog__title_header">
            <div class="dialog__title_container">
                <p class="dialog__title">
                    {{ capitalizeFirstLetter(langStore.translations.certificates || 'Сертификаты') }}
                </p>
                <div class="help-tt" aria-label="help" @click="showInfo = true">
                    <svg class="help-tt__icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="#000" />
                        <path d="M12 17v-5" stroke="#000" stroke-linecap="round"/>
                        <circle cx="12" cy="8" r="1" fill="#000"/>
                    </svg>
<!--                    <div class="tooltipSquare"></div>-->
<!--                    <div class="tooltip">-->
<!--                        <p>Это название мероприятия, отображаемое на карточке и странице хакатона</p>-->
<!--                    </div>-->
                </div>
                <InfoCertificates
                    v-model="showInfo"
                />
            </div>
            <div class="dialog__actions">
                <button class="main__btn main__btn_white" @click="downloadPreview">
                    {{ capitalizeFirstLetter(langStore.translations.downloadPreview || 'Скачать превью') }}
                </button>
            </div>
        </div>
        <DropFileHtml :file="file" @update:file="onFileUpdate" />
        <small v-if="form.errors.template" class="error__text">{{ form.errors.template }}</small>
    </div>

    <div class="dialog__btns" v-if="!isAdmin">
        <button class="main__btn main__btn_white" @click="cancel">
            {{ capitalizeFirstLetter(langStore.translations.cansel || 'Отмена') }}
        </button>
        <button class="main__btn" :disabled="!file || pending" @click="uploadTemplate">
            {{ pending ? (langStore.translations.saving || 'Сохранение...') : (langStore.translations.save || 'Сохранить') }}
        </button>
    </div>
</template>

<style scoped>
.certs { display: grid; gap: 12px; }
.dialog__actions { display: flex; gap: 8px; align-items: center; }
.hint.small { font-size: 12px; color: #888; }
</style>
