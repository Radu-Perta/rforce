<?php
// Includem fișierul de conexiune
require_once 'db_connection.php';

// Verificăm dacă este o cerere POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificăm dacă au fost furnizate datele necesare
    if (isset($_POST['appointment_id']) && is_numeric($_POST['appointment_id']) && 
        isset($_POST['status']) && in_array($_POST['status'], ['Pending', 'Confirmed', 'Declined'])) {
        
        $appointmentId = $_POST['appointment_id'];
        $status = $_POST['status'];
        
        $conn = connectToDatabase();
        
        // Pregătim interogarea pentru actualizarea statusului
        $sql = "UPDATE appointments SET status = ? WHERE appointment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $appointmentId);
        
        // Executăm interogarea
        if ($stmt->execute()) {
            // Actualizare reușită
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Statusul programării a fost actualizat cu succes'
            ]);
        } else {
            // Eroare la actualizare
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Eroare la actualizarea statusului: ' . $conn->error
            ]);
        }
        
        $stmt->close();
        closeConnection($conn);
    } else {
        // Date invalide
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Date invalide pentru actualizarea statusului'
        ]);
    }
} else {
    // Metoda HTTP incorectă
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Metoda HTTP incorectă. Se așteaptă POST.'
    ]);
}
?>