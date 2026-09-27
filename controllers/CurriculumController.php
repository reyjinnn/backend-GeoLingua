<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ResponseHelper.php';
require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/repositories/CurriculumRepository.php';
require_once dirname(__DIR__) . '/repositories/UserRepository.php';

final class CurriculumController
{
    public function __construct(
        private readonly CurriculumRepository $curriculum,
        private readonly UserRepository $users,
        private readonly AuthMiddleware $auth
    ) {
    }

    private function getAuthenticatedUser(): array
    {
        $auth = $this->auth->authenticate(AuthMiddleware::authorizationHeader());
        return $auth['user'];
    }

    private function getActiveCourse(int $userId): array
    {
        $course = $this->users->activeCourseForUser($userId);
        if (!$course) {
            throw new ApiException('FORBIDDEN', 'Silakan selesaikan onboarding terlebih dahulu.', 403);
        }
        return $course;
    }

    public function listModules(): void
    {
        $user = $this->getAuthenticatedUser();
        $course = $this->getActiveCourse($user['id']);
        
        $modules = $this->curriculum->getModulesForCourseAndLevel(
            $course['id'],
            $course['current_level_id'],
            $user['id']
        );
        
        ResponseHelper::jsonResponse(['modules' => $modules]);
    }

    public function getModule(int $moduleId): void
    {
        $user = $this->getAuthenticatedUser();
        $course = $this->getActiveCourse($user['id']);
        
        $module = $this->curriculum->getModuleById($moduleId, $user['id'], $course['id'], $course['current_level_id']);
        
        if (!$module) {
            throw new ApiException('NOT_FOUND', 'Modul tidak ditemukan atau tidak dapat diakses.', 404);
        }
        
        if ($module['progress_status'] === 'locked') {
            throw new ApiException('FORBIDDEN', 'Modul terkunci. Selesaikan prasyarat terlebih dahulu.', 403);
        }
        
        ResponseHelper::jsonResponse(['module' => $module]);
    }

    public function getLesson(int $lessonId): void
    {
        $user = $this->getAuthenticatedUser();
        $course = $this->getActiveCourse($user['id']);
        
        $lesson = $this->curriculum->getLessonById($lessonId, $user['id']);
        
        if (!$lesson || (int)$lesson['course_id'] !== (int)$course['id'] || (int)$lesson['level_id'] !== (int)$course['current_level_id'] || $lesson['module_status'] !== 'published') {
            throw new ApiException('NOT_FOUND', 'Lesson tidak ditemukan atau tidak dapat diakses.', 404);
        }
        
        if ($lesson['progress_status'] === 'locked') {
            throw new ApiException('FORBIDDEN', 'Lesson terkunci. Selesaikan prasyarat modul terlebih dahulu.', 403);
        }
        
        // Remove internal fields before returning
        unset($lesson['course_id'], $lesson['level_id'], $lesson['module_status'], $lesson['prerequisite_module_id'], $lesson['progress_status']);
        
        ResponseHelper::jsonResponse(['lesson' => $lesson]);
    }

    public function getLessonVocabulary(int $lessonId): void
    {
        $user = $this->getAuthenticatedUser();
        $course = $this->getActiveCourse($user['id']);
        
        $lesson = $this->curriculum->getLessonById($lessonId, $user['id']);
        
        if (!$lesson || (int)$lesson['course_id'] !== (int)$course['id'] || (int)$lesson['level_id'] !== (int)$course['current_level_id'] || $lesson['module_status'] !== 'published') {
            throw new ApiException('NOT_FOUND', 'Lesson tidak ditemukan atau tidak dapat diakses.', 404);
        }
        
        if ($lesson['progress_status'] === 'locked') {
            throw new ApiException('FORBIDDEN', 'Lesson terkunci. Selesaikan prasyarat modul terlebih dahulu.', 403);
        }
        
        $vocabularies = $this->curriculum->getVocabularyForLesson($lessonId, (int)$course['base_language_id']);
        ResponseHelper::jsonResponse(['vocabularies' => $vocabularies]);
    }
}
