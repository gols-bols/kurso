@extends('layouts.app')

@section('content')
<main class="page">
    <section class="hero">
        <div>
            <h1>Журнал заявок</h1>
            <p>
                В этом разделе отображаются все зарегистрированные обращения. Интерфейс показывает текущий статус,
                уровень приоритета и краткое описание, чтобы проект выглядел цельно и понятно на демонстрации.
            </p>
        </div>
        <div class="hero-badge">СПК Helpdesk</div>
    </section>

    <section class="panel stack">
        <div class="toolbar">
            <div>
                <strong>Всего заявок: {{ $tickets->count() }}</strong>
                <div class="footer-note">Данные выводятся в порядке последних созданных записей.</div>
            </div>

            <div class="toolbar-actions">
                @if(auth()->user()->role === 'user')
                    <a class="button secondary" href="{{ route('tickets.create') }}">Создать заявку</a>
                @endif
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="button-muted" type="submit">Выйти</button>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="flash">{{ session('success') }}</div>
        @endif

        @if($tickets->isEmpty())
            <div class="empty">
                Пока заявок нет. Создайте первую запись, чтобы продемонстрировать работу системы.
            </div>
        @else
            <div class="grid tickets-grid">
                @foreach($tickets as $ticket)
                    <article class="ticket-card">
                        <div class="meta">
                            <span class="pill">Статус: {{ $ticket->status }}</span>
                            <span class="pill pill-muted">Приоритет: {{ $ticket->priority }}</span>
                        </div>

                        <div>
                            <h3>{{ $ticket->title }}</h3>
                            <p>{{ $ticket->description }}</p>
                        </div>

                        <div class="footer-note">
                            Автор ID: {{ $ticket->created_by }}<br>
                            Создано: {{ optional($ticket->created_at)->format('d.m.Y H:i') ?? '—' }}
                        </div>

                        @if(in_array(auth()->user()->role, ['admin', 'manager'], true) || (int) auth()->id() === (int) $ticket->created_by)
                            <div class="toolbar-actions">
                                <a class="button secondary" href="{{ route('tickets.edit', $ticket) }}">Редактировать</a>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</main>
@endsection
