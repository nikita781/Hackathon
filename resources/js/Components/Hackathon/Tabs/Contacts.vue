<script setup>

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
                <p class="hackathon__my-project__title">Контакты</p>
                <div class="hackathon__contact" v-for="(item, index) in props.tabs.data[3].sections" :key="index">
                    <p class="hackathon__contact_title" v-if="item.items.length">{{ item.title }}:</p>
                    <div class="hackathon__contact_links">
                        <a v-for="(link, inx) in item.items" :key="inx" :href="link.content" class="hackathon__contact_links-item" target="_blank">{{ link.title }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
