<script setup>
import {computed, onMounted, ref} from 'vue'
import { renderEdjs } from '@/utils/renderEdjs'
import IconsCup from '../../Icons/Cup.vue';
import axios from "axios";
import logs from "../../../../../vendor/laravel/telescope/resources/js/screens/logs/index.vue";
import {useLangStore} from "@/store/lang.js";

const props = defineProps({
    positions : { type: Array,   default : () => [] },
    ownTeam : { type: Array,   default : () => [] },
    hackathon : { type: Array,   default : () => [] },
    tabs: { type: Array,   default : () => [] },
    allProjects: { type: Array,   default : () => [] },
})

const langStore = useLangStore()

function formatNumber(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

const partners = ref([]);

async function getPartner() {
    try {
        const response = await axios.get(
            route('hackathons.tabs.partner-images', { hackathon: props.hackathon.slug, tab: props.tabs.data[0].id }),
            {
                headers: {
                    'Accept': 'application/json'
                }
            }
        );

        partners.value = response.data
        console.log(partners.value)
    } catch (e) {
        console.error('hackathon-load', e?.response ?? e);
    }
}

onMounted(async () => {
    await langStore.fetchTranslations()
    getPartner()
})

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}
</script>

<template>
    <div class="hackathon__tab">
        <div class="hackathon__tab_main">
            <div class="hackathon__tab_container" v-if="props.tabs.data[0].sections[0].content">
                <p class="hackathon__my-project__title">{{ capitalizeFirstLetter(langStore.translations.description) }}</p>
                <div v-html="renderEdjs(props.tabs.data[0].sections[0].content)" class="hackathon__article" />
            </div>
            <div class="hackathon__tab_container" v-if="props.tabs.data[0].sections[1].content">
                <p class="hackathon__my-project__title">{{ capitalizeFirstLetter(langStore.translations.plan) }}</p>
                <div v-html="renderEdjs(props.tabs.data[0].sections[1].content)" class="hackathon__article" />
            </div>
            <div class="hackathon__tab_container">
                <p class="hackathon__my-project__title">{{ capitalizeFirstLetter(langStore.translations.prize_fund) }}</p>
                <div class="hackathon__prizes">
                    <p class="hackathon__prizes_title">
                        {{ capitalizeFirstLetter(langStore.translations.total_prize_fund) }}:
                        {{
                            !isNaN(props.hackathon.prize_pool)
                                ? `${Number(props.hackathon.prize_pool).toLocaleString('ru-RU')} ₽`
                                : props.hackathon.prize_pool
                        }}
                    </p>
                    <div class="hackathon__prizes_container" v-if="props.hackathon.nominations">
                        <div class="hackathon__prizes_item"  v-for="(n, idx) in props.hackathon.nominations" :key="n.id">
                            <IconsCup />
                            <div class="hackathon__prizes_content">
                                <p class="hackathon__prizes_name">{{ n.title }}</p>
                                <p class="hackathon__prizes_prize">{{ formatNumber(n.prize) || capitalizeFirstLetter(langStore.translations.no_amount) }} ₽</p>
                                <p class="hackathon__prizes_count">
                                    {{ n.places.length }}
                                    {{ n.places.length === 1 ? capitalizeFirstLetter(langStore.translations.winner_singular) : n.places.length > 1 && n.places.length < 5 ? capitalizeFirstLetter(langStore.translations.winner_genitive) : capitalizeFirstLetter(langStore.translations.winner_plural) }}
                                </p>
                                <p
                                    class="hackathon__prizes_one" v-for="(place, idx) in n.places"
                                    :key="n.id"
                                >
                                    {{place.place}} {{ langStore.translations.place }}
                                    -
                                    {{
                                        !isNaN(place.prize)
                                            ? `${Number(place.prize).toLocaleString('ru-RU')} ₽`
                                            : place.prize
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hackathon__tab_container" v-if="partners.partners">
                <p class="hackathon__my-project__title">{{ capitalizeFirstLetter(langStore.translations.partners) }}</p>
                <div class="hackathon__partners">
                    <div class="hackathon__partners_item" v-for="(partner, idx) in partners.partners" :key="idx">
                        <img :src="partner.url" alt="">
                    </div>
                </div>
            </div>
<!--            <pre>{{ partners.partners }}</pre>-->
<!--            <pre>{{ props.tabs.data[0] }}</pre>-->
        </div>
    </div>
</template>

<style scoped>

</style>
