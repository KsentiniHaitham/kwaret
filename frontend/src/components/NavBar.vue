<template>
  <nav style="position:sticky;top:0;z-index:50;height:64px;padding:0 32px;display:flex;align-items:center;gap:16px;background:rgba(6,9,20,0.9);border-bottom:1px solid rgba(255,255,255,0.07);">
    <!-- Logo -->
    <RouterLink to="/" style="text-decoration:none;flex-shrink:0;">
      <span style="font-size:22px;font-weight:800;" class="gradient-text">✦ Kwaret</span>
    </RouterLink>

    <!-- Nav links -->
    <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
      <RouterLink to="/" class="nav-a">Accueil</RouterLink>
      <RouterLink to="/shop" class="nav-a">Boutique</RouterLink>
      <RouterLink v-if="isAdmin" to="/admin" class="nav-a" style="color:#a5b4fc;">📊 Admin</RouterLink>
    </div>

    <!-- Search bar — centre, prend tout l'espace disponible -->
    <div style="flex:1;max-width:480px;margin:0 auto;">
      <SearchBar />
    </div>

    <!-- Right actions -->
    <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
      <RouterLink to="/cart" style="position:relative;padding:8px;color:#94a3b8;text-decoration:none;font-size:18px;">
        🛒
        <span v-if="cart.count > 0" style="position:absolute;top:-2px;right:-2px;background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;font-size:10px;font-weight:700;width:17px;height:17px;border-radius:50%;display:flex;align-items:center;justify-content:center;">{{ cart.count }}</span>
      </RouterLink>

      <template v-if="auth.isLoggedIn">
        <RouterLink to="/orders" class="nav-a">Commandes</RouterLink>
        <RouterLink to="/support" class="nav-a">Support</RouterLink>
        <UserNotifications />
        <RouterLink to="/wallet" class="nav-a wallet-link">
          <span style="font-size:11px;font-weight:700;color:#818cf8;display:block;line-height:1;">SOLDE</span>
          <span style="font-size:13px;font-weight:800;color:#e2e8f0;">{{ balance.toFixed(2) }} TND</span>
        </RouterLink>
        <span style="color:#475569;font-size:13px;max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ auth.user?.firstName }}</span>
        <button @click="auth.logout(); $router.push('/')" class="btn-ghost" style="padding:7px 14px;font-size:13px;">Déconnexion</button>
      </template>
      <template v-else>
        <RouterLink to="/login" class="nav-a">Connexion</RouterLink>
        <RouterLink to="/register" class="btn-primary" style="padding:8px 16px;font-size:13px;text-decoration:none;">S'inscrire</RouterLink>
      </template>
    </div>
  </nav>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import SearchBar from '@/components/SearchBar.vue'
import UserNotifications from '@/components/UserNotifications.vue'
import api from '@/api'

const auth    = useAuthStore()
const cart    = useCartStore()
const isAdmin = computed(() => auth.user?.roles?.includes('ROLE_ADMIN'))
const balance = ref(0)

async function fetchBalance() {
  if (!auth.isLoggedIn) return
  try {
    const r = await api.get('/wallet')
    balance.value = parseFloat(r.data.balance)
  } catch {}
}

watch(() => auth.isLoggedIn, (v) => { if (v) fetchBalance() }, { immediate: true })
</script>

<style scoped>
.nav-a {
  color: #94a3b8;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  padding: 7px 12px;
  border-radius: 8px;
  transition: all 0.2s;
  white-space: nowrap;
}
.nav-a:hover { color: #e2e8f0; background: rgba(255,255,255,0.06); }
.wallet-link {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  padding: 5px 12px;
  border-radius: 10px;
  border: 1px solid rgba(129,140,248,0.2);
  background: rgba(99,102,241,0.06);
  line-height: 1.3;
}
.wallet-link:hover { background: rgba(99,102,241,0.12); border-color: rgba(129,140,248,0.4); }
</style>
