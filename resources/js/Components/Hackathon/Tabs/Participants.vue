<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue'
import axios from 'axios'
import Pagination from '@/Components/Pagination.vue'
import { useLangStore } from '@/store/lang.js'

// props: можно передать либо весь объект хакатона, либо только slug
const props = defineProps({
    hackathon     : { type: Object, default: null },
    hackathonSlug : { type: String, default: '' },
})

const langStore = useLangStore()

// --- UI state
const search   = ref('')
const sort     = ref('dateD') // dateD – по новизне ↓; dateA – ↑; titleA/D – по названию
const perPage  = ref(8)

// фильтры "как в примере" — логика toggle() и класс .active на контейнере
const selected = reactive({
    team   : [], // ['online', 'offline'] — как в вашем примере надписей
    status : [], // ['upcoming','ongoing']
})

function toggle(group, value) {
    const i = selected[group].indexOf(value)
    i > -1 ? selected[group].splice(i, 1) : selected[group].push(value)
}

// --- данные
const loading = ref(false)
const error   = ref('')
const count   = ref(0)
const teams   = ref([])

const paginationLinks = ref([]) // сюда кладём teams.meta.links из ответа
const meta = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
})

const slug = computed(() => props.hackathon?.slug ?? props.hackathonSlug)

// фильтр-группы (надписи можно локализовать)
const filterGroups = ref([
    {
        name: 'team',
        label: 'Команда',
        options: [
            { label: 'Есть команда',  value: 'online'  },
            { label: 'Работает один', value: 'offline' },
        ],
    },
    {
        name: 'status',
        label: 'Статус работы',
        options: [
            { label: 'Отправлена',     value: 'upcoming' },
            { label: 'Не отправлена',  value: 'ongoing'  },
        ],
    },
])

// сортировка -> параметры
function mapSort(v) {
    switch (v) {
        case 'dateA':  return 'dateA'
        case 'dateD':  return 'dateD'
        case 'titleA': return 'titleA'
        case 'titleD': return 'titleD'
        default:       return undefined
    }
}

// соберём query под ваш бэкенд (если у filter($request) другие ключи — меняем тут)
function buildParams(page = 1) {
    const s = mapSort(sort.value)
    const params = {
        page,
        per_page: perPage.value,
        s
    }
    if (search.value.trim()) params.q = search.value.trim()
    if (selected.team.length)   params['team']   = selected.team
    if (selected.status.length) params['status'] = selected.status
    return params
}

function makePaginationLinks(current, last) {
    const base = route('hackathons.teams.index', { hackathon: slug.value })

    const qs = (p) => {
        const pms = buildParams(p)        // сохраняем все фильтры/поиск/сортировку
        const sp = new URLSearchParams()
        Object.entries(pms).forEach(([k, v]) => {
            if (Array.isArray(v)) v.forEach(x => sp.append(k + '[]', x))
            else if (v !== undefined && v !== null && v !== '') sp.append(k, v)
        })
        return sp.toString()
    }

    const links = []
    // prev
    links.push({
        url: current > 1 ? `${base}?${qs(current - 1)}` : null,
        label: '&laquo; Previous',
        active: false
    })
    // pages 1..last
    for (let i = 1; i <= last; i++) {
        links.push({
            url: `${base}?${qs(i)}`,
            label: String(i),
            active: i === current
        })
    }
    // next
    links.push({
        url: current < last ? `${base}?${qs(current + 1)}` : null,
        label: 'Next &raquo;',
        active: false
    })
    return links
}

