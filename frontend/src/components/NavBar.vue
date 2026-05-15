<template>
  <nav style="position:sticky;top:0;z-index:50;background:rgba(6,9,20,0.95);border-bottom:1px solid rgba(255,255,255,0.07);backdrop-filter:blur(12px);">
    <!-- Desktop / Mobile top bar -->
    <div style="height:64px;padding:0 20px;display:flex;align-items:center;gap:12px;">
      <!-- Logo -->
      <RouterLink to="/" style="text-decoration:none;flex-shrink:0;">
        <span style="font-size:22px;font-weight:800;" class="gradient-text">✦ Kwaret</span>
      </RouterLink>

      <!-- Nav links — hidden on mobile -->
      <div class="nav-links">
        <RouterLink to="/" class="nav-a">{{ $t('nav.home') }}</RouterLink>
        <RouterLink to="/shop" class="nav-a">{{ $t('nav.shop') }}</RouterLink>
        <RouterLink v-if="isAdmin" to="/admin" class="nav-a" style="color:#a5b4fc;">📊 {{ $t('nav.admin') }}</RouterLink>
      </div>

      <!-- Search bar — hidden on mobile -->
      <div class="nav-search">
        <SearchBar />
      </div>

      <!-- Right actions — hidden on mobile -->
      <div class="nav-right">
        <RouterLink to="/cart" style="position:relative;padding:8px;color:#94a3b8;text-decoration:none;font-size:18px;">
          🛒
          <span v-if="cart.count > 0" style="position:absolute;top:-2px;right:-2px;background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;font-size:10px;font-weight:700;width:17px;height:17px;border-radius:50%;display:flex;align-items:center;justify-content:center;">{{ cart.count }}</span>
        </RouterLink>
        <template v-if="auth.isLoggedIn">
          <RouterLink to="/orders" class="nav-a">{{ $t('nav.orders') }}</RouterLink>
          <RouterLink to="/support" class="nav-a">{{ $t('nav.support') }}</RouterLink>
          <UserNotifications />
          <RouterLink to="/wallet" class="nav-a wallet-link">
            <span style="font-size:11px;font-weight:700;color:#818cf8;display:block;line-height:1;">{{ $t('nav.balance') }}</span>
            <span style="font-size:13px;font-weight:800;color:#e2e8f0;">{{ balance.toFixed(2) }} TND</span>
          </RouterLink>
          <button @click="auth.logout(); $router.push('/')" class="btn-ghost" style="padding:7px 14px;font-size:13px;">{{ $t('nav.logout') }}</button>
        </template>
        <template v-else>
          <RouterLink to="/login" class="nav-a">{{ $t('nav.login') }}</RouterLink>
          <RouterLink to="/register" class="btn-primary" style="padding:8px 16px;font-size:13px;text-decoration:none;">{{ $t('nav.register') }}</RouterLink>
        </template>
        <!-- Language toggle -->
        <button @click="lang.toggle()"
          style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:8px;padding:5px 10px;color:#94a3b8;font-size:12px;font-weight:700;cursor:pointer;letter-spacing:.5px;transition:all .2s;"
          onmouseover="this.style.borderColor='rgba(129,140,248,0.4)';this.style.color='#818cf8'"
          onmouseout="this.style.borderColor='rgba(255,255,255,0.1)';this.style.color='#94a3b8'">
          {{ $t('nav.lang_switch') }}
        </button>
      </div>

      <!-- Mobile right: cart + hamburger -->
      <div class="mobile-actions">
        <RouterLink to="/cart" style="position:relative;padding:8px;color:#94a3b8;text-decoration:none;font-size:20px;">
          🛒
          <span v-if="cart.count > 0" style="position:absolute;top:-2px;right:-2px;background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;font-size:10px;font-weight:700;width:17px;height:17px;border-radius:50%;display:flex;align-items:center;justify-content:center;">{{ cart.count }}</span>
        </RouterLink>
        <UserNotifications v-if="auth.isLoggedIn" />
        <button @click="menuOpen = !menuOpen" class="hamburger" :class="{ open: menuOpen }">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>

    <!-- Mobile search bar -->
    <div class="mobile-search">
      <SearchBar />
    </div>

    <!-- Mobile menu overlay -->
    <Transition name="slide-down">
      <div v-if="menuOpen" class="mobile-menu">
        <RouterLink to="/" class="mobile-link" @click="menuOpen=false">🏠 {{ $t('nav.home') }}</RouterLink>
        <RouterLink to="/shop" class="mobile-link" @click="menuOpen=false">🛍️ {{ $t('nav.shop') }}</RouterLink>
        <template v-if="auth.isLoggedIn">
          <RouterLink to="/orders" class="mobile-link" @click="menuOpen=false">📦 {{ $t('nav.orders') }}</RouterLink>
          <RouterLink to="/support" class="mobile-link" @click="menuOpen=false">🎫 {{ $t('nav.support') }}</RouterLink>
          <RouterLink to="/wallet" class="mobile-link" @click="menuOpen=false">
            💳 {{ $t('nav.wallet') }} — <span style="color:#818cf8;font-weight:700;">{{ balance.toFixed(2) }} TND</span>
          </RouterLink>
          <RouterLink v-if="isAdmin" to="/admin" class="mobile-link" style="color:#a5b4fc;" @click="menuOpen=false">📊 {{ $t('nav.admin') }}</RouterLink>
          <button @click="auth.logout(); $router.push('/'); menuOpen=false" class="mobile-link" style="background:none;border:none;cursor:pointer;color:#f87171;text-align:left;width:100%;">🚪 {{ $t('nav.logout') }}</button>
        </template>
        <template v-else>
          <RouterLink to="/login" class="mobile-link" @click="menuOpen=false">🔑 {{ $t('nav.login') }}</RouterLink>
          <RouterLink to="/register" class="mobile-link" style="color:#818cf8;font-weight:700;" @click="menuOpen=false">✨ {{ $t('nav.register') }}</RouterLink>
        </template>
        <button @click="lang.toggle(); menuOpen=false" class="mobile-link" style="background:none;border:none;cursor:pointer;text-align:left;width:100%;color:#94a3b8;">
          🌐 {{ $t('nav.lang_switch_full') }}
        </button>
      </div>
    </Transition>
  </nav>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useLangStore } from '@/stores/lang'
