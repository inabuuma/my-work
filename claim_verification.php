<?php
// Claim Verification System
// Verifies ownership with security questions before approving claims

define('CLAIM_QUESTIONS', [
    'lost' => [
        "What was inside the bag/container?",
        "Describe the exact color and any markings or labels",
        "When did you first realize the item was missing?"
    ],
    'found' => [
        "What specific features does this item have?",
        "Describe the condition when you found it",
        "Where exactly did you find this item?"
    ]
]);

function generateClaimQuestions($claim_type) {
    if (!isset(CLAIM_QUESTIONS[$claim_type])) {
        return [];
    }
    
    $questions = CLAIM_QUESTIONS[$claim_type];
    // Shuffle and pick 3 random questions
    shuffle($questions);
    return array_slice($questions, 0, 3);
}

function createClaimWithQuestions($conn, $item_id, $user_id, $claim_type, $description, $proof_images = []) {
    global $error;
    
    $questions = generateClaimQuestions($claim_type);
    
    if (count($questions) < 3) {
        $error = "Error generating verification questions.";
        return false;
    }
    
    $proof_json = json_encode($proof_images);
    
    $stmt = $conn->prepare("INSERT INTO claims (item_id, user_id, claim_type, description, proof_images, question_1, question_2, question_3, claim_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    
    $stmt->bind_param("iissssss", $item_id, $user_id, $claim_type, $description, $proof_json, $questions[0], $questions[1], $questions[2]);
    
    if ($stmt->execute()) {
        $claim_id = $conn->insert_id;
        $stmt->close();
        return $claim_id;
    } else {
        $error = "Error creating claim. Please try again.";
        $stmt->close();
        return false;
    }
}

function submitClaimAnswers($conn, $claim_id, $answer1, $answer2, $answer3) {
    global $error;
    
    // Validate answers aren't empty
    if (empty($answer1) || empty($answer2) || empty($answer3)) {
        $error = "All answers are required.";
        return false;
    }
    
    $stmt = $conn->prepare("UPDATE claims SET answer_1 = ?, answer_2 = ?, answer_3 = ?, claim_status = 'verified_pending_approval' WHERE claim_id = ?");
    
    $stmt->bind_param("sssi", $answer1, $answer2, $answer3, $claim_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        $error = "Error submitting answers. Please try again.";
        $stmt->close();
        return false;
    }
}

function getClaimWithQuestions($conn, $claim_id) {
    $stmt = $conn->prepare("SELECT * FROM claims WHERE claim_id = ?");
    $stmt->bind_param("i", $claim_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $claim = $result->fetch_assoc();
    $stmt->close();
    
    return $claim;
}

function verifyAndApproveClaim($conn, $claim_id, $verified_by, $is_approved = true) {
    $new_status = $is_approved ? 'approved' : 'rejected';
    
    $stmt = $conn->prepare("UPDATE claims SET claim_status = ?, verified_by = ? WHERE claim_id = ?");
    $stmt->bind_param("sii", $new_status, $verified_by, $claim_id);
    
    if ($stmt->execute()) {
        // If approved, update item status and award points to finder
        if ($is_approved) {
            $claim = getClaimWithQuestions($conn, $claim_id);
            if ($claim) {
                // Get item type to update status
                $item_type = $claim['claim_type'] === 'lost' ? 'lost' : 'found';
                $table = $item_type . '_items';
                
                $update_stmt = $conn->prepare("UPDATE $table SET status = 'claimed' WHERE item_id = ?");
                $update_stmt->bind_param("i", $claim['item_id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Award points to finder
                awardPoints($conn, $claim['user_id'], 10, 'Claimed item verified');
                
                // Log activity
                logActivity($conn, null, "verify_claim", $claim['item_id'], $claim['user_id'], 
                    ['claim_id' => $claim_id, 'status' => $new_status]);
            }
        }
        
        $stmt->close();
        return true;
    } else {
        $stmt->close();
        return false;
    }
}

function awardPoints($conn, $user_id, $points, $reason = '') {
    // Update user points
    $stmt = $conn->prepare("UPDATE users SET points = points + ? WHERE user_id = ?");
    $stmt->bind_param("ii", $points, $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Update leaderboard
    $check = $conn->prepare("SELECT leaderboard_id FROM rewards_leaderboard WHERE user_id = ?");
    $check->bind_param("i", $user_id);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();
    
    if ($exists) {
        $update = $conn->prepare("UPDATE rewards_leaderboard SET total_points = total_points + ?, total_returns = total_returns + 1 WHERE user_id = ?");
        $update->bind_param("ii", $points, $user_id);
        $update->execute();
        $update->close();
    } else {
        $insert = $conn->prepare("INSERT INTO rewards_leaderboard (user_id, total_points, total_returns) VALUES (?, ?, 1)");
        $insert->bind_param("ii", $user_id, $points);
        $insert->execute();
        $insert->close();
    }
    
    // Update reward level based on points
    updateRewardLevel($conn, $user_id);
}

function updateRewardLevel($conn, $user_id) {
    $stmt = $conn->prepare("SELECT points FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($user) {
        $points = $user['points'];
        $level = 'novice';
        
        if ($points >= 100) $level = 'legend';
        elseif ($points >= 50) $level = 'good_samaritan';
        elseif ($points >= 25) $level = 'helper';
        
        $update = $conn->prepare("UPDATE users SET reward_level = ? WHERE user_id = ?");
        $update->bind_param("si", $level, $user_id);
        $update->execute();
        $update->close();
        
        // Update leaderboard rank
        $rank_update = $conn->prepare("UPDATE rewards_leaderboard SET reward_rank = ? WHERE user_id = ?");
        $rank_update->bind_param("si", $level, $user_id);
        $rank_update->execute();
        $rank_update->close();
    }
}

function logActivity($conn, $user_id, $action_type, $related_item_id = null, $related_user_id = null, $details = []) {
    $action = '';
    switch($action_type) {
        case 'verify_claim': $action = 'Verified Claim'; break;
        case 'report_lost': $action = 'Reported Lost Item'; break;
        case 'report_found': $action = 'Reported Found Item'; break;
        case 'claim': $action = 'Submitted Claim'; break;
        case 'approve': $action = 'Approved Claim'; break;
        case 'reject': $action = 'Rejected Claim'; break;
        default: $action = ucfirst(str_replace('_', ' ', $action_type));
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $details_json = json_encode($details);
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, action_type, related_item_id, related_user_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isiiiss", $user_id, $action, $action_type, $related_item_id, $related_user_id, $details_json, $ip);
    $stmt->execute();
    $stmt->close();
}

?>
