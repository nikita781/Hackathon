<script setup>

import GridMenu from "@/Components/Icons/GridMenu.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import MessageMenu from "@/Components/Icons/MessageMenu.vue";
import UsersMenu from "@/Components/Icons/UsersMenu.vue";
import PencilMenu from "@/Components/Icons/PencilMenu.vue";
import {computed, nextTick, onBeforeUnmount, onMounted, ref} from "vue";
import {router} from "@inertiajs/vue3";
import {useLangStore} from "@/store/lang.js";

const langStore = useLangStore()

const activeTab = ref(2)

function goTab(i) {
    if (i === 0) {
        router.get(route('admin.contents.tagsindex'), {
            preserveState: true,
            preserveScroll: true,
            replace: true
        })
    } else if (i === 1) {
        router.get(route('admin.contents.bannersindex'), {
            preserveState: true,
            preserveScroll: true,
            replace: true
        })
    } else {
        router.get(route('admin.contents.awardsindex'), {
            preserveState: true,
            preserveScroll: true,
            replace: true
        })
    }
}

const tabEls = ref([])
const setTabRef = (i) => (el) => {
    if (el) tabEls.value[i] = el
}
const sliderStyle = computed(() => {
    const el = tabEls.value[activeTab.value]
    if (!el) return {}
    return {left: `${el.offsetLeft}px`, width: `${el.offsetWidth}px`}
})

onMounted(async () => {
    await langStore.fetchTranslations()
    await nextTick(() => {
        tabEls.value = Array.from(document.querySelectorAll('.my-hackathon__tabs_item'))
    })
})
onBeforeUnmount(() => {
})
</script>

<template>
    <AuthenticatedLayout>
        <div class="sidebar">
            <div class="sidebar-menu">
                <div class="sidebar-menu__container">
                    <div>
                        <a href="/admin/users" class="sidebar-menu__item">
                            <p class="sidebar-menu__label"></p>
                            <UsersMenu/>
                            <p class="sidebar-menu__label">Пользователи</p>
                        </a>
                    </div>
                    <div>
                        <a href="/admin/moderation/hackathons" class="sidebar-menu__item">
                            <p class="sidebar-menu__label"></p>
                            <PencilMenu/>
                            <p class="sidebar-menu__label">Модерация</p>
                        </a>
                    </div>
                    <div>
                        <a href="/admin/support" class="sidebar-menu__item">
                            <p class="sidebar-menu__label"></p>
                            <MessageMenu/>
                            <p class="sidebar-menu__label">Обратная связь</p>
                        </a>
                    </div>
                    <div>
                        <a href="/admin/contents/tags" class="sidebar-menu__item active">
                            <p class="sidebar-menu__label"></p>
                            <GridMenu/>
                            <p class="sidebar-menu__label">Контент</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin__content">
            <p class="hackathon__my-project__title" style="margin-bottom: 40px">Контент</p>

            <div class="my-hackathon__tabs">
                <p :ref="setTabRef(0)" :class="['my-hackathon__tabs_item',{active:activeTab===0}]" @click="goTab(0)">
                    Теги</p>
                <p :ref="setTabRef(1)" :class="['my-hackathon__tabs_item',{active:activeTab===1}]" @click="goTab(1)">
                    Баннеры</p>
                <p :ref="setTabRef(2)" :class="['my-hackathon__tabs_item',{active:activeTab===2}]" @click="goTab(2)">
                    Награды</p>
                <div class="slider" :style="sliderStyle"></div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>

</style>
