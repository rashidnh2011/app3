import React, { useState } from 'react';
import { motion } from 'framer-motion';
import { 
  Link as LinkIcon, 
  Download, 
  CheckCircle, 
  AlertCircle, 
  Loader,
  ExternalLink,
  Image as ImageIcon,
  Package,
  DollarSign
} from 'lucide-react';
import Button from '../ui/Button';

interface AlibabaProduct {
  title: string;
  price: {
    min: number;
    max: number;
    currency: string;
  };
  images: string[];
  description: string;
  specifications: Array<{
    name: string;
    value: string;
    group: string;
  }>;
  supplier: {
    name: string;
    location: string;
    rating: number;
  };
  moq: number; // Minimum Order Quantity
  leadTime: string;
  category: string;
  keywords: string[];
}

interface AlibabaImportToolProps {
  onImport: (productData: any) => void;
  onClose: () => void;
}

const AlibabaImportTool: React.FC<AlibabaImportToolProps> = ({ onImport, onClose }) => {
  const [url, setUrl] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [importedData, setImportedData] = useState<AlibabaProduct | null>(null);
  const [error, setError] = useState('');
  const [step, setStep] = useState<'input' | 'preview' | 'success'>('input');

  // Mock Alibaba product data for demonstration
  const mockAlibabaData: AlibabaProduct = {
    title: 'Universal Car LED Headlight Kit H7 H11 9005 9006 High Power 6000K White',
    price: {
      min: 15.50,
      max: 28.90,
      currency: 'USD'
    },
    images: [
      'https://images.pexels.com/photos/3802508/pexels-photo-3802508.jpeg?auto=compress&cs=tinysrgb&w=800',
      'https://images.pexels.com/photos/1592384/pexels-photo-1592384.jpeg?auto=compress&cs=tinysrgb&w=800',
      'https://images.pexels.com/photos/3802510/pexels-photo-3802510.jpeg?auto=compress&cs=tinysrgb&w=800'
    ],
    description: 'High-quality LED headlight conversion kit with advanced cooling system. Features plug-and-play installation, superior brightness, and long lifespan. Perfect for upgrading halogen headlights to modern LED technology.',
    specifications: [
      { name: 'Power', value: '60W', group: 'Performance' },
      { name: 'Voltage', value: '12V-24V', group: 'Electrical' },
      { name: 'Color Temperature', value: '6000K', group: 'Performance' },
      { name: 'Lumens', value: '8000LM', group: 'Performance' },
      { name: 'Lifespan', value: '50,000 hours', group: 'Performance' },
      { name: 'Material', value: 'Aluminum Alloy', group: 'Construction' },
      { name: 'Waterproof Rating', value: 'IP68', group: 'Protection' }
    ],
    supplier: {
      name: 'Guangzhou Auto Parts Co., Ltd.',
      location: 'Guangzhou, China',
      rating: 4.7
    },
    moq: 10,
    leadTime: '7-15 days',
    category: 'Auto Lighting System',
    keywords: ['LED headlight', 'car lights', 'automotive lighting', 'H7', 'H11', '9005', '9006']
  };

  const validateAlibabaUrl = (url: string): boolean => {
    const alibabaPatterns = [
      /alibaba\.com/,
      /1688\.com/,
      /aliexpress\.com/
    ];
    return alibabaPatterns.some(pattern => pattern.test(url));
  };

  const extractProductData = async (url: string): Promise<AlibabaProduct> => {
    // Simulate API call to extract product data
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    // In a real implementation, this would:
    // 1. Send the URL to a backend service
    // 2. Use web scraping or Alibaba API to extract product data
    // 3. Parse and structure the data
    
    return mockAlibabaData;
  };

  const handleImport = async () => {
    if (!url.trim()) {
      setError('Please enter a valid Alibaba product URL');
      return;
    }

    if (!validateAlibabaUrl(url)) {
      setError('Please enter a valid Alibaba, 1688, or AliExpress URL');
      return;
    }

    setIsLoading(true);
    setError('');

    try {
      const productData = await extractProductData(url);
      setImportedData(productData);
      setStep('preview');
    } catch (err) {
      setError('Failed to import product data. Please check the URL and try again.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleConfirmImport = () => {
    if (!importedData) return;

    // Convert Alibaba data to our product format
    const productData = {
      name: importedData.title,
      description: importedData.description,
      shortDescription: importedData.title.substring(0, 100) + '...',
      sku: `ALI-${Date.now()}`,
      brand: importedData.supplier.name.split(' ')[0], // Use first word of supplier name
      price: importedData.price.max, // Use max price as retail price
      compareAtPrice: importedData.price.max * 1.3, // Add 30% markup for compare price
      images: importedData.images.map((url, index) => ({
        id: `img-${index}`,
        url,
        alt: importedData.title,
        position: index + 1,
        type: index === 0 ? 'main' : 'gallery'
      })),
      specifications: importedData.specifications,
      inventory: {
        quantity: importedData.moq * 2, // Start with double MOQ
        lowStockThreshold: importedData.moq,
        status: 'in_stock' as const,
        trackQuantity: true
      },
      tags: importedData.keywords,
      featured: false,
      status: 'active' as const,
      seo: {
        title: importedData.title,
        description: importedData.description,
        keywords: importedData.keywords,
        slug: importedData.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')
      }
    };

    onImport(productData);
    setStep('success');
  };

  const resetTool = () => {
    setUrl('');
    setImportedData(null);
    setError('');
    setStep('input');
  };

  return (
    <div className="bg-white rounded-lg shadow-lg p-6">
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center space-x-3">
          <div className="bg-orange-100 p-2 rounded-lg">
            <LinkIcon className="h-6 w-6 text-orange-600" />
          </div>
          <div>
            <h3 className="text-lg font-semibold text-gray-900">Alibaba Product Import</h3>
            <p className="text-sm text-gray-500">Import products directly from Alibaba URLs</p>
          </div>
        </div>
        <button
          onClick={onClose}
          className="text-gray-400 hover:text-gray-600 transition-colors"
        >
          ×
        </button>
      </div>

      {step === 'input' && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="space-y-6"
        >
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Alibaba Product URL
            </label>
            <div className="flex space-x-3">
              <input
                type="url"
                value={url}
                onChange={(e) => setUrl(e.target.value)}
                placeholder="https://www.alibaba.com/product-detail/..."
                className="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              />
              <Button
                onClick={handleImport}
                isLoading={isLoading}
                disabled={!url.trim()}
              >
                <Download className="h-4 w-4 mr-2" />
                Import
              </Button>
            </div>
            {error && (
              <div className="mt-2 flex items-center space-x-2 text-red-600">
                <AlertCircle className="h-4 w-4" />
                <span className="text-sm">{error}</span>
              </div>
            )}
          </div>

          <div className="bg-blue-50 border border-blue-200 rounded-md p-4">
            <h4 className="text-sm font-medium text-blue-800 mb-2">Supported Platforms</h4>
            <ul className="text-sm text-blue-600 space-y-1">
              <li>• Alibaba.com - B2B marketplace</li>
              <li>• 1688.com - Chinese domestic market</li>
              <li>• AliExpress.com - Consumer marketplace</li>
            </ul>
          </div>

          <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4">
            <h4 className="text-sm font-medium text-yellow-800 mb-2">Demo URL</h4>
            <p className="text-sm text-yellow-600 mb-2">
              Try this demo URL to see how the import works:
            </p>
            <button
              onClick={() => setUrl('https://www.alibaba.com/product-detail/led-headlight-kit')}
              className="text-sm text-blue-600 hover:text-blue-800 underline"
            >
              https://www.alibaba.com/product-detail/led-headlight-kit
            </button>
          </div>
        </motion.div>
      )}

      {step === 'preview' && importedData && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="space-y-6"
        >
          <div className="flex items-center space-x-2 text-green-600">
            <CheckCircle className="h-5 w-5" />
            <span className="font-medium">Product data imported successfully!</span>
          </div>

          {/* Product Preview */}
          <div className="border border-gray-200 rounded-lg p-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {/* Product Info */}
              <div>
                <h4 className="font-semibold text-gray-900 mb-3">Product Information</h4>
                <div className="space-y-3">
                  <div>
                    <span className="text-sm font-medium text-gray-500">Title:</span>
                    <p className="text-sm text-gray-900">{importedData.title}</p>
                  </div>
                  <div>
                    <span className="text-sm font-medium text-gray-500">Price Range:</span>
                    <p className="text-sm text-gray-900">
                      ${importedData.price.min} - ${importedData.price.max} {importedData.price.currency}
                    </p>
                  </div>
                  <div>
                    <span className="text-sm font-medium text-gray-500">Supplier:</span>
                    <p className="text-sm text-gray-900">{importedData.supplier.name}</p>
                    <p className="text-xs text-gray-500">{importedData.supplier.location}</p>
                  </div>
                  <div>
                    <span className="text-sm font-medium text-gray-500">MOQ:</span>
                    <p className="text-sm text-gray-900">{importedData.moq} pieces</p>
                  </div>
                  <div>
                    <span className="text-sm font-medium text-gray-500">Lead Time:</span>
                    <p className="text-sm text-gray-900">{importedData.leadTime}</p>
                  </div>
                </div>
              </div>

              {/* Images Preview */}
              <div>
                <h4 className="font-semibold text-gray-900 mb-3">Product Images</h4>
                <div className="grid grid-cols-3 gap-2">
                  {importedData.images.slice(0, 6).map((image, index) => (
                    <div key={index} className="relative">
                      <img
                        src={image}
                        alt={`Product ${index + 1}`}
                        className="w-full h-16 object-cover rounded border"
                      />
                      {index === 0 && (
                        <span className="absolute top-1 left-1 bg-blue-500 text-white text-xs px-1 rounded">
                          Main
                        </span>
                      )}
                    </div>
                  ))}
                  {importedData.images.length > 6 && (
                    <div className="w-full h-16 bg-gray-100 rounded border flex items-center justify-center">
                      <span className="text-xs text-gray-500">
                        +{importedData.images.length - 6} more
                      </span>
                    </div>
                  )}
                </div>
              </div>
            </div>

            {/* Specifications */}
            <div className="mt-6">
              <h4 className="font-semibold text-gray-900 mb-3">Specifications</h4>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {importedData.specifications.map((spec, index) => (
                  <div key={index} className="flex justify-between py-1 border-b border-gray-100">
                    <span className="text-sm text-gray-600">{spec.name}:</span>
                    <span className="text-sm text-gray-900">{spec.value}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* Description */}
            <div className="mt-6">
              <h4 className="font-semibold text-gray-900 mb-3">Description</h4>
              <p className="text-sm text-gray-600 line-clamp-3">{importedData.description}</p>
            </div>
          </div>

          {/* Import Settings */}
          <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h4 className="font-semibold text-gray-900 mb-3">Import Settings</h4>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
              <div className="flex items-center space-x-2">
                <Package className="h-4 w-4 text-blue-500" />
                <span>SKU: ALI-{Date.now().toString().slice(-6)}</span>
              </div>
              <div className="flex items-center space-x-2">
                <DollarSign className="h-4 w-4 text-green-500" />
                <span>Retail Price: ${importedData.price.max}</span>
              </div>
              <div className="flex items-center space-x-2">
                <ImageIcon className="h-4 w-4 text-purple-500" />
                <span>{importedData.images.length} Images</span>
              </div>
            </div>
          </div>

          {/* Action Buttons */}
          <div className="flex items-center justify-end space-x-4">
            <Button variant="outline" onClick={resetTool}>
              Import Another
            </Button>
            <Button onClick={handleConfirmImport}>
              <CheckCircle className="h-4 w-4 mr-2" />
              Confirm Import
            </Button>
          </div>
        </motion.div>
      )}

      {step === 'success' && (
        <motion.div
          initial={{ opacity: 0, scale: 0.95 }}
          animate={{ opacity: 1, scale: 1 }}
          className="text-center py-8"
        >
          <div className="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
            <CheckCircle className="h-8 w-8 text-green-600" />
          </div>
          <h3 className="text-lg font-semibold text-gray-900 mb-2">
            Product Imported Successfully!
          </h3>
          <p className="text-gray-600 mb-6">
            The product has been added to your catalog and is ready for review.
          </p>
          <div className="flex items-center justify-center space-x-4">
            <Button variant="outline" onClick={resetTool}>
              Import Another Product
            </Button>
            <Button onClick={onClose}>
              Close
            </Button>
          </div>
        </motion.div>
      )}

      {isLoading && (
        <div className="absolute inset-0 bg-white bg-opacity-90 flex items-center justify-center rounded-lg">
          <div className="text-center">
            <Loader className="h-8 w-8 text-blue-600 animate-spin mx-auto mb-4" />
            <p className="text-sm text-gray-600">Importing product data...</p>
            <p className="text-xs text-gray-500 mt-1">This may take a few seconds</p>
          </div>
        </div>
      )}
    </div>
  );
};

export default AlibabaImportTool;