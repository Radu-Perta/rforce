<?php
// Include your database connection file
// Replace with your actual database connection file
include_once 'your_db_connection.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if ID parameter is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'Appointment ID is required']);
    exit;
}

// Get appointment ID
$appointmentId = intval($_GET['id']);

// Query to get appointment details
$stmt = $conn->prepare("SELECT * FROM appointments WHERE appointment_id = ?");
$stmt->bind_param("i", $appointmentId);
$stmt->execute();
$result = $stmt->get_result();

// Check if appointment exists
if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Appointment not found']);
    exit;
}

// Fetch appointment data
$appointment = $result->fetch_assoc();

// Return JSON response
echo json_encode($appointment);
?>