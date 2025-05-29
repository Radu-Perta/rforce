<?php
include("admin_db_c.php");

// Process form submissions and AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CREATE: Handle new coupon creation
    if (isset($_POST['action']) && $_POST['action'] === 'create_coupon') {
        // Collect coupon data
        $code = isset($_POST['code']) ? strtoupper(trim($_POST['code'])) : '';
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        $discountAmount = isset($_POST['discount_amount']) ? floatval($_POST['discount_amount']) : 0;
        $discountType = isset($_POST['discount_type']) ? $_POST['discount_type'] : 'percentage';
        $validFrom = isset($_POST['valid_from']) ? $_POST['valid_from'] : date('Y-m-d');
        $validTo = isset($_POST['valid_to']) ? $_POST['valid_to'] : '';
        $serviceType = isset($_POST['service_type']) ? $_POST['service_type'] : 'all';
        $usageLimit = isset($_POST['usage_limit']) ? intval($_POST['usage_limit']) : 0;
        $minimumSpend = isset($_POST['minimum_spend']) ? floatval($_POST['minimum_spend']) : 0;
        $status = isset($_POST['status']) ? $_POST['status'] : 'active';
        
        // Validate required fields
        if (empty($code) || empty($description) || $discountAmount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Required fields are missing or invalid']);
            exit;
        }
        
        // Check if coupon code already exists
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM coupons WHERE code = ?");
        $checkStmt->bind_param("s", $code);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $codeExists = $checkResult->fetch_row()[0] > 0;
        
        if ($codeExists) {
            echo json_encode(['success' => false, 'message' => 'Coupon code already exists']);
            exit;
        }
        
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO coupons (code, description, discount_amount, discount_type, 
                               valid_from, valid_to, service_type, usage_limit, minimum_spend, 
                               status, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $stmt->bind_param("ssdsssidss", $code, $description, $discountAmount, $discountType, 
                         $validFrom, $validTo, $serviceType, $usageLimit, $minimumSpend, $status);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Coupon created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create coupon: ' . $conn->error]);
        }
        
        exit;
    }
    
    // UPDATE: Handle coupon updates
    elseif (isset($_POST['action']) && $_POST['action'] === 'update_coupon') {
        // Collect coupon data
        $couponId = isset($_POST['coupon_id']) ? intval($_POST['coupon_id']) : 0;
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        $discountAmount = isset($_POST['discount_amount']) ? floatval($_POST['discount_amount']) : 0;
        $discountType = isset($_POST['discount_type']) ? $_POST['discount_type'] : 'percentage';
        $validFrom = isset($_POST['valid_from']) ? $_POST['valid_from'] : date('Y-m-d');
        $validTo = isset($_POST['valid_to']) ? $_POST['valid_to'] : '';
        $serviceType = isset($_POST['service_type']) ? $_POST['service_type'] : 'all';
        $usageLimit = isset($_POST['usage_limit']) ? intval($_POST['usage_limit']) : 0;
        $minimumSpend = isset($_POST['minimum_spend']) ? floatval($_POST['minimum_spend']) : 0;
        $status = isset($_POST['status']) ? $_POST['status'] : 'active';
        
        // Validate required fields
        if ($couponId <= 0 || empty($description) || $discountAmount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Required fields are missing or invalid']);
            exit;
        }
        
        // Update in database
        $stmt = $conn->prepare("UPDATE coupons SET description = ?, discount_amount = ?, 
                               discount_type = ?, valid_from = ?, valid_to = ?, service_type = ?, 
                               usage_limit = ?, minimum_spend = ?, status = ?, updated_at = NOW() 
                               WHERE coupon_id = ?");
        
        $stmt->bind_param("sdsssidssi", $description, $discountAmount, $discountType, 
                         $validFrom, $validTo, $serviceType, $usageLimit, $minimumSpend, 
                         $status, $couponId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Coupon updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update coupon: ' . $conn->error]);
        }
        
        exit;
    }
    
    // DELETE: Handle coupon deletion
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_coupon' && isset($_POST['coupon_id'])) {
        $couponId = intval($_POST['coupon_id']);
        
        // Delete from database
        $stmt = $conn->prepare("DELETE FROM coupons WHERE coupon_id = ?");
        $stmt->bind_param("i", $couponId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete coupon']);
        }
        
        exit;
    }
    
    // Toggle coupon status (activate/deactivate)
    elseif (isset($_POST['action']) && $_POST['action'] === 'toggle_status' && isset($_POST['coupon_id']) && isset($_POST['status'])) {
        $couponId = intval($_POST['coupon_id']);
        $newStatus = $_POST['status'] === 'active' ? 'active' : 'inactive';
        
        // Update status in database
        $stmt = $conn->prepare("UPDATE coupons SET status = ? WHERE coupon_id = ?");
        $stmt->bind_param("si", $newStatus, $couponId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update coupon status']);
        }
        
        exit;
    }
    
    // Get coupon details for editing
    elseif (isset($_POST['action']) && $_POST['action'] === 'get_coupon_details' && isset($_POST['coupon_id'])) {
        $couponId = intval($_POST['coupon_id']);
        
        // Get coupon details from database
        $stmt = $conn->prepare("SELECT * FROM coupons WHERE coupon_id = ?");
        $stmt->bind_param("i", $couponId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $coupon = $result->fetch_assoc();
            echo json_encode(['success' => true, 'coupon' => $coupon]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Coupon not found']);
        }
        
        exit;
    }
}

