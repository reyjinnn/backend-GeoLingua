<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ResponseHelper.php';
require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/repositories/WritingRepository.php';
require_once dirname(__DIR__) . '/repositories/CurriculumRepository.php';

require_once dirname(__DIR__) . '/repositories/UserRepository.php';

final class WritingController
{
    private const MAX_TEXT_LENGTH = 5000;

    public function __construct(
        private readonly WritingRepository $writing,
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

    public function getWriting(int $lessonId): void
    {
        $user = $this->getAuthenticatedUser();
        $course = $this->getActiveCourse($user['id']);
        
        $lesson = $this->curriculum->getLessonById($lessonId, $user['id']);
        if (!$lesson || (int)$lesson['course_id'] !== (int)$course['id'] || (int)$lesson['level_id'] !== (int)$course['current_level_id'] || $lesson['module_status'] !== 'published') {
            throw new ApiException('NOT_FOUND', 'Lesson tidak ditemukan atau tidak dapat diakses.', 404);
        }
        if ($lesson['progress_status'] === 'locked') {
            throw new ApiException('FORBIDDEN', 'Lesson terkunci. Selesaikan prasyarat terlebih dahulu.', 403);
        }
        
        $exercise = $this->writing->getExerciseForLesson($lessonId);
        if (!$exercise) {
            throw new ApiException('NOT_FOUND', 'Latihan menulis tidak ditemukan untuk lesson ini.', 404);
        }

        // Hide benchmark_answer and evaluation_guide from the GET request
        unset($exercise['benchmark_answer'], $exercise['evaluation_guide']);
        
        // Convert required_vocabulary to array
        $exercise['required_vocabulary'] = $this->parseRequiredWords($exercise['required_vocabulary']);
        
        ResponseHelper::jsonResponse([
            'writing' => $exercise,
            'prompt' => $exercise
        ]);
    }

    public function submitWriting(int $exerciseId): void
    {
        $user = $this->getAuthenticatedUser();
        $course = $this->getActiveCourse($user['id']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || !isset($input['text'])) {
            throw new ApiException('BAD_REQUEST', 'Teks wajib diisi.', 400);
        }
        
        $submittedText = trim((string) $input['text']);
        if (mb_strlen($submittedText) > self::MAX_TEXT_LENGTH) {
            throw new ApiException('BAD_REQUEST', 'Teks terlalu panjang (maksimum ' . self::MAX_TEXT_LENGTH . ' karakter).', 400);
        }
        
        $exercise = $this->writing->getExerciseById($exerciseId);
        if (!$exercise) {
            throw new ApiException('NOT_FOUND', 'Soal tidak ditemukan.', 404);
        }
        
        // Ensure access to the lesson matching active course and level
        $lesson = $this->curriculum->getLessonById($exercise['lesson_id'], $user['id']);
        if (!$lesson || (int)$lesson['course_id'] !== (int)$course['id'] || (int)$lesson['level_id'] !== (int)$course['current_level_id'] || $lesson['module_status'] !== 'published' || $lesson['progress_status'] === 'locked') {
            throw new ApiException('FORBIDDEN', 'Anda tidak memiliki akses ke soal ini.', 403);
        }
        
        $wordCount = $this->countWords($submittedText);
        $requiredWords = $this->parseRequiredWords($exercise['required_vocabulary']);
        $missingWords = $this->findMissingWords($submittedText, $requiredWords);
        
        $passedValidation = ($wordCount >= $exercise['minimum_words']) && (count($missingWords) === 0);
        
        // Save submission
        $this->writing->saveSubmission($user['id'], $exerciseId, $submittedText, $wordCount, $passedValidation);
        
        // Return results along with benchmark and guide
        ResponseHelper::jsonResponse([
            'passed_validation' => $passedValidation,
            'word_count' => $wordCount,
            'missing_required_words' => $missingWords,
            'benchmark_answer' => $exercise['benchmark_answer'],
            'evaluation_guide' => $exercise['evaluation_guide']
        ]);
    }
    
    private function parseRequiredWords(string $csv): array
    {
        if (trim($csv) === '') return [];
        return array_map('trim', explode(',', $csv));
    }
    
    private function countWords(string $text): int
    {
        // Remove punctuation and normalize spaces
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $text = trim(preg_replace('/\s+/', ' ', $text));
        return $text === '' ? 0 : count(explode(' ', $text));
    }
    
    private function findMissingWords(string $text, array $requiredWords): array
    {
        $missing = [];
        // Punctuation characters to treat as word boundaries or just replace with space
        $cleanText = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text));
        $cleanText = ' ' . trim(preg_replace('/\s+/', ' ', $cleanText)) . ' ';
        
        foreach ($requiredWords as $word) {
            if ($word === '') continue;
            
            $cleanWord = mb_strtolower($word);
            // Check for exact word match with spaces
            // This properly handles multi-word required phrases too
            if (mb_strpos($cleanText, ' ' . $cleanWord . ' ') === false) {
                $missing[] = $word;
            }
        }
        return $missing;
    }
}
