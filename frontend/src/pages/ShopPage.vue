<template>
  <div style="max-width:1200px;margin:0 auto;padding:32px 32px 80px;">
    <div style="margin-bottom:40px;">
      <div style="display:inline-block;background:rgba(129,140,248,0.1);border:1px solid rgba(129,140,248,0.2);color:#818cf8;font-size:11px;font-weight:700;padding:5px 14px;border-radius:100px;letter-spacing:1px;text-transform:uppercase;margin-bottom:14px;">Catalogue</div>
      <h1 style="font-size:42px;font-weight:800;letter-spacing:-1.5px;">Notre <span class="gradient-text">boutique</span></h1>
    </div>

    <div class="shop-layout" style="display:flex;gap:28px;align-items:flex-start;">
      <!-- Sidebar -->
      <aside class="shop-sidebar" style="width:220px;flex-shrink:0;position:sticky;top:84px;">
        <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:20px;padding:20px;">
          <div style="font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:1px;margin-bottom:14px;">Catégories</div>
          <div style="display:flex;flex-direction:column;gap:4px;">
            <button
              @click="activeCategory = null; loadProducts()"
              :style="activeCategory === null ? 'background:linear-gradient(135deg,rgba(99,102,241,0.2),rgba(168,85,247,0.2));border:1px solid rgba(129,140,248,0.3);color:#a5b4fc;' : 'background:transparent;border:1px solid transparent;color:#475569;'"
              style="width:100%;text-align:left;padding:9px 12px;border-radius:12px;font-size:13px;font-weight:500;cursor:pointer;transition:all .2s;"
            >
              Tous les produits
            </button>
            <button
              v-for="cat in categories"
              :key="cat.id"
              @click="activeCategory = cat.id; loadProducts()"
              :style="activeCategory === cat.id ? 'background:linear-gradient(135deg,rgba(99,102,241,0.2),rgba(168,85,247,0.2));border:1px solid rgba(129,140,248,0.3);color:#a5b4fc;' : 'background:transparent;border:1px solid transparent;color:#475569;'"
              style="width:100%;text-align:left;padding:9px 12px;border-radius:12px;font-size:13px;font-weight:500;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:8px;"
            >
              <span>{{ cat.icon }}</span><span>{{ cat.name }}</span>
            </button>
          </div>
        </div>
      </aside>

      <!-- Main -->
      <div style="flex:1;min-width:0;">
        <!-- Search -->
        <div ref="searchWrapper" style="margin-bottom:24px;position:relative;">
          <div style="position:relative;">
            <span style="position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#475569;pointer-events:none;">🔍</span>
            <input
              ref="searchInput"
              v-model="search"
              type="text"
              placeholder="Rechercher un produit..."
              style="width:100%;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:14px;padding:13px 16px 13px 42px;color:#e2e8f0;font-size:14px;outline:none;box-sizing:border-box;transition:border .2s;"
              :style="dropdownOpen ? 'border-color:rgba(129,140,248,0.5);border-bottom-left-radius:0;border-bottom-right-radius:0;border-bottom-color:transparent;' : ''"
              @focus="onSearchFocus"
              @keydown.escape="closeDropdown"
              @keydown.down.prevent="moveDown"
              @keydown.up.prevent="moveUp"
              @keydown.enter.prevent="selectActive"
            />
            <button v-if="search" @click="search=''; searchInput.focus()"
              style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:#475569;cursor:pointer;font-size:16px;line-height:1;padding:2px 4px;transition:color .2s;"
              onmouseover="this.style.color='#94a3b8'" onmouseout="this.style.color='#475569'">✕</button>
          </div>

          <!-- Dropdown -->
          <Transition name="drop">
            <div v-if="dropdownOpen" style="position:absolute;top:100%;left:0;right:0;background:#0d1117;border:1px solid rgba(129,140,248,0.4);border-top:none;border-bottom-left-radius:14px;border-bottom-right-radius:14px;overflow:hidden;z-index:200;box-shadow:0 20px 60px rgba(0,0,0,0.6);">

              <!-- Loading skeletons -->
              <div v-if="dropdownLoading" style="padding:12px;display:flex;flex-direction:column;gap:8px;">
                <div v-for="n in 3" :key="n" style="height:52px;border-radius:10px;background:rgba(255,255,255,0.04);animation:pulse 1.5s infinite;display:flex;align-items:center;gap:10px;padding:0 12px;">
                  <div style="width:40px;height:40px;border-radius:8px;background:rgba(255,255,255,0.06);flex-shrink:0;"></div>
                  <div style="flex:1;">
                    <div style="height:10px;border-radius:4px;background:rgba(255,255,255,0.06);margin-bottom:6px;width:55%;"></div>
                    <div style="height:8px;border-radius:4px;background:rgba(255,255,255,0.04);width:30%;"></div>
                  </div>
                </div>
              </div>

              <!-- No results -->
              <div v-else-if="dropdownResults.length === 0" style="padding:24px;text-align:center;color:#334155;font-size:13px;">
                <div style="font-size:32px;margin-bottom:8px;">🔍</div>
                Aucun produit pour « {{ search }} »
              </div>

              <!-- Results -->
              <div v-else style="max-height:380px;overflow-y:auto;">
                <div
                  v-for="(p, idx) in dropdownResults"
                  :key="p.id"
                  @click="selectResult(p)"
                  @mouseenter="activeIdx = idx"
                  style="display:flex;align-items:center;gap:12px;padding:10px 14px;cursor:pointer;transition:background .15s;"
                  :style="idx === activeIdx ? 'background:rgba(129,140,248,0.12);' : 'background:transparent;'"
                >
                  <!-- Thumbnail -->
                  <div style="width:44px;height:44px;border-radius:10px;overflow:hidden;flex-shrink:0;background:linear-gradient(135deg,rgba(99,102,241,0.15),rgba(168,85,247,0.15));display:flex;align-items:center;justify-content:center;font-size:20px;">
                    <img v-if="p.image" :src="p.image" :alt="p.name" style="width:100%;height:100%;object-fit:cover;" />
                    <span v-else>{{ iconFor(p.category?.slug) }}</span>
                  </div>
                  <!-- Info -->
                  <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:600;color:#e2e8f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" v-html="highlight(p.name)"></div>
                    <div style="font-size:11px;color:#475569;margin-top:2px;">{{ p.category?.name }}</div>
                  </div>
                  <!-- Price -->
                  <div style="text-align:right;flex-shrink:0;">
                    <div class="gradient-text-cyan" style="font-size:14px;font-weight:800;">{{ parseFloat(p.price).toFixed(2) }} TND</div>
                    <div v-if="p.stock === 0" style="font-size:10px;color:#f87171;margin-top:2px;">Indisponible</div>
                  </div>
                </div>

                <!-- View all -->
                <div
                  @click="closeDropdown"
                  style="display:flex;align-items:center;justify-content:center;gap:6px;padding:12px;border-top:1px solid rgba(255,255,255,0.06);color:#818cf8;font-size:12px;font-weight:600;cursor:pointer;transition:background .15s;"
                  onmouseover="this.style.background='rgba(129,140,248,0.06)'" onmouseout="this.style.background='transparent'"
                >
                  Voir tous les résultats pour « {{ search }} » →
                </div>
              </div>

            </div>
          </Transition>
        </div>

        <!-- Loading skeletons -->
        <div v-if="loading" style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;">
          <div v-for="n in 6" :key="n" style="height:280px;border-radius:20px;background:rgba(255,255,255,0.03);animation:pulse 1.5s infinite;"></div>
        </div>

        <!-- Empty -->
        <div v-else-if="filteredProducts.length === 0" style="text-align:center;padding:80px 0;">
          <div style="font-size:56px;margin-bottom:16px;">🔍</div>
          <p style="color:#475569;font-size:16px;">Aucun produit trouvé</p>
        </div>

        <!-- Grid -->
        <div v-else class="shop-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;">
          <ProductCard v-for="product in filteredProducts" :key="product.id" :product="product" @add-to-cart="addToCart"/>
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
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api'
import { useCartStore } from '@/stores/cart'
import ProductCard from '@/components/ProductCard.vue'

