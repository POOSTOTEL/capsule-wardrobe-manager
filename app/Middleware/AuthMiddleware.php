<?php


namespace App\Middleware;

use App\Core\Session;

class AuthMiddleware
{
    protected $session;

    public function __construct()
    {
        $this->session = new Session();
    }

    
    public function handle(): bool
    {
        
        if (!$this->session->get('user_id')) {
            return false;
        }

        
        
        
        

        return true;
    }

    
    public function requireAuth(string $redirectTo = '/login'): void
    {
        if (!$this->handle()) {
            
            if ($redirectTo === '/login') {
                $currentUrl = $_SERVER['REQUEST_URI'] ?? '/';
                header("Location: /login?redirect=" . urlencode($currentUrl));
            } else {
                header("Location: " . $redirectTo);
            }
            exit();
        }
    }

    
    public function requireGuest(string $redirectTo = '/'): void
    {
        if ($this->handle()) {
            header("Location: " . $redirectTo);
            exit();
        }
    }

    
    public function getUserId(): ?int
    {
        return $this->session->get('user_id');
    }

    
    public function getUserData(): ?array
    {
        $userId = $this->getUserId();
        if (!$userId) {
            return null;
        }

        
        
        return [
            'id' => $userId,
            'email' => $this->session->get('user_email'),
            'username' => $this->session->get('user_username'),
            'full_name' => $this->session->get('user_full_name')
        ];
    }

    
    public function hasRole(string $role): bool
    {
        
        
        return $this->handle(); 
    }

    
    public function canAccessResource(int $resourceUserId): bool
    {
        $currentUserId = $this->getUserId();

        
        return $currentUserId && $currentUserId === $resourceUserId;
    }

    
    public function updateLastActivity(): void
    {
        $this->session->set('last_activity', time());
    }

    
    public function checkInactivity(int $timeout = 1800): bool 
    {
        $lastActivity = $this->session->get('last_activity');

        if (!$lastActivity) {
            $this->updateLastActivity();
            return true;
        }

        $inactiveTime = time() - $lastActivity;

        if ($inactiveTime > $timeout) {
            
            $this->session->destroy();
            return false;
        }

        $this->updateLastActivity();
        return true;
    }
}