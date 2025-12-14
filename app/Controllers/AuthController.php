<?php


namespace App\Controllers;

use App\Models\User;
use App\Core\Session;
use App\Middleware\AuthMiddleware;

class AuthController extends Controller
{
    protected $userModel;
    protected $session;

    public function __construct()
    {
        $this->userModel = new User();
        $this->session = new Session();

        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    
    public function showRegister(): void
    {
        
        if ($this->session->get('user_id')) {
            $this->redirect('/');
            return;
        }

        $data = [
            'title' => 'Регистрация - Капсульный Гардероб',
            'errors' => []
        ];

        $this->render('auth/register', $data);
    }

    
    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/register');
            return;
        }

        $data = [
            'email' => trim($_POST['email'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? ''
        ];

        
        $errors = $this->validateRegistration($data);

        if (!empty($errors)) {
            $this->render('auth/register', [
                'title' => 'Регистрация - Капсульный Гардероб',
                'errors' => $errors,
                'form_data' => $data
            ]);
            return;
        }

        
        $userId = $this->userModel->register($data);

        if (!$userId) {
            $errors['email'] = ['Пользователь с таким email уже существует'];
            $this->render('auth/register', [
                'title' => 'Регистрация - Капсульный Гардероб',
                'errors' => $errors,
                'form_data' => $data
            ]);
            return;
        }

        
        $user = $this->userModel->findById($userId);
        if ($user) {
            $this->session->set('user_id', $user['id']);
            $this->session->set('user_email', $user['email']);
            $this->session->set('user_username', $user['username']);

            
            $this->session->setFlash('success', 'Регистрация прошла успешно! Добро пожаловать в Капсульный Гардероб.');
        }

        $this->redirect('/');
    }

    
    public function showLogin(): void
    {
        
        if ($this->session->get('user_id')) {
            $this->redirect('/');
            return;
        }

        $data = [
            'title' => 'Вход - Капсульный Гардероб',
            'errors' => []
        ];

        $this->render('auth/login', $data);
    }

    
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login');
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        
        $errors = [];

        if (empty($email)) {
            $errors['email'] = ['Введите email'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = ['Введите корректный email'];
        }

        if (empty($password)) {
            $errors['password'] = ['Введите пароль'];
        }

        if (!empty($errors)) {
            $this->render('auth/login', [
                'title' => 'Вход - Капсульный Гардероб',
                'errors' => $errors,
                'email' => $email
            ]);
            return;
        }

        
        $user = $this->userModel->verifyCredentials($email, $password);

        if (!$user) {
            $errors['general'] = ['Неверный email или пароль'];
            $this->render('auth/login', [
                'title' => 'Вход - Капсульный Гардероб',
                'errors' => $errors,
                'email' => $email
            ]);
            return;
        }

        
        $this->session->set('user_id', $user['id']);
        $this->session->set('user_email', $user['email']);
        $this->session->set('user_username', $user['username']);
        $this->session->set('user_full_name', $user['full_name'] ?? '');

        
        $this->session->setFlash('success', 'Добро пожаловать, ' . ($user['username'] ?? $user['email']) . '!');

        
        $redirect = $_GET['redirect'] ?? '/';
        $this->redirect($redirect);
    }

    
    public function logout(): void
    {
        
        $this->session->destroy();

        
        session_start();
        $_SESSION['flash']['info'][] = 'Вы успешно вышли из системы.';
        session_write_close();

        $this->redirect('/');
    }

    
    public function showProfile(): void
    {
        
        $authMiddleware = new AuthMiddleware();
        if (!$authMiddleware->handle()) {
            $this->redirect('/login?redirect=/profile');
            return;
        }

        $userId = $this->session->get('user_id');
        $user = $this->userModel->findById($userId);

        if (!$user) {
            $this->session->destroy();
            $this->redirect('/login');
            return;
        }

        $data = [
            'title' => 'Профиль - Капсульный Гардероб',
            'user' => $user,
            'stats' => $this->userModel->getStats($userId),
            'errors' => []
        ];

        $this->render('auth/profile', $data);
    }

    
    public function updateProfile(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/profile');
            return;
        }

        
        $authMiddleware = new AuthMiddleware();
        if (!$authMiddleware->handle()) {
            $this->redirect('/login');
            return;
        }

        $userId = $this->session->get('user_id');

        $data = [
            'email' => trim($_POST['email'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'current_password' => $_POST['current_password'] ?? '',
            'new_password' => $_POST['new_password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? ''
        ];

        
        $errors = $this->validateProfileUpdate($data, $userId);

        if (!empty($errors)) {
            $user = $this->userModel->findById($userId);
            $this->render('auth/profile', [
                'title' => 'Профиль - Капсульный Гардероб',
                'user' => $user,
                'stats' => $this->userModel->getStats($userId),
                'errors' => $errors,
                'form_data' => $data
            ]);
            return;
        }

        
        $updateData = [];
        if (!empty($data['email'])) $updateData['email'] = $data['email'];
        if (!empty($data['username'])) $updateData['username'] = $data['username'];
        if (isset($data['full_name'])) $updateData['full_name'] = $data['full_name'];
        if (!empty($data['new_password'])) $updateData['password'] = $data['new_password'];

        
        $success = $this->userModel->updateProfile($userId, $updateData);

        if ($success) {
            
            if (!empty($data['email'])) {
                $this->session->set('user_email', $data['email']);
            }
            if (!empty($data['username'])) {
                $this->session->set('user_username', $data['username']);
            }
            if (isset($data['full_name'])) {
                $this->session->set('user_full_name', $data['full_name']);
            }

            $this->session->setFlash('success', 'Профиль успешно обновлен!');
        } else {
            $this->session->setFlash('error', 'Ошибка при обновлении профиля. Возможно, email уже используется.');
        }

        $this->redirect('/profile');
    }

    
    private function validateRegistration(array $data): array
    {
        $errors = [];

        
        if (empty($data['email'])) {
            $errors['email'] = ['Введите email'];
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = ['Введите корректный email'];
        }

        
        if (empty($data['username'])) {
            $errors['username'] = ['Введите имя пользователя'];
        } elseif (strlen($data['username']) < 3) {
            $errors['username'] = ['Имя пользователя должно быть не менее 3 символов'];
        } elseif (strlen($data['username']) > 50) {
            $errors['username'] = ['Имя пользователя должно быть не более 50 символов'];
        }

        
        if (empty($data['password'])) {
            $errors['password'] = ['Введите пароль'];
        } elseif (strlen($data['password']) < 6) {
            $errors['password'] = ['Пароль должен быть не менее 6 символов'];
        }

        
        if ($data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = ['Пароли не совпадают'];
        }

        return $errors;
    }

    
    private function validateProfileUpdate(array $data, int $userId): array
    {
        $errors = [];
        $user = $this->userModel->findById($userId);

        
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = ['Введите корректный email'];
            } elseif ($data['email'] !== $user['email']) {
                
                $existing = $this->userModel->findByEmail($data['email']);
                if ($existing && $existing['id'] != $userId) {
                    $errors['email'] = ['Этот email уже используется другим пользователем'];
                }
            }
        }

        
        if (!empty($data['username'])) {
            if (strlen($data['username']) < 3) {
                $errors['username'] = ['Имя пользователя должно быть не менее 3 символов'];
            } elseif (strlen($data['username']) > 50) {
                $errors['username'] = ['Имя пользователя должно быть не более 50 символов'];
            }
        }

        
        if (!empty($data['new_password'])) {
            if (empty($data['current_password'])) {
                $errors['current_password'] = ['Введите текущий пароль для смены пароля'];
            } else {
                
                if (!$this->userModel->verifyCredentials($user['email'], $data['current_password'])) {
                    $errors['current_password'] = ['Неверный текущий пароль'];
                }
            }

            if (strlen($data['new_password']) < 6) {
                $errors['new_password'] = ['Новый пароль должен быть не менее 6 символов'];
            }

            if ($data['new_password'] !== $data['confirm_password']) {
                $errors['confirm_password'] = ['Пароли не совпадают'];
            }
        }

        
        if (!empty($data['current_password']) && empty($data['new_password'])) {
            $errors['new_password'] = ['Введите новый пароль'];
        }

        return $errors;
    }

    
    public static function checkAuth(): bool
    {
        $session = new Session();
        return !empty($session->get('user_id'));
    }

    
    public static function getCurrentUserId(): ?int
    {
        $session = new Session();
        return $session->get('user_id');
    }
}