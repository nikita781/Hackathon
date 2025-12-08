<script setup>
import {useLangStore} from "@/store/lang.js";
import {onMounted} from "vue";

const props = defineProps({
    modelValue : Boolean,
})
const emit = defineEmits(['update:modelValue'])

const langStore = useLangStore()

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

onMounted(async () => {
    await langStore.fetchTranslations()
});

const close = () => emit('update:modelValue', false)
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:5">
        <div class="dialog__container dialog__container_small" style="gap: 10px" @click.stop>
            <h2 style="text-align: center; margin-bottom: 20px; color: #E80024;">{{ capitalizeFirstLetter(langStore.translations.certificate_template_help) }}</h2>

            <div style="margin-bottom: 20px;">
                <h3 style="color: #101011; margin-bottom: 10px;">📁 {{ capitalizeFirstLetter(langStore.translations.formats_and_limits) }}</h3>
                <ul style="list-style: none; padding-left: 0;">
                    <li style="margin-bottom: 8px;">✅ <strong>{{ capitalizeFirstLetter(langStore.translations.format_label) }}</strong>
                        {{ capitalizeFirstLetter(langStore.translations.format_html_only) }}</li>
                    <li style="margin-bottom: 8px;">✅ <strong>{{ capitalizeFirstLetter(langStore.translations.max_size) }}</strong>
                        {{ capitalizeFirstLetter(langStore.translations.max_size_2mb) }}</li>
                    <li style="margin-bottom: 8px;">✅ <strong>{{ capitalizeFirstLetter(langStore.translations.page_size) }}</strong>
                        {{ capitalizeFirstLetter(langStore.translations.page_size_range) }}</li>
                    <li style="margin-bottom: 8px;">✅ <strong>{{ capitalizeFirstLetter(langStore.translations.default_page_size) }}</strong>
                        {{ capitalizeFirstLetter(langStore.translations.default_page_size_a4) }}</li>
                </ul>
            </div>

            <div style="margin-bottom: 20px;">
                <h3 style="color: #101011; margin-bottom: 10px;">🔤 {{ capitalizeFirstLetter(langStore.translations.required_placeholders) }}</h3>
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
                <h3 style="color: #101011; margin-bottom: 10px;">✅ {{ capitalizeFirstLetter(langStore.translations.allowed_technologies) }}</h3>
                <ul style="list-style-type: disc; padding-left: 20px;">
                    <li style="margin-bottom: 5px;">{{ capitalizeFirstLetter(langStore.translations.html5_tags) }}</li>
                    <li style="margin-bottom: 5px;">{{ capitalizeFirstLetter(langStore.translations.css_inline_styles) }}</li>
                    <li style="margin-bottom: 5px;">{{ capitalizeFirstLetter(langStore.translations.system_fonts) }} DejaVu Sans, Arial, Helvetica, Times New Roman</li>
                    <li style="margin-bottom: 5px;">{{ capitalizeFirstLetter(langStore.translations.images_base64_allowed) }}</li>
                </ul>
            </div>

            <div style="margin-bottom: 20px;">
                <h3 style="color: #E80024; margin-bottom: 10px;">❌ {{ capitalizeFirstLetter(langStore.translations.forbidden_elements) }}</h3>
                <ul style="list-style-type: disc; padding-left: 20px;">
                    <li style="margin-bottom: 5px;">{{ capitalizeFirstLetter(langStore.translations.forbidden_js) }}</li>
                    <li style="margin-bottom: 5px;">{{ capitalizeFirstLetter(langStore.translations.forbidden_iframes) }}</li>
                    <li style="margin-bottom: 5px;">{{ capitalizeFirstLetter(langStore.translations.forbidden_forms) }}</li>
                    <li style="margin-bottom: 5px;">{{ capitalizeFirstLetter(langStore.translations.forbidden_external) }}</li>
                </ul>
            </div>

            <div style="margin-bottom: 20px;">
                <h3 style="color: #101011; margin-bottom: 10px;">📄 {{ capitalizeFirstLetter(langStore.translations.pdf_features) }}</h3>
                <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">
                    <strong>{{ capitalizeFirstLetter(langStore.translations.recommended_units) }}</strong><br>
                    ✅ {{ capitalizeFirstLetter(langStore.translations.recommended_units_stable) }}<br>
                    ❌ {{ capitalizeFirstLetter(langStore.translations.recommended_units_unstable) }}
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <h3 style="color: #101011; margin-bottom: 10px;">🎨 {{ capitalizeFirstLetter(langStore.translations.css_properties_supported) }}</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 12px;">
                    <div style="background: #d4edda; padding: 10px; border-radius: 5px;">
                        <strong>{{ capitalizeFirstLetter(langStore.translations.css_good_support) }}</strong><br>
                        font-*<br>
                        color, background-color<br>
                        margin, padding<br>
                        border, border-radius<br>
                        text-align
                    </div>
                    <div style="background: #fff3cd; padding: 10px; border-radius: 5px;">
                        <strong>{{ capitalizeFirstLetter(langStore.translations.css_limited_support) }}</strong><br>
                        background-image<br>
                        flexbox<br>
                        grid<br>
                        transform
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <h3 style="color: #101011; margin-bottom: 10px;">💡 {{ capitalizeFirstLetter(langStore.translations.template_example) }}</h3>
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
            &lcub;&lcub; userNickname &rcub;&rcub; - &lcub;&lcub; place &rcub;&rcub; {{ capitalizeFirstLetter(langStore.translations.placeholder_location) }}
        &lt;/div&gt;
    &lt;/div&gt;
&lt;/body&gt;
&lt;/html&gt;</pre>
            </div>

            <div style="background: #d1ecf1; padding: 15px; border-radius: 8px; border-left: 4px solid #0dcaf0;">
                <strong>💡 {{ capitalizeFirstLetter(langStore.translations.tip) }}</strong> {{
                    capitalizeFirstLetter(langStore.translations.tip_check_preview)
                }}
            </div>

            <div class="dialog__btns" style="justify-content:center; margin-top: 20px;">
                <button class="main__btn main__btn_white dialog__btn" @click="close">
                    {{ capitalizeFirstLetter(langStore.translations.ok) }}
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
