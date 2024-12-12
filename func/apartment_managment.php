<?php
function validateItemData($data) {
    $errors = [];
    
    if (!is_numeric($data['price']) || $data['price'] < 0) {
        $errors[] = "Цена должна быть положительным числом.";
    }
    if (!is_numeric($data['counts']) || $data['counts'] <= 0) {
        $errors[] = "Количество должно быть положительным числом.";
    }
    if (!is_numeric($data['sostoyanie']) || $data['sostoyanie'] <= 0) {
        $errors[] = "Состояние должно быть положительным числом.";
    }
    if (strlen($data['location']) <= 4 || strlen($data['name']) <= 4 || strlen($data['description']) <= 4) {
        $errors[] = "Адрес, описание и имя должны содержать более 4 символов.";
    }
    
    return $errors;
}

function createItem($pdo, $data) {
    $stmt = $pdo->prepare("INSERT INTO main (name, price, description, location, counts, sostoyanie, available) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['name'],
        $data['price'],
        $data['description'],
        $data['location'],
        $data['counts'],
        $data['sostoyanie'],
        isset($data['available']) ? 1 : 0
    ]);
}

function updateItem($pdo, $data) {
    $stmt = $pdo->prepare("UPDATE main SET name = ?, price = ?, description = ?, location = ?, counts = ?, sostoyanie = ?, available = ? WHERE id = ?");
    $stmt->execute([
        $data['name'],
        $data['price'],
        $data['description'],
        $data['location'],
        $data['counts'],
        $data['sostoyanie'],
        isset($data['available']) ? 1 : 0,
        $data['id']
    ]);
}

function deleteItem($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM main WHERE id = ?");
    $stmt->execute([$id]);
}

function searchmain($pdo, $searchTerm) {
    $query = "SELECT * FROM main WHERE name LIKE ? OR description LIKE ? OR location LIKE ?";
    $params = ["%$searchTerm%", "%$searchTerm%", "%$searchTerm%"];
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllmain($pdo) {
    $stmt = $pdo->query("SELECT * FROM main");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>