<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $tab = $request->get('tab', 'inbox');

        $query = Message::with(['sender', 'recipient', 'relatedJob', 'relatedApplication']);

        switch ($tab) {
            case 'sent':
                $query->sent($user->id);
                break;
            case 'archived':
                $query->forUser($user->id)->archived();
                break;
            default:
                $query->inbox($user->id)->active();
                break;
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $messages = $query->orderByDesc('created_at')->paginate(15);

        return Inertia::render('Messages/Index', [
            'messages' => $messages,
            'tab' => $tab,
            'filters' => $request->only(['type', 'search']),
            'unreadCount' => Message::inbox($user->id)->unread()->count(),
        ]);
    }

    public function show(Message $message)
    {
        $user = Auth::user();

        // Check if user can view this message
        if ($message->sender_id !== $user->id && $message->recipient_id !== $user->id) {
            abort(403);
        }

        $message->load(['sender', 'recipient', 'relatedJob.employer', 'relatedApplication.job']);

        // Mark as read if user is the recipient
        if ($message->recipient_id === $user->id && ! $message->read_at) {
            $message->markAsRead();
        }

        return Inertia::render('Messages/Show', [
            'message' => $message,
        ]);
    }

    public function create(Request $request)
    {
        $recipientId = $request->get('recipient_id');
        $jobId = $request->get('job_id');
        $applicationId = $request->get('application_id');

        $recipient = null;
        if ($recipientId) {
            $recipient = User::find($recipientId);
        }

        return Inertia::render('Messages/Create', [
            'recipient' => $recipient,
            'jobId' => $jobId,
            'applicationId' => $applicationId,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'in:direct,application_related,system',
            'related_job_id' => 'nullable|exists:jobs,id',
            'related_application_id' => 'nullable|exists:job_applications,id',
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'recipient_id' => $request->recipient_id,
            'subject' => $request->subject,
            'content' => $request->content,
            'type' => $request->type ?? 'direct',
            'related_job_id' => $request->related_job_id,
            'related_application_id' => $request->related_application_id,
        ]);

        return redirect()->route('messages.show', $message)
            ->with('success', 'Message sent successfully!');
    }

    public function reply(Message $originalMessage)
    {
        $user = Auth::user();

        // Check if user can reply to this message
        if ($originalMessage->sender_id !== $user->id && $originalMessage->recipient_id !== $user->id) {
            abort(403);
        }

        $originalMessage->load(['sender', 'recipient']);

        return Inertia::render('Messages/Reply', [
            'originalMessage' => $originalMessage,
        ]);
    }

    public function sendReply(Request $request, Message $originalMessage)
    {
        $user = Auth::user();

        // Check if user can reply to this message
        if ($originalMessage->sender_id !== $user->id && $originalMessage->recipient_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'content' => 'required|string',
        ]);

        // Determine recipient (the other person in the conversation)
        $recipientId = $originalMessage->sender_id === $user->id
            ? $originalMessage->recipient_id
            : $originalMessage->sender_id;

        $reply = Message::create([
            'sender_id' => $user->id,
            'recipient_id' => $recipientId,
            'subject' => 'Re: '.$originalMessage->subject,
            'content' => $request->content,
            'type' => $originalMessage->type,
            'related_job_id' => $originalMessage->related_job_id,
            'related_application_id' => $originalMessage->related_application_id,
        ]);

        // Mark original message as replied
        $originalMessage->markAsReplied();

        return redirect()->route('messages.show', $reply)
            ->with('success', 'Reply sent successfully!');
    }

    public function archive(Message $message)
    {
        $user = Auth::user();

        // Check if user can archive this message
        if ($message->sender_id !== $user->id && $message->recipient_id !== $user->id) {
            abort(403);
        }

        $message->archive();

        return back()->with('success', 'Message archived successfully!');
    }

    public function unarchive(Message $message)
    {
        $user = Auth::user();

        // Check if user can unarchive this message
        if ($message->sender_id !== $user->id && $message->recipient_id !== $user->id) {
            abort(403);
        }

        $message->unarchive();

        return back()->with('success', 'Message unarchived successfully!');
    }

    public function destroy(Message $message)
    {
        $user = Auth::user();

        // Only sender can delete the message
        if ($message->sender_id !== $user->id) {
            abort(403);
        }

        $message->delete();

        return redirect()->route('messages.index')
            ->with('success', 'Message deleted successfully!');
    }

    public function markAsRead(Message $message)
    {
        $user = Auth::user();

        if ($message->recipient_id === $user->id) {
            $message->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    public function getUnreadCount()
    {
        $user = Auth::user();
        $count = Message::inbox($user->id)->unread()->count();

        return response()->json(['count' => $count]);
    }
}
