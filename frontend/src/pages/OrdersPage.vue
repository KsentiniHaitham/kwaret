<template>
  <div style="max-width:800px;margin:0 auto;padding:40px 32px 80px;">
    <div style="margin-bottom:36px;">
      <h1 style="font-size:40px;font-weight:800;letter-spacing:-1px;">Mes <span class="gradient-text">commandes</span></h1>
    </div>

    <!-- Loading -->
    <div v-if="loading" style="display:flex;flex-direction:column;gap:12px;">
      <div v-for="n in 3" :key="n" style="height:100px;border-radius:20px;background:rgba(255,255,255,0.03);animation:pulse 1.5s infinite;"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="orders.length === 0" style="text-align:center;padding:80px 0;">
      <div style="font-size:64px;margin-bottom:20px;">📦</div>
      <p style="color:#475569;font-size:16px;margin-bottom:28px;">Vous n'avez pas encore de commande</p>
      <RouterLink to="/shop" class="btn-primary" style="text-decoration:none;padding:12px 28px;">Commander maintenant →</RouterLink>
    </div>

    <!-- List -->
    <div v-else style="display:flex;flex-direction:column;gap:14px;">
      <div
        v-for="order in orders"
        :key="order.id"
        class="card"
        style="padding:24px;transition:border .2s;"
        onmouseover="this.style.borderColor='rgba(129,140,248,0.25)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.07)'"
      >
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
          <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:17px;font-weight:700;color:#e2e8f0;font-family:monospace;">#{{ String(order.id).padStart(4,'0') }}</span>
            <span style="color:#334155;font-size:13px;">{{ formatDate(order.createdAt) }}</span>
          </div>
          <span :style="statusStyle(order.status)" style="font-size:11px;font-weight:700;padding:4px 12px;border-radius:100px;">
            {{ statusLabel(order.status) }}
          </span>
        </div>

        <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:16px;">
          <div v-for="item in order.items" :key="item.id" style="display:flex;justify-content:space-between;font-size:13px;">
            <span style="color:#475569;">{{ item.product?.name }} × {{ item.quantity }}</span>
            <span style="color:#94a3b8;">{{ (parseFloat(item.unitPrice) * item.quantity).toFixed(2) }} TND</span>
          </div>
        </div>

        <div style="border-top:1px solid rgba(255,255,255,0.06);padding-top:14px;display:flex;justify-content:space-between;align-items:center;">
          <span style="color:#475569;font-size:13px;">Total commande</span>
          <span class="gradient-text-cyan" style="font-size:20px;font-weight:800;">{{ parseFloat(order.total).toFixed(2) }} TND</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'

const orders = ref([])
const loading = ref(true)

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function statusLabel(status) {
  return { pending: 'En attente', paid: 'Payée', delivered: 'Livrée', cancelled: 'Annulée' }[status] || status
}

function statusStyle(status) {
  const map = {
    pending:   'background:rgba(234,179,8,0.1);border:1px solid rgba(234,179,8,0.3);color:#fbbf24;',
    paid:      'background:rgba(99,102,241,0.1);border:1px solid rgba(99,102,241,0.3);color:#818cf8;',
    delivered: 'background:rgba(52,211,153,0.1);border:1px solid rgba(52,211,153,0.3);color:#34d399;',
    cancelled: 'background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#f87171;',
  }
  return map[status] || 'background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#94a3b8;'
}

onMounted(async () => {
  const res = await api.get('/orders').catch(() => ({ data: [] }))
  orders.value = res.data
  loading.value = false
})
</script>

<style scoped>
@keyframes pulse { 0%,100%{opacity:.5} 50%{opacity:.2} }
</style>
