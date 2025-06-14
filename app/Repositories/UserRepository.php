<?php

namespace App\Repositories;
require_once __DIR__ . '/../Models/User.php';

use App\Models\User;
use PDO;

class UserRepository implements IUserRepository {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function createUser($username, $hashedPassword, $email, $role) {
        $stmt = $this->db->prepare("INSERT INTO users (username, password, email, role, created_at) 
                                  VALUES (:username, :password, :email, :role, NOW())");
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':password', $hashedPassword);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':role', $role);
        

        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function getUserByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindValue(':username', $username);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            return new User(
                $row['id'],
                $row['username'],
                $row['password'],
                $row['email'],
                $row['created_at'],
                $row['role']
            );
        }
        return null;
    }
    
    public function getAllUsers() {
        $stmt = $this->db->prepare("SELECT * FROM users");
        $stmt->execute();
        $usersData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $users = [];
        foreach ($usersData as $userData) {
            $users[] = new User(
                $userData['id'],
                $userData['username'],
                $userData['password'],
                $userData['email'],
                $userData['created_at'],
                $userData['role']
            );
        }
        return $users;
    }
    
    public function getUserCount() {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
     public function getUserById(int $id): ?User
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                return new User(
                    $user['id'],
                    $user['username'],
                    $user['password'],
                    $user['email'],
                    $user['created_at'],
                    $user['role']
                );
            }
            return null;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }
}
