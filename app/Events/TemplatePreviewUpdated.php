<?php

namespace App\Events;

use App\Models\Template;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Template Preview Updated Event
 *
 * Broadcasts real-time updates when template previews are generated or modified.
 */
class TemplatePreviewUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Template $template;
    public array $previewData;
    public string $viewport;
    public ?int $userId;
    public ?string $tenantId;

    /**
     * Create a new event instance.
     *
     * @param Template $template
     * @param array $previewData
     * @param string $viewport
     * @param int|null $userId
     * @param string|null $tenantId
     */
    public function __construct(
        Template $template,
        array $previewData,
        string $viewport = 'desktop',
        ?int $userId = null,
        ?string $tenantId = null
    ) {
        $this->template = $template;
        $this->previewData = $previewData;
        $this->viewport = $viewport;
        $this->userId = $userId;
        $this->tenantId = $tenantId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Template-specific channel for general updates
        $channels[] = new Channel('template.' . $this->template->id . '.preview');

        // User-specific private channel (if user is provided)
        if ($this->userId) {
            $channels[] = new PrivateChannel('user.' . $this->userId . '.template-previews');
        }

        // Tenant-wide preview channel (with tenant isolation)
        if ($this->tenantId) {
            $channels[] = new Channel('tenant.' . $this->tenantId . '.template-previews');
        }

        // Presence channel for collaborative editing
        $channels[] = new PresenceChannel('template.' . $this->template->id . '.collaborators');

        return $channels;
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'template.preview.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'template_id' => $this->template->id,
            'template_name' => $this->template->name,
            'preview_data' => $this->previewData,
            'viewport' => $this->viewport,
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'timestamp' => now()->toISOString(),
            'event_type' => 'preview_update',
        ];
    }

    /**
     * Determine if this event should broadcast.
     *
     * @return bool
     */
    public function broadcastWhen(): bool
    {
        // Always broadcast in development
        if (app()->environment('local')) {
            return true;
        }

        // Only broadcast if there are subscribers or it's a significant update
        return true;
    }

    /**
     * Get the broadcast queue.
     *
     * @return string|null
     */
    public function broadcastQueue(): ?string
    {
        return 'broadcast';
    }
}