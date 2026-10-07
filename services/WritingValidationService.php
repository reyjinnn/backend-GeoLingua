<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/repositories/WritingRepository.php';

final class WritingValidationService
{
    public function __construct(private readonly WritingRepository $repository)
    {
    }

    /**
     * Get writing prompt for a lesson.
     */
    public function promptForLesson(int $lessonId): array
    {
        $exercise = $this->repository->exerciseForLesson($lessonId);
        if ($exercise === null) {
            throw new ApiException(
                'WRITING_NOT_FOUND',
                'Latihan menulis belum tersedia untuk pelajaran ini.',
                404
            );
        }

        return [
            'id' => (int) $exercise['id'],
            'instruction' => (string) $exercise['instruction'],
            'writing_prompt' => (string) $exercise['writing_prompt'],
            'required_vocabulary' => $this->parseRequiredVocabulary($exercise['required_vocabulary']),
            'minimum_words' => (int) $exercise['minimum_words'],
        ];
    }

    /**
     * Validate user text and return result.
     */
    public function validateSubmission(int $userId, int $exerciseId, string $text): array
    {
        $exercise = $this->repository->findById($exerciseId);
        if ($exercise === null) {
            throw new ApiException('WRITING_NOT_FOUND', 'Latihan menulis tidak ditemukan.', 404);
        }

        $requiredVocab = $this->parseRequiredVocabulary($exercise['required_vocabulary']);
        $minimumWords = (int) $exercise['minimum_words'];

        $normalizedText = $this->normalizeText($text);
        $wordCount = $this->countWords($normalizedText);
        $missingWords = $this->findMissingWords($normalizedText, $requiredVocab);

        $passedValidation = $wordCount >= $minimumWords && $missingWords === [];

        $this->repository->saveSubmission(
            $userId,
            $exerciseId,
            $text,
            $wordCount,
            $passedValidation
        );

        return [
            'passed_validation' => $passedValidation,
            'word_count' => $wordCount,
            'missing_required_words' => $missingWords,
            'benchmark_answer' => (string) $exercise['benchmark_answer'],
            'evaluation_guide' => (string) $exercise['evaluation_guide'],
        ];
    }

    /**
     * Parse required vocabulary string into array.
     * Format: "hello,name,live,meet" → ['hello', 'name', 'live', 'meet']
     */
    private function parseRequiredVocabulary(string $raw): array
    {
        return array_values(array_filter(
            array_map('trim', explode(',', $raw)),
            fn($word) => $word !== ''
        ));
    }

    /**
     * Normalize text: lowercase, trim, collapse whitespace.
     */
    private function normalizeText(string $text): string
    {
        return strtolower(trim(preg_replace('/\s+/u', ' ', $text) ?? ''));
    }

    /**
     * Count words in text.
     */
    private function countWords(string $text): int
    {
        if ($text === '') return 0;
        return count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    /**
     * Find required words missing from text.
     */
    private function findMissingWords(string $normalizedText, array $requiredWords): array
    {
        $words = preg_split('/[\s,\.!?;:]+/u', $normalizedText, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $words = array_map(fn($w) => trim($w, ".,!?;:'\"()[]{}"), $words);
        $wordSet = array_flip($words);

        $missing = [];
        foreach ($requiredWords as $required) {
            if (!isset($wordSet[strtolower($required)])) {
                $missing[] = $required;
            }
        }
        return $missing;
    }
}