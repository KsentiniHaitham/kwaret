<template>
  <div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
      <div>
        <h1 style="font-size:26px;font-weight:800;letter-spacing:-.5px;">Avis <span class="gradient-text">clients</span></h1>
        <p style="color:#475569;font-size:13px;margin-top:4px;">
          <span v-if="data">{{ data.count }} avis · Note moyenne :
            <span class="gradient-text-cyan" style="font-weight:800;">{{ data.average ?? '—' }}/5</span>
          </span>
        </p>
      </div>
      <!-- Average stars -->
      <div v-if="data?.average" style="display:flex;gap:3px;align-items:center;">
        <span v-for="i in 5" :key="i" style="font-size:22px;">{{ i <= Math.round(data.average) ? '⭐' : '☆' }}</span>
        <span class="gradient-text-cyan" style="font-size:20px;font-weight:800;margin-left:8px;">{{ data.average }}</span>
      </div>
    </div>

    <!-- Rating distribution -->
    <div v-if="data?.count > 0" class="card" style="padding:20px;margin-bottom:20px;">
      <div style="font-size:11px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;">Répartition des notes</div>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <div v-for="star in [5,4,3,2,1]" :key="star" style="display:flex;align-items:center;gap:10px;">
          <span style="font-size:12px;color:#94a3b8;width:28px;text-align:right;">{{ star }}⭐</span>
          <div style="flex:1;height:8px;border-radius:100px;background:rgba(255,255,255,0.06);overflow:hidden;">
            <div :style="`width:${starPct(star)}%;background:linear-gradient(90deg,#6366f1,#a855f7);border-radius:100px;height:100%;transition:width .4s;`"></div>
          </div>
          <span style="font-size:12px;color:#475569;width:28px;">{{ starCount(star) }}</span>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" style="display:flex;flex-direction:column;gap:10px;">
      <div v-for="n in 5" :key="n" style="height:88px;border-radius:16px;background:rgba(255,255,255,0.03);animation:pulse 1.5s infinite;"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="!data?.reviews?.length" style="text-align:center;padding:64px 0;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);border-radius:20px;">
      <div style="font-size:48px;margin-bottom:16px;">⭐</div>
      <p style="color:#475569;font-size:15px;">Aucun avis pour l'instant</p>
    </div>

    <!-- Reviews list -->
    <div v-else style="display:flex;flex-direction:column;gap:10px;">
      <div v-for="r in data.reviews" :key="r.id"
        class="card" style="padding:18px;display:flex;align-items:flex-start;gap:14px;flex-wrap:wrap;">

        <!-- Avatar -->
        <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,rgba(99,102,241,0.25),rgba(168,85,247,0.2));display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:800;color:#a5b4fc;flex-shrink:0;">
          {{ r.user.firstName?.charAt(0).toUpperCase() }}
        </div>

        <!-- Content -->
        <div style="flex:1;min-width:200px;">
          <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px;margin-bottom:6px;">
            <div>
              <span style="font-size:13px;font-weight:700;color:#e2e8f0;">{{ r.user.firstName }} {{ r.user.lastName }}</span>
              <span style="color:#334155;font-size:11px;margin-left:8px;">{{ r.user.email }}</span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
              <div style="display:flex;gap:2px;">
                <span v-for="i in 5" :key="i" style="font-size:13px;">{{ i <= r.rating ? '⭐' : '☆' }}</span>
              </div>
              <span style="color:#334155;font-size:11px;">{{ formatDate(r.createdAt) }}</span>
            </div>
          </div>

          <div style="font-size:12px;color:#475569;margin-bottom:6px;">
            Commande <span style="font-family:monospace;color:#818cf8;">#{{ String(r.order.id).padStart(4,'0') }}</span>
            <span v-if="r.products?.length"> · {{ r.products.filter(Boolean).join(', ') }}</span>
          </div>

          <p v-if="r.comment" style="color:#94a3b8;font-size:13px;line-height:1.6;margin:0;padding:10px 14px;background:rgba(255,255,255,0.03);border-radius:10px;border-left:2px solid rgba(129,140,248,0.3);">
            {{ r.comment }}
          </p>
          <p v-else style="color:#334155;font-size:12px;font-style:italic;margin:0;">Aucun commentaire.</p>
        </div>

        <!-- Delete -->
        <button @click="deleteReview(r.id)"
          style="background:none;border:1px solid rgba(239,68,68,0.2);color:#f87171;border-radius:8px;padding:6px 12px;font-size:12px;cursor:pointer;flex-shrink:0;transition:all .2s;"
          onmouseover="this.style.background='rgba(239,68,68,0.1)'" onmouseout="this.style.background='none'">
          🗑️ Supprimer
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'

const data    = ref(null)
const loading = ref(true)

function formatDate(d) {
  return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function starCount(star) {
  return data.value?.reviews?.filter(r => r.rating === star).length ?? 0
}

function starPct(star) {
  const total = data.value?.count ?? 0
  if (!total) return 0
  return Math.round(starCount(star) / total * 100)
}

async function load() {
  loading.value = true
  const r = await api.get('/admin/reviews').catch(() => ({ data: { reviews: [], average: null, count: 0 } }))
  data.value = r.data
  loading.value = false
}

async function deleteReview(id) {
  if (!confirm('Supprimer cet avis ?')) return
  try {
    await api.delete(`/admin/reviews/${id}`)
    data.value.reviews = data.value.reviews.filter(r => r.id !== id)
    data.value.count   = data.value.reviews.length
    const ratings = data.value.reviews.map(r => r.rating)
    data.value.average = ratings.length ? +(ratings.reduce((a,b) => a+b, 0) / ratings.length).toFixed(1) : null
  } catch { alert('Erreur lors de la suppression') }
}

onMounted(load)
</script>

<style scoped>
@keyframes pulse { 0%,100%{opacity:.5} 50%{opacity:.2} }
</style>
