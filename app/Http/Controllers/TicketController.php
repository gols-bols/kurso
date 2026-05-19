<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $filters = request()->validate([
            'status' => ['nullable', 'in:open,in_progress,resolved,closed'],
            'priority' => ['nullable', 'in:low,normal,high'],
            'campus' => ['nullable', 'in:main,1,2,3'],
        ]);

        $query = $this->visibleTicketsQuery()
            ->with(['creator', 'assignee'])
            ->withCount('comments')
            ->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['campus']) && $user?->role === 'admin') {
            $query->where('campus', $filters['campus']);
        }

        $tickets = $query->get();

        return view('tickets.index', compact('tickets', 'filters'));
    }

    public function dashboard(): View
    {
        $tickets = $this->visibleTicketsQuery()
            ->with(['assignee'])
            ->latest()
            ->get();

        $total = max($tickets->count(), 1);

        $statusStats = collect(Ticket::STATUS_LABELS)
            ->map(fn (string $label, string $status): array => [
                'label' => $label,
                'count' => $tickets->where('status', $status)->count(),
                'percent' => round($tickets->where('status', $status)->count() / $total * 100),
                'class' => Ticket::STATUS_COLORS[$status] ?? 'status-open',
            ]);

        $campusStats = collect(Ticket::CAMPUS_LABELS)
            ->map(fn (string $label, string $campus): array => [
                'label' => $label,
                'count' => $tickets->where('campus', $campus)->count(),
                'percent' => round($tickets->where('campus', $campus)->count() / $total * 100),
            ])
            ->filter(fn (array $item): bool => $item['count'] > 0 || auth()->user()?->role === 'admin');

        $priorityStats = collect([
            'high' => 'Высокий',
            'normal' => 'Обычный',
            'low' => 'Низкий',
        ])->map(fn (string $label, string $priority): array => [
            'label' => $label,
            'count' => $tickets->where('priority', $priority)->count(),
            'percent' => round($tickets->where('priority', $priority)->count() / $total * 100),
        ]);

        $assigneeStats = $tickets
            ->groupBy(fn (Ticket $ticket): string => $ticket->assignee?->name ?? 'Не назначен')
            ->map(fn ($items, string $name): array => [
                'name' => $name,
                'count' => $items->count(),
                'active' => $items->whereIn('status', ['open', 'in_progress'])->count(),
            ])
            ->sortByDesc('count')
            ->values();

        $latest = $tickets->take(5);

        return view('tickets.dashboard', compact(
            'tickets',
            'statusStats',
            'campusStats',
            'priorityStats',
            'assigneeStats',
            'latest'
        ));
    }

    public function show(Ticket $ticket): View
    {
        abort_unless($this->canView($ticket), 403);

        $ticket->load(['creator', 'assignee', 'comments.user']);

        return view('tickets.show', compact('ticket'));
    }

    public function create(): View
    {
        abort_unless($this->canCreate(), 403);

        return view('tickets.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canCreate(), 403);

        $user = auth()->user();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'in:low,normal,high'],
            'campus' => ['required', 'in:main,1,2,3'],
            'room' => ['required', 'string', 'max:50'],
        ]);

        Ticket::query()->create([
            ...$data,
            'status' => 'open',
            'requester_name' => $user?->name,
            'created_by' => (int) auth()->id(),
        ]);

        return redirect()->route('tickets.index')->with('success', 'Заявка создана.');
    }

    public function edit(Ticket $ticket): View
    {
        abort_unless($this->canEdit($ticket), 403);

        $managers = User::query()
            ->where('role', 'manager')
            ->orderBy('name')
            ->get();

        return view('tickets.edit', compact('ticket', 'managers'));
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($this->canEdit($ticket), 403);

        $user = auth()->user();

        if ($user?->role === 'admin') {
            $data = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string'],
                'priority' => ['required', 'in:low,normal,high'],
                'status' => ['required', 'in:open,in_progress,resolved,closed'],
                'campus' => ['required', 'in:main,1,2,3'],
                'room' => ['required', 'string', 'max:50'],
                'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            ]);
        } else {
            $data = $request->validate([
                'priority' => ['required', 'in:low,normal,high'],
                'status' => ['required', 'in:open,in_progress,resolved,closed'],
            ]);
        }

        $before = $ticket->only(['priority', 'status', 'assignee_id']);

        $ticket->update($data);

        $this->writeUpdateHistory($ticket, $before);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Заявка обновлена.');
    }

    public function comment(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($this->canView($ticket), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $ticket->comments()->create([
            'user_id' => (int) auth()->id(),
            'type' => 'comment',
            'body' => $data['body'],
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Комментарий добавлен в историю заявки.');
    }

    private function canEdit(Ticket $ticket): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'manager' && $user->campus === $ticket->campus;
    }

    private function canView(Ticket $ticket): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'manager') {
            return $user->campus === $ticket->campus;
        }

        return (int) $ticket->created_by === (int) $user->id;
    }

    private function canCreate(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->role === 'user';
    }

    private function visibleTicketsQuery(): Builder
    {
        $user = auth()->user();

        $query = Ticket::query();

        if ($user?->role === 'manager') {
            $query->where('campus', $user->campus);
        }

        if ($user?->role === 'user') {
            $query->where('created_by', $user->id);
        }

        return $query;
    }

    private function writeUpdateHistory(Ticket $ticket, array $before): void
    {
        $ticket->refresh();

        $changes = [];

        if (($before['status'] ?? null) !== $ticket->status) {
            $changes[] = 'статус: ' . $ticket->status_label;
        }

        if (($before['priority'] ?? null) !== $ticket->priority) {
            $changes[] = 'приоритет: ' . $ticket->priority_label;
        }

        if ((int) ($before['assignee_id'] ?? 0) !== (int) ($ticket->assignee_id ?? 0)) {
            $ticket->load('assignee');
            $changes[] = 'исполнитель: ' . ($ticket->assignee?->name ?? 'не назначен');
        }

        if ($changes === []) {
            return;
        }

        $ticket->comments()->create([
            'user_id' => (int) auth()->id(),
            'type' => 'system',
            'body' => 'Обновлены рабочие параметры заявки: ' . implode(', ', $changes) . '.',
        ]);
    }
}
