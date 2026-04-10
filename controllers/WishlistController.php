<?php

namespace app\controllers;

require_once dirname(__DIR__) . '/models/base-model.php';
require_once dirname(__DIR__) . '/models/wishlistModel.php';

use app\models\WishlistModel;
use app\core\Request;

/**
 * Controller for Wishlist operations
 * Handles adding, removing, and retrieving wishlist items
 */
class WishlistController
{
    private $wishlistModel;
    
    public function __construct() {
        $this->wishlistModel = new WishlistModel();
    }
    
    /**
     * Check if a program is in the user's wishlist
     * GET /api?controller=WishlistController&action=checkWishlist&program_id=ID
     */
    public function checkWishlist(Request $request) {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }
            
            $userId = $_SESSION['user_id'];
            $programId = isset($_GET['program_id']) ? intval($_GET['program_id']) : 0;
            
            if ($programId <= 0) {
                $this->sendJsonResponse(false, 'Invalid program_id', null, 400);
                return;
            }
            
            $inWishlist = $this->wishlistModel->isInWishlist($userId, $programId);
            $this->sendJsonResponse(true, 'Wishlist status retrieved', [
                'isInWishlist' => $inWishlist
            ]);
            
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Add a program to the user's wishlist
     * POST /api?controller=WishlistController&action=addToWishlist
     */
    public function addToWishlist(Request $request) {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $programId = isset($input['program_id']) ? intval($input['program_id']) : 0;
            
            if ($programId <= 0) {
                $this->sendJsonResponse(false, 'Invalid program_id', null, 400);
                return;
            }
            
            $userId = $_SESSION['user_id'];
            $this->wishlistModel->addToWishlist($userId, $programId);
            $this->sendJsonResponse(true, 'Added to wishlist');
            
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Remove a program from the user's wishlist
     * DELETE /api?controller=WishlistController&action=removeFromWishlist
     */
    public function removeFromWishlist(Request $request) {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $programId = isset($input['program_id']) ? intval($input['program_id']) : 0;
            
            if ($programId <= 0) {
                $this->sendJsonResponse(false, 'Invalid program_id', null, 400);
                return;
            }
            
            $userId = $_SESSION['user_id'];
            $removed = $this->wishlistModel->removeFromWishlist($userId, $programId);
            
            if ($removed) {
                $this->sendJsonResponse(true, 'Removed from wishlist');
            } else {
                $this->sendJsonResponse(false, 'Item not found in wishlist', null, 404);
            }
            
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Get wishlist count for the current user
     * GET /api?controller=WishlistController&action=getWishlistCount
     */
    public function getWishlistCount(Request $request) {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }
            
            $userId = $_SESSION['user_id'];
            $count = $this->wishlistModel->getWishlistCount($userId);
            $this->sendJsonResponse(true, 'Wishlist count retrieved', ['count' => $count]);
            
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Get wishlist items for the current user
     * GET /api?controller=WishlistController&action=getWishlistItems
     */
    public function getWishlistItems(Request $request) {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }
            
            $userId = $_SESSION['user_id'];
            $items = $this->wishlistModel->getUserWishlist($userId);
            $this->sendJsonResponse(true, 'Wishlist items retrieved', $items);
            
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Send JSON response
     */
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
