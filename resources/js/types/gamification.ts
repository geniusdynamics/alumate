export interface GamificationEvent {
    id: string;
    userId: string;
    gamificationType: string;
    pointsEarned?: number;
    badgeEarned?: string;
    occurredAt: string;
    properties?: Record<string, any>;
}

export interface UserPoints {
    totalPoints: number;
    totalEvents: number;
    lastEarnedAt?: string;
}

export interface UserBadge {
    badge: string;
    earnedAt: string;
    details?: Record<string, any>;
}

export interface LeaderboardEntry {
    rank: number;
    userId: string;
    totalPoints: number;
    totalEvents: number;
    lastActivity?: string;
}

export interface GamificationMetrics {
    totalEvents: number;
    uniqueUsers: number;
    totalPointsAwarded: number;
    totalBadgesEarned: number;
    avgPointsPerEvent: number;
    dateRange?: string[];
}

export interface ActivityTimelineEntry {
    period: string;
    totalEvents: number;
    uniqueUsers: number;
    pointsAwarded: number;
}

export interface GamificationState {
    metrics: GamificationMetrics | null;
    leaderboard: LeaderboardEntry[];
    userPoints: UserPoints | null;
    userBadges: UserBadge[];
    activityTimeline: ActivityTimelineEntry[];
    loading: boolean;
    error: string | null;
}

export interface GamificationStore {
    // State
    metrics: GamificationMetrics | null;
    leaderboard: LeaderboardEntry[];
    userPoints: UserPoints | null;
    userBadges: UserBadge[];
    activityTimeline: ActivityTimelineEntry[];
    loading: boolean;
    error: string | null;

    // Actions
    fetchMetrics: (dateRange?: string[]) => Promise<void>;
    fetchLeaderboard: (limit?: number) => Promise<void>;
    fetchUserPoints: (userId: string) => Promise<void>;
    fetchUserBadges: (userId: string) => Promise<void>;
    fetchActivityTimeline: (period?: string, days?: number) => Promise<void>;
    trackEvent: (event: Partial<GamificationEvent>) => Promise<boolean>;
    clearError: () => void;
}