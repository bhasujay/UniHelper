<?php

namespace app\controllers;

require_once dirname(__DIR__) . '/models/base-model.php';
require_once dirname(__DIR__) . '/models/ZScore_model.php';
require_once dirname(__DIR__) . '/models/UnicodePreferenceModel.php';

use app\core\Request;
use app\models\ZScoreModel;
use app\models\UnicodePreferenceModel;

/**
 * Unicode preference generator API.
 * Uses likely/very_likely eligibility to build a UGC-style preference list.
 */
class UnicodeGeneratorController
{
    private $zScoreModel;
    private $preferenceModel;

    public function __construct() {
        $this->zScoreModel = new ZScoreModel();
        $this->preferenceModel = new UnicodePreferenceModel();
    }

    /**
     * GET /api?controller=UnicodeGeneratorController&action=getPreferencePrograms
     */
    public function getPreferencePrograms(Request $request) {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }

            $userId = (int) $_SESSION['user_id'];
            $eligiblePrograms = $this->getLikelyEligibleProgramsForUser($userId);
            $savedOrderMap = $this->preferenceModel->getUserPreferenceOrderMap($userId);
            $orderedPrograms = $this->mergeWithSavedOrder($eligiblePrograms, $savedOrderMap);

            $selectedProgram = !empty($orderedPrograms) ? $orderedPrograms[0] : null;

