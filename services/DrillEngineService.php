<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/repositories/ExerciseRepository.php';

final class DrillEngineService
{
    public function __construct(private readonly ExerciseRepository $repository)
    {
    }

    /**
     * List drills for lesson — format untuk frontend.
     */
    public function listDrillsForLesson(int $lessonId): array
    {
        $exercises = $this->repository->drillsForLesson($lessonId);
        if ($exercises === []) return [];

        $exerciseIds = array_map(fn($e) => (int) $e['id'], $exercises);
        $optionsByExercise = $this->repository->optionsForExercises($exerciseIds);

        return array_map(function ($exercise) use ($optionsByExercise) {
            $exerciseId = (int) $exercise['id'];
            $type = $exercise['question_type'];
            $result = [
                'id' => $exerciseId,
                'question_type' => $type,
                'prompt' => $exercise['prompt'],
            ];

            if ($type === 'mcq_meaning' || $type === 'mcq_word') {
                $result['options'] = $optionsByExercise[$exerciseId] ?? [];
            }

            if ($type === 'typing' || $type === 'fill_blank') {
                $result['placeholder'] = 'Ketik jawaban di sini...';
            }

            return $result;
        }, $exercises);
    }

    /**
     * Evaluate user answer.
     */
    public function evaluateAnswer(int $exerciseId, string $answer): array
    {
        $exercise = $this->repository->findById($exerciseId);
        if ($exercise === null) {
            throw new ApiException('DRILL_NOT_FOUND', 'Soal drill tidak ditemukan.', 404);
        }

        $type = $exercise['question_type'];
        $correctAnswer = (string) $exercise['correct_answer'];
        $isCorrect = false;

        if ($type === 'mcq_meaning' || $type === 'mcq_word') {
            $correctOptionId = $this->repository->correctOptionId($exerciseId);
            $isCorrect = ctype_digit($answer) && (int) $answer === $correctOptionId;
        }

        if ($type === 'typing' || $type === 'fill_blank') {
            $normalizedUser = strtolower(trim($answer));
            $normalizedCorrect = strtolower(trim($correctAnswer));
            $isCorrect = $normalizedUser === $normalizedCorrect;
        }

        return [
            'is_correct' => $isCorrect,
            'correct_answer' => $correctAnswer,
            'explanation' => (string) ($exercise['explanation'] ?? ''),
        ];
    }
}