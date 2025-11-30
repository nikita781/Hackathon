<script setup>
import DropFile   from '../../DropFile.vue'
import {ref, toRaw, watch, onMounted, nextTick, onBeforeUnmount, computed} from 'vue'
import { useForm } from '@inertiajs/vue3'
import {useLangStore} from "@/store/lang.js";
import CustomSelect from "@/Components/CustomSelect.vue";

const props = defineProps({
    hackathonSlug : { type:String, required:true },
    draft         : Object,
    allTags       : { type:Array,  default:() => [] },
    isEdit   : { type:Boolean, default:false },
    admin      : { type:Boolean, default:() => false },
})
const emit = defineEmits(['saved','cancel','dirty', 'saving'])

const isAdmin = computed(() => !!props.admin)

const loaded = ref(false)
const dirty = ref(false)
const langStore = useLangStore()

const taskStart = ref('')
const taskEnd = ref('')
const evaluationStart = ref('')
const evaluationEnd = ref('')

const form = useForm({
    title            : '',
    image_path       : null,
    format           : 'online',
    type             : 'team',
    min_team_size    : null,
    max_team_size    : null,
    registration_end : '',
    event_start      : '',
    event_end        : '',
    prize_type       : 'cash',
    prize_pool       : '',
    tags             : [],
})

function pad2(n) { return String(n).padStart(2, '0') }

function normalizeToIsoZ(s) {
    if (!s) return ''
    const t = String(s).trim()
    const reBare = /^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2}(\.\d+)?)?$/
    if (reBare.test(t) && !/[zZ]|[+\-]\d{2}:\d{2}$/.test(t)) {
        return t.replace(' ', 'T') + 'Z'
    }
    return t
}

function utcToLocalInputValue(utcStr) {
    if (!utcStr) return ''
    const d = new Date(normalizeToIsoZ(utcStr))
    if (isNaN(d.getTime())) return ''
    const y  = d.getFullYear()
    const m  = pad2(d.getMonth() + 1)
    const dd = pad2(d.getDate())
    const hh = pad2(d.getHours())
    const mm = pad2(d.getMinutes())
    return `${y}-${m}-${dd}T${hh}:${mm}`
}

const previewUrl = ref(null)

const participationType  = ref('Командный')
const presentType        = ref('Денежный приз')
const selectedDirections = ref([])
const toggleDropdown     = ref(false)
const directions         = props.allTags

const toggleDropdownVisibility = () => toggleDropdown.value = !toggleDropdown.value

function selectDirection(tag){
    const idx = selectedDirections.value.findIndex(t=>t.id===tag.id)
    idx === -1
        ? selectedDirections.value.push(tag)
        : selectedDirections.value.splice(idx,1)
}
function removeDirection(tag,e){
    e.stopPropagation()
    const idx = selectedDirections.value.findIndex(t=>t.id===tag.id)
    if(idx!==-1) selectedDirections.value.splice(idx,1)
}
function clearSelection(){
    selectedDirections.value = []
    toggleDropdown.value = false
}
watch(selectedDirections, arr=>{
    form.tags = arr.map(t=>t.id)
},{immediate:true,deep:true})

function revokePreview () {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = null
}

