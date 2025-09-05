<script setup>
import { ref, reactive } from "vue";

const props = defineProps({
    modelValue: Boolean,
});
const emit = defineEmits(["update:modelValue"]);
function close() { emit("update:modelValue", false) }

// только hover (для клика можно будет добавить отдельно)
const hover = reactive({
    relevance: 0,        // Актуальность темы
    novelty: 0,          // Научно-техническая новизна
    attainability: 0,    // Достижимость результатов проекта

    advantage: 0,        // Конкурентные преимущества
    demand: 0,           // Востребованность на рынке

    entrepreneurship: 0, // Предпринимательский потенциал
    passion: 0,          // Увлеченность идеей
})

function setHover(key, n)   { hover[key] = n }
function clearHover(key)    { hover[key] = 0 }
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:2">
        <div class="dialog__container" @click.stop>
            <div class="dialog__header">
                <p>Оценка проекта</p>
                <div class="dialog__close" @click="close"><svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7.90994 6.00019L12.2099 1.71019C12.3982 1.52188 12.504 1.26649 12.504 1.00019C12.504 0.733884 12.3982 0.478489 12.2099 0.290185C12.0216 0.101882 11.7662 -0.00390625 11.4999 -0.00390625C11.2336 -0.00390625 10.9782 0.101882 10.7899 0.290185L6.49994 4.59019L2.20994 0.290185C2.02164 0.101882 1.76624 -0.00390625 1.49994 -0.00390625C1.23364 -0.00390625 0.978243 0.101882 0.789939 0.290185C0.601635 0.478489 0.495847 0.733884 0.495847 1.00019C0.495847 1.26649 0.601635 1.52188 0.789939 1.71019L5.08994 6.00019L0.789939 10.2902C0.696211 10.3831 0.621816 10.4937 0.571048 10.6156C0.520279 10.7375 0.494141 10.8682 0.494141 11.0002C0.494141 11.1322 0.520279 11.2629 0.571048 11.3848C0.621816 11.5066 0.696211 11.6172 0.789939 11.7102C0.882902 11.8039 0.993503 11.8783 1.11536 11.9291C1.23722 11.9798 1.36793 12.006 1.49994 12.006C1.63195 12.006 1.76266 11.9798 1.88452 11.9291C2.00638 11.8783 2.11698 11.8039 2.20994 11.7102L6.49994 7.41019L10.7899 11.7102C10.8829 11.8039 10.9935 11.8783 11.1154 11.9291C11.2372 11.9798 11.3679 12.006 11.4999 12.006C11.632 12.006 11.7627 11.9798 11.8845 11.9291C12.0064 11.8783 12.117 11.8039 12.2099 11.7102C12.3037 11.6172 12.3781 11.5066 12.4288 11.3848C12.4796 11.2629 12.5057 11.1322 12.5057 11.0002C12.5057 10.8682 12.4796 10.7375 12.4288 10.6156C12.3781 10.4937 12.3037 10.3831 12.2099 10.2902L7.90994 6.00019Z" fill="#999999"/></svg></div>
            </div>

            <div class="dialog__component" style="margin-top:-10px; gap:40px">
                <div class="dialog__prize">
                    <div class="dialog__eva_container">
                        <p class="dialog__eva">Научно-технический уровень проекта</p>
                    </div>

                    <div class="dialog__eva_item">
                        <p class="dialog__eva_title">Актуальность темы</p>
                        <div class="dialog__eva_number">
                            <p v-for="n in 10" :key="n"
                               @mouseenter="setHover('relevance', n)"
                               @mouseleave="clearHover('relevance')"
                               :class="{ active: n <= hover.relevance }">{{ n }}</p>
                        </div>
                    </div>

                    <div class="dialog__eva_item">
                        <p class="dialog__eva_title">Научно-техническая новизна</p>
                        <div class="dialog__eva_number">
                            <p v-for="n in 10" :key="n"
                               @mouseenter="setHover('novelty', n)"
                               @mouseleave="clearHover('novelty')"
                               :class="{ active: n <= hover.novelty }">{{ n }}</p>
                        </div>
                    </div>

                    <div class="dialog__eva_item">
                        <p class="dialog__eva_title">Достижимость результатов проекта</p>
                        <div class="dialog__eva_number">
                            <p v-for="n in 10" :key="n"
                               @mouseenter="setHover('attainability', n)"
                               @mouseleave="clearHover('attainability')"
                               :class="{ active: n <= hover.attainability }">{{ n }}</p>
                        </div>
                    </div>
                </div>

                <div class="dialog__prize">
                    <div class="dialog__eva_container">
                        <p class="dialog__eva">Перспективы коммерциализации продукта</p>
                    </div>

                    <div class="dialog__eva_item">
                        <p class="dialog__eva_title">Конкурентные преимущества</p>
                        <div class="dialog__eva_number">
                            <p v-for="n in 10" :key="n"
                               @mouseenter="setHover('advantage', n)"
                               @mouseleave="clearHover('advantage')"
                               :class="{ active: n <= hover.advantage }">{{ n }}</p>
                        </div>
                    </div>

                    <div class="dialog__eva_item">
                        <p class="dialog__eva_title">Востребованность на рынке</p>
                        <div class="dialog__eva_number">
                            <p v-for="n in 10" :key="n"
                               @mouseenter="setHover('demand', n)"
                               @mouseleave="clearHover('demand')"
                               :class="{ active: n <= hover.demand }">{{ n }}</p>
                        </div>
                    </div>
                </div>

                <div class="dialog__prize">
                    <div class="dialog__eva_container">
                        <p class="dialog__eva">Квалификация заявителя</p>
                    </div>

                    <div class="dialog__eva_item">
                        <p class="dialog__eva_title">Предпринимательский потенциал</p>
                        <div class="dialog__eva_number">
                            <p v-for="n in 10" :key="n"
                               @mouseenter="setHover('entrepreneurship', n)"
                               @mouseleave="clearHover('entrepreneurship')"
                               :class="{ active: n <= hover.entrepreneurship }">{{ n }}</p>
                        </div>
                    </div>

                    <div class="dialog__eva_item">
                        <p class="dialog__eva_title">Увлеченность идеей</p>
                        <div class="dialog__eva_number">
                            <p v-for="n in 10" :key="n"
                               @mouseenter="setHover('passion', n)"
                               @mouseleave="clearHover('passion')"
                               :class="{ active: n <= hover.passion }">{{ n }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dialog__btns">
                <button class="main__btn main__btn_white dialog__btn" @click="close">Отменить</button>
                <button class="main__btn dialog__btn">Оценить</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
</style>
