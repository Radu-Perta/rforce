<?php
// Includem fișierul de conexiune
require_once 'db_connection.php';

// Variabile pentru mesaje de eroare și succes
$errors = [];
$success = false;

// Verificăm dacă formularul a fost trimis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Preluăm și validăm datele din formular
    $customerName = trim($_POST['customer_name']);
    $serviceType = trim($_POST['service_type']);
    $ratingValue = (float)$_POST['rating_value'];
    $reviewText = trim($_POST['review_text']);
    $serviceDate = trim($_POST['service_date']);
    $responseTime = (int)$_POST['response_time'];
    $priceSatisfaction = (int)$_POST['price_satisfaction'];
    $wouldRecommend = isset($_POST['would_recommend']) ? 1 : 0;
    
    // Validare
    if (empty($customerName)) {
        $errors[] = "Numele clientului este obligatoriu.";
    }
    
    if (empty($serviceType)) {
        $errors[] = "Tipul de service este obligatoriu.";
    }
    
    if ($ratingValue < 1 || $ratingValue > 5) {
        $errors[] = "Ratingul trebuie să fie între 1 și 5.";
    }
    
    if (empty($serviceDate)) {
        $errors[] = "Data serviciului este obligatorie.";
    }
    
    // Dacă nu există erori, inserăm evaluarea în baza de date
    if (empty($errors)) {
        $conn = connectToDatabase();
        
        // Setăm data evaluării la data curentă
        $ratingDate = date('Y-m-d');
        
        // Pregătim interogarea de inserare
        $sql = "INSERT INTO ratings (customer_name, service_type, rating_value, review_text, rating_date, service_date, response_time, price_satisfaction, would_recommend) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdsssiib", $customerName, $serviceType, $ratingValue, $reviewText, $ratingDate, $serviceDate, $responseTime, $priceSatisfaction, $wouldRecommend);
        
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
    
    $sql = "SELECT DISTINCT service_type FROM ratings 
            UNION 
            SELECT DISTINCT service_type FROM appointments";
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
    <title>Add Rating - Admin</title>
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
        .star-rating {
            direction: rtl;
            unicode-bidi: bidi-override;
            text-align: left;
            font-size: 2em;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            display: inline-block;
            color: #ccc;
            cursor: pointer;
        }
        .star-rating label:before {
            content: '★';
        }
        .star-rating input:checked ~ label,
        .star-rating input:checked ~ label:hover,
        .star-rating input:hover ~ label,
        .star-rating label:hover ~ input:checked ~ label {
            color: #ffd700;
        }
        .satisfaction-slider {
            width: 100%;
        }
        .checkbox-container {
            display: flex;
            align-items: center;
        }
        .checkbox-container input {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Add New Rating</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                Evaluarea a fost adăugată cu succes!
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
                    <label for="customer_name">Nume client:</label>
                    <input type="text" id="customer_name" name="customer_name" class="form-control" required>
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
                    <label>Rating:</label>
                    <div class="star-rating">
                        <input type="radio" id="star5" name="rating_value" value="5" required>
                        <label for="star5"></label>
                        <input type="radio" id="star4" name="rating_value" value="4">
                        <label for="star4"></label>
                        <input type="radio" id="star3" name="rating_value" value="3">
                        <label for="star3"></label>
                        <input type="radio" id="star2" name="rating_value" value="2">
                        <label for="star2"></label>
                        <input type="radio" id="star1" name="rating_value" value="1">
                        <label for="star1"></label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="review_text">Comentariu:</label>
                    <textarea id="review_text" name="review_text" class="form-control" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="service_date">Data serviciului:</label>
                    <input type="date" id="service_date" name="service_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="response_time">Satisfacție privind timpul de răspuns (1-10):</label>
                    <input type="range" id="response_time" name="response_time" min="1" max="10" value="5" class="satisfaction-slider">
                    <span id="response_time_value">5</span>
                </div>
                
                <div class="form-group">
                    <label for="price_satisfaction">Satisfacție privind prețul (1-10):</label>
                    <input type="range" id="price_satisfaction" name="price_satisfaction" min="1" max="10" value="5" class="satisfaction-slider">
                    <span id="price_satisfaction_value">5</span>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-container">
                        <input type="checkbox" id="would_recommend" name="would_recommend">
                        <label for="would_recommend">Ar recomanda serviciul altora</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn-submit">Adaugă evaluare</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Actualizăm valorile afișate pentru slidere
        document.addEventListener('DOMContentLoaded', function() {
            const responseTimeSlider = document.getElementById('response_time');
            const responseTimeValue = document.getElementById('response_time_value');
            
            responseTimeSlider.addEventListener('input', function() {
                responseTimeValue.textContent = this.value;
            });
            
            const priceSatisfactionSlider = document.getElementById('price_satisfaction');
            const priceSatisfactionValue = document.getElementById('price_satisfaction_value');
            
            priceSatisfactionSlider.addEventListener('input', function() {
                priceSatisfactionValue.textContent = this.value;
            });
            
            // Setăm data maximă pentru câmpul data serviciului la ziua curentă
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('service_date').setAttribute('max', today);
        });
    </script>
</body>
</html>