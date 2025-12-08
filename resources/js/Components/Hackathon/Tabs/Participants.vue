<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue'
import axios from 'axios'
import Pagination from '@/Components/Pagination.vue'
import { useLangStore } from '@/store/lang.js'
import {useToast} from "vue-toastification";
import {router} from "@inertiajs/vue3";
import CustomSelect from '@/Components/CustomSelect.vue'

const props = defineProps({
    hackathon     : { type: Object, default: null },
    hackathonSlug : { type: String,  default: ''  },
})

const langStore = useLangStore()

const search  = ref('')
const sort    = ref('dateD')
const perPage = ref(8)

const selected = reactive({
    team  : null,
    status: null,
})
function toggle(group, value) {
    selected[group] = selected[group] === value ? null : value
}

const loading = ref(false)
const error   = ref('')
const count   = ref(0)
const teams   = ref([])

const paginationLinks = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })

const slug = computed(() => props.hackathon?.slug ?? props.hackathonSlug)

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

onMounted(async () => {
    await langStore.fetchTranslations()
});

const pagerWrap = ref(null)

function onPaginationClick(e) {
    const root = pagerWrap.value
    if (!root) return
    const a = e.target.closest('a')
    if (!a || !root.contains(a)) return

    if (a.target && a.target !== '_self') return
    if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.button !== 0) return

    const href = a.getAttribute('href') || a.href
    if (!href) return

    const page = extractPage(href)
    if (!page) return

    e.preventDefault()
    fetchTeams(page)
}

const filterGroups = ref([
    {
        name: 'team',
        label: capitalizeFirstLetter(langStore.translations.team),
        options: [
            { label: capitalizeFirstLetter(langStore.translations.hasTeam),  value: 'yes' },
            { label: capitalizeFirstLetter((langStore.translations.worksAlone)), value: 'no'  },
        ],
    },
    {
        name: 'status',
        label: capitalizeFirstLetter(langStore.translations.workStatus),
        options: [
            { label: capitalizeFirstLetter(langStore.translations.draft),     value: 1 },
            { label: capitalizeFirstLetter(langStore.translations.published), value: 3 },
        ],
    },
])

const allowedOrders = new Set(['dateA','dateD','titleA','titleD'])
function mapSort(v) {
    return allowedOrders.has(v) ? v : undefined
}

function mapOrderToSort(v) {
    switch (v) {
        case 'dateA':  return 'dateA'
        case 'dateD':  return 'dateD'
        case 'titleA': return 'titleA'
        case 'titleD': return 'titleD'
        default:       return 'dateD'
    }
}

function readQueryIntoState() {
    const sp = new URLSearchParams(window.location.search)
    const q  = sp.get('q') ?? ''
    const ord = sp.get('order') ?? 'dateD'
    const team = sp.get('team')
    const status = sp.get('status')
    const pp = Number(sp.get('per_page') ?? '') || perPage.value

    search.value = q
    sort.value = mapOrderToSort(ord)
    selected.team = team || null
    selected.status = status ? (isNaN(+status) ? status : +status) : null
    perPage.value = pp
}

function buildParams(page = 1) {
    const order = mapSort(sort.value)
    const params = {
        page,
        per_page: perPage.value,
        order,
    }
    if (search.value.trim()) params.q = search.value.trim()
    if (selected.team)   params.team   = selected.team
    if (selected.status) params.status = selected.status
    return params
}

function makePaginationLinks(current, last) {
    const base = route('hackathons.teams.index', { hackathon: slug.value })
    const qs = (p) => {
        const pms = buildParams(p)
        const sp = new URLSearchParams()
        Object.entries(pms).forEach(([k, v]) => {
            if (Array.isArray(v)) v.forEach(x => sp.append(k + '[]', x))
            else if (v !== undefined && v !== null && v !== '') sp.append(k, v)
        })
        return sp.toString()
    }
    const links = []
    links.push({ url: current > 1 ? `${base}?${qs(current - 1)}` : null, label: '&laquo; Previous', active: false })
    for (let i = 1; i <= last; i++) links.push({ url: `${base}?${qs(i)}`, label: String(i), active: i === current })
    links.push({ url: current < last ? `${base}?${qs(current + 1)}` : null, label: 'Next &raquo;', active: false })
    return links
}

function syncUrl(page = 1) {
    const href = `${pageBaseHref()}?${buildPageQuery(page)}`
    window.history.replaceState({}, '', href)
}

