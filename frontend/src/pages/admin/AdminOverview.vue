<template>
  <div>
    <div style="margin-bottom:32px;">
      <h1 style="font-size:30px;font-weight:800;letter-spacing:-.5px;">Vue d'ensemble <span class="gradient-text">Admin</span></h1>
      <p style="color:#475569;font-size:14px;margin-top:6px;">Bienvenue dans le panel d'administration Kwaret</p>
    </div>

    <!-- KPI cards -->
    <div class="kpi-grid" style="display:grid;gap:14px;margin-bottom:28px;">
      <div v-for="kpi in kpis" :key="kpi.label" class="card" style="padding:20px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
          <span style="font-size:22px;">{{ kpi.icon }}</span>
          <span :style="kpi.badge.style" style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:100px;">{{ kpi.badge.text }}</span>
        </div>
        <div style="font-size:26px;font-weight:800;color:#e2e8f0;margin-bottom:3px;">
          <span v-if="loading" style="display:inline-block;width:64px;height:22px;background:rgba(255,255,255,0.05);border-radius:6px;animation:pulse 1.5s infinite;"></span>
          <span v-else>{{ kpi.value }}</span>
        </div>
        <div style="font-size:12px;color:#475569;">{{ kpi.label }}</div>
      </div>
    </div>

    <!-- Status breakdown -->
    <div class="status-grid" style="display:grid;gap:14px;margin-bottom:28px;">
      <div class="card" style="padding:24px;">
        <div style="font-size:12px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;">Commandes par statut</div>
        <div v-if="loading" style="display:flex;flex-direction:column;gap:8px;">
          <div v-for="n in 4" :key="n" style="height:32px;border-radius:8px;background:rgba(255,255,255,0.03);animation:pulse 1.5s infinite;"></div>
        </div>
        <div v-else style="display:flex;flex-direction:column;gap:8px;">
          <div v-for="s in statusRows" :key="s.key" style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;border-radius:10px;" :style="s.bg">
            <span :style="s.color" style="font-size:13px;font-weight:600;">{{ s.label }}</span>
            <span :style="s.color" style="font-size:18px;font-weight:800;">{{ stats?.ordersByStatus?.[s.key] ?? 0 }}</span>
          </div>
        </div>
      </div>

      <div class="card" style="padding:24px;">
        <div style="font-size:12px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;">Stock critique</div>
        <div v-if="loading" style="display:flex;flex-direction:column;gap:8px;">
          <div v-for="n in 3" :key="n" style="height:32px;border-radius:8px;background:rgba(255,255,255,0.03);animation:pulse 1.5s infinite;"></div>
        </div>
        <div v-else-if="!stats?.lowStock?.length" style="color:#334155;font-size:13px;padding:8px 0;">
          ✓ Tous les stocks sont OK
        </div>
        <div v-else style="display:flex;flex-direction:column;gap:6px;">
          <div v-for="p in stats.lowStock" :key="p.id" style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;border-radius:10px;background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.15);">
            <span style="font-size:13px;color:#e2e8f0;font-weight:500;">{{ p.name }}</span>
            <span :style="p.stock === 0 ? 'color:#f87171;' : 'color:#fbbf24;'" style="font-size:12px;font-weight:700;">
              {{ p.stock === 0 ? 'Sold Out' : p.stock + ' restant(s)' }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick links -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;">
      <RouterLink v-for="link in quickLinks" :key="link.to" :to="link.to"
        class="card" style="padding:20px;text-decoration:none;display:flex;align-items:center;gap:14px;"
        onmouseover="this.style.borderColor='rgba(129,140,248,0.3)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.07)'">
        <span style="font-size:28px;">{{ link.icon }}</span>
        <div>
          <div style="font-size:14px;font-weight:600;color:#e2e8f0;">{{ link.label }}</div>
          <div style="font-size:12px;color:#334155;margin-top:2px;">{{ link.sub }}</div>
        </div>
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api'

const stats = ref(null)
const loading = ref(true)

const kpis = computed(() => [
  { icon: '📦', label: 'Commandes totales',  value: stats.value?.totalOrders ?? '—',  badge: { text: 'Toutes',   style: 'background:rgba(255,255,255,0.08);color:#94a3b8;' } },
  { icon: '💰', label: "Chiffre d'affaires", value: stats.value ? `${stats.value.totalRevenue.toFixed(2)} TND` : '—', badge: { text: 'Revenus', style: 'background:rgba(52,211,153,0.15);color:#34d399;' } },
  { icon: '👥', label: 'Clients inscrits',   value: stats.value?.totalUsers ?? '—',   badge: { text: 'Users',    style: 'background:rgba(99,102,241,0.15);color:#818cf8;' } },
  { icon: '🛍️', label: 'Produits actifs',    value: stats.value?.totalProducts ?? '—',badge: { text: 'Catalogue',style: 'background:rgba(168,85,247,0.15);color:#c084fc;' } },
])

const statusRows = [
  { key: 'pending',   label: 'En attente', bg: 'background:rgba(234,179,8,0.06);',   color: 'color:#fbbf24;' },
  { key: 'paid',      label: 'Payées',     bg: 'background:rgba(99,102,241,0.06);',  color: 'color:#818cf8;' },
  { key: 'delivered', label: 'Livrées',    bg: 'background:rgba(52,211,153,0.06);',  color: 'color:#34d399;' },
  { key: 'cancelled', label: 'Annulées',   bg: 'background:rgba(239,68,68,0.06);',   color: 'color:#f87171;' },
]

const quickLinks = [
  { to: '/admin/products',   icon: '📦', label: 'Gérer les produits',   sub: 'Ajouter, modifier, supprimer' },
  { to: '/admin/categories', icon: '🏷️', label: 'Gérer les catégories', sub: 'Organiser le catalogue' },
  { to: '/admin/orders',     icon: '🛒', label: 'Gérer les commandes',  sub: 'Mettre à jour les statuts' },
  { to: '/admin/reviews',    icon: '⭐', label: 'Avis clients',          sub: 'Modérer les évaluations' },
  { to: '/admin/recharges',  icon: '💳', label: 'Recharges',             sub: 'Valider les paiements' },
  { to: '/admin/tickets',    icon: '💬', label: 'Support',               sub: 'Répondre aux tickets' },
]

onMounted(async () => {
  const res = await api.get('/admin/stats').catch(() => ({ data: null }))
  stats.value = res.data
  loading.value = false
})
</script>

<style scoped>
@keyframes pulse { 0%,100%{opacity:.5} 50%{opacity:.2} }

.kpi-grid    { grid-template-columns: repeat(4, 1fr); }
.status-grid { grid-template-columns: 1fr 1fr; }

@media (max-width: 900px) {
  .kpi-grid    { grid-template-columns: repeat(2, 1fr); }
  .status-grid { grid-template-columns: 1fr; }
}
@media (max-width: 500px) {
  .kpi-grid    { grid-template-columns: 1fr 1fr; }
}
</style>
