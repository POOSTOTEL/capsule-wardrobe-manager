<?php
/**
 * Детальная информация о капсуле
 * 
 * @var array $capsule
 * @var string $title
 */
?>

<div class="page-header mb-4">
        <div>
            <h1><?= htmlspecialchars($capsule['name']) ?></h1>
            <div class="capsule-meta-header">
                <span class="text-muted">
                    Создано: <?= date('d.m.Y', strtotime($capsule['created_at'])) ?>
                </span>
            </div>
        </div>
        <div class="header-actions">
            <?php if (!empty($capsule['items'])): ?>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#generateOutfitsModal">
                    <i class="fas fa-magic me-2"></i>Сгенерировать образы
                </button>
            <?php endif; ?>
            <a href="/capsules/<?= $capsule['id'] ?>/combinations" class="btn btn-info">
                <i class="fas fa-magic me-2"></i>Комбинации
            </a>
            <a href="/capsules/<?= $capsule['id'] ?>/edit" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>Редактировать
            </a>
            <a href="/capsules" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Назад
            </a>
        </div>
    </div>

    <?php if (!empty($capsule['description'])): ?>
        <div class="card p-4 mb-4">
            <h3 class="mb-3">Описание</h3>
            <p class="mb-0"><?= nl2br(htmlspecialchars($capsule['description'])) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($capsule['season_name'])): ?>
        <div class="card p-4 mb-4">
            <h3 class="mb-3">Сезонность</h3>
            <p class="mb-0">
                <span class="badge bg-info">
                    <i class="fas fa-calendar me-1"></i>
                    <?= htmlspecialchars($capsule['season_name']) ?>
                </span>
            </p>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Вещи -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">
                        <i class="fas fa-tshirt me-2"></i>Вещи в капсуле
                        <span class="badge bg-primary"><?= count($capsule['items']) ?></span>
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($capsule['items'])): ?>
                        <div class="items-grid">
                            <?php foreach ($capsule['items'] as $item): ?>
                                <div class="item-card" data-item-id="<?= $item['id'] ?>">
                                    <a href="/items/<?= $item['id'] ?>" class="item-link">
                                        <div class="item-image">
                                            <img src="/api/items/<?= $item['id'] ?>/image" 
                                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                                 loading="lazy"
                                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'300\'%3E%3Crect fill=\'%23ddd\' width=\'300\' height=\'300\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'18\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EНет фото%3C/text%3E%3C/svg%3E';">
                                        </div>
                                        <div class="item-info">
                                            <h3 class="item-name"><?= htmlspecialchars($item['name']) ?></h3>
                                            <div class="item-meta">
                                                <?php if (!empty($item['category_name'])): ?>
                                                    <span class="item-category">
                                                        <i class="fas fa-tag me-1"></i>
                                                        <?= htmlspecialchars($item['category_name']) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['color_name'])): ?>
                                                    <span class="item-color" style="color: <?= htmlspecialchars($item['color_hex'] ?? '#666') ?>">
                                                        <i class="fas fa-palette me-1"></i>
                                                        <?= htmlspecialchars($item['color_name']) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['season_name'])): ?>
                                                    <span class="item-season">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?= htmlspecialchars($item['season_name']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($item['tags'])): ?>
                                                <div class="item-tags">
                                                    <?php foreach (array_slice($item['tags'], 0, 3) as $tag): ?>
                                                        <span class="item-tag" 
                                                              style="background-color: <?= htmlspecialchars($tag['color'] ?? '#6B7280') ?>">
                                                            <?= htmlspecialchars($tag['name']) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                    <?php if (count($item['tags']) > 3): ?>
                                                        <span class="item-tag-more">+<?= count($item['tags']) - 3 ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (isset($item['usage_count']) && $item['usage_count'] > 0): ?>
                                                <div class="item-usage">
                                                    <i class="fas fa-chart-line me-1"></i>
                                                    Использовано: <?= $item['usage_count'] ?> раз
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                    <div class="item-actions">
                                        <a href="/items/<?= $item['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            В капсуле пока нет вещей.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Образы -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">
                        <i class="fas fa-palette me-2"></i>Образы в капсуле
                        <span class="badge bg-primary"><?= count($capsule['outfits']) ?></span>
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($capsule['outfits'])): ?>
                        <div class="outfits-list-detail">
                            <?php foreach ($capsule['outfits'] as $outfit): ?>
                                <div class="outfit-card-detail">
                                    <a href="/outfits/<?= $outfit['id'] ?>" class="outfit-link-detail">
                                        <div class="outfit-preview-detail">
                                            <?php if (!empty($outfit['items'])): ?>
                                                <?php foreach (array_slice($outfit['items'], 0, 4) as $item): ?>
                                                    <div class="preview-item-detail">
                                                        <img src="/api/items/<?= $item['id'] ?>/image" 
                                                             alt="<?= htmlspecialchars($item['name']) ?>"
                                                             loading="lazy"
                                                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect fill=\'%23ddd\' width=\'100\' height=\'100\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'10\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EНет фото%3C/text%3E%3C/svg%3E';">
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if (count($outfit['items']) > 4): ?>
                                                    <div class="preview-more-detail">
                                                        +<?= count($outfit['items']) - 4 ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="outfit-info-detail">
                                            <div class="outfit-name-detail"><?= htmlspecialchars($outfit['name']) ?></div>
                                            <?php if (!empty($outfit['items'])): ?>
                                                <div class="outfit-meta-detail">
                                                    <i class="fas fa-tshirt me-1"></i>
                                                    <?= count($outfit['items']) ?> вещей
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            В капсуле пока нет образов.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Статистика -->
    <div class="card p-4">
        <h3 class="mb-3">Статистика капсулы</h3>
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= count($capsule['items']) ?></div>
                        <div class="stat-label">Вещей</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= count($capsule['outfits']) ?></div>
                        <div class="stat-label">Образов</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= date('d.m.Y', strtotime($capsule['created_at'])) ?></div>
                        <div class="stat-label">Создано</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-sync"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= date('d.m.Y', strtotime($capsule['updated_at'])) ?></div>
                        <div class="stat-label">Обновлено</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для генерации образов -->
