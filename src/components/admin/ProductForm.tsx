import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { X, Upload, Plus, Trash2, Link as LinkIcon, AlertCircle } from 'lucide-react';
import { useAdminStore } from '../../store/adminStore';
import { useProductStore } from '../../store/productStore';
import { Product, ProductImage, ProductVariant, ProductSpecification, VehicleCompatibility } from '../../types';
import Button from '../ui/Button';
import AlibabaImportTool from './AlibabaImportTool';

interface ProductFormProps {
  productId?: string | null;
  onClose: () => void;
}

const ProductForm: React.FC<ProductFormProps> = ({ productId, onClose }) => {
  const { addProduct, updateProduct, isLoading } = useAdminStore();
  const { categories, fetchCategories } = useProductStore();
  
  const [showAlibabaImport, setShowAlibabaImport] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    shortDescription: '',
    sku: '',
    brand: '',
    categoryId: '',
    price: 0,
    compareAtPrice: 0,
    featured: false,
    status: 'active' as const,
    tags: [] as string[],
    inventory: {
      quantity: 0,
      lowStockThreshold: 5,
      status: 'in_stock' as const,
      trackQuantity: true
    },
    seo: {
      title: '',
      description: '',
      keywords: [] as string[],
      slug: ''
    }
  });

  const [images, setImages] = useState<ProductImage[]>([]);
  const [variants, setVariants] = useState<ProductVariant[]>([]);
  const [specifications, setSpecifications] = useState<ProductSpecification[]>([]);
  const [compatibility, setCompatibility] = useState<VehicleCompatibility[]>([]);
  const [errors, setErrors] = useState<Record<string, string>>({});

  // Load categories on component mount
  useEffect(() => {
    console.log('ProductForm mounted, fetching categories...');
    fetchCategories();
  }, [fetchCategories]);

  // Debug categories
  useEffect(() => {
    console.log('Categories in ProductForm:', categories);
  }, [categories]);

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value, type } = e.target;
    
    if (name.includes('.')) {
      const [parent, child] = name.split('.');
      setFormData(prev => ({
        ...prev,
        [parent]: {
          ...prev[parent as keyof typeof prev],
          [child]: type === 'number' ? parseFloat(value) || 0 : value
        }
      }));
    } else {
      setFormData(prev => ({
        ...prev,
        [name]: type === 'number' ? parseFloat(value) || 0 : 
                type === 'checkbox' ? (e.target as HTMLInputElement).checked : value
      }));
    }

    // Clear error when user starts typing
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  const handleAlibabaImport = (importedData: any) => {
    // Populate form with imported data
    setFormData(prev => ({
      ...prev,
      name: importedData.name,
      description: importedData.description,
      shortDescription: importedData.shortDescription,
      sku: importedData.sku,
      brand: importedData.brand,
      price: importedData.price,
      compareAtPrice: importedData.compareAtPrice,
      featured: importedData.featured,
      status: importedData.status,
      tags: importedData.tags,
      inventory: importedData.inventory,
      seo: importedData.seo
    }));

    // Set images
    setImages(importedData.images);
    
    // Set specifications
    setSpecifications(importedData.specifications);
    
    // Close import tool
    setShowAlibabaImport(false);
  };

  const handleImageAdd = () => {
    const newImage: ProductImage = {
      id: `img-${Date.now()}`,
      url: 'https://images.pexels.com/photos/3802510/pexels-photo-3802510.jpeg?auto=compress&cs=tinysrgb&w=800',
      alt: formData.name || 'Product image',
      position: images.length + 1,
      type: images.length === 0 ? 'main' : 'gallery'
    };
    setImages(prev => [...prev, newImage]);
  };

  const handleImageRemove = (imageId: string) => {
    setImages(prev => prev.filter(img => img.id !== imageId));
  };

  const handleSpecificationAdd = () => {
    const newSpec: ProductSpecification = {
      name: '',
      value: '',
      group: 'General'
    };
    setSpecifications(prev => [...prev, newSpec]);
  };

  const handleSpecificationChange = (index: number, field: keyof ProductSpecification, value: string) => {
    setSpecifications(prev => prev.map((spec, i) => 
      i === index ? { ...spec, [field]: value } : spec
    ));
  };

  const handleSpecificationRemove = (index: number) => {
    setSpecifications(prev => prev.filter((_, i) => i !== index));
  };

  const handleCompatibilityAdd = () => {
    const newCompat: VehicleCompatibility = {
      make: '',
      model: '',
      year: new Date().getFullYear()
    };
    setCompatibility(prev => [...prev, newCompat]);
  };

  const handleCompatibilityChange = (index: number, field: keyof VehicleCompatibility, value: string | number) => {
    setCompatibility(prev => prev.map((compat, i) => 
      i === index ? { ...compat, [field]: value } : compat
    ));
  };

  const handleCompatibilityRemove = (index: number) => {
    setCompatibility(prev => prev.filter((_, i) => i !== index));
  };

  const validateForm = () => {
    const newErrors: Record<string, string> = {};

    if (!formData.name.trim()) newErrors.name = 'Product name is required';
    if (!formData.description.trim()) newErrors.description = 'Description is required';
    if (!formData.sku.trim()) newErrors.sku = 'SKU is required';
    if (!formData.brand.trim()) newErrors.brand = 'Brand is required';
    if (!formData.categoryId) newErrors.categoryId = 'Category is required';
    if (formData.price <= 0) newErrors.price = 'Price must be greater than 0';
    if (images.length === 0) newErrors.images = 'At least one image is required';

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) return;

    try {
      const selectedCategory = categories.find(cat => cat.id === formData.categoryId);
      if (!selectedCategory) {
        setErrors({ categoryId: 'Selected category not found' });
        return;
      }

      const productData = {
        ...formData,
        category: selectedCategory,
        images,
        videos: [],
        variants,
        specifications,
        compatibility,
        ratings: {
          average: 0,
          count: 0,
          distribution: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 }
        }
      };

      if (productId) {
        await updateProduct(productId, productData);
      } else {
        await addProduct(productData);
      }

      onClose();
    } catch (error) {
      console.error('Failed to save product:', error);
      setErrors({ general: 'Failed to save product. Please try again.' });
    }
  };

  return (
    <AnimatePresence>
      <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <motion.div
          initial={{ opacity: 0, scale: 0.95 }}
          animate={{ opacity: 1, scale: 1 }}
          exit={{ opacity: 0, scale: 0.95 }}
          className="bg-white rounded-lg shadow-xl w-full max-w-6xl max-h-[90vh] overflow-hidden"
        >
          {/* Header */}
          <div className="flex items-center justify-between p-6 border-b">
            <h2 className="text-xl font-semibold text-gray-900">
              {productId ? 'Edit Product' : 'Add New Product'}
            </h2>
            <div className="flex items-center space-x-4">
              <Button
                variant="outline"
                size="sm"
                onClick={() => setShowAlibabaImport(true)}
              >
                <LinkIcon className="h-4 w-4 mr-2" />
                Import from Alibaba
              </Button>
              <button
                onClick={onClose}
                className="text-gray-400 hover:text-gray-600 transition-colors"
              >
                <X className="h-6 w-6" />
              </button>
            </div>
          </div>

          {/* Content */}
          <div className="flex h-[calc(90vh-140px)]">
            {/* Main Form */}
            <div className="flex-1 overflow-y-auto">
              <form onSubmit={handleSubmit}>
                <div className="p-6 space-y-6">
                  {/* Error Display */}
                  {errors.general && (
                    <div className="bg-red-50 border border-red-200 rounded-md p-4">
                      <div className="flex">
                        <AlertCircle className="h-5 w-5 text-red-400" />
                        <div className="ml-3">
                          <p className="text-sm text-red-800">{errors.general}</p>
                        </div>
                      </div>
                    </div>
                  )}

                  {/* Categories Debug Info */}
                  {categories.length === 0 && (
                    <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                      <div className="flex">
                        <AlertCircle className="h-5 w-5 text-yellow-400" />
                        <div className="ml-3">
                          <p className="text-sm text-yellow-800">
                            No categories loaded. Please check your API connection.
                          </p>
                        </div>
                      </div>
                    </div>
                  )}

                  {/* Basic Information */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Product Name *
                      </label>
                      <input
                        type="text"
                        name="name"
                        value={formData.name}
                        onChange={handleInputChange}
                        className={`w-full px-3 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500 ${
                          errors.name ? 'border-red-300' : 'border-gray-300'
                        }`}
                        placeholder="Enter product name"
                      />
                      {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        SKU *
                      </label>
                      <input
                        type="text"
                        name="sku"
                        value={formData.sku}
                        onChange={handleInputChange}
                        className={`w-full px-3 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500 ${
                          errors.sku ? 'border-red-300' : 'border-gray-300'
                        }`}
                        placeholder="Enter SKU"
                      />
                      {errors.sku && <p className="mt-1 text-sm text-red-600">{errors.sku}</p>}
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Brand *
                      </label>
                      <input
                        type="text"
                        name="brand"
                        value={formData.brand}
                        onChange={handleInputChange}
                        className={`w-full px-3 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500 ${
                          errors.brand ? 'border-red-300' : 'border-gray-300'
                        }`}
                        placeholder="Enter brand name"
                      />
                      {errors.brand && <p className="mt-1 text-sm text-red-600">{errors.brand}</p>}
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Category * {categories.length > 0 && <span className="text-green-600">({categories.length} available)</span>}
                      </label>
                      <select
                        name="categoryId"
                        value={formData.categoryId}
                        onChange={handleInputChange}
                        className={`w-full px-3 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500 ${
                          errors.categoryId ? 'border-red-300' : 'border-gray-300'
                        }`}
                      >
                        <option value="">
                          {categories.length === 0 ? 'Loading categories...' : 'Select a category'}
                        </option>
                        {categories.map(category => (
                          <option key={category.id} value={category.id}>
                            {category.name} ({category.productCount} products)
                          </option>
                        ))}
                      </select>
                      {errors.categoryId && <p className="mt-1 text-sm text-red-600">{errors.categoryId}</p>}
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Price *
                      </label>
                      <input
                        type="number"
                        name="price"
                        value={formData.price}
                        onChange={handleInputChange}
                        step="0.01"
                        min="0"
                        className={`w-full px-3 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500 ${
                          errors.price ? 'border-red-300' : 'border-gray-300'
                        }`}
                        placeholder="0.00"
                      />
                      {errors.price && <p className="mt-1 text-sm text-red-600">{errors.price}</p>}
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Compare At Price
                      </label>
                      <input
                        type="number"
                        name="compareAtPrice"
                        value={formData.compareAtPrice}
                        onChange={handleInputChange}
                        step="0.01"
                        min="0"
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        placeholder="0.00"
                      />
                    </div>
                  </div>

                  {/* Description */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Description *
                    </label>
                    <textarea
                      name="description"
                      value={formData.description}
                      onChange={handleInputChange}
                      rows={4}
                      className={`w-full px-3 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500 ${
                        errors.description ? 'border-red-300' : 'border-gray-300'
                      }`}
                      placeholder="Enter detailed product description"
                    />
                    {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Short Description
                    </label>
                    <textarea
                      name="shortDescription"
                      value={formData.shortDescription}
                      onChange={handleInputChange}
                      rows={2}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Enter short description for product cards"
                    />
                  </div>

                  {/* Images */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Product Images *
                    </label>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                      {images.map((image, index) => (
                        <div key={image.id} className="relative group">
                          <img
                            src={image.url}
                            alt={image.alt}
                            className="w-full h-24 object-cover rounded-md border"
                          />
                          <button
                            type="button"
                            onClick={() => handleImageRemove(image.id)}
                            className="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                          >
                            <Trash2 className="h-3 w-3" />
                          </button>
                          {index === 0 && (
                            <span className="absolute bottom-1 left-1 bg-blue-500 text-white text-xs px-1 rounded">
                              Main
                            </span>
                          )}
                        </div>
                      ))}
                      <button
                        type="button"
                        onClick={handleImageAdd}
                        className="h-24 border-2 border-dashed border-gray-300 rounded-md flex items-center justify-center hover:border-blue-500 transition-colors"
                      >
                        <Upload className="h-6 w-6 text-gray-400" />
                      </button>
                    </div>
                    {errors.images && <p className="text-sm text-red-600">{errors.images}</p>}
                  </div>

                  {/* Inventory */}
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Quantity
                      </label>
                      <input
                        type="number"
                        name="inventory.quantity"
                        value={formData.inventory.quantity}
                        onChange={handleInputChange}
                        min="0"
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                      />
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Low Stock Threshold
                      </label>
                      <input
                        type="number"
                        name="inventory.lowStockThreshold"
                        value={formData.inventory.lowStockThreshold}
                        onChange={handleInputChange}
                        min="0"
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                      />
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Status
                      </label>
                      <select
                        name="status"
                        value={formData.status}
                        onChange={handleInputChange}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                      >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="discontinued">Discontinued</option>
                      </select>
                    </div>
                  </div>

                  {/* Specifications */}
                  <div>
                    <div className="flex items-center justify-between mb-4">
                      <label className="block text-sm font-medium text-gray-700">
                        Specifications
                      </label>
                      <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={handleSpecificationAdd}
                      >
                        <Plus className="h-4 w-4 mr-1" />
                        Add Spec
                      </Button>
                    </div>
                    <div className="space-y-3">
                      {specifications.map((spec, index) => (
                        <div key={index} className="grid grid-cols-3 gap-3">
                          <input
                            type="text"
                            placeholder="Name"
                            value={spec.name}
                            onChange={(e) => handleSpecificationChange(index, 'name', e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                          />
                          <input
                            type="text"
                            placeholder="Value"
                            value={spec.value}
                            onChange={(e) => handleSpecificationChange(index, 'value', e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                          />
                          <div className="flex">
                            <input
                              type="text"
                              placeholder="Group"
                              value={spec.group}
                              onChange={(e) => handleSpecificationChange(index, 'group', e.target.value)}
                              className="flex-1 px-3 py-2 border border-gray-300 rounded-l-md focus:ring-blue-500 focus:border-blue-500"
                            />
                            <button
                              type="button"
                              onClick={() => handleSpecificationRemove(index)}
                              className="px-3 py-2 bg-red-500 text-white rounded-r-md hover:bg-red-600"
                            >
                              <Trash2 className="h-4 w-4" />
                            </button>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>

                  {/* Vehicle Compatibility */}
                  <div>
                    <div className="flex items-center justify-between mb-4">
                      <label className="block text-sm font-medium text-gray-700">
                        Vehicle Compatibility
                      </label>
                      <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={handleCompatibilityAdd}
                      >
                        <Plus className="h-4 w-4 mr-1" />
                        Add Vehicle
                      </Button>
                    </div>
                    <div className="space-y-3">
                      {compatibility.map((compat, index) => (
                        <div key={index} className="grid grid-cols-4 gap-3">
                          <input
                            type="text"
                            placeholder="Make"
                            value={compat.make}
                            onChange={(e) => handleCompatibilityChange(index, 'make', e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                          />
                          <input
                            type="text"
                            placeholder="Model"
                            value={compat.model}
                            onChange={(e) => handleCompatibilityChange(index, 'model', e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                          />
                          <input
                            type="number"
                            placeholder="Year"
                            value={compat.year}
                            onChange={(e) => handleCompatibilityChange(index, 'year', parseInt(e.target.value))}
                            className="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                          />
                          <button
                            type="button"
                            onClick={() => handleCompatibilityRemove(index)}
                            className="px-3 py-2 bg-red-500 text-white rounded-md hover:bg-red-600"
                          >
                            <Trash2 className="h-4 w-4" />
                          </button>
                        </div>
                      ))}
                    </div>
                  </div>

                  {/* Options */}
                  <div className="flex items-center space-x-6">
                    <label className="flex items-center">
                      <input
                        type="checkbox"
                        name="featured"
                        checked={formData.featured}
                        onChange={handleInputChange}
                        className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                      />
                      <span className="ml-2 text-sm text-gray-700">Featured Product</span>
                    </label>
                  </div>
                </div>

                {/* Footer */}
                <div className="flex items-center justify-end space-x-4 p-6 border-t bg-gray-50">
                  <Button
                    type="button"
                    variant="outline"
                    onClick={onClose}
                  >
                    Cancel
                  </Button>
                  <Button
                    type="submit"
                    isLoading={isLoading}
                  >
                    {productId ? 'Update Product' : 'Create Product'}
                  </Button>
                </div>
              </form>
            </div>

            {/* Alibaba Import Sidebar */}
            {showAlibabaImport && (
              <div className="w-96 border-l bg-gray-50 overflow-y-auto">
                <AlibabaImportTool
                  onImport={handleAlibabaImport}
                  onClose={() => setShowAlibabaImport(false)}
                />
              </div>
            )}
          </div>
        </motion.div>
      </div>
    </AnimatePresence>
  );
};

export default ProductForm;