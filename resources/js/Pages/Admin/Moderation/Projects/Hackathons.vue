<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import UsersMenu from "@/Components/Icons/UsersMenu.vue"
import PencilMenu from "@/Components/Icons/PencilMenu.vue"
import MessageMenu from "@/Components/Icons/MessageMenu.vue"
import GridMenu from "@/Components/Icons/GridMenu.vue"
import IconsFilters from "@/Components/Icons/Filters.vue"
import Pagination from "@/Components/Pagination.vue"
import FiltersMenu from "@/Components/Dialog/Admin/FiltersMenu.vue"

import { useLangStore } from "@/store/lang.js"
import { router } from "@inertiajs/vue3"
import { computed, nextTick, onMounted, ref, watch } from "vue"
import debounce from "lodash.debounce"

const langStore = useLangStore()

const props = defineProps({
    hackathons: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    auth : { type:Object, required:true },
    notifications : { type:Object, required:true },
})

/** --- Табы: здесь активен 1 (Проекты). Клик по «Хакатоны» — назад на страницу хакатонов */
const activeTab = ref(1)
function goTab(i) {
    const params = buildQuery()
    if (i === 0) {
        router.get(route('admin.moderation.hackathonsindex'), params, { preserveState:true, preserveScroll:true, replace:true })
    } else {
        router.get('/admin/moderation/projects', params, { preserveState:true, preserveScroll:true, replace:true })
    }
}

const tabEls = ref([])
const setTabRef = (i) => (el) => { if (el) tabEls.value[i] = el }
const sliderStyle = computed(() => {
    const el = tabEls.value[activeTab.value]
    if (!el) return {}
    return { left: `${el.offsetLeft}px`, width: `${el.offsetWidth}px` }
})

/** --- Фильтры/поиск (та же логика, но запрос уходит на /admin/moderation/projects) */
const search  = ref(props.filters.q ?? '')
const order   = ref(props.filters.order ?? 'dateD')
const selected = ref({
    status: props.filters.status ? props.filters.status.split(',') : [],
})
const showFilter = ref(false)

function buildQuery() {
    return {
        q: search.value || undefined,
        order: order.value || undefined,
        status: selected.value.status.length ? selected.value.status.join(',') : undefined,
    }
}
function runSearch() {
    router.get('/admin/moderation/projects', buildQuery(), { preserveState:true, preserveScroll:true, replace:true })
}
watch(search, debounce(runSearch, 400))

/** --- Список и пагинация */
const rows = computed(() => props.hackathons?.data ?? props.hackathons?.data?.data ?? props.hackathons ?? [])
const withQ = (url) => {
    if (!url) return null
    const usp = new URLSearchParams()
    Object.entries(buildQuery()).forEach(([k,v]) => { if (v != null && v !== '') usp.set(k, v) })
    return url + (url.includes('?') ? '&' : '?') + usp.toString()
}
const pageLinks = computed(() => {
    let links = []
    if (Array.isArray(props.hackathons?.links)) links = props.hackathons.links
    else if (Array.isArray(props.hackathons?.meta?.links)) links = props.hackathons.meta.links
    return links.map(l => ({ ...l, url: withQ(l.url) }))
})

/** --- Утилиты */
function pluralizeRu(n, forms){
    const abs = Math.abs(n); const n10 = abs % 10; const n100 = abs % 100;
    if (n10 === 1 && n100 !== 11) return forms[0];
    if (n10 >= 2 && n10 <= 4 && (n100 < 12 || n100 > 14)) return forms[1];
    return forms[2];
}
onMounted(async () => {
    await langStore.fetchTranslations()
    await nextTick(() => {
        tabEls.value = Array.from(document.querySelectorAll('.my-hackathon__tabs_item'))
    })
})
</script>