async function fetchTeams(page = 1) {
    if (!slug.value) return
    loading.value = true
    error.value = ''
    try {
        const { data } = await axios.get(
            route('hackathons.teams.index', { hackathon: slug.value }),
            { params: buildParams(page), headers: { Accept: 'application/json' } }
        )

        console.log(data)

        // поддержка ДВУХ форматов:
        // 1) { teams: Array, count: number }
        // 2) { teams: { data:[], meta:{...}, links:[...] }, count: number }
        const payload = data?.teams

        if (Array.isArray(payload)) {
            // === твой текущий бэк (как на скрине) ===
            teams.value = payload
            const total = Number(data?.count ?? payload.length)
            const last  = Math.max(1, Math.ceil(total / perPage.value))
            meta.value  = { current_page: page, last_page: last, total }
            paginationLinks.value = makePaginationLinks(page, last)
            count.value = total
        } else {
            // === классический пагинатор Laravel ===
            teams.value = payload?.data ?? []
            // meta может лежать либо в payload.meta, либо в payload
            const m = payload?.meta ?? payload ?? {}
            meta.value = {
                current_page: m.current_page ?? page,
                last_page   : m.last_page ?? 1,
                total       : m.total ?? teams.value.length
            }
            paginationLinks.value = payload?.links ?? payload?.meta?.links ?? []
            count.value = data?.count ?? meta.value.total
        }
    } catch (e) {
        console.error('teams-index', e?.response ?? e)
        error.value = e?.response?.data?.message || e.message || 'Ошибка загрузки'
    } finally {
        loading.value = false
    }
}

// Навигация по пагинации (компонент отдаёт полную ссылку ?page=N)
// Парсим номер страницы и перезапрашиваем через axios
function extractPage(url) {
    if (!url) return null
    try {
        const u = new URL(url, window.location.origin)
        const p = u.searchParams.get('page')
        return p ? Number(p) : 1
    } catch {
        // относительная ссылка без домена
        const q = url.split('?')[1] || ''
        const params = new URLSearchParams(q)
        const p = params.get('page')
        return p ? Number(p) : 1
    }
}
function go(url) {
    const page = extractPage(url)
    if (!page) return
    fetchTeams(page)
}

// дебаунс поиска
let t
watch(search, () => {
    clearTimeout(t)
    t = setTimeout(() => fetchTeams(1), 400)
})
// обновление при смене сортировки/фильтров
watch([sort, () => selected.team, () => selected.status], () => fetchTeams(1))

onMounted(async () => {
    await langStore.fetchTranslations()
    await nextTick()
    fetchTeams(1)
})

// утилита форматирования дат, если понадобится
function formatDate(dateStr) {
    const d = new Date(dateStr)
    const dd = String(d.getDate()).padStart(2, '0')
    const mm = String(d.getMonth() + 1).padStart(2, '0')
    const yy = d.getFullYear()
    return `${dd}.${mm}.${yy}`
}
</script>

