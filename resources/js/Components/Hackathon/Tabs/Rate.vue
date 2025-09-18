<script setup>
import { useLangStore } from '@/store/lang.js'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import IconsPencilMyProject from '@/Components/Icons/PencilMyProject.vue'
import RateProject from '@/Components/Dialog/RateProject.vue'
import axios from 'axios'
import Document from '@/Components/Icons/Document.vue'
import Hyperlink from '@/Components/Icons/Hyperlink.vue'

const showRate = ref(false)

const props = defineProps({
    positions  : { type: Array,  default: () => [] },
    ownTeam    : { type: Array,  default: () => [] },
    hackathon  : { type: Object, required: true },
    tabs       : { type: Array,  default: () => [] },
    allProjects: { type: Array,  default: () => [] },
})

const langStore = useLangStore()

const search  = ref('')
const sort    = ref('dateA')
const page    = ref(1)
const perPage = ref(12)
const loading = ref(false)

const items     = ref([])
const total     = ref(0)
const lastPage  = ref(1)
const previews  = ref(Object.create(null))

const oneProject = ref(null)
const isOne      = computed(() => !!oneProject.value)
const oneGallery = ref([])
const galleryLoading = ref(false)

let abortCtrl = null
let searchTimer = null

// --- ТАБЫ «Оценить / Оценено» ---
// 0 = Оценить (еще не оцененные) → rated=no
// 1 = Оценено (уже оцененные)    → rated=yes
const activeTab = ref(0)
function setActiveTab (idx) {
    if (activeTab.value === idx) return
    activeTab.value = idx
    fetchGallery(1)
}

// Анимация слайдера как в примере my-hackathons
const tabsRef = ref([])
const sliderStyle = ref({})
function recalcSlider () {
    nextTick(() => {
        const els = document.querySelectorAll('.my-hackathon__tabs_item')
        tabsRef.value = els
        const el = els?.[activeTab.value]
        if (!el) return
        sliderStyle.value = {
            left : `${el.offsetLeft}px`,
            width: `${el.offsetWidth}px`,
        }
    })
}

onMounted(async () => {
    await langStore.fetchTranslations()
    await fetchGallery(1)
    recalcSlider()
    window.addEventListener('resize', recalcSlider)
})

onBeforeUnmount(() => {
    Object.values(previews.value).forEach((url) => URL.revokeObjectURL(url))
    previews.value = {}
    if (abortCtrl) abortCtrl.abort()
    window.removeEventListener('resize', recalcSlider)
})

const mapSortToBackend = (v) => {
    switch (v) {
        case 'dateA':  return 'dateA'
        case 'dateD':  return 'dateD'
        case 'titleA': return 'titleA'
        case 'titleD': return 'titleD'
        default:       return undefined
    }
}

const buildParams = (pageNum = 1) => {
    const s = mapSortToBackend(sort.value)
    const q = (search.value || '').trim()
    const params = {
        page    : pageNum,
        per_page: perPage.value,
        // фильтр по табу
        rated   : activeTab.value === 0 ? 'no' : 'yes',
    }
    if (q) {
        params.search = q
        params.q = q
    }
    if (s) params.order = s
    return params
}

const truncate = (text, max = 180) => {
    if (!text) return ''
    return text.length > max ? text.slice(0, max) + '…' : text
}

async function fetchGallery (toPage = 1) {
    if (abortCtrl) abortCtrl.abort()
    abortCtrl = new AbortController()
    loading.value = true

    console.log(buildParams(toPage))

    try {
        const { data } = await axios.get(
            route('hackathons.gallery', { hackathon: props.hackathon.slug }),
            { params: buildParams(toPage), signal: abortCtrl.signal }
        )

        const payload = data.gallery ?? data
        const paged = payload?.data && Array.isArray(payload.data)
            ? payload
            : (payload?.data?.data && Array.isArray(payload.data.data)
                ? { ...payload.data, ...payload.meta }
                : payload)

        const list = Array.isArray(paged?.data) ? paged.data : (Array.isArray(payload) ? payload : [])

        items.value   = list
        page.value    = Number(paged?.current_page ?? 1)
        lastPage.value= Number(paged?.last_page ?? 1)
        total.value   = Number(paged?.total ?? list.length)

        list.forEach((p) => {
            const key = p.slug ?? p.id
            if (!key || previews.value[key]) return
            loadPreviewSafe(key)
        })
    } catch (e) {
        console.error('gallery-fetch', e?.response ?? e)
    } finally {
        loading.value = false
    }
}