async function fetchTeams(page = 1) {
    if (!slug.value) return
    loading.value = true; error.value = ''
    try {
        const { data } = await axios.get(
            route('hackathons.teams.index', { hackathon: slug.value }),
            { params: buildParams(page), headers: { Accept: 'application/json' } }
        )

        // 1) { teams: Array, count }
        // 2) { teams: { data, meta:{...}, links:[...] }, count }
        console.log(data)
        const payload = data?.teams
        if (Array.isArray(payload)) {
            teams.value = payload
            const total = Number(data?.count ?? payload.length)
            const last  = Math.max(1, Math.ceil(total / perPage.value))
            meta.value  = { current_page: page, last_page: last, total }
            paginationLinks.value = toPageLinks([], page, last)
            count.value = total
        } else {
            teams.value = payload?.data ?? []
            const m = payload?.meta ?? payload ?? {}
            const current = Number(m.current_page ?? page)
            const last    = Number(m.last_page ?? 1)
            const total   = Number(m.total ?? teams.value.length)

            meta.value = { current_page: current, last_page: last, total }
            paginationLinks.value = toPageLinks(m.links, current, last)
            count.value = Number(data?.count ?? total)
        }
        syncUrl(meta.value.current_page)
    } catch (e) {
        console.error('teams-index', e?.response ?? e)
        error.value = e?.response?.data?.message || e.message || 'Ошибка загрузки'
    } finally {
        loading.value = false
    }
}

function extractPage(url) {
    if (!url) return null
    try {
        const u = new URL(url, window.location.origin)
        const p = u.searchParams.get('page')
        return p ? Number(p) : 1
    } catch {
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

function isCaptain(item) {
    const n = (item?.position?.name || '').toLowerCase()
    return n === 'капитан' || n === 'captain' || item?.position?.slug === 'captain' || item?.position?.id === 1
}

function captainOf(team) {
    return (team?.users || []).find(isCaptain) || null
}

function membersOf(team) {
    const list = team?.users || []
    const cap = captainOf(team)
    return cap ? list.filter(u => u !== cap) : list
}

let t
watch(search, () => { clearTimeout(t); t = setTimeout(() => fetchTeams(1), 400) })
watch([sort, () => selected.team, () => selected.status], () => fetchTeams(1))


onMounted(async () => {
    await langStore.fetchTranslations()
    await nextTick()
    readQueryIntoState()
    const sp = new URLSearchParams(window.location.search)
    const page = Number(sp.get('page') ?? '1') || 1
    fetchTeams(page)
})

function pageBaseHref() {
    return window.location.pathname
}

function buildPageQuery(page) {
    const sp = new URLSearchParams()
    const pms = buildParams(page)
    Object.entries(pms).forEach(([k,v]) => {
        if (Array.isArray(v)) v.forEach(x => sp.append(k + '[]', x))
        else if (v !== undefined && v !== null && v !== '') sp.append(k, v)
    })
    return sp.toString()
}

function toPageLinks(metaLinks, current, last) {
    if (Array.isArray(metaLinks) && metaLinks.length) {
        return metaLinks.map(l => {
            const p = extractPage(l.url)
            return {
                ...l,
                url: p ? `${pageBaseHref()}?${buildPageQuery(p)}` : null,
            }
        })
    }
    const base = pageBaseHref()
    const links = []
    links.push({ url: current > 1 ? `${base}?${buildPageQuery(current - 1)}` : null, label: '&laquo; Previous', active: false })
    for (let i = 1; i <= last; i++) links.push({ url: `${base}?${buildPageQuery(i)}`, label: String(i), active: i === current })
    links.push({ url: current < last ? `${base}?${buildPageQuery(current + 1)}` : null, label: 'Next &raquo;', active: false })
    return links
}

const toast = useToast();

const syncing = ref(false)

function saveBlob(blob, filename) {
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
}

function getFilenameFromDisposition(disposition, fallback) {
    if (!disposition) return fallback
    const mStar = /filename\*\s*=\s*UTF-8''([^;]+)/i.exec(disposition)
    if (mStar?.[1]) return decodeURIComponent(mStar[1])
    const m = /filename\s*=\s*"?([^";]+)"?/i.exec(disposition)
    return m?.[1] || fallback
}

async function finishHackathon() {
    if (syncing.value || !slug.value) return
    syncing.value = true
    try {
        const url = route('hackathons.download-users', { hackathon: slug.value })
        const { data, headers } = await axios.get(url, { responseType: 'blob' })

        const type = headers['content-type'] || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        const blob = new Blob([data], { type })
        const fallbackName = `hackathon_users_${slug.value}.xlsx`
        const filename = getFilenameFromDisposition(headers['content-disposition'], fallbackName)

        saveBlob(blob, filename)
        useToast().success(capitalizeFirstLetter(langStore.translations.participants_export_started))
    } catch (e) {
        console.error('download-users', e?.response ?? e)
        useToast().error(e?.response?.data?.message || 'Не удалось выгрузить участников.')
    } finally {
        syncing.value = false
    }
}

