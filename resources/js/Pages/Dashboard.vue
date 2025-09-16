<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, usePage} from '@inertiajs/vue3';
import {computed, nextTick, onMounted, ref} from "vue";

const props = defineProps({
    user: Object,
    awards: Object,
    projects: Object
})

console.log(props.projects)

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

function previewSrc(project) {
    const slug = project?.slug ?? project?.id;
    const hackSlug =
        props.hackathon?.slug

    if (slug && hackSlug && typeof route === "function") {
        try {
            return route("hackathons.projects.image", {
                hackathon: hackSlug,
                project: slug,
            });
        } catch (_) { /* no-op */ }
    }
    return "/project.jpg";
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
<!--        <pre>{{props.user}}</pre>-->
        <div class="profile">
            <div class="profile__header">
                <h2 class="profile__nickname">{{props.user?.nickname}}</h2>
                <button
                    type="button"
                    class="main__btn_main hackathon__btn"
                    style="max-width: unset"
                >
                    Редактировать
                </button>
            </div>
            <div class="profile__role">
                <p>{{props.user?.roles[0]?.title}}</p>
                <p>ID{{props.user?.id}}</p>
            </div>
            <div class="profile__content">
                <div class="profile__content_form">
                    <div class="profile__content_row">
                        <div class="dialog__component">
                            <p class="dialog__title">ФИО</p>
                            <input type="text" readonly :value="props.user.name" class="dialog__input" placeholder="Введите ФИО">
                        </div>
                        <div class="dialog__component">
                            <p class="dialog__title">Дата рождения</p>
                            <input type="date" readonly :value="props.user.date_of_birth || new Date().toISOString().slice(0, 10)" class="dialog__input" placeholder="Введите дату рождения">
                        </div>
                    </div>
                    <div class="profile__content_row">
                        <div class="dialog__component">
                            <p class="dialog__title">Email</p>
                            <input type="email" readonly :value="props.user.email" class="dialog__input" placeholder="Введите email">
                        </div>
                        <div class="dialog__component">
                            <p class="dialog__title">Номер телефона</p>
                            <input :value="props.user.phone_number" readonly class="dialog__input" id="phone" type="tel" placeholder="+7 (___) ___‑__‑__" maxlength="18" autocomplete="tel">
                        </div>
                    </div>
                </div>
                <div class="profile__content_image">
                    <div>
                        <img src="/profile.jpg" alt="Profile" />
                    </div>
                </div>
            </div>
        </div>
        <div class="my-hackathon__tabs_cont">
            <div class="my-hackathon__tabs">
                <p :class="['my-hackathon__tabs_item',{active:activeTab===0}]" @click="setActiveTab(0)">
                    Мои награды
                </p>
                <p :class="['my-hackathon__tabs_item',{active:activeTab===1}]" @click="setActiveTab(1)">
                    Сертификаты
                </p>
                <div
                    v-if="tabsRef.length"
                    class="slider"
                    :style="sliderStyle"
                ></div>
            </div>
            <div class="profile__tabs">
                <div v-if="currentTabBody === 'awards'" class="profile__tabs_awards">
                    <div v-for="(award, index) in props.awards" :key="index" class="profile__tabs_awards_item">
                        <img :src="award.image || '/default-award.jpg'" alt="Prize">
                        <div class="profile__tabs_awards_item_content">
                            <div class="profile__tabs_awards_item_header">
                                <p class="profile__tabs_awards_item_title">{{ award.title }}</p>
                                <p class="profile__tabs_awards_item_text">{{ award.description }}</p>
                            </div>
                            <p class="profile__tabs_awards_item_date">
                                Получено {{ new Date(award.awarded_at).toLocaleDateString("ru-RU") }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-else class="hackathon__gallery_container">
<!--                    <pre>{{props.projects}}</pre>-->
                    <div
                        v-for="project in props.projects.data"
                        class="hackathon__my-project__item"
                        style="cursor: pointer"
                    >
                        <div class="hackathon__my-project__item_header">
                            <img :src="previewSrc(project)" alt="">
                        </div>

                        <div class="hackathon__my-project__item_content">
                            <div>
                                <p class="hackathon__my-project__item_title">{{ project.title }}</p>
                                <p class="hackathon__my-project__item_text">{{ project.description }}</p>
                            </div>

                            <ul class="hackathon__my-project__item_avatar">
                                <li><img src="/profile.jpg" alt="Avatar"></li>
                                <li><img src="/profile.jpg" alt="Avatar"></li>
                                <li><img src="/profile.jpg" alt="Avatar"></li>
                            </ul>

                            <a :href="project.certificate_url" class="main__btn_main" style="width: fit-content; margin-top: -10px">Сертификат</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style lang="scss">

</style>
