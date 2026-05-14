<template>
  <div>
    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;">
      <div>
        <h1 style="font-size:26px;font-weight:800;letter-spacing:-0.5px;margin:0;">💳 Recharges portefeuille</h1>
        <p style="color:#475569;font-size:13px;margin-top:4px;">Approuver ou rejeter les demandes de recharge</p>
      </div>
      <button @click="load" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:8px 16px;color:#94a3b8;cursor:pointer;font-size:13px;">↻ Actualiser</button>
    </div>

    <!-- Tabs -->
    <div style="display:flex;gap:4px;margin-bottom:20px;background:rgba(255,255,255,0.03);border-radius:12px;padding:4px;width:fit-content;">
      <button v-for="t in tabs" :key="t.key" @click="activeTab=t.key"
        style="padding:7px 16px;border-radius:9px;border:none;cursor:pointer;font-size:13px;font-weight:600;transition:all .2s;"
        :style="activeTab===t.key ? 'background:rgba(99,102,241,0.2);color:#a5b4fc;' : 'background:transparent;color:#475569;'">
        {{ t.label }}
        <span v-if="count(t.key) > 0" style="margin-left:6px;background:rgba(99,102,241,0.3);color:#a5b4fc;font-size:10px;padding:1px 6px;border-radius:100px;">{{ count(t.key) }}</span>
      </button>
    </div>

    <!-- List -->
    <div v-if="loading" style="display:flex;flex-direction:column;gap:8px;">
      <div v-for="n in 4" :key="n" style="height:80px;border-radius:14px;background:rgba(255,255,255,0.03);animation:pulse 1.5s infinite;"></div>
    </div>

    <div v-else-if="filtered.length===0" style="text-align:center;padding:60px;color:#334155;font-size:14px;">
      Aucune demande {{ activeTab !== 'all' ? 'avec ce statut' : '' }}
    </div>

    <div v-else style="display:flex;flex-direction:column;gap:8px;">
      <div v-for="r in filtered" :key="r.id"
        style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:18px 20px;">

        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;">
          <!-- Left info -->
          <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:0;">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,rgba(99,102,241,0.3),rgba(168,85,247,0.2));display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
              {{ methodIcon(r.method) }}
            </div>
            <div style="min-width:0;">
              <div style="font-size:15px;font-weight:700;color:#e2e8f0;">{{ parseFloat(r.amount).toFixed(2) }} TND</div>
              <div style="font-size:12px;color:#475569;margin-top:2px;">
                {{ methodLabel(r.method) }} · {{ formatDate(r.createdAt) }}
              </div>
              <div v-if="r.user" style="font-size:12px;color:#6366f1;margin-top:3px;">
                👤 {{ r.user.firstName }} {{ r.user.lastName }} ({{ r.user.email }})
              </div>
              <div v-if="r.senderInfo" style="font-size:11px;color:#475569;margin-top:2px;">
                ↗ Expéditeur : {{ r.senderInfo }}
              </div>
            </div>
          </div>

          <!-- Status + actions -->
          <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
            <span :style="statusStyle(r.status)" style="font-size:11px;font-weight:700;padding:4px 10px;border-radius:100px;white-space:nowrap;">
              {{ statusLabel(r.status) }}
            </span>

            <template v-if="r.status==='pending'">
              <button @click="approve(r)" :disabled="r._loading"
                style="padding:7px 14px;border-radius:10px;border:1px solid rgba(52,211,153,0.3);background:rgba(52,211,153,0.1);color:#34d399;font-size:12px;font-weight:700;cursor:pointer;transition:all .2s;">
                {{ r._loading==='approve' ? '…' : '✓ Approuver' }}
              </button>
              <button @click="openReject(r)" :disabled="r._loading"
                style="padding:7px 14px;border-radius:10px;border:1px solid rgba(239,68,68,0.3);background:rgba(239,68,68,0.08);color:#f87171;font-size:12px;font-weight:700;cursor:pointer;transition:all .2s;">
                ✕ Rejeter
              </button>
            </template>
          </div>
        </div>

        <!-- Proof image -->
        <div v-if="r.proofPath" style="margin-top:12px;">
          <a :href="proofUrl(r.proofPath)" target="_blank"
            style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#818cf8;text-decoration:none;background:rgba(99,102,241,0.1);padding:5px 12px;border-radius:8px;border:1px solid rgba(99,102,241,0.2);">
            📎 Voir le justificatif
          </a>
        </div>

        <!-- Admin note if rejected -->
        <div v-if="r.adminNote" style="margin-top:10px;font-size:12px;color:#f87171;background:rgba(239,68,68,0.08);padding:8px 12px;border-radius:8px;border:1px solid rgba(239,68,68,0.15);">
          Note admin : {{ r.adminNote }}
        </div>
      </div>
    </div>

    <!-- Reject modal -->
    <div v-if="rejectTarget" @click.self="rejectTarget=null"
      style="position:fixed;inset:0;z-index:200;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;padding:16px;">
      <div style="background:rgba(10,14,30,0.98);border:1px solid rgba(239,68,68,0.3);border-radius:20px;padding:28px;width:100%;max-width:400px;">
        <div style="font-size:16px;font-weight:700;color:#f87171;margin-bottom:16px;">Rejeter la demande</div>
        <textarea v-model="adminNote" placeholder="Raison du rejet (optionnel)"
          style="width:100%;padding:12px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:12px;color:#e2e8f0;font-size:13px;resize:vertical;min-height:80px;box-sizing:border-box;outline:none;font-family:inherit;"></textarea>
        <div style="display:flex;gap:10px;margin-top:14px;">
          <button @click="rejectTarget=null" style="flex:1;padding:11px;border-radius:10px;border:1px solid rgba(255,255,255,0.1);background:transparent;color:#475569;cursor:pointer;font-size:13px;">Annuler</button>
          <button @click="reject" style="flex:1;padding:11px;border-radius:10px;border:none;background:rgba(239,68,68,0.2);color:#f87171;cursor:pointer;font-size:13px;font-weight:700;">Confirmer le rejet</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api'

