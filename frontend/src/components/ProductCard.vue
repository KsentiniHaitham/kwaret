<template>
  <div class="card" style="overflow:hidden;cursor:pointer;">
    <RouterLink :to="`/product/${product.slug}`" style="text-decoration:none;display:block;">
      <div style="height:160px;display:flex;align-items:center;justify-content:center;font-size:52px;background:linear-gradient(135deg,rgba(99,102,241,0.1),rgba(168,85,247,0.1));position:relative;">
        <img v-if="product.image" :src="product.image" :alt="product.name" style="width:100%;height:100%;object-fit:cover;"/>
        <span v-else>{{ categoryIcon }}</span>

        <!-- Sold Out overlay -->
        <div v-if="product.stock === 0" class="sold-out-overlay">
          <span class="sold-out-badge">Sold Out</span>
        </div>

        <!-- Badges -->
        <div style="position:absolute;top:10px;left:10px;display:flex;gap:6px;">
          <span v-if="product.isFeatured" style="background:rgba(6,9,20,0.8);border:1px solid rgba(234,179,8,0.4);color:#fbbf24;font-size:10px;font-weight:700;padding:3px 10px;border-radius:100px;">⭐ Top</span>
        </div>
      </div>
    </RouterLink>

    <div style="padding:16px;">
      <div style="font-size:10px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px;">
        {{ product.category?.name }}
      </div>
      <RouterLink :to="`/product/${product.slug}`" style="text-decoration:none;">
        <div style="font-size:14px;font-weight:600;color:#e2e8f0;margin-bottom:12px;line-height:1.4;min-height:40px;">{{ product.name }}</div>
      </RouterLink>
      <div style="display:flex;align-items:center;justify-content:space-between;">
        <span class="gradient-text-cyan" style="font-size:22px;font-weight:800;">{{ parseFloat(product.price).toFixed(2) }} TND</span>
        <button
          @click="$emit('add-to-cart', product)"
          :disabled="product.stock === 0"
          :style="product.stock === 0 ? 'opacity:.4;cursor:not-allowed;' : ''"
          style="background:linear-gradient(135deg,rgba(99,102,241,0.2),rgba(168,85,247,0.2));border:1px solid rgba(129,140,248,0.3);color:#a5b4fc;padding:7px 14px;border-radius:10px;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;"
        >
          {{ product.stock === 0 ? 'Indisponible' : '+ Ajouter' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({ product: Object })
defineEmits(['add-to-cart'])
const categoryIcon = computed(() => {
  const s = props.product?.category?.slug || ''
  if (s.includes('gaming')) return '🎮'
  if (s.includes('streaming')) return '📺'
  if (s.includes('ia')) return '🤖'
  if (s.includes('musique')) return '🎵'
  return '💎'
})
</script>
