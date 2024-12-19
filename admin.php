<?php
// Database connection
$host = 'localhost';
$db = 'agriculture_project'; 
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle new user creation
if (isset($_POST['create_user'])) {
    $username = 'farmer_' . rand(1000, 9999);
    $password = bin2hex(random_bytes(4)); // Random 8-character password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Get the area value from the form
    $assigned_area = $_POST['assigned_area'] ?? 0;

    // Ensure that the assigned area is greater than 0
    if ($assigned_area <= 0) {
        $error_message = "Assigned area must be greater than 0.";
    } else {
        // Insert user into the 'users' table
        $stmt = $pdo->prepare("INSERT INTO users (username, password, assigned_area) VALUES (:username, :password, :assigned_area)");
        $stmt->execute(['username' => $username, 'password' => $hashed_password, 'assigned_area' => $assigned_area]);

        // Get the newly created user's ID
        $user_id = $pdo->lastInsertId();

        // Insert default assigned area for the new farmer in the 'assigned_area' table
        $stmt = $pdo->prepare("INSERT INTO assigned_area (user_id, area) VALUES (:user_id, :assigned_area)");
        $stmt->execute(['user_id' => $user_id, 'assigned_area' => $assigned_area]);

        session_start();
        $_SESSION['new_user'] = ['username' => $username, 'password' => $password, 'hashed_password' => $hashed_password, 'assigned_area' => $assigned_area];
        header("Location: admin.php");
        exit;
    }
}

// Pagination for farmers
$itemsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

$totalFarmersStmt = $pdo->query("SELECT COUNT(*) FROM users");
$totalFarmers = $totalFarmersStmt->fetchColumn();
$totalPages = ceil($totalFarmers / $itemsPerPage);

$stmt = $pdo->prepare("SELECT id, username FROM users LIMIT :offset, :items");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':items', $itemsPerPage, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all crop data for all farmers
$cropStmt = $pdo->query("SELECT u.username, t.product_name, t.planting_area FROM agricultural_products t JOIN users u ON t.user_id = u.id ORDER BY u.username, t.product_name");
$crops = $cropStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch distinct crops with total cultivated area
$totalCropDataStmt = $pdo->query("SELECT product_name, SUM(planting_area) as total_area FROM agricultural_products GROUP BY product_name");
$totalCropData = $totalCropDataStmt->fetchAll(PDO::FETCH_ASSOC);

// Search functionality
$searchResults = [];
if (isset($_POST['search'])) {
    $searchUsername = $_POST['search_username'] ?? '';
    $searchStmt = $pdo->prepare("SELECT t.product_name, t.planting_area FROM agricultural_products t JOIN users u ON t.user_id = u.id WHERE u.username = :username");
    $searchStmt->execute(['username' => $searchUsername]);
    $searchResults = $searchStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Agriculture Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #e9f5e9;
        }
        .card-header {
            background-color: #28a745;
            color: white;
        }
        .btn-primary {
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-primary:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .pagination .page-link {
            color: white;
        }
        .pagination .page-item.active .page-link {
            background-color: #28a745;
            border-color: #28a745;
        }
        .pagination .page-link:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center text-success">Ministry of Agriculture Admin Panel</h1>
    <p class="text-center">Manage users and view all data in the system.</p>

    <!-- Section: Create New User -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5>Create New User</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="assigned_area" class="form-label">Farmer's Cultivable Area (in acres)</label>
                    <input type="number" name="assigned_area" class="form-control" id="assigned_area" placeholder="Enter area in acres" required>
                </div>
                <button type="submit" name="create_user" class="btn btn-primary w-100">Create New Farmer User</button>
            </form>
            <?php 
            session_start();
            if (isset($_SESSION['new_user'])): ?>
                <div class="alert alert-success mt-3">
                    <strong>New User Created!</strong><br>
                    Username: <?= htmlspecialchars($_SESSION['new_user']['username']); ?><br>
                    Password: <?= htmlspecialchars($_SESSION['new_user']['password']); ?><br>
                    Hashed Password: <?= htmlspecialchars($_SESSION['new_user']['hashed_password']); ?><br>
                    Assigned Area: <?= htmlspecialchars($_SESSION['new_user']['assigned_area']); ?> acres
                </div>
                <?php unset($_SESSION['new_user']); ?>
            <?php elseif (isset($error_message)): ?>
                <div class="alert alert-danger mt-3">
                    <strong>Error:</strong> <?= $error_message; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Section: Farmer List -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5>All Farmers</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']); ?></td>
                        <td><?= htmlspecialchars($user['username']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Section: All Crop Data -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5>All Crop Data</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>Farmer</th>
                    <th>Crop</th>
                    <th>Cultivable Area (in acres)</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($crops as $crop): ?>
                    <tr>
                        <td><?= htmlspecialchars($crop['username']); ?></td>
                        <td><?= htmlspecialchars($crop['product_name']); ?></td>
                        <td><?= htmlspecialchars($crop['planting_area']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section: Crop Data Chart -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5>Total Cultivated Area by Crop</h5>
        </div>
        <div class="card-body">
            <canvas id="cropChart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- Section: Search Farmer's Crops -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5>Search Farmer's Crops</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="input-group mb-3">
                    <input type="text" name="search_username" class="form-control" placeholder="Enter farmer username" required>
                    <button class="btn btn-primary" type="submit" name="search">Search</button>
                </div>
            </form>

            <?php if (!empty($searchResults)): ?>
                <h6>Search Results</h6>
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>Crop</th>
                        <th>Cultivable Area (in acres)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($searchResults as $result): ?>
                        <tr>
                            <td><?= htmlspecialchars($result['product_name']); ?></td>
                            <td><?= htmlspecialchars($result['planting_area']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif (isset($_POST['search'])): ?>
                <div class="alert alert-warning">No crops found for this farmer.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const cropData = <?= json_encode($totalCropData); ?>;
    const labels = cropData.map(crop => crop.product_name);
    const data = cropData.map(crop => crop.total_area);

    const ctx = document.getElementById('cropChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Cultivable Area (in acres)',
                data: data,
                backgroundColor: 'rgba(40, 167, 69, 0.6)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                }
            }
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