const PLACEHOLDER = '/profile.jpg';

function avatarSrc(photo) {
    if (!photo) return PLACEHOLDER;
    const url = String(photo).trim();

    const hasFileName = /[^/]+\.[a-z0-9]+(?:\?.*)?$/i.test(url);
    if (!hasFileName) return PLACEHOLDER;

    return url;
}

function imgFallback(e) {
    e.target.onerror = null;
    e.target.src = PLACEHOLDER;
}

const sortOptions = computed(() => [
    {
        value: 'dateD',
        label: `${capitalizeFirstLetter(langStore.translations.byNovelty)} ↓`,
    },
    {
        value: 'dateA',
        label: `${capitalizeFirstLetter(langStore.translations.byNovelty)} ↑`,
    },
    {
        value: 'titleA',
        label: `${capitalizeFirstLetter(langStore.translations.by_name)} ↑`,
    },
    {
        value: 'titleD',
        label: `${capitalizeFirstLetter(langStore.translations.by_name)} ↓`,
    },
])
</script>

<template>
    <div class="hackathon__tab">
        <div class="hackathon__gallery">
            <button
                type="button"
                class="main__btn_main"
                style="width: fit-content; max-width: unset"
                @click="finishHackathon"
                :disabled="syncing"
            >
                {{ syncing ? capitalizeFirstLetter(langStore.translations.exporting) : capitalizeFirstLetter(langStore.translations.export_participants) }}
            </button>
            <div class="hackathon__gallery_filter">
                <div class="main__search my-hackathon__search">
                    <div class="main__search_container">
                        <input
                            v-model="search"
                            class="main__search_input"
                            :placeholder="langStore.translations.search"
                        />
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path d="M21.07 16.8299L19 14.7099C18.5547 14.2867 17.9931 14.0063 17.3872 13.9047C16.7813 13.8031 16.1589 13.885 15.6 14.1399L14.7 13.2399C15.7606 11.8229 16.2449 10.0566 16.0555 8.29678C15.8662 6.53694 15.0172 4.91417 13.6794 3.75514C12.3417 2.59612 10.6145 1.9869 8.84566 2.05013C7.07679 2.11335 5.39755 2.84433 4.14597 4.09591C2.89439 5.34749 2.16341 7.02674 2.10018 8.79561C2.03695 10.5645 2.64617 12.2916 3.8052 13.6294C4.96422 14.9671 6.58699 15.8161 8.34683 16.0055C10.1067 16.1948 11.8729 15.7105 13.29 14.6499L14.18 15.5399C13.8951 16.0996 13.793 16.7345 13.8881 17.3553C13.9831 17.976 14.2706 18.5513 14.71 18.9999L16.83 21.1199C17.3925 21.6817 18.155 21.9973 18.95 21.9973C19.745 21.9973 20.5075 21.6817 21.07 21.1199C21.3557 20.8405 21.5828 20.5069 21.7378 20.1385C21.8928 19.7702 21.9726 19.3746 21.9726 18.9749C21.9726 18.5753 21.8928 18.1797 21.7378 17.8114C21.5828 17.443 21.3557 17.1093 21.07 16.8299ZM12.59 12.5899C11.8902 13.2879 10.9993 13.7629 10.0297 13.9548C9.06018 14.1467 8.05549 14.0469 7.1426 13.6681C6.22971 13.2893 5.44956 12.6485 4.90071 11.8265C4.35186 11.0045 4.05894 10.0383 4.05894 9.04994C4.05894 8.06157 4.35186 7.09538 4.90071 6.2734C5.44956 5.45143 6.22971 4.81056 7.1426 4.43175C8.05549 4.05294 9.06018 3.95319 10.0297 4.14509C10.9993 4.33699 11.8902 4.81194 12.59 5.50994C13.0556 5.9744 13.4251 6.52615 13.6771 7.13361C13.9292 7.74106 14.0589 8.39227 14.0589 9.04994C14.0589 9.70761 13.9292 10.3588 13.6771 10.9663C13.4251 11.5737 13.0556 12.1255 12.59 12.5899ZM19.66 19.6599C19.567 19.7537 19.4564 19.8281 19.3346 19.8788C19.2127 19.9296 19.082 19.9557 18.95 19.9557C18.818 19.9557 18.6873 19.9296 18.5654 19.8788C18.4436 19.8281 18.333 19.7537 18.24 19.6599L16.12 17.5399C16.0263 17.447 15.9519 17.3364 15.9011 17.2145C15.8503 17.0927 15.8242 16.962 15.8242 16.8299C15.8242 16.6979 15.8503 16.5672 15.9011 16.4454C15.9519 16.3235 16.0263 16.2129 16.12 16.1199C16.213 16.0262 16.3236 15.9518 16.4454 15.9011C16.5673 15.8503 16.698 15.8241 16.83 15.8241C16.962 15.8241 17.0927 15.8503 17.2146 15.9011C17.3364 15.9518 17.447 16.0262 17.54 16.1199L19.66 18.2399C19.7537 18.3329 19.8281 18.4435 19.8789 18.5654C19.9297 18.6872 19.9558 18.8179 19.9558 18.9499C19.9558 19.082 19.9297 19.2127 19.8789 19.3345C19.8281 19.4564 19.7537 19.567 19.66 19.6599Z" fill="#999999"/>
                        </svg>
                    </div>
                    <button type="button" class="main__btn_main" @click="fetchTeams(1)">{{ langStore.translations.search }}</button>
                </div>

                <div class="main__cards_filter hackathon__gallery_sort">
                    <p>{{ langStore.translations.sort }}:</p>
                    <CustomSelect
                        v-model="sort"
                        :options="sortOptions"
                        full-width
                    />
                </div>
            </div>

            <div class="main__filter main__filter-phone">
                <div v-for="group in filterGroups" :key="group.name" class="main__filter_item">
                    <p class="main__filter_title">{{ group.label }}</p>

                    <div
                        v-for="option in group.options"
                        :key="option.value"
                        class="main__filter_input"
                        :class="{ active: selected[group.name] === option.value }"
                        @click="toggle(group.name, option.value)"
                    >
                        <div class="custom-checkbox"></div>
                        <p>{{ option.label }}</p>
                    </div>
                </div>
            </div>

            <div class="main__container" v-if="langStore.translations" style="padding:0; margin:0">
                <div class="main__filter">
                    <div v-for="group in filterGroups" :key="group.name" class="main__filter_item">
                        <p class="main__filter_title">{{ group.label }}</p>

                        <div
                            v-for="option in group.options"
                            :key="option.value"
                            class="main__filter_input"
                            :class="{ active: selected[group.name] === option.value }"
                            @click="toggle(group.name, option.value)"
                        >
                            <div class="custom-checkbox"></div>
                            <p>{{ option.label }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="loading" class="my-2">{{ capitalizeFirstLetter(langStore.translations.loading) }}...</div>
                <div v-else-if="error" class="my-2" style="color:#e44">{{ error }}</div>

                <div class="main__cards" v-if="!loading">
                    <p class="hackathon__participants_text">{{ capitalizeFirstLetter(langStore.translations.total) }}: {{ count }}</p>

                    <div class="hackathon__participants_container" v-if="teams.length">
                        <div
                            v-for="team in teams"
                            :key="team.id"
                            class="hackathon__participants_item"
                        >
                            <p class="hackathon__participants_title">{{ team.title ?? team.name }}</p>

                            <template v-if="captainOf(team)">
                                <div class="hackathon__my-project__list_item">
                                    <div class="hackathon__my-project__list_container">
                                        <img :src="avatarSrc(captainOf(team).user?.photo)" @error="imgFallback" alt="Avatar">
                                        <p class="hackathon__my-project__list_text">{{ captainOf(team).user?.nickname }}</p>
                                    </div>
                                    <p class="hackathon__my-project__list_text">{{ captainOf(team).position?.name ?? 'Капитан' }}</p>
                                </div>
                            </template>

                            <div class="hackathon__participants_team" v-if="(team.users?.length ?? 0) > 0">
                                <p class="hackathon__participants_text" style="margin-bottom: unset">{{ capitalizeFirstLetter(langStore.translations.team_title) }}</p>

                                <div
                                    v-for="m in membersOf(team)"
                                    :key="m.user?.id ?? `${team.id}-m`"
                                    class="hackathon__my-project__list_item"
                                >
                                    <div class="hackathon__my-project__list_container">
                                        <img :src="avatarSrc(m.user?.photo)" @error="imgFallback" alt="Avatar">
                                        <p class="hackathon__my-project__list_text">{{ m.user?.nickname }}</p>
                                    </div>
                                    <p class="hackathon__my-project__list_text">{{ m.position?.name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div ref="pagerWrap" @click="onPaginationClick" style="margin-top:24px">
                        <Pagination :links="paginationLinks" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
</style>
