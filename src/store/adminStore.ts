import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { Product, Category, AdminUser } from '../types';
import { apiRequest } from '../config/api';

interface AdminState {
  adminUser: AdminUser | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  token: string | null;
  products: Product[];
  categories: Category[];
  
  // Auth methods
  login: (email: string, password: string) => Promise<void>;
  logout: () => void;
  
  // Product management
  addProduct: (product: Omit<Product, 'id' | 'createdAt' | 'updatedAt'>) => Promise<void>;
  updateProduct: (id: string, product: Partial<Product>) => Promise<void>;
  deleteProduct: (id: string) => Promise<void>;
  fetchProducts: () => Promise<void>;
  
  // Category management
  addCategory: (category: Omit<Category, 'id'>) => Promise<void>;
  updateCategory: (id: string, category: Partial<Category>) => Promise<void>;
  deleteCategory: (id: string) => Promise<void>;
  fetchCategories: () => Promise<void>;
}

export const useAdminStore = create<AdminState>()(
  persist(
    (set, get) => ({
      adminUser: null,
      isAuthenticated: false,
      isLoading: false,
      token: null,
      products: [],
      categories: [],

      login: async (email: string, password: string) => {
        set({ isLoading: true });
        try {
          const data = await apiRequest('/admin/login.php', {
            method: 'POST',
            body: JSON.stringify({ email, password }),
          });

          if (data.success) {
            set({ 
              adminUser: data.admin, 
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
        set({ adminUser: null, isAuthenticated: false, token: null });
      },

      addProduct: async (productData) => {
        const { token } = get();
        set({ isLoading: true });
        try {
          const data = await apiRequest('/products/index.php', {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(productData),
          });

          if (data.success) {
            await get().fetchProducts();
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      updateProduct: async (id: string, productData) => {
        const { token } = get();
        set({ isLoading: true });
        try {
          const data = await apiRequest('/products/index.php', {
            method: 'PUT',
            headers: {
              'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ id, ...productData }),
          });

          if (data.success) {
            await get().fetchProducts();
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      deleteProduct: async (id: string) => {
        const { token } = get();
        set({ isLoading: true });
        try {
          const data = await apiRequest(`/products/index.php?id=${id}`, {
            method: 'DELETE',
            headers: {
              'Authorization': `Bearer ${token}`
            },
          });

          if (data.success) {
            await get().fetchProducts();
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      fetchProducts: async () => {
        set({ isLoading: true });
        try {
          const data = await apiRequest('/products/index.php');

          if (data.success) {
            set({ products: data.data, isLoading: false });
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      addCategory: async (categoryData) => {
        const { token } = get();
        set({ isLoading: true });
        try {
          const data = await apiRequest('/categories/index.php', {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(categoryData),
          });

          if (data.success) {
            await get().fetchCategories();
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      updateCategory: async (id: string, categoryData) => {
        const { token } = get();
        set({ isLoading: true });
        try {
          const data = await apiRequest('/categories/index.php', {
            method: 'PUT',
            headers: {
              'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ id, ...categoryData }),
          });

          if (data.success) {
            await get().fetchCategories();
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      deleteCategory: async (id: string) => {
        const { token } = get();
        set({ isLoading: true });
        try {
          const data = await apiRequest(`/categories/index.php?id=${id}`, {
            method: 'DELETE',
            headers: {
              'Authorization': `Bearer ${token}`
            },
          });

          if (data.success) {
            await get().fetchCategories();
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      fetchCategories: async () => {
        set({ isLoading: true });
        try {
          const data = await apiRequest('/categories/index.php');

          if (data.success) {
            set({ categories: data.data, isLoading: false });
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      }
    }),
    {
      name: 'admin-storage',
      partialize: (state) => ({ 
        adminUser: state.adminUser, 
        isAuthenticated: state.isAuthenticated,
        token: state.token
      })
    }
  )
);