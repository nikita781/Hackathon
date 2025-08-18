import edjsHTML from 'editorjs-html'

const escapeHtml = (s = '') =>
    String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')

function renderList(items = [], style = 'unordered') {
    const tag = style === 'ordered' ? 'ol' : 'ul'

    const renderItem = (item) => {
        // Старый формат: просто строка
        if (typeof item === 'string') {
            return `<li>${item}</li>`
        }
        // Новый/вложенный формат: объект { content, items }
        if (item && typeof item === 'object') {
            const inner = item.items?.length ? renderList(item.items, style) : ''
            return `<li>${item.content ?? ''}${inner}</li>`
        }
        // На всякий случай
        return `<li>${String(item ?? '')}</li>`
    }

    return `<${tag}>${items.map(renderItem).join('')}</${tag}>`
}

const parser = edjsHTML({
    header    : ({ data }) => `<h${data.level}>${data.text}</h${data.level}>`,
    paragraph : ({ data }) => `<p>${data.text ?? ''}</p>`,
    list      : ({ data }) => renderList(data.items, data.style),
    delimiter : () => '<hr />',
    quote     : ({ data }) => `<blockquote><p>${data.text ?? ''}</p>${data.caption ? `<cite>${data.caption}</cite>` : ''}</blockquote>`,
    table     : ({ data }) =>
        `<table>${(data.content || [])
            .map(row => `<tr>${row.map(cell => `<td>${cell}</td>`).join('')}</tr>`)
            .join('')}</table>`,
    image     : ({ data }) => {
        const url = data.file?.url || data.url
        const caption = data.caption || ''
        return url ? `<figure><img src="${url}" alt="${caption}">${caption ? `<figcaption>${caption}</figcaption>` : ''}</figure>` : ''
    },
    code      : ({ data }) => `<pre><code>${escapeHtml(data.code ?? '')}</code></pre>`,
    raw       : ({ data }) => data?.html ?? ''
})

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

    // Валидный Editor.js
    if (data?.blocks?.length) {
        const htmlContent = parser.parse(data)
        return htmlContent || '' // Возвращаем строку
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
