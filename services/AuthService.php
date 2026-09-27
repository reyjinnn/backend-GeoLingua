<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/repositories/UserRepository.php';

final class AuthService
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function register(array $input): array
    {
        $details = [];
        $name = $input['full_name'] ?? null;
        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;

        if (!is_string($name) || mb_strlen(trim($name)) < 3 || mb_strlen(trim($name)) > 100) {
            $details['full_name'] = 'Nama harus terdiri dari 3–100 karakter.';
        }
        if (!is_string($email) || strlen($email) > 150 || filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false) {
            $details['email'] = 'Format email tidak valid.';
        }
        if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")
            || preg_match('/[A-Za-z]/', $password) !== 1 || preg_match('/[0-9]/', $password) !== 1) {
            $details['password'] = 'Kata sandi harus 8–72 byte dan berisi huruf serta angka.';
        }
        if ($details !== []) {
            throw new ApiException('VALIDATION_FAILED', 'Input yang dikirim tidak valid.', 422, $details);
        }

        $name = trim($name);
        $email = strtolower(trim($email));
        if ($this->users->findByEmail($email) !== null) {
            throw new ApiException('EMAIL_ALREADY_EXISTS', 'Email sudah terdaftar.', 409, ['email' => 'Email sudah terdaftar.']);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
        $token = $this->newToken();
        try {
            $user = $this->users->createLearnerWithToken($name, $email, $hash, $token, $this->expiry());
        } catch (PDOException $error) {
            if ($error->getCode() === '23000') {
                throw new ApiException('EMAIL_ALREADY_EXISTS', 'Email sudah terdaftar.', 409, ['email' => 'Email sudah terdaftar.']);
            }
            throw $error;
        }
        return ['token' => $token, 'user' => $user];
    }

    public function login(array $input): array
    {
        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;
        if (!is_string($email) || !is_string($password) || filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false) {
            throw new ApiException('VALIDATION_FAILED', 'Input yang dikirim tidak valid.', 422);
        }

        $user = $this->users->findByEmail(strtolower(trim($email)));
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            throw new ApiException('INVALID_CREDENTIALS', 'Email atau kata sandi salah.', 401);
        }

        $token = $this->newToken();
        $this->users->insertToken((int) $user['id'], $token, $this->expiry());
        unset($user['password_hash']);
        $user['id'] = (int) $user['id'];
        $user['active_course'] = $this->users->activeCourseForUser($user['id']);
        return ['token' => $token, 'user' => $user];
    }

    private function newToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function expiry(): string
    {
        return gmdate('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60);
    }
}
