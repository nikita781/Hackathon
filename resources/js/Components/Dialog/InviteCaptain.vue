<script setup>
import IconsCancel from "@/Components/Icons/Cancel.vue";
import { computed, onMounted, ref, watch } from "vue";
import axios from "axios";
import { useLangStore } from "@/store/lang.js";
import { useToast } from "vue-toastification";
import CustomSelect from "@/Components/CustomSelect.vue";

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    hackathon: { type: Object, required: true },
});

const emit = defineEmits(["update:modelValue"]);
const close = () => emit("update:modelValue", false);

const langStore = useLangStore();
const toast = useToast();

const pending = ref(false);

const PLACEHOLDER = "/profile.jpg";
function avatarSrc(photo) {
    if (!photo) return PLACEHOLDER;
    const url = String(photo).trim();
    const hasFileName = /[^/]+\.[a-z0-9]+(?:\?.*)?$/i.test(url);
    return hasFileName ? url : PLACEHOLDER;
}
function imgFallback(e) {
    e.target.onerror = null;
    e.target.src = PLACEHOLDER;
}

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

function makeLookup() {
    return {
        loading: false,
        touched: false,
        found: false,
        canInvite: false,
        user: null,
        errors: [],
    };
}

function makeRow() {
    return {
        q: "",
        rowError: "",
        lookup: makeLookup(),
    };
}

// строки приглашений
const users = ref([makeRow()]);

// дебаунс таймеры по индексам
const timers = new Map();

function clearTimer(i) {
    const t = timers.get(i);
    if (t) clearTimeout(t);
    timers.delete(i);
}

function resetAll() {
    timers.forEach((t) => clearTimeout(t));
    timers.clear();
    users.value = [makeRow()];
    pending.value = false;
}

async function doLookup(i, q) {
    try {
        const { data } = await axios.get(
            route("hackathons.staff.search", { hackathon: props.hackathon.slug }),
            { params: { q } }
        );

        const row = users.value[i];
        if (!row) return;

        row.lookup.loading = false;
        row.lookup.touched = true;
        row.lookup.found = !!data?.user;
        row.lookup.user = data?.user ?? null;
        row.lookup.canInvite = !!data?.canInvite;
        row.lookup.errors = data?.errors ?? [];
    } catch (e) {
        const row = users.value[i];
        if (!row) return;

        row.lookup.loading = false;
        row.lookup.touched = true;
        row.lookup.found = false;
        row.lookup.canInvite = false;
        row.lookup.user = null;
        row.lookup.errors = ["Не удалось проверить пользователя"];
        // eslint-disable-next-line no-console
        console.error("hackathons.staff.search", e?.response ?? e);
    }
}

function onUserInput(i) {
    const row = users.value[i];
    if (!row) return;

    row.rowError = "";
    clearTimer(i);

    const q = row.q.toString().trim();
    if (!q) {
        row.lookup = makeLookup();
        return;
    }

    row.lookup.loading = true;
    row.lookup.touched = true;

    timers.set(
        i,
        setTimeout(() => doLookup(i, q), 350)
    );
}

function addUserField() {
    users.value.push(makeRow());
}

function removeUserField(i) {
    clearTimer(i);
    users.value.splice(i, 1);
    if (!users.value.length) users.value = [makeRow()];
}

function resolveUserId(row) {
    // если нашли пользователя — берём точный id
    if (row.lookup?.found && row.lookup?.user?.id != null) return Number(row.lookup.user.id);

    // иначе отправим как ввёл (число или строка/ник) — бэк сам обработает/вернёт 422
    const raw = row.q.toString().trim();
    if (!raw) return null;
    return /^\d+$/.test(raw) ? Number(raw) : raw;
}

const filledRows = computed(() => users.value.filter(r => r.q.trim().length));

const allFilledRowsAllowed = computed(() => {
    if (!filledRows.value.length) return false;
    return filledRows.value.every(r => r.lookup.found && r.lookup.canInvite);
});

const inviteDisabled = computed(() => pending.value || !filledRows.value.length || !allFilledRowsAllowed.value);