async function loadPreviewSafe (projectKey) {
    try {
        const proj = items.value.find(p => (p.slug ?? p.id) === projectKey)
        if (!proj?.slug) return
        const { data: blob } = await axios.get(
            route('hackathons.projects.image', { hackathon: props.hackathon.slug, project: proj.slug }),
            { responseType: 'blob' }
        )
        previews.value[projectKey] = URL.createObjectURL(blob)
    } catch (_) {}
}

watch(sort, () => fetchGallery(1))

watch(search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => fetchGallery(1), 400)
})

// При смене таба — перерисовываем слайдер
watch(activeTab, () => {
    recalcSlider()
})

// Когда обновляются переводы (может поменяться ширина текста табов) — пересчитать слайдер
watch(
    () => langStore.translations,
    () => recalcSlider(),
    { deep: true }
)

const canPrev = computed(() => page.value > 1)
const canNext = computed(() => page.value < lastPage.value)

function goPrev () { if (canPrev.value) fetchGallery(page.value - 1) }
function goNext () { if (canNext.value) fetchGallery(page.value + 1) }
function goPage (p) { if (p >= 1 && p <= lastPage.value) fetchGallery(p) }

const pagesWindow = computed(() => {
    const W = 2
    const cur = page.value
    const last = lastPage.value
    const start = Math.max(1, cur - W)
    const end = Math.min(last, cur + W)

    const seq = []
    if (start > 1) seq.push(1, 'gap')
    for (let i = start; i <= end; i++) seq.push(i)
    if (end < last) seq.push('gap', last)
    return seq
})

const getTitle = (p) => p.title ?? p.name ?? 'Без названия'
const getDesc  = (p) => truncate(p.short_description ?? p.description ?? '')
const getKey   = (p) => p.slug ?? p.id
const getPreviewSrc = (p) => {
    const key = getKey(p)
    return previews.value[key] || (p.preview_url ?? '/project.jpg')
}

function openProject(p) {
    oneProject.value = p
    loadProjectGallery(p)
}
function closeProject() {
    oneProject.value = null
    oneGallery.value = []
}

async function loadProjectGallery (project) {
    if (!project?.slug) { oneGallery.value = []; return }
    galleryLoading.value = true
    try {
        const { data } = await axios.get(
            route('hackathons.projects.gallery', { hackathon: props.hackathon.slug, project: project.slug }),
            { headers: { Accept: 'application/json' } }
        )
        const raw = data?.gallery ?? []
        oneGallery.value = raw.map(it => ({
            id  : typeof it === 'object' ? (it.id ?? it.media_id ?? it.key ?? null) : null,
            url : typeof it === 'string'
                ? it
                : (it.url ?? it.original_url ?? it.preview_url ?? it.path ?? ''),
            name: typeof it === 'object' ? (it.name ?? it.filename ?? '') : '',
        })).filter(it => it.url)
    } catch (e) {
        console.error('project-gallery', e?.response ?? e)
        oneGallery.value = []
    } finally {
        galleryLoading.value = false
    }
}

const oneTitle = computed(() => getTitle(oneProject.value || {}))
const oneShortDesc  = computed(() => oneProject.value?.description || '')
const oneDesc  = computed(() => oneProject.value?.about || '')
const oneStack = computed(() => oneProject.value?.stack || '')
const onePreview = computed(() => oneProject.value ? getPreviewSrc(oneProject.value) : '/project.jpg')
const links = computed(() => ({
    project     : oneProject.value?.project_link || '',
    presentation: oneProject.value?.presentation_path || oneProject.value?.presentation_url || '',
    video       : oneProject.value?.video_link || '',
}))

const idProject = ref(null)
function setIdProject (id) {
    console.log('Selected project id:', id)
    idProject.value = id
    showRate.value = true
}
</script>