onMounted(async () => {
    try{
        const { data } = await axios.get(
            route('hackathons.show', { hackathon:props.hackathonSlug }),
        )
        const { data: blob } = await axios.get(
            route('hackathons.image', { hackathon: props.hackathonSlug }),
            { responseType: 'blob' }
        )
        const h = data.hackathon.original
        previewUrl.value = URL.createObjectURL(blob)

        form.title             = h.title
        form.format            = h.format
        form.type              = h.type
        form.min_team_size     = h.min_team_size
        form.max_team_size     = h.max_team_size
        form.registration_end  = utcToLocalInputValue(h.registration_end)
        form.event_start       = utcToLocalInputValue(h.event_start)
        form.event_end         = utcToLocalInputValue(h.event_end)

        taskStart.value       = utcToLocalInputValue(h.work_time_start)
        taskEnd.value         = utcToLocalInputValue(h.work_time_end)
        evaluationStart.value = utcToLocalInputValue(h.evaluation_start)
        evaluationEnd.value   = utcToLocalInputValue(h.evaluation_end)

        form.prize_type        = h.prize_type
        form.prize_pool        = h.prize_pool
        form.image_path        = previewUrl.value

        participationType.value= h.type==='team' ? 'Командный':'Индивидуальный'
        presentType.value      = h.prize_type==='cash' ? 'Денежный приз':'Призы'

        selectedDirections.value = h.tags ?? []
        await nextTick()
        loaded.value = true
    }catch(e){
        console.error('hackathon-load',e?.response??e)
    }
})

function clearFieldError(field) {
    form.clearErrors(field)
}

watch(
    () => form.image_path,
    file => {
        if (!file) { revokePreview(); return }
        form.clearErrors('image_path')
    }
)

watch(
    [form],
    () => {
        if (!loaded.value) return

        if (!dirty.value) {
            dirty.value = true
            emit('dirty', true)
        }
    },
    { deep:true }
)

const DATE_FIELDS = ['registration_end', 'event_start', 'event_end'];

function localDateTimeToUtcISO(localStr) {
    // Ожидаем строку формата "YYYY-MM-DDTHH:mm" (datetime-local)
    if (!localStr) return '';
    // Надёжно парсим как ЛОКАЛЬНОЕ время, без двусмысленностей разных браузеров:
    const [datePart, timePart] = localStr.split('T');
    if (!datePart || !timePart) return '';

    const [y, m, d] = datePart.split('-').map(Number);
    const [hh, mm]  = timePart.split(':').map(Number);

    // Создаём Date в ЛОКАЛЬНОЙ зоне пользователя:
    const local = new Date(y, (m ?? 1) - 1, d ?? 1, hh ?? 0, mm ?? 0, 0);

    // Возвращаем UTC ISO 8601 (с Z). Например, "2025-09-22T10:00" (Берлин, UTC+2)
    // превратится в "2025-09-22T08:00:00.000Z".
    return isNaN(local.getTime()) ? '' : local.toISOString();
}

async function save(){
    form.clearErrors()

    emit('saving', true)

    const fd = new FormData()
    const data = form.data()

    fd.append('work_time_start',  localDateTimeToUtcISO(taskStart.value))
    fd.append('work_time_end',    localDateTimeToUtcISO(taskEnd.value))
    fd.append('evaluation_start', localDateTimeToUtcISO(evaluationStart.value))
    fd.append('evaluation_end',   localDateTimeToUtcISO(evaluationEnd.value))

    toRaw(data.tags).forEach(id => fd.append('tags[]', id))

    Object.entries(data).forEach(([k, v]) => {
        if (k === 'tags' || k === 'image_path') return
        if (k === 'min_team_size' || k === 'max_team_size') {
            if (form.type === 'team') {
                fd.append(k, v);
            } else {
                fd.append(k, 1);
            }
            return;
        }
        if ((DATE_FIELDS).includes(k)) {
            fd.append(k, localDateTimeToUtcISO(v));
            return;
        }
        fd.append(k, v ?? '')
    })
    if (form.image_path instanceof File) {
        fd.append('image_path', form.image_path)
        // console.log(form.image_path)
    }
    fd.append('_method','PATCH')

    // console.log('FD →', [...fd.entries()].map(([k, v]) => [k, v instanceof File ? v.name : v]));

    try{
        await axios.post(
            route('hackathons.update', { hackathon:props.hackathonSlug }),
            fd, { headers:{'Content-Type':'multipart/form-data'} }
        )
        dirty.value = false
        loaded.value = true
        emit('dirty', false)
        emit('saved',{ slug:props.hackathonSlug })
    }catch(e){
        if (e.response?.status === 422) {
            const errors = e.response.data.errors
            Object.entries(errors).forEach(([field, messages]) => {
                form.setError(field, messages.join(' '))
            })

            console.table(errors)
        } else {

            console.error('hackathon-update', e)
        }
    }finally {
        emit('saving', false)
    }
}

