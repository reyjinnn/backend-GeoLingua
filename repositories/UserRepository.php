<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

final class UserRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public static function fromConfig(): self
    {
        return new self(databaseConnection());
    }

    public function findByEmail(string $email): ?array
    {
        $query = $this->db->prepare(
            'SELECT u.id, u.full_name, u.email, u.password_hash, r.name AS role
             FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = :email LIMIT 1'
        );
        $query->execute(['email' => $email]);
        return $query->fetch() ?: null;
    }

    public function createLearnerWithToken(string $name, string $email, string $passwordHash, string $token, string $expiresAt): array
    {
        $this->db->beginTransaction();
        try {
            $roleQuery = $this->db->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
            $roleQuery->execute(['name' => 'learner']);
            $roleId = $roleQuery->fetchColumn();
            if ($roleId === false) {
                throw new RuntimeException('The learner role must be seeded before registration.');
            }

            $query = $this->db->prepare(
                'INSERT INTO users (role_id, full_name, email, password_hash) VALUES (:role_id, :full_name, :email, :password_hash)'
            );
            $query->execute(['role_id' => $roleId, 'full_name' => $name, 'email' => $email, 'password_hash' => $passwordHash]);
            $userId = (int) $this->db->lastInsertId();
            $this->insertToken($userId, $token, $expiresAt);
            $this->db->commit();
            return ['id' => $userId, 'full_name' => $name, 'email' => $email, 'role' => 'learner'];
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function insertToken(int $userId, string $token, string $expiresAt): void
    {
        $query = $this->db->prepare(
            'INSERT INTO user_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)'
        );
        $query->execute(['user_id' => $userId, 'token' => $token, 'expires_at' => $expiresAt]);
    }

    public function findByValidToken(string $token, string $now): ?array
    {
        $query = $this->db->prepare(
            'SELECT u.id, u.full_name, u.email, r.name AS role
             FROM user_tokens t JOIN users u ON u.id = t.user_id JOIN roles r ON r.id = u.role_id
             WHERE t.token = :token AND t.expires_at > :now LIMIT 1'
        );
        $query->execute(['token' => $token, 'now' => $now]);
        return $query->fetch() ?: null;
    }

    public function revokeToken(string $token): void
    {
        $query = $this->db->prepare('DELETE FROM user_tokens WHERE token = :token');
        $query->execute(['token' => $token]);
    }

    public function activeCourseForUser(int $userId): ?array
    {
        $query = $this->db->prepare(
            'SELECT c.id, base.code AS base_language, target.code AS target_language, l.code AS current_level
             FROM user_learning_languages choice
             JOIN courses c ON c.id = choice.course_id
             JOIN languages base ON base.id = c.base_language_id
             JOIN languages target ON target.id = c.target_language_id
             JOIN levels l ON l.id = choice.current_level_id
             WHERE choice.user_id = :user_id AND choice.is_primary = 1 ORDER BY choice.id DESC LIMIT 1'
        );
        $query->execute(['user_id' => $userId]);
        $course = $query->fetch();
        if ($course === false) {
            return null;
        }
        $course['id'] = (int) $course['id'];
        return $course;
    }
}
