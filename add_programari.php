<?php
// Includem fișierul de conexiune
require_once 'db_connection.php';

// Variabile pentru mesaje de eroare și succes
$errors = [];
$success = false;

// Verificăm dacă formularul a fost trimis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Preluăm și validăm datele din formular
    $clientName = trim($_POST['client_name']);
    $contactEmail = trim($_POST['contact_email']);
    $contactPhone = trim($_POST['contact_phone']);
    $appointmentDate = trim($_POST['appointment_date']);
    $appointmentTime = trim($_POST['appointment_time']);
    $vehicleVin = trim($_POST['vehicle_vin']);
    $serviceType = trim($_POST['service_type']);
    $notes = trim($_POST['notes']);
    
    // Validare
    if (empty($clientName)) {
        $errors[] = "Numele clientului este obligatoriu.";
    }
    
    if (empty($contactEmail)) {
        $errors[] = "Email-ul de contact este obligatoriu.";
    } elseif (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email-ul nu este valid.";
    }
    
    if (empty($contactPhone)) {
        $errors[] = "Telefonul de contact este obligatoriu.";
    }
    
    if (empty($appointmentDate)) {
        $errors[] = "Data programării este obligatorie.";
    }
    
    if (empty($appointmentTime)) {
        $errors[] = "Ora programării este obligatorie.";
    }
    
    if (empty($vehicleVin)) {
        $errors[] = "VIN-ul vehiculului este obligatoriu.";
    }
    
    if (empty($serviceType)) {
        $errors[] = "Tipul de service este obligatoriu.";
    }
    
    // Dacă nu există erori, inserăm programarea în baza de date
    if (empty($errors)) {
        // Combinăm data și ora
        $appointmentDateTime = $appointmentDate . ' ' . $appointmentTime;
        
        $conn = connectToDatabase();
        
        // Pregătim interogarea de inserare
        $sql = "INSERT INTO appointments (client_name, contact_email, contact_phone, appointment_date, vehicle_vin, service_type, status, notes) 
                VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $clientName, $contactEmail, $contactPhone, $appointmentDateTime, $vehicleVin, $serviceType, $notes);
        
        // Executăm interogarea
        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "A apărut o eroare: " . $stmt->error;
        }
        
        $stmt->close();
        closeConnection($conn);
    }
}

// Obținem lista serviciilor disponibile pentru dropdown
function getAvailableServices() {
    $conn = connectToDatabase();
    
    $sql = "SELECT DISTINCT service_type FROM appointments";
    $result = $conn->query($sql);
    
    $services = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $services[] = $row['service_type'];
        }
    } else {
        // Servicii predefinite în cazul în care nu există în baza de date
        $services = ['Oil Change', 'Tire Replacement', 'Brake Service', 'Engine Diagnostic', 'Regular Maintenance'];
    }
    
    closeConnection($conn);
    
    return $services;
}

$availableServices = getAvailableServices();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Appointment - Admin</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
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
        }
        .btn-submit {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-submit:hover {
            background-color: #45a049;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        .alert-danger {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
        .date-time-container {
            display: flex;
            gap: 10px;
        }
        .date-time-container .form-control {
            flex: 1;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Add New Appointment</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                Programarea a fost adăugată cu succes!
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Au apărut următoarele erori:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="client_name">Nume client:</label>
                    <input type="text" id="client_name" name="client_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="contact_email">Email contact:</label>
                    <input type="email" id="contact_email" name="contact_email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="contact_phone">Telefon contact:</label>
                    <input type="text" id="contact_phone" name="contact_phone" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Data și ora programării:</label>
                    <div class="date-time-container">
                        <input type="date" id="appointment_date" name="appointment_date" class="form-control" required>
                        <input type="time" id="appointment_time" name="appointment_time" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="vehicle_vin">VIN vehicul:</label>
                    <input type="text" id="vehicle_vin" name="vehicle_vin" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="service_type">Tip service:</label>
                    <select id="service_type" name="service_type" class="form-control" required>
                        <option value="">Selectați tipul de service</option>
                        <?php foreach ($availableServices as $service): ?>
                            <option value="<?php echo $service; ?>"><?php echo $service; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="notes">Note adiționale:</label>
                    <textarea id="notes" name="notes" class="form-control" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn-submit">Adaugă programare</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Setăm data de astăzi ca valoare minimă pentru câmpul de dată
        document.addEventListener('DOMContentLoaded', function() {
            var today = new Date().toISOString().split('T')[0];
            document.getElementById('appointment_date').setAttribute('min', today);
        });
    </script>
</body>
</html>