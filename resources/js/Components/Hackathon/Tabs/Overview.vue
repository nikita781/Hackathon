<script setup>
import {computed, ref} from 'vue'
import { renderEdjs } from '@/utils/renderEdjs'

const props = defineProps({
    positions : { type: Array,   default : () => [] },
    ownTeam : { type: Array,   default : () => [] },
    hackathon : { type: Array,   default : () => [] },
    tabs: { type: Array,   default : () => [] },
})

</script>

<template>
    <div class="hackathon__tab">
        <div class="hackathon__tab_main">
            <div class="hackathon__tab_container">
                <p class="hackathon__my-project__title">Описание</p>
                <div v-html="renderEdjs(props.tabs.data[0].sections[0].content)" class="article" />
            </div>
            <pre>{{ props.tabs.data[0] }}</pre>
        </div>
    </div>
</template>

<style scoped>

</style>
