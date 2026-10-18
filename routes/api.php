<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ResponseHelper.php';
require_once dirname(__DIR__) . '/controllers/AuthController.php';
require_once dirname(__DIR__) . '/controllers/OnboardingController.php';
require_once dirname(__DIR__) . '/controllers/ProgressController.php';
require_once dirname(__DIR__) . '/controllers/CurriculumController.php';
require_once dirname(__DIR__) . '/services/CurriculumService.php';
require_once dirname(__DIR__) . '/repositories/CurriculumRepository.php';
require_once dirname(__DIR__) . '/controllers/LearningController.php';
require_once dirname(__DIR__) . '/services/DrillEngineService.php';
require_once dirname(__DIR__) . '/repositories/ExerciseRepository.php';
require_once dirname(__DIR__) . '/services/WritingValidationService.php';
require_once dirname(__DIR__) . '/repositories/WritingRepository.php';
require_once dirname(__DIR__) . '/services/QuizService.php';
require_once dirname(__DIR__) . '/repositories/QuizRepository.php';
require_once dirname(__DIR__) . '/services/ModuleProgressService.php';
require_once dirname(__DIR__) . '/controllers/AdminController.php';
require_once dirname(__DIR__) . '/middleware/AdminMiddleware.php';
require_once dirname(__DIR__) . '/repositories/AdminRepository.php';
require_once dirname(__DIR__) . '/repositories/UserRepository.php';
require_once dirname(__DIR__) . '/repositories/OnboardingRepository.php';
require_once dirname(__DIR__) . '/repositories/ProgressRepository.php';
require_once dirname(__DIR__) . '/services/AuthService.php';
require_once dirname(__DIR__) . '/services/OnboardingService.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';

