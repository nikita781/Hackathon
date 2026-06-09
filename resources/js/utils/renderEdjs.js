import edjsHTML from 'editorjs-html'
import VkVideoTool from '../Components/VkVideoTool.js';

const escapeHtml = (s = '') =>
    String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')

function renderList(items = [], style = 'unordered', start = 1) {
    const tag = style === 'ordered' ? 'ol' : 'ul'
    const startAttr = style === 'ordered' && start ? ` start="${start}"` : ''

    const renderItem = (item) => {
        if (typeof item === 'string') {
            return `<li>${item}</li>`
        }
        if (item && typeof item === 'object') {
            const inner = item.items?.length ? renderList(item.items, style, start) : ''
            return `<li>${item.content ?? ''}${inner}</li>`
        }
        return `<li>${String(item ?? '')}</li>`
    }

    return `<${tag}${startAttr}>${items.map(renderItem).join('')}</${tag}>`
}

const parser = edjsHTML({
    header    : ({ data }) => `<h${data.level}>${data.text}</h${data.level}>`,
    paragraph : ({ data }) => `<p>${data.text ?? ''}</p>`,
    list      : ({ data }) => renderList(data.items, data.style, data.meta?.start),
    delimiter : () => '<hr />',
    quote     : ({ data }) => `<blockquote><p>${data.text ?? ''}</p>${data.caption ? `<cite>${data.caption}</cite>` : ''}</blockquote>`,
    table     : ({ data }) => `<table>${(data.content || []).map(row => `<tr>${row.map(cell => `<td>${cell}</td>`).join('')}</tr>`).join('')}</table>`,
    image     : ({ data }) => {
        const url = data.file?.url || data.url;
        const caption = data.caption || '';
        return url ? `<figure><img src="${url}" alt="${caption}">${caption ? `<figcaption>${caption}</figcaption>` : ''}</figure>` : '';
    },
    code      : ({ data }) => `<pre><code>${escapeHtml(data.code ?? '')}</code></pre>`,
    raw       : ({ data }) => data?.html ?? '',
    vkvideo   : ({ data }) => {
        const tool = new VkVideoTool({ data });
        const embed = tool.buildEmbedUrl(data?.code || '');
        return embed
            ? `<div class="vkvideo-wrap"><iframe src="${embed}" width="100%" height="360" frameborder="0" allow="autoplay; encrypted-media; fullscreen; picture-in-picture; screen-wake-lock;" allowfullscreen></iframe></div>`
            : '';
    }
});

const isEditorJsData = (v) =>
    v && typeof v === 'object' && Array.isArray(v.blocks)

const isEditorJsEmpty = (v) =>
    isEditorJsData(v) && v.blocks.length === 0

export function renderEdjs (raw) {
    let data = raw
    if (typeof raw === 'string') {
        try {
            data = JSON.parse(raw)
        } catch (e) {
            console.error('Invalid JSON:', e)
            return `<p style="color:#999">Некорректные данные</p>`
        }
    }

    if (isEditorJsEmpty(data)) {
        return ''
    }

    if (isEditorJsData(data) && data.blocks.length > 0) {
        const htmlParts = parser.parse(data)
        const html = Array.isArray(htmlParts) ? htmlParts.join('') : String(htmlParts ?? '')
        return html
    }

    if (data?.start && data?.end) {
        const fmt = d => new Date(d).toLocaleString('ru-RU', {
            day:'2-digit', month:'2-digit', year:'numeric',
            hour:'2-digit', minute:'2-digit'
        })
        return `<p>${fmt(data.start)} — ${fmt(data.end)}</p>`
    }

    if (!raw) return '<p style="color:#999">Нет описания</p>'

    return `<pre>${escapeHtml(JSON.stringify(data ?? raw, null, 2))}</pre>`
}
