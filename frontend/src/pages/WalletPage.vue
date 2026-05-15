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

    <!-- Gift Cards section -->
    <div class="card" style="padding:28px;margin-top:20px;">
      <div style="font-size:12px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;margin-bottom:20px;">🎁 Cartes cadeaux</div>

      <!-- Redeem a gift card -->
      <div style="margin-bottom:24px;">
        <div style="font-size:14px;font-weight:600;color:#94a3b8;margin-bottom:10px;">Utiliser un code cadeau</div>
        <div style="display:flex;gap:10px;align-items:center;">
          <input v-model="redeemCode" type="text" placeholder="KWARET-XXXX-XXXX"
            style="flex:1;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:10px 14px;color:#e2e8f0;font-size:14px;outline:none;transition:border .2s;text-transform:uppercase;font-family:monospace;"
            onfocus="this.style.borderColor='rgba(129,140,248,0.5)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'" />
          <button @click="redeemCard" :disabled="!redeemCode.trim() || redeeming"
            class="btn-primary" style="padding:10px 20px;font-size:13px;border:none;cursor:pointer;white-space:nowrap;">
            {{ redeeming ? '…' : 'Utiliser' }}
          </button>
        </div>
        <div v-if="redeemError" style="margin-top:8px;font-size:12px;color:#f87171;">{{ redeemError }}</div>
        <div v-if="redeemSuccess" style="margin-top:8px;font-size:12px;color:#34d399;font-weight:600;">{{ redeemSuccess }}</div>
      </div>

      <!-- Buy a gift card -->
      <div style="border-top:1px solid rgba(255,255,255,0.06);padding-top:20px;">
        <div style="font-size:14px;font-weight:600;color:#94a3b8;margin-bottom:14px;">Acheter une carte cadeau</div>
        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
          <button v-for="amt in [10, 20, 50, 100, 200]" :key="amt"
            @click="buyAmount = amt"
            :class="buyAmount === amt ? 'btn-primary' : ''"
            style="padding:10px 20px;border-radius:12px;font-size:14px;font-weight:700;cursor:pointer;transition:all .2s;"
            :style="buyAmount === amt ? 'border:none;' : 'background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);color:#94a3b8;'">
            {{ amt }} TND
          </button>
        </div>
        <div v-if="buyAmount" style="margin-bottom:12px;font-size:13px;color:#64748b;">
          Coût : <strong style="color:#e2e8f0;">{{ buyAmount }} TND</strong> déduits de votre portefeuille → vous recevrez un code cadeau à partager.
        </div>
        <div v-if="buyError" style="margin-bottom:8px;font-size:12px;color:#f87171;">{{ buyError }}</div>
        <div v-if="newCard" style="margin-bottom:12px;background:rgba(52,211,153,0.08);border:1px solid rgba(52,211,153,0.2);border-radius:12px;padding:14px 18px;">
          <div style="font-size:12px;color:#34d399;font-weight:700;margin-bottom:6px;">✅ Carte cadeau créée !</div>
          <div style="font-family:monospace;font-size:18px;font-weight:800;color:#e2e8f0;letter-spacing:2px;">{{ newCard.code }}</div>
          <div style="font-size:12px;color:#64748b;margin-top:4px;">Valeur : {{ newCard.initialValue }} TND</div>
        </div>
        <button @click="buyCard" :disabled="!buyAmount || buying"
          class="btn-primary" style="padding:10px 24px;font-size:13px;border:none;cursor:pointer;"
          :style="!buyAmount ? 'opacity:.4;cursor:not-allowed;' : ''">
          {{ buying ? 'Achat...' : `Acheter ${buyAmount ? buyAmount + ' TND' : ''}` }}
        </button>
      </div>
    </div>

    <!-- My gift cards list -->
    <div v-if="myCards.length > 0" class="card" style="padding:28px;margin-top:20px;">
      <div style="font-size:12px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;">Mes cartes cadeaux</div>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <div v-for="card in myCards" :key="card.id"
          style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-radius:12px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);flex-wrap:wrap;gap:8px;">
          <span style="font-family:monospace;font-size:14px;font-weight:700;color:#a5b4fc;">{{ card.code }}</span>
          <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:13px;color:#64748b;">{{ card.initialValue }} TND</span>
            <span v-if="card.isRedeemed" style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:100px;background:rgba(71,85,105,0.2);color:#475569;border:1px solid rgba(71,85,105,0.3);">Utilisée</span>
            <span v-else style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:100px;background:rgba(52,211,153,0.12);color:#34d399;border:1px solid rgba(52,211,153,0.25);">Disponible</span>
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

// Gift card state
const redeemCode    = ref('')
const redeeming     = ref(false)
const redeemError   = ref('')
const redeemSuccess = ref('')
const buyAmount     = ref(null)
const buying        = ref(false)
const buyError      = ref('')
const newCard       = ref(null)
const myCards       = ref([])

const methodLabel = m => ({ paypal:'PayPal', flouci:'Flouci', ooredoo:'Ooredoo', d17:'D17', poste:'Poste TN', crypto:'Crypto', refund:'Remboursement', cashback:'Cashback', gift_card:'Carte cadeau', gift_card_purchase:'Achat carte cadeau' }[m] || m)
const methodIcon  = m => ({ paypal:'💳', flouci:'📲', ooredoo:'📡', d17:'🏦', poste:'📮', crypto:'₿', refund:'↩️', cashback:'🎁', gift_card:'🎁', gift_card_purchase:'🎁' }[m] || '💰')

function statusLabel(s) { return { pending:'En attente', approved:'Approuvé', rejected:'Rejeté' }[s] || s }
function statusStyle(s) {
  return { pending:'background:rgba(234,179,8,0.12);border:1px solid rgba(234,179,8,0.25);color:#fbbf24;', approved:'background:rgba(52,211,153,0.12);border:1px solid rgba(52,211,153,0.25);color:#34d399;', rejected:'background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.25);color:#f87171;' }[s] || ''
}
function formatDate(d) { return new Date(d).toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }) }

async function load() {
  const [br, hr, gr] = await Promise.all([
    api.get('/wallet').catch(() => ({ data: { balance: 0 } })),
    api.get('/wallet/history').catch(() => ({ data: [] })),
    api.get('/gift-cards').catch(() => ({ data: [] })),
  ])
  balance.value = parseFloat(br.data.balance)
  history.value = hr.data
  myCards.value = gr.data
  loading.value = false
}

async function redeemCard() {
  redeemError.value   = ''
  redeemSuccess.value = ''
  redeeming.value     = true
  try {
    const r = await api.post('/gift-cards/redeem', { code: redeemCode.value })
    redeemSuccess.value = r.data.message
    redeemCode.value    = ''
    balance.value = r.data.newBalance
    await load()
  } catch (e) {
    redeemError.value = e.response?.data?.message || 'Code invalide'
  } finally {
    redeeming.value = false
  }
}

async function buyCard() {
  buyError.value = ''
  newCard.value  = null
  buying.value   = true
  try {
    const r = await api.post('/gift-cards/buy', { amount: buyAmount.value })
    newCard.value  = r.data
    buyAmount.value = null
    await load()
  } catch (e) {
    buyError.value = e.response?.data?.message || 'Erreur lors de l\'achat'
  } finally {
    buying.value = false
  }
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
