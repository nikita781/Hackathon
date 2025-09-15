<script setup>
import { computed, nextTick, onMounted, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import WriteAppeal from "@/Components/Dialog/WriteAppeal.vue";
import AnswerSupport from "@/Components/Dialog/AnswerSupport.vue";
import Pagination from "@/Components/Pagination.vue"
import axios from "axios";

const props = defineProps({
    positions: { type: Array, default: () => [] },
    ownTeam: { type: Array, default: () => [] },
    hackathon: { type: Array, default: () => [] },
    tabs: { type: Array, default: () => [] },
    can: { type: Array, default: () => [] },
    allProjects: { type: Array, default: () => [] },
});

const supports = ref([]);

async function fetchSupport() {
    try {
        const { data } = await axios.get(
            route('hackathons.support.index', { hackathon: props.hackathon.slug })
        )
        supports.value = data
        console.log(supports.value)
    } catch (e) {
        console.error('support-fetch', e?.response ?? e)
    }
}

const isForm = ref(false);
const activeTab = ref(usePage().props.query?.tab === 'past' ? 1 : 0);

const showAnswer = ref(false);
const messageAnswer = ref([]);
const isAnswer = ref(true);

function openAnswer(message) {
    messageAnswer.value = message;

    isAnswer.value = !!props?.can?.support?.answer && currentBucketKey.value !== 'going' && currentBucketKey.value !== 'completed' && currentBucketKey.value !== 'receivedSupportCompleted';

    showAnswer.value = true;
}

const statusFilter = ref('open');

const currentBucketKey = computed(() => {
    if (!isOrganizer.value) {
        // без права отвечать: вкладки — going / completed
        return activeTab.value === 0 ? 'going' : 'completed';
    }
    // организатор: вкладки «Для меня» / «От меня»
    if (activeTab.value === 0) {
        // Для меня
        return statusFilter.value === 'open'
            ? 'receivedSupportGoing'
            : 'receivedSupportCompleted';
    } else {
        // От меня
        return statusFilter.value === 'open' ? 'going' : 'completed';
    }
});

function setStatus(v) {
    statusFilter.value = v;
}

const TYPE_LABEL = {question: "Вопрос", suggestion: "Предложение", bug: "Ошибка"};

const isOrganizer = computed(() => !!props?.can?.support?.answer);

const activePageBlock = computed(() => {
    if (!isOrganizer.value) {
        // Пользователь без права отвечать: обычные going/completed
        return activeTab.value === 0
            ? supports.value?.going
            : supports.value?.completed;
    }

    // Организатор: две вкладки
    const isOpen = statusFilter.value === 'open';

    if (activeTab.value === 0) {
        // Вкладка «Для меня»
        return isOpen
            ? supports.value?.receivedSupportGoing
            : supports.value?.receivedSupportCompleted;
    } else {
        // Вкладка «От меня»
        return isOpen
            ? supports.value?.going
            : supports.value?.completed;
    }
});

const currentList = computed(() => activePageBlock.value?.data ?? []);

function setActiveTab(idx) {
    if (activeTab.value === idx) return;
    activeTab.value = idx;
}

const tabsRef = ref([]);
const sliderStyle = computed(() => {
    if (!tabsRef.value.length) return {};
    const el = (tabsRef.value)[activeTab.value];
    const left = el?.offsetLeft || 0;
    const width = el?.offsetWidth || 0;
    return {left: `${left}px`, width: `${width}px`};
});

const pageLinks = computed(() =>
    (activePageBlock.value?.links ?? []).map(l => ({
        ...l,
        url: l.url, // или withQ(l.url) если хочешь сохранять таб/фильтр в query
    }))
);

onMounted(async () => {
    await fetchSupport();
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
                        :org="isOrganizer"
                        v-model="isForm"
                    />
                </div>
                <div class="my-hackathon__tabs">
                    <p :class="['my-hackathon__tabs_item',{active:activeTab===0}]" @click="setActiveTab(0)">
                        {{ !props.can.support.answer ? 'Открытые' : 'Для меня' }}
                    </p>
                    <p :class="['my-hackathon__tabs_item',{active:activeTab===1}]" @click="setActiveTab(1)">
                        {{ !props.can.support.answer ? 'Закрытые' : 'От меня' }}
                    </p>
                    <div
                        class="slider"
                        :style="sliderStyle"
                    ></div>
                </div>

                <div class="hackathon__support_org" v-if="isOrganizer">
                    <div class="hackathon__support_org-btn first"
                         :class="{ active: statusFilter === 'open' }"
                         @click="setStatus('open')">Открытые</div>
                    <div class="hackathon__support_org-btn end"
                         :class="{ active: statusFilter === 'closed' }"
                         @click="setStatus('closed')">Завершенные</div>
                </div>

                <template v-if="currentList.length">
                    <div
                        v-for="(s, idx) in currentList"
                        :key="s.id"
                        class="hackathon__support_item"
                        @click="openAnswer(s)"
                        style="cursor: pointer"
                    >
                        <p class="hackathon__support_title">Обращение {{ idx + 1 }}</p>
                        <p class="hackathon__contact_links-item">{{ TYPE_LABEL[s.type] ?? s.type }}</p>
                    </div>
                </template>
                <p v-else class="hackathon__support_title" style="opacity:.7; margin-top:12px">
                    Здесь пока пусто
                </p>

                <Pagination
                    :links="pageLinks"
                    style="margin-top: 30px"
                />
            </div>
        </div>

        <AnswerSupport
            v-model="showAnswer"
            :message="messageAnswer"
            :can="isAnswer"
        />
    </div>
</template>

<style scoped>

</style>
