<script setup>
import IconsPencilMyProject from "@/Components/Icons/PencilMyProject.vue";
import InvitationToTheManager from "@/Components/Dialog/InvitationToTheManager.vue";
import {ref} from "vue";

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
                    />
                </div>
            </div>
            <div class="hackathon__participants_team">
                <div class="hackathon__my-project__list_item">
                    <div class="hackathon__my-project__list_container">
                        <img src="/profile.jpg" alt="Avatar">
                        <p class="hackathon__my-project__list_text">Глеб123</p>
                    </div>
                    <p class="hackathon__my-project__list_text">Капитан</p>
                </div>
                <div class="hackathon__my-project__list_item">
                    <div class="hackathon__my-project__list_container">
                        <img src="/profile.jpg" alt="Avatar">
                        <p class="hackathon__my-project__list_text">Глеб123</p>
                    </div>
                    <p class="hackathon__my-project__list_text">Капитан</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
