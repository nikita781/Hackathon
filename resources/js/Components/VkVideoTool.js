// VkVideoTool.js
export default class VkVideoTool {
    /** Кнопка в тулбаре */
    static get toolbox() {
        return {
            title: 'VK Video',
            icon: `<svg viewBox="0 0 24 24" width="18" height="18">
               <path fill="#4C75A3" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Z
                m5.64 14.67h-1.85c-1.16 0-1.64-.71-2.42-1.64-.67-.81-1.09-1.11-1.44-1.11
                -.16 0-.35.09-.55.29-.22.23-.23.45-.23.65v1.81H9.5V9.74h1.65v2.22
                c.23-.22.48-.46.74-.7.57-.52 1.02-.78 1.61-.78.46 0 .84.18 1.18.47.46.41.76
                1 .99 1.64.18.48.39.86.63 1.1.14.14.28.2.42.2s.28-.07.42-.2c.15-.13.19-.3.19
                -.44V9.74h1.64v3.81c0 .88-.2 1.63-.58 2.19-.42.61-1.01.93-1.72.93Z"/>
             </svg>`
        };
    }

    constructor({ data }) {
        this.data = { code: data?.code || '' };
    }

    /** конвертируем ссылку video-oid_id → embed-URL на vkvideo.ru */
    buildEmbedUrl(url) {
        // • если это уже embed-ссылка – оставляем как есть
        if (url.includes('video_ext.php')) return url;

        // • выдёргиваем oid и id независимо от домена
        const m = url.match(/video[-\/]?([\-0-9]+)_([0-9]+)/);
        if (!m) return null;

        const [ , oid, id ] = m;

        // • формируем iframe-URL на vkvideo.ru
        return `https://vkvideo.ru/video_ext.php?oid=-${oid}&id=${id}&hd=1`;
    }

    render() {
        this.wrapper = document.createElement('div');

        // поле ввода ссылки
        this.input = document.createElement('input');
        this.input.type = 'text';
        this.input.placeholder = 'Прямая ссылка на VK-видео';
        this.input.style.cssText = 'width: calc(100% - 20px) ;padding:6px 8px;margin-bottom:12px;';
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

    /** что сохранится в JSON Editor.js */
    save() {
        return { code: this.data.code };
    }

    /** рисуем/обновляем iframe-превью */
    drawPreview() {
        this.preview.innerHTML = '';

        if (!this.data.code) return;

        const embed = this.buildEmbedUrl(this.data.code);
        if (!embed) {
            this.preview.innerHTML =
                '<small style="color:#c00">Неверный формат ссылки</small>';
            return;
        }

        const iframe = document.createElement('iframe');
        iframe.src = embed;
        iframe.width = '100%';
        iframe.height = '360';
        iframe.allow =
            'autoplay; encrypted-media; fullscreen; picture-in-picture; screen-wake-lock;';
        iframe.frameBorder = '0';
        iframe.allowFullscreen = true;
        iframe.style.maxWidth = '100%';

        this.preview.appendChild(iframe);
    }
}
