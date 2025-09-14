<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue'
import PencilMenu from '@/Components/Icons/PencilMenu.vue'
import Eye from '@/Components/Icons/Eye.vue'
import NoPreview from '@/Components/Icons/NoPreview.vue'

const props = defineProps({
    show: { type: Boolean, default: false },
    x:    { type: Number,  default: 0 },
    y:    { type: Number,  default: 0 },
    user: { type: Object,  default: null },
})
const emit = defineEmits(['close','block','role'])

const menuRef = ref(null)

function onDocClick(e){
    if (!props.show) return
    const el = menuRef.value
    if (el && !el.contains(e.target)) emit('close')
}
function onEsc(e){ if (e.key === 'Escape') emit('close') }
function onScroll(){ emit('close') }

onMounted(()=>{
    document.addEventListener('mousedown', onDocClick)
    document.addEventListener('keydown', onEsc)
    window.addEventListener('scroll', onScroll, { passive:true })
})
onBeforeUnmount(()=>{
    document.removeEventListener('mousedown', onDocClick)
    document.removeEventListener('keydown', onEsc)
    window.removeEventListener('scroll', onScroll)
})
</script>

<template>
    <teleport to="body">
        <div
            v-if="show"
            class="ctx"
            :style="{ top: `${y}px`, left: `${x}px` }"
            ref="menuRef"
        >
            <button class="ctx__item" @click="$emit('block', user)">
                <NoPreview class="ctx__icon" v-if="user.status === 1"/>
                <Eye class="ctx__icon" v-else/>
                <span>{{ user.status === 1 ? 'Заблокировать' : 'Разблокировать' }}</span>
            </button>
            <button class="ctx__item" @click="$emit('role', user)">
                <PencilMenu class="ctx__icon" />
                <span>Изменить роль</span>
            </button>
        </div>
    </teleport>
</template>

<style lang="scss" scoped>
.ctx{
    position: fixed;
    z-index: 9999;
    min-width: 220px;
    border-radius: 6px;
    background: #101011;
    color: #fff;
    box-shadow: 0 16px 40px rgba(0,0,0,.35);
}
.ctx__item{
    width: 100%;
    display: flex;
    gap: 10px;
    align-items: center;
    padding: 20px 24px;
    border: 0;
    background: transparent;
    color: inherit;
    border-radius: 8px;
    cursor: pointer;
    font-size: 18px;
    svg {
        width: 24px;
        height: 24px;
    }
}
.ctx__item:hover{ background: rgba(255,255,255,.06); }
.ctx__icon{ width: 18px; height: 18px; }
</style>
