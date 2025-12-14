<?php
// public/views/tags/manage.php

/**
 * @var array $tags
 * @var string $title
 */
?>
<div class="container tags-management mt-4">
    <h1 class="mb-4"><?= htmlspecialchars($title) ?></h1>

    <!-- Форма создания нового тега -->
    <div class="tag-form">
        <h3 class="mb-3">Создать новый тег</h3>
        <form id="create-tag-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="tag-name" class="form-label">Название тега</label>
                    <input type="text"
                           id="tag-name"
                           class="form-control"
                           placeholder="Введите название тега..."
                           required>
                    <div class="invalid-feedback" id="name-error"></div>
                </div>

                <div class="form-group" style="width: 120px;">
                    <label for="tag-color" class="form-label">Цвет</label>
                    <input type="color"
                           id="tag-color"
                           class="form-control"
                           value="#3B82F6"
                           style="height: 40px; padding: 5px;">
                    <div class="color-preview" id="color-preview" style="background-color: #3B82F6;"></div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="height: 40px;">
                        Создать тег
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Список всех тегов -->
    <div class="tags-list">
        <h3 class="mb-3">Все теги</h3>

        <?php if (empty($tags)): ?>
            <div class="alert alert-info">
                У вас пока нет тегов. Создайте первый тег с помощью формы выше.
            </div>
        <?php else: ?>
            <?php foreach ($tags as $tag): ?>
                <div class="tag-card" data-tag-id="<?= $tag['id'] ?>">
                    <div class="tag-info">
                        <div class="tag-color-badge"
                             style="background-color: <?= $tag['color'] ?>;"></div>
                        <div class="tag-details">
                            <span class="tag-name"><?= htmlspecialchars($tag['name']) ?></span>
                            <div class="tag-meta">
                                <span class="tag-type <?= $tag['is_system'] ? 'system' : 'user' ?>">
                                    <?= $tag['is_system'] ? 'Системный' : 'Пользовательский' ?>
                                </span>
                                <?php if (!$tag['is_system']): ?>
                                    <span> • Создан: <?= date('d.m.Y', strtotime($tag['created_at'])) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!$tag['is_system']): ?>
                        <div class="tag-actions">
                            <button type="button"
                                    class="btn btn-sm btn-outline-primary edit-tag-btn"
                                    data-tag-id="<?= $tag['id'] ?>"
                                    data-tag-name="<?= htmlspecialchars($tag['name']) ?>"
                                    data-tag-color="<?= $tag['color'] ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger delete-tag-btn"
                                    data-tag-id="<?= $tag['id'] ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Модальное окно редактирования тега -->
<div class="modal fade" id="editTagModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Редактировать тег</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="edit-tag-form">
                <div class="modal-body">
                    <input type="hidden" id="edit-tag-id">

                    <div class="mb-3">
                        <label for="edit-tag-name" class="form-label">Название тега</label>
                        <input type="text"
                               id="edit-tag-name"
                               class="form-control"
                               required>
                        <div class="invalid-feedback" id="edit-name-error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="edit-tag-color" class="form-label">Цвет</label>
                        <input type="color"
                               id="edit-tag-color"
                               class="form-control"
                               style="height: 40px; padding: 5px;">
                        <div class="color-preview" id="edit-color-preview"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Подключаем скрипты -->
<script src="/assets/js/tags.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Инициализируем менеджер тегов
        TagManager.init();

        // Обработка формы создания тега
        const createForm = document.getElementById('create-tag-form');
        const colorInput = document.getElementById('tag-color');
        const colorPreview = document.getElementById('color-preview');

        // Обновление предпросмотра цвета
        colorInput.addEventListener('input', function() {
            colorPreview.style.backgroundColor = this.value;
        });

        // Создание тега
        createForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const nameInput = document.getElementById('tag-name');
            const name = nameInput.value.trim();
            const color = colorInput.value;

            // Валидация
            if (!name) {
                showError(nameInput, 'Введите название тега');
                return;
            }

            if (name.length > 50) {
                showError(nameInput, 'Название не должно превышать 50 символов');
                return;
            }

            // Создаем тег
            const tag = await TagManager.createTag(name, color);

            if (tag) {
                // Обновляем страницу
                location.reload();
            }
        });

        // Обработка кнопок редактирования
        document.querySelectorAll('.edit-tag-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tagId = this.dataset.tagId;
                const tagName = this.dataset.tagName;
                const tagColor = this.dataset.tagColor;

                // Заполняем форму в модальном окне
                document.getElementById('edit-tag-id').value = tagId;
                document.getElementById('edit-tag-name').value = tagName;
                document.getElementById('edit-tag-color').value = tagColor;
                document.getElementById('edit-color-preview').style.backgroundColor = tagColor;

                // Показываем модальное окно
                const modal = new bootstrap.Modal(document.getElementById('editTagModal'));
                modal.show();
            });
        });

        // Обработка формы редактирования тега
        const editForm = document.getElementById('edit-tag-form');

        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const tagId = document.getElementById('edit-tag-id').value;
            const name = document.getElementById('edit-tag-name').value.trim();
            const color = document.getElementById('edit-tag-color').value;

            if (!name) {
                showError(document.getElementById('edit-tag-name'), 'Введите название тега');
                return;
            }

            const tag = await TagManager.updateTag(tagId, name, color);

            if (tag) {
                // Закрываем модальное окно и обновляем страницу
                bootstrap.Modal.getInstance(document.getElementById('editTagModal')).hide();
                location.reload();
            }
        });

        // Обработка кнопок удаления
        document.querySelectorAll('.delete-tag-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const tagId = this.dataset.tagId;

                const confirmed = await TagManager.deleteTag(tagId);

                if (confirmed) {
                    // Удаляем карточку тега из DOM
                    const tagCard = document.querySelector(`.tag-card[data-tag-id="${tagId}"]`);
                    if (tagCard) {
                        tagCard.remove();
                    }
                }
            });
        });

        // Вспомогательная функция для показа ошибок
        function showError(input, message) {
            input.classList.add('is-invalid');
            const errorElement = input.nextElementSibling;
            if (errorElement && errorElement.classList.contains('invalid-feedback')) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            }

            // Убираем ошибку при исправлении
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            }, { once: true });
        }
    });
</script>