async function inviteCaptain() {
    if (!filledRows.value.length) return;

    // очистим ошибки
    users.value.forEach(r => (r.rowError = ""));

    const payload = {
        users: filledRows.value.map((r) => ({ user_id: resolveUserId(r) })),
    };

    try {
        pending.value = true;

        await axios.post(
            route("hackathons.invite-capitan", { hackathon: props.hackathon.slug }),
            payload
        );

        toast.success("Приглашение(я) капитана отправлены", { position: "top-right", timeout: 5000 });
        resetAll();
        close();
    } catch (error) {
        if (error?.response?.status === 422) {
            const errs = error.response.data?.errors ?? {};
            // пример ключа: users.0.user_id
            Object.entries(errs).forEach(([key, messages]) => {
                const m = key.match(/^users\.(\d+)\.user_id$/);
                if (!m) return;
                const idx = Number(m[1]);
                if (!Number.isFinite(idx) || !users.value[idx]) return;
                users.value[idx].rowError = Array.isArray(messages) ? (messages[0] ?? "Ошибка") : String(messages);
            });

            // если прилетела общая ошибка — покажем toast
            if (!Object.keys(errs).length) {
                toast.error("Ошибка валидации", { position: "top-right", timeout: 5000 });
            }
        } else {
            // eslint-disable-next-line no-console
            console.error("hackathons.invite-capitan", error?.response ?? error);
            toast.error(error?.response?.data?.message || "Не удалось отправить приглашение");
        }
    } finally {
        pending.value = false;
    }
}

watch(
    () => props.modelValue,
    async (v) => {
        if (!v) {
            resetAll();
            return;
        }
        await langStore.fetchTranslations();
    }
);

