<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, usePage} from '@inertiajs/vue3';
import {computed, nextTick, onMounted, ref} from "vue";

const props = defineProps({
    user: Object,
})

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
                    <div class="profile__tabs_awards_item">
                        <img src="/test.jpg" alt="Prize">
                        <div class="profile__tabs_awards_item_content">
                            <div class="profile__tabs_awards_item_header">
                                <p class="profile__tabs_awards_item_title">Добро пожаловать в хакатон</p>
                                <p class="profile__tabs_awards_item_text">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Assumenda, debitis deserunt, ducimus earum eum, eveniet facilis fuga itaque iusto libero maiores nam nisi numquam officiis placeat rem similique unde voluptates?</p>
                            </div>
                            <p class="profile__tabs_awards_item_date">Получено 12.04.2025</p>
                        </div>
                    </div>
                    <div class="profile__tabs_awards_item">
                        <img src="/test.jpg" alt="Prize">
                        <div class="profile__tabs_awards_item_content">
                            <div class="profile__tabs_awards_item_header">
                                <p class="profile__tabs_awards_item_title">Добро пожаловать в хакатон</p>
                                <p class="profile__tabs_awards_item_text">Получи свою первую награду на сайте </p>
                            </div>
                            <p class="profile__tabs_awards_item_date">Получено 12.04.2025</p>
                        </div>
                    </div>
                    <div class="profile__tabs_awards_item">
                        <img src="/test.jpg" alt="Prize">
                        <div class="profile__tabs_awards_item_content">
                            <div class="profile__tabs_awards_item_header">
                                <p class="profile__tabs_awards_item_title">Добро пожаловать в хакатон</p>
                                <p class="profile__tabs_awards_item_text">Получи свою первую награду на сайте </p>
                            </div>
                            <p class="profile__tabs_awards_item_date">Получено 12.04.2025</p>
                        </div>
                    </div>
                </div>

                <div v-else class="profile__tabs_certificates">

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style lang="scss">

</style>
