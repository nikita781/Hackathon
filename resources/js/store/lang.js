import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

// Глобальная переменная для базового URL
const BASE_URL = import.meta.env.VITE_APP_URL || 'http://127.0.0.1:8000'; // Замените на конфигурацию вашего приложения

const localeMap = {
    ru: 'ru_RU',
    en: 'en_US',
    es: 'es',
    zh: 'zh_CH',
    fr: 'fr_FR',
    de: 'de_DE',
    pt: 'pt_PT'
}

export const useLangStore = defineStore('lang', () => {
    const translations = ref({})
    const currentLanguage = ref('ru')

    const fetchTranslations = async (lang = 'ru') => {
        try {
            const response = await axios.get(`${BASE_URL}/lang/${lang}.json`)
            translations.value = response.data
            currentLanguage.value = lang
        } catch (error) {
            console.error('Ошибка загрузки переводов:', error)
        }
    }

    async function switchLanguage(langShort = 'en') {
        try {
            const locale = localeMap[langShort] ?? langShort
            await axios.get(`${BASE_URL}/lang/switch/${locale}`)
            await fetchTranslations(langShort)
        } catch (e) {
            console.error('Ошибка смены языка:', e)
        }
    }

    return {
        translations,
        currentLanguage,
        fetchTranslations,
        switchLanguage
    }
})
