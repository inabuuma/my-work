<?php
// Predictive Suggestions System
// Smart filtering based on item characteristics and location

function suggestMatchingItems($conn, $search_item_id, $search_type = 'lost', $hostel_id = 1, $limit = 5) {
    global $error;
    
    // Get the search item details
    if ($search_type === 'lost') {
        $item_stmt = $conn->prepare("SELECT * FROM lost_items WHERE item_id = ? AND hostel_id = ?");
    } else {
        $item_stmt = $conn->prepare("SELECT * FROM found_items WHERE item_id = ? AND hostel_id = ?");
    }
    $item_stmt->bind_param("ii", $search_item_id, $hostel_id);
    $item_stmt->execute();
    $search_item = $item_stmt->get_result()->fetch_assoc();
    $item_stmt->close();
    
    if (!$search_item) {
        $error = "Item not found.";
        return [];
    }
    
    // Opposite table to search in
    $opposite_type = ($search_type === 'lost') ? 'found' : 'lost';
    $opposite_table = $opposite_type . '_items';
    
    // Build suggestion query with scoring
    $search_term = "%{$search_item['title']}%";
    $category = $search_item['category'];
    $location = $search_item['location'];
    
    // Get suggestions ordered by relevance score
    $stmt = $conn->prepare("
        SELECT *, 
            (
                CASE 
                    WHEN category = ? THEN 5
                    ELSE 0
                END
                +
                CASE 
                    WHEN location = ? THEN 10
                    WHEN SUBSTRING_INDEX(location, ' ', 1) = SUBSTRING_INDEX(?, ' ', 1) THEN 5
                    ELSE 0
                END
                +
                CASE 
                    WHEN title LIKE ? THEN 8
                    WHEN description LIKE ? THEN 3
                    ELSE 0
                END
                +
                CASE 
                    WHEN ABS(DATEDIFF(DATE(NOW()), DATE_$opposite_type)) <= 7 THEN 5
                    WHEN ABS(DATEDIFF(DATE(NOW()), DATE_$opposite_type)) <= 14 THEN 2
                    ELSE 0
                END
            ) as relevance_score
        FROM $opposite_table
        WHERE hostel_id = ? 
        AND status IN ('found', 'lost') 
        AND (category = ? OR title LIKE ? OR description LIKE ?)
        HAVING relevance_score > 0
        ORDER BY relevance_score DESC
        LIMIT ?
    ");
    
    $field_date = ($opposite_type === 'lost') ? 'date_lost' : 'date_found';
    $stmt->bind_param("ssssssii", $category, $location, $location, $search_term, $search_term, $search_term, $hostel_id, $category, $search_term, $search_term, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $suggestions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $suggestions;
}

function getSuggestionsByLocation($conn, $location, $hostel_id = 1, $item_type = 'lost', $limit = 5) {
    // Get items found/lost in the same location recently
    $table = $item_type . '_items';
    $date_field = ($item_type === 'lost') ? 'date_lost' : 'date_found';
    
    $stmt = $conn->prepare("
        SELECT *, 
            DATEDIFF(NOW(), $date_field) as days_ago
        FROM $table
        WHERE location LIKE ? 
        AND hostel_id = ? 
        AND status IN ('found', 'lost')
        ORDER BY created_at DESC
        LIMIT ?
    ");
    
    $location_search = "%$location%";
    $stmt->bind_param("sii", $location_search, $hostel_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $suggestions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $suggestions;
}

function getSuggestionsByCategory($conn, $category, $hostel_id = 1, $limit = 5) {
    // Get similar items by category that are still open
    $stmt = $conn->prepare("
        SELECT *, 
            CASE 
                WHEN status = 'lost' THEN 'Still searching'
                WHEN status = 'found' THEN 'Recently found'
            END as status_label
        FROM (
            SELECT *, 'lost' as type FROM lost_items 
            WHERE category = ? AND hostel_id = ? AND status IN ('lost', 'recovered')
            UNION
            SELECT *, 'found' as type FROM found_items 
            WHERE category = ? AND hostel_id = ? AND status IN ('found', 'returned')
        ) as combined
        ORDER BY created_at DESC
        LIMIT ?
    ");
    
    $stmt->bind_param("sisis", $category, $hostel_id, $category, $hostel_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $suggestions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $suggestions;
}

function getPersonalizedSuggestions($conn, $user_id, $hostel_id = 1) {
    // Smart suggestions based on user history and lost items
    
    // Get user's lost items
    $lost_stmt = $conn->prepare("SELECT * FROM lost_items WHERE user_id = ? AND status IN ('lost', 'recovered') ORDER BY created_at DESC LIMIT 1");
    $lost_stmt->bind_param("i", $user_id);
    $lost_stmt->execute();
    $recent_lost = $lost_stmt->get_result()->fetch_assoc();
    $lost_stmt->close();
    
    $suggestions = [];
    
    if ($recent_lost) {
        // Suggest found items in same location/category
        $suggestions_stmt = $conn->prepare("
            SELECT fi.*, 'location_match' as reason FROM found_items fi
            WHERE (fi.location LIKE ? OR fi.category = ?)
            AND fi.hostel_id = ?
            AND fi.status = 'found'
            AND fi.user_id != ?
            ORDER BY fi.created_at DESC
            LIMIT 3
        ");
        
        $location = "%{$recent_lost['location']}%";
        $suggestions_stmt->bind_param("siii", $location, $recent_lost['category'], $hostel_id, $user_id);
        $suggestions_stmt->execute();
        $result = $suggestions_stmt->get_result();
        $suggestions = $result->fetch_all(MYSQLI_ASSOC);
        $suggestions_stmt->close();
    }
    
    return $suggestions;
}

function getSmartRecommendations($conn, $user_id, $hostel_id = 1) {
    $recommendations = [];
    
    // 1. Get user's recent activity
    $activity_stmt = $conn->prepare("
        SELECT * FROM activity_logs 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $activity_stmt->bind_param("i", $user_id);
    $activity_stmt->execute();
    $activities = $activity_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $activity_stmt->close();
    
    // 2. Extract patterns from recent items
    $categories = [];
    $locations = [];
    
    foreach ($activities as $activity) {
        if ($activity['action_type'] === 'report_lost' || $activity['action_type'] === 'report_found') {
            // This would need more detailed data to work properly
        }
    }
    
    // 3. Find trending categories
    $trending_stmt = $conn->prepare("
        SELECT category, COUNT(*) as count
        FROM (
            SELECT category FROM lost_items WHERE hostel_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            UNION ALL
            SELECT category FROM found_items WHERE hostel_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
        ) as combined
        GROUP BY category
        ORDER BY count DESC
        LIMIT 5
    ");
    $trending_stmt->bind_param("ii", $hostel_id, $hostel_id);
    $trending_stmt->execute();
    $trending = $trending_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $trending_stmt->close();
    
    $recommendations['trending_categories'] = $trending;
    
    // 4. Hot locations
    $hotspots = getLocationHotspots($conn, $hostel_id, 3);
    $recommendations['hotspots'] = $hotspots;
    
    // 5. Top finders (leaderboard)
    $leaders_stmt = $conn->prepare("
        SELECT u.user_id, u.full_name, rl.total_points, rl.reward_rank, rl.total_returns
        FROM rewards_leaderboard rl
        JOIN users u ON rl.user_id = u.user_id
        WHERE u.hostel_id = ?
        ORDER BY rl.total_points DESC
        LIMIT 5
    ");
    $leaders_stmt->bind_param("i", $hostel_id);
    $leaders_stmt->execute();
    $leaders = $leaders_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $leaders_stmt->close();
    
    $recommendations['top_helpers'] = $leaders;
    
    return $recommendations;
}

?>
