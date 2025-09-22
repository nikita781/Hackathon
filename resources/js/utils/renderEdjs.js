import edjsHTML from 'editorjs-html'
import VkVideoTool from '../Components/VkVideoTool.js';

const escapeHtml = (s = '') =>
    String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')

function renderList(items = [], style = 'unordered', start = 1) {
    const tag = style === 'ordered' ? 'ol' : 'ul'
    const startAttr = style === 'ordered' && start ? ` start="${start}"` : '' // Добавляем атрибут start для ordered

    const renderItem = (item) => {
        // Старый формат: просто строка
        if (typeof item === 'string') {
            return `<li>${item}</li>`
        }
        // Новый/вложенный формат: объект { content, items }
        if (item && typeof item === 'object') {
            const inner = item.items?.length ? renderList(item.items, style, start) : ''
            return `<li>${item.content ?? ''}${inner}</li>`
        }
        // На всякий случай
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
    // НОВОЕ:
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

/**
 * @param {string|object|null} raw — поле `content` из БД/бэка
 * @returns {string} HTML
 */
export function renderEdjs (raw) {
    // Если пришла строка — пробуем распарсить JSON
    let data = raw
    if (typeof raw === 'string') {
        try {
            data = JSON.parse(raw)  // Преобразуем строку JSON в объект
        } catch (e) {
            console.error('Invalid JSON:', e)
            return `<p style="color:#999">Некорректные данные</p>`
        }
    }

    // Editor.js: пустой → ничего не выводим
    if (isEditorJsEmpty(data)) {
        return '' // вообще ничего
    }

    // Editor.js: непустой → рендерим
    if (isEditorJsData(data) && data.blocks.length > 0) {
        const htmlParts = parser.parse(data)            // массив строк
        const html = Array.isArray(htmlParts) ? htmlParts.join('') : String(htmlParts ?? '')
        return html
    }

    // Спец-случай: объект с датами
    if (data?.start && data?.end) {
        const fmt = d => new Date(d).toLocaleString('ru-RU', {
            day:'2-digit', month:'2-digit', year:'numeric',
            hour:'2-digit', minute:'2-digit'
        })
        return `<p>${fmt(data.start)} — ${fmt(data.end)}</p>`
    }

    // Пусто
    if (!raw) return '<p style="color:#999">Нет описания</p>'

    // Фолбэк: показать как JSON (экранируем)
    return `<pre>${escapeHtml(JSON.stringify(data ?? raw, null, 2))}</pre>`
}
