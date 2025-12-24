<?php

namespace app\controllers;

require_once dirname(__DIR__, 1) . '/models/qa.php';

use app\core\Request;
use app\models\Qna;

class QaController
{
    private Qna $qnaModel;

    public function __construct()
    {
        $this->qnaModel = new Qna();
    }

    // Handle creating a new QnA
    public function createQna(Request $request)
    {
        $data = $request->getBody();
        return $this->qnaModel->insert($data);
    }

    // Handle deleting a QnA by ID
    public function deleteQna(Request $request)
    {
        $id = $request->get('id');
        return $this->qnaModel->delete($id);
    }

    // Handle retrieving a QnA by ID
    public function getQna(Request $request)
    {
        $id = $request->get('id');
        return $this->qnaModel->getQna($id);
    }

    // Handle updating a QnA by ID
    public function updateQna(Request $request)
    {
        $id = $request->get('id');
        $data = $request->getBody();
        return $this->qnaModel->update($id, $data);
    }
}