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
        return view('tickets.create');
    }

    public function store(Request $request): RedirectResponse
    {
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
}
