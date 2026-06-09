import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useNotificationsStore = defineStore('notifications', () => {
    const hasNotifications = ref(false);

    const setNotifications = (data) => {
        hasNotifications.value = data;
    };

    const getNotifications = () => {
        return hasNotifications.value;
    };

    return {
        hasNotifications,
        setNotifications,
        getNotifications,
    };
});
