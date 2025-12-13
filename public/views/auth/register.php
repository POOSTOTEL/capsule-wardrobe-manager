<?php
// public/views/auth/register.php
$errors = $errors ?? [];
$form_data = $form_data ?? [];
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header text-center">
                    <h1 class="h3 mb-0">Регистрация</h1>
                    <p class="text-muted mb-0">Создайте аккаунт для управления вашим гардеробом</p>
                </div>

                <div class="card-body">
                    <?php if (isset($errors['general'])): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors['general'] as $error): ?>
                                <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/register" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email"
                                   class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                   id="email"
                                   name="email"
                                   value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>"
                                   required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?php foreach ($errors['email'] as $error): ?>
                                        <div><?php echo htmlspecialchars($error); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Имя пользователя *</label>
                            <input type="text"
                                   class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>"
                                   id="username"
                                   name="username"
                                   value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>"
                                   required>
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?php foreach ($errors['username'] as $error): ?>
                                        <div><?php echo htmlspecialchars($error); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="full_name" class="form-label">Полное имя</label>
                            <input type="text"
                                   class="form-control"
                                   id="full_name"
                                   name="full_name"
                                   value="<?php echo htmlspecialchars($form_data['full_name'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Пароль *</label>
                            <input type="password"
                                   class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                   id="password"
                                   name="password"
                                   required>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?php foreach ($errors['password'] as $error): ?>
                                        <div><?php echo htmlspecialchars($error); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="form-text">Пароль должен содержать не менее 6 символов</div>
                        </div>

                        <div class="mb-4">
                            <label for="password_confirm" class="form-label">Подтверждение пароля *</label>
                            <input type="password"
                                   class="form-control <?php echo isset($errors['password_confirm']) ? 'is-invalid' : ''; ?>"
                                   id="password_confirm"
                                   name="password_confirm"
                                   required>
                            <?php if (isset($errors['password_confirm'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?php foreach ($errors['password_confirm'] as $error): ?>
                                        <div><?php echo htmlspecialchars($error); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">Зарегистрироваться</button>
                        </div>

                        <div class="text-center">
                            <p class="mb-0">Уже есть аккаунт? <a href="/login" class="text-decoration-none">Войти</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .container.py-5 {
        min-height: calc(100vh - 200px);
        display: flex;
        align-items: center;
    }

    .card {
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .card-header {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        border-bottom: none;
        padding: 2rem 1rem;
    }

    .card-body {
        padding: 2rem;
    }

    .btn-lg {
        padding: 12px 24px;
        font-size: 1.1rem;
    }
</style>