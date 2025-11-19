import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

const BASE_URL = import.meta.env.VITE_APP_URL || 'http://127.0.0.1:8000';

const supportedLanguages = ['ru', 'en', 'es', 'zh', 'fr', 'de', 'pt'];

export const useLangStore = defineStore('lang', () => {
    const translations = ref({})
    const currentLanguage = ref(localStorage.getItem('language') || 'ru');

    async function fetchTranslations(lang = currentLanguage.value) {
        try {
            const fileLang = lang === 'zh' ? 'zh_CN' : lang;
            const { data } = await axios.get(`${BASE_URL}/lang/${fileLang}.json`)
            translations.value = data
            currentLanguage.value = lang
            localStorage.setItem('language', lang)
        } catch (error) {
            console.error('Error fetching translations:', error)
        }
    }

    async function switchLanguage(lang = 'en') {
        try {
            if (!supportedLanguages.includes(lang)) {
                console.error('Unsupported language:', lang)
                return
            }

            await axios.get(`${BASE_URL}/lang/switch/${lang}`)
            await fetchTranslations(lang)

            window.location.reload()
        } catch (error) {
            console.error('Error switching language:', error)
        }
    }

    async function init() {
        await fetchTranslations(currentLanguage.value)
    }

    return {
        translations,
        currentLanguage,
        fetchTranslations,
        switchLanguage,
        init
    }
})
