<?php
session_start();

require 'func/db.php';
require 'func/create_user.php';
require 'func/user_validation.php'; 

$dsn = 'mysql:host=localhost;dbname=php_laba3;charset=utf8';
$username = 'root';
$password = '';

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


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $userIdToDelete = $_POST['user_id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userIdToDelete]);

        $_SESSION['success'] = "Пользователь успешно удалён.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Не удалось удалить пользователя. Ошибка: " . $e->getMessage();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    try {
        $pdo = getPDO(); 
        list($errors, $userData) = validateUserData($_POST); 


        if (!empty($errors)) {
            $error = implode(' ', $errors);
            $_SESSION['error'] = $error;
        } elseif (userExists($pdo, $userData['username'])) {
            $error = "Пользователь с таким логином уже существует.";
        } else  {
        
            $userId = createUser(
                $pdo, $userData['name'], 
                $userData['username'], 
                $userData['password'],
                $userData['age'],
                $userData['gender'], 
                $userData['role']
            );
            $_SESSION['success'] = "Пользователь успешно добавлен.";
        
        // $_SESSION['user_id'] = $userId;
        // $_SESSION['username'] = $username;
        // $_SESSION['role'] = $role;

        // header("Location: apartments.php");
        // exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'База данных недоступна. Пожалуйста, попробуйте позже.' . $e->getMessage();
}
}

$query = "SELECT * FROM users";
$stmt = $pdo->query($query);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require 'common/header.php'; ?>

<main style="padding: 20px;">
    <h2>Пользователи</h2>

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

    <?php if (!empty($error)): ?>
        <div class="notification error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <h3>Добавить пользователя</h3>
        <label for="name">Имя:</label>
        <input type="text" name="name" required>
        
        <label for="username">Логин:</label>
        <input type="text" name="username" required>
        
        <label for="password">Пароль:</label>
        <input type="password" name="password" required>
        
        <label for="age">Возраст:</label>
        <input type="number" name="age" required>
        
        <label for="gender">Пол:
            <select name="gender" required>
                <option value="male">Мужской</option>
                <option value="female">Женский</option>
        </select>
        </label>

        <label for="role">Роль: 
            <select name="role" required>
                <option value="seller">Продавец</option>
                <option value="client">Клиент</option>
                <option value="admin">Администратор</option>
            </select>
        </label>

        <label for="create_usre">
            <button type="submit" name="create_user">Добавить пользователя</button>
        </label>
    </form>

    <h3>Список пользователей</h3>
<table>
    <tr>
        <th>ID</th>
        <th>Имя</th>
        <th>Логин</th>
        <th>Возраст</th>
        <th>Пол</th>
        <th>Роль</th>
        <th>Действия</th>
    </tr>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user['id']) ?></td>
            <td><?= htmlspecialchars($user['name']) ?></td>
            <td><?= htmlspecialchars($user['username']) ?></td>
            <td><?= htmlspecialchars($user['age']) ?></td>
            <td><?= htmlspecialchars(ucfirst($user['gender'])) ?></td>
            <td><?= htmlspecialchars(ucfirst($user['role'])) ?></td>
            <td>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                    <button type="submit" name="delete_user" onclick="return confirm('Вы уверены, что хотите удалить этого пользователя?');">Удалить</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

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