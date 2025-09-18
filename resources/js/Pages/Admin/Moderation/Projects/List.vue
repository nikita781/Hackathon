<script setup>
import PencilMenu from "@/Components/Icons/PencilMenu.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import GridMenu from "@/Components/Icons/GridMenu.vue";
import UsersMenu from "@/Components/Icons/UsersMenu.vue";
import MessageMenu from "@/Components/Icons/MessageMenu.vue";
import IconsFilters from "@/Components/Icons/Filters.vue";
import { nextTick, onMounted, ref, watch } from "vue";
import { useLangStore } from "@/store/lang.js";
import FiltersMenu from "@/Components/Dialog/Admin/FiltersMenu.vue";

import { router } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import debounce from "lodash.debounce";
import IconsPencilMyProject from "@/Components/Icons/PencilMyProject.vue";
import ViewProject from "@/Components/Dialog/ViewProject.vue";
import {useToast} from "vue-toastification";

const langStore = useLangStore();

const props = defineProps({
    projects: { type: Object, default: () => ({}) },
    hackathons: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    // если на странице есть сам хакатон — он нужен для fallback превью
    hackathon: { type: Object, default: () => null },
});

const search   = ref(props.filters.q ?? "");
const order    = ref(props.filters.order ?? "dateD");
const selected = ref({
    status: props.filters.status
        ? String(props.filters.status).split(",").filter(Boolean)
        : [],
});
const showFilter = ref(false);

const showView = ref(false);
const oneProject = ref([]);

function openView(project) {
    oneProject.value = project;
    showView.value = true;
}

onMounted(async () => {
    await langStore.fetchTranslations();
});

/** Собираем параметры для бэка (как в хакатонах: q, status, order) */
function buildParams () {
    const params = {};
    const q = (search.value || "").trim();
    if (q) params.q = q;

    const st = (selected.value?.status || []).filter(Boolean).join(",");
    if (st) params.status = st;

    if (order.value && order.value !== "dateD") {
        params.order = order.value;
    } else {
        // по умолчанию (как у тебя в контроллере) — dateD
        params.order = "dateD";
    }
    return params;
}

