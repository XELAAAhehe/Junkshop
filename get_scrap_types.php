<?php
// get_scrap_types.php
include('includes/db.php');

$result = $conn->query("SELECT type, unit_price FROM scrap_types ORDER BY type ASC");

$types = [];
while ($row = $result->fetch_assoc()) {
    $types[] = $row;
}

header('Content-Type: application/json');
echo json_encode($types);
?>