<div class="modal fade" id="generateOutfitsModal" tabindex="-1" aria-labelledby="generateOutfitsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generateOutfitsModalLabel">
                    <i class="fas fa-magic me-2"></i>Сгенерировать образы из капсулы
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form id="generateOutfitsForm">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        На основе вещей в капсуле будут автоматически созданы образы. Каждый образ будет сохранен и связан с этой капсулой.
                    </div>
                    <div class="mb-3">
                        <label for="outfitsCount" class="form-label">Количество образов для генерации</label>
                        <input type="number" class="form-control" id="outfitsCount" name="count" 
                               min="1" max="50" value="5" required>
                        <div class="form-text">Введите число от 1 до 50</div>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted">
                            <i class="fas fa-tshirt me-1"></i>
                            В капсуле: <strong><?= count($capsule['items']) ?></strong> вещей
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-success" id="generateOutfitsBtn">
                        <i class="fas fa-magic me-2"></i>Сгенерировать
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('generateOutfitsForm');
    const modal = new bootstrap.Modal(document.getElementById('generateOutfitsModal'));
    const submitBtn = document.getElementById('generateOutfitsBtn');
    
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const count = parseInt(document.getElementById('outfitsCount').value);
            
            if (count < 1 || count > 50) {
                alert('Количество образов должно быть от 1 до 50');
                return;
            }
            
            // Блокируем кнопку
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Генерация...';
            
            try {
                const response = await fetch('/capsules/<?= $capsule['id'] ?>/generate-outfits', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        count: count
                    })
                });
                
                // Проверяем, является ли ответ JSON
                const contentType = response.headers.get('content-type') || '';
                let data;
                
                if (contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    // Если ответ не JSON, пытаемся распарсить как текст
                    const text = await response.text();
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        // Если не JSON, значит сервер вернул HTML (редирект или ошибка)
                        throw new Error('Сервер вернул неожиданный ответ. Попробуйте обновить страницу.');
                    }
                }
                
                if (data.success) {
                    modal.hide();
                    // Показываем сообщение об успехе
                    const message = data.message || `Успешно сгенерировано ${data.data?.generated_count || count} образов`;
                    alert(message);
                    // Перезагружаем страницу для отображения новых образов
                    window.location.reload();
                } else {
                    alert(data.message || 'Ошибка при генерации образов');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-magic me-2"></i>Сгенерировать';
                }
            } catch (error) {
                console.error('Ошибка:', error);
                alert('Произошла ошибка при генерации образов: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-magic me-2"></i>Сгенерировать';
            }
        });
    }
});
</script>
