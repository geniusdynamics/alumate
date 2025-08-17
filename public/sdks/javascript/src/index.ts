import axios, { AxiosInstance, AxiosRequestConfig } from 'axios';

export interface AlumniPlatformConfig {
  baseURL: string;
  token: string;
  timeout?: number;
}

export interface PaginatedResponse<T> {
  success: boolean;
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  bio?: string;
  avatar_url?: string;
  location?: string;
  created_at: string;
  updated_at: string;
}

export interface Post {
  id: number;
  content: string;
  user: User;
  media_urls?: string[];
  visibility: 'public' | 'circles' | 'groups';
  likes_count: number;
  comments_count: number;
  created_at: string;
}

export interface Job {
  id: number;
  title: string;
  company: string;
  location: string;
  description: string;
  requirements: string[];
  salary_range?: string;
  posted_at: string;
}

export interface Event {
  id: number;
  title: string;
  description: string;
  start_date: string;
  end_date: string;
  location: string;
  type: string;
  registration_count: number;
}

export interface Webhook {
  id: number;
  url: string;
  events: string[];
  status: 'active' | 'inactive';
  created_at: string;
}

export class AlumniPlatformAPI {
  private client: AxiosInstance;

  constructor(config: AlumniPlatformConfig) {
    this.client = axios.create({
      baseURL: config.baseURL,
      timeout: config.timeout || 30000,
      headers: {
        'Authorization': `Bearer ${config.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    });

    // Add response interceptor for error handling
    this.client.interceptors.response.use(
      (response) => response,
      (error) => {
        if (error.response?.status === 401) {
          throw new Error('Authentication failed. Please check your API token.');
        }
        if (error.response?.status === 429) {
          throw new Error('Rate limit exceeded. Please try again later.');
        }
        throw error;
      }
    );
  }

  // User methods
  async getUser(): Promise<User> {
    const response = await this.client.get<ApiResponse<User>>('/user');
    return response.data.data;
  }

  // Timeline methods
  async getTimeline(page = 1, perPage = 20): Promise<PaginatedResponse<Post>> {
    const response = await this.client.get<PaginatedResponse<Post>>('/timeline', {
      params: { page, per_page: perPage }
    });
    return response.data;
  }

  async refreshTimeline(): Promise<PaginatedResponse<Post>> {
    const response = await this.client.get<PaginatedResponse<Post>>('/timeline/refresh');
    return response.data;
  }

  // Post methods
  async createPost(data: {
    content: string;
    visibility?: 'public' | 'circles' | 'groups';
    media_urls?: string[];
  }): Promise<Post> {
    const response = await this.client.post<ApiResponse<Post>>('/posts', data);
    return response.data.data;
  }

  async likePost(postId: number): Promise<void> {
    await this.client.post(`/posts/${postId}/like`);
  }

  async commentOnPost(postId: number, content: string): Promise<void> {
    await this.client.post(`/posts/${postId}/comment`, { content });
  }

  // Alumni methods
  async searchAlumni(filters: {
    search?: string;
    industry?: string;
    location?: string;
    graduation_year?: number;
    page?: number;
    per_page?: number;
  } = {}): Promise<PaginatedResponse<User>> {
    const response = await this.client.get<PaginatedResponse<User>>('/alumni/search', {
      params: filters
    });
    return response.data;
  }

  async connectWithAlumni(userId: number, message?: string): Promise<void> {
    await this.client.post(`/alumni/${userId}/connect`, { message });
  }

  // Job methods
  async getJobRecommendations(filters: {
    industry?: string;
    location?: string;
    experience_level?: string;
  } = {}): Promise<Job[]> {
    const response = await this.client.get<ApiResponse<Job[]>>('/jobs/recommendations', {
      params: filters
    });
    return response.data.data;
  }

  async applyForJob(jobId: number, applicationData: {
    cover_letter?: string;
    resume_url?: string;
  }): Promise<void> {
    await this.client.post(`/jobs/${jobId}/apply`, applicationData);
  }

  // Event methods
  async getEvents(filters: {
    type?: string;
    upcoming?: boolean;
    page?: number;
    per_page?: number;
  } = {}): Promise<PaginatedResponse<Event>> {
    const response = await this.client.get<PaginatedResponse<Event>>('/events', {
      params: filters
    });
    return response.data;
  }

  async registerForEvent(eventId: number): Promise<void> {
    await this.client.post(`/events/${eventId}/register`);
  }

  // Mentorship methods
  async requestMentorship(data: {
    mentor_id: number;
    message: string;
    goals?: string[];
  }): Promise<void> {
    await this.client.post('/mentorship/request', data);
  }

  async getMentorships(): Promise<any[]> {
    const response = await this.client.get<ApiResponse<any[]>>('/mentorships');
    return response.data.data;
  }

  // Webhook methods
  async createWebhook(data: {
    url: string;
    events: string[];
    name?: string;
    secret?: string;
  }): Promise<Webhook> {
    const response = await this.client.post<ApiResponse<Webhook>>('/developer/webhooks', data);
    return response.data.data;
  }

  async getWebhooks(): Promise<Webhook[]> {
    const response = await this.client.get<ApiResponse<Webhook[]>>('/developer/webhooks');
    return response.data.data;
  }

  async deleteWebhook(webhookId: number): Promise<void> {
    await this.client.delete(`/developer/webhooks/${webhookId}`);
  }

  // Notification methods
  async getNotifications(page = 1): Promise<PaginatedResponse<any>> {
    const response = await this.client.get<PaginatedResponse<any>>('/notifications', {
      params: { page }
    });
    return response.data;
  }

  async markNotificationAsRead(notificationId: number): Promise<void> {
    await this.client.post(`/notifications/${notificationId}/read`);
  }

  async markAllNotificationsAsRead(): Promise<void> {
    await this.client.post('/notifications/mark-all-read');
  }

  // Utility methods
  async ping(): Promise<{ status: string; timestamp: string }> {
    const response = await this.client.get('/ping');
    return response.data;
  }
}

export default AlumniPlatformAPI;