function resetState(){
    form.reset()
    participationType.value  = 'Командный'
    presentType.value        = 'Денежный приз'
    selectedDirections.value = []
    toggleDropdown.value     = false
}
function cancel(){
    resetState()
    emit('cancel')
}

defineExpose({ save })
onBeforeUnmount(revokePreview)

function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

onMounted(async () => {
    await langStore.fetchTranslations()
});

const rootRef = ref(null)

function onClickOutside(e) {
    if (!toggleDropdown.value) return
    const el = rootRef.value
    if (el && !el.contains(e.target)) {
        toggleDropdown.value = false
    }
}

onMounted(() => {
    document.addEventListener('mousedown', onClickOutside)
})

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onClickOutside)
})

const sortOptions = computed(() => [
    {
        value: 'online',
        label: `${capitalizeFirstLetter(langStore.translations.online)}`,
    },
    {
        value: 'offline',
        label: `${capitalizeFirstLetter(langStore.translations.offline)}`,
    },
    {
        value: 'hybrid',
        label: `${capitalizeFirstLetter(langStore.translations.hybrid)}`,
    },
])
const sortOptions2 = computed(() => [
    {
        value: 'team',
        label: `${capitalizeFirstLetter(langStore.translations.team_type)}`,
    },
    {
        value: 'individual',
        label: `${capitalizeFirstLetter(langStore.translations.individual_type)}`,
    },
])
const sortOptions3 = computed(() => [
    {
        value: 'cash',
        label: `${capitalizeFirstLetter(langStore.translations.money_prize)}`,
    },
    {
        value: 'non-cash',
        label: `${capitalizeFirstLetter(langStore.translations.item_prize)}`,
    },
])
</script>