onMounted(async () => {
    await langStore.fetchTranslations();
});
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:2">
        <div class="dialog__container dialog__container_small" @click.stop>
            <div class="dialog__header">
                <p>Пригласить капитана</p>
                <div class="dialog__close" @click="close"><svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M7.90994 6.00019L12.2099 1.71019C12.3982 1.52188 12.504 1.26649 12.504 1.00019C12.504 0.733884 12.3982 0.478489 12.2099 0.290185C12.0216 0.101882 11.7662 -0.00390625 11.4999 -0.00390625C11.2336 -0.00390625 10.9782 0.101882 10.7899 0.290185L6.49994 4.59019L2.20994 0.290185C2.02164 0.101882 1.76624 -0.00390625 1.49994 -0.00390625C1.23364 -0.00390625 0.978243 0.101882 0.789939 0.290185C0.601635 0.478489 0.495847 0.733884 0.495847 1.00019C0.495847 1.26649 0.601635 1.52188 0.789939 1.71019L5.08994 6.00019L0.789939 10.2902C0.696211 10.3831 0.621816 10.4937 0.571048 10.6156C0.520279 10.7375 0.494141 10.8682 0.494141 11.0002C0.494141 11.1322 0.520279 11.2629 0.571048 11.3848C0.621816 11.5066 0.696211 11.6172 0.789939 11.7102C0.882902 11.8039 0.993503 11.8783 1.11536 11.9291C1.23722 11.9798 1.36793 12.006 1.49994 12.006C1.63195 12.006 1.76266 11.9798 1.88452 11.9291C2.00638 11.8783 2.11698 11.8039 2.20994 11.7102L6.49994 7.41019L10.7899 11.7102C10.8829 11.8039 10.9935 11.8783 11.1154 11.9291C11.2372 11.9798 11.3679 12.006 11.4999 12.006C11.632 12.006 11.7627 11.9798 11.8845 11.9291C12.0064 11.8783 12.117 11.8039 12.2099 11.7102C12.3037 11.6172 12.3781 11.5066 12.4288 11.3848C12.4796 11.2629 12.5057 11.1322 12.5057 11.0002C12.5057 10.8682 12.4796 10.7375 12.4288 10.6156C12.3781 10.4937 12.3037 10.3831 12.2099 10.2902L7.90994 6.00019Z"
                        fill="#999999"
                    />
                </svg></div>
            </div>

            <p class="dialog__text" style="margin-top:-18px">
                Можно пригласить сразу несколько капитанов — добавьте строки и выберите пользователей через поиск.
            </p>

            <div
                v-for="(row, i) in users"
                :key="i"
                class="dialog__component"
                style="margin-top: 10px"
            >
                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px">
                    <p class="dialog__title">Пользователь {{ i + 1 }}</p>
                </div>

                <div class="dialog__input_btns dialog__input_btns_small dialog__input_btns-phone">
                    <input
                        v-model="row.q"
                        class="dialog__input"
                        placeholder="ID или ник"
                        style="width: 100%"
                        :class="{ error: row.rowError }"
                        @input="onUserInput(i)"
                    />
                    <div class="dialog__input_reset">
                        <div>
                            <IconsCancel v-if="users.length > 1" class="clickable" style="cursor: pointer" @click="removeUserField(i)"/>
                        </div>
                    </div>
                </div>

                <small v-if="row.rowError" class="error__text">{{ row.rowError }}</small>

                <div v-if="row.lookup.touched" class="dialog__hint" style="margin-top:6px">
                    <template v-if="row.lookup.loading">
                        {{ capitalizeFirstLetter(langStore.translations?.searching || 'поиск') }}...
                    </template>

                    <template v-else-if="row.lookup.found">
                        <div class="found-user">
                            <img
                                :src="avatarSrc(row.lookup.user?.photo)"
                                @error="imgFallback"
                                alt="Avatar"
                                class="found-user__avatar"
                            />
                            <div class="found-user__meta">
                                <div>
                                    <b>@{{ row.lookup.user.nickname }}</b>
                                    (ID {{ row.lookup.user.id }})
                                    <span v-if="row.lookup.canInvite" class="found-user__ok"> — можно пригласить</span>
                                    <span v-else class="error__text"> — нельзя пригласить</span>
                                </div>

                                <ul
                                    v-if="!row.lookup.canInvite && row.lookup.errors?.length"
                                    class="error__text"
                                    style="margin:4px 0 0 0;padding-left:16px"
                                >
                                    <li v-for="(e, idx) in row.lookup.errors" :key="idx">{{ e }}</li>
                                </ul>
                            </div>
                        </div>
                    </template>

                    <template v-else>
                        <p class="error__text">
                            {{ capitalizeFirstLetter(langStore.translations?.user_not_found || 'пользователь не найден') }}
                        </p>
                    </template>
                </div>
            </div>

            <div class="dialog__plus" style="margin-top: -10px" @click="addUserField">
                <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M13.1665 7.33317H9.1665V3.33317C9.1665 3.15636 9.09627 2.98679 8.97124 2.86177C8.84622 2.73674 8.67665 2.6665 8.49984 2.6665C8.32303 2.6665 8.15346 2.73674 8.02843 2.86177C7.90341 2.98679 7.83317 3.15636 7.83317 3.33317V7.33317H3.83317C3.65636 7.33317 3.48679 7.40341 3.36177 7.52843C3.23674 7.65346 3.1665 7.82303 3.1665 7.99984C3.1665 8.17665 3.23674 8.34622 3.36177 8.47124C3.48679 8.59627 3.65636 8.6665 3.83317 8.6665H7.83317V12.6665C7.83317 12.8433 7.90341 13.0129 8.02843 13.1379C8.15346 13.2629 8.32303 13.3332 8.49984 13.3332C8.67665 13.3332 8.84622 13.2629 8.97124 13.1379C9.09627 13.0129 9.1665 12.8433 9.1665 12.6665V8.6665H13.1665C13.3433 8.6665 13.5129 8.59627 13.6379 8.47124C13.7629 8.34622 13.8332 8.17665 13.8332 7.99984C13.8332 7.82303 13.7629 7.65346 13.6379 7.52843C13.5129 7.40341 13.3433 7.33317 13.1665 7.33317Z"
                        fill="#E80024"
                    />
                </svg>
                <p>{{ capitalizeFirstLetter(langStore.translations?.addMore || 'добавить ещё') }}</p>
            </div>

            <div class="dialog__btns">
                <button class="main__btn main__btn_white dialog__btn" @click="close">
                    {{ capitalizeFirstLetter(langStore.translations?.cansel || 'отмена') }}
                </button>

                <button
                    class="main__btn dialog__btn"
                    :class="{ blocked: inviteDisabled }"
                    :disabled="inviteDisabled"
                    @click="inviteCaptain"
                >
                    {{ pending ? '...' : 'Пригласить' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dialog__hint {
    font-size: 12px;
}

.found-user {
    display: flex;
    align-items: center;
    gap: 8px;
}

.found-user__avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    object-fit: cover;
    flex: 0 0 28px;
}

.found-user__ok {
    color: #1c7430;
}
</style>