const route = useRoute()
const router = useRouter()
const cart = useCartStore()

const categories = ref([])
const products = ref([])
const loading = ref(true)
const search = ref(route.query.q || '')
const activeCategory = ref(route.query.category ? parseInt(route.query.category) : null)
const toast = ref('')

// Search dropdown state
const searchWrapper = ref(null)
const searchInput = ref(null)
const dropdownOpen = ref(false)
const dropdownResults = ref([])
const dropdownLoading = ref(false)
const activeIdx = ref(-1)
let debounceTimer = null

const filteredProducts = computed(() => {
  if (!search.value.trim()) return products.value
  const q = search.value.toLowerCase()
  return products.value.filter(p => p.name.toLowerCase().includes(q) || p.description?.toLowerCase().includes(q))
})

watch(search, (val) => {
  activeIdx.value = -1
  clearTimeout(debounceTimer)
  if (val.trim().length < 2) {
    dropdownResults.value = []
    dropdownOpen.value = false
    return
  }
  dropdownLoading.value = true
  dropdownOpen.value = true
  debounceTimer = setTimeout(() => {
    const q = val.toLowerCase()
    dropdownResults.value = products.value
      .filter(p => p.name.toLowerCase().includes(q) || p.description?.toLowerCase().includes(q) || p.category?.name?.toLowerCase().includes(q))
      .slice(0, 8)
    dropdownLoading.value = false
  }, 150)
})

