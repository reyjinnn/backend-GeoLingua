<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/repositories/OnboardingRepository.php';

final class OnboardingService
{
    public function __construct(private readonly OnboardingRepository $repository)
    {
    }

    public function save(int $userId, array $input): array
    {
        $base = $input['base_language_code'] ?? null;
        $target = $input['target_language_code'] ?? null;
        $level = $input['level_code'] ?? null;
        $details = [];
        foreach (['base_language_code' => $base, 'target_language_code' => $target] as $field => $value) {
            if (!is_string($value) || preg_match('/^[a-z]{2,10}$/', strtolower(trim($value))) !== 1) {
                $details[$field] = 'Kode bahasa tidak valid.';
            }
        }
        if (!is_string($level) || preg_match('/^[A-Z][0-9]$/', strtoupper(trim($level))) !== 1) {
            $details['level_code'] = 'Kode level tidak valid.';
        }
        if ($details !== []) {
            throw new ApiException('VALIDATION_FAILED', 'Input yang dikirim tidak valid.', 422, $details);
        }

        $base = strtolower(trim($base));
        $target = strtolower(trim($target));
        $level = strtoupper(trim($level));
        if ($base === $target) {
            throw new ApiException('VALIDATION_FAILED', 'Bahasa pengantar dan sasaran harus berbeda.', 422);
        }
        $courseId = $this->repository->courseId($base, $target);
        if ($courseId === null) {
            throw new ApiException('COURSE_UNAVAILABLE', 'Pasangan bahasa belum tersedia.', 422);
        }
        $levelId = $this->repository->levelId($level);
        if ($levelId === null) {
            throw new ApiException('LEVEL_UNAVAILABLE', 'Level belajar belum tersedia.', 422);
        }

        $this->repository->savePreferences($userId, $courseId, $levelId);
        return ['message' => 'Preferensi belajar berhasil disimpan.'];
    }
}
