<script setup>
import DropFile   from '../../DropFile.vue'
import {ref, toRaw, watch, onMounted, nextTick, onBeforeUnmount, computed} from 'vue'
import { useForm } from '@inertiajs/vue3'
import {useLangStore} from "@/store/lang.js";

const props = defineProps({
    hackathonSlug : { type:String, required:true },
    draft         : Object,
    allTags       : { type:Array,  default:() => [] },
    isEdit   : { type:Boolean, default:false },
    admin      : { type:Boolean, default:() => false },
})
const emit = defineEmits(['saved','cancel','dirty'])

const isAdmin = computed(() => !!props.admin)

const loaded = ref(false)
const dirty = ref(false)
const langStore = useLangStore()

const form = useForm({
    title            : '',
    image_path       : null,
    format           : 'online',
    type             : 'team',
    min_team_size    : null,
    max_team_size    : null,
    registration_end : '',
    event_start      : '',
    event_end        : '',
    prize_type       : 'cash',
    prize_pool       : '',
    tags             : [],
})

const previewUrl = ref(null)

const participationType  = ref('Командный')
const presentType        = ref('Денежный приз')
const selectedDirections = ref([])
const toggleDropdown     = ref(false)
const directions         = props.allTags

const toggleDropdownVisibility = () => toggleDropdown.value = !toggleDropdown.value

function selectDirection(tag){
    const idx = selectedDirections.value.findIndex(t=>t.id===tag.id)
    idx === -1
        ? selectedDirections.value.push(tag)
        : selectedDirections.value.splice(idx,1)
}
function removeDirection(tag,e){
    e.stopPropagation()
    const idx = selectedDirections.value.findIndex(t=>t.id===tag.id)
    if(idx!==-1) selectedDirections.value.splice(idx,1)
}
function clearSelection(){
    selectedDirections.value = []
    toggleDropdown.value = false
}
watch(selectedDirections, arr=>{
    form.tags = arr.map(t=>t.id)
},{immediate:true,deep:true})

function revokePreview () {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = null
}

onMounted(async () => {
    try{
        const { data } = await axios.get(
            route('hackathons.show', { hackathon:props.hackathonSlug }),
        )
        const { data: blob } = await axios.get(
            route('hackathons.image', { hackathon: props.hackathonSlug }),
            { responseType: 'blob' }
        )
        const h = data.hackathon.original
        previewUrl.value = URL.createObjectURL(blob)

        form.title             = h.title
        form.format            = h.format
        form.type              = h.type
        form.min_team_size     = h.min_team_size
        form.max_team_size     = h.max_team_size
        form.registration_end  = h.registration_end?.slice(0,16) ?? ''
        form.event_start       = h.event_start?.slice(0,16) ?? ''
        form.event_end         = h.event_end?.slice(0,16) ?? ''
        form.prize_type        = h.prize_type
        form.prize_pool        = h.prize_pool
        form.image_path        = previewUrl.value

        participationType.value= h.type==='team' ? 'Командный':'Индивидуальный'
        presentType.value      = h.prize_type==='cash' ? 'Денежный приз':'Призы'

        selectedDirections.value = h.tags ?? []
        await nextTick()
        loaded.value = true
    }catch(e){
        console.error('hackathon-load',e?.response??e)
    }
})

watch(
    () => form.image_path,
    file => {
        if (!file) revokePreview()
    }
)

watch(
    [form],
    () => {
        if (!loaded.value) return

        if (!dirty.value) {
            dirty.value = true
            emit('dirty', true)
        }
    },
    { deep:true }
)

