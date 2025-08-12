<script setup>
import IconsCheck from "@/Components/Icons/Check.vue";
import {computed, nextTick, onMounted, ref, watch} from "vue";

const props = defineProps({
    hackathonSlug: { type: String, required: true },
    project: { type: Object, required: true },
})

const emit = defineEmits(['cancel'])

const previewUrl = ref(null);

const agree = ref(false);
const pending = ref(false)
const disabled = computed(() => !agree.value || pending.value)
function toggleAgree () { agree.value = !agree.value }

function cancel () {
    agree.value = false
    pending.value = false
    emit('cancel')
}

async function getPreview() {
    try{
        const { data: blob } = await axios.get(
            route('hackathons.projects.image', { hackathon: props.hackathonSlug, project: props.project.slug }),
            { responseType: 'blob' }
        )
        previewUrl.value = URL.createObjectURL(blob)
    }catch(e){
        console.error('hackathon-load',e?.response??e)
    }
}
onMounted(() => {
    nextTick(() => {
        previewUrl.value = null;
        getPreview();
    });
});

watch(() => props.project, () => {
    previewUrl.value = null;
    getPreview();
});
</script>

<template>
    <div class="project__form">
        <div class="dialog__component">
            <p class="dialog__title">Превью проекта</p>
            <div class="project__form_preview">
                <div class="hackathon__my-project__item">
                    <div class="hackathon__my-project__item_header">
                        <div class="skeleton-loader" v-if="!previewUrl"></div>
                        <img v-if="previewUrl" :src="previewUrl" alt="Превью проекта" />
                    </div>
                    <div class="hackathon__my-project__item_content">
                        <div>
                            <p class="hackathon__my-project__item_title">{{ props.project.title }}</p>
                            <p class="hackathon__my-project__item_text">{{ props.project.description }}</p>
                        </div>
                        <ul class="hackathon__my-project__item_avatar">
                            <li><img src="/profile.jpg" alt="Avatar"></li>
                            <li><img src="/profile.jpg" alt="Avatar"></li>
                            <li><img src="/profile.jpg" alt="Avatar"></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="dialog__component">
            <p class="dialog__title">Правила и условия</p>
            <div class="dialog__checkbox" style="margin-top: 10px">
                <div>
                    <div @click="toggleAgree" class="custom-checkbox" :class="agree ? 'active' : ''">
                        <IconsCheck />
                    </div>
                </div>
                <p>Я и все члены моей команды ознакомились с Официальными правилами и Условиями предоставления услуг и согласны с ними</p>
            </div>
        </div>
        <div class="project__form_btns">
            <button class="main__btn dialog__btn"
                    :class="{ blocked: disabled }"
                    :disabled="disabled"
            >
                Отправить
            </button>
            <button class="main__btn main__btn_white dialog__btn" @click="cancel">
                Отменить
            </button>
        </div>
    </div>
</template>

<style scoped>
.skeleton-loader {
    width: 100%;
    height: 200px;
    background-color: #e0e0e0;
    animation: skeleton 1.2s ease-in-out infinite;
    border-radius: 4px;
}

@keyframes skeleton {
    0% {
        background-color: #e0e0e0;
    }
    50% {
        background-color: #f0f0f0;
    }
    100% {
        background-color: #e0e0e0;
    }
}
</style>
