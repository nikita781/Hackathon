<script setup>
import IconsPencilMyProject from "@/Components/Icons/PencilMyProject.vue";
import InvitationToTheManager from "@/Components/Dialog/InvitationToTheManager.vue";
import {onMounted, ref} from "vue";
import InvitationToTheTeam from "@/Components/Dialog/InvitationToTheTeam.vue";
import EditManagers from "@/Components/Dialog/EditManagers.vue";
import EditTeam from "@/Components/Dialog/EditTeam.vue";
import {useLangStore} from "@/store/lang.js";

const props = defineProps({
    positions : { type: Array,   default : () => [] },
    ownTeam : { type: Array,   default : () => [] },
    hackathon : { type: Array,   default : () => [] },
    tabs: { type: Array,   default : () => [] },
    allProjects: { type: Array,   default : () => [] },
    hackathonStaff: { type: Array,   default : () => [] },
})

const showInvitation = ref(false);
const showEditTeam = ref(false);

const langStore = useLangStore()

const PLACEHOLDER = '/profile.jpg';

function avatarSrc(photo) {
    if (!photo) return PLACEHOLDER;
    const url = String(photo).trim();

    const hasFileName = /[^/]+\.[a-z0-9]+(?:\?.*)?$/i.test(url);
    if (!hasFileName) return PLACEHOLDER;

    return url;
}

function imgFallback(e) {
    e.target.onerror = null;
    e.target.src = PLACEHOLDER;
}

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

onMounted(async () => {
    await langStore.fetchTranslations()
});
</script>

<template>
    <div class="hackathon__tab">
        <div class="hackathon__my-project__create">
            <div class="hackathon__my-project__header">
                <p class="hackathon__my-project__title">{{ capitalizeFirstLetter(langStore.translations.managers) }}</p>
                <div class="hackathon__managers">
                    <button
                        type="button"
                        class="main__btn_main hackathon__my-project__team_svg"
                        @click="showEditTeam = true"
                    >
                        <IconsPencilMyProject/>
                    </button>
                    <button
                        type="button"
                        class="main__btn_main hackathon__tab_back"
                        @click="showInvitation = true"
                    >
                        {{ capitalizeFirstLetter(langStore.translations.inviteManagers) }}
                    </button>
                    <EditManagers
                        v-model="showEditTeam"
                        :managers="props.hackathonStaff"
                        :hackathon="props.hackathon"
                    />
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
                        <img :src="avatarSrc(person.photo)" @error="imgFallback" alt="Avatar">
                        <p class="hackathon__my-project__list_text">{{ person.nickname }}</p>
                    </div>
                    <p class="hackathon__my-project__list_text">{{ person.hackathon_role.title }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