async function save(){
    form.clearErrors()

    const fd = new FormData()
    const data = form.data()

    toRaw(data.tags).forEach(id => fd.append('tags[]', id))

    Object.entries(data).forEach(([k, v]) => {
        if (k === 'tags' || k === 'image_path') return
        if (k === 'min_team_size' || k === 'max_team_size') {
            if (form.type === 'team') {
                fd.append(k, v);
            } else {
                fd.append(k, 1);
            }
            return;
        }
        fd.append(k, v ?? '')
    })
    if (form.image_path instanceof File) {
        fd.append('image_path', form.image_path)
    }
    fd.append('_method','PATCH')

    try{
        await axios.post(
            route('hackathons.update', { hackathon:props.hackathonSlug }),
            fd, { headers:{'Content-Type':'multipart/form-data'} }
        )
        dirty.value = false
        loaded.value = true
        emit('dirty', false)
        emit('saved',{ slug:props.hackathonSlug })
    }catch(e){
        if (e.response?.status === 422) {
            const errors = e.response.data.errors
            Object.entries(errors).forEach(([field, messages]) => {
                form.setError(field, messages.join(' '))
            })

            console.table(errors)
        } else {

            console.error('hackathon-update', e)
        }
    }
}

function resetState(){
    form.reset()
    participationType.value  = 'Командный'
    presentType.value        = 'Денежный приз'
    selectedDirections.value = []
    toggleDropdown.value     = false
}
function cancel(){
    resetState()
    emit('cancel')
}

defineExpose({ save })
onBeforeUnmount(revokePreview)

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

onMounted(async () => {
    await langStore.fetchTranslations()
});
</script>

<template>
    <div class="dialog__component">
        <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.hackathon_title) }}</p>
        <input
            v-model="form.title"
            type="text"
            class="dialog__input"
            :placeholder="capitalizeFirstLetter(langStore.translations.enter_title)"
        >
        <small v-if="form.errors.title" class="error">{{ form.errors.title }}</small>
    </div>
    <div class="dialog__block">
        <div class="dialog__component" :class="form.type === 'team' ? 'small' : 'medium'">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.hackathon_format) }}</p>
            <select class="main__cards_select dialog__select" v-model="form.format">
                <option value="online">{{ capitalizeFirstLetter(langStore.translations.online) }}</option>
                <option value="offline">{{ capitalizeFirstLetter(langStore.translations.offline) }}</option>
                <option value="hybrid">{{ capitalizeFirstLetter(langStore.translations.hybrid) }}</option>
            </select>
        </div>
        <div class="dialog__component" :class="form.type === 'team' ? 'small' : 'medium'">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.participation_type) }}</p>
            <select v-model="form.type" class="main__cards_select dialog__select">
                <option value="team">{{ capitalizeFirstLetter(langStore.translations.team_type) }}</option>
                <option value="individual">{{ capitalizeFirstLetter(langStore.translations.individual_type) }}</option>
            </select>
        </div>
        <div v-if="form.type === 'team'" class="dialog__component">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.team_size) }}</p>
            <div class="dialog__horizontal">
                <div class="dialog__info">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.from) }}</p>
                    <input v-model.number="form.min_team_size" type="number" class="dialog__input dialog__input_short" :placeholder="capitalizeFirstLetter(langStore.translations.amount)">
                </div>
                <div class="dialog__info">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.to) }}</p>
                    <input v-model.number="form.max_team_size" type="number" class="dialog__input dialog__input_short" :placeholder="capitalizeFirstLetter(langStore.translations.amount)">
                </div>
            </div>
        </div>
    </div>
    <div class="dialog__component">
        <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.categories_plural) }}</p>
        <div class="custom-container">
            <div class="custom-select" @click="toggleDropdownVisibility">
                <div class="selected-option">
                    <span class="dialog__tag_placeholder" v-if="!selectedDirections.length">{{ selectedDirections.length > 0 ? '' : 'Выберите направления' }}</span>
                    <div class="dialog__tags" v-if="selectedDirections.length > 0">
                        <div v-for="(direction, index) in selectedDirections" :key="index" class="dialog__tag" @click="removeDirection(direction, $event)">
                            <span>{{ direction.title }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 -960 960 960" width="16px" fill="#1f1f1f"><path fill="#000" d="m291-240-51-51 189-189-189-189 51-51 189 189 189-189 51 51-189 189 189 189-51 51-189-189-189 189Z"/></svg>
                        </div>
                    </div>
                    <svg width="8" height="6" viewBox="0 0 8 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 1L4 4L7 1" stroke="#E80024" stroke-width="1.5"/>
                    </svg>
                </div>
            </div>
            <div v-if="toggleDropdown" class="dropdown-options">
                <div
                    v-for="(option, index) in directions"
                    :key="index"
                    class="dropdown-item"
                    @click="selectDirection(option)"
                >
                    <span>{{ option.title }}</span>
                    <span v-if="selectedDirections.includes(option)" class="selected-mark">✔</span>
                </div>
                <div v-if="selectedDirections.length > 0" class="clear-selection" @click="clearSelection">
                    <span>Очистить</span>
                </div>
            </div>
        </div>
    </div>
    <div class="dialog__block">
        <div class="dialog__component medium">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.registration_deadline) }}</p>
            <input v-model="form.registration_end" type="datetime-local" id="datepicker" class="dialog__input" placeholder="Выберите дату" />
        </div>
        <div class="dialog__component large">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.event_date) }}</p>
            <div class="dialog__horizontal">
                <div class="dialog__info">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.from) }}</p>
                    <input v-model="form.event_start" type="datetime-local" class="dialog__input dialog__input_medium" placeholder="Кол-во">
                </div>
                <div class="dialog__info">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.to) }}</p>
                    <input v-model="form.event_end" type="datetime-local" class="dialog__input dialog__input_medium" placeholder="Кол-во">
                </div>
            </div>
        </div>
    </div>
    <div class="dialog__block">
        <div class="dialog__component medium">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.prize_format) }}</p>
            <select v-model="form.prize_type" class="main__cards_select dialog__select">
                <option value="cash">{{ capitalizeFirstLetter(langStore.translations.money_prize) }}</option>
                <option value="non-cash">{{ capitalizeFirstLetter(langStore.translations.item_prize) }}</option>
            </select>
        </div>
        <div class="dialog__component large">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.prize_fund) }}</p>
            <input v-model="form.prize_pool" type="text" class="dialog__input" :placeholder="capitalizeFirstLetter(langStore.translations.enter_prize_hint)">
        </div>
    </div>
    <div class="dialog__component">
        <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.hackathon_card_preview) }}</p>
        <DropFile v-model:file="form.image_path"/>
    </div>
    <div class="dialog__btns" v-if="!isAdmin">
        <button class="main__btn main__btn_white" @click="cancel">{{ capitalizeFirstLetter(langStore.translations.cansel) }}</button>
        <button class="main__btn" @click="save">{{ capitalizeFirstLetter(langStore.translations.save) }}</button>
    </div>