function dispatchApi(string $method, string $path): void
{
    if ($method === 'GET' && $path === '/api/health') {
        ResponseHelper::jsonResponse(['status' => 'ok']);
        return;
    }

    $authRoutes = [
        'POST /api/auth/register' => 'register',
        'POST /api/auth/login' => 'login',
        'GET /api/auth/me' => 'me',
        'POST /api/auth/logout' => 'logout',
    ];
    $action = $authRoutes[$method . ' ' . $path] ?? null;
    if ($action !== null) {
        $users = UserRepository::fromConfig();
        $controller = new AuthController(new AuthService($users), new AuthMiddleware($users), $users);
        $controller->$action();
        return;
    }

    $onboardingRoutes = [
        'GET /api/languages' => 'languages',
        'GET /api/levels' => 'levels',
        'GET /api/courses' => 'courses',
        'POST /api/user/preferences' => 'savePreferences',
    ];
    $action = $onboardingRoutes[$method . ' ' . $path] ?? null;
    if ($action !== null) {
        $users = UserRepository::fromConfig();
        $controller = new OnboardingController(OnboardingRepository::fromConfig(), new AuthMiddleware($users));
        $controller->$action();
        return;
    }

    if ($method === 'GET' && $path === '/api/progress/summary') {
        $users = UserRepository::fromConfig();
        $controller = new ProgressController(ProgressRepository::fromConfig(), new AuthMiddleware($users));
        $controller->summary();
        return;
    }

    // CURRICULUM ROUTES

    // GET /api/modules
    if ($method === 'GET' && $path === '/api/modules') {
        $users = UserRepository::fromConfig();
        $controller = new CurriculumController(
            new CurriculumService(CurriculumRepository::fromConfig()),
            new AuthMiddleware($users)
        );
        $controller->listModules();
        return;
    }

    // GET /api/modules/:id
    if ($method === 'GET' && preg_match('#^/api/modules/(\d+)$#', $path, $matches) === 1) {
        $users = UserRepository::fromConfig();
        $controller = new CurriculumController(
            new CurriculumService(CurriculumRepository::fromConfig()),
            new AuthMiddleware($users)
        );
        $controller->showModule($matches[1]);
        return;
    }


    // GET /api/lessons/:id/vocabulary
    if ($method === 'GET' && preg_match('#^/api/lessons/(\d+)/vocabulary$#', $path, $matches) === 1) {
    $users = UserRepository::fromConfig();
    $controller = new CurriculumController(
        new CurriculumService(CurriculumRepository::fromConfig()),
        new AuthMiddleware($users)
    );
    $controller->vocabulary($matches[1]);
    return;
    }

    // GET /api/lessons/:id/drills
    if ($method === 'GET' && preg_match('#^/api/lessons/(\d+)/drills$#', $path, $matches) === 1) {
        $users = UserRepository::fromConfig();
        $controller = new LearningController(
            new DrillEngineService(ExerciseRepository::fromConfig()),
            new WritingValidationService(WritingRepository::fromConfig()),
             new QuizService(QuizRepository::fromConfig()),
            new AuthMiddleware($users)
        );
        $controller->drills($matches[1]);
        return;
    }

    // POST /api/drills/:id/answer
    if ($method === 'POST' && preg_match('#^/api/drills/(\d+)/answer$#', $path, $matches) === 1) {
        $users = UserRepository::fromConfig();
        $controller = new LearningController(
            new DrillEngineService(ExerciseRepository::fromConfig()),
            new WritingValidationService(WritingRepository::fromConfig()),
            new QuizService(QuizRepository::fromConfig()), 
            new AuthMiddleware($users)
        );
        $controller->answerDrill($matches[1]);
        return;
    }

    // LEARNING ROUTES — WRITING
    
    // GET /api/lessons/:id/writing
    if ($method === 'GET' && preg_match('#^/api/lessons/(\d+)/writing$#', $path, $matches) === 1) {
        $users = UserRepository::fromConfig();
        $controller = new LearningController(
            new DrillEngineService(ExerciseRepository::fromConfig()),
            new WritingValidationService(WritingRepository::fromConfig()),
            new QuizService(QuizRepository::fromConfig()),
            new AuthMiddleware($users)
        );
        $controller->writing($matches[1]);
        return;
    }


// LEARNING ROUTES — QUIZ

// GET /api/modules/:id/quiz
if ($method === 'GET' && preg_match('#^/api/modules/(\d+)/quiz$#', $path, $matches) === 1) {
    $users = UserRepository::fromConfig();
    $controller = new LearningController(
        new DrillEngineService(ExerciseRepository::fromConfig()),
        new WritingValidationService(WritingRepository::fromConfig()),
        new QuizService(QuizRepository::fromConfig()),
        new AuthMiddleware($users)
    );
    $controller->quiz($matches[1]);
    return;
    }

    // POST /api/quizzes/:id/submit
    if ($method === 'POST' && preg_match('#^/api/quizzes/(\d+)/submit$#', $path, $matches) === 1) {
        $users = UserRepository::fromConfig();
        $controller = new LearningController(
            new DrillEngineService(ExerciseRepository::fromConfig()),
            new WritingValidationService(WritingRepository::fromConfig()),
            new QuizService(QuizRepository::fromConfig()),
            new AuthMiddleware($users)
        );
        $controller->submitQuiz($matches[1]);
        return;
    }


    // POST /api/writing/:id/submit
    if ($method === 'POST' && preg_match('#^/api/writing/(\d+)/submit$#', $path, $matches) === 1) {
        $users = UserRepository::fromConfig();
        $controller = new LearningController(
            new DrillEngineService(ExerciseRepository::fromConfig()),
            new WritingValidationService(WritingRepository::fromConfig()),
            new QuizService(QuizRepository::fromConfig()),
            new AuthMiddleware($users)
        );
        $controller->submitWriting($matches[1]);
        return;
    }

    
    // ADMIN ROUTES
    

    $adminMiddleware = new AdminMiddleware(new AuthMiddleware(UserRepository::fromConfig()));
    $adminController = new AdminController(AdminRepository::fromConfig(), $adminMiddleware);

    // GET /api/admin/dashboard
    if ($method === 'GET' && $path === '/api/admin/dashboard') {
        $adminController->dashboard();
        return;
    }

    // GET /api/admin/modules
    if ($method === 'GET' && $path === '/api/admin/modules') {
        $adminController->listModules();
        return;
    }

    // POST /api/admin/modules
    if ($method === 'POST' && $path === '/api/admin/modules') {
        $adminController->createModule();
        return;
    }

    // GET /api/admin/modules/:id
    if ($method === 'GET' && preg_match('#^/api/admin/modules/(\d+)$#', $path, $matches) === 1) {
        $adminController->showModule($matches[1]);
        return;
    }

    // PUT /api/admin/modules/:id
    if ($method === 'PUT' && preg_match('#^/api/admin/modules/(\d+)$#', $path, $matches) === 1) {
        $adminController->updateModule($matches[1]);
        return;
    }

    // PUT /api/admin/modules/:id/publish
    if ($method === 'PUT' && preg_match('#^/api/admin/modules/(\d+)/publish$#', $path, $matches) === 1) {
        $adminController->publishModule($matches[1]);
        return;
    }

    // PUT /api/admin/modules/:id/draft
    if ($method === 'PUT' && preg_match('#^/api/admin/modules/(\d+)/draft$#', $path, $matches) === 1) {
        $adminController->unpublishModule($matches[1]);
        return;
    }

    // DELETE /api/admin/modules/:id
    if ($method === 'DELETE' && preg_match('#^/api/admin/modules/(\d+)$#', $path, $matches) === 1) {
        $adminController->deleteModule($matches[1]);
        return;
    }

        
    // ADMIN — LESSONS
    

    // GET /api/admin/modules/:id/lessons
    if ($method === 'GET' && preg_match('#^/api/admin/modules/(\d+)/lessons$#', $path, $matches) === 1) {
        $adminController->listLessons($matches[1]);
        return;
    }

    // POST /api/admin/lessons
    if ($method === 'POST' && $path === '/api/admin/lessons') {
        $adminController->createLesson();
        return;
    }

    // PUT /api/admin/lessons/:id
    if ($method === 'PUT' && preg_match('#^/api/admin/lessons/(\d+)$#', $path, $matches) === 1) {
        $adminController->updateLesson($matches[1]);
        return;
    }

    // DELETE /api/admin/lessons/:id
    if ($method === 'DELETE' && preg_match('#^/api/admin/lessons/(\d+)$#', $path, $matches) === 1) {
        $adminController->deleteLesson($matches[1]);
        return;
    }

    
    // ADMIN — VOCABULARIES
    

    // POST /api/admin/vocabularies
    if ($method === 'POST' && $path === '/api/admin/vocabularies') {
        $adminController->createVocabulary();
        return;
    }

    // DELETE /api/admin/vocabularies/:id
    if ($method === 'DELETE' && preg_match('#^/api/admin/vocabularies/(\d+)$#', $path, $matches) === 1) {
        $adminController->deleteVocabulary($matches[1]);
        return;
    }

    
    // ADMIN — EXERCISES
    

    // POST /api/admin/exercises
    if ($method === 'POST' && $path === '/api/admin/exercises') {
        $adminController->createExercise();
        return;
    }

    // DELETE /api/admin/exercises/:id
    if ($method === 'DELETE' && preg_match('#^/api/admin/exercises/(\d+)$#', $path, $matches) === 1) {
        $adminController->deleteExercise($matches[1]);
        return;
    }

    
    // ADMIN — WRITING EXERCISES
    

    // POST /api/admin/writing-exercises
    if ($method === 'POST' && $path === '/api/admin/writing-exercises') {
        $adminController->createWritingExercise();
        return;
    }

    // DELETE /api/admin/writing-exercises/:id
    if ($method === 'DELETE' && preg_match('#^/api/admin/writing-exercises/(\d+)$#', $path, $matches) === 1) {
        $adminController->deleteWritingExercise($matches[1]);
        return;
    }

    
    // ADMIN — QUIZZES
    

    // POST /api/admin/quizzes
    if ($method === 'POST' && $path === '/api/admin/quizzes') {
        $adminController->createQuiz();
        return;
    }

    // DELETE /api/admin/quizzes/:id
    if ($method === 'DELETE' && preg_match('#^/api/admin/quizzes/(\d+)$#', $path, $matches) === 1) {
        $adminController->deleteQuiz($matches[1]);
        return;
    }
    
    ResponseHelper::jsonError('NOT_FOUND', 'Endpoint tidak ditemukan.', 404);
}