<template>
    <AuthenticatedLayout
        :auth="props.auth"
        :notifications="props.notifications"
    >
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
                        <a href="/admin/moderation/hackathons" class="sidebar-menu__item active">
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
                        <a href="/admin/contents/tags" class="sidebar-menu__item">
                            <p class="sidebar-menu__label"></p>
                            <GridMenu/>
                            <p class="sidebar-menu__label">Контент</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin__content">
            <p class="hackathon__my-project__title" style="margin-bottom: 40px">Модерация</p>

            <div class="my-hackathon__tabs">
                <p :ref="setTabRef(0)" :class="['my-hackathon__tabs_item',{active:activeTab===0}]" @click="goTab(0)">Хакатоны</p>
                <p :ref="setTabRef(1)" :class="['my-hackathon__tabs_item',{active:activeTab===1}]" @click="goTab(1)">Проекты</p>
                <div class="slider" :style="sliderStyle"></div>
            </div>

            <!-- Поиск/фильтры -->
            <div class="hackathon__gallery_filter" style="margin-top: 40px">
                <div class="main__search my-hackathon__search">
                    <div class="main__search_container">
                        <input v-model="search" class="main__search_input" :placeholder="langStore.translations.search" />
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path d="M21.07 16.8299L19 14.7099C18.5547 14.2867 17.9931 14.0063 17.3872 13.9047C16.7813 13.8031 16.1589 13.885 15.6 14.1399L14.7 13.2399C15.7606 11.8229 16.2449 10.0566 16.0555 8.29678C15.8662 6.53694 15.0172 4.91417 13.6794 3.75514C12.3417 2.59612 10.6145 1.9869 8.84566 2.05013C7.07679 2.11335 5.39755 2.84433 4.14597 4.09591C2.89439 5.34749 2.16341 7.02674 2.10018 8.79561C2.03695 10.5645 2.64617 12.2916 3.8052 13.6294C4.96422 14.9671 6.58699 15.8161 8.34683 16.0055C10.1067 16.1948 11.8729 15.7105 13.29 14.6499L14.18 15.5399C13.8951 16.0996 13.793 16.7345 13.8881 17.3553C13.9831 17.976 14.2706 18.5513 14.71 18.9999L16.83 21.1199C17.3925 21.6817 18.155 21.9973 18.95 21.9973C19.745 21.9973 20.5075 21.6817 21.07 21.1199C21.3557 20.8405 21.5828 20.5069 21.7378 20.1385C21.8928 19.7702 21.9726 19.3746 21.9726 18.9749C21.9726 18.5753 21.8928 18.1797 21.7378 17.8114C21.5828 17.443 21.3557 17.1093 21.07 16.8299ZM12.59 12.5899C11.8902 13.2879 10.9993 13.7629 10.0297 13.9548C9.06018 14.1467 8.05549 14.0469 7.1426 13.6681C6.22971 13.2893 5.44956 12.6485 4.90071 11.8265C4.35186 11.0045 4.05894 10.0383 4.05894 9.04994C4.05894 8.06157 4.35186 7.09538 4.90071 6.2734C5.44956 5.45143 6.22971 4.81056 7.1426 4.43175C8.05549 4.05294 9.06018 3.95319 10.0297 4.14509C10.9993 4.33699 11.8902 4.81194 12.59 5.50994C13.0556 5.9744 13.4251 6.52615 13.6771 7.13361C13.9292 7.74106 14.0589 8.39227 14.0589 9.04994C14.0589 9.70761 13.9292 10.3588 13.6771 10.9663C13.4251 11.5737 13.0556 12.1255 12.59 12.5899ZM19.66 19.6599C19.567 19.7537 19.4564 19.8281 19.3346 19.8788C19.2127 19.9296 19.082 19.9557 18.95 19.9557C18.818 19.9557 18.6873 19.9296 18.5654 19.8788C18.4436 19.8281 18.333 19.7537 18.24 19.6599L16.12 17.5399C16.0263 17.447 15.9519 17.3364 15.9011 17.2145C15.8503 17.0927 15.8242 16.962 15.8242 16.8299C15.8242 16.6979 15.8503 16.5672 15.9011 16.4454C15.9519 16.3235 16.0263 16.2129 16.12 16.1199C16.213 16.0262 16.3236 15.9518 16.4454 15.9011C16.5673 15.8503 16.698 15.8241 16.83 15.8241C16.962 15.8241 17.0927 15.8503 17.2146 15.9011C17.3364 15.9518 17.447 16.0262 17.54 16.1199L19.66 18.2399C19.7537 18.3329 19.8281 18.4435 19.8789 18.5654C19.9297 18.6872 19.9558 18.8179 19.9558 18.9499C19.9558 19.082 19.9297 19.2127 19.8789 19.3345C19.8281 19.4564 19.7537 19.567 19.66 19.6599Z" fill="#999999"/>
                        </svg>
                    </div>
                    <button type="button" class="main__btn_main" @click="runSearch">{{ langStore.translations.search }}</button>
                </div>
                <button class="main__btn main__btn_white" @click="showFilter = true">
                    <IconsFilters class="admin__btn_filters" />
                </button>
            </div>

            <!-- Карточки: КОНТЕНТ ВТОРОГО таба (как у тебя при activeTab===1) -->
            <div class="main__cards" style="margin-top: 40px">
                <a v-for="hackathon in rows" :key="hackathon.id" class="main__card" :href="`/hackathons/${hackathon.slug}`">
                    <div class="main__card_photo">
                        <img :src="hackathon.image_path" alt="Photo" />
                        <p class="main__card_photo-status yellow" v-if="hackathon.status === 2">На рассмотрении</p>
                        <p class="main__card_photo-status red" v-else-if="hackathon.status === 4">Отклонен</p>
                        <p class="main__card_photo-status green" v-else-if="hackathon.status === 3">Принят</p>
                    </div>
                    <div class="main__card_content">
                        <p class="main__card_title">{{ hackathon.title }}</p>

                        <div class="main__card_info">
                            <div class="main__card_item">
                                <div style="width:24px;height:24px" class="svg-black"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"> <path d="M10.25 10.1827C10.6946 9.79779 11.0513 9.32175 11.2957 8.78687C11.5401 8.25198 11.6667 7.67077 11.6666 7.08268C11.6666 5.97761 11.2277 4.91781 10.4463 4.1364C9.66486 3.355 8.60505 2.91602 7.49998 2.91602C6.39491 2.91602 5.3351 3.355 4.5537 4.1364C3.7723 4.91781 3.33331 5.97761 3.33331 7.08268C3.33331 7.67077 3.45981 8.25198 3.70425 8.78687C3.94869 9.32175 4.30533 9.79779 4.74998 10.1827C3.58343 10.7109 2.5937 11.564 1.89913 12.6398C1.20456 13.7157 0.83454 14.9688 0.833313 16.2494C0.833313 16.4704 0.92111 16.6823 1.07739 16.8386C1.23367 16.9949 1.44563 17.0827 1.66665 17.0827C1.88766 17.0827 2.09962 16.9949 2.2559 16.8386C2.41218 16.6823 2.49998 16.4704 2.49998 16.2494C2.49998 14.9233 3.02676 13.6515 3.96445 12.7138C4.90213 11.7761 6.1739 11.2493 7.49998 11.2493C8.82606 11.2493 10.0978 11.7761 11.0355 12.7138C11.9732 13.6515 12.5 14.9233 12.5 16.2494C12.5 16.4704 12.5878 16.6823 12.7441 16.8386C12.9003 16.9949 13.1123 17.0827 13.3333 17.0827C13.5543 17.0827 13.7663 16.9949 13.9226 16.8386C14.0788 16.6823 14.1666 16.4704 14.1666 16.2494C14.1654 14.9688 13.7954 13.7157 13.1008 12.6398C12.4063 11.564 11.4165 10.7109 10.25 10.1827ZM7.49998 9.58268C7.00553 9.58268 6.52218 9.43606 6.11105 9.16136C5.69993 8.88665 5.3795 8.49621 5.19028 8.03939C5.00106 7.58258 4.95155 7.07991 5.04802 6.59496C5.14448 6.11 5.38258 5.66455 5.73221 5.31492C6.08184 4.96528 6.5273 4.72718 7.01225 4.63072C7.49721 4.53426 7.99987 4.58376 8.45669 4.77298C8.9135 4.9622 9.30395 5.28263 9.57865 5.69376C9.85336 6.10488 9.99998 6.58823 9.99998 7.08268C9.99998 7.74572 9.73659 8.38161 9.26775 8.85045C8.79891 9.31929 8.16302 9.58268 7.49998 9.58268ZM15.6166 9.84935C16.15 9.24879 16.4983 8.5069 16.6198 7.71297C16.7413 6.91904 16.6308 6.10691 16.3015 5.37435C15.9721 4.64179 15.4381 4.02001 14.7637 3.58387C14.0893 3.14772 13.3032 2.9158 12.5 2.91602C12.279 2.91602 12.067 3.00381 11.9107 3.16009C11.7544 3.31637 11.6666 3.52834 11.6666 3.74935C11.6666 3.97036 11.7544 4.18232 11.9107 4.3386C12.067 4.49489 12.279 4.58268 12.5 4.58268C13.163 4.58268 13.7989 4.84607 14.2677 5.31492C14.7366 5.78376 15 6.41964 15 7.08268C14.9988 7.52038 14.8827 7.9501 14.6634 8.32887C14.444 8.70763 14.129 9.02217 13.75 9.24102C13.6264 9.31228 13.5232 9.41407 13.4503 9.53663C13.3773 9.6592 13.3371 9.79843 13.3333 9.94102C13.3298 10.0825 13.3624 10.2225 13.428 10.3479C13.4936 10.4733 13.5901 10.5799 13.7083 10.6577L14.0333 10.8744L14.1416 10.9327C15.1461 11.4091 15.9936 12.1627 16.5841 13.1046C17.1747 14.0465 17.4838 15.1376 17.475 16.2494C17.475 16.4704 17.5628 16.6823 17.7191 16.8386C17.8753 16.9949 18.0873 17.0827 18.3083 17.0827C18.5293 17.0827 18.7413 16.9949 18.8976 16.8386C19.0538 16.6823 19.1416 16.4704 19.1416 16.2494C19.1485 14.9705 18.8282 13.7112 18.2112 12.5911C17.5942 11.4709 16.7011 10.5271 15.6166 9.84935Z" fill="#121212"/> </svg></div>
                                <p>{{ hackathon.users_count ?? 0 }} {{ pluralizeRu(hackathon.users_count ?? 0, ['участник', 'участника', 'участников']) }}</p>
                            </div>

                            <div class="main__card_item">
                                <div style="width:24px;height:24px" class="svg-black"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"> <path d="M18.3333 6.0327C18.3339 5.92302 18.3129 5.81431 18.2715 5.71277C18.23 5.61124 18.1689 5.5189 18.0916 5.44103L14.5583 1.9077C14.4804 1.83046 14.3881 1.76936 14.2866 1.72789C14.185 1.68642 14.0763 1.6654 13.9666 1.66603C13.857 1.6654 13.7482 1.68642 13.6467 1.72789C13.5452 1.76936 13.4528 1.83046 13.375 1.9077L11.0166 4.26603L1.90831 13.3744C1.83107 13.4522 1.76997 13.5446 1.7285 13.6461C1.68703 13.7476 1.66601 13.8564 1.66664 13.966V17.4994C1.66664 17.7204 1.75444 17.9323 1.91072 18.0886C2.067 18.2449 2.27896 18.3327 2.49997 18.3327H6.03331C6.14991 18.339 6.26655 18.3208 6.37566 18.2792C6.48476 18.2375 6.5839 18.1734 6.66664 18.091L15.725 8.9827L18.0916 6.66603C18.1677 6.58526 18.2297 6.49231 18.275 6.39103C18.283 6.3246 18.283 6.25745 18.275 6.19103C18.2789 6.15224 18.2789 6.11315 18.275 6.07436L18.3333 6.0327ZM5.69164 16.666H3.33331V14.3077L11.6083 6.0327L13.9666 8.39103L5.69164 16.666ZM15.1416 7.21603L12.7833 4.8577L13.9666 3.6827L16.3166 6.0327L15.1416 7.21603Z" fill="#121212"/> </svg></div>
                                <p>{{ hackathon.projects_count ?? 0 }} {{ pluralizeRu(hackathon.projects_count ?? 0, ['проект', 'проекта', 'проектов']) }}</p>
                            </div>

                            <a :href="`/admin/moderation/projects/hackathon/${hackathon.slug}`" class="main__btn_main" style="width: fit-content">Перейти к проектам</a>
                        </div>
                    </div>
                </a>
            </div>

            <Pagination style="margin-top: 30px" :links="pageLinks" />

            <FiltersMenu
                v-model="showFilter"
                :order="order"
                :selected="selected"
                @apply="({ order:ord, selected:sel }) => { order = ord; selected = sel; runSearch() }"
                @reset="() => { order='dateD'; selected={status:[]}; search=''; runSearch() }"
            />
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.svg-black{display:flex;justify-content:center;align-items:center}
</style>
