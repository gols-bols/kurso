<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

$action = $_GET['action'] ?? 'dashboard';
$user = current_user();

if (!$user && !in_array($action, ['login', 'register'], true)) {
    redirect('?action=login');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'register') {
        $name = trim($_POST['full_name'] ?? '');
        $email = mb_strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 6) {
            flash('error', 'Проверьте корректность ФИО, email и пароль (минимум 6 символов).');
            redirect('?action=register');
        }

        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            flash('error', 'Пользователь с таким email уже существует.');
            redirect('?action=register');
        }

        $stmt = db()->prepare('INSERT INTO users (full_name, email, password_hash, role, department) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), 'user', '']);

        flash('success', 'Регистрация успешна. Войдите в систему.');
        redirect('?action=login');
    }

    if ($action === 'login') {
        $email = mb_strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $foundUser = $stmt->fetch();

        if (!$foundUser || !password_verify($password, $foundUser['password_hash'])) {
            flash('error', 'Неверный email или пароль.');
            redirect('?action=login');
        }

        $_SESSION['user_id'] = (int) $foundUser['id'];
        redirect('?action=dashboard');
    }

    if (!$user) {
        redirect('?action=login');
    }

    if ($action === 'create_ticket') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';
        $validPriorities = ['low', 'medium', 'high', 'critical'];

        if ($title === '' || $description === '' || !in_array($priority, $validPriorities, true)) {
            flash('error', 'Заполните все поля заявки корректно.');
            redirect('?action=tickets');
        }

        $ticketNumber = 'SPK-' . date('Y') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        $stmt = db()->prepare('INSERT INTO tickets (ticket_number, title, description, priority, creator_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$ticketNumber, $title, $description, $priority, $user['id']]);

        flash('success', 'Заявка создана.');
        redirect('?action=tickets');
    }

    if ($action === 'change_status' && is_admin()) {
        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
        $status = $_POST['status'] ?? 'new';
        $validStatuses = ['new', 'in_progress', 'resolved', 'closed', 'reopened', 'cancelled'];

        if ($ticketId < 1 || !in_array($status, $validStatuses, true)) {
            flash('error', 'Некорректные данные статуса.');
            redirect('?action=tickets');
        }

        $stmt = db()->prepare('SELECT status FROM tickets WHERE id = ?');
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch();

        if (!$ticket) {
            flash('error', 'Заявка не найдена.');
            redirect('?action=tickets');
        }

        $update = db()->prepare('UPDATE tickets SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $update->execute([$status, $ticketId]);

        $log = db()->prepare('INSERT INTO status_logs (ticket_id, changed_by, from_status, to_status) VALUES (?, ?, ?, ?)');
        $log->execute([$ticketId, $user['id'], $ticket['status'], $status]);

        flash('success', 'Статус обновлен.');
        redirect('?action=tickets');
    }

    if ($action === 'add_comment') {
        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
        $body = trim($_POST['body'] ?? '');

        if ($ticketId < 1 || $body === '') {
            flash('error', 'Комментарий пустой или заявка не выбрана.');
            redirect('?action=tickets');
        }

        $stmt = db()->prepare('SELECT id FROM tickets WHERE id = ?');
        $stmt->execute([$ticketId]);
        if (!$stmt->fetch()) {
            flash('error', 'Заявка не найдена.');
            redirect('?action=tickets');
        }

        $insert = db()->prepare('INSERT INTO comments (ticket_id, author_id, body) VALUES (?, ?, ?)');
        $insert->execute([$ticketId, $user['id'], $body]);

        flash('success', 'Комментарий добавлен.');
        redirect('?action=ticket&id=' . $ticketId);
    }
}

