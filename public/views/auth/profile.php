<?php

$user = $user ?? [];
$stats = $stats ?? [];
$errors = $errors ?? [];
$form_data = $form_data ?? $user;
?>
<div class="container py-5">
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="profile-sidebar">
                <div class="profile-header text-center mb-4">
                    <div class="profile-avatar mb-3">
                        <i class="fas fa-user-circle" style="font-size: 4rem; color: var(--primary-color);"></i>
                    </div>
                    <h3 class="profile-name mt-3"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h3>
                    <p class="profile-email text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>

                <div class="profile-stats">
                    <h5 class="mb-3">Статистика</h5>
                    <div class="stat-item" style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                        <div class="stat-icon">
                            <i class="fas fa-tshirt"></i>
                        </div>
                        <div class="stat-info" style="display: flex; align-items: center; gap: 8px;">
                            <div class="stat-value" style="margin: 0;"><?php echo $stats['total_items'] ?? 0; ?></div>
                            <div class="stat-label" style="margin: 0;">Вещей</div>
                        </div>
                    </div>
                    <div class="stat-item" style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                        <div class="stat-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-info" style="display: flex; align-items: center; gap: 8px;">
                            <div class="stat-value" style="margin: 0;"><?php echo $stats['total_outfits'] ?? 0; ?></div>
                            <div class="stat-label" style="margin: 0;">Образов</div>
                        </div>
                    </div>
                    <div class="stat-item" style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                        <div class="stat-icon">
                            <i class="fas fa-suitcase"></i>
                        </div>
                        <div class="stat-info" style="display: flex; align-items: center; gap: 8px;">
                            <div class="stat-value" style="margin: 0;"><?php echo $stats['total_capsules'] ?? 0; ?></div>
                            <div class="stat-label" style="margin: 0;">Капсул</div>
                        </div>
                    </div>
                </div>

                <div class="profile-info mt-4">
                    <h5 class="mb-3">Информация</h5>
                    <p><strong>Имя пользователя:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                    <p><strong>Зарегистрирован:</strong> <?php echo date('d.m.Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="profile-content">
                <h2 class="mb-4">Редактирование профиля</h2>

                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors['general'] as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/profile" class="profile-form">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email"
                                   class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                   id="email"
                                   name="email"
                                   value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?php foreach ($errors['email'] as $error): ?>
                                        <div><?php echo htmlspecialchars($error); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Имя пользователя</label>
                            <input type="text"
                                   class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>"
                                   id="username"
                                   name="username"
                                   value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>">
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?php foreach ($errors['username'] as $error): ?>
                                        <div><?php echo htmlspecialchars($error); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="full_name" class="form-label">Полное имя</label>
                        <input type="text"
                               class="form-control"
                               id="full_name"
                               name="full_name"
                               value="<?php echo htmlspecialchars($form_data['full_name'] ?? ''); ?>">
                    </div>

                    <hr class="my-4">

                    <h5 class="mb-3">Смена пароля</h5>

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Текущий пароль</label>
                        <input type="password"
                               class="form-control <?php echo isset($errors['current_password']) ? 'is-invalid' : ''; ?>"
                               id="current_password"
                               name="current_password">
                        <?php if (isset($errors['current_password'])): ?>
                            <div class="invalid-feedback d-block">
                                <?php foreach ($errors['current_password'] as $error): ?>
                                    <div><?php echo htmlspecialchars($error); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="new_password" class="form-label">Новый пароль</label>
                            <input type="password"
                                   class="form-control <?php echo isset($errors['new_password']) ? 'is-invalid' : ''; ?>"
                                   id="new_password"
                                   name="new_password">
                            <?php if (isset($errors['new_password'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?php foreach ($errors['new_password'] as $error): ?>
                                        <div><?php echo htmlspecialchars($error); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="form-text">Оставьте пустым, если не хотите менять пароль</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Подтверждение пароля</label>
                            <input type="password"
                                   class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                                   id="confirm_password"
                                   name="confirm_password">
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?php foreach ($errors['confirm_password'] as $error): ?>
                                        <div><?php echo htmlspecialchars($error); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="/" class="btn btn-outline-secondary">Назад</a>
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>