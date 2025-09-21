<script setup>
import {ref, computed, watch, onMounted} from 'vue'
import Pagination from "@/Components/Pagination.vue";
import { router } from "@inertiajs/vue3";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import AcceptInvitationToJoin from "@/Components/Dialog/AcceptInvitationToJoin.vue";
import {useLangStore} from "@/store/lang.js";

const props = defineProps({
    notifications: Object,
    auth : { type:Object, required:true },
    flash: Object,
})

const langStore = useLangStore()

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

const showAcceptInvitationToJoin  = ref(false)

const formatDate = (date) => {
    const formattedDate = new Date(date)
    const day = String(formattedDate.getDate()).padStart(2, '0')
    const month = String(formattedDate.getMonth() + 1).padStart(2, '0')
    const year = formattedDate.getFullYear()

    return `${day}.${month}.${year}`
}

const links = computed(() => props.notifications?.links || []);
const currentPage = computed(() => props.notifications?.current_page || 1);

async function markNotificationsAsRead() {
    const ids = props.notifications.data.map((notification) => notification.id);

    try {
        const fd = new FormData();

        ids.forEach(id => fd.append('ids[]', id));
        fd.append('_method', 'PATCH');

        await axios.post(
            route('notification.mark-as-read'),
            fd,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );

        // console.log('Notifications marked as read successfully');
    } catch (err) {
        console.error('Error marking notifications as read:', err?.response ?? err);
    }
}
markNotificationsAsRead();

function go(pageUrl) {
    router.get(pageUrl, {}, { preserveState: true, preserveScroll: true, replace: true });
    markNotificationsAsRead();
}

const currentUrl = ref("");

function openModal(url) {
    currentUrl.value = url;
    showAcceptInvitationToJoin.value = true;
}

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

onMounted(async () => {
    await langStore.fetchTranslations()
});
</script>

<template>
    <AuthenticatedLayout
        :auth="props.auth"
    >
        <div class="notification">
<!--            <pre>{{props.notifications}}</pre>-->
            <h2 class="notification__title">Уведомления</h2>
            <div class="notification__container">
<!--                <pre>{{props.notifications}}</pre>-->
                <div v-for="n in props.notifications.data" :key="n.id"
                     class="notification__item"
                     >
                    <div class="notification__image">
                        <img :src="n?.hackathon?.image_path ?? '/test.jpg'" alt="">
                    </div>

<!--                    <pre>{{n}}</pre>-->

                    <div class="notification__content">
                        <div class="notification__main">
                            <p  class="notification__name">{{ n?.title }}</p>
                            <p  class="notification__text">{{ n?.description }}</p>
                            <p  class="notification__text">{{ n?.message }}</p>
                            <button
                                v-if="n?.url"
                                type="button"
                                class="main__btn dialog__btn notification__btn"
                                style="max-width: fit-content"
                                @click="openModal(n?.url)"
                                :class="{ blocked: !n.is_active }"
                                :disabled="!n.is_active"
                            >
                                {{ !n.is_active ? capitalizeFirstLetter(langStore.translations.invitationAccepted) : capitalizeFirstLetter(langStore.translations.confirm) }}
                            </button>
                        </div>
                        <p class="profile__tabs_awards_item_date">{{ capitalizeFirstLetter(langStore.translations.sentStatus) }} {{ formatDate(n.created_at) }}</p>
                    </div>
                    <AcceptInvitationToJoin
                        v-model="showAcceptInvitationToJoin"
                        :url="currentUrl"
                    />
                </div>
            </div>
            <Pagination style="margin-top: 40px;" :links="links" @navigate="go" />
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>

</style>
