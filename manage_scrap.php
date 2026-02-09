<?php
include('includes/db.php');
ini_set('display_errors', 1);
error_reporting(E_ALL);



session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] != 'admin' && basename($_SERVER['PHP_SELF']) == 'scrap_ui.php') {
    header("Location: dashboard.php");
    exit();
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

 if ($action === 'add') {
    $type = $_POST['type'];
    $price = $_POST['price'];
    $measure = $_POST['measure'];
    $stmt = $conn->prepare("INSERT INTO scrap_types (type, price, measure) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $type, $price, $measure);
    $stmt->execute();
    echo "Scrap type added successfully";
    exit;
}


    if ($action === 'update') {
        $id = $_POST['id'];
        $price = $_POST['price'];
        $stmt = $conn->prepare("UPDATE scrap_types SET price = ? WHERE id = ?");
        $stmt->bind_param("di", $price, $id);
        $stmt->execute();
        echo "Price updated successfully";
        exit;
    }

    if ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM scrap_types WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo "Scrap type removed";
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT * FROM scrap_types");
    $scrapList = [];
    while ($row = $result->fetch_assoc()) {
        $scrapList[] = $row;
    }
    echo json_encode($scrapList);
    exit;
}


?>
