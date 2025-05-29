<?php
// Verificăm dacă utilizatorul este autentificat
// Această verificare ar trebui implementată conform sistemului de autentificare
// În acest exemplu, presupunem că utilizatorul este deja autentificat
$isLoggedIn = true; // Aceasta ar trebui să fie o verificare reală a sesiunii

// Calea curentă pentru a evidenția elementul de meniu activ
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<header>
    <div class="header-container">
        <div class="logo">
            <a href="index.php">R Force Admin</a>
        </div>
        
        <?php if ($isLoggedIn): ?>
        <nav>
            <ul class="main-menu">
                <li <?php if ($currentPage === 'users.php') echo 'class="active"'; ?>>
                    <a href="users.php">Users</a>
                </li>
                <li <?php if ($currentPage === 'appointments_view.php' || $currentPage === 'appointments_add.php') echo 'class="active"'; ?>>
                    <a href="appointments_view.php">Appointments</a>
                </li>
                <li <?php if ($currentPage === 'ratings_view.php' || $currentPage === 'ratings_add.php') echo 'class="active"'; ?>>
                    <a href="ratings_view.php">Ratings</a>
                </li>
                <li <?php if ($currentPage === 'coupons.php') echo 'class="active"'; ?>>
                    <a href="coupons.php">Coupons</a>
                </li>
            </ul>
        </nav>
        
        <div class="user-actions">
            <a href="logout.php">Log out</a>
        </div>
        <?php endif; ?>
    </div>
</header>

<style>
    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #333;
        color: white;
        padding: 0 20px;
        height: 60px;
    }
    
    .logo a {
        color: white;
        text-decoration: none;
        font-size: 1.5em;
        font-weight: bold;
    }
    
    .main-menu {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    
    .main-menu li {
        margin: 0 10px;
    }
    
    .main-menu li a {
        color: white;
        text-decoration: none;
        padding: 20px 10px;
        display: block;
    }
    
    .main-menu li:hover a, .main-menu li.active a {
        color: #4CAF50;
    }
    
    .user-actions a {
        color: white;
        text-decoration: none;
    }
    
    .user-actions a:hover {
        text-decoration: underline;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f4f4f4;
    }
    
    h1 {
        color: #333;
        margin-bottom: 30px;
    }
</style>