<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your {{ ucfirst($frequency) }} Digest</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
            font-size: 28px;
        }
        .header p {
            color: #6b7280;
            margin: 10px 0 0 0;
            font-size: 16px;
        }
        .summary {
            background-color: #f3f4f6;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        .summary h2 {
            margin: 0 0 10px 0;
            color: #1f2937;
            font-size: 24px;
        }
        .summary p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }
        .notification-group {
            margin-bottom: 30px;
        }
        .notification-group h3 {
            color: #1f2937;
            font-size: 18px;
            margin: 0 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        .notification-item {
            display: flex;
            align-items: flex-start;
            padding: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            margin-bottom: 10px;
            background-color: #fafafa;
        }
        .notification-item:last-child {
            margin-bottom: 0;
        }
        .notification-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .notification-content {
            flex: 1;
        }
        .notification-content p {
            margin: 0 0 5px 0;
            font-size: 14px;
            line-height: 1.4;
        }
        .notification-time {
            color: #9ca3af;
            font-size: 12px;
        }
        .notification-icon {
            width: 20px;
            height: 20px;
            margin-left: 10px;
            flex-shrink: 0;
        }
        .cta-section {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
        }
        .cta-button {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            margin: 0 10px 10px 0;
        }
        .cta-button:hover {
            background-color: #1d4ed8;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 12px;
        }
        .footer a {
            color: #2563eb;
            text-decoration: none;
        }
        .unsubscribe {
            margin-top: 20px;
            font-size: 11px;
            color: #9ca3af;
        }
        .unsubscribe a {
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>{{ config('app.name', 'Alumni Platform') }}</h1>
            <p>Your {{ $frequency }} digest</p>
        </div>

        <!-- Summary -->
        <div class="summary">
            <h2>{{ $totalCount }}</h2>
            <p>{{ $totalCount === 1 ? 'notification' : 'notifications' }} from {{ $period }}</p>
        </div>

        <!-- Notifications by Type -->
        @foreach($groupedNotifications as $type => $typeNotifications)
            <div class="notification-group">
                <h3>
                    @switch($type)
                        @case('post_reaction')
                            👍 Post Reactions ({{ $typeNotifications->count() }})
                            @break
                        @case('post_comment')
                            💬 Comments ({{ $typeNotifications->count() }})
                            @break
                        @case('post_mention')
                            📢 Mentions ({{ $typeNotifications->count() }})
                            @break
                        @case('connection_request')
                            🤝 Connection Requests ({{ $typeNotifications->count() }})
                            @break
                        @case('connection_accepted')
                            ✅ Connections Accepted ({{ $typeNotifications->count() }})
                            @break
                        @default
                            📋 Other Notifications ({{ $typeNotifications->count() }})
                    @endswitch
                </h3>

                @foreach($typeNotifications->take(5) as $notification)
                    <div class="notification-item">
                        <img 
                            src="{{ $notification->data['actor_avatar'] ?? asset('images/default-avatar.png') }}" 
                            alt="Avatar" 
                            class="notification-avatar"
                        >
                        <div class="notification-content">
                            <p>
                                @switch($type)
                                    @case('post_reaction')
                                        <strong>{{ $notification->data['reactor_name'] ?? 'Someone' }}</strong>
                                        {{ $notification->data['reaction_type'] === 'like' ? 'liked' : ($notification->data['reaction_type'] ?? 'reacted to') }}
                                        your post
                                        @break
                                    @case('post_comment')
                                        <strong>{{ $notification->data['commenter_name'] ?? 'Someone' }}</strong>
                                        commented on your post
                                        @break
                                    @case('post_mention')
                                        <strong>{{ $notification->data['mentioner_name'] ?? 'Someone' }}</strong>
                                        mentioned you in a comment
                                        @break
                                    @case('connection_request')
                                        <strong>{{ $notification->data['requester_name'] ?? 'Someone' }}</strong>
                                        wants to connect with you
                                        @break
                                    @case('connection_accepted')
                                        <strong>{{ $notification->data['accepter_name'] ?? 'Someone' }}</strong>
                                        accepted your connection request
                                        @break
                                    @default
                                        You have a new notification
                                @endswitch
                            </p>
                            <div class="notification-time">
                                {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                @endforeach

                @if($typeNotifications->count() > 5)
                    <p style="text-align: center; color: #6b7280; font-size: 14px; margin-top: 15px;">
                        And {{ $typeNotifications->count() - 5 }} more...
                    </p>
                @endif
            </div>
        @endforeach

        <!-- Call to Action -->
        <div class="cta-section">
            <a href="{{ url('/notifications') }}" class="cta-button">
                View All Notifications
            </a>
            <a href="{{ url('/timeline') }}" class="cta-button">
                Visit Timeline
            </a>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>
                You're receiving this {{ $frequency }} digest because you're part of the 
                <strong>{{ config('app.name', 'Alumni Platform') }}</strong> community.
            </p>
            <p>
                <a href="{{ url('/settings/notifications') }}">Update your notification preferences</a>
            </p>
            
            <div class="unsubscribe">
                <p>
                    Don't want these emails? 
                    <a href="{{ url('/settings/notifications') }}">Change your email preferences</a> 
                    or <a href="{{ url('/unsubscribe') }}">unsubscribe</a>.
                </p>
            </div>
        </div>
    </div>
</body>
</html>