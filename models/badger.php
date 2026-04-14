<?php

namespace app\models;

use app\core\Database;

require_once __DIR__ . '/notify.php';

use app\models\notify;

class badger
{
    private $db;
    private $notify;
    private $badgelookup;


    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->notify = new notify();
        $this->badgelookup = [
                'curious-mind' => [
                    'id'   => 1,
                    'name' => 'Curious Mind'
                ],
                'insightful-question' => [
                    'id'   => 2,
                    'name' => 'Insightful Question'
                ],
                'top-question' => [
                    'id'   => 3,
                    'name' => 'Top Question'
                ],
                'regular-inquirer' => [
                    'id'   => 4,
                    'name' => 'Regular Inquirer'
                ],
                'discussion-starter' => [
                    'id'   => 5,
                    'name' => 'Discussion Starter'
                ],
                'peer-influencer' => [
                    'id'   => 6,
                    'name' => 'Peer Influencer'
                ],
                'trendsetter' => [
                    'id'   => 7,
                    'name' => 'Trendsetter'
                ],
                'explorer' => [
                    'id'   => 8,
                    'name' => 'Explorer'
                ],
                'avid-voter' => [
                    'id'   => 9,
                    'name' => 'Avid Voter'
                ],
                'community-member' => [
                    'id'   => 10,
                    'name' => 'Community Member'
                ],
                'social-worker' => [
                    'id'   => 11,
                    'name' => 'Social Worker'
                ],
                'celebrity' => [
                    'id'   => 12,
                    'name' => 'Celebrity'
                ],
            ];
    }

    // add a new badger entry for a user
    public function add($userId, $badgeName)
    {
        try {
            $sql = "INSERT INTO user_badges (user_id, badge_id) VALUES (:user_id, :badge_id)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':badge_id', $this->badgelookup[$badgeName]['id']);

            if ($badgeName === 'celebrity') {
                $type = 'other';
            } else {
                $type = 'qa';
            }
            $this->notify->insertNotification($userId, "Congratulations! You've earned the '" . $this->badgelookup[$badgeName]['name'] . "' badge.", $type, '/unihelper/profile/view');
            
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Badger add error: " . $e->getMessage());
            return false;
        }
    }
}