const recharges   = ref([])
const loading     = ref(true)
const activeTab   = ref('pending')
const rejectTarget = ref(null)
const adminNote   = ref('')

const tabs = [
  { key: 'pending',  label: 'En attente' },
  { key: 'approved', label: 'Approuvées' },
  { key: 'rejected', label: 'Rejetées' },
  { key: 'all',      label: 'Toutes' },
]

const methodLabel = m => ({ paypal:'PayPal', flouci:'Flouci', ooredoo:'Ooredoo', d17:'D17', poste:'Poste TN', crypto:'Crypto' }[m] || m)
const methodIcon  = m => ({ paypal:'💳', flouci:'📲', ooredoo:'📡', d17:'🏦', poste:'📮', crypto:'₿' }[m] || '💰')

function statusLabel(s) { return { pending:'En attente', approved:'Approuvé', rejected:'Rejeté' }[s] || s }
function statusStyle(s) {
  return { pending:'background:rgba(234,179,8,0.12);border:1px solid rgba(234,179,8,0.25);color:#fbbf24;', approved:'background:rgba(52,211,153,0.12);border:1px solid rgba(52,211,153,0.25);color:#34d399;', rejected:'background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.25);color:#f87171;' }[s] || ''
}
function formatDate(d) { return new Date(d).toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }) }
function proofUrl(path) { return `http://localhost:8000${path}` }

const filtered = computed(() => {
  if (activeTab.value === 'all') return recharges.value
  return recharges.value.filter(r => r.status === activeTab.value)
})

function count(tab) {
  if (tab === 'all') return recharges.value.length
  return recharges.value.filter(r => r.status === tab).length
}

async function load() {
  loading.value = true
  try {
    const r = await api.get('/admin/recharges')
    recharges.value = r.data.map(x => ({ ...x, _loading: false }))
  } catch {}
  loading.value = false
}

async function approve(r) {
  r._loading = 'approve'
  try {
    await api.patch(`/admin/recharges/${r.id}/approve`)
    r.status = 'approved'
  } catch {}
  r._loading = false
}

function openReject(r) {
  rejectTarget.value = r
  adminNote.value = ''
}

async function reject() {
  if (!rejectTarget.value) return
  const r = rejectTarget.value
  r._loading = 'reject'
  try {
    await api.patch(`/admin/recharges/${r.id}/reject`, { note: adminNote.value })
    r.status = 'rejected'
    r.adminNote = adminNote.value
  } catch {}
  r._loading = false
  rejectTarget.value = null
}

onMounted(load)
</script>

<style scoped>
@keyframes pulse { 0%,100%{opacity:.5} 50%{opacity:.2} }
</style>