</template>

<style scoped lang="scss">
$x-small: 575.98px;
$small: 767.98px;
$medium: 991.98px;
$large: 1199.98px;
$x-large: 1399.98px;
$big: 1592.98px;
$x-big: 1829.98px;

.custom-container {
    display: flex;
    flex-direction: column;
}

.custom-select {
    border: 1px solid #E80024;
    border-radius: 100px;
    padding: 8px 16px;
    background-color: #fff;
    cursor: pointer;
}

.selected-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dropdown-options {
    background-color: #fff;
    border: 1px solid #E80024;
    z-index: 2;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-radius: 20px;
}

.dropdown-item {
    padding: 10px 15px;
    color: #E80024;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 20px;
    @media screen and (max-width: $small){
        font-size: 14px;
    }
}

.dropdown-item:hover {
    background-color: #f8f8f8;
}

.selected-mark {
    color: #E80024;
}

.clear-selection {
    padding: 10px 15px;
    background-color: #fff;
    border-top: 1px solid #E80024;
    text-align: center;
    color: #E80024;
    cursor: pointer;
    border-bottom-left-radius: 20px;
    border-bottom-right-radius: 20px;
    @media screen and (max-width: $small){
        font-size: 14px;
    }
}

.clear-selection:hover {
    background-color: #f8f8f8;
}
</style>
