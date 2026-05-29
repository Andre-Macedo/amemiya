<?php

namespace Modules\System\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketApiController extends Controller
{
    public function index(): JsonResponse
    {
        $tickets = SupportTicket::with('user')
            ->latest()
            ->get();

        return response()->json($tickets);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'category' => 'nullable|string',
        ]);

        $ticket = SupportTicket::create([
            'user_id' => auth()->id(),
            'subject' => $data['subject'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'category' => $data['category'],
            'status' => 'open',
        ]);

        // Cria a primeira mensagem
        $ticket->messages()->create([
            'user_id' => auth()->id(),
            'message' => $data['description'],
            'is_internal' => false,
        ]);

        return response()->json($ticket, 201);
    }

    public function show(SupportTicket $ticket): JsonResponse
    {
        return response()->json($ticket->load(['user', 'messages.user']));
    }

    public function addMessage(Request $request, SupportTicket $ticket): JsonResponse
    {
        $data = $request->validate([
            'message' => 'required|string',
        ]);

        $message = $ticket->messages()->create([
            'user_id' => auth()->id(),
            'message' => $data['message'],
            'is_internal' => false,
        ]);

        // Se o cliente respondeu, o status volta para 'open' ou 'in_progress'
        if ($ticket->status === 'resolved') {
            $ticket->update(['status' => 'in_progress']);
        }

        return response()->json($message, 201);
    }
}
