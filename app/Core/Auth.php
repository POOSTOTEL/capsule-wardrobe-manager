<?php
namespace App\Core;

class Auth {
    private Database $db;
    public function login(string $email, string $password): bool;
    public function logout(): void;
    public function isLoggedIn(): bool;
    public function getCurrentUser(): ?User;
    public function register(array $userData): bool;
}