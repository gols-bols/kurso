<!doctype html>
<html lang="ru">
<body>
<h1>Вход</h1>
@if($errors->any())
    <p>{{ $errors->first() }}</p>
@endif
<form method="post" action="/login">
    @csrf
    <input type="email" name="email" placeholder="email" required>
    <input type="password" name="password" placeholder="password" required>
    <button type="submit">Войти</button>
</form>
</body>
</html>
