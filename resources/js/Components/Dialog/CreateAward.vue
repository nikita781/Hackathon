<script setup>
import { reactive, watch, onBeforeUnmount, toRaw } from 'vue'
import DropFile from "@/Components/DropFile.vue";
import {ref} from "vue";

const props = defineProps({
    modelValue : Boolean,
    hackathonSlug : { type:String, required:true },
    initial    : { type:Object, default:null },
    defaultType   : { type:String, default:'forAll' }
})
const emit = defineEmits(['update:modelValue','saved'])

const blank = {
    id     : null,
    type   : 'forAll',
    place  : '',
    title  : '',
    text   : '',
    file   : null,
    imgUrl : ''
}
const form = reactive({ ...blank })

function revoke () {
    if (form.imgUrl.startsWith('blob:')) URL.revokeObjectURL(form.imgUrl)
}

const getImgSrc = (aw) => {
    if (aw._localBlob) return aw._localBlob

    const ver = aw.updated_at ?? Date.now()
    return `${route('awards.image', { award: aw.id })}?v=${ver}`
}

watch([() => props.initial, () => props.defaultType], ([val, def]) => {
    revoke()
    if (!val) {
        Object.assign(form, { ...blank, type: def })
        return
    }

    Object.assign(form, {
        id    : val.id,
        type  : val.for_all ? 'forAll' : 'forPrize',
        place : val.place   ?? '',
        title : val.title,
        text  : val.description ?? '',
        file  : null,
        imgUrl: getImgSrc(val)
    })
},{ immediate:true })
onBeforeUnmount(revoke)

function close () { emit('update:modelValue', false) }

function onFileChange (f) {
    if (!f || !f.type.startsWith('image/')) return
    revoke()
    form.file = f
    form.imgUrl = URL.createObjectURL(f)
    if (props.initial) props.initial._localBlob = form.imgUrl
}

async function save () {
    const fd = new FormData()
    fd.append('title',       form.title.trim())
    fd.append('description', form.text.trim())
    fd.append('for_all', form.type === 'forAll' ? 1 : 0)
    fd.append('place',       form.type === 'forPrize' ? (form.place || '') : '')
    fd.append('system', 0)

    if (form.file) fd.append('image', form.file)

    // console.log('FD →', [...fd.entries()].map(([k, v]) => [k, v instanceof File ? v.name : v]));

    try {
        if (form.id) {
            fd.append('_method','PATCH')
            await axios.post(
                route('hackathons.awards.update', { hackathon: props.hackathonSlug, award: form.id }),
                fd, { headers:{ 'Content-Type':'multipart/form-data' } }
            )
        } else {
            await axios.post(
                route('hackathons.awards.store',  { hackathon: props.hackathonSlug }),
                fd, { headers:{ 'Content-Type':'multipart/form-data' } }
            )
        }
        emit('saved')
        close()
    } catch (e) {
        if (e.response?.status === 422) { console.table(e.response.data.errors) }
        else console.error('award-save',e)
        console.log(e.response && e.response.data);
    }
}
</script>

<template>
    <div v-if="modelValue" class="dialog" style="z-index:3" @click.self="close">
        <div class="dialog__container dialog__container_small" @click.stop>
            <div class="dialog__header">
                <p>{{ props.initial ? 'Редактировать награду' : 'Добавить награду' }}</p>
                <div class="dialog__close" @click="close">✕</div>
            </div>
            <div class="dialog__component">
                <p class="dialog__title">Тип награды</p>
                <select v-model="form.type" class="main__cards_select dialog__select">
                    <option value="forAll">Для всех</option>
                    <option value="forPrize">Для призовых мест</option>
                </select>
            </div>
            <div class="dialog__component" v-if="form.type === 'forPrize'">
                <p class="dialog__title">Место</p>
                <input type="number" min="1" v-model="form.place" class="dialog__input" placeholder="Введите место"/>
            </div>
            <div class="dialog__component">
                <p class="dialog__title">Название награды</p>
                <input v-model="form.title" class="dialog__input" placeholder="Введите название"/>
            </div>
            <div class="dialog__component">
                <p class="dialog__title">Описание награды</p>
                <textarea v-model="form.text" class="dialog__textarea" placeholder="Введите описание"/>
            </div>
            <div class="dialog__component">
                <p class="dialog__title">Изображение</p>
                <DropFile :file="form.file ?? form.imgUrl" @update:file="onFileChange"/>
            </div>
            <div class="dialog__btns">
                <button class="main__btn main__btn_white dialog__btn" @click="close">
                    Отменить
                </button>
                <button class="main__btn dialog__btn" @click="save">
                    {{ props.initial ? 'Изменить' : 'Добавить' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
