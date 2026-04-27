<?php
// Expiry & Auction System for Unclaimed Items

define('ITEM_EXPIRY_DAYS', 30); // Items unclaimed after 30 days are flagged

function checkAndFlagExpiredItems($conn, $hostel_id = 1) {
    $expiry_date = date('Y-m-d H:i:s', strtotime('-' . ITEM_EXPIRY_DAYS . ' days'));
    
    $stmt = $conn->prepare("
        UPDATE found_items 
        SET status = 'unclaimed', expiry_date = NOW()
        WHERE status = 'found' 
        AND hostel_id = ?
        AND created_at < ?
        AND item_id NOT IN (SELECT item_id FROM claims WHERE claim_status = 'approved')
    ");
    $stmt->bind_param("is", $hostel_id, $expiry_date);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    return $affected;
}

function createAuctionForItem($conn, $item_id, $auction_type, $reserve_price = 0, $days_duration = 7) {
    global $error;
    
    // Get item details
    $item_stmt = $conn->prepare("SELECT user_id, title FROM found_items WHERE item_id = ?");
    $item_stmt->bind_param("i", $item_id);
    $item_stmt->execute();
    $item = $item_stmt->get_result()->fetch_assoc();
    $item_stmt->close();
    
    if (!$item) {
        $error = "Item not found.";
        return false;
    }
    
    $end_date = date('Y-m-d H:i:s', strtotime("+$days_duration days"));
    
    $stmt = $conn->prepare("
        INSERT INTO auction_items (item_id, auction_type, reserve_price, end_date, status)
        VALUES (?, ?, ?, ?, 'active')
    ");
    $stmt->bind_param("isdd", $item_id, $auction_type, $reserve_price, $end_date);
    
    if ($stmt->execute()) {
        $auction_id = $conn->insert_id;
        $stmt->close();
        
        // Update item status
        $update = $conn->prepare("UPDATE found_items SET status = 'auctioned' WHERE item_id = ?");
        $update->bind_param("i", $item_id);
        $update->execute();
        $update->close();
        
        // Log activity
        logActivity($conn, null, "auction_created", $item_id, null, 
            ['auction_type' => $auction_type, 'auction_id' => $auction_id]);
        
        // Notify original finder
        createNotification($conn, $item['user_id'], 
            "Your item '{$item['title']}' has been listed for " . ucfirst(str_replace('_', ' ', $auction_type)), 
            'system', $item_id);
        
        return $auction_id;
    } else {
        $error = "Error creating auction.";
        $stmt->close();
        return false;
    }
}

function placeBid($conn, $user_id, $auction_id, $bid_amount) {
    global $error;
    
    $auction_stmt = $conn->prepare("SELECT * FROM auction_items WHERE auction_id = ?");
    $auction_stmt->bind_param("i", $auction_id);
    $auction_stmt->execute();
    $auction = $auction_stmt->get_result()->fetch_assoc();
    $auction_stmt->close();
    
    if (!$auction) {
        $error = "Auction not found.";
        return false;
    }
    
    if ($auction['status'] !== 'active') {
        $error = "Auction is not active.";
        return false;
    }
    
    if ($bid_amount <= $auction['current_bid'] || $bid_amount < $auction['reserve_price']) {
        $error = "Your bid must be higher than current bid and meet reserve price.";
        return false;
    }
    
    $stmt = $conn->prepare("UPDATE auction_items SET current_bid = ?, highest_bidder_id = ? WHERE auction_id = ?");
    $stmt->bind_param("dii", $bid_amount, $user_id, $auction_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Log activity
        logActivity($conn, $user_id, "bid_placed", $auction['item_id'], null, 
            ['auction_id' => $auction_id, 'bid_amount' => $bid_amount]);
        
        return true;
    } else {
        $error = "Error placing bid.";
        $stmt->close();
        return false;
    }
}

function finalizeAuction($conn, $auction_id) {
    $auction_stmt = $conn->prepare("SELECT * FROM auction_items WHERE auction_id = ?");
    $auction_stmt->bind_param("i", $auction_id);
    $auction_stmt->execute();
    $auction = $auction_stmt->get_result()->fetch_assoc();
    $auction_stmt->close();
    
    if (!$auction) {
        return false;
    }
    
    if ($auction['auction_type'] === 'auction_internal') {
        // Award to highest bidder
        if ($auction['highest_bidder_id']) {
            $status = 'claimed';
            $stmt = $conn->prepare("UPDATE found_items SET status = ? WHERE item_id = ?");
            $stmt->bind_param("si", $status, $auction['item_id']);
            $stmt->execute();
            $stmt->close();
            
            // Award points to winner and finder
            awardPoints($conn, $auction['highest_bidder_id'], 5, 'Auction won');
            awardPoints($conn, $auction['item_id'], 5, 'Item auctioned successfully');
            
            // Notify winner
            $item_stmt = $conn->prepare("SELECT title FROM found_items WHERE item_id = ?");
            $item_stmt->bind_param("i", $auction['item_id']);
            $item_stmt->execute();
            $item = $item_stmt->get_result()->fetch_assoc();
            $item_stmt->close();
            
            createNotification($conn, $auction['highest_bidder_id'], 
                "Congratulations! You won the auction for '{$item['title']}'", 'system', $auction['item_id']);
        }
    } elseif ($auction['auction_type'] === 'donate') {
        $status = 'donated';
        $stmt = $conn->prepare("UPDATE found_items SET status = ? WHERE item_id = ?");
        $stmt->bind_param("si", $status, $auction['item_id']);
        $stmt->execute();
        $stmt->close();
    } elseif ($auction['auction_type'] === 'dispose') {
        $status = 'disposed';
        $stmt = $conn->prepare("UPDATE found_items SET status = ? WHERE item_id = ?");
        $stmt->bind_param("si", $status, $auction['item_id']);
        $stmt->execute();
        $stmt->close();
    }
    
    $update = $conn->prepare("UPDATE auction_items SET status = 'completed' WHERE auction_id = ?");
    $update->bind_param("i", $auction_id);
    $update->execute();
    $update->close();
    
    // Log activity
    logActivity($conn, null, "auction_finalized", $auction['item_id'], null, 
        ['auction_type' => $auction['auction_type'], 'auction_id' => $auction_id]);
    
    return true;
}

function getExpiredItems($conn, $hostel_id = 1, $limit = 100) {
    $stmt = $conn->prepare("
        SELECT fi.*, u.full_name, u.email
        FROM found_items fi
        JOIN users u ON fi.user_id = u.user_id
        WHERE fi.status = 'unclaimed' AND fi.hostel_id = ?
        ORDER BY fi.expiry_date ASC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $hostel_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $items;
}

function getActiveAuctions($conn, $hostel_id = 1) {
    $stmt = $conn->prepare("
        SELECT ai.*, fi.title, fi.description, fi.image_paths, u.full_name as finder_name
        FROM auction_items ai
        JOIN found_items fi ON ai.item_id = fi.item_id
        JOIN users u ON fi.user_id = u.user_id
        WHERE ai.status = 'active' AND fi.hostel_id = ?
        ORDER BY ai.end_date ASC
    ");
    $stmt->bind_param("i", $hostel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $auctions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $auctions;
}

?>
