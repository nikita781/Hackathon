<script setup>
import {router, usePage} from '@inertiajs/vue3'
import {computed, nextTick, onMounted, ref} from 'vue'
import LangArrow from "@/Components/Icons/LangArrow.vue";
import {useLangStore} from '@/store/lang'
import {useNotificationsStore} from "@/store/notification.js";
import Login from "@/Components/Dialog/Login.vue";

const page = usePage()
const menuOpen = ref(false)

const langStore = useLangStore()
const notificationsStore = useNotificationsStore()

const notifications = ref(notificationsStore.hasNotifications)

const isActiveMyHackathons = computed(() => {
    return usePage().url.startsWith('/my-hackathons')
})

const langMenuOpen = ref(false)
const overLangWrap = ref(false)
const overLangMenu = ref(false)

const overHeader = ref(false)
const overMenu = ref(false)

function handleLangWrapEnter() {
    langMenuOpen.value = true
}
function handleLangWrapLeave() {
    overLangWrap.value = false
}
function handleLangMenuEnter() {
    overLangMenu.value = true
}
function handleLangMenuLeave() {
    overLangMenu.value = false
}
function tryCloseLang() {
    if (!overLangWrap.value && !overLangMenu.value) langMenuOpen.value = false
}

function handleAvatarEnter() {
    menuOpen.value = true
}
function handleMenuEnter() {
    overMenu.value = true
}
function handleMenuLeave() {
    overMenu.value = false
    tryClose()
    tryCloseLang()
}
function handleHeaderEnter() {
    overHeader.value = true
}
function handleHeaderLeave() {
    overHeader.value = false
    tryClose()
    tryCloseLang()
}
function tryClose() {
    if (!overHeader.value && !overMenu.value) {
        menuOpen.value = false
    }
}

const isAuthenticated = computed(() => !!page.props.auth?.user)
const user = computed(() => page.props.auth?.user)

function logout() {
    router.get(route('logout'))
}

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

onMounted(async () => {
    await langStore.fetchTranslations()
})

async function changeLanguage(lang) {
    try {
        await langStore.switchLanguage(lang)
    } catch (error) {
        console.error("Ошибка при смене языка:", error)
    }
}

const showLogin = ref(false)

const langMap = {
    ru: { flag: '/language/ru.jpg', label: 'RU' },
    en: { flag: '/language/en.png', label: 'EN' },
    fr: { flag: '/language/fr.png', label: 'FR' },
    zh_cn: { flag: '/language/zh.png', label: 'ZH' },
    de: { flag: '/language/de.jpg', label: 'DE' },
    es: { flag: '/language/es.jpg', label: 'ES' },
    pt: { flag: '/language/pt.jpg', label: 'PT' }
}

const normalizeLang = (code) => (code || '').toLowerCase().split('-')[0]

const currentLang = computed(() => {
    const key = normalizeLang(langStore.currentLanguage)
    return langMap[key] ?? langMap.ru
})

</script>

