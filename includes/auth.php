<?php
// includes/auth.php

class Auth {
    private $db;
    
    public function __construct() {
        // Don't start session here - it's handled in bootstrap
        if (!isset($GLOBALS['database'])) {
            $this->db = new Database();
        } else {
            $this->db = $GLOBALS['database'];
        }
    }
    
    public function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }
    
    public function requireLogin(): void {
        if (!$this->isLoggedIn()) {
            header("Location: " . BASE_URL . "/auth/login.php");
            exit();
        }
    }
    
    public function requirePermission(int $level): void {
        $this->requireLogin();
        
        if (!isset($_SESSION['role_level']) || $_SESSION['role_level'] < $level) {
            header("Location: " . BASE_URL . "/auth/unauthorized.php");
            exit();
        }
    }
    
    public function getUserRole(): ?string {
        return $_SESSION['role'] ?? null;
    }

    public function getUserId(): ?int {
        return $_SESSION['user_id'] ?? null;
    }

    public function getUsername(): ?string {
        return $_SESSION['username'] ?? null;
    }

    public function logout(): void {
        session_destroy();
        header("Location: " . BASE_URL . "/auth/login.php");
        exit();
    }
}