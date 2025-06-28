import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { Cart, CartItem, Product, ProductVariant } from '../types';
import { apiRequest } from '../config/api';

interface CartState {
  cart: Cart;
  isLoading: boolean;
  addItem: (product: Product, variant?: ProductVariant, quantity?: number) => Promise<void>;
  removeItem: (itemId: string) => Promise<void>;
  updateQuantity: (itemId: string, quantity: number) => Promise<void>;
  clearCart: () => Promise<void>;
  fetchCart: () => Promise<void>;
  applyCoupon: (code: string) => Promise<void>;
  removeCoupon: () => void;
  calculateTotals: () => void;
}

const initialCart: Cart = {
  id: 'cart-1',
  items: [],
  subtotal: 0,
  tax: 0,
  shipping: 0,
  discount: 0,
  total: 0,
  currency: 'USD',
  updatedAt: new Date().toISOString()
};

export const useCartStore = create<CartState>()(
  persist(
    (set, get) => ({
      cart: initialCart,
      isLoading: false,

      addItem: async (product: Product, variant?: ProductVariant, quantity = 1) => {
        set({ isLoading: true });
        try {
          const authStorage = localStorage.getItem('auth-storage');
          const token = authStorage ? JSON.parse(authStorage).state.token : null;

          if (!token) {
            // Handle guest cart (local storage)
            const { cart } = get();
            const existingItemIndex = cart.items.findIndex(
              item => item.productId === product.id && item.variantId === variant?.id
            );

            let newItems: CartItem[];
            
            if (existingItemIndex >= 0) {
              newItems = cart.items.map((item, index) =>
                index === existingItemIndex
                  ? { ...item, quantity: item.quantity + quantity }
                  : item
              );
            } else {
              const newItem: CartItem = {
                id: `item-${Date.now()}`,
                productId: product.id,
                variantId: variant?.id,
                quantity,
                price: variant?.price || product.price,
                product,
                variant,
                addedAt: new Date().toISOString()
              };
              newItems = [...cart.items, newItem];
            }

            const updatedCart = {
              ...cart,
              items: newItems,
              updatedAt: new Date().toISOString()
            };

            set({ cart: updatedCart, isLoading: false });
            get().calculateTotals();
            return;
          }

          const data = await apiRequest('/cart/index.php', {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
              productId: product.id,
              variantId: variant?.id,
              quantity,
              price: variant?.price || product.price
            }),
          });

          if (data.success) {
            await get().fetchCart();
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      removeItem: async (itemId: string) => {
        set({ isLoading: true });
        try {
          const authStorage = localStorage.getItem('auth-storage');
          const token = authStorage ? JSON.parse(authStorage).state.token : null;

          if (!token) {
            // Handle guest cart
            const { cart } = get();
            const updatedCart = {
              ...cart,
              items: cart.items.filter(item => item.id !== itemId),
              updatedAt: new Date().toISOString()
            };
            set({ cart: updatedCart, isLoading: false });
            get().calculateTotals();
            return;
          }

          const data = await apiRequest(`/cart/index.php?id=${itemId}`, {
            method: 'DELETE',
            headers: {
              'Authorization': `Bearer ${token}`
            },
          });

          if (data.success) {
            await get().fetchCart();
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      updateQuantity: async (itemId: string, quantity: number) => {
        if (quantity <= 0) {
          await get().removeItem(itemId);
          return;
        }

        set({ isLoading: true });
        try {
          const authStorage = localStorage.getItem('auth-storage');
          const token = authStorage ? JSON.parse(authStorage).state.token : null;

          if (!token) {
            // Handle guest cart
            const { cart } = get();
            const updatedCart = {
              ...cart,
              items: cart.items.map(item =>
                item.id === itemId ? { ...item, quantity } : item
              ),
              updatedAt: new Date().toISOString()
            };
            set({ cart: updatedCart, isLoading: false });
            get().calculateTotals();
            return;
          }

          const data = await apiRequest('/cart/index.php', {
            method: 'PUT',
            headers: {
              'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ id: itemId, quantity }),
          });

          if (data.success) {
            await get().fetchCart();
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      clearCart: async () => {
        set({ isLoading: true });
        try {
          const authStorage = localStorage.getItem('auth-storage');
          const token = authStorage ? JSON.parse(authStorage).state.token : null;

          if (!token) {
            set({ cart: { ...initialCart, updatedAt: new Date().toISOString() }, isLoading: false });
            return;
          }

          const data = await apiRequest('/cart/index.php?clear=true', {
            method: 'DELETE',
            headers: {
              'Authorization': `Bearer ${token}`
            },
          });

          if (data.success) {
            set({ cart: { ...initialCart, updatedAt: new Date().toISOString() }, isLoading: false });
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      fetchCart: async () => {
        set({ isLoading: true });
        try {
          const authStorage = localStorage.getItem('auth-storage');
          const token = authStorage ? JSON.parse(authStorage).state.token : null;

          if (!token) {
            set({ isLoading: false });
            return;
          }

          const data = await apiRequest('/cart/index.php', {
            headers: {
              'Authorization': `Bearer ${token}`
            },
          });

          if (data.success) {
            set({ cart: data.data, isLoading: false });
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      applyCoupon: async (code: string) => {
        set({ isLoading: true });
        try {
          // API call to apply coupon
          await new Promise(resolve => setTimeout(resolve, 500));
          
          const { cart } = get();
          let discount = 0;
          
          if (code === 'SAVE10') {
            discount = cart.subtotal * 0.1;
          } else if (code === 'FREESHIP') {
            discount = cart.shipping;
          }

          const updatedCart = {
            ...cart,
            couponCode: code,
            discount,
            updatedAt: new Date().toISOString()
          };

          set({ cart: updatedCart, isLoading: false });
          get().calculateTotals();
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      removeCoupon: () => {
        const { cart } = get();
        const updatedCart = {
          ...cart,
          couponCode: undefined,
          discount: 0,
          updatedAt: new Date().toISOString()
        };

        set({ cart: updatedCart });
        get().calculateTotals();
      },

      calculateTotals: () => {
        const { cart } = get();
        const subtotal = cart.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const tax = subtotal * 0.08; // 8% tax rate
        const shipping = subtotal > 100 ? 0 : 15; // Free shipping over $100
        const total = subtotal + tax + shipping - cart.discount;

        const updatedCart = {
          ...cart,
          subtotal,
          tax,
          shipping,
          total,
          updatedAt: new Date().toISOString()
        };

        set({ cart: updatedCart });
      }
    }),
    {
      name: 'cart-storage',
      partialize: (state) => ({ cart: state.cart })
    }
  )
);