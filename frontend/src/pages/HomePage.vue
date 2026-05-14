<template>
  <div style="max-width:1200px;margin:0 auto;padding:0 32px;">

    <!-- Hero -->
    <section style="min-height:88vh;display:flex;align-items:center;padding:60px 0;">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;width:100%;">
        <div>
          <div class="pill" style="margin-bottom:28px;">
            <span style="width:6px;height:6px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a855f7);display:inline-block;"></span>
            Livraison instantanée · Garantie 24/7
          </div>
          <h1 style="font-size:58px;font-weight:800;line-height:1.1;letter-spacing:-2px;margin-bottom:20px;">
            Le meilleur du<br>
            <span class="gradient-text">numérique</span><br>
            à portée de main
          </h1>
          <p style="color:#64748b;font-size:17px;line-height:1.7;margin-bottom:36px;max-width:440px;">
            Gaming, streaming, intelligence artificielle — des produits premium livrés en moins d'une heure.
          </p>
          <div style="display:flex;gap:12px;margin-bottom:48px;flex-wrap:wrap;">
            <RouterLink to="/shop" class="btn-primary" style="text-decoration:none;">Explorer la boutique →</RouterLink>
            <RouterLink to="/register" class="btn-ghost" style="text-decoration:none;">Créer un compte</RouterLink>
          </div>
          <div style="display:flex;gap:40px;">
            <div v-for="s in stats" :key="s.label">
              <div class="gradient-text-cyan" style="font-size:28px;font-weight:800;">{{ s.value }}</div>
              <div style="color:#475569;font-size:12px;margin-top:2px;">{{ s.label }}</div>
            </div>
          </div>
        </div>

        <!-- Mini product cards -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <div v-if="loadingFeatured" v-for="n in 4" :key="n" style="height:130px;border-radius:20px;background:rgba(255,255,255,0.03);animation:pulse 1.5s infinite;"></div>
          <template v-else>
            <div
              v-for="(p, i) in featured.slice(0,4)"
              :key="p.id"
              :style="i===0 ? 'grid-column:span 2;background:linear-gradient(135deg,rgba(99,102,241,0.12),rgba(168,85,247,0.12));border:1px solid rgba(129,140,248,0.25);border-radius:20px;padding:24px;' : 'background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:20px;padding:20px;'"
            >
              <div style="font-size:28px;margin-bottom:10px;">{{ iconFor(p.category?.slug) }}</div>
              <div style="font-size:10px;color:#475569;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">{{ p.category?.name }}</div>
              <div style="font-size:14px;font-weight:600;color:#e2e8f0;margin-bottom:8px;">{{ p.name }}</div>
              <div class="gradient-text-cyan" style="font-size:20px;font-weight:800;">{{ parseFloat(p.price).toFixed(2) }} TND</div>
            </div>
          </template>
        </div>
      </div>
    </section>

    <!-- Categories -->
    <section style="padding-bottom:80px;">
      <div style="margin-bottom:40px;">
        <div style="display:inline-block;background:rgba(129,140,248,0.1);border:1px solid rgba(129,140,248,0.2);color:#818cf8;font-size:11px;font-weight:700;padding:5px 14px;border-radius:100px;letter-spacing:1px;text-transform:uppercase;margin-bottom:14px;">Catalogue</div>
        <h2 style="font-size:38px;font-weight:800;letter-spacing:-1px;">Nos <span class="gradient-text">catégories</span></h2>
      </div>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;">
        <RouterLink
          v-for="cat in categories" :key="cat.id"
          :to="`/shop?category=${cat.id}`"
          style="text-decoration:none;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:20px;padding:28px 20px;text-align:center;transition:all .3s;display:block;"
          onmouseover="this.style.borderColor='rgba(129,140,248,0.3)';this.style.background='rgba(129,140,248,0.06)'"
          onmouseout="this.style.borderColor='rgba(255,255,255,0.07)';this.style.background='rgba(255,255,255,0.03)'"
        >
          <div style="font-size:38px;margin-bottom:12px;filter:drop-shadow(0 4px 12px rgba(129,140,248,0.3));">{{ cat.icon }}</div>
          <div style="font-size:15px;font-weight:600;color:#94a3b8;">{{ cat.name }}</div>
        </RouterLink>
      </div>
    </section>

    <!-- Top vendus -->
    <section style="padding-bottom:80px;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:40px;">
        <div>
          <div style="display:inline-block;background:rgba(129,140,248,0.1);border:1px solid rgba(129,140,248,0.2);color:#818cf8;font-size:11px;font-weight:700;padding:5px 14px;border-radius:100px;letter-spacing:1px;text-transform:uppercase;margin-bottom:14px;">Top ventes</div>
          <h2 style="font-size:38px;font-weight:800;letter-spacing:-1px;">Produits <span class="gradient-text">populaires</span></h2>
        </div>
        <RouterLink to="/shop" style="color:#818cf8;text-decoration:none;font-size:14px;">Voir tout →</RouterLink>
      </div>
      <div v-if="loadingFeatured" style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;">
        <div v-for="n in 4" :key="n" style="height:280px;border-radius:20px;background:rgba(255,255,255,0.03);"></div>
      </div>
      <div v-else style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;">
        <ProductCard v-for="p in featured" :key="p.id" :product="p" @add-to-cart="addToCart"/>
      </div>
    </section>

    <!-- Toast -->
    <Transition name="slide">
      <div v-if="toast" style="position:fixed;bottom:24px;right:24px;background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;padding:14px 24px;border-radius:14px;font-weight:600;font-size:14px;box-shadow:0 8px 32px rgba(99,102,241,0.4);z-index:100;">
        ✓ {{ toast }}
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import { useCartStore } from '@/stores/cart'
import ProductCard from '@/components/ProductCard.vue'

const categories = ref([])
const featured = ref([])
const loadingFeatured = ref(true)
const toast = ref('')
const cart = useCartStore()

const stats = [
  { value: '2K+', label: 'Clients satisfaits' },
  { value: '500+', label: 'Produits dispo' },
  { value: '< 1h', label: 'Délai livraison' },
]

const iconFor = (slug = '') => {
  if (slug.includes('gaming')) return '🎮'
  if (slug.includes('streaming')) return '📺'
  if (slug.includes('ia')) return '🤖'
  return '🎵'
}

function addToCart(product) {
  if (product.stock === 0) return
  cart.addItem(product)
  toast.value = `${product.name} ajouté au panier`
  setTimeout(() => toast.value = '', 2500)
}

onMounted(async () => {
  const [catsRes, prodsRes] = await Promise.all([
    api.get('/categories').catch(() => ({ data: [] })),
    api.get('/products/featured').catch(() => ({ data: [] })),
  ])
  categories.value = catsRes.data
  featured.value = prodsRes.data
  loadingFeatured.value = false
})
</script>
