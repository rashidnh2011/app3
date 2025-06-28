import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { User } from '../types';
import { apiRequest } from '../config/api';

interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  token: string | null;
  login: (email: string, password: string) => Promise<void>;
  loginWithSocial: (provider: 'google' | 'facebook' | 'apple') => Promise<void>;
  register: (userData: RegisterData) => Promise<void>;
  logout: () => void;
  updateProfile: (data: Partial<User>) => Promise<void>;
  resetPassword: (email: string) => Promise<void>;
}

interface RegisterData {
  email: string;
  password: string;
  firstName: string;
  lastName: string;
  phone?: string;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      isAuthenticated: false,
      isLoading: false,
      token: null,

      login: async (email: string, password: string) => {
        set({ isLoading: true });
        try {
          const data = await apiRequest('/auth/login.php', {
            method: 'POST',
            body: JSON.stringify({ email, password }),
          });

          if (data.success) {
            set({ 
              user: data.user, 
              isAuthenticated: true, 
              token: data.token,
              isLoading: false 
            });
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      loginWithSocial: async (provider: 'google' | 'facebook' | 'apple') => {
        set({ isLoading: true });
        try {
          if (provider === 'google') {
            // Simulate Google OAuth response for demo
            const mockGoogleUser = {
              googleId: 'google_123456789',
              email: 'user@gmail.com',
              firstName: 'John',
              lastName: 'Doe',
              avatar: 'https://lh3.googleusercontent.com/a/default-user'
            };

            const data = await apiRequest('/auth/google.php', {
              method: 'POST',
              body: JSON.stringify(mockGoogleUser),
            });

            if (data.success) {
              set({ 
                user: data.user, 
                isAuthenticated: true, 
                token: data.token,
                isLoading: false 
              });
            } else {
              throw new Error(data.message);
            }
          }
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      register: async (userData: RegisterData) => {
        set({ isLoading: true });
        try {
          const data = await apiRequest('/auth/register.php', {
            method: 'POST',
            body: JSON.stringify(userData),
          });

          if (data.success) {
            set({ 
              user: data.user, 
              isAuthenticated: true, 
              token: data.token,
              isLoading: false 
            });
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      logout: () => {
        set({ user: null, isAuthenticated: false, token: null });
      },

      updateProfile: async (data: Partial<User>) => {
        const { user, token } = get();
        if (!user || !token) return;

        set({ isLoading: true });
        try {
          // API call to update profile would go here
          const updatedUser = { ...user, ...data };
          set({ user: updatedUser, isLoading: false });
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      resetPassword: async (email: string) => {
        set({ isLoading: true });
        try {
          await apiRequest('/auth/reset-password.php', {
            method: 'POST',
            body: JSON.stringify({ email }),
          });
          set({ isLoading: false });
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      }
    }),
    {
      name: 'auth-storage',
      partialize: (state) => ({ 
        user: state.user, 
        isAuthenticated: state.isAuthenticated,
        token: state.token
      })
    }
  )
);