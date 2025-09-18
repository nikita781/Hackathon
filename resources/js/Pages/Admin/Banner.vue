<!-- resources/js/Pages/Admin/Contents/Banners.vue (страница со списком) -->
<script setup>
import GridMenu from "@/Components/Icons/GridMenu.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import MessageMenu from "@/Components/Icons/MessageMenu.vue";
import UsersMenu from "@/Components/Icons/UsersMenu.vue";
import PencilMenu from "@/Components/Icons/PencilMenu.vue";
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useLangStore } from "@/store/lang.js";
import { useToast } from "vue-toastification";
import Sortable from "sortablejs";
import CreateBanners from "@/Components/Dialog/CreateBanners.vue";

const toast = useToast();
const langStore = useLangStore();

const props = defineProps({
    banners: { type: Object, required: true }, // массив или пагинация {data:[]}
});

const activeTab = ref(1);
function goTab(i) {
    if (i === 0) {
        router.get(route("admin.contents.tagsindex"), { preserveState: true, preserveScroll: true, replace: true });
    } else if (i === 1) {
        router.get(route("admin.contents.bannersindex"), { preserveState: true, preserveScroll: true, replace: true });
    } else {
        router.get(route("admin.contents.awardsindex"), { preserveState: true, preserveScroll: true, replace: true });
    }
}

const tabEls = ref([]);
const setTabRef = (i) => (el) => { if (el) tabEls.value[i] = el; };
const sliderStyle = computed(() => {
    const el = tabEls.value[activeTab.value];
    if (!el) return {};
    return { left: `${el.offsetLeft}px`, width: `${el.offsetWidth}px` };
});

/* ---- Таблица + DnD ---- */
const tbodyRef = ref(null);
const sortableRef = ref(null);
const isSaving = ref(false);

// нормализуем входные баннеры в локальный массив
const rows = ref(Array.isArray(props.banners) ? [...props.banners] : Array.isArray(props.banners?.data) ? [...props.banners.data] : []);
watch(
    () => props.banners,
    (v) => { rows.value = Array.isArray(v) ? [...v] : Array.isArray(v?.data) ? [...v.data] : []; },
    { deep: true }
);

function saveOrder() {
    if (isSaving.value) return;
    isSaving.value = true;
    sortableRef.value?.option("disabled", true);

    const payload = { banners: rows.value.map((r, idx) => ({ id: r.id, order: idx + 1 })) };

    router.post(
        route("admin.contents.bannerschange-order"),
        payload,
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onSuccess: () => {
                toast.success("Порядок баннеров успешно изменён", { position: "top-right", timeout: 4000 });
            },
            onError: (err) => {
                const first = err && Object.values(err)[0];
                toast.error(first || "Не удалось сохранить новый порядок", { position: "top-right", timeout: 5000 });
            },
            onFinish: () => {
                isSaving.value = false;
                sortableRef.value?.option("disabled", false);
            },
        }
    );
}

function initSortable() {
    if (!tbodyRef.value) return;
    sortableRef.value?.destroy();

    sortableRef.value = Sortable.create(tbodyRef.value, {
        draggable: "tr",
        handle: ".drag-handle",
        animation: 200,
        direction: "vertical",
        ghostClass: "row-ghost",
        chosenClass: "row-chosen",
        dragClass: "row-drag",
        fallbackOnBody: true,
        scroll: true,
        scrollSensitivity: 30,
        scrollSpeed: 10,
        onEnd(evt) {
            const { oldIndex, newIndex } = evt;
            if (oldIndex === newIndex) return;

            const arr = rows.value.slice();
            const [moved] = arr.splice(oldIndex, 1);
            arr.splice(newIndex, 0, moved);
            rows.value = arr;

            saveOrder();
        },
    });
}

/* ---- Диалог создания/редактирования ---- */
const showBanner = ref(false);
const editingBanner = ref(null);

function openCreate() {
    editingBanner.value = null;
    showBanner.value = true;
}
function openEdit(row, e) {
    // игнорируем клики по ручке dnd и по кнопке удаления
    if (e?.target?.closest(".drag-handle") || e?.target?.closest(".btn-delete")) return;
    editingBanner.value = row;
    showBanner.value = true;
}
function onSaved() {
    // После успеха страница перерисуется через Inertia (redirect back)
}

/* ---- Удаление ---- */
const deletingId = ref(null);
function deleteBanner(row) {
    if (deletingId.value) return;
    if (!confirm("Удалить баннер?")) return;

    deletingId.value = row.id;
    router.post(
        route("admin.contents.bannersdelete", row.id),
        { _method: "delete" },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onSuccess: () => {
                toast.success("Баннер успешно удалён", { position: "top-right", timeout: 4000 });
            },
            onError: () => {
                toast.error("Не удалось удалить баннер", { position: "top-right", timeout: 5000 });
            },
            onFinish: () => { deletingId.value = null; },
        }
    );
}

