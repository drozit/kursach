<!-- common/header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/index.css">
    <title>Товары</title>
</head>
<body>
<header>
    <div class="header-container">
        <h1>Добро пожаловать в наш магазин!</h1>
        <nav>
            <ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="main.php">Товары</a></li>
                    <li><a href="manage_applications.php">Заявки</a></li>
                    <?php if ($_SESSION['role'] === 'client' || $_SESSION['role'] === 'admin'): ?>
                        <li><a href="favorites.php">Избранное</a></li>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li><a href="users.php">Пользователи</a></li>
                    <?php endif; ?>
                    <li><span>Привет, <?= htmlspecialchars($_SESSION['username'] ?? 'Гость') ?>!</span></li>
                    <li><a href="logout.php" class="logout">Выйти</a></li>
                <?php else: ?>
                    <li><a href="register.php">Регистрация</a></li>
                    <li><a href="login.php">Логин</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <?php if (isset($_SESSION['db_error'])): ?>
        <div class="notification error"><?= htmlspecialchars($_SESSION['db_error']) ?></div>
        <?php unset($_SESSION['db_error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="notification error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="notification success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
</header>


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
