<?php

namespace app\controllers;

require_once dirname(__DIR__) . '\models\search.php';

use app\models\search;
use app\core\Request;

class searchController
{
    private $model;

    public function __construct()
    {
        $this->model = new search();
    }

    // ---------------------------------------------------------------
    // GET  ?controller=searchController&action=search&query=...&type=qa|user|feed|session&index=...
    // type=user response contract: [{user_id, name, profile_picture, role}, ...]
    // ---------------------------------------------------------------
    public function search(Request $request)
    {
        header('Content-Type: application/json');

        $query = $request->get('query');
        $type = $request->get('type');
        $index = $request->get('index') ?? 0;

        if (!$query || !$type) {
            echo json_encode([
                'success' => false,
                'message' => 'Missing query or type'
            ]);
            return;
        }

        try {
            $results = null;

            if ($type === 'qa') {
                $results = $this->model->qa_search($query, $index);
            } elseif ($type === 'user') {
                // Do not exclude the current user from search results
                $results = $this->model->user_search($query, $index);
            } elseif ($type === 'feed') {
                $results = $this->model->feed_search($query, $index);
            } elseif ($type === 'session') {
                $results = $this->model->session_search($query, $index);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid search type'
                ]);
                return;
            }

            echo json_encode([
                'success' => true,
                'data'    => $results
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