if ($action === 'logout') {
    session_destroy();
    redirect('?action=login');
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$flashes = flashes();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ИС заявок СПК</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f3f4f6; }
        header { background: #1f2937; color: #fff; padding: 14px 20px; }
        nav a { color: #fff; margin-right: 12px; text-decoration: none; }
        main { max-width: 980px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 10px; }
        .flash { padding: 10px; border-radius: 8px; margin-bottom: 10px; }
        .success { background: #dcfce7; }
        .error { background: #fee2e2; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        input, textarea, select { width: 100%; padding: 8px; margin: 6px 0 12px; }
        button { background: #2563eb; color: white; border: none; padding: 10px 14px; border-radius: 8px; cursor: pointer; }
    </style>
</head>
<body>
<header>
    <strong>ИС управления заявками — ГБПОУ МО СПК</strong>
    <?php if ($user): ?>
        <nav>
            <a href="?action=dashboard">Главная</a>
            <a href="?action=tickets">Заявки</a>
            <a href="?action=logout">Выход (<?= h($user['full_name']) ?>)</a>
        </nav>
    <?php endif; ?>
</header>
<main>
    <?php foreach ($flashes as $msg): ?>
        <div class="flash <?= h($msg['type']) ?>"><?= h($msg['message']) ?></div>
    <?php endforeach; ?>

    <?php if ($action === 'login'): ?>
        <h2>Вход</h2>
        <form method="post" action="?action=login">
            <label>Email</label><input type="email" name="email" required>
            <label>Пароль</label><input type="password" name="password" required>
            <button type="submit">Войти</button>
        </form>
        <p>Нет аккаунта? <a href="?action=register">Регистрация</a></p>
        <p>Тестовый администратор: <code>admin@spk.local / admin123</code></p>

    <?php elseif ($action === 'register'): ?>
        <h2>Регистрация</h2>
        <form method="post" action="?action=register">
            <label>ФИО</label><input name="full_name" required>
            <label>Email</label><input type="email" name="email" required>
            <label>Пароль</label><input type="password" name="password" minlength="6" required>
            <button type="submit">Создать аккаунт</button>
        </form>

    <?php elseif ($action === 'dashboard'): ?>
        <?php
        $total = (int) db()->query('SELECT COUNT(*) FROM tickets')->fetchColumn();
        $open = (int) db()->query("SELECT COUNT(*) FROM tickets WHERE status IN ('new', 'in_progress', 'reopened')")->fetchColumn();
        $closed = (int) db()->query("SELECT COUNT(*) FROM tickets WHERE status = 'closed'")->fetchColumn();
        ?>
        <h2>Панель управления</h2>
        <p>Всего заявок: <strong><?= $total ?></strong></p>
        <p>Открытые: <strong><?= $open ?></strong></p>
        <p>Закрытые: <strong><?= $closed ?></strong></p>

    <?php elseif ($action === 'tickets'): ?>
        <h2>Заявки</h2>
        <form method="post" action="?action=create_ticket">
            <label>Тема</label><input name="title" maxlength="200" required>
            <label>Описание</label><textarea name="description" rows="4" required></textarea>
            <label>Приоритет</label>
            <select name="priority">
                <option value="low">Низкий</option>
                <option value="medium" selected>Средний</option>
                <option value="high">Высокий</option>
                <option value="critical">Критический</option>
            </select>
            <button type="submit">Создать заявку</button>
        </form>

        <?php
        $rows = db()->query('SELECT t.*, u.full_name as creator_name FROM tickets t JOIN users u ON u.id=t.creator_id ORDER BY t.id DESC')->fetchAll();
        ?>
        <table>
            <tr><th>№</th><th>Тема</th><th>Статус</th><th>Приоритет</th><th>Автор</th><th></th></tr>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= h($row['ticket_number']) ?></td>
                    <td><?= h($row['title']) ?></td>
                    <td><?= h($row['status']) ?></td>
                    <td><?= h($row['priority']) ?></td>
                    <td><?= h($row['creator_name']) ?></td>
                    <td><a href="?action=ticket&id=<?= (int) $row['id'] ?>">Открыть</a></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <?php if (is_admin()): ?>
            <h3>Смена статуса (администратор)</h3>
            <form method="post" action="?action=change_status">
                <label>ID заявки</label><input type="number" name="ticket_id" min="1" required>
                <label>Новый статус</label>
                <select name="status">
                    <option value="new">new</option>
                    <option value="in_progress">in_progress</option>
                    <option value="resolved">resolved</option>
                    <option value="closed">closed</option>
                    <option value="reopened">reopened</option>
                    <option value="cancelled">cancelled</option>
                </select>
                <button type="submit">Обновить статус</button>
            </form>
        <?php endif; ?>

    <?php elseif ($action === 'ticket'): ?>
        <?php
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = db()->prepare('SELECT t.*, u.full_name as creator_name FROM tickets t JOIN users u ON u.id=t.creator_id WHERE t.id = ?');
        $stmt->execute([$id]);
        $ticket = $stmt->fetch();
        ?>
        <?php if (!$ticket): ?>
            <p>Заявка не найдена.</p>
        <?php else: ?>
            <h2><?= h($ticket['title']) ?></h2>
            <p><strong>Номер:</strong> <?= h($ticket['ticket_number']) ?></p>
            <p><strong>Описание:</strong> <?= nl2br(h($ticket['description'])) ?></p>
            <p><strong>Статус:</strong> <?= h($ticket['status']) ?> | <strong>Приоритет:</strong> <?= h($ticket['priority']) ?></p>
            <p><strong>Автор:</strong> <?= h($ticket['creator_name']) ?></p>

            <h3>Комментарии</h3>
            <?php
            $commentsStmt = db()->prepare('SELECT c.*, u.full_name FROM comments c JOIN users u ON u.id=c.author_id WHERE c.ticket_id=? ORDER BY c.id DESC');
            $commentsStmt->execute([$id]);
            $comments = $commentsStmt->fetchAll();
            ?>
            <?php foreach ($comments as $comment): ?>
                <p><strong><?= h($comment['full_name']) ?>:</strong> <?= h($comment['body']) ?> <small>(<?= h($comment['created_at']) ?>)</small></p>
            <?php endforeach; ?>

            <form method="post" action="?action=add_comment">
                <input type="hidden" name="ticket_id" value="<?= (int) $id ?>">
                <label>Новый комментарий</label>
                <textarea name="body" rows="3" required></textarea>
                <button type="submit">Добавить</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</main>
</body>
</html>
