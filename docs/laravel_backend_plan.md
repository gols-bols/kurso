# Разработка серверной части ИС управления заявками (Laravel)

## 1) Анализ предметной области

**Контекст:** ГБПОУ МО «СПК», обработка внутренних заявок (ИТ/хозяйственные/административные).

### Основные роли
- **Пользователь (сотрудник/преподаватель):** создает заявки, отслеживает статус, добавляет комментарии.
- **Системный администратор (исполнитель):** принимает заявки в работу, меняет статусы, ведет журнал действий.
- **Руководитель/диспетчер:** контролирует SLA, распределяет заявки, формирует отчеты.

### Бизнес-процесс обработки заявки
1. Пользователь создает заявку.
2. Система присваивает номер и статус `new`.
3. Диспетчер/администратор назначает исполнителя, статус -> `in_progress`.
4. В ходе работ возможны комментарии и вложения.
5. После выполнения статус -> `resolved`.
6. После подтверждения пользователем статус -> `closed`.
7. Если проблема повторяется/не устранена — `reopened`.

### Ключевые требования
- Централизованное хранение данных и истории изменений.
- Ролевая модель доступа.
- Валидация входящих данных и защита API.
- Аудит действий сотрудников.

---

## 2) Проектирование структуры БД

Ниже приведена рекомендуемая модель данных для PostgreSQL/MySQL.

### Таблица `users`
- `id` (PK)
- `full_name`
- `email` (unique)
- `password`
- `role` (`user`, `admin`, `manager`)
- `department`
- `is_active`
- `created_at`, `updated_at`

### Таблица `categories`
- `id` (PK)
- `name`
- `description`
- `created_at`, `updated_at`

### Таблица `tickets`
- `id` (PK)
- `ticket_number` (unique)
- `title`
- `description`
- `priority` (`low`, `medium`, `high`, `critical`)
- `status` (`new`, `in_progress`, `resolved`, `closed`, `reopened`, `cancelled`)
- `creator_id` (FK -> users.id)
- `assignee_id` (FK -> users.id, nullable)
- `category_id` (FK -> categories.id)
- `due_at` (nullable)
- `resolved_at` (nullable)
- `closed_at` (nullable)
- `created_at`, `updated_at`

### Таблица `ticket_comments`
- `id` (PK)
- `ticket_id` (FK -> tickets.id)
- `author_id` (FK -> users.id)
- `comment`
- `is_internal` (bool)
- `created_at`, `updated_at`

### Таблица `attachments`
- `id` (PK)
- `ticket_id` (FK -> tickets.id)
- `uploaded_by` (FK -> users.id)
- `path`
- `original_name`
- `mime_type`
- `size`
- `created_at`, `updated_at`

### Таблица `ticket_status_logs`
- `id` (PK)
- `ticket_id` (FK -> tickets.id)
- `changed_by` (FK -> users.id)
- `from_status`
- `to_status`
- `comment` (nullable)
- `created_at`

### Таблица `personal_access_tokens`
- стандартная таблица Laravel Sanctum для токенов API.

---

## 3) Серверная логика на Laravel

### Технологический стек
- Laravel 11+
- PHP 8.2+
- PostgreSQL/MySQL
- Laravel Sanctum (token-based auth)
- Policies + Gates для авторизации

### Доменные правила
- Пользователь может редактировать заявку только в статусе `new` и только свою.
- Перевод в `in_progress` доступен администратору/менеджеру.
- Закрытие заявки доступно менеджеру или автору после `resolved`.
- Каждая смена статуса фиксируется в `ticket_status_logs`.

### Слои
- **Controllers**: прием HTTP-запросов, делегирование сервисам.
- **FormRequest**: валидация входных данных.
- **Services**: бизнес-логика (создание/смена статуса/назначение).
- **Policies**: проверка прав доступа.
- **Resources**: единый формат JSON-ответов.

---

## 4) API для клиентской части

Базовый префикс: `/api/v1`

### Аутентификация
- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/logout`
- `GET /auth/me`

### Заявки
- `GET /tickets` — список с фильтрами (`status`, `priority`, `assignee_id`, `date_from`, `date_to`)
- `POST /tickets` — создание
- `GET /tickets/{id}` — просмотр
- `PATCH /tickets/{id}` — редактирование
- `POST /tickets/{id}/assign` — назначение исполнителя
- `POST /tickets/{id}/status` — смена статуса
- `POST /tickets/{id}/comments` — добавить комментарий
- `POST /tickets/{id}/attachments` — загрузить файл

### Справочники и отчеты
- `GET /categories`
- `POST /categories` (admin/manager)
- `GET /reports/tickets-summary` (manager)

### Пример JSON-ответа
```json
{
  "data": {
    "id": 45,
    "ticket_number": "SPK-2026-000045",
    "title": "Не работает принтер в аудитории 21",
    "status": "in_progress",
    "priority": "high"
  }
}
```

---

## 5) Аутентификация и авторизация

### Аутентификация
- Использовать **Laravel Sanctum**.
- Логин по email/password.
- Выдача bearer-токена с ограничением срока действия (по политике ИБ).

### Авторизация
- RBAC по ролям: `user`, `admin`, `manager`.
- Дополнительно — объектные ограничения через `TicketPolicy`.
- Проверки доступа в контроллерах через `authorize()` и middleware.

---

## 6) Защита данных и валидация

### Валидация
- Обязательное использование `FormRequest` для всех write-endpoints.
- Ограничения длины, формата и допустимых enum-значений.
- Проверка MIME/размера файлов вложений.

### Безопасность
- Хеширование паролей через `Hash::make` (bcrypt/argon2).
- Защита от массового присваивания (`$fillable` / `$guarded`).
- Ограничение частоты запросов (`throttle:api`, усиленный rate-limit на `/auth/login`).
- CORS-конфигурация только для доверенных источников.
- Логирование ошибок и аудит критических действий.

### Надежность
- Транзакции при критических операциях (`DB::transaction`).
- Индексы на поля фильтрации (`status`, `priority`, `assignee_id`, `created_at`).
- Мягкое удаление (Soft Deletes) при необходимости регламентов хранения.

---

## Минимальная структура Laravel-проекта

```text
app/
  Http/Controllers/Api/V1/
    AuthController.php
    TicketController.php
    TicketStatusController.php
    TicketCommentController.php
  Http/Requests/
    Auth/LoginRequest.php
    Tickets/StoreTicketRequest.php
    Tickets/UpdateTicketRequest.php
    Tickets/ChangeStatusRequest.php
  Models/
    User.php
    Ticket.php
    Category.php
    TicketComment.php
    Attachment.php
    TicketStatusLog.php
  Policies/
    TicketPolicy.php
  Services/
    TicketService.php
routes/
  api.php
database/migrations/
```

---

## Критерии готовности (Definition of Done)
- Реализованы CRUD-операции по заявкам и комментариям.
- Реализованы назначение исполнителя и жизненный цикл статусов.
- Все write-запросы покрыты валидацией.
- Включены аутентификация и ролевой доступ.
- Добавлены feature-тесты для ключевых сценариев API.
- Подготовлена документация API (Postman/OpenAPI).
