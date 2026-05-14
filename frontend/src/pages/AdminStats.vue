<template>
  <div style="max-width:1200px;margin:0 auto;padding:40px 32px 80px;">
    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:40px;">
      <div>
        <div style="display:inline-block;background:rgba(129,140,248,0.1);border:1px solid rgba(129,140,248,0.2);color:#818cf8;font-size:11px;font-weight:700;padding:5px 14px;border-radius:100px;letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;">Monitoring</div>
        <h1 style="font-size:36px;font-weight:800;letter-spacing:-1px;">Statistiques <span class="gradient-text">& Analytics</span></h1>
      </div>
      <RouterLink to="/admin" class="btn-ghost" style="text-decoration:none;padding:10px 20px;font-size:13px;">
        ← Dashboard
      </RouterLink>
    </div>

    <!-- KPI cards -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:36px;">
      <div v-for="kpi in kpis" :key="kpi.label" class="card" style="padding:22px;">
        <div style="font-size:28px;margin-bottom:10px;">{{ kpi.icon }}</div>
        <div style="font-size:26px;font-weight:800;margin-bottom:4px;" :class="kpi.colorClass">
          <span v-if="loading" style="display:inline-block;width:70px;height:24px;background:rgba(255,255,255,0.05);border-radius:6px;animation:pulse 1.5s infinite;"></span>
          <span v-else>{{ kpi.value }}</span>
        </div>
        <div style="font-size:12px;color:#475569;">{{ kpi.label }}</div>
      </div>
    </div>

    <!-- Charts row 1 -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px;">
      <!-- Revenue by day -->
      <div class="card" style="padding:28px;">
        <div style="font-size:13px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:1px;margin-bottom:20px;">Revenus — 7 derniers jours</div>
        <div style="position:relative;height:220px;">
          <canvas ref="revenueChart"></canvas>
        </div>
      </div>

      <!-- Orders by status (donut) -->
      <div class="card" style="padding:28px;">
        <div style="font-size:13px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:1px;margin-bottom:20px;">Statuts des commandes</div>
        <div style="position:relative;height:200px;display:flex;align-items:center;justify-content:center;">
          <canvas ref="statusChart"></canvas>
        </div>
        <div style="margin-top:16px;display:flex;flex-direction:column;gap:7px;">
          <div v-for="item in statusLegend" :key="item.label" style="display:flex;align-items:center;justify-content:space-between;font-size:12px;">
            <div style="display:flex;align-items:center;gap:6px;">
              <div :style="`background:${item.color};`" style="width:8px;height:8px;border-radius:50%;"></div>
              <span style="color:#64748b;">{{ item.label }}</span>
            </div>
            <span style="color:#e2e8f0;font-weight:600;">{{ item.count }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts row 2 -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
      <!-- Revenue by month -->
      <div class="card" style="padding:28px;">
        <div style="font-size:13px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:1px;margin-bottom:20px;">Revenus mensuels — 6 mois</div>
        <div style="position:relative;height:220px;">
          <canvas ref="monthChart"></canvas>
        </div>
      </div>

      <!-- Top products -->
      <div class="card" style="padding:28px;">
        <div style="font-size:13px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:1px;margin-bottom:20px;">Top produits vendus</div>
        <div style="position:relative;height:220px;">
          <canvas ref="topChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Low stock table -->
    <div class="card" style="padding:28px;">
      <div style="font-size:13px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:1px;margin-bottom:20px;">
        ⚠️ Stock faible — produits à réapprovisionner
      </div>
      <div v-if="loading" style="display:flex;flex-direction:column;gap:8px;">
        <div v-for="n in 3" :key="n" style="height:44px;border-radius:10px;background:rgba(255,255,255,0.03);animation:pulse 1.5s infinite;"></div>
      </div>
      <div v-else-if="!stats?.lowStock?.length" style="color:#334155;font-size:13px;padding:12px 0;">
        ✓ Tous les stocks sont suffisants
      </div>
      <div v-else style="display:flex;flex-direction:column;gap:8px;">
        <div
          v-for="p in stats.lowStock"
          :key="p.id"
          style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-radius:12px;background:rgba(239,68,68,0.05);border:1px solid rgba(239,68,68,0.15);"
        >
          <span style="font-size:13px;font-weight:600;color:#e2e8f0;">{{ p.name }}</span>
          <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:11px;color:#475569;">Stock restant</span>
            <span :style="p.stock === 0 ? 'background:rgba(239,68,68,0.2);color:#f87171;' : 'background:rgba(234,179,8,0.15);color:#fbbf24;'"
              style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:100px;">
              {{ p.stock === 0 ? 'Sold Out' : `${p.stock} restant(s)` }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Chart, registerables } from 'chart.js'
import api from '@/api'

Chart.register(...registerables)

const stats = ref(null)
const loading = ref(true)

const revenueChart = ref(null)
const statusChart  = ref(null)
const monthChart   = ref(null)
const topChart     = ref(null)

const kpis = computed(() => [
  { icon: '💰', label: "Chiffre d'affaires total",  value: stats.value ? `${stats.value.totalRevenue.toFixed(2)} TND` : '—', colorClass: 'gradient-text-cyan' },
  { icon: '📦', label: 'Commandes totales',          value: stats.value?.totalOrders ?? '—',   colorClass: 'gradient-text' },
  { icon: '👥', label: 'Clients inscrits',           value: stats.value?.totalUsers ?? '—',    colorClass: 'gradient-text' },
  { icon: '🛍️', label: 'Produits catalogués',        value: stats.value?.totalProducts ?? '—', colorClass: 'gradient-text-cyan' },
])

const statusLegend = computed(() => {
  if (!stats.value) return []
  const s = stats.value.ordersByStatus || {}
  return [
    { label: 'En attente', color: '#fbbf24', count: s.pending   || 0 },
    { label: 'Payées',     color: '#818cf8', count: s.paid      || 0 },
    { label: 'Livrées',    color: '#34d399', count: s.delivered || 0 },
    { label: 'Annulées',   color: '#f87171', count: s.cancelled || 0 },
  ]
})

const chartDefaults = {
  gridColor: 'rgba(255,255,255,0.05)',
  tickColor: '#334155',
  font: { family: 'system-ui', size: 11 },
}

function buildRevenueChart(data) {
  new Chart(revenueChart.value, {
    type: 'line',
    data: {
      labels: data.map(d => d.label),
      datasets: [{
        label: 'Revenus (TND)',
        data: data.map(d => parseFloat(d.value)),
        borderColor: '#818cf8',
        backgroundColor: 'rgba(129,140,248,0.08)',
        borderWidth: 2,
        pointBackgroundColor: '#818cf8',
        pointRadius: 4,
        tension: 0.4,
        fill: true,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: chartDefaults.gridColor }, ticks: { color: chartDefaults.tickColor, font: chartDefaults.font } },
        y: { grid: { color: chartDefaults.gridColor }, ticks: { color: chartDefaults.tickColor, font: chartDefaults.font } },
      },
    }
  })
}

