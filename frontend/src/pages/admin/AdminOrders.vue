<template>
  <div>
    <div style="margin-bottom:28px;">
      <h1 style="font-size:26px;font-weight:800;letter-spacing:-.5px;">Commandes <span class="gradient-text">({{ orders.length }})</span></h1>
      <p style="color:#475569;font-size:13px;margin-top:4px;">Suivi et gestion des commandes clients</p>
    </div>

    <!-- Tabs -->
    <div style="display:flex;border-bottom:1px solid rgba(255,255,255,0.07);margin-bottom:20px;overflow-x:auto;gap:0;">
      <button v-for="tab in tabs" :key="tab.value" @click="activeTab=tab.value; loadOrders()"
        :style="activeTab===tab.value ? 'border-bottom:2px solid #818cf8;color:#a5b4fc;' : 'border-bottom:2px solid transparent;color:#475569;'"
        style="display:flex;align-items:center;gap:7px;padding:12px 18px;font-size:13px;font-weight:600;white-space:nowrap;background:none;border-left:none;border-right:none;border-top:none;cursor:pointer;transition:color .2s;">
        {{ tab.label }}
        <span :style="tab.countStyle" style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:100px;">
          {{ stats?.ordersByStatus?.[tab.value] ?? (tab.value === 'all' ? orders.length : '') }}
        </span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" style="display:flex;flex-direction:column;gap:8px;">
      <div v-for="n in 5" :key="n" style="height:60px;border-radius:12px;background:rgba(255,255,255,0.03);animation:pulse 1.5s infinite;"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="orders.length===0" style="text-align:center;padding:60px;color:#334155;">
      <div style="font-size:40px;margin-bottom:12px;">📭</div>
      <p>Aucune commande dans cette catégorie</p>
    </div>

    <!-- Table -->
    <div v-else style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:20px;overflow:hidden;">
      <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
          <thead>
            <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
              <th style="padding:12px 16px;text-align:left;font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;">#</th>
              <th style="padding:12px 16px;text-align:left;font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;">Client</th>
              <th style="padding:12px 16px;text-align:left;font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;">Produits</th>
              <th style="padding:12px 16px;text-align:right;font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;">Total</th>
              <th style="padding:12px 16px;text-align:center;font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;">Date</th>
              <th style="padding:12px 16px;text-align:center;font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;">Statut</th>
              <th style="padding:12px 16px;text-align:center;font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in orders" :key="order.id"
              style="border-bottom:1px solid rgba(255,255,255,0.04);transition:background .15s;"
              onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
              <td style="padding:14px 16px;">
                <span style="font-family:monospace;font-weight:700;color:#e2e8f0;">#{{ String(order.id).padStart(4,'0') }}</span>
              </td>
              <td style="padding:14px 16px;">
                <div style="font-weight:600;color:#e2e8f0;">{{ order.user?.firstName }} {{ order.user?.lastName }}</div>
                <div style="font-size:11px;color:#334155;">{{ order.user?.email }}</div>
              </td>
              <td style="padding:14px 16px;max-width:220px;">
                <div style="color:#64748b;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  {{ order.items.map(i => i.product?.name).join(', ') }}
                </div>
                <div style="color:#334155;font-size:11px;margin-top:1px;">{{ order.items.length }} article(s)</div>
              </td>
              <td style="padding:14px 16px;text-align:right;">
                <span class="gradient-text-cyan" style="font-weight:800;">{{ parseFloat(order.total).toFixed(2) }} TND</span>
              </td>
              <td style="padding:14px 16px;text-align:center;color:#475569;white-space:nowrap;">{{ formatDate(order.createdAt) }}</td>
              <td style="padding:14px 16px;text-align:center;">
                <span :style="statusStyle(order.status)" style="font-size:10px;font-weight:700;padding:3px 10px;border-radius:100px;">{{ statusLabel(order.status) }}</span>
              </td>
              <td style="padding:14px 16px;text-align:center;">
                <select :value="order.status" @change="updateStatus(order, $event.target.value)"
                  style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#94a3b8;font-size:11px;border-radius:9px;padding:6px 10px;cursor:pointer;outline:none;">
                  <option value="pending">En attente</option>
                  <option value="paid">Payée</option>
                  <option value="delivered">Livrée</option>
                  <option value="cancelled">Annulée</option>
                </select>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Toast -->
    <Transition name="slide">
      <div v-if="toast" :style="toastError ? 'background:linear-gradient(135deg,#ef4444,#dc2626);' : 'background:linear-gradient(135deg,#6366f1,#a855f7);'"
        style="position:fixed;bottom:24px;right:24px;color:#fff;padding:14px 22px;border-radius:14px;font-weight:600;font-size:13px;box-shadow:0 8px 32px rgba(0,0,0,0.3);z-index:300;">
        {{ toast }}
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'

