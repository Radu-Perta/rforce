<?php
// Includem fișierul de conexiune
require_once 'db_connection.php';

// Funcție pentru obținerea tuturor evaluărilor
function getAllRatings($serviceType = null) {
    $conn = connectToDatabase();
    
    // Construirea interogării în funcție de filtrul de service
    $sql = "SELECT * FROM ratings";
    if ($serviceType !== null && $serviceType !== 'All Services') {
        $sql .= " WHERE service_type = ?";
    }
    $sql .= " ORDER BY rating_date DESC";
    
    $stmt = $conn->prepare($sql);
    
    // Dacă există filtru de service, îl legăm ca parametru
    if ($serviceType !== null && $serviceType !== 'All Services') {
        $stmt->bind_param("s", $serviceType);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ratings = [];
    while ($row = $result->fetch_assoc()) {
        $ratings[] = $row;
    }
    
    $stmt->close();
    closeConnection($conn);
    
    return $ratings;
}

// Funcție pentru calcularea ratingului mediu
function calculateAverageRating($ratings) {
    if (empty($ratings)) return 0;
    
    $sum = 0;
    foreach ($ratings as $rating) {
        $sum += $rating['rating_value'];
    }
    
    return round($sum / count($ratings), 1);
}

// Funcție pentru calcularea procentului de review-uri de 5 stele
function calculateFiveStarPercentage($ratings) {
    if (empty($ratings)) return 0;
    
    $fiveStarCount = 0;
    foreach ($ratings as $rating) {
        if ($rating['rating_value'] == 5) {
            $fiveStarCount++;
        }
    }
    
    return round(($fiveStarCount / count($ratings)) * 100);
}

// Funcție pentru obținerea tuturor tipurilor de service disponibile
function getAllServiceTypes() {
    $conn = connectToDatabase();
    
    $sql = "SELECT DISTINCT service_type FROM ratings";
    $result = $conn->query($sql);
    
    $serviceTypes = [];
    while ($row = $result->fetch_assoc()) {
        $serviceTypes[] = $row['service_type'];
    }
    
    closeConnection($conn);
    
    return $serviceTypes;
}

// Obținem toate evaluările sau filtrăm după serviciu dacă este setat
$serviceFilter = isset($_GET['service']) ? $_GET['service'] : null;
$allRatings = getAllRatings($serviceFilter);
$averageRating = calculateAverageRating($allRatings);
$fiveStarPercentage = calculateFiveStarPercentage($allRatings);

// Obținem toate tipurile de service pentru filtru
$serviceTypes = getAllServiceTypes();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Ratings - Admin</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .ratings-summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .summary-box {
            flex: 1;
            padding: 20px;
            text-align: center;
            background-color: #f9f9f9;
            border-radius: 5px;
            margin: 0 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .rating-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #673ab7;
            margin: 10px 0;
        }
        .five-star-percentage {
            font-size: 2.5em;
            font-weight: bold;
            color: #ff5722;
            margin: 10px 0;
        }
        .total-reviews {
            font-size: 2.5em;
            font-weight: bold;
            color: #2196f3;
            margin: 10px 0;
        }
        .star-rating {
            color: #ffd700;
            font-size: 1.2em;
        }
        .filter-container {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .filter-container label {
            margin-right: 10px;
            font-weight: bold;
        }
        .filter-container select {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .refresh-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
        .refresh-btn:hover {
            background-color: #45a049;
        }
        .ratings-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .ratings-table th, .ratings-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .ratings-table th {
            background-color: #f2f2f2;
        }
        .ratings-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .add-rating-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        .add-rating-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Customer Ratings</h1>
        
        <a href="ratings_add.php" class="add-rating-btn">Add New Rating</a>
        
        <div class="ratings-summary">
            <div class="summary-box">
                <h3>Overall Rating</h3>
                <div class="rating-value"><?php echo $averageRating; ?></div>
                <div class="star-rating">
                    <?php
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $averageRating) {
                            echo "★";
                        } elseif ($i - 0.5 <= $averageRating) {
                            echo "½";
                        } else {
                            echo "☆";
                        }
                    }
                    ?>
                </div>
            </div>
            
            <div class="summary-box">
                <h3>Total Reviews</h3>
                <div class="total-reviews"><?php echo count($allRatings); ?></div>
            </div>
            
            <div class="summary-box">
                <h3>5-Star Reviews</h3>
                <div class="five-star-percentage"><?php echo $fiveStarPercentage; ?>%</div>
            </div>
        </div>
        
        <div class="filter-container">
            <label for="service-filter">Filter by:</label>
            <select id="service-filter" onchange="window.location.href = '?service=' + this.value">
                <option value="All Services" <?php if ($serviceFilter === null || $serviceFilter === 'All Services') echo 'selected'; ?>>All Services</option>
                <?php foreach ($serviceTypes as $service): ?>
                    <option value="<?php echo $service; ?>" <?php if ($serviceFilter === $service) echo 'selected'; ?>>
                        <?php echo $service; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="refresh-btn" onclick="window.location.reload()">Refresh</button>
        </div>
        
        <table class="ratings-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Service</th>
                    <th>Rating</th>
                    <th>Review</th>
                    <th>Satisfacție preț</th>
                    <th>Recomandare</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($allRatings)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No ratings found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($allRatings as $rating): ?>
                        <tr>
                            <td><?php echo date('m/d/Y', strtotime($rating['rating_date'])); ?></td>
                            <td><?php echo htmlspecialchars($rating['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($rating['service_type']); ?></td>
                            <td>
                                <div class="star-rating">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $rating['rating_value'] ? "★" : "☆";
                                    }
                                    ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($rating['review_text']); ?></td>
                            <td><?php echo $rating['price_satisfaction']; ?>/10</td>
                            <td><?php echo $rating['would_recommend'] ? 'Da' : 'Nu'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>