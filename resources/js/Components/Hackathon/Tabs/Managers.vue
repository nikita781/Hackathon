<script setup>
import IconsPencilMyProject from "@/Components/Icons/PencilMyProject.vue";
import InvitationToTheManager from "@/Components/Dialog/InvitationToTheManager.vue";
import {ref} from "vue";
import InvitationToTheTeam from "@/Components/Dialog/InvitationToTheTeam.vue";

const props = defineProps({
    positions : { type: Array,   default : () => [] },
    ownTeam : { type: Array,   default : () => [] },
    hackathon : { type: Array,   default : () => [] },
    tabs: { type: Array,   default : () => [] },
    allProjects: { type: Array,   default : () => [] },
    hackathonStaff: { type: Array,   default : () => [] },
})

console.log(props.hackathonStaff)

const showInvitation = ref(false);
</script>

<template>
    <div class="hackathon__tab">
        <div class="hackathon__my-project__create">
            <div class="hackathon__my-project__header">
                <p class="hackathon__my-project__title">Управляющие</p>
                <div class="hackathon__managers">
                    <button
                        type="button"
                        class="main__btn_main hackathon__my-project__team_svg"
                    >
                        <IconsPencilMyProject/>
                    </button>
                    <button
                        type="button"
                        class="main__btn_main"
                        @click="showInvitation = true"
                    >
                        Пригласить управляющих
                    </button>
                    <InvitationToTheManager
                        v-model="showInvitation"
                        :positions="props.positions"
                        :ownTeam="props.ownTeam"
                        :hackathon="props.hackathon"
                    />
                </div>
            </div>
            <div class="hackathon__participants_team">
                <div class="hackathon__my-project__list_item" v-for="(person,idx) in props.hackathonStaff" :key="idx">
                    <div class="hackathon__my-project__list_container">
                        <img src="/profile.jpg" alt="Avatar">
                        <p class="hackathon__my-project__list_text">{{ person.name }}</p>
                    </div>
                    <p class="hackathon__my-project__list_text">{{ person.hackathon_role.title }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