<template>
    <div class="hackathon__tab">
        <div class="hackathon__gallery">
            <div class="hackathon__gallery_filter">
                <!-- Поиск -->
                <div class="main__search my-hackathon__search">
                    <div class="main__search_container">
                        <input
                            v-model="search"
                            class="main__search_input"
                            :placeholder="langStore.translations.search"
                        />
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path d="M21.07 16.8299L19 14.7099C18.5547 14.2867 17.9931 14.0063 17.3872 13.9047C16.7813 13.8031 16.1589 13.885 15.6 14.1399L14.7 13.2399C15.7606 11.8229 16.2449 10.0566 16.0555 8.29678C15.8662 6.53694 15.0172 4.91417 13.6794 3.75514C12.3417 2.59612 10.6145 1.9869 8.84566 2.05013C7.07679 2.11335 5.39755 2.84433 4.14597 4.09591C2.89439 5.34749 2.16341 7.02674 2.10018 8.79561C2.03695 10.5645 2.64617 12.2916 3.8052 13.6294C4.96422 14.9671 6.58699 15.8161 8.34683 16.0055C10.1067 16.1948 11.8729 15.7105 13.29 14.6499L14.18 15.5399C13.8951 16.0996 13.793 16.7345 13.8881 17.3553C13.9831 17.976 14.2706 18.5513 14.71 18.9999L16.83 21.1199C17.3925 21.6817 18.155 21.9973 18.95 21.9973C19.745 21.9973 20.5075 21.6817 21.07 21.1199C21.3557 20.8405 21.5828 20.5069 21.7378 20.1385C21.8928 19.7702 21.9726 19.3746 21.9726 18.9749C21.9726 18.5753 21.8928 18.1797 21.7378 17.8114C21.5828 17.443 21.3557 17.1093 21.07 16.8299ZM12.59 12.5899C11.8902 13.2879 10.9993 13.7629 10.0297 13.9548C9.06018 14.1467 8.05549 14.0469 7.1426 13.6681C6.22971 13.2893 5.44956 12.6485 4.90071 11.8265C4.35186 11.0045 4.05894 10.0383 4.05894 9.04994C4.05894 8.06157 4.35186 7.09538 4.90071 6.2734C5.44956 5.45143 6.22971 4.81056 7.1426 4.43175C8.05549 4.05294 9.06018 3.95319 10.0297 4.14509C10.9993 4.33699 11.8902 4.81194 12.59 5.50994C13.0556 5.9744 13.4251 6.52615 13.6771 7.13361C13.9292 7.74106 14.0589 8.39227 14.0589 9.04994C14.0589 9.70761 13.9292 10.3588 13.6771 10.9663C13.4251 11.5737 13.0556 12.1255 12.59 12.5899Z" fill="#999999"/>
                        </svg>
                    </div>
                    <button type="button" class="main__btn_main" @click="fetchTeams(1)">
                        {{ langStore.translations.search }}
                    </button>
                </div>

                <!-- Сортировка -->
                <div class="main__cards_filter hackathon__gallery_sort" style="width: 320px">
                    <p>{{ langStore.translations.sort }}:</p>
                    <select v-model="sort" class="main__cards_select">
                        <option value="dateD">По новизне ↓</option>
                        <option value="dateA">По новизне ↑</option>
                        <option value="titleA">По названию ↑</option>
                        <option value="titleD">По названию ↓</option>
                    </select>
                </div>
            </div>

            <div class="main__container" v-if="langStore.translations" style="padding: 0; margin: 0">
                <!-- Фильтры как в вашем примере: логика toggle() + класс .active -->
                <div class="main__filter">
                    <div
                        v-for="group in filterGroups"
                        :key="group.name"
                        class="main__filter_item"
                    >
                        <p class="main__filter_title">{{ group.label }}</p>

                        <div
                            v-for="option in group.options"
                            :key="option.value"
                            class="main__filter_input"
                            :class="{ active: selected[group.name].includes(option.value) }"
                            @click="toggle(group.name, option.value)"
                        >
                            <div class="custom-checkbox"></div>
                            <p>{{ option.label }}</p>
                        </div>
                    </div>
                </div>

                <!-- Статусы загрузки -->
                <div v-if="loading" class="my-2">Загрузка…</div>
                <div v-else-if="error" class="my-2" style="color:#e44">{{ error }}</div>

                <!-- Список команд -->
                <div class="main__cards" v-if="!loading">
                    <p class="hackathon__participants_text" v-if="!loading">Всего: {{ count }}</p>
                    <div class="hackathon__participants_container" v-if="teams.length">
                        <div
                            v-for="team in teams"
                            :key="team.id"
                            class="hackathon__participants_item"
                        >
                            <p class="hackathon__participants_title">{{ team.title ?? team.name }}</p>

                            <!-- Капитан -->
                            <div class="hackathon__my-project__list_item" v-if="team.captain">
                                <div class="hackathon__my-project__list_container">
                                    <img :src="team.captain?.avatar ?? '/profile.jpg'" alt="Avatar">
                                    <p class="hackathon__my-project__list_text">{{ team.captain?.name }}</p>
                                </div>
                                <p class="hackathon__my-project__list_text">Капитан</p>
                            </div>

                            <!-- Участники -->
                            <div class="hackathon__participants_team" v-if="Array.isArray(team.members) && team.members.length">
                                <p class="hackathon__participants_text" style="margin-bottom: unset">Команда</p>

                                <div
                                    v-for="m in team.members"
                                    :key="m.id"
                                    class="hackathon__my-project__list_item"
                                >
                                    <div class="hackathon__my-project__list_container">
                                        <img :src="m.avatar ?? '/profile.jpg'" alt="Avatar">
                                        <p class="hackathon__my-project__list_text">{{ m.name }}</p>
                                    </div>
                                    <p class="hackathon__my-project__list_text">{{ m.role ?? '' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="my-2">Ничего не найдено</div>
                </div>

                <!-- Пагинация -->
                <Pagination
                    v-if="(meta?.last_page ?? 1) > 1 && paginationLinks.length"
                    :links="paginationLinks"
                    @navigate="go"
                    style="margin-top: 24px"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
</style>
