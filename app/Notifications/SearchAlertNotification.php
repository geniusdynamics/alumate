<?php

namespace App\Notifications;

use App\Models\SavedSearch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SearchAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected SavedSearch $savedSearch;

    protected int $newResultsCount;

    protected array $sampleResults;

    /**
     * Create a new notification instance.
     */
    public function __construct(SavedSearch $savedSearch, int $newResultsCount, array $sampleResults = [])
    {
        $this->savedSearch = $savedSearch;
        $this->newResultsCount = $newResultsCount;
        $this->sampleResults = $sampleResults;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("New alumni found for your saved search: {$this->savedSearch->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("We found {$this->newResultsCount} new alumni matching your saved search \"{$this->savedSearch->name}\".");

        if (! empty($this->savedSearch->query)) {
            $message->line("Search query: \"{$this->savedSearch->query}\"");
        }

        if ($this->savedSearch->filter_description) {
            $message->line("Filters: {$this->savedSearch->filter_description}");
        }

        if (! empty($this->sampleResults)) {
            $message->line('Here are some of the new results:');

            foreach (array_slice($this->sampleResults, 0, 3) as $result) {
                $name = $result['name'] ?? 'Unknown';
                $company = $result['company'] ?? '';
                $title = $result['title'] ?? '';

                $resultLine = "• $name";
                if ($title && $company) {
                    $resultLine .= " - $title at $company";
                } elseif ($title) {
                    $resultLine .= " - $title";
                } elseif ($company) {
                    $resultLine .= " - $company";
                }

                $message->line($resultLine);
            }
        }

        $message->action('View All Results', route('search.results', [
            'query' => $this->savedSearch->query,
            'filters' => $this->savedSearch->filters,
        ]));

        $message->line('You can manage your search alerts in your account settings.');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'search_alert',
            'saved_search_id' => $this->savedSearch->id,
            'saved_search_name' => $this->savedSearch->name,
            'new_results_count' => $this->newResultsCount,
            'sample_results' => $this->sampleResults,
            'message' => "Found {$this->newResultsCount} new alumni matching your saved search \"{$this->savedSearch->name}\"",
        ];
    }
}
