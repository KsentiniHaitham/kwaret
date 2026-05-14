<template>
  <div style="max-width:760px;margin:0 auto;padding:40px 32px 80px;">
    <!-- Header -->
    <div style="margin-bottom:36px;">
      <h1 style="font-size:38px;font-weight:800;letter-spacing:-1px;">Mon <span class="gradient-text">portefeuille</span></h1>
    </div>

    <!-- Balance card -->
    <div style="background:linear-gradient(135deg,rgba(99,102,241,0.15),rgba(168,85,247,0.12));border:1px solid rgba(129,140,248,0.3);border-radius:24px;padding:32px;margin-bottom:28px;display:flex;align-items:center;justify-content:space-between;">
      <div>
        <div style="font-size:12px;font-weight:700;color:#818cf8;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;">Solde disponible</div>
        <div class="gradient-text-cyan" style="font-size:48px;font-weight:800;letter-spacing:-1px;">{{ balance.toFixed(2) }} <span style="font-size:24px;">TND</span></div>
        <div style="font-size:13px;color:#475569;margin-top:8px;">Utilisable immédiatement pour vos achats</div>
      </div>
      <button @click="showRecharge=true" class="btn-primary" style="border:none;cursor:pointer;padding:14px 24px;font-size:14px;flex-shrink:0;">
        + Recharger
      </button>
    </div>

    <!-- History -->
    <div class="card" style="padding:28px;">
      <div style="font-size:12px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;margin-bottom:20px;">Historique des recharges</div>

      <div v-if="loading" style="display:flex;flex-direction:column;gap:8px;">
        <div v-for="n in 3" :key="n" style="height:52px;border-radius:10px;background:rgba(255,255,255,0.03);animation:pulse 1.5s infinite;"></div>
      </div>

      <div v-else-if="history.length===0" style="text-align:center;padding:32px;color:#334155;font-size:14px;">
        Aucune recharge pour l'instant
      </div>

      <div v-else style="display:flex;flex-direction:column;gap:8px;">
        <div v-for="r in history" :key="r.id"
          style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-radius:14px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);">
          <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:22px;">{{ methodIcon(r.method) }}</span>
            <div>
              <div style="font-size:13px;font-weight:600;color:#e2e8f0;">{{ methodLabel(r.method) }}</div>
              <div style="font-size:11px;color:#334155;margin-top:2px;">{{ formatDate(r.createdAt) }}</div>
            </div>
          </div>
          <div style="text-align:right;">
            <div style="font-size:16px;font-weight:700;" :style="r.method==='cashback' ? 'color:#a855f7;' : 'color:#e2e8f0;'">
              +{{ parseFloat(r.amount).toFixed(2) }} TND
            </div>
            <span v-if="r.method==='refund' || r.method==='cashback'"
              style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:100px;background:rgba(52,211,153,0.12);border:1px solid rgba(52,211,153,0.25);color:#34d399;">
              Crédité
            </span>
            <span v-else :style="statusStyle(r.status)" style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:100px;">{{ statusLabel(r.status) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Recharge modal -->
    <RechargeModal v-if="showRecharge" @close="showRecharge=false" @success="onSuccess" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import RechargeModal from '@/components/RechargeModal.vue'

const balance     = ref(0)
const history     = ref([])
const loading     = ref(true)
const showRecharge = ref(false)

const methodLabel = m => ({ paypal:'PayPal', flouci:'Flouci', ooredoo:'Ooredoo', d17:'D17', poste:'Poste TN', crypto:'Crypto', refund:'Remboursement', cashback:'Cashback' }[m] || m)
const methodIcon  = m => ({ paypal:'💳', flouci:'📲', ooredoo:'📡', d17:'🏦', poste:'📮', crypto:'₿', refund:'↩️', cashback:'🎁' }[m] || '💰')

function statusLabel(s) { return { pending:'En attente', approved:'Approuvé', rejected:'Rejeté' }[s] || s }
function statusStyle(s) {
  return { pending:'background:rgba(234,179,8,0.12);border:1px solid rgba(234,179,8,0.25);color:#fbbf24;', approved:'background:rgba(52,211,153,0.12);border:1px solid rgba(52,211,153,0.25);color:#34d399;', rejected:'background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.25);color:#f87171;' }[s] || ''
}
function formatDate(d) { return new Date(d).toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }) }

async function load() {
  const [br, hr] = await Promise.all([
    api.get('/wallet').catch(() => ({ data: { balance: 0 } })),
    api.get('/wallet/history').catch(() => ({ data: [] })),
  ])
  balance.value = parseFloat(br.data.balance)
  history.value = hr.data
  loading.value = false
}

async function onSuccess() {
  showRecharge.value = false
  await load()
}

onMounted(load)
</script>

<style scoped>
@keyframes pulse { 0%,100%{opacity:.5} 50%{opacity:.2} }
</style>
