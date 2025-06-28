// Production API Configuration for Hostinger
export const API_CONFIG = {
  // Production URL - Updated for your domain
  BASE_URL: import.meta.env.PROD 
    ? 'https://autriq.com/api'  // Your actual domain
    : 'http://localhost/autriq/api',
  
  TIMEOUT: 30000, // Increased for production
  
  HEADERS: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
};

// Enhanced API Helper with better error handling for production
export const apiRequest = async (endpoint: string, options: RequestInit = {}) => {
  const url = `${API_CONFIG.BASE_URL}${endpoint}`;
  
  const config: RequestInit = {
    ...options,
    headers: {
      ...API_CONFIG.HEADERS,
      ...options.headers,
    },
    // Add timeout for production
    signal: AbortSignal.timeout(API_CONFIG.TIMEOUT),
  };

  try {
    console.log(`API Request: ${url}`, config); // Debug log
    
    const response = await fetch(url, config);
    
    console.log(`API Response Status: ${response.status}`, response); // Debug log
    
    if (!response.ok) {
      const errorText = await response.text();
      console.error(`API Error: ${response.status}`, errorText); // Debug log
      throw new Error(`HTTP ${response.status}: ${errorText}`);
    }
    
    const data = await response.json();
    console.log('API Response Data:', data); // Debug log
    return data;
  } catch (error) {
    if (error instanceof Error) {
      if (error.name === 'AbortError') {
        throw new Error('Request timeout - please try again');
      }
      console.error('API Request failed:', error.message);
      throw new Error(error.message);
    }
    throw new Error('Network error - please check your connection');
  }
};

// Environment detection
export const isProduction = import.meta.env.PROD;
export const isDevelopment = import.meta.env.DEV;