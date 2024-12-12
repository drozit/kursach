<?php
session_start();

$dsn = 'mysql:host=localhost;dbname=php_laba3;charset=utf8';
$username = 'root';
$password = '';

require 'common/header.php';

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

$params = [];
$query = "
    SELECT applications.id, main.name AS item_name, users.name AS user_name, applications.status
    FROM applications
    JOIN main ON applications.itemID = main.id
    JOIN users ON applications.userID = users.id
    WHERE 1=1";

if ($isseller || $isAdmin) {
    $sellerID = $_SESSION['user_id'];
    $query .= " AND main.sellerID = ?";
    $params[] = $sellerID;
}

if ($isClient) {
    $userID = $_SESSION['user_id'];
    $query .= " AND applications.userID = ?";
    $params[] = $userID;
}

$stmt = $pdo->prepare($query);

$stmt->execute($params);

$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'])) {
    // Обработка изменения статуса заявки
    if (isset($_POST['status'])) {
        $applicationID = $_POST['application_id'];
        $status = $_POST['status'];

        $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->execute([$status, $applicationID]);

        $_SESSION['success'] = "Статус заявки успешно обновлен.";
        header('Location: manage_applications.php'); // Перенаправление после обновления
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_application_id'])) {
    $applicationID = $_POST['delete_application_id'];

    // Проверка, является ли пользователь клиентом
    if ($isClient) {
        $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ? AND userID = ?");
        $stmt->execute([$applicationID, $userID]);

        $_SESSION['success'] = "Заявка успешно удалена.";
        header('Location: manage_applications.php'); // Перенаправление после удаления
        exit();
    }
}
?>

<main>
    <?php if (empty($applications)): ?>
        <p>Нет заявок для обработки.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Название товара</th>
                    <th>Имя пользователя</th>
                    <th>Статус</th>
                    <?php if ($isseller || $isAdmin): ?>
                        <th>Действия</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $application): ?>
                    <tr>
                        <td><?= htmlspecialchars($application['item_name']) ?></td>
                        <td><?= htmlspecialchars($application['user_name']) ?></td>
                        <td><?= htmlspecialchars($application['status']) ?></td>
                        <!-- <td>
                            <a href="chat.php?applicationID=<?php echo $application['id']; ?>" class="btn">Чат</a>
                        </td> -->
                        <?php if ($isseller || $isAdmin): ?>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="application_id" value="<?= $application['id'] ?>">
                                    <select name="status">
                                        <option value="in progress" <?= $application['status'] === 'in progress' ? 'selected' : '' ?>>Ожидает</option>
                                        <option value="agreed" <?= $application['status'] === 'agreed' ? 'selected' : '' ?>>Согласовано</option>
                                        <option value="rejected" <?= $application['status'] === 'rejected' ? 'selected' : '' ?>>Отклонено</option>
                                    </select>
                                    <button type="submit">Обновить статус</button>
                                </form>
                            </td>
                        <?php endif; ?>
                        <?php if ($isClient): ?>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="delete_application_id" value="<?= $application['id'] ?>">
                                    <button type="submit" onclick="return confirm('Вы уверены, что хотите удалить эту заявку?');">Удалить</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>