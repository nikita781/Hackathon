<script setup>
import {reactive, watch, onBeforeUnmount, toRaw, onMounted, ref, computed} from 'vue'
import DropFile from "@/Components/DropFile.vue";
import {useLangStore} from "@/store/lang.js";
import CustomSelect from "@/Components/CustomSelect.vue";

const props = defineProps({
    modelValue : Boolean,
    hackathonSlug : { type:String, required:true },
    initial    : { type:Object, default:null },
    defaultType   : { type:String, default:'forAll' }
})
const emit = defineEmits(['update:modelValue','saved'])

const langStore = useLangStore()
const previousBodyOverflow = ref('')

watch(
    () => props.modelValue,
    (opened) => {
        if (opened) {
            previousBodyOverflow.value = document.body.style.overflow
            document.body.style.overflow = 'hidden'
        } else {
            document.body.style.overflow = previousBodyOverflow.value || ''
        }
    }
)

onBeforeUnmount(() => {
    document.body.style.overflow = previousBodyOverflow.value || ''
})

const blank = {
    id     : null,
    type   : 'forAll',
    place  : '',
    title  : '',
    text   : '',
    file   : null,
    imgUrl : ''
}
const form = reactive({ ...blank })

const saving = ref(false)
const errors = ref({})

function revoke () {
    if (form.imgUrl && form.imgUrl.startsWith('blob:')) URL.revokeObjectURL(form.imgUrl)
}

const getImgSrc = (aw) => {
    if (aw._localBlob) return aw._localBlob
    const ver = aw.updated_at ?? Date.now()
    return `${route('awards.image', { award: aw.id })}?v=${ver}`
}

watch([() => props.initial, () => props.defaultType], ([val, def]) => {
    revoke()
    if (!val) {
        Object.assign(form, { ...blank, type: def })
        return
    }
    Object.assign(form, {
        id    : val.id,
        type  : val.for_all ? 'forAll' : 'forPrize',
        place : val.place   ?? '',
        title : val.title,
        text  : val.description ?? '',
        file  : null,
        imgUrl: getImgSrc(val)
    })
},{ immediate:true })
onBeforeUnmount(revoke)

function close () { emit('update:modelValue', false) }

function onFileChange (f) {
    if (!f || !f.type.startsWith('image/')) return
    revoke()
    form.file = f
    form.imgUrl = URL.createObjectURL(f)
    if (props.initial) props.initial._localBlob = form.imgUrl
}

