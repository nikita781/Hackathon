<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import UsersMenu from "@/Components/Icons/UsersMenu.vue";
import PencilMenu from "@/Components/Icons/PencilMenu.vue";
import MessageMenu from "@/Components/Icons/MessageMenu.vue";
import GridMenu from "@/Components/Icons/GridMenu.vue";

import Pagination from "@/Components/Pagination.vue";
import { useLangStore } from '@/store/lang.js'
import { router } from '@inertiajs/vue3'
import {onMounted, ref, computed, watch, nextTick} from "vue";
import debounce from 'lodash.debounce'

import TableActionsMenu from '@/Components/TableActionsMenu.vue'
import { reactive } from 'vue'
import {useToast} from "vue-toastification";
import ChangeRole from "@/Components/Dialog/Admin/ChangeRole.vue";
import axios from "axios";

const menu = reactive({
    show: false,
    x: 0,
    y: 0,
    user: null,
    anchorKey: null,
})
const rolesResp = ref({ roles: [] })
async function getRoles () {
    try {
        const { data } = await axios.get(route('admin.roles'))
        rolesResp.value = data ?? { roles: [] }
        console.log(rolesResp.value)
    } catch (e) { console.error('roles-error', e) }
}

function openMenu(e, user, key) {
    const rect = e.currentTarget.getBoundingClientRect()
    const GAP = 8
    const MENU_W = 240
    const x = Math.min(rect.right - MENU_W, window.innerWidth - MENU_W - 12)
    const y = rect.bottom + GAP
    Object.assign(menu, { show: true, x, y, user, anchorKey: key })
}
function closeMenu(){ menu.show = false; menu.user = null; }

async function onBlock(u) {
    closeMenu()

    console.log(u.nickname)

    const wasActive = u.status === 1
    const url = wasActive
        ? route('admin.users.block', u.nickname)
        : route('admin.users.unblock', u.nickname)

    const prev = u.status
    u.status = wasActive ? 2 : 1

    try {
        await axios.post(url)
        toast.success(wasActive ? 'Пользователь заблокирован' : 'Пользователь разблокирован', {
            position: 'top-right',
            timeout: 5000,
        });
    } catch (e) {
        u.status = prev
        console.error('block/unblock failed', e?.response ?? e)
    }
}

const showChangeRole = ref(false)
const roleUser = ref(null)

function onRole(u){
    closeMenu()
    roleUser.value = u
    showChangeRole.value = true
}

async function onRoleSaved(){
    await router.reload({ only: ['users'], preserveState:true, preserveScroll:true })
    toast.success('Роли пользователя изменены', { position:'top-right', timeout:5000 })
}

const langStore = useLangStore()

const props = defineProps({
    users: { type: Object, required: true },
    flash: Object,
    auth : { type:Object, required:true },
    notifications : { type:Object, required:true },
    can: Object
})

const toast = useToast();

const showToast = () => {
    if (props.flash?.error) {
        toast.error(props.flash.error, {
            position: 'top-right',
            timeout: 5000,
        });
    } else if (props.flash?.status) {
        toast.success(props.flash.status, {
            position: 'top-right',
            timeout: 5000,
        });
    }
};

watch(() => props.flash, (newFlash) => {
    if (newFlash) {
        showToast();
    }
});

const syncing = ref(false)

const search = ref(new URLSearchParams(window.location.search).get('q') ?? '')

const rows = computed(() => props.users?.data ?? [])

const withQ = (url) => {
    if (!url) return null
    const sep = url.includes('?') ? '&' : '?'
    return search.value ? `${url}${sep}q=${encodeURIComponent(search.value)}` : url
}

const pageLinks = computed(() => (props.users?.links ?? []).map(l => ({ ...l, url: withQ(l.url) })))

function runSearch () {
    const base = route('admin.users.index')
    const url  = withQ(base)
    router.get(url, {}, { preserveState: true, preserveScroll: true, replace: true })
}

watch(search, debounce(runSearch, 400))

onMounted(async () => {
    await langStore.fetchTranslations()
    await getRoles()
    nextTick(() => {
        showToast();
    });
})