/** Ходим на эту же страницу, но с query (withQueryString на бэке сохранит пагинацию/фильтры) */
function runSearch (pageUrl = null) {
    const url =
        pageUrl ||
        // замени на твой именованный роут, если он есть. Если страница уже эта — можно просто router.get(route().current(), …).
        route(route().current(), route().params);

    router.get(url, buildParams(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

const toast = useToast();

/** Пагинация — в Pagination компонент прилетает готовый URL, в нём уже есть ?page, а бэк делает withQueryString */
function go (pageUrl) {
    // передадим и текущие фильтры, чтобы не потерять их при переходе
    runSearch(pageUrl);
}

async function onApprove() {
    console.log(oneProject.value.slug)
    try {
        const response = await axios.post(route('admin.moderation.projectsaccept', { project: oneProject.value.slug }, {
            comment: ''
        }));

        toast.success("Проект принят!");
    } catch (e) {
        toast.error("Ошибка при принятии проекта.");
        console.error('approve-error', e?.response ?? e);
    }
}

async function onReject() {
    try {
        const response = await axios.post(route('admin.moderation.projectsreject', { project: oneProject.value.slug }, {
            comment: ''
        }));

        toast.success("Проект отклонен!");
    } catch (e) {
        toast.error("Ошибка при отклонении проекта.");
        console.error('reject-error', e?.response ?? e);
    }
}

/** Дебаунс поиска */
const debouncedSearch = debounce(() => runSearch(), 400);
watch(search, debouncedSearch);
watch(order, () => runSearch());
watch(
    () => selected.value,
    () => runSearch(),
    { deep: true }
);

function previewSrc(project) {
    const slug = project?.slug ?? project?.id;
    const hackSlug =
        props.hackathon?.slug

    if (slug && hackSlug && typeof route === "function") {
        try {
            return route("hackathons.projects.image", {
                hackathon: hackSlug,
                project: slug,
            });
        } catch (_) { /* no-op */ }
    }
    return "/project.jpg";
}
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
            <a href="/admin/moderation/projects/" class="main__btn_main" style="width: fit-content">← Назад к хакатонам</a>

            <div class="hackathon__gallery_filter" style="margin-top: 40px">
                <div class="main__search my-hackathon__search">
                    <div class="main__search_container">
                        <input v-model="search" class="main__search_input" :placeholder="langStore.translations.search" />
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path d="M21.07 16.8299L19 14.7099C18.5547 14.2867 17.9931 14.0063 17.3872 13.9047C16.7813 13.8031 16.1589 13.885 15.6 14.1399L14.7 13.2399C15.7606 11.8229 16.2449 10.0566 16.0555 8.29678C15.8662 6.53694 15.0172 4.91417 13.6794 3.75514C12.3417 2.59612 10.6145 1.9869 8.84566 2.05013C7.07679 2.11335 5.39755 2.84433 4.14597 4.09591C2.89439 5.34749 2.16341 7.02674 2.10018 8.79561C2.03695 10.5645 2.64617 12.2916 3.8052 13.6294C4.96422 14.9671 6.58699 15.8161 8.34683 16.0055C10.1067 16.1948 11.8729 15.7105 13.29 14.6499L14.18 15.5399C13.8951 16.0996 13.793 16.7345 13.8881 17.3553C13.9831 17.976 14.2706 18.5513 14.71 18.9999L16.83 21.1199C17.3925 21.6817 18.155 21.9973 18.95 21.9973C19.745 21.9973 20.5075 21.6817 21.07 21.1199C21.3557 20.8405 21.5828 20.5069 21.7378 20.1385C21.8928 19.7702 21.9726 19.3746 21.9726 18.9749C21.9726 18.5753 21.8928 18.1797 21.7378 17.8114C21.5828 17.443 21.3557 17.1093 21.07 16.8299ZM12.59 12.5899C11.8902 13.2879 10.9993 13.7629 10.0297 13.9548C9.06018 14.1467 8.05549 14.0469 7.1426 13.6681C6.22971 13.2893 5.44956 12.6485 4.90071 11.8265C4.35186 11.0045 4.05894 10.0383 4.05894 9.04994C4.05894 8.06157 4.35186 7.09538 4.90071 6.2734C5.44956 5.45143 6.22971 4.81056 7.1426 4.43175C8.05549 4.05294 9.06018 3.95319 10.0297 4.14509C10.9993 4.33699 11.8902 4.81194 12.59 5.50994C13.0556 5.9744 13.4251 6.52615 13.6771 7.13361C13.9292 7.74106 14.0589 8.39227 14.0589 9.04994C14.0589 9.70761 13.9292 10.3588 13.6771 10.9663C13.4251 11.5737 13.0556 12.1255 12.59 12.5899ZM19.66 19.6599C19.567 19.7537 19.4564 19.8281 19.3346 19.8788C19.2127 19.9296 19.082 19.9557 18.95 19.9557C18.818 19.9557 18.6873 19.9296 18.5654 19.8788C18.4436 19.8281 18.333 19.7537 18.24 19.6599L16.12 17.5399C16.0263 17.447 15.9519 17.3364 15.9011 17.2145C15.8503 17.0927 15.8242 16.962 15.8242 16.8299C15.8242 16.6979 15.8503 16.5672 15.9011 16.4454C15.9519 16.3235 16.0263 16.2129 16.12 16.1199C16.213 16.0262 16.3236 15.9518 16.4454 15.9011C16.5673 15.8503 16.698 15.8241 16.83 15.8241C16.962 15.8241 17.0927 15.8503 17.2146 15.9011C17.3364 15.9518 17.447 16.0262 17.54 16.1199L19.66 18.2399C19.7537 18.3329 19.8281 18.4435 19.8789 18.5654C19.9297 18.6872 19.9558 18.8179 19.9558 18.9499C19.9558 19.082 19.9297 19.2127 19.8789 19.3345C19.8281 19.4564 19.7537 19.567 19.66 19.6599Z" fill="#999999"/>
                        </svg>
                    </div>
                    <button type="button" class="main__btn_main" @click="runSearch()">
                        {{ langStore.translations.search }}
                    </button>
                </div>
                <button class="main__btn main__btn_white" @click="showFilter = true">
                    <IconsFilters class="admin__btn_filters" />
                </button>
            </div>

            <div class="project-container">
                <div
                    v-for="project in props.projects.data"
                    :key="project.id"
                    class="hackathon__my-project__item"
                    style="cursor: pointer"
                >
                    <div class="hackathon__my-project__item_header">
                        <img :src="previewSrc(project)" alt="">
                        <p class="main__card_photo-status yellow" v-if="project.status === 2">На рассмотрении</p>
                        <p class="main__card_photo-status red" v-else-if="project.status === 4">Отклонен</p>
                        <p class="main__card_photo-status green" v-else-if="project.status === 3">Принят</p>
                        <button
                            type="button"
                            class="main__btn_main hackathon__my-project__team_svg"
                            @click="openView(project)"
                        >
                            <IconsPencilMyProject/>
                        </button>
                    </div>

                    <div class="hackathon__my-project__item_content">
                        <div>
                            <p class="hackathon__my-project__item_title">{{ project.title }}</p>
                            <p class="hackathon__my-project__item_text">{{ project.description }}</p>
                        </div>

                        <ul class="hackathon__my-project__item_avatar">
                            <li><img src="/profile.jpg" alt="Avatar"></li>
                            <li><img src="/profile.jpg" alt="Avatar"></li>
                            <li><img src="/profile.jpg" alt="Avatar"></li>
                        </ul>
                    </div>
                </div>
            </div>

            <Pagination
                v-if="(props.projects?.meta?.links ?? []).length"
                :links="props.projects.meta.links"
                @go="go"
                style="margin-top: 30px;"
            />

            <FiltersMenu
                v-model="showFilter"
                :order="order"
                :selected="selected"
                @apply="({ order:ord, selected:sel }) => { order = ord; selected = sel; runSearch() }"
                @reset="() => { order='dateD'; selected={status:[]}; search=''; runSearch() }"
            />

            <ViewProject
                v-model="showView"
                :project="oneProject"
                :hackathon-slug="props.hackathon.slug"
                @approve="onApprove"
                @reject="onReject"
            />
        </div>
    </AuthenticatedLayout>
</template>

<style lang="scss" scoped>
    .project-container {
        margin-top: 60px;
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 60px;
        @media screen and (max-width: 1300px) {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>