<template>
    <div class="dialog__component">
        <div class="dialog__title_container">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.hackathon_title) }} *</p>

            <div class="help-tt" aria-label="help">
                <svg class="help-tt__icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="#000" />
                    <path d="M12 17v-5" stroke="#000" stroke-linecap="round"/>
                    <circle cx="12" cy="8" r="1" fill="#000"/>
                </svg>
                <div class="tooltipSquare"></div>
                <div class="tooltip">
                    <p>Это название мероприятия, отображаемое на карточке и странице хакатона</p>
                </div>
            </div>
        </div>
        <input
            v-model="form.title"
            type="text"
            class="dialog__input"
            :placeholder="capitalizeFirstLetter(langStore.translations.enter_title)"
            :class="{ 'error': form.errors.title }"
            @input="clearFieldError('title')"
        />
        <small v-if="form.errors.title" class="error__text">{{ form.errors.title }}</small>
    </div>
    <div class="dialog__block">
        <div class="dialog__component" :class="form.type === 'team' ? 'small' : 'medium'">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.hackathon_format) }}</p>
            <CustomSelect
                v-model="form.format"
                :options="sortOptions"
                full-width
                red
                close-by-scroll
            />
        </div>
        <div class="dialog__component" :class="form.type === 'team' ? 'small' : 'medium'">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.participation_type) }}</p>
            <CustomSelect
                v-model="form.type"
                :options="sortOptions2"
                full-width
                red
                close-by-scroll
            />
        </div>
        <div v-if="form.type === 'team'" class="dialog__component">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.team_size) }} *</p>
            <div class="dialog__horizontal">
                <div class="dialog__info">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.from) }}</p>
                    <input
                        v-model.number="form.min_team_size"
                        type="number"
                        class="dialog__input dialog__input_short"
                        :placeholder="capitalizeFirstLetter(langStore.translations.amount)"
                        :class="{ 'error': form.errors.min_team_size }"
                        @input="clearFieldError('min_team_size')"
                    />
                </div>
                <div class="dialog__info">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.to) }}</p>
                    <input
                        v-model.number="form.max_team_size"
                        type="number"
                        class="dialog__input dialog__input_short"
                        :placeholder="capitalizeFirstLetter(langStore.translations.amount)"
                        :class="{ 'error': form.errors.max_team_size }"
                        @input="clearFieldError('max_team_size')"
                    />
                </div>
            </div>
            <small v-if="form.errors.min_team_size || form.errors.max_team_size" class="error__text">
                {{ form.errors.min_team_size }}<br>{{ form.errors.max_team_size }}
            </small>
        </div>
    </div>
    <div class="dialog__component">
        <div class="dialog__title_container">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.categories_plural) }}</p>
            <div class="help-tt" aria-label="help">
                <svg class="help-tt__icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="#000" />
                    <path d="M12 17v-5" stroke="#000" stroke-linecap="round"/>
                    <circle cx="12" cy="8" r="1" fill="#000"/>
                </svg>
                <div class="tooltipSquare"></div>
                <div class="tooltip">
                    <p>Это теги мероприятия, помогающие его поиску</p>
                </div>
            </div>
        </div>
        <div class="custom-container" ref="rootRef">
            <div class="custom-select" @click="toggleDropdownVisibility">
                <div class="selected-option">
                    <span class="dialog__tag_placeholder" v-if="!selectedDirections.length">{{ selectedDirections.length > 0 ? '' : 'Выберите направления' }}</span>
                    <div class="dialog__tags" v-if="selectedDirections.length > 0">
                        <div v-for="(direction, index) in selectedDirections" :key="index" class="dialog__tag" @click="removeDirection(direction, $event)">
                            <span>{{ direction.title }}</span>
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 -960 960 960" width="16px" fill="#1f1f1f"><path fill="#000" d="m291-240-51-51 189-189-189-189 51-51 189 189 189-189 51 51-189 189 189 189-51 51-189-189-189 189Z"/></svg>
                            </div>
                        </div>
                    </div>
                    <div>
                        <svg width="8" height="6" viewBox="0 0 8 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 1L4 4L7 1" stroke="#E80024" stroke-width="1.5"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div v-if="toggleDropdown" class="dropdown-options">
                <div
                    v-for="(option, index) in directions"
                    :key="index"
                    class="dropdown-item"
                    @click="selectDirection(option)"
                >
                    <span>{{ option.title }}</span>
                    <span v-if="selectedDirections.includes(option)" class="selected-mark">✔</span>
                </div>
                <div v-if="selectedDirections.length > 0" class="clear-selection" @click="clearSelection">
                    <span>Очистить</span>
                </div>
            </div>
        </div>
    </div>