function onSearchFocus() {
  if (search.value.trim().length >= 2) dropdownOpen.value = true
}

function closeDropdown() {
  dropdownOpen.value = false
  activeIdx.value = -1
}

function moveDown() {
  if (!dropdownOpen.value) return
  activeIdx.value = Math.min(activeIdx.value + 1, dropdownResults.value.length - 1)
}

function moveUp() {
  activeIdx.value = Math.max(activeIdx.value - 1, -1)
}

function selectActive() {
  if (activeIdx.value >= 0 && dropdownResults.value[activeIdx.value]) {
    router.push(`/product/${dropdownResults.value[activeIdx.value].slug}`)
    closeDropdown()
  }
}

function selectResult(product) {
  router.push(`/product/${product.slug}`)
  closeDropdown()
}

function highlight(text) {
  if (!search.value) return text
  const regex = new RegExp(`(${search.value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi')
  return text.replace(regex, '<mark style="background:rgba(129,140,248,0.3);color:#a5b4fc;border-radius:3px;padding:0 2px;">$1</mark>')
}

function iconFor(slug = '') {
  if (slug?.includes('gaming')) return '🎮'
  if (slug?.includes('streaming')) return '📺'
  if (slug?.includes('ia')) return '🤖'
  return '🎵'
}

function handleClickOutside(e) {
  if (searchWrapper.value && !searchWrapper.value.contains(e.target)) closeDropdown()
}

function addToCart(product) {
  if (product.stock === 0) return
  cart.addItem(product)
  toast.value = `${product.name} ajouté au panier`
  setTimeout(() => toast.value = '', 2500)
}

async function loadProducts() {
  loading.value = true
  const url = activeCategory.value ? `/products?category=${activeCategory.value}` : '/products'
  const res = await api.get(url).catch(() => ({ data: [] }))
  products.value = res.data
  loading.value = false
}

onMounted(async () => {
  const [catsRes] = await Promise.all([
    api.get('/categories').catch(() => ({ data: [] })),
    loadProducts(),
  ])
  categories.value = catsRes.data
  document.addEventListener('mousedown', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleClickOutside)
  clearTimeout(debounceTimer)
})
</script>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: all .3s ease; }
.slide-enter-from, .slide-leave-to { opacity: 0; transform: translateY(20px); }
.drop-enter-active, .drop-leave-active { transition: opacity .15s, transform .15s; }
.drop-enter-from, .drop-leave-to { opacity: 0; transform: translateY(-6px); }
@keyframes pulse { 0%,100%{opacity:.5} 50%{opacity:.2} }
</style>
