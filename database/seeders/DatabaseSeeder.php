<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@spk.local'],
            [
                'name' => 'Администратор СПК',
                'password' => 'password',
                'role' => 'admin',
            ]
        );

        $manager = User::query()->updateOrCreate(
            ['email' => 'manager@spk.local'],
            [
                'name' => 'Менеджер СПК',
                'password' => 'password',
                'role' => 'manager',
            ]
        );

        $user = User::query()->updateOrCreate(
            ['email' => 'user@spk.local'],
            [
                'name' => 'Пользователь СПК',
                'password' => 'password',
                'role' => 'user',
            ]
        );

        Ticket::query()->firstOrCreate(
            [
                'title' => 'Не работает принтер в кабинете 21',
                'created_by' => $user->id,
            ],
            [
                'description' => 'Принтер включается, но документы остаются в очереди печати и не выводятся на устройство.',
                'priority' => 'high',
                'status' => 'open',
            ]
        );

        Ticket::query()->firstOrCreate(
            [
                'title' => 'Требуется установка офисного пакета',
                'created_by' => $manager->id,
            ],
            [
                'description' => 'На новом рабочем месте необходимо установить базовый набор программ для подготовки учебных материалов.',
                'priority' => 'normal',
                'status' => 'in_progress',
            ]
        );

        Ticket::query()->firstOrCreate(
            [
                'title' => 'Нет доступа к локальной сети',
                'created_by' => $admin->id,
            ],
            [
                'description' => 'Компьютер в методическом кабинете не получает сетевой адрес и не открывает внутренние ресурсы колледжа.',
                'priority' => 'high',
                'status' => 'resolved',
            ]
        );
    }
}
