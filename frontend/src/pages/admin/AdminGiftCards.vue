<template>
  <div style="max-width:900px;margin:0 auto;padding:40px 32px 80px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:36px;flex-wrap:wrap;gap:12px;">
      <div>
        <div style="display:inline-block;background:rgba(129,140,248,0.1);border:1px solid rgba(129,140,248,0.2);color:#818cf8;font-size:11px;font-weight:700;padding:5px 14px;border-radius:100px;letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;">Marketing</div>
        <h1 style="font-size:32px;font-weight:800;letter-spacing:-1px;">Cartes <span class="gradient-text">cadeaux</span></h1>
      </div>
      <button @click="showForm = !showForm" class="btn-primary" style="padding:11px 22px;font-size:13px;border:none;cursor:pointer;">
        {{ showForm ? '✕ Annuler' : '+ Générer des cartes' }}
      </button>
    </div>

    <!-- Generate form -->
    <div v-if="showForm" class="card" style="padding:28px;margin-bottom:24px;">
      <div style="font-size:13px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:1px;margin-bottom:20px;">Générer des cartes cadeaux</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
        <div>
          <label style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Valeur (TND)</label>
          <input v-model.number="genAmount" type="number" min="1" step="1" placeholder="50" class="field-input" />
        </div>
        <div>
          <label style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Quantité (max 20)</label>
          <input v-model.number="genQty" type="number" min="1" max="20" placeholder="1" class="field-input" />
        </div>
      </div>
      <div v-if="genResult" style="margin-bottom:14px;background:rgba(52,211,153,0.08);border:1px solid rgba(52,211,153,0.2);border-radius:12px;padding:14px 18px;">
        <div style="font-size:12px;color:#34d399;font-weight:700;margin-bottom:8px;">✅ {{ genResult.message }}</div>
        <div v-for="code in genResult.codes" :key="code" style="font-family:monospace;font-size:14px;font-weight:700;color:#e2e8f0;letter-spacing:2px;padding:3px 0;">{{ code }}</div>
      </div>
      <button @click="generate" :disabled="generating || !genAmount" class="btn-primary" style="padding:10px 24px;font-size:13px;border:none;cursor:pointer;">
        {{ generating ? 'Génération...' : 'Générer' }}
      </button>
    </div>

    <!-- Cards list -->
    <div v-if="loading" style="display:flex;flex-direction:column;gap:10px;">
      <div v-for="n in 3" :key="n" style="height:60px;border-radius:14px;background:rgba(255,255,255,0.03);animation:pulse 1.5s infinite;"></div>
    </div>

    <div v-else-if="cards.length === 0" style="text-align:center;padding:60px 0;color:#334155;">
      <div style="font-size:36px;margin-bottom:12px;">🎁</div>
      Aucune carte cadeau pour l'instant
    </div>

    <div v-else class="card" style="overflow:hidden;padding:0;">
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="border-bottom:1px solid rgba(255,255,255,0.06);">
            <th v-for="h in ['Code','Valeur','Statut','Acheté par','Date']" :key="h"
              style="text-align:left;padding:12px 16px;font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.5px;">{{ h }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="card in cards" :key="card.id" style="border-bottom:1px solid rgba(255,255,255,0.04);"
            onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
            <td style="padding:12px 16px;font-family:monospace;font-size:13px;font-weight:700;color:#a5b4fc;">{{ card.code }}</td>
            <td style="padding:12px 16px;font-size:13px;font-weight:600;color:#e2e8f0;">{{ card.initialValue }} TND</td>
            <td style="padding:12px 16px;">
              <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:100px;"
                :style="card.isRedeemed ? 'background:rgba(71,85,105,0.2);color:#475569;border:1px solid rgba(71,85,105,0.3);' : 'background:rgba(52,211,153,0.12);color:#34d399;border:1px solid rgba(52,211,153,0.25);'">
                {{ card.isRedeemed ? '✓ Utilisée' : '● Disponible' }}
              </span>
            </td>
            <td style="padding:12px 16px;font-size:13px;color:#64748b;">{{ card.purchasedBy }}</td>
            <td style="padding:12px 16px;font-size:12px;color:#334155;">{{ new Date(card.createdAt).toLocaleDateString('fr-FR') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'

const cards      = ref([])
const loading    = ref(true)
const showForm   = ref(false)
const generating = ref(false)
const genAmount  = ref(50)
const genQty     = ref(1)
const genResult  = ref(null)

async function load() {
  loading.value = true
  try {
    const r = await api.get('/admin/gift-cards')
    cards.value = r.data
  } catch {}
  loading.value = false
}

async function generate() {
  generating.value = true
  genResult.value  = null
  try {
    const r = await api.post('/admin/gift-cards/generate', { amount: genAmount.value, quantity: genQty.value })
    genResult.value = r.data
    await load()
  } catch {}
  generating.value = false
}

onMounted(load)
</script>

<style scoped>
.field-input {
  width: 100%;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 10px;
  padding: 10px 12px;
  color: #e2e8f0;
  font-size: 13px;
  outline: none;
  box-sizing: border-box;
  transition: border .2s;
}
.field-input:focus { border-color: rgba(129,140,248,0.5); }
@keyframes pulse { 0%,100%{opacity:.5} 50%{opacity:.2} }
</style>