// Set up database table if it doesn't exist
function setupCouponsTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS coupons (
        coupon_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        description TEXT NOT NULL,
        discount_amount DECIMAL(10,2) NOT NULL,
        discount_type ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
        valid_from DATE NOT NULL,
        valid_to DATE,
        service_type VARCHAR(100) DEFAULT 'all',
        usage_limit INT(11) DEFAULT 0,
        usage_count INT(11) DEFAULT 0,
        minimum_spend DECIMAL(10,2) DEFAULT 0,
        status ENUM('active', 'inactive', 'expired') NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL,
        updated_at DATETIME
    )";
    
    if ($conn->query($sql) !== TRUE) {
        echo "Error creating table: " . $conn->error;
    }
}

// Initialize the coupons table
setupCouponsTable($conn);

// Fetch coupons based on filters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$serviceFilter = isset($_GET['service']) ? $_GET['service'] : '';
$searchTerm = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '';

// Build the SQL query based on filters
$sql = "SELECT * FROM coupons WHERE 1=1";
$params = [];
$types = "";

// Add status filter if not 'all'
if ($statusFilter !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

// Add service filter if provided
if (!empty($serviceFilter) && $serviceFilter !== 'all') {
    $sql .= " AND (service_type = ? OR service_type = 'all')";
    $params[] = $serviceFilter;
    $types .= "s";
}

// Add search filter if provided
if (!empty($searchTerm)) {
    $sql .= " AND (code LIKE ? OR description LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

// Add order by
$sql .= " ORDER BY created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get active coupon count for notification badge
$countStmt = $conn->prepare("SELECT COUNT(*) FROM coupons WHERE status = 'active'");
$countStmt->execute();
$countResult = $countStmt->get_result();
$activeCouponsCount = $countResult->fetch_row()[0];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupons Management - Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="navbar_admin.html"></div>

    <div class="content">
        <h1>Coupons Management</h1>
        
        <div class="top-controls">
            <div class="search-container">
                <select class="service-filter" id="service-filter">
                    <option value="all">All Services</option>
                    <option value="oil-change" <?php echo $serviceFilter === 'oil-change' ? 'selected' : ''; ?>>Oil Change</option>
                    <option value="brake-service" <?php echo $serviceFilter === 'brake-service' ? 'selected' : ''; ?>>Brake Service</option>
                    <option value="tire-replacement" <?php echo $serviceFilter === 'tire-replacement' ? 'selected' : ''; ?>>Tire Replacement</option>
                    <option value="engine-diagnostics" <?php echo $serviceFilter === 'engine-diagnostics' ? 'selected' : ''; ?>>Engine Diagnostics</option>
                    <option value="ac-service" <?php echo $serviceFilter === 'ac-service' ? 'selected' : ''; ?>>AC/Heating Service</option>
                    <option value="timing-chain" <?php echo $serviceFilter === 'timing-chain' ? 'selected' : ''; ?>>Timing Chain Replacement</option>
                    <option value="timing-belt" <?php echo $serviceFilter === 'timing-belt' ? 'selected' : ''; ?>>Timing Belt Replacement</option>
                    <option value="other" <?php echo $serviceFilter === 'other' ? 'selected' : ''; ?>>Other Service</option>
                </select>
                <input type="text" id="search-input" class="search-input" placeholder="Search by code or description..." value="<?php echo isset($_GET['search']) ? htmlspecialchars(trim($_GET['search'], '%')) : ''; ?>">
                <button id="search-btn" class="btn btn-search">Search</button>
            </div>
            <button class="btn-create" onclick="showCreateModal()">Create New Coupon</button>
        </div>
        
        <div class="tabs">
            <a href="?status=all<?php echo !empty($serviceFilter) ? '&service=' . $serviceFilter : ''; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode(trim($_GET['search'], '%')) : ''; ?>" class="tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
                All Coupons
            </a>
            <a href="?status=active<?php echo !empty($serviceFilter) ? '&service=' . $serviceFilter : ''; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode(trim($_GET['search'], '%')) : ''; ?>" class="tab <?php echo $statusFilter === 'active' ? 'active' : ''; ?>">
                Active <span class="notification-badge"><?php echo $activeCouponsCount; ?></span>
            </a>
            <a href="?status=inactive<?php echo !empty($serviceFilter) ? '&service=' . $serviceFilter : ''; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode(trim($_GET['search'], '%')) : ''; ?>" class="tab <?php echo $statusFilter === 'inactive' ? 'active' : ''; ?>">
                Inactive
            </a>
            <a href="?status=expired<?php echo !empty($serviceFilter) ? '&service=' . $serviceFilter : ''; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode(trim($_GET['search'], '%')) : ''; ?>" class="tab <?php echo $statusFilter === 'expired' ? 'active' : ''; ?>">
                Expired
            </a>
        </div>
        
        <?php if ($result && $result->num_rows > 0): ?>
            <table class="coupons-table" id="coupons-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Description</th>
                        <th>Discount</th>
                        <th>Service</th>
                        <th>Valid Period</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="coupons-list">
                    <?php while ($coupon = $result->fetch_assoc()): ?>
                        <?php 
                            // Check if coupon is expired based on valid_to date
                            $isExpired = false;
                            if (!empty($coupon['valid_to'])) {
                                $validTo = new DateTime($coupon['valid_to']);
                                $today = new DateTime();
                                if ($validTo < $today) {
                                    $isExpired = true;
                                }
                            }
                            
                            // Update status to 'expired' if needed
                            if ($isExpired && $coupon['status'] !== 'expired') {
                                $updateStmt = $conn->prepare("UPDATE coupons SET status = 'expired' WHERE coupon_id = ?");
                                $updateStmt->bind_param("i", $coupon['coupon_id']);
                                $updateStmt->execute();
                                $coupon['status'] = 'expired';
                            }
                        ?>
                        <tr>
                            <td><span class="coupon-code"><?php echo htmlspecialchars($coupon['code']); ?></span></td>
                            <td><?php echo htmlspecialchars($coupon['description']); ?></td>
                            <td>
                                <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                    <?php echo $coupon['discount_amount']; ?>%
                                <?php else: ?>
                                    $<?php echo number_format($coupon['discount_amount'], 2); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    $serviceTypes = [
                                        'all' => 'All Services',
                                        'oil-change' => 'Oil Change',
                                        'brake-service' => 'Brake Service',
                                        'tire-replacement' => 'Tire Replacement',
                                        'engine-diagnostics' => 'Engine Diagnostics',
                                        'ac-service' => 'AC/Heating Service',
                                        'timing-chain' => 'Timing Chain Replacement',
                                        'timing-belt' => 'Timing Belt Replacement',
                                        'other' => 'Other Service'
                                    ];
                                    
                                    echo isset($serviceTypes[$coupon['service_type']]) 
                                        ? $serviceTypes[$coupon['service_type']] 
                                        : $coupon['service_type'];
                                ?>
                            </td>
                            <td>
                                <?php 
                                    $validFrom = new DateTime($coupon['valid_from']);
                                    echo $validFrom->format('M j, Y');
                                    
                                    if (!empty($coupon['valid_to'])) {
                                        $validTo = new DateTime($coupon['valid_to']);
                                        echo ' to ' . $validTo->format('M j, Y');
                                    } else {
                                        echo ' onwards';
                                    }
                                ?>
                            </td>
                            <td class="status-<?php echo $coupon['status']; ?>">
                                <?php echo ucfirst($coupon['status']); ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($coupon['status'] === 'inactive'): ?>
                                        <button class="btn btn-activate" 
                                                onclick="toggleStatus(<?php echo $coupon['coupon_id']; ?>, 'active')">
                                            Activate
                                        </button>
                                    <?php elseif ($coupon['status'] === 'active'): ?>
                                        <button class="btn btn-deactivate" 
                                                onclick="toggleStatus(<?php echo $coupon['coupon_id']; ?>, 'inactive')">
                                            Deactivate
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-edit" 
                                            onclick="showEditModal(<?php echo $coupon['coupon_id']; ?>)">
                                        Edit
                                    </button>
                                    <button class="btn btn-delete" 
                                            onclick="confirmDelete(<?php echo $coupon['coupon_id']; ?>)">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-coupons">
                <p>No coupons found matching your criteria.</p>
            </div>
        <?php endif; ?>
        
        <!-- Create/Edit Coupon Modal -->
        <div id="coupon-modal" class="modal">
            <div class="modal-content">
                <span class="modal-close" onclick="closeModal('coupon-modal')">&times;</span>
                <h2 id="modal-title">Create New Coupon</h2>
                
                <form id="coupon-form">
                    <input type="hidden" id="coupon_id" name="coupon_id" value="0">
                    <input type="hidden" id="form_action" name="action" value="create_coupon">
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="code">Coupon Code *</label>
                                <input type="text" id="code" name="code" class="form-control" required placeholder="e.g. SUMMER25">
                                <div class="help-text">Unique identifier for the coupon. Letters and numbers only, no spaces.</div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description *</label>
                        <input type="text" id="description" name="description" class="form-control" required placeholder="e.g. 25% off summer services">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="discount_amount">Discount Amount *</label>
                                <input type="number" id="discount_amount" name="discount_amount" class="form-control" required min="0" step="0.01">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="discount_type">Discount Type</label>
                                <select id="discount_type" name="discount_type" class="form-control">
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="fixed">Fixed Amount ($)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="valid_from">Valid From *</label>
                                <input type="date" id="valid_from" name="valid_from" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="valid_to">Valid To</label>
                                <input type="date" id="valid_to" name="valid_to" class="form-control">
                                <div class="help-text">Leave empty for no expiration date</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="service_type">Applicable Service</label>
                                <select id="service_type" name="service_type" class="form-control">
                                    <option value="all">All Services</option>
                                    <option value="oil-change">Oil Change</option>
                                    <option value="brake-service">Brake Service</option>
                                    <option value="tire-replacement">Tire Replacement</option>
                                    <option value="engine-diagnostics">Engine Diagnostics</option>
                                    <option value="ac-service">AC/Heating Service</option>
                                    <option value="timing-chain">Timing Chain Replacement</option>
                                    <option value="timing-belt">Timing Belt Replacement</option>
                                    <option value="other">Other Service</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="minimum_spend">Minimum Spend ($)</label>
                                <input type="number" id="minimum_spend" name="minimum_spend" class="form-control" min="0" step="0.01" value="0">
                                <div class="help-text">Minimum order value required to use this coupon</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="usage_limit">Usage Limit</label>
                        <input type="number" id="usage_limit" name="usage_limit" class="form-control" min="0" step="1" value="0">
                        <div class="help-text">Maximum number of times this coupon can be used (0 = unlimited)</div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn" onclick="closeModal('coupon-modal')">Cancel</button>
                        <button type="submit" class="btn btn-confirm" id="submit-btn">Create Coupon</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Delete Confirmation Modal -->
        <div id="delete-modal" class="modal">
            <div class="modal-content">
                <span class="modal-close" onclick="closeModal('delete-modal')">&times;</span>
                <h2>Confirm Deletion</h2>
                
                <p>Are you sure you want to delete this coupon? This action cannot be undone.</p>
                
                <div class="modal-actions">
                    <button type="button" class="btn" onclick="closeModal('delete-modal')">Cancel</button>
                    <button type="button" class="btn btn-delete" id="confirm-delete-btn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fetch the navbar.html file
            fetch('navbar_admin.html')
                .then(response => response.text())
                .then(data => {
                    // Insert the navbar HTML
                    document.getElementById('navbar_admin.html').innerHTML = data;
                })
                .catch(error => console.error('Error loading navbar:', error));
                
            // Set up search functionality
            document.getElementById('search-btn').addEventListener('click', function() {
                performSearch();
            });
            
            document.getElementById('search-input').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
            
            // Set up service filter
            document.getElementById('service-filter').addEventListener('change', function() {
                performSearch();
            });
            
            // Set up coupon form submission
            document.getElementById('coupon-form').addEventListener('submit', function(e) {
                e.preventDefault();
                submitCouponForm();
            });
            
            // Set default date to today for create form
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            
            // Format today's date for date inputs
            const formattedDate = `${yyyy}-${mm}-${dd}`;
            
            // Set default date value
            document.getElementById('valid_from').value = formattedDate;
        });
        
        // Function to perform search based on inputs
        function performSearch() {
            const searchTerm = document.getElementById('search-input').value.trim();
            const serviceFilter = document.getElementById('service-filter').value;
            const currentStatus = new URLSearchParams(window.location.search).get('status') || 'all';
            
            // Build query string
            let queryString = `?status=${currentStatus}`;
            
            if (serviceFilter) {
                queryString += `&service=${serviceFilter}`;
            }
            
            if (searchTerm) {
                queryString += `&search=${encodeURIComponent(searchTerm)}`;
            }
            
            // Redirect to the same page with new query parameters
            window.location.href = window.location.pathname + queryString;
        }
        
        // Function to show the create modal
        function showCreateModal() {
            // Reset form
            document.getElementById('coupon-form').reset();
            document.getElementById('coupon_id').value = '0';
            document.getElementById('form_action').value = 'create_coupon';
            document.getElementById('modal-title').textContent = 'Create New Coupon';
            document.getElementById('submit-btn').textContent = 'Create Coupon';
            
            // Enable code field for new coupons
            document.getElementById('code').readOnly = false;
            
            // Set default date to today
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            document.getElementById('valid_from').value = `${yyyy}-${mm}-${dd}`;
            
            // Show modal
            document.getElementById('coupon-modal').style.display = 'block';
        }
        
        // Function to show the edit modal
        function showEditModal(couponId) {
            // Reset form
            document.getElementById('coupon-form').reset();
            
            // Set form action and title
            document.getElementById('coupon_id').value = couponId;
            document.getElementById('form_action').value = 'update_coupon';
            document.getElementById('modal-title').textContent = 'Edit Coupon';
            document.getElementById('submit-btn').textContent = 'Update Coupon';
            
            // Fetch coupon details
            const formData = new FormData();
            formData.append('action', 'get_coupon_details');
            formData.append('coupon_id', couponId);
            
            // Send AJAX request
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const coupon = data.coupon;
                    
                    // Populate form fields
                    document.getElementById('code').value = coupon.code;
                    document.getElementById('code').readOnly = true; // Don't allow editing the code
                    document.getElementById('description').value = coupon.description;
                    document.getElementById('discount_amount').value = coupon.discount_amount;
                    document.getElementById('discount_type').value = coupon.discount_type;
                    document.getElementById('valid_from').value = coupon.valid_from;
                    document.getElementById('valid_to').value = coupon.valid_to || '';
                    document.getElementById('service_type').value = coupon.service_type;
                    document.getElementById('usage_limit').value = coupon.usage_limit;
                    document.getElementById('minimum_spend').value = coupon.minimum_spend;
                    document.getElementById('status').value = coupon.status;
                    
                    // Show modal
                    document.getElementById('coupon-modal').style.display = 'block';
                } else {
                    alert('Failed to load coupon details: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load coupon details. Please try again.');
            });
        }
        
        // Function to submit the coupon form (create or update)
        function submitCouponForm() {
            const form = document.getElementById('coupon-form');
            const action = document.getElementById('form_action').value;
            
            // Basic validation
            const requiredFields = ['code', 'description', 'discount_amount', 'valid_from'];
            for (const field of requiredFields) {
                if (!form.elements[field].value.trim()) {
                    alert('Please fill in all required fields.');
                    return;
                }
            }
            
            // Validate coupon code format
            if (action === 'create_coupon') {
                const codeField = document.getElementById('code');
                const codeValue = codeField.value.trim();
                const codeRegex = /^[A-Z0-9]+$/;
                
                if (!codeRegex.test(codeValue)) {
                    alert('Coupon code should contain only uppercase letters and numbers without spaces.');
                    codeField.focus();
                    return;
                }
            }
            
            // Create form data
            const formData = new FormData(form);
            
            // Send AJAX request
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal and reload to show new/updated coupon
                    closeModal('coupon-modal');
                    window.location.reload();
                } else {
                    alert('Failed to ' + (action === 'create_coupon' ? 'create' : 'update') + ' coupon: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to submit form. Please try again.');
            });
        }
        
        // Function to toggle coupon status
        function toggleStatus(couponId, newStatus) {
            // Create form data
            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('coupon_id', couponId);
            formData.append('status', newStatus);
            
            // Send AJAX request
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to show updated data
                    window.location.reload();
                } else {
                    alert('Failed to update coupon status: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update coupon status. Please try again.');
            });
        }
        
        // Function to confirm deletion
        function confirmDelete(couponId) {
            const deleteModal = document.getElementById('delete-modal');
            deleteModal.style.display = 'block';
            
            // Set up the delete confirmation button
            document.getElementById('confirm-delete-btn').onclick = function() {
                deleteCoupon(couponId);
            };
        }
        
        // Function to delete coupon
        function deleteCoupon(couponId) {
            // Create form data
            const formData = new FormData();
            formData.append('action', 'delete_coupon');
            formData.append('coupon_id', couponId);
            
            // Send AJAX request
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal and reload to update the list
                    closeModal('delete-modal');
                    window.location.reload();
                } else {
                    alert('Failed to delete coupon: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete coupon. Please try again.');
            });
        }
        
        // Function to close a modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (const modal of modals) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            }
        };
    </script>
</body>
</html>