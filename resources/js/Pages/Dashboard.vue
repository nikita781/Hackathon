<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, usePage} from '@inertiajs/vue3';
import {computed, nextTick, onMounted, ref} from "vue";
import {useLangStore} from "@/store/lang.js";
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
    user: Object,
    awards: Object,
    projects: Object,
    auth : { type:Object, required:true },
    notifications : { type:Object, required:true },
})

const langStore = useLangStore()

const activeTab = ref(usePage().props.query?.tab   === 'past' ? 1 : 0)

function setActiveTab(idx) {
    if (activeTab.value === idx) return
    activeTab.value = idx
}

const tabBodies = [
    'awards',
    'certificates',
]

const currentTabBody = computed(() => tabBodies[activeTab.value])

const tabsRef = ref([]);
const sliderStyle = computed(() => {
    if (!tabsRef.value.length) return {};

    const activeTabElement = tabsRef.value[activeTab.value];
    const left = activeTabElement?.offsetLeft || 0;
    const width = activeTabElement?.offsetWidth || 0;

    return {
        left: `${left}px`,
        width: `${width}px`,
    };
});

onMounted(async () => {
    await langStore.fetchTranslations()
    await nextTick(() => {
        tabsRef.value = document.querySelectorAll('.my-hackathon__tabs_item');
    });
    const phone = document.getElementById('phone');

    phone.addEventListener('input', e => {
        let digits = e.target.value.replace(/\D/g, '');

        if (digits[0] === '8') digits = '7' + digits.slice(1);
        if (digits[0] !== '7') digits = '7' + digits;

        const parts = [
            digits.slice(0, 1),
            digits.slice(1, 4),
            digits.slice(4, 7),
            digits.slice(7, 9),
            digits.slice(9, 11)
        ];

        let formatted = '+';
        if (parts[0]) formatted += parts[0];
        if (parts[1]) formatted += ' (' + parts[1];
        if (parts[1].length === 3) formatted += ') ';
        if (parts[2]) formatted += parts[2];
        if (parts[3]) formatted += '-' + parts[3];
        if (parts[4]) formatted += '-' + parts[4];

        e.target.value = formatted;
    });
});

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
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

function previewSrc(project) {
    const hackSlug = project?.hackathon?.slug
    if (hackSlug && typeof route === "function") {
        try {
            // GET hackathons/{hackathon}/media  → name: hackathons.image
            return route("hackathons.image", { hackathon: hackSlug })
        } catch (_) { /* no-op */ }
    }
    return "/project.jpg"
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout
        :auth="props.auth"
        :notifications="props.notifications"
    >
<!--        <pre>{{props.user}}</pre>-->
        <div class="profile">
            <div class="profile__header">
                <h2 class="profile__nickname">{{props.user?.nickname}}</h2>
                <a
                    type="button"
                    class="main__btn_main hackathon__btn"
                    style="max-width: unset"
                    href="https://foncode.ru/cabinet/profile"
                    target="_blank"
                >
                    {{ capitalizeFirstLetter(langStore.translations.editProfile) }}
                </a>
            </div>
            <div class="profile__role">
<!--                <pre>{{props.user}}</pre>-->
                <p v-for="role in props.user?.roles">{{role?.title}}</p>
                <p>ID{{props.user?.id}}</p>
            </div>
            <div class="profile__content">
                <div class="profile__content_form">
                    <div class="profile__content_row">
                        <div class="dialog__component">
                            <p class="dialog__title" style="text-transform: uppercase">{{ capitalizeFirstLetter(langStore.translations.fullName) }}</p>
                            <input type="text" readonly :value="props.user.name" class="dialog__input">
                        </div>
                        <div class="dialog__component">
                            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.birthDate) }}</p>
                            <input type="date" readonly :value="props.user.date_of_birth || new Date().toISOString().slice(0, 10)" class="dialog__input">
                        </div>
                    </div>
                    <div class="profile__content_row">
                        <div class="dialog__component">
                            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.email) }}</p>
                            <input type="email" readonly :value="props.user.email" class="dialog__input">
                        </div>
                        <div class="dialog__component">
                            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.phoneNumber) }}</p>
                            <input :value="props.user.phone_number" readonly class="dialog__input" id="phone" type="tel" placeholder="+7 (___) ___‑__‑__" maxlength="18" autocomplete="tel">
                        </div>
                    </div>
                </div>
                <div class="profile__content_image">
                    <div>
                        <img :src="avatarSrc(props.auth.user.photo)" @error="imgFallback" alt="Profile" />
                    </div>
                </div>
            </div>
        </div>
        <div class="my-hackathon__tabs_cont">
            <div class="my-hackathon__tabs">
                <p :class="['my-hackathon__tabs_item',{active:activeTab===0}]" @click="setActiveTab(0)">
                    {{ capitalizeFirstLetter(langStore.translations.myAwards) }}
                </p>
                <p :class="['my-hackathon__tabs_item',{active:activeTab===1}]" @click="setActiveTab(1)">
                    {{ capitalizeFirstLetter(langStore.translations.certificates) }}
                </p>
                <div
                    v-if="tabsRef.length"
                    class="slider"
                    :style="sliderStyle"
                ></div>
            </div>
            <div class="profile__tabs">
<!--                <pre>{{props.awards}}</pre>-->
                <div v-if="currentTabBody === 'awards'" class="profile__tabs_awards">
                    <div v-for="(award, index) in props.awards" :key="index" class="profile__tabs_awards_item">
                        <img :src="award.image || '/default-award.jpg'" alt="Prize">
                        <div class="profile__tabs_awards_item_content">
                            <div class="profile__tabs_awards_item_header">
                                <p class="profile__tabs_awards_item_title">{{ award.title }}</p>
                                <p class="profile__tabs_awards_item_text">{{ award.description }}</p>
                            </div>
                            <p class="profile__tabs_awards_item_date">
                                {{ capitalizeFirstLetter(langStore.translations.received) }} {{ new Date(award.awarded_at).toLocaleDateString("ru-RU") }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-else>
                    <div class="hackathon__gallery_container">
                        <!--                    <pre>{{props.projects}}</pre>-->
                        <div
                            v-for="project in props.projects.data"
                            :key="project.slug || project.id"
                            class="hackathon__my-project__item"
                            style="cursor: pointer"
                        >
                            <div class="hackathon__my-project__item_header">
                                <img :src="previewSrc(project)" alt="">
                                <div
                                    v-if="Number(project?.place) > 0"
                                    class="hackathon__gallery_place"
                                    :class="Number(project.place) < 4 ? 'first' : 'second'"
                                >
                                    {{ project.place }}
                                </div>
                            </div>

                            <div class="hackathon__my-project__item_content">
                                <div>
                                    <p class="hackathon__my-project__item_title">{{ project.title }}</p>
                                    <p class="hackathon__my-project__item_text">{{ project.description }}</p>
                                </div>

                                <ul class="hackathon__my-project__item_avatar" v-if="project?.team?.users">
                                    <li v-for="user in project.team.users"><img :src="avatarSrc(user.photo)" @error="imgFallback" alt="Avatar"></li>
                                </ul>

                                <a :href="project.certificate_url" class="main__btn_main" style="width: fit-content; margin-top: -10px">{{ capitalizeFirstLetter(langStore.translations.certificate) }}</a>
                            </div>
                        </div>
                    </div>
                    <Pagination
                        style="margin-top: 20px;"
                        v-if="props.projects?.meta?.links?.length"
                        :links="props.projects.meta.links"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style lang="scss">

</style>
