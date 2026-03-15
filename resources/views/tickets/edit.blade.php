@extends('layouts.app')

@section('content')
<main class="page">
    <section class="hero">
        <div>
            <h1>Редактирование заявки</h1>
            <p>
                Здесь можно изменить основные параметры существующей заявки. Администратор и менеджер редактируют любые записи,
                обычный пользователь — только свои.
            </p>
        </div>
        <a class="hero-badge" href="{{ route('tickets.index') }}">К списку</a>
    </section>

    <section class="panel stack">
        @if($errors->any())
            <ul class="error-list">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form class="form-grid" method="post" action="{{ route('tickets.update', $ticket) }}">
            @csrf
            @method('put')

            <label>
                Заголовок заявки
                <input name="title" value="{{ old('title', $ticket->title) }}" required>
            </label>

            <label>
                Подробное описание
                <textarea name="description" required>{{ old('description', $ticket->description) }}</textarea>
            </label>

            <label>
                Приоритет
                <select name="priority">
                    <option value="low" @selected(old('priority', $ticket->priority) === 'low')>Низкий</option>
                    <option value="normal" @selected(old('priority', $ticket->priority) === 'normal')>Обычный</option>
                    <option value="high" @selected(old('priority', $ticket->priority) === 'high')>Высокий</option>
                </select>
            </label>

            <label>
                Статус
                <select name="status">
                    <option value="open" @selected(old('status', $ticket->status) === 'open')>Открыта</option>
                    <option value="in_progress" @selected(old('status', $ticket->status) === 'in_progress')>В работе</option>
                    <option value="resolved" @selected(old('status', $ticket->status) === 'resolved')>Решена</option>
                    <option value="closed" @selected(old('status', $ticket->status) === 'closed')>Закрыта</option>
                </select>
            </label>

            <div class="toolbar-actions">
                <button type="submit">Сохранить изменения</button>
                <a class="button secondary" href="{{ route('tickets.index') }}">Отмена</a>
            </div>
        </form>
    </section>
</main>
@endsection
