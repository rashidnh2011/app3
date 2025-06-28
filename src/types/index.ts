export interface User {
  id: string;
  email: string;
  firstName: string;
  lastName: string;
  avatar?: string;
  phone?: string;
  addresses: Address[];
  preferences: UserPreferences;
  loyaltyPoints: number;
  membershipTier: 'bronze' | 'silver' | 'gold' | 'platinum';
  createdAt: string;
  lastLogin: string;
}

export interface AdminUser {
  id: string;
  email: string;
  firstName: string;
  lastName: string;
  avatar?: string;
  role: 'admin' | 'super_admin' | 'moderator';
  permissions: string[];
  createdAt: string;
  lastLogin: string;
}

export interface Address {
  id: string;
  type: 'billing' | 'shipping';
  firstName: string;
  lastName: string;
  company?: string;
  street1: string;
  street2?: string;
  city: string;
  state: string;
  zipCode: string;
  country: string;
  phone?: string;
  isDefault: boolean;
}

export interface UserPreferences {
  notifications: {
    email: boolean;
    sms: boolean;
    push: boolean;
  };
  marketing: boolean;
  currency: string;
  language: string;
}

export interface Product {
  id: string;
  name: string;
  description: string;
  shortDescription: string;
  sku: string;
  brand: string;
  category: Category;
  subcategory?: string;
  price: number;
  compareAtPrice?: number;
  images: ProductImage[];
  videos?: ProductVideo[];
  variants: ProductVariant[];
  specifications: ProductSpecification[];
  compatibility: VehicleCompatibility[];
  inventory: InventoryInfo;
  seo: SEOInfo;
  ratings: ProductRatings;
  tags: string[];
  featured: boolean;
  status: 'active' | 'inactive' | 'discontinued';
  createdAt: string;
  updatedAt: string;
}

export interface ProductImage {
  id: string;
  url: string;
  alt: string;
  position: number;
  type: 'main' | 'gallery' | 'variant';
}

export interface ProductVideo {
  id: string;
  url: string;
  thumbnail: string;
  title: string;
  duration: number;
  type: 'demo' | 'installation' | 'review';
}

export interface ProductVariant {
  id: string;
  name: string;
  sku: string;
  price: number;
  compareAtPrice?: number;
  attributes: VariantAttribute[];
  inventory: InventoryInfo;
  images: ProductImage[];
}

export interface VariantAttribute {
  name: string;
  value: string;
  type: 'color' | 'size' | 'material' | 'finish';
}

export interface ProductSpecification {
  name: string;
  value: string;
  group: string;
}

export interface VehicleCompatibility {
  make: string;
  model: string;
  year: number;
  trim?: string;
  engine?: string;
}

export interface InventoryInfo {
  quantity: number;
  lowStockThreshold: number;
  status: 'in_stock' | 'low_stock' | 'out_of_stock' | 'backorder';
  backorderDate?: string;
  trackQuantity: boolean;
}

export interface SEOInfo {
  title: string;
  description: string;
  keywords: string[];
  slug: string;
}

export interface ProductRatings {
  average: number;
  count: number;
  distribution: {
    1: number;
    2: number;
    3: number;
    4: number;
    5: number;
  };
}

export interface Category {
  id: string;
  name: string;
  slug: string;
  description: string;
  image?: string;
  parentId?: string;
  children?: Category[];
  productCount: number;
  featured: boolean;
  seo: SEOInfo;
}

export interface CartItem {
  id: string;
  productId: string;
  variantId?: string;
  quantity: number;
  price: number;
  product: Product;
  variant?: ProductVariant;
  addedAt: string;
}

export interface Cart {
  id: string;
  items: CartItem[];
  subtotal: number;
  tax: number;
  shipping: number;
  discount: number;
  total: number;
  currency: string;
  couponCode?: string;
  updatedAt: string;
}

export interface Order {
  id: string;
  orderNumber: string;
  userId: string;
  status: OrderStatus;
  items: OrderItem[];
  billing: Address;
  shipping: Address;
  payment: PaymentInfo;
  fulfillment: FulfillmentInfo;
  totals: OrderTotals;
  notes?: string;
  createdAt: string;
  updatedAt: string;
}

export interface OrderItem {
  id: string;
  productId: string;
  variantId?: string;
  quantity: number;
  price: number;
  total: number;
  product: Product;
  variant?: ProductVariant;
}

export interface OrderStatus {
  current: 'pending' | 'confirmed' | 'processing' | 'shipped' | 'delivered' | 'cancelled' | 'refunded';
  history: OrderStatusHistory[];
}

export interface OrderStatusHistory {
  status: string;
  timestamp: string;
  note?: string;
}

export interface PaymentInfo {
  method: 'card' | 'paypal' | 'apple_pay' | 'google_pay' | 'bank_transfer';
  status: 'pending' | 'authorized' | 'captured' | 'failed' | 'refunded';
  transactionId: string;
  gateway: string;
  last4?: string;
  brand?: string;
}

export interface FulfillmentInfo {
  method: 'standard' | 'express' | 'overnight' | 'pickup';
  carrier?: string;
  trackingNumber?: string;
  estimatedDelivery?: string;
  actualDelivery?: string;
}

export interface OrderTotals {
  subtotal: number;
  tax: number;
  shipping: number;
  discount: number;
  total: number;
  currency: string;
}

export interface Review {
  id: string;
  productId: string;
  userId: string;
  rating: number;
  title: string;
  content: string;
  verified: boolean;
  helpful: number;
  images?: string[];
  createdAt: string;
  user: {
    firstName: string;
    lastName: string;
    avatar?: string;
  };
}

export interface SearchFilters {
  query?: string;
  category?: string;
  brand?: string[];
  priceRange?: [number, number];
  rating?: number;
  inStock?: boolean;
  compatibility?: {
    make?: string;
    model?: string;
    year?: number;
  };
  sortBy?: 'relevance' | 'price_asc' | 'price_desc' | 'rating' | 'newest';
  page?: number;
  limit?: number;
}

export interface SearchResults {
  products: Product[];
  total: number;
  page: number;
  limit: number;
  filters: {
    categories: Array<{ id: string; name: string; count: number }>;
    brands: Array<{ name: string; count: number }>;
    priceRange: [number, number];
  };
}

export interface Coupon {
  id: string;
  code: string;
  type: 'percentage' | 'fixed' | 'free_shipping';
  value: number;
  minimumAmount?: number;
  maximumDiscount?: number;
  usageLimit?: number;
  usageCount: number;
  validFrom: string;
  validTo: string;
  active: boolean;
}

export interface WishlistItem {
  id: string;
  productId: string;
  variantId?: string;
  addedAt: string;
  product: Product;
  variant?: ProductVariant;
}