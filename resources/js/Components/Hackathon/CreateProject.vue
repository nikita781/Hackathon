<script setup>
import CheckMenu from "@/Components/Icons/CheckMenu.vue";
import {defineAsyncComponent, ref} from 'vue';
import Step1 from "@/Components/Hackathon/Steps/Step1.vue";
import Step2 from "@/Components/Hackathon/Steps/Step2.vue";
import Step3 from "@/Components/Hackathon/Steps/Step3.vue";
import Step4 from "@/Components/Hackathon/Steps/Step4.vue";

const props = defineProps({
    hackathonSlug: String,
    teamId:        Number,
    modelValue: Boolean,
    oneProject:    { type: Object, default: null },
})

const step = ref(1);
const project  = ref(null)
const tabs = [Step1, Step2, Step3, Step4];

const nextStep = () => {
    if (step.value < 5) {
        step.value++;
    }
};

const goToStep = (targetStep) => {
    if (targetStep <= step.value) {
        step.value = targetStep;
    }
};

const emit  = defineEmits(['update:modelValue'])

function close () {
    resetDialog()
    emit('update:modelValue', false)
}

function resetDialog () {
    step.value = 1;
    project.value = null;
}

const handleSuccess = (data) => {
    if (step.value === 1) {
        onStep1Success(data);
    } else if (step.value === 2) {
        onStep2Success();
    } else if (step.value === 3) {
        onStep2Success();
    }
};

const onStep1Success = (data) => {project.value = data; nextStep() }
const onStep2Success = ()      => { nextStep() }
</script>

<template>
    <div class="project__title">{{ project ? project.title : 'Название проекта' }}</div>
<!--    <pre>{{oneProject}}</pre>-->
    <div class="project__container">
        <div class="project__menu">
            <div class="project__menu_item" @click="goToStep(1)">
                <p class="project__menu_text">Основная информация</p>
                <div>
                    <div class="project__menu_btn active" :class="{'completed': step > 1}">
                        <CheckMenu />
                    </div>
                </div>
            </div>
            <div class="project__menu_line_container">
                <div class="project__menu_line" :class="{'active': step > 1}"></div>
            </div>
            <div class="project__menu_item" @click="goToStep(2)">
                <p class="project__menu_text">Описание</p>
                <div>
                    <div class="project__menu_btn" :class="{'active': step > 1, 'completed': step > 2}">
                        <CheckMenu />
                    </div>
                </div>
            </div>
            <div class="project__menu_line_container">
                <div class="project__menu_line" :class="{'active': step > 2}"></div>
            </div>
            <div class="project__menu_item" @click="goToStep(3)">
                <p class="project__menu_text">Материалы</p>
                <div>
                    <div class="project__menu_btn" :class="{'active': step > 2, 'completed': step > 3}">
                        <CheckMenu />
                    </div>
                </div>
            </div>
            <div class="project__menu_line_container">
                <div class="project__menu_line" :class="{'active': step > 3}"></div>
            </div>
            <div class="project__menu_item" @click="goToStep(4)">
                <p class="project__menu_text">Отправка</p>
                <div>
                    <div class="project__menu_btn" :class="{'active': step > 3, 'completed': step > 4}">
                        <CheckMenu />
                    </div>
                </div>
            </div>
        </div>

        <div class="project__content">
<!--            <pre>{{project}}</pre>-->
            <keep-alive>
                <component
                    :is="tabs[step - 1]"
                    :hackathon-slug="props.hackathonSlug"
                    :team-id="props.teamId"
                    :project="project"
                    :oneProject="oneProject"
                    @success="handleSuccess"
                    @cancel="close"
                />
            </keep-alive>
        </div>
    </div>
</template>

<style scoped>

</style>
