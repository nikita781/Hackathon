<script setup>
import DropFile from '../../DropFile.vue';
import {ref, toRaw, watch} from "vue";
import { useForm, router } from '@inertiajs/vue3'
import logs from "../../../../../vendor/laravel/telescope/resources/js/screens/logs/index.vue";

const props = defineProps({
    draft    : Object,
    allTags  : { type:Array, default:() => [] }
})
const emit = defineEmits(['saved', 'cancel', 'dirty'])

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

async function save () {
    form.clearErrors()

    const data = form.data()

    const fd = new FormData()

    Object.entries(data).forEach(([key, value]) => {
        if (key === 'tags') {
            toRaw(value).forEach(id => fd.append('tags[]', id))
            return
        }

        if (key === 'image_path' && value instanceof File) {
            fd.append('image_path', value)
            return
        }

        if (key === 'min_team_size' || key === 'max_team_size') {
            if (form.type === 'team') {
                fd.append(key, value);
            } else {
                fd.append(key, 1);
            }
            return;
        }

        fd.append(key, value ?? '')
    })
    // console.log('FD →', [...fd.entries()].map(([k, v]) => [k, v instanceof File ? v.name : v]));

    try {
        const { data: res } = await axios.post(
            route('hackathons.store'),
            fd,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        )

        props.draft.slug = res.hackathon.slug
        emit('saved', { slug: res.hackathon.slug })
    } catch (e) {
        if (e.response?.status === 422) {
            // form.clearErrors()

            const errors = e.response.data.errors
            Object.entries(errors).forEach(([field, messages]) => {
                form.setError(field, messages.join(' '))
            })

            console.table(errors)
        } else {

            console.error('hackathon-create', e)
        }
    }
}

const participationType = ref('Командный');
const presentType = ref('Денежный приз');
const selectedDirections = ref([]); // Массив для хранения выбранных направлений
const toggleDropdown = ref(false);

const directions = props.allTags

const toggleDropdownVisibility = () => { toggleDropdown.value = !toggleDropdown.value }

// Функция для выбора направления
const selectDirection = (tag) => {
    const idx = selectedDirections.value.findIndex(t => t.id === tag.id)
    idx === -1
        ? selectedDirections.value.push(tag)
        : selectedDirections.value.splice(idx, 1)
}

const removeDirection = (tag, e) => {
    e.stopPropagation()
    const idx = selectedDirections.value.findIndex(t => t.id === tag.id)
    if (idx !== -1) selectedDirections.value.splice(idx, 1)
}
watch(
    selectedDirections,
    arr   => { form.tags = arr.map(t => t.id) },
    { immediate:true, deep:true }
)

const clearSelection = () => { selectedDirections.value = []; toggleDropdown.value = false }
defineExpose({ save })

const resetState = () => {
    form.reset()
    participationType.value = 'Командный'
    presentType.value       = 'Денежный приз'
    selectedDirections.value = []
    toggleDropdown.value     = false
}

function cancel () {
    resetState()
    emit('cancel')
}
</script>

<template>
    <div class="dialog__component">
        <p class="dialog__title">Название хакатона</p>
        <input v-model="form.title" type="text" class="dialog__input" placeholder="Введите название" :class="{ 'is-invalid': form.errors.title }">
        <small v-if="form.errors.title" class="error">{{ form.errors.title }}</small>
    </div>
    <div class="dialog__block">
        <div class="dialog__component" :class="form.type === 'team' ? 'small' : 'medium'">
            <p class="dialog__title">Формат хакатона</p>
            <select class="main__cards_select dialog__select" v-model="form.format">
                <option value="online">Онлайн</option>
                <option value="offline">Офлайн</option>
                <option value="hybrid">Смешанный</option>
            </select>
        </div>
        <div class="dialog__component" :class="form.type === 'team' ? 'small' : 'medium'">
            <p class="dialog__title">Тип участия</p>
            <select v-model="form.type" class="main__cards_select dialog__select">
                <option value="team">Командный</option>
                <option value="individual">Индивидуальный</option>
            </select>
        </div>
        <div v-if="form.type === 'team'" class="dialog__component">
            <p class="dialog__title">Количество человек в команде</p>
            <div class="dialog__horizontal">
                <div class="dialog__info">
                    <p class="dialog__title">От</p>
                    <input v-model.number="form.min_team_size" type="number" class="dialog__input dialog__input_short" placeholder="Кол-во">
                </div>
                <div class="dialog__info">
                    <p class="dialog__title">До</p>
                    <input v-model.number="form.max_team_size" type="number" class="dialog__input dialog__input_short" placeholder="Кол-во">
                </div>
            </div>
        </div>
    </div>
    <div class="dialog__component">
        <p class="dialog__title">Направления</p>
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
            <p class="dialog__title">Последний день регистрации</p>
            <input v-model="form.registration_end" type="datetime-local" id="datepicker" class="dialog__input" placeholder="Выберите дату" />
        </div>
        <div class="dialog__component large">
            <p class="dialog__title">Дата проведения</p>
            <div class="dialog__horizontal">
                <div class="dialog__info">
                    <p class="dialog__title">От</p>
                    <input v-model="form.event_start" type="datetime-local" class="dialog__input dialog__input_medium" placeholder="Кол-во">
                </div>
                <div class="dialog__info">
                    <p class="dialog__title">До</p>
                    <input v-model="form.event_end" type="datetime-local" class="dialog__input dialog__input_medium" placeholder="Кол-во">
                </div>
            </div>
        </div>
    </div>
    <div class="dialog__block">
        <div class="dialog__component medium">
            <p class="dialog__title">Формат приза</p>
            <select v-model="form.prize_type" class="main__cards_select dialog__select">
                <option value="cash">Денежный приз</option>
                <option value="non-cash">Призы</option>
            </select>
        </div>
        <div class="dialog__component medium">
            <p class="dialog__title">Призовой фонд</p>
            <input v-model="form.prize_pool" type="text" class="dialog__input" placeholder="Введите сумму или количество призов">
        </div>
    </div>
    <div class="dialog__component">
        <p class="dialog__title">Превью карточки хакатона</p>
        <DropFile v-model:file="form.image_path" />
    </div>
    <div class="dialog__btns">
        <button class="main__btn main__btn_white" @click="cancel">Отменить</button>
        <button class="main__btn" @click="save">Сохранить</button>
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
