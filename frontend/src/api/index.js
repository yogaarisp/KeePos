import axios from 'axios';

export const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
export const baseUrl = (apiUrl.startsWith('http') ? apiUrl.replace(/\/api\/?$/, '') : '');
console.log('API URL:', apiUrl);
console.log('Base URL (Asset Root):', baseUrl || '(Relative to Domain Root)');


const api = axios.create({
    baseURL: apiUrl,
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    }
});

api.interceptors.request.use(config => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    
    // Add tenant slug header if user is logged in
    const user = JSON.parse(localStorage.getItem('user') || 'null');
    if (user && user.tenant) {
        config.headers['X-Tenant-Slug'] = user.tenant.slug;
    }
    
    return config;
});

// Response interceptor to handle 401 errors
api.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            // Token expired or invalid
            console.warn('⚠️ 401 Unauthorized:', error.config?.url);
            
            // Clear storage and redirect to login if not already there
            if (!window.location.pathname.includes('/login')) {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user');
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);

export default api;