const orders     = ref([])
const stats      = ref(null)
const loading    = ref(true)
const activeTab  = ref('all')
const toast      = ref('')
const toastError = ref(false)

const tabs = [
  { value:'all',       label:'Toutes',     countStyle:'background:rgba(255,255,255,0.08);color:#94a3b8;' },
  { value:'pending',   label:'En attente', countStyle:'background:rgba(234,179,8,0.15);color:#fbbf24;' },
  { value:'paid',      label:'Payées',     countStyle:'background:rgba(99,102,241,0.15);color:#818cf8;' },
  { value:'delivered', label:'Livrées',    countStyle:'background:rgba(52,211,153,0.15);color:#34d399;' },
  { value:'cancelled', label:'Annulées',   countStyle:'background:rgba(239,68,68,0.15);color:#f87171;' },
]

function formatDate(d) {
  return new Date(d).toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric' })
}
function statusLabel(s) {
  return { pending:'En attente', paid:'Payée', delivered:'Livrée', cancelled:'Annulée' }[s] || s
}
function statusStyle(s) {
  return { pending:'background:rgba(234,179,8,0.1);border:1px solid rgba(234,179,8,0.25);color:#fbbf24;', paid:'background:rgba(99,102,241,0.1);border:1px solid rgba(99,102,241,0.25);color:#818cf8;', delivered:'background:rgba(52,211,153,0.1);border:1px solid rgba(52,211,153,0.25);color:#34d399;', cancelled:'background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#f87171;' }[s] || 'background:rgba(255,255,255,0.05);color:#94a3b8;'
}

async function loadOrders() {
  loading.value = true
  const url = activeTab.value === 'all' ? '/admin/orders' : `/admin/orders?status=${activeTab.value}`
  const res = await api.get(url).catch(() => ({ data: [] }))
  orders.value = res.data
  loading.value = false
}

async function updateStatus(order, newStatus) {
  if (order.status === newStatus) return
  try {
    await api.patch(`/admin/orders/${order.id}/status`, { status: newStatus })
    order.status = newStatus
    const r = await api.get('/admin/stats')
    stats.value = r.data
    showToast(`Commande #${String(order.id).padStart(4,'0')} → ${statusLabel(newStatus)}`)
  } catch { showToast('Erreur mise à jour', true) }
}

function showToast(msg, isError = false) {
  toast.value = msg; toastError.value = isError
  setTimeout(() => toast.value = '', 3000)
}

onMounted(async () => {
  const [r] = await Promise.all([
    api.get('/admin/stats').catch(() => ({ data: null })),
    loadOrders(),
  ])
  stats.value = r.data
})
</script>

<style scoped>
.slide-enter-active, .slide-leave-active { transition:all .3s ease; }
.slide-enter-from, .slide-leave-to { opacity:0; transform:translateY(20px); }
@keyframes pulse { 0%,100%{opacity:.5} 50%{opacity:.2} }
</style>
