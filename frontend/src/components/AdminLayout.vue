<template>
  <div style="display:flex;min-height:calc(100vh - 64px);position:relative;">

    <!-- Mobile top bar -->
    <div class="admin-mobile-bar">
      <span style="font-size:13px;font-weight:700;color:#a5b4fc;">📊 Admin</span>
      <div style="display:flex;align-items:center;gap:8px;">
        <AdminNotifications />
        <button @click="sidebarOpen = !sidebarOpen"
          style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:8px;padding:7px 10px;cursor:pointer;color:#94a3b8;font-size:16px;">
          {{ sidebarOpen ? '✕' : '☰' }}
        </button>
      </div>
    </div>

    <!-- Sidebar overlay (mobile) -->
    <div v-if="sidebarOpen" class="admin-sidebar-overlay" @click="sidebarOpen=false"></div>

    <!-- Sidebar -->
    <aside :class="['admin-sidebar', { 'admin-sidebar--open': sidebarOpen }]">
      <!-- Title + Bell -->
      <div style="display:flex;align-items:center;justify-content:space-between;padding:0 12px;margin-bottom:10px;">
        <div style="font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1.5px;">Administration</div>
        <AdminNotifications class="desktop-only" />
      </div>

      <nav style="display:flex;flex-direction:column;gap:2px;">
        <RouterLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          @click="sidebarOpen = false"
          style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:12px;text-decoration:none;font-size:13px;font-weight:500;transition:all .2s;"
          :style="isActive(item.to)
            ? 'background:linear-gradient(135deg,rgba(99,102,241,0.2),rgba(168,85,247,0.15));border:1px solid rgba(129,140,248,0.25);color:#a5b4fc;'
            : 'color:#475569;border:1px solid transparent;'"
          @mouseover="(e) => { if (!isActive(item.to)) e.currentTarget.style.color='#94a3b8' }"
          @mouseout="(e) => { if (!isActive(item.to)) e.currentTarget.style.color='#475569' }"
        >
          <span style="font-size:16px;width:20px;text-align:center;">{{ item.icon }}</span>
          {{ item.label }}
        </RouterLink>
      </nav>

      <div style="margin-top:auto;border-top:1px solid rgba(255,255,255,0.06);padding-top:16px;">
        <RouterLink to="/" @click="sidebarOpen=false" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:12px;text-decoration:none;font-size:13px;font-weight:500;color:#334155;transition:color .2s;"
          onmouseover="this.style.color='#94a3b8'" onmouseout="this.style.color='#334155'">
          <span style="font-size:16px;width:20px;text-align:center;">↩</span>
          Retour au site
        </RouterLink>
      </div>
    </aside>

    <!-- Content -->
    <div class="admin-content">
      <RouterView />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import AdminNotifications from '@/components/AdminNotifications.vue'

const route = useRoute()
const sidebarOpen = ref(false)

const navItems = [
  { to: '/admin',            icon: '📊', label: 'Vue d\'ensemble' },
  { to: '/admin/products',   icon: '📦', label: 'Produits' },
  { to: '/admin/categories', icon: '🏷️', label: 'Catégories' },
  { to: '/admin/orders',     icon: '🛒', label: 'Commandes' },
  { to: '/admin/users',      icon: '👥', label: 'Utilisateurs' },
  { to: '/admin/recharges',  icon: '💳', label: 'Recharges' },
  { to: '/admin/tickets',    icon: '💬', label: 'Support' },
  { to: '/admin/promos',     icon: '🏷️', label: 'Codes promo' },
  { to: '/admin/gift-cards', icon: '🎁', label: 'Cartes cadeaux' },
  { to: '/admin/reviews',    icon: '⭐', label: 'Avis clients' },
  { to: '/admin/stats',      icon: '📈', label: 'Statistiques' },
]

function isActive(path) {
  if (path === '/admin') return route.path === '/admin'
  return route.path.startsWith(path)
}
</script>

<style scoped>
/* ── Desktop layout ── */
.admin-sidebar {
  width: 220px;
  flex-shrink: 0;
  border-right: 1px solid rgba(255,255,255,0.07);
  padding: 24px 12px;
  position: sticky;
  top: 64px;
  height: calc(100vh - 64px);
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}
.admin-content {
  flex: 1;
  min-width: 0;
  padding: 32px 36px 80px;
}
.admin-mobile-bar  { display: none; }
.admin-sidebar-overlay { display: none; }

/* ── Mobile ── */
@media (max-width: 768px) {
  .admin-mobile-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: fixed;
    top: 64px;
    left: 0; right: 0;
    background: rgba(6,9,20,0.98);
    border-bottom: 1px solid rgba(255,255,255,0.07);
    padding: 10px 16px;
    z-index: 100;
  }
  .admin-sidebar {
    position: fixed;
    top: 112px; /* 64px nav + 48px mobile bar */
    left: -240px;
    width: 220px;
    height: calc(100vh - 112px);
    z-index: 200;
    background: rgba(6,9,20,0.99);
    border-right: 1px solid rgba(255,255,255,0.1);
    transition: left .25s ease;
  }
  .admin-sidebar--open {
    left: 0;
  }
  .admin-sidebar-overlay {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 150;
  }
  .admin-content {
    padding: 80px 16px 80px; /* leave room for mobile bar */
    width: 100%;
  }
  .desktop-only { display: none; }
}
</style>
