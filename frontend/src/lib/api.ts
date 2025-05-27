class ApiClient {
  private baseUrl: string;

  constructor(baseUrl: string = '/api') {
    this.baseUrl = baseUrl;
  }

  private getAuthToken(): string | null {
    return localStorage.getItem('auth_token');
  }

  private getHeaders(): HeadersInit {
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
    };

    const token = this.getAuthToken();
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    return headers;
  }

  private async handleResponse<T>(response: Response): Promise<T> {
    if (response.status === 401) {
      // Token expired or invalid, remove it
      localStorage.removeItem('auth_token');
      window.location.reload(); // Force re-authentication
      throw new Error('Authentication required');
    }

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new Error(errorData.error || errorData.message || `HTTP ${response.status}`);
    }

    return response.json();
  }

  async get<T = any>(endpoint: string): Promise<T> {
    const response = await fetch(`${this.baseUrl}${endpoint}`, {
      method: 'GET',
      headers: this.getHeaders(),
    });

    return this.handleResponse<T>(response);
  }

  async post<T = any>(endpoint: string, data?: any): Promise<T> {
    const response = await fetch(`${this.baseUrl}${endpoint}`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: data ? JSON.stringify(data) : undefined,
    });

    return this.handleResponse<T>(response);
  }

  async put<T = any>(endpoint: string, data?: any): Promise<T> {
    const response = await fetch(`${this.baseUrl}${endpoint}`, {
      method: 'PUT',
      headers: this.getHeaders(),
      body: data ? JSON.stringify(data) : undefined,
    });

    return this.handleResponse<T>(response);
  }

  async delete<T = any>(endpoint: string): Promise<T> {
    const response = await fetch(`${this.baseUrl}${endpoint}`, {
      method: 'DELETE',
      headers: this.getHeaders(),
    });

    return this.handleResponse<T>(response);
  }
}

// Create singleton instance
export const apiClient = new ApiClient();

// Convenience functions for common API calls
export const api = {
  // Authentication
  auth: {
    login: (email: string, password: string) =>
      apiClient.post('/auth/login', { email, password }),
    me: () => apiClient.get('/auth/me'),
    logout: () => apiClient.post('/auth/logout'),
  },

  // Docker containers
  docker: {
    containers: () => apiClient.get('/docker/containers'),
    container: (id: string) => apiClient.get(`/docker/containers/${id}`),
    start: (id: string) => apiClient.post(`/docker/containers/${id}/start`),
    stop: (id: string) => apiClient.post(`/docker/containers/${id}/stop`),
    restart: (id: string) => apiClient.post(`/docker/containers/${id}/restart`),
    logs: (id: string) => apiClient.get(`/docker/containers/${id}/logs`),
  },

  // Infrastructure
  infrastructure: {
    health: () => apiClient.get('/infrastructure/health'),
    metrics: () => apiClient.get('/infrastructure/metrics'),
  },
}; 