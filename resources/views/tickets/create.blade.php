<!doctype html>
<html lang="ru">
<body>
<h1>Новая заявка</h1>
<form method="post" action="{{ route('tickets.store') }}">
    @csrf
    <input name="title" placeholder="Заголовок" required>
    <textarea name="description" placeholder="Описание" required></textarea>
    <select name="priority">
        <option value="low">Низкий</option>
        <option value="normal" selected>Обычный</option>
        <option value="high">Высокий</option>
    </select>
    <button type="submit">Сохранить</button>
</form>
</body>
</html>
