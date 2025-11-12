<script setup>
const props = defineProps({
    modelValue : Boolean,
})
const emit = defineEmits(['update:modelValue'])

const close = () => emit('update:modelValue', false)
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:5">
        <div class="dialog__container dialog__container_small" style="gap: 10px" @click.stop>
            <h2 style="text-align: center; margin-bottom: 20px; color: #E80024;">Справка по шаблонам сертификатов</h2>

            <div style="margin-bottom: 20px;">
                <h3 style="color: #101011; margin-bottom: 10px;">📁 Форматы и ограничения</h3>
                <ul style="list-style: none; padding-left: 0;">
                    <li style="margin-bottom: 8px;">✅ <strong>Формат:</strong> только HTML файлы (.html)</li>
                    <li style="margin-bottom: 8px;">✅ <strong>Максимальный размер:</strong> 2 МБ</li>
                    <li style="margin-bottom: 8px;">✅ <strong>Размеры страницы:</strong> 50×50 мм до 1000×1000 мм</li>
                    <li style="margin-bottom: 8px;">✅ <strong>Размеры страницы по умолчанию:</strong> A4 (297×210 мм) в альбомной ориентации</li>
                </ul>
            </div>

            <div style="margin-bottom: 20px;">
                <h3 style="color: #101011; margin-bottom: 10px;">🔤 Обязательные плейсхолдеры</h3>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 14px;">
                    &lcub;&lcub; hackathonTitle &rcub;&rcub;<br>
                    &lcub;&lcub; userName &rcub;&rcub;<br>
                    &lcub;&lcub; userNickname &rcub;&rcub;<br>
                    &lcub;&lcub; place &rcub;&rcub;<br>
                    &lcub;&lcub; organizatorNickname &rcub;&rcub;<br>
                    &lcub;&lcub; startTime &rcub;&rcub;<br>
                    &lcub;&lcub; endTime &rcub;&rcub;<br>
                    &lcub;&lcub; seal &rcub;&rcub;
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <h3 style="color: #101011; margin-bottom: 10px;">✅ Разрешенные технологии</h3>
                <ul style="list-style-type: disc; padding-left: 20px;">
                    <li style="margin-bottom: 5px;">HTML5 - все стандартные теги</li>
                    <li style="margin-bottom: 5px;">CSS inline-стили (атрибут style)</li>
                    <li style="margin-bottom: 5px;">Системные шрифты: DejaVu Sans, Arial, Helvetica, Times New Roman</li>
                    <li style="margin-bottom: 5px;">Изображения в base64 или абсолютные URL</li>
                </ul>
            </div>

            <div style="margin-bottom: 20px;">
                <h3 style="color: #E80024; margin-bottom: 10px;">❌ Запрещенные элементы (будут удалены)</h3>
                <ul style="list-style-type: disc; padding-left: 20px;">
                    <li style="margin-bottom: 5px;">JavaScript (&lt;script&gt;, onclick, onload и т.д.)</li>
                    <li style="margin-bottom: 5px;">Iframe и embed-объекты</li>
                    <li style="margin-bottom: 5px;">Формы и элементы ввода</li>
                    <li style="margin-bottom: 5px;">Внешние скрипты и стили</li>
                </ul>
            </div>

            <div style="margin-bottom: 20px;">
                <h3 style="color: #101011; margin-bottom: 10px;">📄 Особенности PDF (DomPDF)</h3>
                <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">
                    <strong>Рекомендуемые единицы измерения:</strong><br>
                    ✅ mm, pt, px - работают стабильно<br>
                    ❌ %, em, rem - могут работать непредсказуемо
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <h3 style="color: #101011; margin-bottom: 10px;">🎨 Поддерживаемые CSS свойства</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 12px;">
                    <div style="background: #d4edda; padding: 10px; border-radius: 5px;">
                        <strong>Хорошая поддержка:</strong><br>
                        font-*<br>
                        color, background-color<br>
                        margin, padding<br>
                        border, border-radius<br>
                        text-align
                    </div>
                    <div style="background: #fff3cd; padding: 10px; border-radius: 5px;">
                        <strong>Ограниченная поддержка:</strong><br>
                        background-image<br>
                        flexbox<br>
                        grid<br>
                        transform
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <h3 style="color: #101011; margin-bottom: 10px;">💡 Пример правильного шаблона</h3>
                <pre style="background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 8px; font-size: 12px; overflow: auto;">
&lt;!DOCTYPE html&gt;
&lt;html&gt;
&lt;head&gt;
    &lt;meta charset="UTF-8"&gt;
&lt;/head&gt;
&lt;body style="font-family: DejaVu Sans, Arial, sans-serif;"&gt;
    &lt;div style="width: 280mm; height: 190mm; border: 2px solid #000;"&gt;
        &lt;h1 style="text-align: center;"&gt;
            &lcub;&lcub; hackathonTitle &rcub;&rcub;
        &lt;/h1&gt;
        &lt;div style="text-align: center;"&gt;
            &lcub;&lcub; userNickname &rcub;&rcub; - &lcub;&lcub; place &rcub;&rcub; место
        &lt;/div&gt;
    &lt;/div&gt;
&lt;/body&gt;
&lt;/html&gt;</pre>
            </div>

            <div style="background: #d1ecf1; padding: 15px; border-radius: 8px; border-left: 4px solid #0dcaf0;">
                <strong>💡 Совет:</strong> Всегда проверяйте превью сертификата после загрузки шаблона!
            </div>

            <div class="dialog__btns" style="justify-content:center; margin-top: 20px;">
                <button class="main__btn main__btn_white dialog__btn" @click="close">
                    Понятно
                </button>
            </div>
        </div>
    </div>
</template>

<style lang="scss">
    li {
        margin-left: 20px;
    }
</style>
