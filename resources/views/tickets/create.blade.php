@extends('layouts.app')

@section('content')
<main class="page">
    <section class="hero">
        <div>
            <h1>Создание новой заявки</h1>
            <p>
                Форма предназначена для регистрации обращения пользователя. В текущей версии проекта доступны
                базовые поля: заголовок, описание и приоритет.
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

        <form class="form-grid" method="post" action="{{ route('tickets.store') }}">
            @csrf

            <label>
                Заголовок заявки
                <span class="hint">Коротко опишите проблему, чтобы ее можно было быстро идентифицировать.</span>
                <input name="title" value="{{ old('title') }}" placeholder="Например: Не работает принтер в кабинете 21" required>
            </label>

            <label>
                Подробное описание
                <span class="hint">Укажите обстоятельства возникновения проблемы, оборудование и желаемый результат.</span>
                <textarea name="description" placeholder="Опишите ситуацию подробнее" required>{{ old('description') }}</textarea>
            </label>

            <label>
                Приоритет
                <span class="hint">Приоритет влияет на очередность обработки заявки.</span>
                <select name="priority">
                    <option value="low" @selected(old('priority') === 'low')>Низкий</option>
                    <option value="normal" @selected(old('priority', 'normal') === 'normal')>Обычный</option>
                    <option value="high" @selected(old('priority') === 'high')>Высокий</option>
                </select>
            </label>

            <div class="toolbar-actions">
                <button type="submit">Сохранить заявку</button>
                <a class="button secondary" href="{{ route('tickets.index') }}">Отмена</a>
            </div>
        </form>
    </section>
</main>
@endsection
