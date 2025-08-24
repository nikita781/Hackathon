<script setup>
import Hyperlink from "@/Components/Icons/Hyperlink.vue";
import Document from "@/Components/Icons/Document.vue";

const props = defineProps({
    positions : { type: Array,   default : () => [] },
    ownTeam : { type: Array,   default : () => [] },
    hackathon : { type: Array,   default : () => [] },
    tabs: { type: Array,   default : () => [] },
    allProjects: { type: Array,   default : () => [] },
})
</script>

<template>
    <div class="hackathon__tab">
        <div class="hackathon__tab_main">
            <div class="hackathon__tab_container">
                <p class="hackathon__my-project__title">Edugame</p>
                <div class="hackathon__oneProject_image">
                    <img src="/project.jpg" alt="">
                </div>
                <p class="">Образовательная игра для детей, которая сочетает в себе веселье и обучение. Игра включает в себя различные уровни, где дети могут изучать математику, науку и языки в интерактивной форме.</p>
            </div>
            <div class="hackathon__tab_container">
                <p class="hackathon__my-project__title">Описание</p>
                <p class="">Образовательная игра для детей, которая сочетает в себе веселье и обучение. Игра включает в себя различные уровни, где дети могут изучать математику, науку и языки в интерактивной форме. Игра предлагает увлекательные задания и головоломки, которые помогают развивать критическое мышление и творческие способности. Каждый уровень содержит уникальные миссии, которые адаптируются к возрасту и уровню знаний ребенка. Дети могут зарабатывать баллы и награды, что делает процесс обучения более мотивирующим. Игра также включает в себя элементы сотрудничества, позволяя детям работать в командах и обмениваться знаниями.</p>
            </div>
            <div class="hackathon__tab_container">
                <p class="hackathon__my-project__title">Технологический стек проекта</p>
                <p class="">C#, Unity, Adobe Photoshop</p>
            </div>
            <div class="hackathon__tab_container">
                <p class="hackathon__my-project__title">Галерея проекта</p>
                <div class="hackathon__oneProject_gallery">
                    <img src="/project.jpg" alt="">
                    <img src="/project.jpg" alt="">
                    <img src="/project.jpg" alt="">
                    <img src="/project.jpg" alt="">
                </div>
            </div>
            <div class="hackathon__tab_container">
                <p class="hackathon__my-project__title">Материалы</p>
                <div class="hackathon__oneProject_media">
                    <p class="hackathon__oneProject_media-title">Ссылка на проект</p>
                    <div class="hackathon__oneProject_media-item">
                        <Hyperlink />
                        <a href="#">Ссылка</a>
                    </div>
                </div>
                <div class="hackathon__oneProject_media">
                    <p class="hackathon__oneProject_media-title">Презентация</p>
                    <div class="hackathon__oneProject_media-item">
                        <Document />
                        <a href="#">Файл</a>
                    </div>
                </div>
                <div class="hackathon__oneProject_media">
                    <p class="hackathon__oneProject_media-title">Ссылка на видео</p>
                    <div class="hackathon__oneProject_media-item">
                        <Hyperlink />
                        <a href="#">Ссылка</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