async function save () {
    errors.value = {}
    const fd = new FormData()
    fd.append('title',       form.title.trim())
    fd.append('description', form.text.trim())
    fd.append('for_all', form.type === 'forAll' ? 1 : 0)
    fd.append('place',       form.type === 'forPrize' ? (form.place || '') : '')
    fd.append('system', 0)
    if (form.file) fd.append('image', form.file)

    try {
        saving.value = true
        if (form.id) {
            fd.append('_method','PATCH')
            await axios.post(
                route('hackathons.awards.update', { hackathon: props.hackathonSlug, award: form.id }),
                fd, { headers:{ 'Content-Type':'multipart/form-data' } }
            )
        } else {
            await axios.post(
                route('hackathons.awards.store',  { hackathon: props.hackathonSlug } ),
                fd, { headers:{ 'Content-Type':'multipart/form-data' } }
            )
        }
        emit('saved')
        close()
    } catch (e) {
        if (e.response?.status === 422) {
            const errs = e.response.data?.errors || {}
            for (const k in errs) { const base = k.split('.')[0]; if (!errs[base]) errs[base] = errs[k] }
            errors.value = errs
            console.table(e.response.data.errors)
        }
        else console.error('award-save',e)
    } finally {
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

const sortOptions = computed(() => [
    {
        value: 'forAll',
        label: `${capitalizeFirstLetter(langStore.translations.for_all)} ↓`,
    },
    {
        value: 'forPrize',
        label: `${capitalizeFirstLetter(langStore.translations.for_prize_places)} ↑`,
    },
])
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:3" @click.self="close">
        <div class="dialog__container_custom dialog__container_small" @click.stop>
            <div class="dialog__inner" :class="{ 'is-saving': saving }">
                <div class="dialog__header">
                    <p>{{ props.initial ? capitalizeFirstLetter(langStore.translations.edit_award) : capitalizeFirstLetter(langStore.translations.addAward) }}</p>
                    <div class="dialog__close" @click="close">
                        <svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M7.90994 6.00019L12.2099 1.71019C12.3982 1.52188 12.504 1.26649 12.504 1.00019C12.504 0.733884 12.3982 0.478489 12.2099 0.290185C12.0216 0.101882 11.7662 -0.00390625 11.4999 -0.00390625C11.2336 -0.00390625 10.9782 0.101882 10.7899 0.290185L6.49994 4.59019L2.20994 0.290185C2.02164 0.101882 1.76624 -0.00390625 1.49994 -0.00390625C1.23364 -0.00390625 0.978243 0.101882 0.789939 0.290185C0.601635 0.478489 0.495847 0.733884 0.495847 1.00019C0.495847 1.26649 0.601635 1.52188 0.789939 1.71019L5.08994 6.00019L0.789939 10.2902C0.696211 10.3831 0.621816 10.4937 0.571048 10.6156C0.520279 10.7375 0.494141 10.8682 0.494141 11.0002C0.494141 11.1322 0.520279 11.2629 0.571048 11.3848C0.621816 11.5066 0.696211 11.6172 0.789939 11.7102C0.882902 11.8039 0.993503 11.8783 1.11536 11.9291C1.23722 11.9798 1.36793 12.006 1.49994 12.006C1.63195 12.006 1.76266 11.9798 1.88452 11.9291C2.00638 11.8783 2.11698 11.8039 2.20994 11.7102L6.49994 7.41019L10.7899 11.7102C10.8829 11.8039 10.9935 11.8783 11.1154 11.9291C11.2372 11.9798 11.3679 12.006 11.4999 12.006C11.632 12.006 11.7627 11.9798 11.8845 11.9291C12.0064 11.8783 12.117 11.8039 12.2099 11.7102C12.3037 11.6172 12.3781 11.5066 12.4288 11.3848C12.4796 11.2629 12.5057 11.1322 12.5057 11.0002C12.5057 10.8682 12.4796 10.7375 12.4288 10.6156C12.3781 10.4937 12.3037 10.3831 12.2099 10.2902L7.90994 6.00019Z" fill="#999999"/>
                        </svg></div>
                </div>

                <div class="dialog__component">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.award_type) }}</p>
                    <CustomSelect
                        v-model="form.type"
                        :options="sortOptions"
                        close-by-scroll
                    />
                </div>

                <div class="dialog__component" v-if="form.type === 'forPrize'">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.place) }}</p>
                    <input type="number" min="1" v-model="form.place" class="dialog__input" :placeholder="capitalizeFirstLetter(langStore.translations.enter_place)"/>
                    <span v-if="errors.place" class="error__text">{{ errors.place[0] }}</span>
                </div>

                <div class="dialog__component">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.awardTitle) }}</p>
                    <input v-model="form.title" class="dialog__input" :placeholder="capitalizeFirstLetter(langStore.translations.enterAwardTitle)"/>
                    <span v-if="errors.title" class="error__text">{{ errors.title[0] }}</span>
                </div>

                <div class="dialog__component">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.awardDescription) }}</p>
                    <textarea v-model="form.text" class="dialog__textarea" :placeholder="capitalizeFirstLetter(langStore.translations.enterAwardDescription)"/>
                    <span v-if="errors.description" class="error__text">{{ errors.description[0] }}</span>
                </div>

                <div class="dialog__component">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.image) }}</p>
                    <DropFile :file="form.file ?? form.imgUrl" @update:file="onFileChange"/>
                    <span v-if="errors.image" class="error__text">{{ errors.image[0] }}</span>
                </div>

                <div class="dialog__btns">
                    <button class="main__btn main__btn_white dialog__btn" @click="close">
                        {{ capitalizeFirstLetter(langStore.translations.cansel) }}
                    </button>
                    <button class="main__btn dialog__btn" @click="save">
                        {{ props.initial ? capitalizeFirstLetter(langStore.translations.update) : capitalizeFirstLetter(langStore.translations.add) }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dialog { position: fixed; inset: 0; }

.dialog__container { position: relative; }

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