<template>
    <header class="header" @mouseenter="handleHeaderEnter" @mouseleave="handleHeaderLeave">
        <div class="header__container">
            <div class="header__head">
                <a href="/">
                    <img src="/logo.png" alt="Logo" class="header__logo"/>
                </a>
                <div class="header__main">
                    <div class="header__content">
                        <a href="/my-hackathons" class="header__link" :class="{ active: isActiveMyHackathons }"
                           v-if="isAuthenticated">{{ langStore.translations.my_hackathons }}</a>
                        <div class="header__btns" v-else>
                            <a @click="showLogin = true" class="main__btn">{{ langStore.translations.Login }}</a>
                            <a href="https://foncode.ru/register" target="_blank" class="main__btn main__btn_white"
                               style="color: white !important">{{ langStore.translations.Register }}</a>
                        </div>
                        <Login
                            v-model="showLogin"
                        />
                        <div class="header__btns_phone" v-if="isAuthenticated">
                            <a href="/notification" class="header__notification">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M18 13.18V10C17.9986 8.58312 17.4958 7.21247 16.5806 6.13077C15.6655 5.04908 14.3971 4.32615 13 4.09V3C13 2.73478 12.8946 2.48043 12.7071 2.29289C12.5196 2.10536 12.2652 2 12 2C11.7348 2 11.4804 2.10536 11.2929 2.29289C11.1054 2.48043 11 2.73478 11 3V4.09C9.60294 4.32615 8.33452 5.04908 7.41939 6.13077C6.50425 7.21247 6.00144 8.58312 6 10V13.18C5.41645 13.3863 4.911 13.7681 4.55294 14.2729C4.19488 14.7778 4.00174 15.3811 4 16V18C4 18.2652 4.10536 18.5196 4.29289 18.7071C4.48043 18.8946 4.73478 19 5 19H8.14C8.37028 19.8474 8.873 20.5954 9.5706 21.1287C10.2682 21.6621 11.1219 21.951 12 21.951C12.8781 21.951 13.7318 21.6621 14.4294 21.1287C15.127 20.5954 15.6297 19.8474 15.86 19H19C19.2652 19 19.5196 18.8946 19.7071 18.7071C19.8946 18.5196 20 18.2652 20 18V16C19.9983 15.3811 19.8051 14.7778 19.4471 14.2729C19.089 13.7681 18.5835 13.3863 18 13.18ZM8 10C8 8.93913 8.42143 7.92172 9.17157 7.17157C9.92172 6.42143 10.9391 6 12 6C13.0609 6 14.0783 6.42143 14.8284 7.17157C15.5786 7.92172 16 8.93913 16 10V13H8V10ZM12 20C11.651 19.9979 11.3086 19.9045 11.0068 19.7291C10.7051 19.5536 10.4545 19.3023 10.28 19H13.72C13.5455 19.3023 13.2949 19.5536 12.9932 19.7291C12.6914 19.9045 12.349 19.9979 12 20ZM18 17H6V16C6 15.7348 6.10536 15.4804 6.29289 15.2929C6.48043 15.1054 6.73478 15 7 15H17C17.2652 15 17.5196 15.1054 17.7071 15.2929C17.8946 15.4804 18 15.7348 18 16V17Z"
                                        fill="white"/>
                                </svg>
                                <span v-if="notifications" class="header__notification_active"></span>
                            </a>
                            <div class="header__profile" @mouseenter="handleAvatarEnter">
                                <img src="/profile.jpg" alt="Profile" class="header__profile_img"/>
                                <div class="header__profile_menu"
                                     v-show="menuOpen"
                                     @mouseenter="handleMenuEnter"
                                     @mouseleave="handleMenuLeave">
                                    <a href="/profile"
                                       class="header__profile_menu_item">{{ capitalizeFirstLetter(langStore.translations.profile) }}</a>
                                    <a @click.prevent="logout"
                                       class="header__profile_menu_item"
                                        style="
                                            border-bottom-right-radius: 6px;
                                            border-bottom-left-radius: 6px;
                                        "
                                    >{{ capitalizeFirstLetter(langStore.translations.logout) }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="header__lang"
                         @mouseenter="handleLangWrapEnter"
                         @mouseleave="handleLangWrapLeave">
                        <div class="header__lang_flag">
                            <img :src="currentLang.flag" alt="RU">
                        </div>
                        <p class="header__lang_text">{{ currentLang.label }}</p>
                        <LangArrow/>
                        <div class="header__lang_menu"
                             v-show="langMenuOpen"
                             @mouseenter="handleLangMenuEnter"
                             @mouseleave="handleLangMenuLeave">
                            <div class="header__lang_item" @click="changeLanguage('ru')">
                                <div class="header__lang_flag">
                                    <img src="/language/ru.jpg" alt="RU">
                                </div>
                                <p class="header__lang_text">RU</p>
                            </div>
                            <div class="header__lang_item" @click="changeLanguage('en')">
                                <div class="header__lang_flag">
                                    <img src="/language/en.png" alt="RU">
                                </div>
                                <p class="header__lang_text">EN</p>
                            </div>
                            <div class="header__lang_item" @click="changeLanguage('fr')">
                                <div class="header__lang_flag">
                                    <img src="/language/fr.png" alt="RU">
                                </div>
                                <p class="header__lang_text">FR</p>
                            </div>
                            <div class="header__lang_item" @click="changeLanguage('zh')">
                                <div class="header__lang_flag">
                                    <img src="/language/zh.png" alt="RU">
                                </div>
                                <p class="header__lang_text">ZH</p>
                            </div>
                            <div class="header__lang_item" @click="changeLanguage('de')">
                                <div class="header__lang_flag">
                                    <img src="/language/de.jpg" alt="RU">
                                </div>
                                <p class="header__lang_text">DE</p>
                            </div>
                            <div class="header__lang_item" @click="changeLanguage('es')">
                                <div class="header__lang_flag">
                                    <img src="/language/es.jpg" alt="RU">
                                </div>
                                <p class="header__lang_text">ES</p>
                            </div>
                            <div class="header__lang_item" @click="changeLanguage('pt')">
                                <div class="header__lang_flag">
                                    <img src="/language/pt.jpg" alt="RU">
                                </div>
                                <p class="header__lang_text">PT</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="header__lang phone"
                 @mouseenter="handleLangWrapEnter"
                 @mouseleave="handleLangWrapLeave">
                <div class="header__lang_flag">
                    <img :src="currentLang.flag" alt="RU">
                </div>
                <p class="header__lang_text">{{ currentLang.label }}</p>
                <LangArrow/>
                <div class="header__lang_menu"
                     v-show="langMenuOpen"
                     @mouseenter="handleLangMenuEnter"
                     @mouseleave="handleLangMenuLeave">
                    <div class="header__lang_item" @click="changeLanguage('ru')">
                        <div class="header__lang_flag">
                            <img src="/language/ru.jpg" alt="RU">
                        </div>
                        <p class="header__lang_text">RU</p>
                    </div>
                    <div class="header__lang_item" @click="changeLanguage('en')">
                        <div class="header__lang_flag">
                            <img src="/language/en.png" alt="RU">
                        </div>
                        <p class="header__lang_text">EN</p>
                    </div>
                    <div class="header__lang_item" @click="changeLanguage('fr')">
                        <div class="header__lang_flag">
                            <img src="/language/fr.png" alt="RU">
                        </div>
                        <p class="header__lang_text">FR</p>
                    </div>
                    <div class="header__lang_item" @click="changeLanguage('zh')">
                        <div class="header__lang_flag">
                            <img src="/language/zh.png" alt="RU">
                        </div>
                        <p class="header__lang_text">ZH</p>
                    </div>
                    <div class="header__lang_item" @click="changeLanguage('de')">
                        <div class="header__lang_flag">
                            <img src="/language/de.jpg" alt="RU">
                        </div>
                        <p class="header__lang_text">DE</p>
                    </div>
                    <div class="header__lang_item" @click="changeLanguage('es')">
                        <div class="header__lang_flag">
                            <img src="/language/es.jpg" alt="RU">
                        </div>
                        <p class="header__lang_text">ES</p>
                    </div>
                    <div class="header__lang_item" @click="changeLanguage('pt')">
                        <div class="header__lang_flag">
                            <img src="/language/pt.jpg" alt="RU">
                        </div>
                        <p class="header__lang_text">PT</p>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>

<style scoped>

</style>
