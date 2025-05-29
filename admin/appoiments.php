<?php
include("admin_db_c.php");

// Process form submissions and AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CREATE: Handle new appointment creation
    if (isset($_POST['action']) && $_POST['action'] === 'create_appointment') {
        // Collect appointment data
        $clientName = isset($_POST['client_name']) ? $_POST['client_name'] : '';
        $clientEmail = isset($_POST['client_email']) ? $_POST['client_email'] : '';
        $clientPhone = isset($_POST['client_phone']) ? $_POST['client_phone'] : '';
        $appointmentDate = isset($_POST['appointment_date']) ? $_POST['appointment_date'] : '';
        $appointmentTime = isset($_POST['appointment_time']) ? $_POST['appointment_time'] : '';
        $vehicleVin = isset($_POST['vehicle_vin']) ? $_POST['vehicle_vin'] : '';
        $services = isset($_POST['services']) ? implode(',', $_POST['services']) : '';
        $additionalInfo = isset($_POST['additional_info']) ? $_POST['additional_info'] : '';
        $status = 'pending'; // Default status for new appointments
        
        // Validate required fields
        if (empty($clientName) || empty($appointmentDate) || empty($appointmentTime) || empty($services)) {
            echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
            exit;
        }
        
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO appointments (client_name, client_email, client_phone, 
                               appointment_date, appointment_time, vehicle_vin, service, 
                               additional_info, status, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $stmt->bind_param("sssssssss", $clientName, $clientEmail, $clientPhone, $appointmentDate, 
                         $appointmentTime, $vehicleVin, $services, $additionalInfo, $status);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Appointment created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create appointment: ' . $conn->error]);
        }
        
        exit;
    }
    
    // DELETE: Handle appointment deletion
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_appointment' && isset($_POST['appointment_id'])) {
        $appointmentId = intval($_POST['appointment_id']);
        
        // Delete from database
        $stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
        $stmt->bind_param("i", $appointmentId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete appointment']);
        }
        
        exit;
    }
    
    // UPDATE: Handle appointment status updates
    elseif (isset($_POST['action']) && $_POST['action'] === 'update_status' && isset($_POST['appointment_id']) && isset($_POST['status'])) {
        $appointmentId = intval($_POST['appointment_id']);
        $newStatus = $_POST['status'];
        
        // Validate status value
        if (!in_array($newStatus, ['pending', 'confirmed', 'declined'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }
        
        // Update status in database
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
        $stmt->bind_param("si", $newStatus, $appointmentId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
        
        exit;
    }
    
    // VIEW: Handle appointment details request
    elseif (isset($_POST['action']) && $_POST['action'] === 'get_details' && isset($_POST['appointment_id'])) {
        $appointmentId = intval($_POST['appointment_id']);
        
        // Get appointment details
        $stmt = $conn->prepare("SELECT * FROM appointments WHERE appointment_id = ?");
        $stmt->bind_param("i", $appointmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $appointment = $result->fetch_assoc();
            
            // Format services
            $services = explode(',', $appointment['service']);
            $serviceNames = [
                'oil-change' => 'Oil Change',
                'brake-service' => 'Brake Service',
                'tire-replacement' => 'Tire Replacement',
                'engine-diagnostics' => 'Engine Diagnostics',
                'ac-service' => 'AC/Heating Service',
                'timing-chain' => 'Timing Chain Replacement',
                'timing-belt' => 'Timing Belt Replacement',
                'other' => 'Other Service'
            ];
            
            // Format date
            $date = new DateTime($appointment['appointment_date']);
            $formattedDate = $date->format('D, M j, Y');
            
            // Build HTML for appointment details
            echo '<input type="hidden" id="appointment-status" value="' . $appointment['status'] . '">';
            echo '<input type="hidden" id="appointment-id" value="' . $appointment['appointment_id'] . '">';
            
            echo '<div class="detail-row">
                <div class="detail-label">Client:</div>
                <div class="detail-value">' . htmlspecialchars($appointment['client_name']) . '</div>
            </div>';
            
            echo '<div class="detail-row">
                <div class="detail-label">Contact:</div>
                <div class="detail-value">';
            
            if (!empty($appointment['client_email'])) {
                echo htmlspecialchars($appointment['client_email']) . '<br>';
            } else {
                echo 'No email provided<br>';
            }
            
            if (!empty($appointment['client_phone'])) {
                echo htmlspecialchars($appointment['client_phone']);
            } else {
                echo 'No phone provided';
            }
            
            echo '</div>
            </div>';
            
            echo '<div class="detail-row">
                <div class="detail-label">Appointment:</div>
                <div class="detail-value">
                    ' . $formattedDate . ' at ' . $appointment['appointment_time'] . '
                </div>
            </div>';
            
            echo '<div class="detail-row">
                <div class="detail-label">Vehicle VIN:</div>
                <div class="detail-value">
                    ' . (!empty($appointment['vehicle_vin']) ? htmlspecialchars($appointment['vehicle_vin']) : 'Not provided') . '
                </div>
            </div>';
            
            echo '<div class="detail-row">
                <div class="detail-label">Services:</div>
                <div class="detail-value">
                    <ul class="services-list">';
            
            foreach ($services as $service) {
                $serviceName = isset($serviceNames[$service]) ? $serviceNames[$service] : $service;
                echo '<li>' . htmlspecialchars($serviceName) . '</li>';
            }
            
            echo '</ul>
                </div>
            </div>';
            
            if (!empty($appointment['additional_info'])) {
                echo '<div class="detail-row">
                    <div class="detail-label">Additional Info:</div>
                    <div class="detail-value">
                        ' . nl2br(htmlspecialchars($appointment['additional_info'])) . '
                    </div>
                </div>';
            }
            
            echo '<div class="detail-row">
                <div class="detail-label">Status:</div>
                <div class="detail-value">
                    <span class="status-' . $appointment['status'] . '">' . ucfirst($appointment['status']) . '</span>
                </div>
            </div>';
            
            echo '<div class="detail-row">
                <div class="detail-label">Submitted:</div>
                <div class="detail-value">
                    ' . date('M j, Y g:i A', strtotime($appointment['created_at'])) . '
                </div>
            </div>';
        } else {
            echo '<p>Appointment not found.</p>';
        }
        
        exit;
    }
}

// Fetch appointments based on filters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$serviceFilter = isset($_GET['service']) ? $_GET['service'] : '';
$searchTerm = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '';
$pendingCount = 0;

// Build the SQL query based on filters
$sql = "SELECT * FROM appointments WHERE 1=1";
$params = [];
$types = "";

// Add status filter if not 'all'
if ($statusFilter !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

// Add service filter if provided
if (!empty($serviceFilter)) {
    $sql .= " AND service LIKE ?";
    $params[] = '%' . $serviceFilter . '%';
    $types .= "s";
}

// Add search filter if provided
if (!empty($searchTerm)) {
    $sql .= " AND (client_name LIKE ? OR client_email LIKE ? OR client_phone LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
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

// Get pending count for notification badge
$countStmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE status = 'pending'");
$countStmt->execute();
$countResult = $countStmt->get_result();
$pendingCount = $countResult->fetch_row()[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management - Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .content {
            padding: 20px;
        }

        h1 {
            margin-bottom: 20px;
        }

        .top-controls {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            align-items: center;
        }

        .search-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            flex-grow: 1;
            max-width: 250px;
        }

        .btn-search {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-create {
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        .service-filter {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 10px;
        }

        .appointments-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 30px;
        }

        .appointments-table th, 
        .appointments-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .appointments-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .status-pending {
            color: orange;
            font-weight: bold;
        }
        
        .status-confirmed {
            color: green;
            font-weight: bold;
        }
        
        .status-declined {
            color: red;
            font-weight: bold;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }

        .btn-confirm {
            background-color: green;
            color: white;
        }

        .btn-decline {
            background-color: red;
            color: white;
        }
        
        .btn-details {
            background-color: #3498db;
            color: white;
        }

        .btn-delete {
            background-color: #e74c3c;
            color: white;
        }

        .no-appointments {
            text-align: center;
            color: #666;
            padding: 20px;
            background-color: white;
            margin-top: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 80%;
            max-width: 800px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .modal-close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .modal-close:hover {
            color: black;
        }
        
        .appointment-details {
            margin-top: 20px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .detail-label {
            font-weight: bold;
            width: 150px;
        }
        
        .detail-value {
            flex: 1;
        }
        
        .services-list {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        
        .services-list li {
            padding: 3px 0;
        }
        
        .modal-actions {
            margin-top: 20px;
            text-align: right;
        }
        
        /* Form styles */
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .service-checkbox {
            margin-right: 10px;
        }
        
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 5px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .checkbox-item input {
            margin-right: 5px;
        }
        
        /* Tabs for filtering appointments */
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            margin-right: 5px;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            background-color: #f8f8f8;
        }
        
        .tab.active {
            background-color: white;
            border-bottom: 1px solid white;
            margin-bottom: -1px;
            font-weight: bold;
        }
        
        /* Notification badge */
        .notification-badge {
            display: inline-block;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div id="navbar_admin.html"></div>

    <div class="content">
        <h1>Appointment Management</h1>
        
        <div class="top-controls">
            <div class="search-container">
                <select class="service-filter" id="service-filter">
                    <option value="">All Services</option>
                    <option value="oil-change" <?php echo $serviceFilter === 'oil-change' ? 'selected' : ''; ?>>Oil Change</option>
                    <option value="brake-service" <?php echo $serviceFilter === 'brake-service' ? 'selected' : ''; ?>>Brake Service</option>
                    <option value="tire-replacement" <?php echo $serviceFilter === 'tire-replacement' ? 'selected' : ''; ?>>Tire Replacement</option>
                    <option value="engine-diagnostics" <?php echo $serviceFilter === 'engine-diagnostics' ? 'selected' : ''; ?>>Engine Diagnostics</option>
                    <option value="ac-service" <?php echo $serviceFilter === 'ac-service' ? 'selected' : ''; ?>>AC/Heating Service</option>
                    <option value="timing-chain" <?php echo $serviceFilter === 'timing-chain' ? 'selected' : ''; ?>>Timing Chain Replacement</option>
                    <option value="timing-belt" <?php echo $serviceFilter === 'timing-belt' ? 'selected' : ''; ?>>Timing Belt Replacement</option>
                    <option value="other" <?php echo $serviceFilter === 'other' ? 'selected' : ''; ?>>Other Service</option>
                </select>
                <input type="text" id="search-input" class="search-input" placeholder="Search by name, email, phone..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button id="search-btn" class="btn btn-search">Search</button>
            </div>
            <button class="btn-create" onclick="showCreateModal()">Create New Appointment</button>
        </div>
        
        <div class="tabs">
            <a href="?status=pending<?php echo !empty($serviceFilter) ? '&service=' . $serviceFilter : ''; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode(trim($_GET['search'])) : ''; ?>" class="tab <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
                Pending <span class="notification-badge"><?php echo $pendingCount; ?></span>
            </a>
            <a href="?status=confirmed<?php echo !empty($serviceFilter) ? '&service=' . $serviceFilter : ''; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode(trim($_GET['search'])) : ''; ?>" class="tab <?php echo $statusFilter === 'confirmed' ? 'active' : ''; ?>">
                Confirmed
            </a>
            <a href="?status=declined<?php echo !empty($serviceFilter) ? '&service=' . $serviceFilter : ''; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode(trim($_GET['search'])) : ''; ?>" class="tab <?php echo $statusFilter === 'declined' ? 'active' : ''; ?>">
                Declined
            </a>
            <a href="?status=all<?php echo !empty($serviceFilter) ? '&service=' . $serviceFilter : ''; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode(trim($_GET['search'])) : ''; ?>" class="tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
                All Appointments
            </a>
        </div>
        
        <?php if ($result && $result->num_rows > 0): ?>
            <table class="appointments-table" id="appointments-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Client</th>
                        <th>Services</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="appointments-list">
                    <?php while ($appointment = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php 
                                    $date = new DateTime($appointment['appointment_date']);
                                    echo $date->format('D, M j, Y') . ' at ' . $appointment['appointment_time']; 
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($appointment['client_name']); ?></td>
                            <td>
                                <?php 
                                    $services = explode(',', $appointment['service']);
                                    $serviceNames = [
                                        'oil-change' => 'Oil Change',
                                        'brake-service' => 'Brake Service',
                                        'tire-replacement' => 'Tire Replacement',
                                        'engine-diagnostics' => 'Engine Diagnostics',
                                        'ac-service' => 'AC/Heating Service',
                                        'timing-chain' => 'Timing Chain Replacement',
                                        'timing-belt' => 'Timing Belt Replacement',
                                        'other' => 'Other Service'
                                    ];
                                    
                                    $displayServices = [];
                                    foreach ($services as $service) {
                                        $displayServices[] = isset($serviceNames[$service]) 
                                            ? $serviceNames[$service] 
                                            : $service;
                                    }
                                    
                                    if (count($displayServices) <= 2) {
                                        echo implode(', ', $displayServices);
                                    } else {
                                        echo $displayServices[0] . ', ' . $displayServices[1] . ' and ' . (count($displayServices) - 2) . ' more';
                                    }
                                ?>
                            </td>
                            <td class="status-<?php echo $appointment['status']; ?>">
                                <?php echo ucfirst($appointment['status']); ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($appointment['status'] === 'pending'): ?>
                                        <button class="btn btn-confirm" 
                                                onclick="updateStatus(<?php echo $appointment['appointment_id']; ?>, 'confirmed')">
                                            Confirm
                                        </button>
                                        <button class="btn btn-decline" 
                                                onclick="updateStatus(<?php echo $appointment['appointment_id']; ?>, 'declined')">
                                            Decline
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-details" 
                                            onclick="showDetails(<?php echo $appointment['appointment_id']; ?>)">
                                        Details
                                    </button>
                                    <button class="btn btn-delete" 
                                            onclick="confirmDelete(<?php echo $appointment['appointment_id']; ?>)">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-appointments">
                <p>No appointment requests found matching your criteria.</p>
            </div>
        <?php endif; ?>
        
        <!-- Appointment Details Modal -->
        <div id="appointment-modal" class="modal">
            <div class="modal-content">
                <span class="modal-close" onclick="closeModal('appointment-modal')">&times;</span>
                <h2>Appointment Details</h2>
                
                <div class="appointment-details" id="appointment-details">
                    <!-- Details will be loaded via AJAX -->
                </div>
                
                <div class="modal-actions" id="modal-actions">
                    <!-- Action buttons will be added via JavaScript -->
                </div>
            </div>
        </div>
        
        <!-- Create Appointment Modal -->
        <div id="create-modal" class="modal">
            <div class="modal-content">
                <span class="modal-close" onclick="closeModal('create-modal')">&times;</span>
                <h2>Create New Appointment</h2>
                
                <form id="create-appointment-form">
                    <div class="form-group">
                        <label for="client_name">Client Name *</label>
                        <input type="text" id="client_name" name="client_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="client_email">Client Email</label>
                        <input type="email" id="client_email" name="client_email" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="client_phone">Client Phone</label>
                        <input type="text" id="client_phone" name="client_phone" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="appointment_date">Appointment Date *</label>
                        <input type="date" id="appointment_date" name="appointment_date" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="appointment_time">Appointment Time *</label>
                        <input type="time" id="appointment_time" name="appointment_time" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="vehicle_vin">Vehicle VIN</label>
                        <input type="text" id="vehicle_vin" name="vehicle_vin" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Services *</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="oil-change" name="services[]" value="oil-change" class="service-checkbox">
                                <label for="oil-change">Oil Change</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="brake-service" name="services[]" value="brake-service" class="service-checkbox">
                                <label for="brake-service">Brake Service</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="tire-replacement" name="services[]" value="tire-replacement" class="service-checkbox">
                                <label for="tire-replacement">Tire Replacement</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="engine-diagnostics" name="services[]" value="engine-diagnostics" class="service-checkbox">
                                <label for="engine-diagnostics">Engine Diagnostics</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="ac-service" name="services[]" value="ac-service" class="service-checkbox">
                                <label for="ac-service">AC/Heating Service</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="timing-chain" name="services[]" value="timing-chain" class="service-checkbox">
                                <label for="timing-chain">Timing Chain Replacement</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="timing-belt" name="services[]" value="timing-belt" class="service-checkbox">
                                <label for="timing-belt">Timing Belt Replacement</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="other" name="services[]" value="other" class="service-checkbox">
                                <label for="other">Other Service</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="additional_info">Additional Information</label>
                        <textarea id="additional_info" name="additional_info" class="form-control" rows="4"></textarea>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn" onclick="closeModal('create-modal')">Cancel</button>
                        <button type="submit" class="btn btn-confirm">Create Appointment</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Delete Confirmation Modal -->
        <div id="delete-modal" class="modal">
            <div class="modal-content">
                <span class="modal-close" onclick="closeModal('delete-modal')">&times;</span>
                <h2>Confirm Deletion</h2>
                
                <p>Are you sure you want to delete this appointment? This action cannot be undone.</p>
                
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
            
            // Set up create appointment form submission
            document.getElementById('create-appointment-form').addEventListener('submit', function(e) {
                e.preventDefault();
                createAppointment();
            });
        });
        
        // Function to perform search based on inputs
        function performSearch() {
            const searchTerm = document.getElementById('search-input').value.trim();
            const serviceFilter = document.getElementById('service-filter').value;
            const currentStatus = new URLSearchParams(window.location.search).get('status') || 'pending';
            
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

        // Function to update appointment status
        function updateStatus(appointmentId, newStatus) {
            // Create form data
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('appointment_id', appointmentId);
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
                    alert('Failed to update appointment status: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update appointment status. Please try again.');
            });
        }
        
        // Function to show appointment details
        function showDetails(appointmentId) {
            // Show the modal
            const modal = document.getElementById('appointment-modal');
            modal.style.display = 'block';
            
            // Create form data for POST
            const formData = new FormData();
            formData.append('action', 'get_details');
            formData.append('appointment_id', appointmentId);
            
            // Send AJAX request to get details
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                document.getElementById('appointment-details').innerHTML = html;
                
                // Set action buttons based on status
                const status = document.getElementById('appointment-status').value;
                const appointmentId = document.getElementById('appointment-id').value;
                const actionsContainer = document.getElementById('modal-actions');
                
                if (status === 'pending') {
                    actionsContainer.innerHTML = `
                        <button class="btn btn-confirm" onclick="updateStatus(${appointmentId}, 'confirmed')">
                            Confirm Appointment
                        </button>
                        <button class="btn btn-decline" onclick="updateStatus(${appointmentId}, 'declined')">
                            Decline Appointment
                        </button>
                        <button class="btn btn-delete" onclick="confirmDelete(${appointmentId})">
                            Delete Appointment
                        </button>
                    `;
                } else {
                    actionsContainer.innerHTML = `
                        <button class="btn" onclick="closeModal('appointment-modal')">Close</button>
                        <button class="btn btn-delete" onclick="confirmDelete(${appointmentId})">
                            Delete Appointment
                        </button>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('appointment-details').innerHTML = 
                    '<p>Failed to load appointment details. Please try again.</p>';
            });
        }
        
        // Function to show create appointment modal
        function showCreateModal() {
            document.getElementById('create-modal').style.display = 'block';
            document.getElementById('create-appointment-form').reset();
            
            // Set default date to today
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            document.getElementById('appointment_date').value = `${yyyy}-${mm}-${dd}`;
        }
        
        // Function to create a new appointment
        function createAppointment() {
            const form = document.getElementById('create-appointment-form');
            
            // Basic validation
            const requiredFields = ['client_name', 'appointment_date', 'appointment_time'];
            for (const field of requiredFields) {
                if (!form.elements[field].value.trim()) {
                    alert('Please fill in all required fields.');
                    return;
                }
            }
            
            // Check if at least one service is selected
            const serviceCheckboxes = form.querySelectorAll('input[name="services[]"]:checked');
            if (serviceCheckboxes.length === 0) {
                alert('Please select at least one service.');
                return;
            }
            
            // Create form data
            const formData = new FormData(form);
            formData.append('action', 'create_appointment');
            
            // Send AJAX request
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal and reload to show new appointment
                    closeModal('create-modal');
                    window.location.reload();
                } else {
                    alert('Failed to create appointment: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to create appointment. Please try again.');
            });
        }
        
        // Function to confirm deletion
        function confirmDelete(appointmentId) {
            const deleteModal = document.getElementById('delete-modal');
            deleteModal.style.display = 'block';
            
            // Set up the delete confirmation button
            document.getElementById('confirm-delete-btn').onclick = function() {
                deleteAppointment(appointmentId);
            };
        }
        
        // Function to delete appointment
        function deleteAppointment(appointmentId) {
            // Create form data
            const formData = new FormData();
            formData.append('action', 'delete_appointment');
            formData.append('appointment_id', appointmentId);
            
            // Send AJAX request
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modals and reload to update the list
                    closeModal('delete-modal');
                    closeModal('appointment-modal');
                    window.location.reload();
                } else {
                    alert('Failed to delete appointment: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete appointment. Please try again.');
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