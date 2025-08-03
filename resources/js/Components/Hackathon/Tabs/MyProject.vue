<script setup>
import IconsPencilMyProject from "@/Components/Icons/PencilMyProject.vue";
import InvitationToTheTeam from "@/Components/Dialog/InvitationToTheTeam.vue";
import EditTeam from "@/Components/Dialog/EditTeam.vue";
import CreateProject from "@/Components/Hackathon/CreateProject.vue";
import {ref} from "vue";

const props = defineProps({
    positions : { type: Array,   default : () => [] },
    ownTeam : { type: Array,   default : () => [] },
    hackathon : { type: Array,   default : () => [] },
    tabs: { type: Array,   default : () => [] },
})

const showEditTeam = ref(false)
const showInvitation = ref(false);

const isForm = ref(false);
</script>

<template>
<div class="hackathon__tab">
    <div class="hackathon__my-project" v-if="!isForm">
        <div class="hackathon__my-project__create">
            <div class="hackathon__my-project__header">
                <p class="hackathon__my-project__title">Мой проект</p>
                <button
                    type="button"
                    class="main__btn_main hackathon__btn"
                    @click="isForm = true"
                >
                    Создать
                </button>
            </div>
            <div class="hackathon__my-project__project">
                <div class="hackathon__my-project__item">
                    <div class="hackathon__my-project__item_header">
                        <img src="/project.jpg" alt="">
                        <button
                            type="button"
                            class="main__btn_main hackathon__my-project__team_svg"
                        >
                            <IconsPencilMyProject/>
                        </button>
                    </div>
                    <div class="hackathon__my-project__item_content">
                        <div>
                            <p class="hackathon__my-project__item_title">Название</p>
                            <p class="hackathon__my-project__item_text">Lorem ipsum dolor sit amet, consectetur adipisicing elit. At fuga quas quo rerum voluptas voluptatibus. Ad aliquam culpa ducimus enim excepturi explicabo laborum quam repellendus ullam. Distinctio eaque repellendus sed.</p>
                        </div>
                        <ul class="hackathon__my-project__item_avatar">
                            <li><img src="/profile.jpg" alt="Avatar"></li>
                            <li><img src="/profile.jpg" alt="Avatar"></li>
                            <li><img src="/profile.jpg" alt="Avatar"></li>
                        </ul>
                    </div>
                </div>
                <div class="hackathon__my-project__item">
                    <div class="hackathon__my-project__item_header">
                        <img src="/project.jpg" alt="">
                        <button
                            type="button"
                            class="main__btn_main hackathon__my-project__team_svg"
                        >
                            <IconsPencilMyProject/>
                        </button>
                    </div>
                    <div class="hackathon__my-project__item_content">
                        <div>
                            <p class="hackathon__my-project__item_title">Название</p>
                            <p class="hackathon__my-project__item_text">Lorem ipsum dolor sit amet, consectetur adipisicing elit. At fuga quas quo rerum voluptas voluptatibus. Ad aliquam culpa ducimus enim excepturi explicabo laborum quam repellendus ullam. Distinctio eaque repellendus sed.</p>
                        </div>
                        <ul class="hackathon__my-project__item_avatar">
                            <li><img src="/profile.jpg" alt="Avatar"></li>
                            <li><img src="/profile.jpg" alt="Avatar"></li>
                            <li><img src="/profile.jpg" alt="Avatar"></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="hackathon__my-project__team">
            <div class="hackathon__my-project__header">
                <p class="hackathon__my-project__title">Моя команда</p>
                <button
                    type="button"
                    class="main__btn_main hackathon__my-project__team_svg"
                    @click="showEditTeam = true"
                >
                    <IconsPencilMyProject
                    />
                </button>
                <EditTeam
                    v-model="showEditTeam"
                    :team="props.ownTeam"
                    :positions="props.positions"
                    :hackathon="props.hackathon"
                />
            </div>
<!--            <pre>{{props.ownTeam}}</pre>-->
            <div class="hackathon__my-project__list">
                <div class="hackathon__my-project__list_item" v-for="(person,idx) in props.ownTeam.users" :key="idx">
                    <div class="hackathon__my-project__list_container">
                        <img src="/profile.jpg" alt="Avatar">
                        <p class="hackathon__my-project__list_text">{{ person.user.name }}</p>
                    </div>
                    <p class="hackathon__my-project__list_text">{{ person.position.name }}</p>
                </div>
            </div>
            <button
                type="button"
                class="main__btn_main hackathon__btn"
                @click="showInvitation = true"
            >
                Пригласить в команду
            </button>
            <InvitationToTheTeam
                v-model="showInvitation"
                :positions="props.positions"
                :ownTeam="props.ownTeam"
                :hackathon="props.hackathon"
            />
        </div>
    </div>
    <CreateProject
        v-else
        :hackathonSlug="props.hackathon.slug"
        :teamId="props.ownTeam.id"
    />
</div>
</template>

<style scoped>

</style>