async function updateUsers() {
    if (syncing.value) return
    syncing.value = true
    try {
        await axios.post(route('admin.sync-user'))
        await router.reload({ only: ['users'], preserveState: true, preserveScroll: true })
        toast.success('Данные пользователей успешно обновлены!', {
            position: 'top-right',
            timeout: 5000,
        })
    } catch (e) {
        console.error('Ошибка синхронизации данных пользователей', e?.response ?? e)
        toast.error('Ошибка при обновлении данных пользователей.', {
            position: 'top-right',
            timeout: 5000,
        })
    } finally {
        syncing.value = false
    }
}
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
                        <a href="/admin/users" class="sidebar-menu__item active">
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
            <p class="hackathon__my-project__title" style="margin-bottom: 40px">Пользователи</p>

            <button
                type="button"
                class="main__btn_main"
                style="margin-bottom: 30px"
                @click="updateUsers"
                :disabled="syncing"
            >
                {{ syncing ? 'Обновляем…' : 'Обновить данные' }}
            </button>

            <div class="main__search my-hackathon__search" style="margin-bottom: 20px">
                <div class="main__search_container">
                    <input
                        v-model="search"
                        class="main__search_input"
                        :placeholder="langStore.translations.search"
                        @keyup.enter="runSearch"
                    />
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                         xmlns="http://www.w3.org/2000/svg">
                        <path d="M21.07 16.8299L19 14.7099C18.5547 14.2867 17.9931 14.0063 17.3872 13.9047C16.7813 13.8031 16.1589 13.885 15.6 14.1399L14.7 13.2399C15.7606 11.8229 16.2449 10.0566 16.0555 8.29678C15.8662 6.53694 15.0172 4.91417 13.6794 3.75514C12.3417 2.59612 10.6145 1.9869 8.84566 2.05013C7.07679 2.11335 5.39755 2.84433 4.14597 4.09591C2.89439 5.34749 2.16341 7.02674 2.10018 8.79561C2.03695 10.5645 2.64617 12.2916 3.8052 13.6294C4.96422 14.9671 6.58699 15.8161 8.34683 16.0055C10.1067 16.1948 11.8729 15.7105 13.29 14.6499L14.18 15.5399C13.8951 16.0996 13.793 16.7345 13.8881 17.3553C13.9831 17.976 14.2706 18.5513 14.71 18.9999L16.83 21.1199C17.3925 21.6817 18.155 21.9973 18.95 21.9973C19.745 21.9973 20.5075 21.6817 21.07 21.1199C21.3557 20.8405 21.5828 20.5069 21.7378 20.1385C21.8928 19.7702 21.9726 19.3746 21.9726 18.9749C21.9726 18.5753 21.8928 18.1797 21.7378 17.8114C21.5828 17.443 21.3557 17.1093 21.07 16.8299ZM12.59 12.5899C11.8902 13.2879 10.9993 13.7629 10.0297 13.9548C9.06018 14.1467 8.05549 14.0469 7.1426 13.6681C6.22971 13.2893 5.44956 12.6485 4.90071 11.8265C4.35186 11.0045 4.05894 10.0383 4.05894 9.04994C4.05894 8.06157 4.35186 7.09538 4.90071 6.2734C5.44956 5.45143 6.22971 4.81056 7.1426 4.43175C8.05549 4.05294 9.06018 3.95319 10.0297 4.14509C10.9993 4.33699 11.8902 4.81194 12.59 5.50994C13.0556 5.9744 13.4251 6.52615 13.6771 7.13361C13.9292 7.74106 14.0589 8.39227 14.0589 9.04994C14.0589 9.70761 13.9292 10.3588 13.6771 10.9663C13.4251 11.5737 13.0556 12.1255 12.59 12.5899ZM19.66 19.6599C19.567 19.7537 19.4564 19.8281 19.3346 19.8788C19.2127 19.9296 19.082 19.9557 18.95 19.9557C18.818 19.9557 18.6873 19.9296 18.5654 19.8788C18.4436 19.8281 18.333 19.7537 18.24 19.6599L16.12 17.5399C16.0263 17.447 15.9519 17.3364 15.9011 17.2145C15.8503 17.0927 15.8242 16.962 15.8242 16.8299C15.8242 16.6979 15.8503 16.5672 15.9011 16.4454C15.9519 16.3235 16.0263 16.2129 16.12 16.1199C16.213 16.0262 16.3236 15.9518 16.4454 15.9011C16.5673 15.8503 16.698 15.8241 16.83 15.8241C16.962 15.8241 17.0927 15.8503 17.2146 15.9011C17.3364 15.9518 17.447 16.0262 17.54 16.1199L19.66 18.2399C19.7537 18.3329 19.8281 18.4435 19.8789 18.5654C19.9297 18.6872 19.9558 18.8179 19.9558 18.9499C19.9558 19.082 19.9297 19.2127 19.8789 19.3345C19.8281 19.4564 19.7537 19.567 19.66 19.6599Z" fill="#999999"/>
                    </svg>
                </div>
                <button type="button" class="main__btn_main" @click="runSearch">
                    {{ langStore.translations.search }}
                </button>
            </div>

            <div class="admin__table_container">
            <table class="admin__table">
                <thead>
                <tr>
                    <th>Имя</th>
                    <th>Id</th>
                    <th>Роль</th>
                    <th>ФИО</th>
                    <th>Дата рожд.</th>
                    <th>Email</th>
                    <th>Телефон</th>
                    <th>Статус</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="u in rows" :key="u.id">
                    <td>{{ u.nickname ?? '—' }}</td>
                    <td>{{ u.id }}</td>
                    <td><p v-for="role in u.roles" :key="role.id">{{ role.title }}<br></p></td>
                    <td>{{ [u.surname, u.name, u.patronymic].filter(Boolean).join(' ') || '—' }}</td>
                    <td>{{ u.date_of_birth ?? '—' }}</td>
                    <td>{{ u.email ?? '—' }}</td>
                    <td>{{ u.phone_number ?? '—' }}</td>
                    <td>{{ u.status === 1 ? 'Активен' : 'Заблокирован' }}</td>
                    <td style="font-weight: 700; text-align:center">
                        <button class="dots" @click="openMenu($event, u, u.id)" aria-label="Действия">…</button>
                    </td>
                </tr>
                </tbody>
            </table>
            </div>

            <TableActionsMenu
                :show="menu.show"
                :x="menu.x"
                :y="menu.y"
                :user="menu.user"
                :can="props.can"
                @close="closeMenu"
                @block="onBlock"
                @role="onRole"
            />

            <ChangeRole
                v-model="showChangeRole"
                :user="roleUser"
                :options="rolesResp.roles"
                @saved="onRoleSaved"
            />

            <Pagination style="margin-top: 30px;" :links="pageLinks" />
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.td-actions { text-align: center; width: 44px; }
.dots{
    font-weight: 700;
    font-size: 18px;
    line-height: 1;
    padding: 6px 10px;
    background: transparent;
    border: 0;
    border-radius: 8px;
    cursor: pointer;
}
.dots:hover{ background: rgba(0,0,0,.06); }

.main__btn_main[disabled] {
    opacity: .6;
    cursor: not-allowed;
    pointer-events: none;
}
</style>