<template>
    <div class="hackathon__tab">
        <div v-if="!isOne" class="hackathon__tab_main">
            <div class="hackathon__tab_container">
                <p class="hackathon__my-project__title">Оценить Проекты</p>

                <!-- Табы с анимированным слайдером -->
                <div class="my-hackathon__tabs">
                    <p
                        :class="['my-hackathon__tabs_item',{active:activeTab===0}]"
                        @click="setActiveTab(0)"
                    >
                        Оценить
                    </p>
                    <p
                        :class="['my-hackathon__tabs_item',{active:activeTab===1}]"
                        @click="setActiveTab(1)"
                    >
                        Оценено
                    </p>
                    <div class="slider" :style="sliderStyle"></div>
                </div>

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
                        <button type="button" class="main__btn_main" @click="fetchGallery(1)">
                            {{ langStore.translations.search }}
                        </button>
                    </div>

                    <div class="main__cards_filter hackathon__gallery_sort">
                        <p>{{ langStore.translations.sort }}:</p>
                        <select v-model="sort" class="main__cards_select hackathon__gallery_sort-select" style="min-width: 185px">
                            <option value="dateA">По дате ↑</option>
                            <option value="dateD">По дате ↓</option>
                            <option value="titleA">По названию ↑</option>
                            <option value="titleD">По названию ↓</option>
                        </select>
                    </div>
                </div>

                <div class="hackathon__gallery_container">
                    <template v-if="loading">
                        <div v-for="i in 6" :key="'s'+i" class="hackathon__my-project__item">
                            <div class="skeleton-loader" style="height: 180px; border-radius: 12px;"></div>
                            <div class="skeleton-loader" style="height: 90px; margin-top: 12px;"></div>
                        </div>
                    </template>
                    <template v-else>
                        <div
                            v-for="project in items"
                            :key="getKey(project)"
                            class="hackathon__my-project__item"
                            @click="openProject(project)"
                            style="cursor: pointer"
                        >
                            <div class="hackathon__my-project__item_header">
                                <img :src="getPreviewSrc(project)" alt="">
                                <button
                                    type="button"
                                    class="main__btn_main"
                                    @click.stop="setIdProject(project.slug)"
                                >
                                    Оценить
                                </button>
                            </div>

                            <div class="hackathon__my-project__item_content">
                                <div>
                                    <p class="hackathon__my-project__item_title">{{ getTitle(project) }}</p>
                                    <p class="hackathon__my-project__item_text">{{ getDesc(project) }}</p>
                                </div>

                                <ul class="hackathon__my-project__item_avatar">
                                    <li><img src="/profile.jpg" alt="Avatar"></li>
                                    <li><img src="/profile.jpg" alt="Avatar"></li>
                                    <li><img src="/profile.jpg" alt="Avatar"></li>
                                </ul>
                            </div>
                        </div>
                    </template>

                    <RateProject
                        v-model="showRate"
                        :hackathon="props.hackathon"
                        :project-id="idProject"
                    />
                </div>
            </div>
        </div>

        <div v-else class="hackathon__tab_main">
            <div class="hackathon__tab_container">
                <div style="display:flex; gap:12px; align-items:center; margin-bottom:12px;">
                    <button type="button" class="main__btn_main" @click="closeProject">← Назад к проектам</button>
                    <p class="hackathon__my-project__title" style="margin:0">{{ oneTitle }}</p>
                </div>

                <div class="hackathon__oneProject_image">
                    <img :src="onePreview" alt="">
                </div>

                <p class="">{{ oneShortDesc }}</p>
            </div>

            <div class="hackathon__tab_container" v-if="oneDesc">
                <p class="hackathon__my-project__title">Описание</p>
                <p class="">{{ oneDesc }}</p>
            </div>

            <div class="hackathon__tab_container" v-if="oneStack">
                <p class="hackathon__my-project__title">Технологический стек проекта</p>
                <p class="">{{ oneStack }}</p>
            </div>

            <div class="hackathon__tab_container">
                <p class="hackathon__my-project__title">Галерея проекта</p>

                <div v-if="galleryLoading" class="hackathon__oneProject_gallery">
                    <div v-for="i in 4" :key="'g'+i" class="skeleton-loader" style="height:160px"></div>
                </div>

                <div v-else-if="oneGallery.length" class="hackathon__oneProject_gallery">
                    <a
                        v-for="img in oneGallery"
                        :key="img.id ?? img.url"
                        :href="img.url"
                        class="hackathon__oneProject_gallery-item"
                        target="_blank" rel="noopener noreferrer"
                        title="Открыть в новой вкладке"
                    >
                        <img :src="img.url" :alt="img.name || 'Изображение проекта'">
                    </a>
                </div>
            </div>

            <div class="hackathon__tab_container">
                <p class="hackathon__my-project__title">Материалы</p>

                <div class="hackathon__oneProject_media" v-if="links.project">
                    <p class="hackathon__oneProject_media-title">Ссылка на проект</p>
                    <div class="hackathon__oneProject_media-item">
                        <Hyperlink />
                        <a :href="links.project" target="_blank" rel="noopener noreferrer">Ссылка</a>
                    </div>
                </div>

                <div class="hackathon__oneProject_media" v-if="links.presentation">
                    <p class="hackathon__oneProject_media-title">Презентация</p>
                    <div class="hackathon__oneProject_media-item">
                        <Document />
                        <a :href="links.presentation" target="_blank" rel="noopener noreferrer">Файл</a>
                    </div>
                </div>

                <div class="hackathon__oneProject_media" v-if="links.video">
                    <p class="hackathon__oneProject_media-title">Ссылка на видео</p>
                    <div class="hackathon__oneProject_media-item">
                        <Hyperlink />
                        <a :href="links.video" target="_blank" rel="noopener noreferrer">Ссылка</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
