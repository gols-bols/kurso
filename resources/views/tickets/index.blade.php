<!doctype html>
<html lang="ru">
<body>
<h1>Заявки</h1>
<a href="{{ route('tickets.create') }}">Создать</a>
<form method="post" action="{{ route('logout') }}">@csrf <button type="submit">Выйти</button></form>
<ul>
@foreach($tickets as $ticket)
    <li>{{ $ticket->title }} ({{ $ticket->status }})</li>
@endforeach
</ul>
</body>
</html>
