<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    links: {
        type: Array,
        required: true,
    },
})

const isPrev = (l) => l.label.includes('&laquo;') || l.label === '‹'
const isNext = (l) => l.label.includes('&raquo;') || l.label === '›'

const W = 4

const paginated = computed(() => {
    const prev = props.links.find(isPrev)
    const next = props.links.find(isNext)
    const pages = props.links.filter((l) => !isPrev(l) && !isNext(l))

    const curIdx = pages.findIndex((p) => p.active)

    const keep = new Set([0, pages.length - 1])
    for (let i = curIdx - W; i <= curIdx + W; i++) {
        if (i >= 0 && i < pages.length) keep.add(i)
    }

    const out = []
    pages.forEach((p, i) => {
        if (keep.has(i)) {
            out.push(p)
        } else if (out[out.length - 1] !== 'gap') {
            out.push('gap')
        }
    })

    const final = out.map((x) =>
        x === 'gap'
            ? { url: null, label: '…', active: false }
            : x
    )

    return { prev, pages: final, next }
})

const fixLabel = (label) => {
    if (label === 'pagination.previous') return '‹'
    if (label === 'pagination.next') return '›'
    return label
}
</script>

<template>
    <nav class="main__pagination">
        <Link
            v-if="paginated.prev"
            :href="paginated.prev.url ?? ''"
            class="main__pagination_item arrow"
            :class="{ disabled: !paginated.prev.url }"
            preserve-scroll
            replace
        >
            <svg width="20" height="20" viewBox="0 0 20 20">
                <path
                    d="M15 18l-6-6 6-6"
                    fill="none"
                    stroke="#E80024"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </Link>

        <Link
            v-for="page in paginated.pages"
            :key="page.label + page.url"
            :href="page.url ?? ''"
            :class="['main__pagination_item', { active: page.active, disabled: !page.url }]"
            preserve-scroll
            replace
        >
            <span v-html="fixLabel(page.label)" />
        </Link>

        <Link
            v-if="paginated.next"
            :href="paginated.next.url ?? ''"
            class="main__pagination_item arrow"
            :class="{ disabled: !paginated.next.url }"
            preserve-scroll
            replace
        >
            <svg width="20" height="20" viewBox="0 0 20 20">
                <path
                    d="M9 6l6 6-6 6"
                    fill="none"
                    stroke="#E80024"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </Link>
    </nav>
</template>

<style scoped>
.disabled {
    path {
        stroke: black;
    }
    pointer-events: none;
}
</style>
