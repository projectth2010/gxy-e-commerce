<template>
  <div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Our Products</h1>
    
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-8">
      <p>Loading products...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
      <p>Error loading products: {{ error }}</p>
    </div>

    <!-- Products Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="product in products" :key="product.id" class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="h-48 bg-gray-200 flex items-center justify-center">
          <span v-if="!product.image" class="text-gray-500">No image</span>
          <img v-else :src="product.image" :alt="product.name" class="h-full w-full object-cover">
        </div>
        <div class="p-4">
          <h2 class="text-xl font-semibold mb-2">{{ product.name }}</h2>
          <p class="text-gray-600 mb-4">{{ product.description || 'No description available' }}</p>
          <div class="flex justify-between items-center">
            <span class="text-lg font-bold">฿{{ product.price.toLocaleString() }}</span>
            <span :class="['text-sm', product.stock > 0 ? 'text-green-600' : 'text-red-600']">
              {{ product.stock > 0 ? `In Stock (${product.stock})` : 'Out of Stock' }}
            </span>
          </div>
          <button 
            @click="addToCart(product)" 
            :disabled="product.stock <= 0"
            class="mt-4 w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 disabled:bg-gray-400"
          >
            Add to Cart
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const products = ref([]);
const loading = ref(true);
const error = ref(null);

const fetchProducts = async () => {
  try {
    loading.value = true;
    const response = await axios.get('http://localhost:8000/api/products');
    products.value = response.data;
  } catch (err) {
    console.error('Error fetching products:', err);
    error.value = err.message || 'Failed to load products';
  } finally {
    loading.value = false;
  }
};

const addToCart = (product) => {
  // TODO: Implement add to cart functionality
  console.log('Added to cart:', product);
  alert(`Added ${product.name} to cart!`);
};

onMounted(() => {
  fetchProducts();
});
</script>
