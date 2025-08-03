<script setup>
import IconsPencilMyProject from "@/Components/Icons/PencilMyProject.vue";
import IconsCheck from "@/Components/Icons/Check.vue";
import {computed, nextTick, onMounted, ref} from "vue";

const props = defineProps({
    hackathonSlug: { type: String, required: true },
    project: { type: Object, required: true },
})

const previewUrl = ref(null);

const agree = ref(false);
const pending = ref(false)
const disabled = computed(() => !agree.value || pending.value)
function toggleAgree () { agree.value = !agree.value }

onMounted(async () => {
    try{
        const { data: blob } = await axios.get(
            route('hackathons.projects.image', { hackathon: props.hackathonSlug, project: props.project.slug }),
            { responseType: 'blob' }
        )
        console.log(blob)
        previewUrl.value = URL.createObjectURL(blob)
    }catch(e){
        console.error('hackathon-load',e?.response??e)
    }
})
</script>

<template>
    <div class="project__form">
        <div class="dialog__component">
            <p class="dialog__title">Превью проекта</p>
            <div class="project__form_preview">
                <div class="hackathon__my-project__item">
                    <div class="hackathon__my-project__item_header">
                        <img src="/project.jpg" alt="">
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
            <button class="main__btn main__btn_white dialog__btn">
                Отменить
            </button>
        </div>
    </div>
</template>

<style scoped>

</style>
