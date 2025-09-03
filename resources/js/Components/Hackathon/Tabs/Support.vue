<script setup>
import {computed, nextTick, onMounted, ref} from "vue";
import {usePage} from "@inertiajs/vue3";
import WriteAppeal from "@/Components/Dialog/WriteAppeal.vue";

const props = defineProps({
    positions : { type: Array,   default : () => [] },
    ownTeam : { type: Array,   default : () => [] },
    hackathon : { type: Array,   default : () => [] },
    tabs: { type: Array,   default : () => [] },
    allProjects: { type: Array,   default : () => [] },
    supports: { type: Array,   default : () => [] },
})

console.log(props.supports)

const isForm = ref(false);
const activeTab = ref(usePage().props.query?.tab   === 'past' ? 1 : 0)

function setActiveTab(idx) {
    if (activeTab.value === idx) return
    activeTab.value = idx
}

const tabsRef = ref([]);
const sliderStyle = computed(() => {
    if (!tabsRef.value.length) return {};

    const activeTabElement = tabsRef.value[activeTab.value];
    const left = activeTabElement?.offsetLeft || 0;
    const width = activeTabElement?.offsetWidth || 0;

    return {
        left: `${left}px`,
        width: `${width}px`,
    };
});

onMounted(async () => {
    await nextTick(() => {
        tabsRef.value = document.querySelectorAll('.my-hackathon__tabs_item');
    });
});
</script>

<template>
    <div class="hackathon__tab">
        <div class="hackathon__tab_main">
            <div class="hackathon__tab_container">
                <div class="hackathon__my-project__header">
                    <p class="hackathon__my-project__title">Мои обращения</p>
                    <button
                        type="button"
                        class="main__btn_main hackathon__btn"
                        @click="isForm = true"
                    >
                        Написать обращение
                    </button>
                    <WriteAppeal
                        :hackathonSlug="props.hackathon.slug"
                        v-model="isForm"
                    />
                </div>
                <div class="my-hackathon__tabs">
                    <p :class="['my-hackathon__tabs_item',{active:activeTab===0}]" @click="setActiveTab(0)">
                        Открытые
                    </p>
                    <p :class="['my-hackathon__tabs_item',{active:activeTab===1}]" @click="setActiveTab(1)">
                        Закрытые
                    </p>
                    <div
                        class="slider"
                        :style="sliderStyle"
                    ></div>
                </div>
                <div class="hackathon__support_item">
                    <p class="hackathon__support_title">Обращение 1</p>
                    <p class="hackathon__contact_links-item">Предложение</p>
                </div>
                <div class="hackathon__support_item">
                    <p class="hackathon__support_title">Обращение 2</p>
                    <p class="hackathon__contact_links-item">Вопрос</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
