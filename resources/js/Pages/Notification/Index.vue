<script setup>
import { ref, computed }      from 'vue'

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import AcceptInvitationToJoin from "@/Components/Dialog/AcceptInvitationToJoin.vue";

const props = defineProps({
    notifications: Object
})

const selected = ref([])

const showAcceptInvitationToJoin  = ref(false)
</script>

<template>
    <AuthenticatedLayout>
        <pre>{{props.notifications}}</pre>
        <div class="notification">
            <h2 class="notification__title">Уведомления</h2>
            <div class="notification__container">
                <div v-for="n in props.notifications.data" :key="n.id"
                     class="notification__item"
                     >
                    <div class="notification__image">
                        <img :src="n.data.image ?? '/placeholder.jpg'" alt="">
                    </div>

                    <div class="notification__content">
                        <div class="notification__main">
                            <p  class="notification__name">{{ n.data.title }}</p>
                            <p  class="notification__text">{{ n.data.description }}</p>

                            <button v-if="n.data.url"
                                    class="main__btn_main hackathon__btn notification__btn"
                                    >
                                Принять
                            </button>
                        </div>

                        <div class="notification__meta">
                            <p class="notification__date">{{ n.created_at }}</p>

                            <label class="notification__chk">
                                <input type="checkbox"
                                       :value="n.id"
                                       v-model="selected" />
                                <span></span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="notification__item">
                    <div class="notification__image">
                        <img src="/test.jpg" alt="">
                    </div>
                    <div class="notification__content">
                        <div class="notification__main">
                            <p class="notification__name">Старт хакатона</p>
                            <p class="notification__text">Не пропустите начало хакатона «Код будущего». Уже через неделю стартует событие.</p>
                            <button
                                type="button"
                                class="main__btn_main hackathon__btn notification__btn"
                                style="max-width: unset"
                                @click="showAcceptInvitationToJoin = true"
                            >
                                Подтвердить
                            </button>
                        </div>
                        <p class="profile__tabs_awards_item_date">Отправлено 08.05.2025</p>
                    </div>
                </div>
                <div class="notification__item">
                    <div class="notification__image">
                        <img src="/test.jpg" alt="">
                    </div>
                    <div class="notification__content">
                        <div class="notification__main">
                            <p class="notification__name">Старт хакатона</p>
                            <p class="notification__text">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ad adipisci, aut commodi consectetur eos et fugiat impedit, labore libero nostrum quam quasi quo quod, quos sed tempore vel veniam voluptates! Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ad adipisci, aut commodi consectetur eos et fugiat impedit, labore libero nostrum quam quasi quo quod, quos sed tempore vel veniam voluptates! Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ad adipisci, aut commodi consectetur eos et fugiat impedit, labore libero nostrum quam quasi quo quod, quos sed tempore vel veniam voluptates!
                            </p>
                            <button
                                type="button"
                                class="main__btn_main hackathon__btn notification__btn"
                                style="max-width: unset"
                                @click="showAcceptInvitationToJoin = true"
                            >
                                Подтвердить
                            </button>
                        </div>
                        <p class="profile__tabs_awards_item_date">Отправлено 08.05.2025</p>
                    </div>
                </div>
            </div>
        </div>
        <AcceptInvitationToJoin
            v-model="showAcceptInvitationToJoin"
        />
    </AuthenticatedLayout>
</template>

<style scoped>

</style>
