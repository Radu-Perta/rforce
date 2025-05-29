<?php
// Includem fișierul de conexiune
require_once 'db_connection.php';

// Verificăm dacă a fost furnizat un ID valid
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $appointmentId = $_GET['id'];
    
    $conn = connectToDatabase();
    
    // Pregătim interogarea pentru a obține detaliile programării
    $sql = "SELECT * FROM appointments WHERE appointment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    // Verificăm dacă programarea există
    if ($result->num_rows > 0) {
        $appointment = $result->fetch_assoc();
        
        // Returnăm datele ca JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'appointment' => $appointment
        ]);
    } else {
        // Programarea nu a fost găsită
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Programarea nu a fost găsită'
        ]);
    }
    
    $stmt->close();
    closeConnection($conn);
} else {
    // ID-ul nu a fost furnizat sau nu este valid
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ID programare invalid'
    ]);
}
?>