function buildStatusChart(data) {
  const s = data || {}
  new Chart(statusChart.value, {
    type: 'doughnut',
    data: {
      labels: ['En attente', 'Payées', 'Livrées', 'Annulées'],
      datasets: [{
        data: [s.pending || 0, s.paid || 0, s.delivered || 0, s.cancelled || 0],
        backgroundColor: ['rgba(251,191,36,0.8)', 'rgba(129,140,248,0.8)', 'rgba(52,211,153,0.8)', 'rgba(248,113,113,0.8)'],
        borderColor: ['#fbbf24', '#818cf8', '#34d399', '#f87171'],
        borderWidth: 1,
        hoverOffset: 8,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      cutout: '68%',
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } },
      },
    }
  })
}

function buildMonthChart(data) {
  new Chart(monthChart.value, {
    type: 'bar',
    data: {
      labels: data.map(d => d.label),
      datasets: [{
        label: 'Revenus (TND)',
        data: data.map(d => parseFloat(d.value)),
        backgroundColor: 'rgba(168,85,247,0.5)',
        borderColor: '#a855f7',
        borderWidth: 1,
        borderRadius: 6,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: chartDefaults.gridColor }, ticks: { color: chartDefaults.tickColor, font: chartDefaults.font } },
        y: { grid: { color: chartDefaults.gridColor }, ticks: { color: chartDefaults.tickColor, font: chartDefaults.font } },
      },
    }
  })
}

function buildTopChart(data) {
  new Chart(topChart.value, {
    type: 'bar',
    data: {
      labels: data.map(d => d.name.length > 18 ? d.name.slice(0,18) + '…' : d.name),
      datasets: [{
        label: 'Vendus',
        data: data.map(d => parseInt(d.totalQty)),
        backgroundColor: 'rgba(99,102,241,0.6)',
        borderColor: '#6366f1',
        borderWidth: 1,
        borderRadius: 6,
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: chartDefaults.gridColor }, ticks: { color: chartDefaults.tickColor, font: chartDefaults.font } },
        y: { grid: { display: false }, ticks: { color: chartDefaults.tickColor, font: chartDefaults.font } },
      },
    }
  })
}

onMounted(async () => {
  const res = await api.get('/admin/stats').catch(() => ({ data: null }))
  stats.value = res.data
  loading.value = false

  if (!stats.value) return

  buildRevenueChart(stats.value.revenueByDay || [])
  buildStatusChart(stats.value.ordersByStatus || {})
  buildMonthChart(stats.value.revenueByMonth || [])
  buildTopChart(stats.value.topProducts || [])
})
</script>

<style scoped>
@keyframes pulse { 0%,100%{opacity:.5} 50%{opacity:.2} }
</style>
