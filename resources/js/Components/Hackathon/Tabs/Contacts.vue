<script setup>

import {useLangStore} from "@/store/lang.js";
import {onMounted} from "vue";

const props = defineProps({
    positions : { type: Array,   default : () => [] },
    ownTeam : { type: Array,   default : () => [] },
    hackathon : { type: Array,   default : () => [] },
    tabs: { type: Array,   default : () => [] },
    allProjects: { type: Array,   default : () => [] },
})
const langStore = useLangStore()

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

function makeHref(raw) {
    const s = String(raw || '').trim()
    if (!s) return '#'
    const digits = s.replace(/\D/g, '')
    if (digits.length >= 7 && digits.length <= 15) {
        return `tel:${digits}`
    }

    if (/^(https?:\/\/|mailto:|tel:)/i.test(s)) return s
    return `https://${s}`
}

onMounted(async () => {
    await langStore.fetchTranslations()
});
</script>

<template>
    <div class="hackathon__tab">
        <div class="hackathon__tab_main">
            <div class="hackathon__tab_container" style="gap: unset">
                <p class="hackathon__my-project__title">{{ capitalizeFirstLetter(langStore.translations.contacts) }}</p>
                <div class="hackathon__contact_container">
                    <div class="hackathon__contact" v-for="(item, index) in props.tabs.data[3].sections" :key="index">
                        <p class="hackathon__contact_title" style="margin-top: 30px" v-if="item.items.length">{{ item.title }}:</p>
                        <div class="hackathon__contact_links">
<!--                            <pre>{{item.items}}</pre>-->
                            <a v-for="(link, inx) in item.items"
                               :key="inx"
                               :href="makeHref(link.content)"
                               class="hackathon__contact_links-item"
                               :target="makeHref(link.content).startsWith('tel:') ? null : '_blank'">{{ link.title }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
