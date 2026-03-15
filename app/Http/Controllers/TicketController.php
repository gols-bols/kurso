<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(): View
    {
        $tickets = Ticket::query()->latest()->get();

        return view('tickets.index', compact('tickets'));
    }

    public function create(): View
    {
        abort_unless($this->canCreate(), 403);

        return view('tickets.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canCreate(), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'in:low,normal,high'],
        ]);

        Ticket::query()->create([
            ...$data,
            'status' => 'open',
            'created_by' => (int) auth()->id(),
        ]);

        return redirect()->route('tickets.index')->with('success', 'Заявка создана.');
    }

    public function edit(Ticket $ticket): View
    {
        abort_unless($this->canEdit($ticket), 403);

        return view('tickets.edit', compact('ticket'));
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($this->canEdit($ticket), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'in:low,normal,high'],
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
        ]);

        $ticket->update($data);

        return redirect()->route('tickets.index')->with('success', 'Заявка обновлена.');
    }

    private function canEdit(Ticket $ticket): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return in_array($user->role, ['admin', 'manager'], true) || (int) $ticket->created_by === (int) $user->id;
    }

    private function canCreate(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->role === 'user';
    }
}
