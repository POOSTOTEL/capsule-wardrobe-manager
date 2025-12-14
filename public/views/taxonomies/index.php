<?php



?>
<div class="container mt-4">
    <h1 class="mb-4"><?= htmlspecialchars($title) ?></h1>

    <div class="row">
        
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Категории вещей</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($taxonomies['categories'] as $category): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><?= htmlspecialchars($category['name']) ?></span>
                                    <span class="badge bg-secondary">ID: <?= $category['id'] ?></span>
                                </div>
                                <?php if (!empty($category['description'])): ?>
                                    <small class="text-muted d-block mt-1">
                                        <?= htmlspecialchars($category['description']) ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h5 class="mb-0">Цветовая палитра</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <?php foreach ($taxonomies['colors'] as $color): ?>
                            <div class="col-6">
                                <div class="p-3 rounded" style="background-color: <?= $color['hex_code'] ?>; color: <?= $color['text_color'] ?>;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong><?= htmlspecialchars($color['name']) ?></strong>
                                        <small>#<?= ltrim($color['hex_code'], '#') ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Сезонность</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($taxonomies['seasons'] as $season): ?>
                            <div class="list-group-item">
                                <div class="d-flex align-items-center">
                                    <?php
                                    $icon = match($season['name']) {
                                        'Лето' => '☀️',
                                        'Зима' => '❄️',
                                        'Весна' => '🌱',
                                        'Осень' => '🍂',
                                        default => '🔄'
                                    };
                                    ?>
                                    <span class="me-3" style="font-size: 1.5rem;"><?= $icon ?></span>
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($season['name']) ?></h6>
                                        <small class="text-muted">
                                            <?php
                                            $desc = match($season['name']) {
                                                'Лето' => 'Легкая одежда, светлые тона',
                                                'Зима' => 'Теплая одежда, темные тона',
                                                'Весна' => 'Переходная одежда, пастельные тона',
                                                'Осень' => 'Утепленная одежда, теплые тона',
                                                'Демисезон' => 'Универсальная одежда',
                                                'Всесезон' => 'Одежда для любого времени года',
                                                default => ''
                                            };
                                            echo $desc;
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Статистика справочников</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="display-4 text-primary"><?= count($taxonomies['categories']) ?></div>
                            <p class="text-muted">Категорий</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="display-4 text-warning"><?= count($taxonomies['colors']) ?></div>
                            <p class="text-muted">Цветов</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="display-4 text-success"><?= count($taxonomies['seasons']) ?></div>
                            <p class="text-muted">Сезонов</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Информация о справочниках</h5>
                </div>
                <div class="card-body">
                    <p>Справочники используются для классификации вещей в гардеробе:</p>
                    <ul>
                        <li><strong>Категории</strong> - определяют тип вещи (верх, низ, обувь и т.д.)</li>
                        <li><strong>Цвета</strong> - цветовая палитра для фильтрации и поиска</li>
                        <li><strong>Сезоны</strong> - время года, для которого подходит вещь</li>
                    </ul>
                    <p class="mb-0">Эти справочники предустановлены и используются во всех модулях приложения.</p>
                </div>
            </div>
        </div>
    </div>
</div>