// public/js/tags.js

/**
 * Модуль для работы с тегами через API
 */
let TagManager = (function() {
    // Конфигурация
    const config = {
        apiBase: '/api/tags',
        endpoints: {
            index: '/api/tags',
            search: '/api/tags/search',
            store: '/api/tags',
            update: '/api/tags',
            destroy: '/api/tags',
            popular: '/api/tags/popular',
            grouped: '/api/tags/grouped'
        }
    };

    // Хранилище тегов
    let tags = [];
    let groupedTags = { system: [], user: [] };

    // Инициализация
    function init() {
        console.log('TagManager инициализирован');
        return loadTags();
    }

    // Загрузить все теги
    async function loadTags() {
        try {
            const response = await fetch(config.endpoints.index);
            const data = await response.json();

            if (data.success) {
                tags = data.data;
                console.log(`Загружено ${tags.length} тегов`);
                return tags;
            } else {
                throw new Error(data.message || 'Ошибка загрузки тегов');
            }
        } catch (error) {
            console.error('Ошибка загрузки тегов:', error);
            showError('Не удалось загрузить теги');
            return [];
        }
    }

    // Загрузить группированные теги
    async function loadGroupedTags() {
        try {
            const response = await fetch(config.endpoints.grouped);
            const data = await response.json();

            if (data.success) {
                groupedTags = data.data;
                return groupedTags;
            } else {
                throw new Error(data.message || 'Ошибка загрузки тегов');
            }
        } catch (error) {
            console.error('Ошибка загрузки группированных тегов:', error);
            return { system: [], user: [] };
        }
    }

    // Поиск тегов
    async function searchTags(query, limit = 10) {
        if (query.length < 2) {
            return [];
        }

        try {
            const url = new URL(config.endpoints.search, window.location.origin);
            url.searchParams.append('query', query);
            url.searchParams.append('limit', limit);

            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                return data.data;
            } else {
                throw new Error(data.message || 'Ошибка поиска тегов');
            }
        } catch (error) {
            console.error('Ошибка поиска тегов:', error);
            return [];
        }
    }

    // Создать новый тег
    async function createTag(name, color = '') {
        try {
            const response = await fetch(config.endpoints.store, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ 
                    name: name.trim(), 
                    color: color || '' 
                })
            });

            const data = await response.json();

            if (data.success) {
                // Добавляем новый тег в хранилище
                if (data.data) {
                    tags.push(data.data);
                }
                showSuccess(data.message || 'Тег успешно создан');
                return data.data;
            } else {
                throw new Error(data.message || 'Ошибка создания тега');
            }
        } catch (error) {
            console.error('Ошибка создания тега:', error);
            showError(error.message || 'Не удалось создать тег');
            return null;
        }
    }

    // Обновить тег
    async function updateTag(id, name, color) {
        try {
            const response = await fetch(`${config.endpoints.update}/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ 
                    name: name.trim(), 
                    color: color || '' 
                })
            });

            const data = await response.json();

            if (data.success) {
                // Обновляем тег в хранилище
                const index = tags.findIndex(tag => tag.id == id);
                if (index !== -1 && data.data) {
                    tags[index] = data.data;
                }
                showSuccess(data.message || 'Тег успешно обновлен');
                return data.data;
            } else {
                throw new Error(data.message || 'Ошибка обновления тега');
            }
        } catch (error) {
            console.error('Ошибка обновления тега:', error);
            showError(error.message || 'Не удалось обновить тег');
            return null;
        }
    }

    // Удалить тег
    async function deleteTag(id) {
        if (!confirm('Вы уверены, что хотите удалить этот тег?')) {
            return false;
        }

        try {
            const response = await fetch(`${config.endpoints.destroy}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                // Удаляем тег из хранилища
                tags = tags.filter(tag => tag.id != id);
                showSuccess(data.message || 'Тег успешно удален');
                return true;
            } else {
                throw new Error(data.message || 'Ошибка удаления тега');
            }
        } catch (error) {
            console.error('Ошибка удаления тега:', error);
            showError(error.message || 'Не удалось удалить тег');
            return false;
        }
    }

    // Получить популярные теги
    async function getPopularTags(limit = 10) {
        try {
            const url = new URL(config.endpoints.popular, window.location.origin);
            url.searchParams.append('limit', limit);

            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                return data.data;
            } else {
                throw new Error(data.message || 'Ошибка загрузки популярных тегов');
            }
        } catch (error) {
            console.error('Ошибка загрузки популярных тегов:', error);
            return [];
        }
    }

    // Получить все теги
    function getAllTags() {
        return [...tags];
    }

    // Получить системные теги
    function getSystemTags() {
        return tags.filter(tag => tag.is_system);
    }

    // Получить пользовательские теги
    function getUserTags() {
        return tags.filter(tag => !tag.is_system);
    }

    // Найти тег по ID
    function findTagById(id) {
        return tags.find(tag => tag.id === id);
    }

    // Найти тег по имени
    function findTagByName(name) {
        return tags.find(tag => tag.name.toLowerCase() === name.toLowerCase());
    }

    // Вспомогательные функции для уведомлений
    function showSuccess(message) {
        console.log('Success:', message);
        // Пытаемся использовать Bootstrap toast, если доступен
        if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
            const toastElement = document.createElement('div');
            toastElement.className = 'toast align-items-center text-white bg-success border-0';
            toastElement.setAttribute('role', 'alert');
            toastElement.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            document.body.appendChild(toastElement);
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
        } else {
            alert(message);
        }
    }

    function showError(message) {
        console.error('Error:', message);
        // Пытаемся использовать Bootstrap toast, если доступен
        if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
            const toastElement = document.createElement('div');
            toastElement.className = 'toast align-items-center text-white bg-danger border-0';
            toastElement.setAttribute('role', 'alert');
            toastElement.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            document.body.appendChild(toastElement);
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
        } else {
            alert('Ошибка: ' + message);
        }
    }

    // Публичное API
    return {
        init,
        loadTags,
        loadGroupedTags,
        searchTags,
        createTag,
        updateTag,
        deleteTag,
        getPopularTags,
        getAllTags,
        getSystemTags,
        getUserTags,
        findTagById,
        findTagByName,
        getGroupedTags: () => groupedTags
    };
})();

