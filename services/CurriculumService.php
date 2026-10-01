<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/repositories/CurriculumRepository.php';

final class CurriculumService
{
    public function __construct(private readonly CurriculumRepository $repository)
    {
    }

    public function listModulesForUser(int $userId): array
    {
        $active = $this->repository->activeCourseAndLevel($userId);
        if ($active === null) {
            throw new ApiException(
                'NO_ACTIVE_COURSE',
                'User belum memilih kursus. Selesaikan onboarding dulu.',
                422
            );
        }

        $modules = $this->repository->modulesForCourseLevel(
            (int) $active['course_id'],
            (int) $active['current_level_id']
        );
        $progress = $this->repository->progressForUser($userId);

        $result = [];
        $previousCompleted = true;

        foreach ($modules as $module) {
            $moduleId = (int) $module['id'];
            $userProgress = $progress[$moduleId] ?? null;

            $status = 'locked';
            if ((int) $module['order_index'] === 1 || $previousCompleted) {
                $status = $userProgress['status'] ?? 'unlocked';
            }

            $isCompleted = ($userProgress['status'] ?? '') === 'completed';

            $result[] = [
                'id' => $moduleId,
                'module_code' => $module['module_code'],
                'title' => $module['title'],
                'topic' => $module['topic'],
                'order_index' => (int) $module['order_index'],
                'status' => $status,
                'progress_percentage' => $isCompleted ? 100 : 0,
                'is_completed' => $isCompleted,
            ];

            $previousCompleted = $isCompleted;
        }

        return $result;
    }

    public function moduleWithLessons(int $userId, int $moduleId): array
    {
    $module = $this->repository->moduleDetail($moduleId);
    if ($module === null) {
        throw new ApiException('MODULE_NOT_FOUND', 'Modul tidak ditemukan.', 404);
    }

    if (!$this->repository->isModuleAccessible($userId, $moduleId)) {
        throw new ApiException(
            'MODULE_LOCKED',
            'Modul ini masih terkunci. Selesaikan modul sebelumnya dulu.',
            403
        );
    }

    $lessons = $this->repository->lessonsForModule($moduleId);
    $quiz = $this->repository->quizForModule($moduleId);

    return [
        'id' => (int) $module['id'],
        'module_code' => $module['module_code'],
        'title' => $module['title'],
        'topic' => $module['topic'],
        'description' => $module['description'],
        'learning_objectives' => $module['learning_objectives'],
        'estimated_duration_minutes' => (int) $module['estimated_duration_minutes'],
        'lessons' => array_map(fn($l) => [
            'id' => (int) $l['id'],
            'lesson_name' => $l['lesson_name'],
            'order_index' => (int) $l['order_index'],
            'vocabulary_count' => (int) $l['vocabulary_count'],
        ], $lessons),
        'quiz' => $quiz === null ? null : [
            'id' => (int) $quiz['id'],
            'title' => $quiz['title'],
            'passing_score' => (int) $quiz['passing_score'],
        ],
    ];
}
}