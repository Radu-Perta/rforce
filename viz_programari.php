<?php
// Includem fișierul de conexiune
require_once 'db_connection.php';

// Funcție pentru obținerea tuturor programărilor
function getAllAppointments($status = null) {
    $conn = connectToDatabase();
    
    // Construirea interogării în funcție de filtrul de status
    $sql = "SELECT * FROM appointments";
    if ($status !== null) {
        $sql .= " WHERE status = ?";
    }
    $sql .= " ORDER BY appointment_date DESC";
    
    $stmt = $conn->prepare($sql);
    
    // Dacă există filtru de status, îl legăm ca parametru
    if ($status !== null) {
        $stmt->bind_param("s", $status);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    
    $stmt->close();
    closeConnection($conn);
    
    return $appointments;
}

// Funcție pentru obținerea detaliilor unei programări
function getAppointmentDetails($appointmentId) {
    $conn = connectToDatabase();
    
    $sql = "SELECT * FROM appointments WHERE appointment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();
    
    $stmt->close();
    closeConnection($conn);
    
    return $appointment;
}

// Funcție pentru actualizarea statusului unei programări
function updateAppointmentStatus($appointmentId, $status) {
    $conn = connectToDatabase();
    
    $sql = "UPDATE appointments SET status = ? WHERE appointment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $appointmentId);
    
    $success = $stmt->execute();
    
    $stmt->close();
    closeConnection($conn);
    
    return $success;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Requests - Admin</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .tab-container {
            margin-top: 20px;
        }
        .tab {
            overflow: hidden;
            border: 1px solid #ccc;
            background-color: #f1f1f1;
        }
        .tab button {
            background-color: inherit;
            float: left;
            border: none;
            outline: none;
            cursor: pointer;
            padding: 14px 16px;
            transition: 0.3s;
            font-size: 17px;
        }
        .tab button:hover {
            background-color: #ddd;
        }
        .tab button.active {
            background-color: #ccc;
        }
        .tab-content {
            display: none;
            padding: 6px 12px;
            border: 1px solid #ccc;
            border-top: none;
        }
        .appointment-table {
            width: 100%;
            border-collapse: collapse;
        }
        .appointment-table th, .appointment-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .appointment-table th {
            background-color: #f2f2f2;
        }
        .appointment-details {
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
        .details-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
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
        .button {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .button-confirm {
            background-color: #4CAF50;
            color: white;
        }
        .button-decline {
            background-color: #f44336;
            color: white;
        }
        .button-details {
            background-color: #2196F3;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Appointment Requests</h1>
        
        <div class="tab-container">
            <div class="tab">
                <button class="tablinks active" onclick="openTab(event, 'Pending')">Pending <?php 
                    $pendingCount = count(getAllAppointments('Pending'));
                    if ($pendingCount > 0) echo "($pendingCount)"; 
                ?></button>
                <button class="tablinks" onclick="openTab(event, 'Confirmed')">Confirmed</button>
                <button class="tablinks" onclick="openTab(event, 'Declined')">Declined</button>
            </div>
            
            <!-- Tab pentru programări în așteptare -->
            <div id="Pending" class="tab-content" style="display: block;">
                <table class="appointment-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Client</th>
                            <th>Service</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $pendingAppointments = getAllAppointments('Pending');
                        if (empty($pendingAppointments)) {
                            echo "<tr><td colspan='4'>No pending appointments.</td></tr>";
                        } else {
                            foreach ($pendingAppointments as $appointment) {
                                $appointmentDate = date('D, M j, Y \a\t g:i A', strtotime($appointment['appointment_date']));
                                echo "<tr>";
                                echo "<td>$appointmentDate</td>";
                                echo "<td>{$appointment['client_name']}</td>";
                                echo "<td>{$appointment['service_type']}</td>";
                                echo "<td>
                                        <button class='button button-details' onclick='showDetails({$appointment['appointment_id']})'>Details</button>
                                        <button class='button button-confirm' onclick='updateStatus({$appointment['appointment_id']}, \"Confirmed\")'>Confirm</button>
                                        <button class='button button-decline' onclick='updateStatus({$appointment['appointment_id']}, \"Declined\")'>Decline</button>
                                      </td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Tab pentru programări confirmate -->
            <div id="Confirmed" class="tab-content">
                <table class="appointment-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Client</th>
                            <th>Service</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $confirmedAppointments = getAllAppointments('Confirmed');
                        if (empty($confirmedAppointments)) {
                            echo "<tr><td colspan='4'>No confirmed appointments.</td></tr>";
                        } else {
                            foreach ($confirmedAppointments as $appointment) {
                                $appointmentDate = date('D, M j, Y \a\t g:i A', strtotime($appointment['appointment_date']));
                                echo "<tr>";
                                echo "<td>$appointmentDate</td>";
                                echo "<td>{$appointment['client_name']}</td>";
                                echo "<td>{$appointment['service_type']}</td>";
                                echo "<td>
                                        <button class='button button-details' onclick='showDetails({$appointment['appointment_id']})'>Details</button>
                                      </td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Tab pentru programări refuzate -->
            <div id="Declined" class="tab-content">
                <table class="appointment-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Client</th>
                            <th>Service</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $declinedAppointments = getAllAppointments('Declined');
                        if (empty($declinedAppointments)) {
                            echo "<tr><td colspan='4'>No declined appointments.</td></tr>";
                        } else {
                            foreach ($declinedAppointments as $appointment) {
                                $appointmentDate = date('D, M j, Y \a\t g:i A', strtotime($appointment['appointment_date']));
                                echo "<tr>";
                                echo "<td>$appointmentDate</td>";
                                echo "<td>{$appointment['client_name']}</td>";
                                echo "<td>{$appointment['service_type']}</td>";
                                echo "<td>
                                        <button class='button button-details' onclick='showDetails({$appointment['appointment_id']})'>Details</button>
                                      </td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal pentru detalii programare -->
    <div id="appointmentDetailsModal" class="appointment-details">
        <div class="details-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Appointment Details</h2>
            <div id="appointmentDetailsContent">
                <!-- Conținutul va fi completat via AJAX -->
            </div>
            <div id="appointmentActions">
                <!-- Butoanele de acțiune vor fi adăugate dinamic -->
            </div>
        </div>
    </div>
    
    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tablinks");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }
        
        function showDetails(appointmentId) {
            // Aici ar fi codul AJAX pentru a obține detaliile programării
            // Pentru simplitate, vom simula cu date statice
            
            fetch('get_appointment_details.php?id=' + appointmentId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const appointment = data.appointment;
                        let detailsHtml = `
                            <p><strong>Client:</strong> ${appointment.client_name}</p>
                            <p><strong>Contact:</strong> ${appointment.contact_email}<br>${appointment.contact_phone}</p>
                            <p><strong>Appointment:</strong> ${new Date(appointment.appointment_date).toLocaleString()}</p>
                            <p><strong>Vehicle VIN:</strong> ${appointment.vehicle_vin}</p>
                            <p><strong>Services:</strong> ${appointment.service_type}</p>
                            <p><strong>Status:</strong> <span class="status-${appointment.status.toLowerCase()}">${appointment.status}</span></p>
                            <p><strong>Submitted:</strong> ${new Date(appointment.submitted_datetime).toLocaleString()}</p>
                        `;
                        
                        document.getElementById('appointmentDetailsContent').innerHTML = detailsHtml;
                        
                        // Adăugare butoane de acțiune în funcție de status
                        let actionsHtml = '';
                        if (appointment.status === 'Pending') {
                            actionsHtml = `
                                <button class="button button-confirm" onclick="updateStatus(${appointment.appointment_id}, 'Confirmed')">Confirm Appointment</button>
                                <button class="button button-decline" onclick="updateStatus(${appointment.appointment_id}, 'Declined')">Decline Appointment</button>
                            `;
                        }
                        document.getElementById('appointmentActions').innerHTML = actionsHtml;
                        
                        document.getElementById('appointmentDetailsModal').style.display = 'block';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while fetching appointment details.');
                });
        }
        
        function closeModal() {
            document.getElementById('appointmentDetailsModal').style.display = 'none';
        }
        
        function updateStatus(appointmentId, status) {
            if (confirm(`Are you sure you want to ${status.toLowerCase()} this appointment?`)) {
                // Aici ar fi codul AJAX pentru a actualiza statusul
                fetch('update_appointment_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `appointment_id=${appointmentId}&status=${status}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Appointment has been ${status.toLowerCase()}.`);
                        // Reîncărcarea paginii pentru a reflecta schimbările
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating appointment status.');
                });
            }
        }
        
        // Închiderea modalului când se face click în afara conținutului
        window.onclick = function(event) {
            var modal = document.getElementById('appointmentDetailsModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>