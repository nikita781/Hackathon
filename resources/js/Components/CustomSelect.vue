<script setup>
import {computed, onMounted, onBeforeUnmount, ref, nextTick} from 'vue'

const props = defineProps({
    modelValue: {
        type: [String, Number, null],
        default: null,
    },
    options: {
        type: Array,
        required: true,
    },
    placeholder: {
        type: String,
        default: '',
    },
    minWidth: {
        type: [String, Number],
        default: null,
    },
    maxWidth: {
        type: [String, Number],
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    closeByScroll: {
        type: Boolean,
        default: false,
    },
    fullWidth: {
        type: Boolean,
        default: false,
    },
    red: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['update:modelValue', 'change'])

const isOpen    = ref(false)
const rootRef   = ref(null)
const triggerRef = ref(null)
const menuRef   = ref(null)
const menuStyle = ref({})

const rootStyle = computed(() => {
    const style = {}

    if (props.fullWidth) {
        style.width = '100%'
    }

    const minW = props.minWidth ?? 182
    style.minWidth = typeof minW === 'number' ? `${minW}px` : minW

    if (props.maxWidth != null) {
        style.maxWidth = typeof props.maxWidth === 'number'
            ? `${props.maxWidth}px`
            : props.maxWidth
    }

    return style
})

const selectedOption = computed(() =>
    props.options.find(o => String(o.value) === String(props.modelValue)) || null
)

const labelToShow = computed(() =>
    selectedOption.value?.label || props.placeholder || ''
)

function updateMenuPosition() {
    const trigger = triggerRef.value
    if (!trigger) return

    const rect = trigger.getBoundingClientRect()
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0
    const margin = 8
    const idealMaxHeight = 260

    const spaceBelow = viewportHeight - rect.bottom - margin
    const spaceAbove = rect.top - margin

    let openUp = false
    let maxHeight

    if (spaceBelow < 120 && spaceAbove > spaceBelow) {
        maxHeight = Math.min(idealMaxHeight, spaceBelow)
    } else {
        maxHeight = Math.min(idealMaxHeight, spaceBelow)
    }

    if (!maxHeight || maxHeight < 80) {
        maxHeight = 80
    }

    let top
    if (openUp) {
        top = rect.top + window.scrollY - maxHeight
    } else {
        top = rect.bottom + window.scrollY
    }

    menuStyle.value = {
        position: 'absolute',
        top:  `${top}px`,
        left: `${rect.left + window.scrollX}px`,
        minWidth: `${rect.width}px`,
        maxHeight: `${maxHeight}px`,
        overflowY: 'auto',
        zIndex: 9999,
    }
}

async function openMenu() {
    if (props.disabled) return
    isOpen.value = true
    await nextTick()
    updateMenuPosition()
    requestAnimationFrame(() => {
        if (!isOpen.value) return
        updateMenuPosition()
    })
}

function onScrollOrResize(e) {
    if (!isOpen.value) return

    if (props.closeByScroll) {
        const menu = menuRef.value

        if (menu && e.target && menu.contains(e.target)) {
            return
        }
        closeMenu()
    } else {
        updateMenuPosition()
    }
}

function closeMenu() {
    isOpen.value = false
}

function toggle() {
    if (props.disabled) return
    isOpen.value ? closeMenu() : openMenu()
}

function selectOption(option) {
    if (props.disabled) return

    if (option.value !== props.modelValue) {
        emit('update:modelValue', option.value)
        emit('change', option.value)
    }
    closeMenu()
}

function onClickOutside(e) {
    const root = rootRef.value
    const menu = menuRef.value
    if (!root && !menu) return

    if ((root && root.contains(e.target)) || (menu && menu.contains(e.target))) {
        return
    }

    closeMenu()
}

onMounted(() => {
    document.addEventListener('click', onClickOutside, true)

    window.addEventListener('scroll', onScrollOrResize, true)
    window.addEventListener('resize', onScrollOrResize)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', onClickOutside, true)
    window.removeEventListener('scroll', onScrollOrResize, true)
    window.removeEventListener('resize', onScrollOrResize)
})
</script>

<template>
    <div
        class="custom-select"
        ref="rootRef"
        :style="rootStyle"
    >
        <button
            type="button"
            class="custom-select__trigger"
            :class="{
                'is-disabled': disabled,
                'red': props.red,
            }"
            @click="toggle"
            ref="triggerRef"
        >
            <span>{{ labelToShow }}</span>
            <span
                class="custom-select__arrow"
                :class="{
                    'is-open': isOpen,
                    'red': props.red,
                }"
            >
            </span>
        </button>
    </div>

    <teleport to="body">
        <ul
            v-if="isOpen"
            ref="menuRef"
            class="custom-select__menu"
            :style="menuStyle"
        >
            <li
                v-for="opt in options"
                :key="opt.value"
                class="custom-select__item"
                :class="{
                  'is-active': String(opt.value) === String(modelValue),
                  'is-disabled': opt.disabled
                }"
                @click="!opt.disabled && selectOption(opt)"
                style="margin-left: unset"
            >
                {{ opt.label }}
            </li>
        </ul>
    </teleport>
</template>

<style scoped lang="scss">
$x-small: 575.98px;
$small: 767.98px;
$medium: 991.98px;
$large: 1199.98px;
$x-large: 1399.98px;
$big: 1592.98px;
$x-big: 1829.98px;

.custom-select {
    position: relative;
}

.custom-select__trigger {
    cursor: pointer;
    width: 100%;
    box-sizing: border-box;
    padding: 8px 16px;
    border-radius: 100px;
    border: 2px solid #121212;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 18px;
    outline: none;
    transition: border-color .15s, color .15s, background-color .15s;
    text-wrap: nowrap;
    &.red {
        border: 2px solid #E80024;
        color: #E80024;
    }
    @media screen and (max-width: $small){
        font-size: 14px;
    }
}

.custom-select__trigger:hover {
    border-color: #E80024;
    color: #E80024;
}

.custom-select__trigger.is-disabled {
    opacity: .6;
    cursor: default;
}

.custom-select__arrow {
    width: 9px;
    height: 6px;
    background-image: url("data:image/svg+xml,%3Csvg width='7' height='4' viewBox='0 0 7 4' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0.5 0.5L3.5 3.5L6.5 0.5' stroke='%23121212' stroke-width='1'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: center;
    background-size: 9px 6px;
    transition: transform .15s;
    &.red {
        background-image: url("data:image/svg+xml,%3Csvg width='7' height='4' viewBox='0 0 7 4' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0.5 0.5L3.5 3.5L6.5 0.5' stroke='%23E80024' stroke-width='1'/%3E%3C/svg%3E");
    }
}

.custom-select__arrow.is-open {
    transform: rotate(180deg);
}

.custom-select__menu {
    margin: 0;
    padding: 8px 0;
    list-style: none;
    background: #fff;
    border-radius: 24px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, .12);
    max-height: 260px;
    overflow-y: auto;
}

.custom-select__item {
    padding: 6px 16px;
    cursor: pointer;
    white-space: nowrap;
    @media screen and (max-width: $small){
        font-size: 14px;
    }
}

.custom-select__item:hover {
    color: #E80024;
}

.custom-select__item.is-active {
    color: gray;
    font-weight: 500;
}

.custom-select__item.is-disabled {
    opacity: .5;
    cursor: default;
    pointer-events: none;
}
</style>