/**
 * Компонент селектора тегов с автодополнением
 */
class TagSelector {
    constructor(options = {}) {
        this.element = options.element;
        this.onChange = options.onChange || (() => {});
        this.maxTags = options.maxTags || 10;
        this.allowCreate = options.allowCreate !== false;

        this.selectedTags = new Set();
        this.selectedTagIds = new Set();

        this.init();
    }

    async init() {
        // Загружаем теги
        await TagManager.loadGroupedTags();

        // Создаем интерфейс
        this.createUI();

        // Инициализируем автодополнение
        this.initAutocomplete();
    }

    createUI() {
        // Контейнер
        this.container = document.createElement('div');
        this.container.className = 'tag-selector';

        // Поле ввода
        this.input = document.createElement('input');
        this.input.type = 'text';
        this.input.placeholder = 'Введите название тега...';
        this.input.className = 'form-control tag-input';

        // Контейнер для выбранных тегов
        this.selectedContainer = document.createElement('div');
        this.selectedContainer.className = 'selected-tags mt-2';

        // Контейнер для подсказок
        this.suggestionsContainer = document.createElement('div');
        this.suggestionsContainer.className = 'tag-suggestions';
        this.suggestionsContainer.style.display = 'none';

        // Собираем интерфейс
        this.container.appendChild(this.input);
        this.container.appendChild(this.suggestionsContainer);
        this.container.appendChild(this.selectedContainer);

        // Заменяем оригинальный элемент
        if (this.element) {
            this.element.parentNode.replaceChild(this.container, this.element);
        }
    }

