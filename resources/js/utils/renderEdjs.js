// utils/renderEdjs.js
import edjsHTML from 'editorjs-html'

// кастомные рендеры для Editor-JS-блоков
const parser = edjsHTML({
    header : ({ data }) => `<h${data.level}>${data.text}</h${data.level}>`,
    list   : ({ data }) =>
        data.style === 'ordered'
            ? `<ol>${data.items.map(i => `<li>${i}</li>`).join('')}</ol>`
            : `<ul>${data.items.map(i => `<li>${i}</li>`).join('')}</ul>`
})

/**
 * @param {string|object|null} raw — то самое поле `content`
 * @returns {string}              — HTML-строка
 */
export function renderEdjs (raw) {
    /** попытка распарсить строку-JSON */
    const data = typeof raw === 'string'
        ? (() => { try { return JSON.parse(raw) } catch { return null } })()
        : raw

    /* === валидный Editor-JS === */
    if (data?.blocks?.length) {
        return parser.parse(data).join('')
    }

    /* === «спец»-объект с диапазоном дат === */
    if (data?.start && data?.end) {
        const fmt = d => new Date(d).toLocaleString('ru-RU', {
            day:'2-digit', month:'2-digit', year:'numeric',
            hour:'2-digit', minute:'2-digit'
        })
        return `<p>${fmt(data.start)} — ${fmt(data.end)}</p>`
    }

    /* === что-то другое / пусто === */
    if (!raw) return '<p style="color:#999">Нет описания</p>'
    return `<pre>${JSON.stringify(data ?? raw, null, 2)}</pre>`
}
