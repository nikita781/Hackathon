<script setup>
import DropFile from '../../DropFile.vue';
import {ref} from "vue";

const participationType = ref('Командный');
const presentType = ref('Денежный приз');
const selectedDirections = ref([]); // Массив для хранения выбранных направлений
const toggleDropdown = ref(false);

const directions = ['UX/UI', 'Тестировщики', 'Веб-дизайнеры'];

// Функция для открытия/закрытия выпадающего списка
const toggleDropdownVisibility = () => {
    toggleDropdown.value = !toggleDropdown.value;
};

// Функция для выбора направления
const selectDirection = (direction) => {
    const index = selectedDirections.value.indexOf(direction);
    if (index === -1) {
        selectedDirections.value.push(direction); // Добавить направление в массив
    } else {
        selectedDirections.value.splice(index, 1); // Удалить направление из массива
    }
};

const removeDirection = (direction, event) => {
    event.stopPropagation();``
    const index = selectedDirections.value.indexOf(direction);
    if (index !== -1) {
        selectedDirections.value.splice(index, 1);
    }
};

// Функция для очистки выбранных направлений
const clearSelection = () => {
    selectedDirections.value = []; // Очистить все выбранные направления
    toggleDropdown.value = false; // Закрыть список
};

const pickedFile = ref(null)
</script>

<template>
    <div class="dialog__component">
        <p class="dialog__title">Название хакатона</p>
        <input type="text" class="dialog__input" placeholder="Введите название">
    </div>
    <div class="dialog__block">
        <div class="dialog__component" :class="participationType === 'Командный' ? 'small' : 'medium'">
            <p class="dialog__title">Формат хакатона</p>
            <select class="main__cards_select dialog__select">
                <option>Онлайн</option>
                <option>Офлайн</option>
                <option>Смешанный</option>
            </select>
        </div>
        <div class="dialog__component" :class="participationType === 'Командный' ? 'small' : 'medium'">
            <p class="dialog__title">Тип участия</p>
            <select v-model="participationType" class="main__cards_select dialog__select">
                <option>Командный</option>
                <option>Индивидуальный</option>
            </select>
        </div>
        <div v-if="participationType === 'Командный'" class="dialog__component">
            <p class="dialog__title">Количество человек в команде</p>
            <div class="dialog__horizontal">
                <div class="dialog__info">
                    <p class="dialog__title">От</p>
                    <input type="number" class="dialog__input dialog__input_short" placeholder="Кол-во">
                </div>
                <div class="dialog__info">
                    <p class="dialog__title">До</p>
                    <input type="number" class="dialog__input dialog__input_short" placeholder="Кол-во">
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
                            <span>{{ direction }}</span>
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
                    <span>{{ option }}</span>
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
            <input type="date" id="datepicker" class="dialog__input" placeholder="Выберите дату" />
        </div>
        <div class="dialog__component medium">
            <p class="dialog__title">Дата проведения</p>
            <div class="dialog__horizontal">
                <div class="dialog__info">
                    <p class="dialog__title">От</p>
                    <input type="date" class="dialog__input dialog__input_medium" placeholder="Кол-во">
                </div>
                <div class="dialog__info">
                    <p class="dialog__title">До</p>
                    <input type="date" class="dialog__input dialog__input_medium" placeholder="Кол-во">
                </div>
            </div>
        </div>
    </div>
    <div class="dialog__block">
        <div class="dialog__component medium">
            <p class="dialog__title">Формат приза</p>
            <select v-model="presentType" class="main__cards_select dialog__select">
                <option>Денежный приз</option>
                <option>Призы</option>
            </select>
        </div>
        <div class="dialog__component medium">
            <p class="dialog__title">Призовой фонд</p>
            <input type="text" class="dialog__input" placeholder="Введите сумму или количество призов">
        </div>
    </div>
    <div class="dialog__component">
        <p class="dialog__title">Превью карточки хакатона</p>
        <DropFile v-model:file="pickedFile" />
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
}

.dropdown-item {
    padding: 10px 15px;
    color: #E80024;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
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
    @media screen and (max-width: $small){
        font-size: 14px;
    }
}

.clear-selection:hover {
    background-color: #f8f8f8;
}
</style>