    initAutocomplete() {
        let timeout;

        this.input.addEventListener('input', async () => {
            clearTimeout(timeout);

            timeout = setTimeout(async () => {
                const query = this.input.value.trim();

                if (query.length < 2) {
                    this.hideSuggestions();
                    return;
                }

                const suggestions = await TagManager.searchTags(query);
                this.showSuggestions(suggestions, query);
            }, 300);
        });

        // Обработка клавиш
        this.input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.handleEnter();
            } else if (e.key === 'Backspace' && this.input.value === '') {
                this.removeLastTag();
            }
        });

        // Клик вне поля скрывает подсказки
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) {
                this.hideSuggestions();
            }
        });
    }

    showSuggestions(suggestions, query) {
        if (suggestions.length === 0 && this.allowCreate) {
            // Показать опцию создания нового тега
            this.suggestionsContainer.innerHTML = `
                <div class="tag-suggestion create-new" data-action="create">
                    <span class="text-muted">Создать тег:</span>
                    <strong>"${query}"</strong>
                </div>
            `;
        } else {
            // Показать найденные теги
            const suggestionsHtml = suggestions.map(tag => `
                <div class="tag-suggestion" data-tag-id="${tag.id}">
                    <span class="tag-color" style="background-color: ${tag.color}"></span>
                    ${tag.name}
                    ${tag.is_system ? '<span class="badge bg-secondary ms-2">Системный</span>' : ''}
                </div>
            `).join('');

            this.suggestionsContainer.innerHTML = suggestionsHtml;
        }

        this.suggestionsContainer.style.display = 'block';

        // Добавляем обработчики кликов
        this.suggestionsContainer.querySelectorAll('.tag-suggestion').forEach(item => {
            item.addEventListener('click', () => {
                if (item.dataset.action === 'create') {
                    this.createNewTag(query);
                } else {
                    this.selectTag(parseInt(item.dataset.tagId));
                }
                this.hideSuggestions();
            });
        });
    }

    hideSuggestions() {
        this.suggestionsContainer.style.display = 'none';
    }

    async handleEnter() {
        const query = this.input.value.trim();

        if (query === '') return;

        // Ищем существующий тег
        const existingTag = TagManager.findTagByName(query);

        if (existingTag) {
            this.selectTag(existingTag.id);
        } else if (this.allowCreate) {
            await this.createNewTag(query);
        }

        this.input.value = '';
        this.hideSuggestions();
    }

    async createNewTag(name) {
        const tag = await TagManager.createTag(name);
        if (tag) {
            this.selectTag(tag.id);
        }
    }

    selectTag(tagId) {
        if (this.selectedTagIds.has(tagId)) {
            return; // Тег уже выбран
        }

        if (this.selectedTags.size >= this.maxTags) {
            alert(`Максимум ${this.maxTags} тегов`);
            return;
        }

        const tag = TagManager.findTagById(tagId);
        if (!tag) return;

        this.selectedTags.add(tag);
        this.selectedTagIds.add(tagId);

        this.renderSelectedTags();
        this.onChange(this.getSelectedTags());
    }

    removeTag(tagId) {
        this.selectedTags = new Set([...this.selectedTags].filter(tag => tag.id !== tagId));
        this.selectedTagIds.delete(tagId);

        this.renderSelectedTags();
        this.onChange(this.getSelectedTags());
    }

    removeLastTag() {
        const lastTag = [...this.selectedTags].pop();
        if (lastTag) {
            this.removeTag(lastTag.id);
        }
    }

    renderSelectedTags() {
        this.selectedContainer.innerHTML = '';

        this.selectedTags.forEach(tag => {
            const tagElement = document.createElement('span');
            tagElement.className = 'selected-tag badge me-1 mb-1';
            tagElement.style.backgroundColor = tag.color;
            tagElement.style.color = getContrastColor(tag.color);

            tagElement.innerHTML = `
                ${tag.name}
                <button type="button" class="btn-close btn-close-white ms-1" 
                        style="font-size: 0.6rem;"
                        data-tag-id="${tag.id}"></button>
            `;

            this.selectedContainer.appendChild(tagElement);

            // Добавляем обработчик удаления
            tagElement.querySelector('button').addEventListener('click', (e) => {
                e.stopPropagation();
                this.removeTag(tag.id);
            });
        });
    }

    getSelectedTags() {
        return [...this.selectedTags];
    }

    getSelectedTagIds() {
        return [...this.selectedTagIds];
    }

    setSelectedTags(tagIds) {
        this.clear();

        tagIds.forEach(tagId => {
            const tag = TagManager.findTagById(tagId);
            if (tag) {
                this.selectedTags.add(tag);
                this.selectedTagIds.add(tagId);
            }
        });

        this.renderSelectedTags();
    }

    clear() {
        this.selectedTags.clear();
        this.selectedTagIds.clear();
        this.renderSelectedTags();
        this.onChange([]);
    }
}

/**
 * Вспомогательная функция для определения контрастного цвета текста
 */
function getContrastColor(hexColor) {
    hexColor = hexColor.replace('#', '');

    if (hexColor.length === 3) {
        hexColor = hexColor[0] + hexColor[0] + hexColor[1] + hexColor[1] + hexColor[2] + hexColor[2];
    }

    const r = parseInt(hexColor.substr(0, 2), 16);
    const g = parseInt(hexColor.substr(2, 2), 16);
    const b = parseInt(hexColor.substr(4, 2), 16);

    const brightness = (r * 299 + g * 587 + b * 114) / 1000;

    return brightness > 128 ? '#000000' : '#FFFFFF';
}

// Экспортируем модули для глобального использования
window.TagManager = TagManager;
window.TagSelector = TagSelector;