import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useNotificationsStore = defineStore('notifications', () => {
    const hasNotifications = ref(false);

    const setNotifications = (data) => {
        hasNotifications.value = data;
        console.log(hasNotifications.value)
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
