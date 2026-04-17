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
            $userId = $request->session('user_id');
            if (!$userId) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }
            
            if ($request->getMethod() !== 'GET') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }

            $programId = $this->resolveProgramId($request);
            
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
            $userId = $request->session('user_id');
            if (!$userId) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }
            
            if ($request->getMethod() !== 'POST') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }

            $programId = $this->resolveProgramId($request);
            
            if ($programId <= 0) {
                $this->sendJsonResponse(false, 'Invalid program_id', null, 400);
                return;
            }

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
            $userId = $request->session('user_id');
            if (!$userId) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }
            
            if ($request->getMethod() !== 'DELETE') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }

            $programId = $this->resolveProgramId($request);
            
            if ($programId <= 0) {
                $this->sendJsonResponse(false, 'Invalid program_id', null, 400);
                return;
            }

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
            $userId = $request->session('user_id');
            if (!$userId) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }

            if ($request->getMethod() !== 'GET') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }

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
            $userId = $request->session('user_id');
            if (!$userId) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }

            if ($request->getMethod() !== 'GET') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }

            $items = $this->wishlistModel->getUserWishlist($userId);
            $this->sendJsonResponse(true, 'Wishlist items retrieved', $items);
            
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Resolve program_id from Request abstraction first, then JSON body fallback.
     */
    private function resolveProgramId(Request $request): int
    {
        $requestValue = $request->get('program_id');
        if ($requestValue !== null && $requestValue !== '') {
            return (int)$requestValue;
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        if (is_array($payload) && isset($payload['program_id'])) {
            return (int)$payload['program_id'];
        }

        return 0;
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