<!--    <pre>{{form.errors}}</pre>-->
    <div class="dialog__component">
        <div class="dialog__title_container">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.registration_deadline) }} *</p>
            <div class="help-tt" aria-label="help">
                <svg class="help-tt__icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="#000" />
                    <path d="M12 17v-5" stroke="#000" stroke-linecap="round"/>
                    <circle cx="12" cy="8" r="1" fill="#000"/>
                </svg>
                <div class="tooltipSquare"></div>
                <div class="tooltip">
                    <p>Это дата, по истечению которой, прекратиться прием участников на мероприятие</p>
                    <p>Стартовая дата наступит в момент публикации мероприятия</p>
                </div>
            </div>
        </div>
        <input
            v-model="form.registration_end"
            type="datetime-local"
            id="datepicker"
            class="dialog__input"
            :class="{ 'error': form.errors.registration_end || form.errors.registration_start }"
            @input="clearFieldError('registration_end')"
        />
        <small v-if="form.errors.registration_start || form.errors.registration_end" class="error__text">
            {{ form.errors.registration_start }}<br>{{ form.errors.registration_end }}
        </small>
    </div>
    <div class="dialog__block">
        <div class="dialog__component" style="width: 100%">
            <div class="dialog__title_container">
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.event_date) }} *</p>
                <div class="help-tt" aria-label="help">
                    <svg class="help-tt__icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="#000" />
                        <path d="M12 17v-5" stroke="#000" stroke-linecap="round"/>
                        <circle cx="12" cy="8" r="1" fill="#000"/>
                    </svg>
                    <div class="tooltipSquare"></div>
                    <div class="tooltip">
                        <p>Это интервал дат мероприятия, включающий в себя решение задания и оценку проектов</p>
                    </div>
                </div>
            </div>
            <div class="dialog__horizontal">
                <div class="dialog__info">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.from) }}</p>
                    <input
                        v-model="form.event_start"
                        type="datetime-local"
                        class="dialog__input"
                        style="width: 100%"
                        :class="{ 'error': form.errors.event_start }"
                        @input="clearFieldError('event_start')"
                    />
                </div>
                <div class="dialog__info">
                    <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.to) }}</p>
                    <input
                        v-model="form.event_end"
                        type="datetime-local"
                        class="dialog__input"
                        style="width: 100%"
                        :class="{ 'error': form.errors.event_end }"
                        @input="clearFieldError('event_end')"
                    />
                </div>
            </div>
            <small v-if="form.errors.event_start || form.errors.event_end" class="error__text">
                {{ form.errors.event_start }}<br>{{ form.errors.event_end }}
            </small>
        </div>
    </div>
    <div class="dialog__component">
        <div class="dialog__title_container">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.taskTime) }} *</p>
            <div class="help-tt" aria-label="help">
                <svg class="help-tt__icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="#000" />
                    <path d="M12 17v-5" stroke="#000" stroke-linecap="round"/>
                    <circle cx="12" cy="8" r="1" fill="#000"/>
                </svg>
                <div class="tooltipSquare"></div>
                <div class="tooltip">
                    <p>Это интервал дат, в который участник (команда) может отправить свое решение на проверку</p>
                </div>
            </div>
        </div>
        <div class="dialog__horizontal">
            <div class="dialog__info" style="width: 100%">
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.from) }}</p>
                <input
                    type="datetime-local"
                    v-model="taskStart"
                    class="dialog__input"
                    :class="{ 'error': form.errors.work_time_start }"
                    @input="clearFieldError('work_time_start')"
                    style="width: 100%"
                >
            </div>

            <div class="dialog__info" style="width: 100%">
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.to) }}</p>
                <input
                    type="datetime-local"
                    v-model="taskEnd"
                    class="dialog__input"
                    :class="{ 'error': form.errors.work_time_end }"
                    @input="clearFieldError('work_time_end')"
                    style="width: 100%"
                >
            </div>
        </div>
        <small v-if="form.errors.work_time_start" class="error__text">{{ form.errors.work_time_start }}</small>
        <small v-if="form.errors.work_time_end" class="error__text">{{ form.errors.work_time_end }}</small>
    </div>
    <div class="dialog__component">
        <div class="dialog__title_container">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.reviewTime) }} *</p>
            <div class="help-tt" aria-label="help">
                <svg class="help-tt__icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="#000" />
                    <path d="M12 17v-5" stroke="#000" stroke-linecap="round"/>
                    <circle cx="12" cy="8" r="1" fill="#000"/>
                </svg>
                <div class="tooltipSquare"></div>
                <div class="tooltip">
                    <p>Это интервал дат, в который судьи оценивают работы участников</p>
                </div>
            </div>
        </div>
        <div class="dialog__horizontal">
            <div class="dialog__info" style="width: 100%">
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.from) }}</p>
                <input
                    type="datetime-local"
                    v-model="evaluationStart"
                    class="dialog__input"
                    :class="{ 'error': form.errors.evaluation_start }"
                    @input="clearFieldError('evaluation_start')"
                    style="width: 100%"
                >
            </div>

            <div class="dialog__info" style="width: 100%">
                <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.to) }}</p>
                <input
                    type="datetime-local"
                    v-model="evaluationEnd"
                    class="dialog__input"
                    :class="{ 'error': form.errors.evaluation_end }"
                    @input="clearFieldError('evaluation_end')"
                    style="width: 100%"
                >
            </div>
        </div>
        <small v-if="form.errors.evaluation_start" class="error__text">{{ form.errors.evaluation_start }}</small>
        <small v-if="form.errors.evaluation_end" class="error__text">{{ form.errors.evaluation_end }}</small>
    </div>
    <div class="dialog__block">
        <div class="dialog__component medium">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.prize_format) }}</p>
            <CustomSelect
                v-model="form.prize_type"
                :options="sortOptions3"
                full-width
                red
                close-by-scroll
            />
        </div>
        <div class="dialog__component large">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.prize_fund) }} *</p>
            <input
                v-model="form.prize_pool"
                type="text"
                class="dialog__input"
                :placeholder="capitalizeFirstLetter(langStore.translations.enter_prize_hint)"
                :class="{ 'error': form.errors.prize_pool }"
                @input="clearFieldError('prize_pool')"
            />
            <small v-if="form.errors.prize_pool" class="error__text">{{ form.errors.prize_pool }}</small>
        </div>
    </div>
    <div class="dialog__component">
        <div class="dialog__title_container">
            <p class="dialog__title">{{ capitalizeFirstLetter(langStore.translations.hackathon_card_preview) }} *</p>
            <div class="help-tt" aria-label="help">
                <svg class="help-tt__icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="#000" />
                    <path d="M12 17v-5" stroke="#000" stroke-linecap="round"/>
                    <circle cx="12" cy="8" r="1" fill="#000"/>
                </svg>
                <div class="tooltipSquare"></div>
                <div class="tooltip">
                    <p>Это картинка для карточки хакатона и страницы мероприятия</p>
                    <p>JPG/PNG, до 5 МБ</p>
                </div>
            </div>
        </div>
        <DropFile v-model:file="form.image_path"/>
        <small v-if="form.errors.image_path" class="error__text">{{ form.errors.image_path }}</small>
    </div>
    <div class="dialog__btns" v-if="!isAdmin">
        <button class="main__btn main__btn_white" @click="cancel">{{ capitalizeFirstLetter(langStore.translations.cansel) }}</button>
        <button class="main__btn" @click="save">{{ capitalizeFirstLetter(langStore.translations.save) }}</button>
    </div>
