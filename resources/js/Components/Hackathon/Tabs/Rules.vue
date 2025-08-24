<script setup>
import {renderEdjs} from "@/utils/renderEdjs.js";
import Download from "@/Components/Icons/Download.vue";

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
                <p class="hackathon__my-project__title">Правила</p>
                <div v-if="props?.tabs?.data[2]?.sections[0]?.content" v-html="renderEdjs(props.tabs.data[1].sections[0].content)" class="hackathon__article" />
                <div class="hackathon__files">
                    <a :href="item.url" class="hackathon__files_item" v-for="(item, index) in props?.tabs?.data[2]?.files" :key="index">
                        <p>{{item.name}}</p>
                        <Download />
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
