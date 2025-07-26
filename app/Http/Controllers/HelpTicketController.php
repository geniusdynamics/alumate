<?php

namespace App\Http\Controllers;

use App\Models\HelpTicket;
use App\Models\HelpTicketResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HelpTicketController extends Controller
{
    public function index()
    {
        $tickets = HelpTicket::with(['user', 'responses'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('HelpTickets/Index', [
            'tickets' => $tickets
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'category' => 'required|string|max:100',
        ]);

        $ticket = HelpTicket::create([
            'user_id' => auth()->id(),
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'category' => $validated['category'],
            'status' => 'open',
        ]);

        return redirect()->route('help-tickets.index')
            ->with('success', 'Help ticket created successfully.');
    }

    public function show(HelpTicket $helpTicket)
    {
        $helpTicket->load(['user', 'responses.user']);

        return Inertia::render('HelpTickets/Show', [
            'ticket' => $helpTicket
        ]);
    }

    public function storeResponse(Request $request, HelpTicket $helpTicket)
    {
        $validated = $request->validate([
            'response' => 'required|string',
        ]);

        HelpTicketResponse::create([
            'help_ticket_id' => $helpTicket->id,
            'user_id' => auth()->id(),
            'response' => $validated['response'],
        ]);

        return redirect()->route('help-tickets.show', $helpTicket)
            ->with('success', 'Response added successfully.');
    }

    public function updateStatus(Request $request, HelpTicket $helpTicket)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        $helpTicket->update([
            'status' => $validated['status'],
        ]);

        return redirect()->route('help-tickets.show', $helpTicket)
            ->with('success', 'Ticket status updated successfully.');
    }
}