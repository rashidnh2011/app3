import { create } from 'zustand';
import { Product, SearchFilters, SearchResults, Category } from '../types';
import { apiRequest } from '../config/api';

interface ProductState {
  products: Product[];
  categories: Category[];
  searchResults: SearchResults | null;
  isLoading: boolean;
  error: string | null;
  fetchProducts: () => Promise<void>;
  fetchCategories: () => Promise<void>;
  searchProducts: (filters: SearchFilters) => Promise<void>;
  getProduct: (id: string) => Product | undefined;
  getFeaturedProducts: () => Product[];
}

export const useProductStore = create<ProductState>((set, get) => ({
  products: [],
  categories: [],
  searchResults: null,
  isLoading: false,
  error: null,

  fetchProducts: async () => {
    set({ isLoading: true, error: null });
    try {
      console.log('Fetching products...');
      const data = await apiRequest('/products/index.php');

      console.log('Products API response:', data);

      if (data.success) {
        set({ products: data.data, isLoading: false });
        console.log('Products loaded successfully:', data.data.length);
      } else {
        throw new Error(data.message || 'Failed to fetch products');
      }
    } catch (error) {
      console.error('Error fetching products:', error);
      set({ error: 'Failed to fetch products', isLoading: false });
    }
  },

  fetchCategories: async () => {
    set({ isLoading: true, error: null });
    try {
      console.log('Fetching categories...');
      const data = await apiRequest('/categories/index.php');

      console.log('Categories API response:', data);

      if (data.success) {
        set({ categories: data.data, isLoading: false });
        console.log('Categories loaded successfully:', data.data.length);
      } else {
        throw new Error(data.message || 'Failed to fetch categories');
      }
    } catch (error) {
      console.error('Error fetching categories:', error);
      set({ error: 'Failed to fetch categories', isLoading: false });
    }
  },

  searchProducts: async (filters: SearchFilters) => {
    set({ isLoading: true, error: null });
    try {
      const queryParams = new URLSearchParams();
      
      if (filters.query) queryParams.append('q', filters.query);
      if (filters.category) queryParams.append('category', filters.category);
      if (filters.priceRange) {
        queryParams.append('min_price', filters.priceRange[0].toString());
        queryParams.append('max_price', filters.priceRange[1].toString());
      }
      if (filters.rating) queryParams.append('rating', filters.rating.toString());
      if (filters.sortBy) queryParams.append('sort', filters.sortBy);
      if (filters.page) queryParams.append('page', filters.page.toString());
      if (filters.limit) queryParams.append('limit', filters.limit.toString());

      const data = await apiRequest(`/products/search.php?${queryParams}`);

      if (data.success) {
        set({ searchResults: data.data, isLoading: false });
      } else {
        throw new Error(data.message || 'Failed to search products');
      }
    } catch (error) {
      console.error('Error searching products:', error);
      set({ error: 'Failed to search products', isLoading: false });
    }
  },

  getProduct: (id: string) => {
    const { products } = get();
    return products.find(product => product.id === id);
  },

  getFeaturedProducts: () => {
    const { products } = get();
    return products.filter(product => product.featured);
  }
}));