import SearchBar from '@/components/SearchBar.vue'
import UserNotifications from '@/components/UserNotifications.vue'
import api from '@/api'

const auth    = useAuthStore()
const cart    = useCartStore()
const lang    = useLangStore()
const isAdmin = computed(() => auth.user?.roles?.includes('ROLE_ADMIN'))
const balance = ref(0)
const menuOpen = ref(false)

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
.nav-links { display:flex;align-items:center;gap:4px;flex-shrink:0; }
.nav-search { flex:1;max-width:480px;margin:0 auto; }
.nav-right  { display:flex;align-items:center;gap:8px;flex-shrink:0; }
.mobile-actions { display:none;align-items:center;gap:4px;margin-left:auto; }
.mobile-search  { display:none;padding:8px 16px 12px; }

/* Hamburger */
.hamburger {
  background: none; border: none; cursor: pointer;
  width: 36px; height: 36px;
  display: flex; flex-direction: column;
  justify-content: center; align-items: center; gap: 5px;
}
.hamburger span {
  display: block; width: 22px; height: 2px;
  background: #94a3b8; border-radius: 2px;
  transition: all .25s;
}
.hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.hamburger.open span:nth-child(2) { opacity: 0; }
.hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

/* Mobile menu */
.mobile-menu {
  background: rgba(6,9,20,0.98);
  border-top: 1px solid rgba(255,255,255,0.07);
  display: flex; flex-direction: column;
  padding: 8px 0 16px;
}
.mobile-link {
  padding: 14px 20px;
  color: #94a3b8;
  text-decoration: none;
  font-size: 15px;
  font-weight: 500;
  border-bottom: 1px solid rgba(255,255,255,0.04);
  display: block;
  transition: background .15s, color .15s;
}
.mobile-link:hover { background: rgba(255,255,255,0.04); color: #e2e8f0; }

/* Transition */
.slide-down-enter-active, .slide-down-leave-active { transition: opacity .2s, transform .2s; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-8px); }

/* Wallet link */
.wallet-link {
  display: flex; flex-direction: column; align-items: flex-end;
  padding: 5px 12px; border-radius: 10px;
  border: 1px solid rgba(129,140,248,0.2);
  background: rgba(99,102,241,0.06); line-height: 1.3;
}
.wallet-link:hover { background: rgba(99,102,241,0.12); border-color: rgba(129,140,248,0.4); }

.nav-a {
  color: #94a3b8; text-decoration: none; font-size: 14px;
  font-weight: 500; padding: 7px 12px; border-radius: 8px;
  transition: all 0.2s; white-space: nowrap;
}
.nav-a:hover { color: #e2e8f0; background: rgba(255,255,255,0.06); }

/* ── Responsive ── */
@media (max-width: 768px) {
  .nav-links, .nav-search, .nav-right { display: none !important; }
  .mobile-actions { display: flex !important; }
  .mobile-search  { display: block !important; }
}
</style>
