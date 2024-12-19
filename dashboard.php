<?php
session_start();

// Redirect to the login page if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Database connection
$host = 'localhost'; // Server name
$db = 'agriculture_project'; // Database name
$user = 'root'; // Database username
$pass = ''; // Database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Predefined list of all cultivable crops
$all_crops = [
    "Wheat", "Rice", "Corn", "Soybeans", "Barley", 
    "Potatoes", "Tomatoes", "Onions", "Carrots", "Apples", 
    "Oranges", "Bananas", "Strawberries", "Peppers", "Lettuce"
];

// Processing added crop data
$error_message = ''; // Error message variable
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_crop'])) {
    $crop = $_POST['crop'];
    $area = (int)$_POST['area'];
    $user_id = $_SESSION['user_id'];

    // Get the total assigned area for the user
    $stmt = $pdo->prepare("SELECT assigned_area FROM users WHERE id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $assignedArea = $stmt->fetch(PDO::FETCH_ASSOC)['assigned_area'];

    // Get the total planted area for the user
    $stmt = $pdo->prepare("SELECT SUM(planting_area) AS total_area FROM agricultural_products WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $totalArea = $stmt->fetch(PDO::FETCH_ASSOC)['total_area'];
    
    // Calculate the remaining area
    $remainingArea = $assignedArea - $totalArea;

    // Check if the area to be added exceeds the remaining area allocated to the user
    if ($area > $remainingArea) {
        $error_message = "The area you are trying to add exceeds your remaining allocated area ({$remainingArea} acres). Please enter a smaller value.";
    } else {
        // If the crop has already been added by the user, update it
        $stmt = $pdo->prepare("SELECT id, planting_area FROM agricultural_products WHERE product_name = :crop AND user_id = :user_id");
        $stmt->execute(['crop' => $crop, 'user_id' => $user_id]);
        $existingCrop = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingCrop) {
            // Update the existing crop
            $new_area = $existingCrop['planting_area'] + $area;
            $updateStmt = $pdo->prepare("UPDATE agricultural_products SET planting_area = :new_area WHERE id = :id");
            $updateStmt->execute(['new_area' => $new_area, 'id' => $existingCrop['id']]);
        } else {
            // Add a new crop
            $insertStmt = $pdo->prepare("INSERT INTO agricultural_products (product_name, planting_area, user_id) VALUES (:crop, :area, :user_id)");
            $insertStmt->execute(['crop' => $crop, 'area' => $area, 'user_id' => $user_id]);
        }
    }
}

// Harvest process
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['harvest_crop'])) {
    $crop = $_POST['harvest_crop_name'];
    $harvested_area = (int)$_POST['harvested_area'];
    $user_id = $_SESSION['user_id'];

    // Get the current planted area of the crop
    $stmt = $pdo->prepare("SELECT id, planting_area FROM agricultural_products WHERE product_name = :crop AND user_id = :user_id");
    $stmt->execute(['crop' => $crop, 'user_id' => $user_id]);
    $existingCrop = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingCrop) {
        // Check if the harvested area does not exceed the planted area
        if ($harvested_area > $existingCrop['planting_area']) {
            $error_message = "The harvested area cannot exceed the planted area ({$existingCrop['planting_area']} acres).";
        } else {
            // Update the planted area
            $new_area = $existingCrop['planting_area'] - $harvested_area;
            $updateStmt = $pdo->prepare("UPDATE agricultural_products SET planting_area = :new_area WHERE id = :id");
            $updateStmt->execute(['new_area' => $new_area, 'id' => $existingCrop['id']]);
        }
    } else {
        $error_message = "Data not found for the selected crop.";
    }
}

// Get all crops data for all users
$stmt = $pdo->prepare("SELECT product_name, SUM(planting_area) AS total_area FROM agricultural_products GROUP BY product_name");
$stmt->execute();
$allCropsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the assigned area for the user
$stmt = $pdo->prepare("SELECT assigned_area FROM users WHERE id = :user_id");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$assignedArea = $stmt->fetch(PDO::FETCH_ASSOC)['assigned_area'];

// Get the user's planted crops data
$stmt = $pdo->prepare("SELECT product_name, planting_area FROM agricultural_products WHERE user_id = :user_id");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$userCrops = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate the total planted area for the user
$totalUserPlantedArea = array_sum(array_column($userCrops, 'planting_area'));

// Calculate the remaining available area
$availableArea = $assignedArea - $totalUserPlantedArea;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Agriculture Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container mt-5">
    <!-- Logout Button -->
    <div class="d-flex justify-content-start mb-3">
        <a href="index.php" class="btn btn-danger">Logout</a>
    </div>

    <h1 class="text-center text-success">Agriculture Dashboard</h1>
    <p class="text-center">Welcome, here you can manage your planted crops.</p>

    <!-- Display assigned area and remaining area -->
    <div class="alert alert-info">
        <strong>Your Assigned Area:</strong> <?= htmlspecialchars($assignedArea); ?> acres
    </div>
    <div class="alert alert-info">
        <strong>Remaining Area:</strong> <?= htmlspecialchars($availableArea); ?> acres
    </div>

    <!-- Display error message if any -->
    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <strong>Error:</strong> <?= htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Crop data entry form -->
    <div class="card shadow mb-4">
        <div class="card-header bg-success text-white">
            <h5>Enter Crop Data</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="crop" class="form-label">Select Crop</label>
                    <select class="form-select" id="crop" name="crop" required>
                        <?php foreach ($all_crops as $crop): ?>
                            <option value="<?= htmlspecialchars($crop) ?>"><?= htmlspecialchars($crop) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="area" class="form-label">Planting Area (in acres)</label>
                    <input type="number" class="form-control" id="area" name="area" required min="1">
                </div>
                <button type="submit" name="add_crop" class="btn btn-success w-100">Add Data</button>
            </form>
        </div>
    </div>

    <!-- Harvest data entry form -->
    <div class="card shadow mb-4">
        <div class="card-header bg-warning text-white">
            <h5>Enter Harvest Data</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="harvest_crop" class="form-label">Select Crop to Harvest</label>
                    <select class="form-select" id="harvest_crop" name="harvest_crop_name" required>
                        <?php foreach ($userCrops as $userCrop): ?>
                            <option value="<?= htmlspecialchars($userCrop['product_name']) ?>"><?= htmlspecialchars($userCrop['product_name']) ?> (<?= htmlspecialchars($userCrop['planting_area']) ?> acres)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="harvested_area" class="form-label">Harvested Area (in acres)</label>
                    <input type="number" class="form-control" id="harvested_area" name="harvested_area" required min="1">
                </div>
                <button type="submit" name="harvest_crop" class="btn btn-warning w-100">Harvest</button>
            </form>
        </div>
    </div>

    <!-- Graph to visualize all users' planted crops -->
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h5>All Users' Crop Planting Overview</h5>
        </div>
        <div class="card-body">
            <canvas id="cropChart"></canvas>
        </div>
    </div>
</div>

<script>
    // Prepare the data for the chart
    var ctx = document.getElementById('cropChart').getContext('2d');
    var cropData = <?= json_encode($allCropsData) ?>;
    var labels = cropData.map(item => item.product_name);
    var data = cropData.map(item => item.total_area);

    var cropChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Area Planted by All Users (Acres)',
                data: data,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
</body>
</html>
