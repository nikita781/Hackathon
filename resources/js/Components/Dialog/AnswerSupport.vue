<script setup>
import {ref, computed, watch, onMounted} from "vue";
import {useToast} from "vue-toastification";
import {useLangStore} from "@/store/lang.js";

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    message: { type: Array, required: true },
    can: { type: Boolean, default: true },
    admin: { type: Boolean, default: false },
});
const emit = defineEmits(["update:modelValue", "sent"]);

const textAppeal = ref('');
const pending = ref(false);
const errorMsg = ref('');
const backendErrors = ref({});

const toast = useToast();
const isAdmin = ref(true);

const isDisabled = computed(() => pending.value || !textAppeal.value.trim());

watch(() => props.modelValue, () => {
    isAdmin.value = true;
    if (props.admin && props.message.messages[1]) {
        console.log(props.message.messages[1])
        isAdmin.value = false;
    }
});

function reset() {
    textAppeal.value = '';
    pending.value = false;
    errorMsg.value = '';
    backendErrors.value = {};
}
function close() {
    reset();
    emit("update:modelValue", false);
}

async function submitAppeal() {
    errorMsg.value = '';
    backendErrors.value = {};

    try {
        pending.value = true;
        await axios.post(
            route('support.answer', { support: props.message.id }),
            {
                message: textAppeal.value.trim(),
            }
        );
        toast.success(capitalizeFirstLetter(langStore.translations.reply_sent), { position:'top-right', timeout:5000 })
        emit('sent');
        window.location.reload()
        close();
    } catch (e) {
        const resp = e?.response;
        if (resp?.status === 422) {
            backendErrors.value = resp.data?.errors || {};
            errorMsg.value = backendErrors.value?.message?.[0] ?? '';
        }
        toast.error(capitalizeFirstLetter(langStore.translations.reply_failed), { position:'top-right', timeout:5000 })
        console.error('support-store', resp ?? e);
    } finally {
        pending.value = false;
    }
}

const langStore = useLangStore()

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

onMounted(async () => {
    await langStore.fetchTranslations()
});
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:2">
        <div class="dialog__container dialog__container_small" @click.stop>
            <div class="dialog__header">
                <p v-if="message.messages[1] || !props.can">{{ capitalizeFirstLetter(langStore.translations.reply) }}</p>
                <p v-else>{{ capitalizeFirstLetter(langStore.translations.write_reply) }}</p>
                <div class="dialog__close" @click="close"><svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M7.90994 6.00019L12.2099 1.71019C12.3982 1.52188 12.504 1.26649 12.504 1.00019C12.504 0.733884 12.3982 0.478489 12.2099 0.290185C12.0216 0.101882 11.7662 -0.00390625 11.4999 -0.00390625C11.2336 -0.00390625 10.9782 0.101882 10.7899 0.290185L6.49994 4.59019L2.20994 0.290185C2.02164 0.101882 1.76624 -0.00390625 1.49994 -0.00390625C1.23364 -0.00390625 0.978243 0.101882 0.789939 0.290185C0.601635 0.478489 0.495847 0.733884 0.495847 1.00019C0.495847 1.26649 0.601635 1.52188 0.789939 1.71019L5.08994 6.00019L0.789939 10.2902C0.696211 10.3831 0.621816 10.4937 0.571048 10.6156C0.520279 10.7375 0.494141 10.8682 0.494141 11.0002C0.494141 11.1322 0.520279 11.2629 0.571048 11.3848C0.621816 11.5066 0.696211 11.6172 0.789939 11.7102C0.882902 11.8039 0.993503 11.8783 1.11536 11.9291C1.23722 11.9798 1.36793 12.006 1.49994 12.006C1.63195 12.006 1.76266 11.9798 1.88452 11.9291C2.00638 11.8783 2.11698 11.8039 2.20994 11.7102L6.49994 7.41019L10.7899 11.7102C10.8829 11.8039 10.9935 11.8783 11.1154 11.9291C11.2372 11.9798 11.3679 12.006 11.4999 12.006C11.632 12.006 11.7627 11.9798 11.8845 11.9291C12.0064 11.8783 12.117 11.8039 12.2099 11.7102C12.3037 11.6172 12.3781 11.5066 12.4288 11.3848C12.4796 11.2629 12.5057 11.1322 12.5057 11.0002C12.5057 10.8682 12.4796 10.7375 12.4288 10.6156C12.3781 10.4937 12.3037 10.3831 12.2099 10.2902L7.90994 6.00019Z"
                        fill="#999999"
                    />
                </svg></div>
            </div>
<!--            <pre>{{message}}</pre>-->
            <div class="dialog__component">
                <p class="message-type" v-if="message.type === 'bug'">{{ capitalizeFirstLetter(langStore.translations.error_message) }}</p>
                <p class="message-type" v-if="message.type === 'suggestion'">{{ capitalizeFirstLetter(langStore.translations.suggestion) }}</p>
                <p class="message-type" v-if="message.type === 'question'">{{ capitalizeFirstLetter(langStore.translations.question) }}</p>
            </div>
            <div class="dialog__component">
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.requestText) }}</p>
                <textarea
                    style="min-height: 170px"
                    class="dialog__textarea"
                    :value="message.messages[0].message"
                    disabled
                />
            </div>
            <div class="dialog__component" v-if="message.messages[1] || !props.can">
                <p class="dialog__title" v-if="message.messages[1]">{{ capitalizeFirstLetter(langStore.translations.reply_text) }}</p>
                <textarea
                    v-if="message.messages[1]"
                    style="min-height: 170px"
                    class="dialog__textarea"
                    :value="message.messages[1].message"
                    disabled
                />
            </div>
            <div class="dialog__component" v-else>
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.reply_text) }}</p>
                <textarea
                    v-model="textAppeal"
                    style="min-height: 170px"
                    class="dialog__textarea"
                    :class="{ error: backendErrors.message }"
                    :placeholder="capitalizeFirstLetter(langStore.translations.enter_reply_text)"
                />
                <p v-if="errorMsg" style="color:#e74c3c; margin-top:8px;">{{ errorMsg }}</p>
            </div>
<!--            {{props.can}}-->
            <div class="dialog__btns" v-if="props.can && isAdmin">
                <button class="main__btn main__btn_white dialog__btn" @click="close" :disabled="pending">
                    {{ capitalizeFirstLetter(langStore.translations.cansel) }}
                </button>
                <button
                    class="main__btn dialog__btn"
                    @click="submitAppeal"
                    :disabled="isDisabled"
                >
                    {{ pending ? `${capitalizeFirstLetter(langStore.translations.submission)}…` : capitalizeFirstLetter(langStore.translations.send) }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.message-type {
    width: fit-content;
    border: 1px solid #E80024;
    border-radius: 100px;
    padding: 8px 16px;
    font-size: 18px;
    color: #F80024;
}
</style>
