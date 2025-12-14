// items.js - JavaScript для управления вещами

/**
 * Менеджер фильтров для вещей
 */
const ItemFilters = {
    init: function() {
        // Автоматическое применение фильтров при изменении
        const filterForm = document.getElementById('filters-form');
        if (filterForm) {
            const inputs = filterForm.querySelectorAll('select, input[type="text"]');
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    // Можно добавить debounce для поиска
                    if (this.type === 'text') {
                        clearTimeout(this.searchTimeout);
                        this.searchTimeout = setTimeout(() => {
                            filterForm.submit();
                        }, 500);
                    } else {
                        filterForm.submit();
                    }
                });
            });
        }
    }
};

/**
 * Менеджер вещей
 */
const ItemManager = {
    /**
     * Удалить вещь
     */
    delete: async function(itemId) {
        if (!confirm('Вы уверены, что хотите удалить эту вещь?')) {
            return false;
        }

        try {
            const response = await fetch(`/api/items/${itemId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success) {
                return true;
            } else {
                alert(result.message || 'Ошибка при удалении вещи');
                return false;
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Ошибка при удалении вещи');
            return false;
        }
    },

    /**
     * Получить вещь по ID
     */
    get: async function(itemId) {
        try {
            const response = await fetch(`/api/items/${itemId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success) {
                return result.data;
            } else {
                console.error('Error:', result.message);
                return null;
            }
        } catch (error) {
            console.error('Error:', error);
            return null;
        }
    },

    /**
     * Получить список вещей с фильтрами
     */
    list: async function(filters = {}) {
        try {
            const params = new URLSearchParams();
            Object.keys(filters).forEach(key => {
                if (filters[key] !== null && filters[key] !== undefined && filters[key] !== '') {
                    if (Array.isArray(filters[key])) {
                        params.append(key, filters[key].join(','));
                    } else {
                        params.append(key, filters[key]);
                    }
                }
            });

            const response = await fetch(`/api/items?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success) {
                return result.data;
            } else {
                console.error('Error:', result.message);
                return [];
            }
        } catch (error) {
            console.error('Error:', error);
            return [];
        }
    }
};

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ItemFilters, ItemManager };
}