onMounted(async () => {
    await langStore.fetchTranslations();
    await nextTick(() => {
        tabEls.value = Array.from(document.querySelectorAll(".my-hackathon__tabs_item"));
        initSortable();
    });
});
onBeforeUnmount(() => { sortableRef.value?.destroy(); });

function bannerSrc(row) {
    return route('banners.image', { banner: row.id })
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
                            <UsersMenu />
                            <p class="sidebar-menu__label">Пользователи</p>
                        </a>
                    </div>
                    <div>
                        <a href="/admin/moderation/hackathons" class="sidebar-menu__item">
                            <p class="sidebar-menu__label"></p>
                            <PencilMenu />
                            <p class="sidebar-menu__label">Модерация</p>
                        </a>
                    </div>
                    <div>
                        <a href="/admin/support" class="sidebar-menu__item">
                            <p class="sidebar-menu__label"></p>
                            <MessageMenu />
                            <p class="sidebar-menu__label">Обратная связь</p>
                        </a>
                    </div>
                    <div>
                        <a href="/admin/contents/tags" class="sidebar-menu__item active">
                            <p class="sidebar-menu__label"></p>
                            <GridMenu />
                            <p class="sidebar-menu__label">Контент</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin__content">
            <p class="hackathon__my-project__title" style="margin-bottom: 40px">Контент</p>

            <div class="my-hackathon__tabs">
                <p :ref="setTabRef(0)" :class="['my-hackathon__tabs_item',{active:activeTab===0}]" @click="goTab(0)">Теги</p>
                <p :ref="setTabRef(1)" :class="['my-hackathon__tabs_item',{active:activeTab===1}]" @click="goTab(1)">Баннеры</p>
                <p :ref="setTabRef(2)" :class="['my-hackathon__tabs_item',{active:activeTab===2}]" @click="goTab(2)">Награды</p>
                <div class="slider" :style="sliderStyle"></div>
            </div>

            <div class="btn-container">
                <div class="my-hackathon__btn main__btn_main" @click="openCreate">
                    Добавить
                    <div>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M15.8333 9.16683H10.8333V4.16683C10.8333 3.94582 10.7455 3.73385 10.5892 3.57757C10.4329 3.42129 10.2209 3.3335 9.99992 3.3335C9.7789 3.3335 9.56694 3.42129 9.41066 3.57757C9.25438 3.73385 9.16658 3.94582 9.16658 4.16683V9.16683H4.16659C3.94557 9.16683 3.73361 9.25463 3.57733 9.41091C3.42105 9.56719 3.33325 9.77915 3.33325 10.0002C3.33325 10.2212 3.42105 10.4331 3.57733 10.5894C3.73361 10.7457 3.94557 10.8335 4.16659 10.8335H9.16658V15.8335C9.16658 16.0545 9.25438 16.2665 9.41066 16.4228C9.56694 16.579 9.7789 16.6668 9.99992 16.6668C10.2209 16.6668 10.4329 16.579 10.5892 16.4228C10.7455 16.2665 10.8333 16.0545 10.8333 15.8335V10.8335H15.8333C16.0543 10.8335 16.2662 10.7457 16.4225 10.5894C16.5788 10.4331 16.6666 10.2212 16.6666 10.0002C16.6666 9.77915 16.5788 9.56719 16.4225 9.41091C16.2662 9.25463 16.0543 9.16683 15.8333 9.16683Z" fill="white"/>
                        </svg>
                    </div>
                </div>
            </div>

            <table class="admin__table admin__table--draggable" style="margin-top: 30px;">
                <thead>
                <tr>
                    <th>Номер</th>
                    <th>Изображение</th>
                    <th style="width: 15px;"></th>
                    <th style="width: 15px;"></th>
                </tr>
                </thead>
                <tbody ref="tbodyRef">
                <tr
                    v-for="u in rows"
                    :key="u.id"
                    class="draggable-row"
                    @click="openEdit(u, $event)"
                >
                    <td>{{ u.order ?? '—' }}</td>
                    <td>
                        <img :src="bannerSrc(u)" alt="" class="banner-thumb" />
                    </td>
                    <td style="text-align:end; padding: 14px 5px 14px 16px">
                        <button class="drag-handle btn-drop" aria-label="Переместить" title="Переместить" @click.stop>
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="6" cy="6" r="1.5" fill="currentColor" />
                                <circle cx="10" cy="6" r="1.5" fill="currentColor" />
                                <circle cx="14" cy="6" r="1.5" fill="currentColor" />
                                <circle cx="6" cy="10" r="1.5" fill="currentColor" />
                                <circle cx="10" cy="10" r="1.5" fill="currentColor" />
                                <circle cx="14" cy="10" r="1.5" fill="currentColor" />
                                <circle cx="6" cy="14" r="1.5" fill="currentColor" />
                                <circle cx="10" cy="14" r="1.5" fill="currentColor" />
                                <circle cx="14" cy="14" r="1.5" fill="currentColor" />
                            </svg>
                        </button>
                    </td>
                    <td style="text-align:end; padding: 14px 16px 14px 5px">
                        <button class="btn-drop btn-delete" aria-label="Удалить" title="Удалить" @click.stop="deleteBanner(u)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="17" viewBox="0 0 16 17" fill="none">
                                <path d="M8.93931 8.50012L13.1393 4.30679C13.2648 4.18125 13.3354 4.01099 13.3354 3.83346C13.3354 3.65592 13.2648 3.48566 13.1393 3.36012C13.0138 3.23459 12.8435 3.16406 12.666 3.16406C12.4884 3.16406 12.3182 3.23459 12.1926 3.36012L7.99931 7.56012L3.80597 3.36012C3.68044 3.23459 3.51018 3.16406 3.33264 3.16406C3.15511 3.16406 2.98484 3.23459 2.85931 3.36012C2.73377 3.48566 2.66325 3.65592 2.66325 3.83346C2.66325 4.01099 2.73377 4.18125 2.85931 4.30679L7.05931 8.50012L2.85931 12.6935C2.79682 12.7554 2.74723 12.8292 2.71338 12.9104C2.67954 12.9916 2.66211 13.0788 2.66211 13.1668C2.66211 13.2548 2.67954 13.3419 2.71338 13.4232C2.74723 13.5044 2.79682 13.5781 2.85931 13.6401C2.92128 13.7026 2.99502 13.7522 3.07626 13.7861C3.1575 13.8199 3.24463 13.8373 3.33264 13.8373C3.42065 13.8373 3.50779 13.8199 3.58903 13.7861C3.67027 13.7522 3.744 13.7026 3.80597 13.6401L7.99931 9.44012L12.1926 13.6401C12.2546 13.7026 12.3284 13.7522 12.4096 13.7861C12.4908 13.8199 12.578 13.8373 12.666 13.8373C12.754 13.8373 12.8411 13.8199 12.9224 13.7861C13.0036 13.7522 13.0773 13.7026 13.1393 13.6401C13.2018 13.5781 13.2514 13.5044 13.2852 13.4232C13.3191 13.3419 13.3365 13.2548 13.3365 13.1668C13.3365 13.0788 13.3191 12.9916 13.2852 12.9104C13.2514 12.8292 13.2018 12.7554 13.1393 12.6935L8.93931 8.50012Z" fill="#121212"/>
                            </svg>
                        </button>
                    </td>
                </tr>
                </tbody>
            </table>

<!--            <pre>{{props.banners}}</pre>-->

            <CreateBanners
                v-model="showBanner"
                :banner="editingBanner"
                @saved="onSaved"
            />
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.btn-container {
    display: flex;
    justify-content: flex-end;
    margin-top: 30px;
}
.btn-drop {
    display: flex;
    align-items: center;
    justify-content: center;
    appearance: none;
    background: transparent;
    border: 0;
    padding: 4px;
    cursor: pointer;
}
.banner-thumb {
    max-height: 96px;
    max-width: 153px;
    object-fit: cover;
    display: block;
    border-radius: 10px;
}

/* строка кликабельна для редактирования */
.admin__table--draggable tbody tr.draggable-row {
    transition: background-color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease;
    cursor: pointer;
}
.admin__table--draggable tbody tr.draggable-row:hover {
    background: #fafafa;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}

/* ручка перетаскивания */
.admin__table--draggable .drag-handle {
    color: #999;
    cursor: grab;
    border-radius: 8px;
    transition: background-color 0.15s ease, color 0.15s ease, transform 0.1s ease;
}
.admin__table--draggable .drag-handle:hover { background: #f3f3f3; color: #666; }
.admin__table--draggable .drag-handle:active { cursor: grabbing; transform: scale(0.98); }

/* классы SortableJS */
.admin__table--draggable tbody tr.row-chosen { background: #fff7f9; }
.admin__table--draggable tbody tr.row-drag { box-shadow: 0 8px 24px rgba(0,0,0,.12); transform: scale(1.01); }
.admin__table--draggable tbody tr.row-ghost { opacity: .6; }

/* заголовки */
.admin__table thead th { user-select: none; }
</style>
