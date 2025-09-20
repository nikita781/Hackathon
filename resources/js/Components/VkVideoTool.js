// VkVideoTool.js
export default class VkVideoTool {
    static get toolbox() {
        return {
            title: 'VK Video',
            icon: `<svg viewBox="0 0 24 24" width="18" height="18"><path fill="#4C75A3" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Z m5.64 14.67h-1.85c-1.16 0-1.64-.71-2.42-1.64-.67-.81-1.09-1.11-1.44-1.11-.16 0-.35.09-.55.29-.22.23-.23.45-.23.65v1.81H9.5V9.74h1.65v2.22c.23-.22.48-.46.74-.7.57-.52 1.02-.78 1.61-.78.46 0 .84.18 1.18.47.46.41.76 1 .99 1.64.18.48.39.86.63 1.1.14.14.28.2.42.2s.28-.07.42-.2c.15-.13.19-.3.19-.44V9.74h1.64v3.81c0 .88-.2 1.63-.58 2.19-.42.61-1.01.93-1.72.93Z"/></svg>`
        };
    }

    constructor({ data }) {
        this.data = { code: data?.code || '' };
    }

    // Преобразуем разные форматы ссылок VK в iframe-embed
    buildEmbedUrl(url = '') {
        if (!url) return null;

        // уже embed-ссылки — пропускаем
        if (url.includes('video_ext.php')) return url;

        // поддержка форматов:
        // https://vk.com/video-123_456
        // https://vk.com/video/club123?z=video-123_456
        // https://m.vk.com/video-123_456
        // ... и т.п.
        const m =
            url.match(/video[-\/]?([-\d]+)_([\d]+)/) ||
            url.match(/z=video([-\d]+)_([\d]+)/);

        if (!m) return null;

        let [, oid, id] = m;
        // нормализуем oid: нужен всегда со знаком «-»
        const n = parseInt(String(oid), 10);
        const normOid = (isNaN(n) ? oid : (n < 0 ? n : -n));

        return `https://vkvideo.ru/video_ext.php?oid=${normOid}&id=${id}&hd=1`;
    }

    render() {
        this.wrapper = document.createElement('div');

        this.input = document.createElement('input');
        this.input.type = 'text';
        this.input.placeholder = 'Прямая ссылка на VK-видео';
        this.input.style.cssText = 'width: calc(100% - 20px); padding:6px 8px; margin-bottom:12px;';
        this.input.value = this.data.code;

        this.input.addEventListener('input', () => {
            this.data.code = this.input.value.trim();
            this.drawPreview();
        });

        this.preview = document.createElement('div');
        this.drawPreview();

        this.wrapper.append(this.input, this.preview);
        return this.wrapper;
    }

    save() {
        return { code: this.data.code };
    }

    drawPreview() {
        this.preview.innerHTML = '';
        if (!this.data.code) return;

        const embed = this.buildEmbedUrl(this.data.code);
        if (!embed) {
            this.preview.innerHTML = '<small style="color:#c00">Неверный формат ссылки</small>';
            return;
        }

        const iframe = document.createElement('iframe');
        iframe.src = embed;
        iframe.width = '100%';
        iframe.height = '360';
        iframe.allow = 'autoplay; encrypted-media; fullscreen; picture-in-picture; screen-wake-lock;';
        iframe.frameBorder = '0';
        iframe.allowFullscreen = true;
        iframe.style.maxWidth = '100%';

        this.preview.appendChild(iframe);
    }
}
