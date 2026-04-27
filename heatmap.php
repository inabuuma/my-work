<?php
$pageTitle = "Location Heatmap | Lost & Found";
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/location_heatmap.php';
requireLogin();
require_once '../includes/header.php';
require_once '../includes/navbar.php';

$hostel_id = (int)($_SESSION['hostel_id'] ?? 1);

// Get hotspots
$hotspots = getLocationHotspots($conn, $hostel_id);
$dangerous_locations = getMostDangerousLocations($conn, $hostel_id);
$recovery_rates = getRecoveryRateByLocation($conn, $hostel_id);
?>

<div class="container py-4">
    <h4 class="mb-4"><i class="bi bi-geo-alt"></i> Location Heatmap - Where Items Are Lost</h4>
    
    <div class="row g-3 mb-4">
        <!-- Heatmap Stats -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Dangerous Hotspots (Most Lost Items)</h5>
                </div>
                <div class="card-body">
                    <div style="width: 100%; height: 400px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <div class="text-center">
                            <p class="text-muted"><i class="bi bi-map"></i> Interactive heatmap would display here</p>
                            <small>Intensity based on lost item frequency</small>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h6>Top Risk Locations:</h6>
                        <?php foreach ($dangerous_locations as $loc): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 border-bottom">
                                <div>
                                    <strong><?= htmlspecialchars($loc['location']) ?></strong>
                                    <br>
                                    <small class="text-danger"><?= $loc['lost_count'] ?> items lost</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-danger"><?= round($loc['lost_count'] / max(1, $loc['lost_count'] + $loc['found_count']) * 100) ?>% Lost Rate</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recovery Rates -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Recovery Rates</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($recovery_rates as $rate): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small><?= htmlspecialchars($rate['location']) ?></small>
                                <strong><?= $rate['recovery_rate'] ?>%</strong>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" style="width: <?= $rate['recovery_rate'] ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Security Insights</h5>
                </div>
                <div class="card-body small">
                    <p><strong>Observation:</strong></p>
                    <ul class="mb-0">
                        <li>High-traffic areas have more lost items</li>
                        <li>Recovery rate varies by location</li>
                        <li>Security presence affects recovery</li>
                    </ul>
                    <hr>
                    <p class="mb-0"><em>⚠️ Recommend: Extra monitoring at dangerous hotspots</em></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- All Locations -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">All Locations Statistics</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Location</th>
                        <th>Lost Items</th>
                        <th>Found Items</th>
                        <th>Total</th>
                        <th>Recovery Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hotspots as $spot): ?>
                        <tr>
                            <td><?= htmlspecialchars($spot['location']) ?></td>
                            <td><span class="badge bg-danger"><?= $spot['lost_count'] ?></span></td>
                            <td><span class="badge bg-primary"><?= $spot['found_count'] ?></span></td>
                            <td><strong><?= $spot['total_incidents'] ?></strong></td>
                            <td>
                                <?php 
                                    $recovery = $spot['total_incidents'] > 0 
                                        ? round($spot['found_count'] / $spot['total_incidents'] * 100) 
                                        : 0;
                                ?>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar" style="width: <?= $recovery ?>%"></div>
                                </div>
                                <?= $recovery ?>%
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
