<?php
session_start();

$dsn = 'mysql:host=localhost;dbname=php_laba3;charset=utf8';
$username = 'root';
$password = '';

$isseller = $_SESSION['role'] === 'seller';
$isClient = $_SESSION['role'] === 'client';
$isAdmin = $_SESSION['role'] === 'admin';

$userID = $_SESSION['user_id'];
try {
    $pdo = new PDO($dsn, $username, $password);
} catch (PDOException $e) {
    $_SESSION['db_error'] = 'Ошибка подключения к базе данных: ' . $e->getMessage();
}

if (!$pdo) {
    $_SESSION['db_error'] = 'База данных недоступна. Пожалуйста, попробуйте позже.'  . $e->getMessage();
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['role']);
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['user_id'] )) {
    header("Location: login.php");
    exit();
}



$searchTerm = '';

try {
    $stmt = $pdo->query("SELECT is_active FROM smart_search LIMIT 1");
    $smartSearch = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$smartSearch) {
        $pdo->exec("INSERT INTO smart_search (is_active) VALUES (0)");
        $isActive = 0;
    } else {
        $isActive = $smartSearch['is_active'];
    }
} catch (PDOException $e) {
    $_SESSION['db_error'] = 'Ошибка при получении состояния умного поиска: ' . htmlspecialchars($e->getMessage());
    header("Location: logout.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $searchTerm = $_POST['searchTerm'];

    if (strlen($searchTerm) > 1000) {
        $_SESSION['error'] = "Поисковый запрос не должен превышать 1000 символов.";
        $searchTerm = '';
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_save'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $price = $_POST['price'];
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $counts = $_POST['counts'];
    $sostoyanie = $_POST['sostoyanie'];
    $available = isset($_POST['available']) ? 1 : 0;


    if (!is_numeric($price) || $price < 0) {
        $_SESSION['error'] = "Цена должна быть положительным числом.";
    } elseif (strlen($location) <= 4 
        || strlen($name) <= 4
        || strlen($description) <= 4
        || strlen($description) >= 256
        || strlen($location) >= 256
        || strlen($name) >= 256
    ) {
        $_SESSION['error'] = "Адрес, описание и имя должны содержать более 4 символов и менее 256 (пробелы не считаются).";
    } elseif (!is_numeric($counts) || $counts < 0) {
        $_SESSION['error'] = "Количество должно быть положительным числом.";
    } elseif (!is_numeric($sostoyanie) || $sostoyanie < 1 || $sostoyanie > 10) {
        $_SESSION['error'] = "Состояние должно быть положительным числом от 1 до 10.";
    } else {
        try {
            // $stmt = $pdo->prepare("CALL Additem(?, ?, ?, ?, ?, ?, ?)");
            // $stmt->execute([$name, $price, $description, $location, $counts, $sostoyanie, $available]);
            $stmt = $pdo->prepare("UPDATE main SET name = ?, price = ?, description = ?, location = ?, counts = ?, sostoyanie = ?, available = ? WHERE id = ?");
            $stmt->execute([$name, $price, $description, $location, $counts, $sostoyanie, $available, $id]);
            $_SESSION['success'] = "Товар успешно обновлен.";
        } catch (PDOException $e) {
            $_SESSION['db_error'] = 'Ошибка при выполнении запроса: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $name = trim($_POST['name']);
    $price = $_POST['price'];
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $counts = $_POST['counts'];
    $sostoyanie = $_POST['sostoyanie'];
    $available = isset($_POST['available']) ? 1 : 0;

    if (!is_numeric($price) || $price < 0) {
        $_SESSION['db_error'] = "Цена должна быть положительным числом.";
    } elseif (strlen($location) <= 4 
    || strlen($name) <= 4
    || strlen($description) <= 4
    || strlen($description) >= 256
    || strlen($location) >= 256
    || strlen($name) >= 256
) {
    $_SESSION['error'] = "Адрес, описание и имя должны содержать более 4 символов и менее 256 (пробелы не считаются).";
} elseif (!is_numeric($counts) || $counts < 0) {
        $_SESSION['db_error'] = "Количествоы должно быть положительным числом.";
    } elseif (!is_numeric($sostoyanie) || $sostoyanie < 0 || $sostoyanie > 10) {
        $_SESSION['db_error'] = "Состояние должно быть положительным числом.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO main (name, price, description, location, counts, sostoyanie, available, sellerID) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $price, $description, $location, $counts, $sostoyanie, $available, $_SESSION["user_id"]]);
            $_SESSION['success'] = "Товар успешно добавлен.";
        } catch (PDOException $e) {
            $_SESSION['db_error'] = 'Ошибка при выполнении запроса: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    error_log("POST Data: " . print_r($_POST, true));
    $itemID = $_POST['save'];
    $userID = $_SESSION['user_id'];

    if (empty($itemID)) {
        $_SESSION['error'] = "ID товара не может быть пустым.";
    } else {
        try {
            // Проверка существующей записи
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE userID = ? AND itemID = ?");
            $stmt->execute([$userID, $itemID]);
            $exists = $stmt->fetchColumn();

            if ($exists) {
                $_SESSION['error'] = "Этот товар уже в избранном.";
            } else {
                // Если записи нет, добавляем
                $stmt = $pdo->prepare("INSERT INTO favorites (userID, itemID) VALUES (?, ?)");
                $stmt->execute([$userID, $itemID]);
                $_SESSION['success'] = "Товар добавлен в избранное.";
            }
        } catch (PDOException $e) {
            $_SESSION['db_error'] = 'Ошибка при выполнении запроса: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request'])) {
    error_log("POST Data: " . print_r($_POST, true));
    $itemID = (int)$_POST['request'];
    $userID = $_SESSION['user_id'];
    if (empty($itemID)) {
        $_SESSION['error'] = "ID товара не может быть пустым.";
        // Можно сделать редирект или другую обработку
        header("Location: main.php");
        exit();
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO applications (userID, itemID) VALUES (?, ?)");
        $stmt->execute([+$userID, +$itemID]);
        $_SESSION['success'] = "Заявка успешно отправлена.";
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Ошибка при выполнении запроса: ' . $itemID . $e->getMessage();
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM main WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = "Товар успешно удален.";
    } catch (PDOException $e) {
        $_SESSION['db_error'] = 'Ошибка при выполнении запроса: ' . $e->getMessage();
    }
    header("Location: main.php");
    exit();
}

$main = [];

$favoriteIDs = [];
$stmt = $pdo->prepare("SELECT itemID FROM favorites WHERE userID = ?");
$stmt->execute([$userID]);
$favoriteIDs = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

$applicationIDs = [];
$stmt = $pdo->prepare("SELECT itemID FROM applications WHERE userID = ?");
$stmt->execute([$userID]);
$applicationIDs = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);


if ($isActive && $isClient) {
    $params = [];
    $query = "SELECT a.*, 
       seller_stats.favorite_count, 
       seller_stats.application_count
FROM main a
JOIN (
    SELECT a.sellerID, 
                     COUNT(f.itemID) AS favorite_count, 
                     COUNT(app.itemID) AS application_count 
              FROM main a
              LEFT JOIN favorites f ON a.id = f.itemID AND f.userID = ?
              LEFT JOIN applications app ON a.id = app.itemID AND app.userID = ?
              WHERE 1=1"; 
    
    $params[] = $userID;
    $params[] = $userID;
    
    if ($searchTerm) {
        $query .= " AND (name LIKE ? OR description LIKE ? OR location LIKE ?)";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
    }
    
    if ($isActive && $isClient) {
        $query .= " GROUP BY a.sellerID) AS seller_stats ON a.sellerID = seller_stats.sellerID
        ORDER BY seller_stats.application_count  DESC, seller_stats.favorite_count DESC;";
    }
    
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $main = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $params = [];
    $query = "SELECT * FROM main WHERE 1=1";

    if ($isseller) {
        $sellerID = $_SESSION['user_id'];
        $query .= " AND sellerID = ?";
        $params[] = $sellerID;
    } 
    
    if ($searchTerm) {
        $query .= " AND (name LIKE ? OR description LIKE ? OR location LIKE ?)";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $main = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// $stmt = $pdo->prepare($query);
// $stmt->execute($params);
// $main = $stmt->fetchAll(PDO::FETCH_ASSOC);

$favoriteIDs = [];
$stmt = $pdo->prepare("SELECT itemID FROM favorites WHERE userID = ?");
$stmt->execute([$userID]);
$favoriteIDs = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

$applicationIDs = [];
$stmt = $pdo->prepare("SELECT itemID FROM applications WHERE userID = ?");
$stmt->execute([$userID]);
$applicationIDs = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
?>

<?php require 'common/header.php'; ?>

<main style="padding: 20px;">
    <h2>Товары</h2>

    <?php if (isset($_SESSION['db_error'])): ?>
        <div class="notification error">
            <?= htmlspecialchars($_SESSION['db_error']) ?>
        </div>
        <?php unset($_SESSION['db_error']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="notification success">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <form method="post" style="margin-bottom: 20px;">
        <input type="text" name="searchTerm" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Поиск по названию, описанию и локации...">
        <button type="submit" name="search">Поиск</button>
    </form>

    <h3>Список товаров</h3>
    <table style="margin-bottom: 10px;">
        <tr>
            <th>ID</th>
            <th>Название</th>
            <th>Цена</th>
            <th>Описание</th>
            <th>Адрес</th>
            <th>Количество</th>
            <th>Состояние</th>
            <th>Доступно</th>
        </tr>
        <?php foreach ($main as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['id']) ?></td>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= htmlspecialchars($item['price']) ?></td>
                <td><?= htmlspecialchars($item['description']) ?></td>
                <td><?= htmlspecialchars($item['location']) ?></td>
                <td><?= htmlspecialchars($item['counts']) ?></td>
                <td><?= htmlspecialchars($item['sostoyanie']) ?></td>
                <td><?= htmlspecialchars($item['available'] ? 'Да' : 'Нет') ?></td>
                <td style="margin-bottom: 10px; margin-top: 10px; display: flex; flex-direction: column; align-items: space-between; justify-content: space-between; height: 100px;">
                    <?php if ($isseller || $isAdmin): ?>
                        <div>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                <button type="submit" name="edit" formaction="main.php?id=<?= $item['id'] ?>">Редактировать</button>
                            </form>
                            <form method="get" style="display: inline;">
                                <input type="hidden" name="delete" value="<?= $item['id'] ?>">
                                <button type="submit" onclick="return confirm('Вы уверены, что хотите удалить эту запись?');">Удалить</button>
                            </form>
                        </div>
                    <?php endif; ?>
                    <?php if ($isClient || $isAdmin): ?>
                        <div>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="save" value="<?= $item['id'] ?>">
                            <?php if (!in_array($item['id'], $favoriteIDs)): ?>
                                    <button type="submit">Добавить в избранное</button>
                            <?php else: ?>
                                <span>В избранном</span>
                            <?php endif; ?>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="request" value="<?= $item['id'] ?>">
                            <?php if (!in_array($item['id'], $applicationIDs)): ?>
                                <button type="submit">Оставить заявку</button>
                            <?php else: ?>
                                <span>Заявка отправлена</span>
                            <?php endif; ?>
                        </form>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php if (isset($_GET['id']) && ($isseller || $isAdmin)): ?>
        <?php
        $id = $_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM main WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>
        <div>
            <h3>Редактировать квартиру</h3>
            <form method="post">
                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                <label for="name">Название:</label>
                <input type="text" name="name" value="<?= htmlspecialchars($item['name']) ?>" required>
                
                <label for="price">Цена:</label>
                <input type="number" name="price" step="0.01" value="<?= htmlspecialchars($item['price']) ?>" required>
                
                <label for="description">Описание:</label>
                <textarea name="description" required><?= htmlspecialchars($item['description']) ?></textarea>
                
                <label for="location">Адрес:</label>
                <input type="text" name="location" value="<?= htmlspecialchars($item['location']) ?>" required>
                
                <label for="counts">Количество:</label>
                <input type="number" name="counts" value="<?= htmlspecialchars($item['counts']) ?>" required>
                
                <label for="sostoyanie">Состояние:</label>
                <input type="number" name="sostoyanie" step="0.1" value="<?= htmlspecialchars($item['sostoyanie']) ?>" required>
                
                <label for="available">Доступно: <input type="checkbox" name="available" <?= $item['available'] ? 'checked' : '' ?>> </label>

                <button type="submit" name="edit_save">Сохранить изменения</button>
                <a href="main.php">Отмена</a>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($isseller || $isAdmin): ?>
    <form method="post">
        <h3>Добавить товар</h3>
        <label for="name">Название:</label>
        <input type="text" name="name" required>
        
        <label for="price">Цена:</label>
        <input type="number" name="price" step="0.01" required>
        
        <label for="description">Описание:</label>
        <textarea name="description" required></textarea>
        
        <label for="location">Адрес:</label>
        <input type="text" name="location" required>
        
        <label for="counts">Количество:</label>
        <input type="number" name="counts" required>
        
        <label for="sostoyanie">Состояние:</label>
        <input type="number" name="sostoyanie" step="0.1" min="1" max="10" required>
        
        <label for="available">Доступно: <input type="checkbox" name="available" checked> </label>

        <button type="submit" name="create">Добавить товар</button>
    </form>
    <?php endif; ?>
</main>

<style>
.notification {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 10px;
    border-radius: 5px;
    z-index: 1000;
    animation: fadeInOut 5s forwards; 
}

.error {
    background-color: red;
    color: white;
}

.success {
    background-color: green;
    color: white;
}

@keyframes fadeInOut {
    0% {
        opacity: 0;
        transform: translateY(-20px);
    }
    20% {
        opacity: 1;
        transform: translateY(0);
    }
    80% {
        opacity: 1;
    }
    100% {
        opacity: 0;
        transform: translateY(-20px);
    }
}
</style>

<?php require 'common/footer.php'; ?>