            $this->sendJsonResponse(true, 'Preference programs generated successfully', [
                'programs' => $orderedPrograms,
                'selected_program' => $selectedProgram,
                'has_saved_order' => !empty($savedOrderMap),
                'criteria' => [
                    'allowed_levels' => ['very_likely', 'likely']
                ]
            ]);
        } catch (\RuntimeException $e) {
            $status = $e->getCode();
            if ($status < 400 || $status > 599) {
                $status = 400;
            }
            $this->sendJsonResponse(false, $e->getMessage(), null, $status);
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * POST /api?controller=UnicodeGeneratorController&action=savePreferenceOrder
     * Body: {"program_ids": [12, 55, 3, ...]}
     */
    public function savePreferenceOrder(Request $request) {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }

            $payload = json_decode(file_get_contents('php://input'), true);
            if (!is_array($payload)) {
                $payload = $_POST;
            }

            $programIds = $payload['program_ids'] ?? [];
            if (!is_array($programIds) || empty($programIds)) {
                $this->sendJsonResponse(false, 'program_ids array is required', null, 400);
                return;
            }

            $programIds = array_values(array_unique(array_filter(array_map('intval', $programIds), function($id) {
                return $id > 0;
            })));

            if (empty($programIds)) {
                $this->sendJsonResponse(false, 'No valid program ids provided', null, 400);
                return;
            }

            $userId = (int) $_SESSION['user_id'];
            $eligiblePrograms = $this->getLikelyEligibleProgramsForUser($userId);

            if (empty($eligiblePrograms)) {
                $this->sendJsonResponse(false, 'No likely or very likely programs available for your current Z-Score', null, 400);
                return;
            }

            $eligibleById = [];
            foreach ($eligiblePrograms as $program) {
                $eligibleById[(int) $program['program_id']] = $program;
            }

            $invalidIds = [];
            foreach ($programIds as $programId) {
                if (!isset($eligibleById[$programId])) {
                    $invalidIds[] = $programId;
                }
            }

            if (!empty($invalidIds)) {
                $this->sendJsonResponse(false, 'Preference list contains programs outside likely/very_likely eligibility', [
                    'invalid_program_ids' => $invalidIds
                ], 400);
                return;
            }

            $orderedPreferences = [];
            foreach ($programIds as $index => $programId) {
                $program = $eligibleById[$programId];
                $orderedPreferences[] = [
                    'program_id' => (int) $programId,
                    'preference_order' => $index + 1,
                    'probability_percent' => isset($program['probability_percent']) ? (float) $program['probability_percent'] : null,
                    'eligibility_level' => $program['eligibility'] ?? null,
                    'recommendation_score' => isset($program['recommendation_score']) ? (float) $program['recommendation_score'] : null,
                ];
            }

            $this->preferenceModel->replaceUserPreferences($userId, $orderedPreferences);

            $orderedPrograms = [];
            foreach ($programIds as $index => $programId) {
                $program = $eligibleById[$programId];
                $program['current_rank'] = $index + 1;
                $orderedPrograms[] = $program;
            }

            $selectedProgram = !empty($orderedPrograms) ? $orderedPrograms[0] : null;

            $this->sendJsonResponse(true, 'Preference order saved successfully', [
                'programs' => $orderedPrograms,
                'selected_program' => $selectedProgram,
                'saved_count' => count($orderedPrograms)
            ]);
        } catch (\RuntimeException $e) {
            $status = $e->getCode();
            if ($status < 400 || $status > 599) {
                $status = 400;
            }
            $this->sendJsonResponse(false, $e->getMessage(), null, $status);
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * DELETE /api?controller=UnicodeGeneratorController&action=clearPreferenceOrder
     */
    public function clearPreferenceOrder(Request $request) {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }

            $userId = (int) $_SESSION['user_id'];
            $this->preferenceModel->clearUserPreferences($userId);

            $this->sendJsonResponse(true, 'Saved preference order cleared');
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Build eligible list and keep only likely/very_likely records.
     */
    private function getLikelyEligibleProgramsForUser($userId) {
        $zScoreData = $this->zScoreModel->findByUserId($userId);
        if (!$zScoreData) {
            throw new \RuntimeException('No Z-Score found. Please enter your Z-Score first.', 404);
        }

        $rawResults = $this->runEligibilityScript($zScoreData);

        if (!is_array($rawResults)) {
            return [];
        }

        $interest = $this->zScoreModel->getUserInterestProfile($userId);
        $preferredMajorId = isset($interest['major_id']) && $interest['major_id'] !== null
            ? (int) $interest['major_id']
            : null;

        $programs = [];

        foreach ($rawResults as $program) {
            if (!is_array($program)) {
                continue;
            }

            $eligibility = strtolower((string) ($program['eligibility'] ?? ''));
            if (!in_array($eligibility, ['likely', 'very_likely'], true)) {
                continue;
            }

            $programId = isset($program['program_id']) ? (int) $program['program_id'] : 0;
            if ($programId <= 0) {
                continue;
            }

            $probability = isset($program['probability_percent']) && $program['probability_percent'] !== null
                ? (float) $program['probability_percent']
                : null;

            $majorId = isset($program['major_id']) && $program['major_id'] !== null
                ? (int) $program['major_id']
                : null;

            $majorMatch = $preferredMajorId !== null && $majorId !== null && $preferredMajorId === $majorId;

            $probabilityPart = $probability !== null ? $probability : 0.0;
            $eligibilityBoost = $eligibility === 'very_likely' ? 8.0 : 0.0;
            $interestBoost = $majorMatch ? 4.0 : 0.0;
            $recommendationScore = round($probabilityPart + $eligibilityBoost + $interestBoost, 2);

            $programs[] = [
                'program_id' => $programId,
                'name' => $program['name'] ?? 'Unknown Program',
                'university' => $program['university'] ?? ($program['university_name'] ?? null),
                'unicode' => $program['unicode'] ?? null,
                'major_id' => $majorId,
                'eligibility' => $eligibility,
                'probability_percent' => $probability,
                'predicted' => isset($program['predicted']) && $program['predicted'] !== null ? (float) $program['predicted'] : null,
                'min_cutoff' => isset($program['min_cutoff']) && $program['min_cutoff'] !== null ? (float) $program['min_cutoff'] : null,
                'max_cutoff' => isset($program['max_cutoff']) && $program['max_cutoff'] !== null ? (float) $program['max_cutoff'] : null,
                'major_match' => $majorMatch,
                'recommendation_score' => $recommendationScore,
            ];
        }

        usort($programs, function($a, $b) {
            $scoreCompare = $b['recommendation_score'] <=> $a['recommendation_score'];
            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            $probA = $a['probability_percent'] !== null ? $a['probability_percent'] : -1;
            $probB = $b['probability_percent'] !== null ? $b['probability_percent'] : -1;
            $probCompare = $probB <=> $probA;
            if ($probCompare !== 0) {
                return $probCompare;
            }

            return strcmp((string) $a['name'], (string) $b['name']);
        });

        foreach ($programs as $index => &$program) {
            $program['suggested_rank'] = $index + 1;
            $program['current_rank'] = $index + 1;
        }
        unset($program);

        return $programs;
    }

    /**
     * Applies saved order first; appends any new eligible programs afterward.
     */
    private function mergeWithSavedOrder($eligiblePrograms, $savedOrderMap) {
        if (empty($savedOrderMap)) {
            return $eligiblePrograms;
        }

        $eligibleById = [];
        foreach ($eligiblePrograms as $program) {
            $eligibleById[(int) $program['program_id']] = $program;
        }

        asort($savedOrderMap, SORT_NUMERIC);

        $ordered = [];
        foreach ($savedOrderMap as $programId => $order) {
            $programId = (int) $programId;
            if (isset($eligibleById[$programId])) {
                $ordered[] = $eligibleById[$programId];
                unset($eligibleById[$programId]);
            }
        }

        foreach ($eligiblePrograms as $program) {
            $programId = (int) $program['program_id'];
            if (isset($eligibleById[$programId])) {
                $ordered[] = $eligibleById[$programId];
                unset($eligibleById[$programId]);
            }
        }

        foreach ($ordered as $index => &$program) {
            $program['current_rank'] = $index + 1;
        }
        unset($program);

        return $ordered;
    }

    /**
     * Executes the existing Python eligibility script.
     */
    private function runEligibilityScript($zScoreData) {
        $scriptPath = dirname(__DIR__) . '/python/eligibility.py';
        if (!file_exists($scriptPath)) {
            throw new \RuntimeException('Eligibility script not found: ' . $scriptPath, 500);
        }

        $pythonPath = dirname(__DIR__) . '/.venv/bin/python';
        if (!file_exists($pythonPath)) {
            $pythonPath = '/opt/homebrew/bin/python3';
            if (!file_exists($pythonPath)) {
                $detected = trim(shell_exec('which python3'));
                $pythonPath = !empty($detected) ? $detected : 'python3';
            }
        }

        $command = escapeshellarg($pythonPath) . ' ' .
                   escapeshellarg($scriptPath) . ' ' .
                   escapeshellarg($zScoreData['z_score']) . ' ' .
                   escapeshellarg($zScoreData['stream']) . ' ' .
                   escapeshellarg($zScoreData['district']) . ' ' .
                   escapeshellarg($zScoreData['subject1'] ?? '') . ' ' .
                   escapeshellarg($zScoreData['subject2'] ?? '') . ' ' .
                   escapeshellarg($zScoreData['subject3'] ?? '') . ' 2>&1';

        error_log('UnicodeGeneratorController command: ' . $command);

        $output = shell_exec($command);
        if ($output === null) {
            throw new \RuntimeException('Failed to execute eligibility script', 500);
        }

        $lines = explode("\n", trim($output));
        $jsonLine = end($lines);

        $result = json_decode($jsonLine, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('UnicodeGeneratorController invalid JSON output: ' . $output);
            throw new \RuntimeException('Invalid response from eligibility script: ' . json_last_error_msg(), 500);
        }

        if (isset($result['error'])) {
            throw new \RuntimeException('Eligibility processing error: ' . $result['error'], 500);
        }

        return $result;
    }

    private function sendJsonResponse($success, $message, $data = null, $httpCode = 200) {
        http_response_code($httpCode);
        header('Content-Type: application/json');

        $response = [
            'success' => $success,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response);
        exit;
    }
}
