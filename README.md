# Hackathon

Платформа для проведения хакатонов: создание мероприятий, регистрация команд, загрузка проектов, оценка жюри, галерея работ, модерация и админка. Интерфейс многоязычный (7 языков).

## Стек

- **Backend:** Laravel 10 (PHP 8.1+)
- **Frontend:** Vue 3 (Composition API, `<script setup>`) + Inertia.js
- **Сборка:** Vite
- **Состояние:** Pinia
- **Стили:** SCSS
- **Библиотеки:** Editor.js (редактор контента), SortableJS (drag-n-drop), Swiper (слайдеры), VueUse, vue-toastification

## Где смотреть фронтенд

Весь фронт лежит в `resources/js`:

```
resources/js/
├── Pages/        # страницы под Inertia (Hackathon, Admin, Profile, Dashboard …)
├── Components/   # компоненты, в т.ч. кастомные (CustomSelect, EditorField, диалоги)
├── Layouts/      # лейауты
├── store/        # сторы Pinia (lang — i18n, notification)
├── utils/        # утилиты (renderEdjs — рендер Editor.js → HTML и др.)
└── app.js        # точка входа
```