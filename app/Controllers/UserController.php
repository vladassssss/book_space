<?php

namespace App\Controllers;

use App\Services\UserService;

class UserController {
    private $userService;

    public function __construct(UserService $userService) {
        $this->userService = $userService;
    }

    public function register($username, $password, $email, $role) {
        $userId = $this->userService->registerUser($username, $password, $email, $role);
        return $userId;
    }

    public function login($username, $password) {
        $user = $this->userService->loginUser($username, $password);
        return $user;
    }

    public function getAllUsers() {
        return $this->userService->getAllUsers();
    }
    public function getUserCount() {
        return $this->userService->getUserCount();
    }
}