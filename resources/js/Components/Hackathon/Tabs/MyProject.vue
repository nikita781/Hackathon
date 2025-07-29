<script setup>
import IconsPencilMyProject from "@/Components/Icons/PencilMyProject.vue";
import InvitationToTheTeam from "@/Components/Dialog/InvitationToTheTeam.vue";
import EditTeam from "@/Components/Dialog/EditTeam.vue";
import {ref} from "vue";

const props = defineProps({
    positions : { type: Array,   default : () => [] },
    ownTeam : { type: Array,   default : () => [] },
    hackathon : { type: Array,   default : () => [] },
    tabs: { type: Array,   default : () => [] },
})

const showEditTeam = ref(false)
const showInvitation = ref(false);
</script>

<template>
<div class="hackathon__tab">
    <div class="hackathon__my-project">
        <div class="hackathon__my-project__create">
            <div class="hackathon__my-project__header">
                <p class="hackathon__my-project__title">Мой проект</p>
                <button
                    type="button"
                    class="main__btn_main hackathon__btn"
                >
                    Создать
                </button>
            </div>
        </div>
        <div class="hackathon__my-project__team">
            <div class="hackathon__my-project__header">
                <p class="hackathon__my-project__title">Моя команда</p>
                <button
                    type="button"
                    class="main__btn_main hackathon__my-project__team_svg"
                >
                    <IconsPencilMyProject
                        @click="showEditTeam = true"
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
</div>
</template>

<style scoped>

</style>
