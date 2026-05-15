<template>
  <div style="max-width:1000px;margin:0 auto;padding:40px 32px 80px;">
    <!-- Loading -->
    <div v-if="loading" style="display:flex;justify-content:center;padding:80px 0;">
      <div style="width:40px;height:40px;border:3px solid rgba(129,140,248,0.2);border-top-color:#818cf8;border-radius:50%;animation:spin 1s linear infinite;"></div>
    </div>

    <!-- Not found -->
    <div v-else-if="!product" style="text-align:center;padding:80px 0;">
      <div style="font-size:56px;margin-bottom:16px;">😕</div>
      <p style="color:#475569;font-size:16px;margin-bottom:20px;">Produit introuvable</p>
      <RouterLink to="/shop" style="color:#818cf8;text-decoration:none;">{{ $t('common.back_shop') }}</RouterLink>
    </div>

    <div v-else>
      <!-- Breadcrumb -->
      <nav style="display:flex;align-items:center;gap:8px;font-size:13px;color:#334155;margin-bottom:32px;">
        <RouterLink to="/" style="color:#334155;text-decoration:none;transition:color .2s;" onmouseover="this.style.color='#818cf8'" onmouseout="this.style.color='#334155'">{{ $t('nav.home') }}</RouterLink>
        <span>/</span>
        <RouterLink to="/shop" style="color:#334155;text-decoration:none;transition:color .2s;" onmouseover="this.style.color='#818cf8'" onmouseout="this.style.color='#334155'">{{ $t('nav.shop') }}</RouterLink>
        <span>/</span>
        <span style="color:#94a3b8;">{{ product.name }}</span>
      </nav>

      <div class="product-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:start;">
        <!-- Image -->
        <div class="product-image" style="position:relative;border-radius:24px;overflow:hidden;height:380px;background:linear-gradient(135deg,rgba(99,102,241,0.1),rgba(168,85,247,0.1));display:flex;align-items:center;justify-content:center;font-size:96px;border:1px solid rgba(255,255,255,0.07);">
          <img v-if="product.image" :src="product.image" :alt="product.name" style="width:100%;height:100%;object-fit:cover;"/>
          <span v-else>{{ categoryIcon }}</span>
          <div v-if="product.stock === 0" class="sold-out-overlay">
            <span class="sold-out-badge">Sold Out</span>
          </div>
        </div>

        <!-- Info -->
        <div>
          <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
            <span style="background:rgba(99,102,241,0.15);border:1px solid rgba(99,102,241,0.3);color:#818cf8;font-size:11px;font-weight:700;padding:4px 12px;border-radius:100px;">{{ product.category?.name }}</span>
            <span v-if="product.isFeatured" style="background:rgba(234,179,8,0.1);border:1px solid rgba(234,179,8,0.3);color:#fbbf24;font-size:11px;font-weight:700;padding:4px 12px;border-radius:100px;">⭐ Populaire</span>
          </div>

          <h1 style="font-size:30px;font-weight:800;color:#e2e8f0;letter-spacing:-.5px;margin-bottom:16px;line-height:1.2;">{{ product.name }}</h1>
          <p style="color:#475569;font-size:14px;line-height:1.8;margin-bottom:24px;">{{ product.description }}</p>

          <div class="gradient-text-cyan" style="font-size:40px;font-weight:800;margin-bottom:20px;">
            {{ parseFloat(product.price).toFixed(2) }} TND
          </div>

          <!-- Stock indicator -->
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:28px;">
            <div :style="product.stock > 0 ? 'background:#34d399;' : 'background:#f87171;'" style="width:8px;height:8px;border-radius:50%;"></div>
            <span :style="product.stock > 0 ? 'color:#34d399;' : 'color:#f87171;'" style="font-size:13px;font-weight:600;">
              {{ product.stock === 0 ? $t('product.sold_out') : $t('product.stock') }}
            </span>
          </div>

          <button
            @click="addToCart"
            :disabled="product.stock === 0"
            class="btn-primary"
            style="width:100%;padding:16px;font-size:15px;font-weight:700;border:none;display:flex;align-items:center;justify-content:center;gap:8px;"
            :style="product.stock === 0 ? 'opacity:.4;cursor:not-allowed;' : 'cursor:pointer;'"
          >
            🛒 {{ product.stock === 0 ? $t('product.sold_out') : $t('product.add_cart') }}
          </button>

          <!-- Feature pills -->
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:24px;">
            <div v-for="f in features" :key="f.label" class="card" style="padding:14px;text-align:center;">
              <div style="font-size:22px;margin-bottom:6px;">{{ f.icon }}</div>
              <div style="font-size:11px;color:#475569;font-weight:600;">{{ f.label }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <Transition name="slide">
      <div v-if="toast" style="position:fixed;bottom:24px;right:24px;background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;padding:14px 24px;border-radius:14px;font-weight:600;font-size:14px;box-shadow:0 8px 32px rgba(99,102,241,0.4);z-index:100;">
        ✓ {{ toast }}
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api'
import { useCartStore } from '@/stores/cart'

const route = useRoute()
const cart = useCartStore()
const product = ref(null)
const loading = ref(true)
const toast = ref('')

const features = [
  { icon: '⚡', label: 'Livraison rapide' },
  { icon: '🔒', label: 'Paiement sécurisé' },
  { icon: '🛡️', label: 'Garantie 24/7' },
]

const categoryIcon = computed(() => {
  const slug = product.value?.category?.slug || ''
  if (slug.includes('gaming')) return '🎮'
  if (slug.includes('streaming')) return '📺'
  if (slug.includes('ia') || slug.includes('ai')) return '🤖'
  if (slug.includes('music')) return '🎵'
  return '💎'
})

function addToCart() {
  if (product.value.stock === 0) return
  cart.addItem(product.value)
  toast.value = `${product.value.name} ajouté au panier`
  setTimeout(() => toast.value = '', 2500)
}

onMounted(async () => {
  const res = await api.get(`/products/${route.params.slug}`).catch(() => null)
  product.value = res?.data || null
  loading.value = false
})
</script>

<style scoped>
@keyframes spin { to { transform: rotate(360deg); } }
.slide-enter-active, .slide-leave-active { transition: all .3s ease; }
.slide-enter-from, .slide-leave-to { opacity: 0; transform: translateY(20px); }
</style>
