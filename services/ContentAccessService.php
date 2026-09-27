<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/repositories/UserRepository.php';
require_once dirname(__DIR__) . '/repositories/CurriculumRepository.php';

final class ContentAccessService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly CurriculumRepository $curriculum
    ) {
    }

    public function getActiveCourse(int $userId): array
    {
        $course = $this->users->activeCourseForUser($userId);
        if (!$course) {
            throw new ApiException('FORBIDDEN', 'Silakan selesaikan onboarding terlebih dahulu.', 403);
        }
        return $course;
    }

    public function ensureModuleAccess(int $moduleId, int $userId): array
    {
        $course = $this->getActiveCourse($userId);
        $module = $this->curriculum->getModuleById($moduleId, $userId, (int) $course['id'], (int) $course['current_level_id']);
        
        if (!$module || ($module['module_status'] ?? $module['status'] ?? '') !== 'published') {
            throw new ApiException('NOT_FOUND', 'Modul tidak ditemukan atau tidak dapat diakses.', 404);
        }
        
        if (($module['progress_status'] ?? '') === 'locked' || ($module['status'] ?? '') === 'locked') {
            throw new ApiException('FORBIDDEN', 'Modul terkunci. Selesaikan prasyarat terlebih dahulu.', 403);
        }

        $module['active_course'] = $course;
        return $module;
    }

    public function ensureLessonAccess(int $lessonId, int $userId): array
    {
        $course = $this->getActiveCourse($userId);
        $lesson = $this->curriculum->getLessonById($lessonId, $userId);

        if (!$lesson 
            || (int) $lesson['course_id'] !== (int) $course['id'] 
            || (int) $lesson['level_id'] !== (int) $course['current_level_id'] 
            || ($lesson['module_status'] ?? '') !== 'published') {
            throw new ApiException('NOT_FOUND', 'Lesson tidak ditemukan atau tidak dapat diakses.', 404);
        }

        if (($lesson['progress_status'] ?? '') === 'locked') {
            throw new ApiException('FORBIDDEN', 'Lesson terkunci. Selesaikan prasyarat modul terlebih dahulu.', 403);
        }

        $lesson['active_course'] = $course;
        return $lesson;
    }
}
