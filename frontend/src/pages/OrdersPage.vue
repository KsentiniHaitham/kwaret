<template>
  <div style="max-width:800px;margin:0 auto;padding:40px 20px 80px;">
    <div style="margin-bottom:36px;">
      <h1 style="font-size:36px;font-weight:800;letter-spacing:-1px;">{{ $t('orders.title') }} <span class="gradient-text">{{ $t('orders.title2') }}</span></h1>
    </div>

    <!-- Loading -->
    <div v-if="loading" style="display:flex;flex-direction:column;gap:12px;">
      <div v-for="n in 3" :key="n" style="height:100px;border-radius:20px;background:rgba(255,255,255,0.03);animation:pulse 1.5s infinite;"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="orders.length === 0" style="text-align:center;padding:80px 0;">
      <div style="font-size:64px;margin-bottom:20px;">📦</div>
      <p style="color:#475569;font-size:16px;margin-bottom:28px;">{{ $t('orders.empty') }}</p>
      <RouterLink to="/shop" class="btn-primary" style="text-decoration:none;padding:12px 28px;">{{ $t('orders.empty.btn') }}</RouterLink>
    </div>

    <!-- List -->
    <div v-else style="display:flex;flex-direction:column;gap:14px;">
      <div v-for="order in orders" :key="order.id" class="card" style="padding:20px;transition:border .2s;">
        <!-- Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
          <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <span style="font-size:17px;font-weight:700;color:#e2e8f0;font-family:monospace;">#{{ String(order.id).padStart(4,'0') }}</span>
            <span style="color:#334155;font-size:13px;">{{ formatDate(order.createdAt) }}</span>
          </div>
          <span :style="statusStyle(order.status)" style="font-size:11px;font-weight:700;padding:4px 12px;border-radius:100px;">
            {{ statusLabel(order.status) }}
          </span>
        </div>

        <!-- Items -->
        <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:16px;">
          <div v-for="item in order.items" :key="item.id" style="display:flex;justify-content:space-between;font-size:13px;flex-wrap:wrap;gap:4px;">
            <span style="color:#475569;">{{ item.product?.name }} × {{ item.quantity }}</span>
            <span style="color:#94a3b8;">{{ (parseFloat(item.unitPrice) * item.quantity).toFixed(2) }} TND</span>
          </div>
        </div>

        <!-- Total -->
        <div style="border-top:1px solid rgba(255,255,255,0.06);padding-top:14px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
          <span style="color:#475569;font-size:13px;">{{ $t('orders.total') }}</span>
          <span class="gradient-text-cyan" style="font-size:20px;font-weight:800;">{{ parseFloat(order.total).toFixed(2) }} TND</span>
        </div>

        <!-- Review section — only for delivered orders -->
        <div v-if="order.status === 'delivered'" style="margin-top:16px;border-top:1px solid rgba(255,255,255,0.06);padding-top:16px;">
          <!-- Already reviewed -->
          <div v-if="reviews[order.id]" style="display:flex;align-items:center;gap:8px;">
            <div style="display:flex;gap:3px;">
              <span v-for="i in 5" :key="i" style="font-size:16px;">{{ i <= reviews[order.id].rating ? '⭐' : '☆' }}</span>
            </div>
            <span style="color:#475569;font-size:13px;">{{ $t('orders.review.done') }}</span>
          </div>
          <!-- Review form -->
          <div v-else>
            <div v-if="!showReview[order.id]">
              <button @click="showReview[order.id] = true" style="background:rgba(52,211,153,0.1);border:1px solid rgba(52,211,153,0.3);color:#34d399;padding:8px 16px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">
                {{ $t('orders.review.btn') }}
              </button>
            </div>
            <div v-else>
              <p style="color:#94a3b8;font-size:13px;margin-bottom:10px;">{{ $t('orders.review.rate') }}</p>
              <!-- Stars -->
              <div style="display:flex;gap:6px;margin-bottom:12px;">
                <button
                  v-for="i in 5" :key="i"
                  @click="setRating(order.id, i)"
                  style="background:none;border:none;cursor:pointer;font-size:24px;padding:0;line-height:1;transition:transform .1s;"
                  :style="i <= (pendingRating[order.id] || 0) ? 'filter:none;' : 'filter:grayscale(1);opacity:.4;'"
                  onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'"
                >⭐</button>
              </div>
              <!-- Comment -->
              <textarea
                v-model="pendingComment[order.id]"
                :placeholder="$t('orders.review.ph')"
                style="width:100%;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:10px;padding:10px 14px;color:#e2e8f0;font-size:13px;resize:vertical;min-height:70px;outline:none;box-sizing:border-box;font-family:inherit;"
              ></textarea>
              <div style="display:flex;gap:8px;margin-top:10px;">
                <button
                  @click="submitReview(order.id)"
                  :disabled="!pendingRating[order.id] || submitting[order.id]"
                  class="btn-primary"
                  style="padding:8px 20px;font-size:13px;border:none;"
                >{{ submitting[order.id] ? $t('orders.review.sending') : $t('orders.review.send') }}</button>
                <button @click="showReview[order.id] = false" style="background:none;border:1px solid rgba(255,255,255,0.1);color:#475569;padding:8px 16px;border-radius:10px;font-size:13px;cursor:pointer;">{{ $t('orders.review.cancel') }}</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import api from '@/api'

const orders        = ref([])
const loading       = ref(true)
const reviews       = reactive({})    // orderId → { rating, comment }
const showReview    = reactive({})
const pendingRating  = reactive({})
const pendingComment = reactive({})
const submitting     = reactive({})

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}
function statusLabel(s) {
  return { pending: 'En attente', paid: 'Payée', delivered: 'Livrée', cancelled: 'Annulée' }[s] || s
}
function statusStyle(s) {
  return {
    pending:   'background:rgba(234,179,8,0.1);border:1px solid rgba(234,179,8,0.3);color:#fbbf24;',
    paid:      'background:rgba(99,102,241,0.1);border:1px solid rgba(99,102,241,0.3);color:#818cf8;',
    delivered: 'background:rgba(52,211,153,0.1);border:1px solid rgba(52,211,153,0.3);color:#34d399;',
    cancelled: 'background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#f87171;',
  }[s] || 'background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#94a3b8;'
}

function setRating(orderId, val) {
  pendingRating[orderId] = val
}

async function submitReview(orderId) {
  if (!pendingRating[orderId]) return
  submitting[orderId] = true
  try {
    await api.post(`/orders/${orderId}/review`, {
      rating:  pendingRating[orderId],
      comment: pendingComment[orderId] || '',
    })
    reviews[orderId] = { rating: pendingRating[orderId], comment: pendingComment[orderId] }
    showReview[orderId] = false
  } catch (e) {
    alert(e.response?.data?.message || 'Erreur lors de l\'envoi')
  } finally {
    submitting[orderId] = false
  }
}

onMounted(async () => {
  const res = await api.get('/orders').catch(() => ({ data: [] }))
  orders.value = res.data
  loading.value = false

  // Charger les avis existants pour les commandes livrées
  for (const order of orders.value) {
    if (order.status === 'delivered') {
      api.get(`/orders/${order.id}/review`)
        .then(r => { if (r.data) reviews[order.id] = r.data })
        .catch(() => {})
    }
  }
})
</script>

<style scoped>
@keyframes pulse { 0%,100%{opacity:.5} 50%{opacity:.2} }
</style>
