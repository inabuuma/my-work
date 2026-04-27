# Quick Start Guide - New Features

## 🔑 Key Includes to Use

All features work by including the appropriate files:

```php
require_once '../includes/claim_verification.php';  // For claims
require_once '../includes/location_heatmap.php';    // For heatmap
require_once '../includes/messaging.php';           // For messages
require_once '../includes/auction_system.php';      // For auctions
require_once '../includes/hostel_management.php';   // For multi-hostel
require_once '../includes/offline_sync.php';        // For offline sync
require_once '../includes/role_based_access.php';   // For permissions
require_once '../includes/emergency_mode.php';      // For emergencies
require_once '../includes/predictive_suggestions.php'; // For smart suggestions
```

---

## 📱 Common Usage Examples

### Claim Verification
```php
// Step 1: Create claim with random questions
$claim_id = createClaimWithQuestions($conn, $item_id, $user_id, 'lost', $description);

// Step 2: User answers questions
submitClaimAnswers($conn, $claim_id, $answer1, $answer2, $answer3);

// Step 3: Admin approves
verifyAndApproveClaim($conn, $claim_id, $admin_id, true);
```

### Award Points
```php
awardPoints($conn, $user_id, 10, 'Claim verified');
// Automatically updates leaderboard and reward level
```

### Send Message
```php
$msg_id = sendMessage($conn, $sender_id, $recipient_id, $message_text, $item_id);
// Automatically creates notification
```

### Update Heatmap
```php
updateLocationHeatmap($conn, $location, $hostel_id, 'lost');
// Called automatically when item reported
```

### Create Emergency Alert
```php
$alert_id = createEmergencyAlert($conn, $user_id, $item_id, $title, $description);
// Broadcasts to all users + sends SMS
```

### Check Permission
```php
requirePermission($_SESSION['role'], 'manage_users', '/resident/dashboard.php');
// Redirects if no permission
```

### Log Activity
```php
logActivity($conn, $user_id, "claim", $item_id, $claimant_id, $details);
```

---

## 🎯 Feature Integration Points

### When Item is Reported (Lost or Found):
1. ✅ Update location heatmap
2. ✅ Check for emergency items
3. ✅ Log activity
4. ✅ Create notifications
5. ✅ Check offline sync queue

### When Claim is Approved:
1. ✅ Award points to claimant
2. ✅ Update reward level
3. ✅ Log activity
4. ✅ Update item status
5. ✅ Send notifications

### When User Messages:
1. ✅ Create message record
2. ✅ Send notification to recipient
3. ✅ Mark as read when viewed
4. ✅ Track in activity logs

---

## 🔐 Permission System

### Roles Available:
- `resident` - Regular user
- `hostel_manager` - Manages hostel
- `security` - Security personnel
- `admin` - System admin

### Check Permission:
```php
if (hasPermission($_SESSION['role'], 'manage_users')) {
    // Show admin panel
}
```

---

## 📊 Analytics Functions

### Get Leaderboard
```php
$stmt = $conn->prepare("SELECT ... FROM rewards_leaderboard ORDER BY total_points DESC");
```

### Get Hotspots
```php
$hotspots = getLocationHotspots($conn, $hostel_id, 10);
```

### Get Recovery Rate
```php
$recovery = getRecoveryRateByLocation($conn, $hostel_id);
```

### Get Activity Log
```php
$logs = getActivity Logs($conn, $filter_type, 200);
```

---

## 🆘 Emergency Mode Usage

### Create Emergency
```php
$alert_id = createEmergencyAlert($conn, $user_id, $item_id, 
    "LOST: iPhone 14 - Very Important!",
    "Lost near library. Contains important documents. Reward offered."
);
```

### View Active Emergencies
```php
$alerts = getActiveEmergencyAlerts($conn, $hostel_id);
```

### Resolve Emergency
```php
resolveEmergencyAlert($conn, $alert_id);
```

---

## 📨 Offline Sync

### Save Form Offline
```javascript
// In JavaScript
const offlineData = {
    title: 'Lost Phone',
    description: 'iPhone 14 Pro',
    category: 'Electronics',
    location: 'Study Room',
    date_lost: '2026-04-24'
};
localStorage.setItem('pending_report', JSON.stringify(offlineData));
```

### Sync When Online
```php
$result = syncOfflineReports($conn, $user_id);
// Returns: ['synced' => 5, 'total' => 7, 'errors' => [], 'status' => 'partial']
```

---

## 🎮 Gamification

### Points System:
- **10 pts** - Claim verified
- **5 pts** - Successful return
- **1 pt** - Search/activity

### Reward Levels:
- 🌟 Novice (0-24 pts)
- ⭐ Helper (25-49 pts)
- 🏆 Good Samaritan (50-99 pts)
- 👑 Legend (100+ pts)

---

## 🏢 Multi-Hostel Management

### Create Hostel
```php
$hostel_id = createHostel($conn, 'Zest Hostel A', 'Block A', $manager_id);
```

### Get Hostel Stats
```php
$stats = getHostelStatistics($conn, $hostel_id);
// Returns: total_lost, total_found, recovered, claimed, active_residents
```

### Assign Manager
```php
assignHostelManager($conn, $hostel_id, $user_id);
```

---

## 🗂️ File Structure

```
lost_found/
├── includes/
│   ├── claim_verification.php ✓
│   ├── location_heatmap.php ✓
│   ├── messaging.php ✓
│   ├── auction_system.php ✓
│   ├── hostel_management.php ✓
│   ├── offline_sync.php ✓
│   ├── role_based_access.php ✓
│   ├── emergency_mode.php ✓
│   └── predictive_suggestions.php ✓
├── resident/
│   ├── claim_item.php ✓
│   ├── messages.php ✓
│   ├── leaderboard.php ✓
│   └── heatmap.php ✓
├── admin/
│   ├── review_claims.php ✓
│   ├── activity_logs.php ✓
│   └── emergency_alerts.php ✓
└── database_schema.sql ✓
```

---

## ⚠️ Important Notes

1. **Database Migration**: Must run updated schema.sql before testing
2. **Session Variables**: Ensure `hostel_id` set in `$_SESSION` for multi-tenant
3. **Permissions**: Check role before allowing actions
4. **Logging**: All important actions auto-logged via `logActivity()`
5. **SMS**: Currently logs locally, integrate Twilio/AWS for production

---

## 🧪 Testing Checklist

- [ ] Create lost item → Update heatmap
- [ ] Report found item → Create suggestion
- [ ] Claim item → Answer questions → Admin approve → Award points
- [ ] Send message → Recipient gets notification
- [ ] Item expires → Auto-flag → Create auction
- [ ] Mark high-value item → Create emergency alert
- [ ] Check leaderboard → See points/ranks
- [ ] View heatmap → See hotspots
- [ ] Check activity logs → See all actions
- [ ] Test offline reporting → Sync when online

---

Generated: April 24, 2026