</template>

<style scoped lang="scss">
$x-small: 575.98px;
$small: 767.98px;
$medium: 991.98px;
$large: 1199.98px;
$x-large: 1399.98px;
$big: 1592.98px;
$x-big: 1829.98px;

.custom-container {
    display: flex;
    flex-direction: column;
}

.custom-select {
    border: 1px solid #E80024;
    border-radius: 100px;
    padding: 8px 16px;
    background-color: #fff;
    cursor: pointer;
}

.selected-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dropdown-options {
    background-color: #fff;
    border: 1px solid #E80024;
    z-index: 2;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-radius: 20px;
}

.dropdown-item {
    padding: 10px 15px;
    color: #E80024;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 20px;
    @media screen and (max-width: $small){
        font-size: 14px;
    }
}

.dropdown-item:hover {
    background-color: #f8f8f8;
}

.selected-mark {
    color: #E80024;
}

.clear-selection {
    padding: 10px 15px;
    background-color: #fff;
    border-top: 1px solid #E80024;
    text-align: center;
    color: #E80024;
    cursor: pointer;
    border-bottom-left-radius: 20px;
    border-bottom-right-radius: 20px;
    @media screen and (max-width: $small){
        font-size: 14px;
    }
}

.clear-selection:hover {
    background-color: #f8f8f8;
}
</style>
