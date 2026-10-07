<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/repositories/QuizRepository.php';

final class QuizService
{
    public function __construct(private readonly QuizRepository $repository)
    {
    }

    /**
     * Get quiz for a module (soal tanpa kunci jawaban).
     */
    public function quizForModule(int $moduleId): array
    {
        $quiz = $this->repository->quizForModule($moduleId);
        if ($quiz === null) {
            throw new ApiException('QUIZ_NOT_FOUND', 'Kuis untuk modul ini belum tersedia.', 404);
        }

        $questions = $this->repository->questionsForQuiz((int) $quiz['id']);

        return [
            'quiz_id' => (int) $quiz['id'],
            'title' => (string) $quiz['title'],
            'passing_score' => (int) $quiz['passing_score'],
            'questions' => array_map(function ($q) {
                return [
                    'id' => (int) $q['id'],
                    'question_text' => (string) $q['question_text'],
                    'options' => array_map(fn($opt) => [
                        'id' => (int) $opt['id'],
                        'text' => (string) $opt['text'],
                    ], $q['options']),
                ];
            }, $questions),
        ];
    }

    /**
     * Submit quiz answers and calculate score.
     */
    public function submitQuiz(int $userId, int $quizId, array $answers, int $durationSeconds): array
    {
        $quiz = $this->repository->findById($quizId);
        if ($quiz === null) {
            throw new ApiException('QUIZ_NOT_FOUND', 'Kuis tidak ditemukan.', 404);
        }

        $questions = $this->repository->questionsForQuiz($quizId);
        $totalWeight = 0;
        $earnedWeight = 0;

        foreach ($questions as $question) {
            $questionId = (int) $question['id'];
            $weight = (int) $question['score_weight'];
            $totalWeight += $weight;

            $selectedOptionId = $this->findAnswerForQuestion($answers, $questionId);
            if ($selectedOptionId === null) continue;

            $correctOptionId = $this->repository->correctOptionId($questionId);
            if ($correctOptionId !== null && $selectedOptionId === $correctOptionId) {
                $earnedWeight += $weight;
            }
        }

        $score = $totalWeight > 0 ? (int) round(($earnedWeight / $totalWeight) * 100) : 0;
        $passingScore = (int) $quiz['passing_score'];
        $isPassed = $score >= $passingScore;

        $this->repository->saveAttempt($userId, $quizId, $score, $isPassed, $durationSeconds);

        return [
            'score' => $score,
            'passing_score' => $passingScore,
            'is_passed' => $isPassed,
        ];
    }

    private function findAnswerForQuestion(array $answers, int $questionId): ?int
    {
        foreach ($answers as $answer) {
            if (isset($answer['question_id']) && (int) $answer['question_id'] === $questionId) {
                return isset($answer['selected_option_id']) ? (int) $answer['selected_option_id'] : null;
            }
        }
        return null;
    }
}