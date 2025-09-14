<script setup>
import {computed, onMounted, ref, watch} from "vue";
import IconsCheck from '@/Components/Icons/Check.vue'
import {useLangStore} from "@/store/lang.js";
import IconsPencil from "@/Components/Icons/Pencil.vue";
import IconsCancel from "@/Components/Icons/Cancel.vue";

const props = defineProps({
    modelValue: Boolean,
    user: {type: Object, required: true},
    options: {type: Array, default: () => []},
})
const emit = defineEmits(['update:modelValue', 'saved'])

const langStore = useLangStore()

function close() {
    emit('update:modelValue', false)
}

onMounted(async () => {
    await langStore.fetchTranslations()
})

const roleLabel = (r) => r?.title ?? r?.name ?? `Роль #${r?.id ?? ''}`

const picks = ref([])

watch(() => props.user, (u) => {
    picks.value = (u?.roles ?? []).map(r => r.id)
}, {immediate: true})

function addPick() {
    picks.value.push(null)
}

function removePick(idx) {
    picks.value.splice(idx, 1)
}

const used = computed(() => new Set(picks.value.filter(v => v != null)))

const pending = ref(false)

async function save() {
    if (pending.value) return
    const roles = [...new Set(picks.value.filter(v => v != null))]

    if (!roles.length) {
        alert('У пользователя должна быть хотя бы одна роль')
        return
    }

    try {
        pending.value = true
        const key = props.user?.nickname ?? props.user?.id
        await axios.post(route('admin.users.change-roles', key), {roles})

        emit('update:modelValue', false)
        emit('saved', roles)
    } catch (e) {
        console.error('change-roles failed', e?.response ?? e)
    } finally {
        pending.value = false
    }
}
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:2" @click.self="close">
        <div class="dialog__container dialog__container_x-small" @click.stop>
            <div class="dialog__header">
                <p>Изменить роль</p>
                <div class="dialog__close" @click="close">
                    <svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M7.91 6l4.3-4.29A1 1 0 0011.5-0c-.27 0-.53.11-.71.29L6.5 4.59 2.21.29A1 1 0 10.79 1.71L5.09 6 .79 10.29a1 1 0 101.42 1.42L6.5 7.41l4.29 4.3a1 1 0 001.42-1.42L7.91 6z"
                            fill="#999"/>
                    </svg>
                </div>
            </div>

            <div class="dialog__component" style="margin-top:-10px">
                <div v-for="(v,idx) in picks" :key="idx"
                     style="display:flex; gap:8px; align-items:center; margin-bottom:10px">
                    <select
                        v-model="picks[idx]"
                        class="main__cards_select dialog__select"
                        style="width: 100%"
                    >
                        <option :value="null" disabled>Выберите роль…</option>
                        <option
                            v-for="r in options"
                            :key="r.id"
                            :value="r.id"
                            :disabled="used.has(r.id) && r.id !== v"
                        >
                            {{ roleLabel(r) }}
                        </option>
                    </select>

                    <div class="dialog__prize_btns">
                        <IconsCancel
                            class="clickable"
                            v-if="picks.length > 1"
                            @click="removePick(idx)"
                            title="Удалить"
                        />
                    </div>
                </div>

                <div class="dialog__plus" @click="addPick" style="cursor:pointer">
                    <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M13.1665 7.33317H9.1665V3.33317C9.1665 3.15636 9.09627 2.98679 8.97124 2.86177C8.84622 2.73674 8.67665 2.6665 8.49984 2.6665C8.32303 2.6665 8.15346 2.73674 8.02843 2.86177C7.90341 2.98679 7.83317 3.15636 7.83317 3.33317V7.33317H3.83317C3.65636 7.33317 3.48679 7.40341 3.36177 7.52843C3.23674 7.65346 3.1665 7.82303 3.1665 7.99984C3.1665 8.17665 3.23674 8.34622 3.36177 8.47124C3.48679 8.59627 3.65636 8.6665 3.83317 8.6665H7.83317V12.6665C7.83317 12.8433 7.90341 13.0129 8.02843 13.1379C8.15346 13.2629 8.32303 13.3332 8.49984 13.3332C8.67665 13.3332 8.84622 13.2629 8.97124 13.1379C9.09627 13.0129 9.1665 12.8433 9.1665 12.6665V8.6665H13.1665C13.3433 8.6665 13.5129 8.59627 13.6379 8.47124C13.7629 8.34622 13.8332 8.17665 13.8332 7.99984C13.8332 7.82303 13.7629 7.65346 13.6379 7.52843C13.5129 7.40341 13.3433 7.33317 13.1665 7.33317Z"
                            fill="#E80024"/>
                    </svg>
                    <p>{{
                            (langStore.translations.addMore || 'Добавить ещё').charAt(0).toUpperCase() + (langStore.translations.addMore || 'Добавить ещё').slice(1)
                        }}</p>
                </div>
            </div>

            <div class="dialog__btns">
                <button class="main__btn main__btn_white dialog__btn" @click="close">
                    Отменить
                </button>
                <button class="main__btn dialog__btn" :disabled="pending" @click="save">
                    Сохранить
                </button>
            </div>
        </div>
    </div>
</template>
