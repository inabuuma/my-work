<?php
// Location Heatmap and Hotspot Tracking System

function updateLocationHeatmap($conn, $location, $hostel_id = 1, $item_type = 'lost') {
    // Get coordinates if available
    $lat = 0;
    $lon = 0;
    
    // Check if heatmap entry exists
    $check = $conn->prepare("SELECT heatmap_id FROM location_heatmap WHERE location = ? AND hostel_id = ?");
    $check->bind_param("si", $location, $hostel_id);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();
    
    if ($item_type === 'lost') {
        $column = 'lost_count';
    } else {
        $column = 'found_count';
    }
    
    if ($exists) {
        // Update existing entry
        $update = $conn->prepare("UPDATE location_heatmap SET $column = $column + 1, total_incidents = total_incidents + 1 WHERE location = ? AND hostel_id = ?");
        $update->bind_param("si", $location, $hostel_id);
        $update->execute();
        $update->close();
    } else {
        // Insert new entry
        if ($item_type === 'lost') {
            $lost_count = 1;
            $found_count = 0;
        } else {
            $lost_count = 0;
            $found_count = 1;
        }
        $total = 1;
        
        $insert = $conn->prepare("INSERT INTO location_heatmap (hostel_id, location, lost_count, found_count, total_incidents, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insert->bind_param("isiiidd", $hostel_id, $location, $lost_count, $found_count, $total, $lat, $lon);
        $insert->execute();
        $insert->close();
    }
}

function getLocationHotspots($conn, $hostel_id = 1, $limit = 10) {
    $stmt = $conn->prepare("
        SELECT location, lost_count, found_count, total_incidents, latitude, longitude
        FROM location_heatmap
        WHERE hostel_id = ?
        ORDER BY total_incidents DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $hostel_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $hotspots = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $hotspots;
}

function getMostDangerousLocations($conn, $hostel_id = 1) {
    // Get top lost item locations
    $stmt = $conn->prepare("
        SELECT location, lost_count, found_count
        FROM location_heatmap
        WHERE hostel_id = ? AND lost_count > 0
        ORDER BY lost_count DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $hostel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $locations = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $locations;
}

function getRecoveryRateByLocation($conn, $hostel_id = 1) {
    $stmt = $conn->prepare("
        SELECT location, lost_count, found_count, 
               CASE 
                   WHEN (lost_count + found_count) > 0 
                   THEN ROUND((found_count / (lost_count + found_count)) * 100, 2)
                   ELSE 0
               END as recovery_rate
        FROM location_heatmap
        WHERE hostel_id = ? AND total_incidents > 0
        ORDER BY recovery_rate DESC
    ");
    $stmt->bind_param("i", $hostel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $recovery = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $recovery;
}

function getHeatmapData($conn, $hostel_id = 1) {
    $stmt = $conn->prepare("
        SELECT location, total_incidents as intensity, latitude, longitude
        FROM location_heatmap
        WHERE hostel_id = ? AND total_incidents > 0
        ORDER BY total_incidents DESC
    ");
    $stmt->bind_param("i", $hostel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return json_encode($data);
}

function suggestImprovedSecurityLocations($conn, $hostel_id = 1) {
    // Locations with high lost item counts
    $stmt = $conn->prepare("
        SELECT location, lost_count, found_count, total_incidents
        FROM location_heatmap
        WHERE hostel_id = ? AND lost_count > (found_count + 2)
        ORDER BY lost_count DESC
    ");
    $stmt->bind_param("i", $hostel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $suggestions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $suggestions;
}

?>
