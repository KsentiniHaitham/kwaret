<template>
  <div style="max-width:900px;margin:0 auto;padding:40px 32px 80px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:36px;flex-wrap:wrap;gap:12px;">
      <div>
        <div style="display:inline-block;background:rgba(129,140,248,0.1);border:1px solid rgba(129,140,248,0.2);color:#818cf8;font-size:11px;font-weight:700;padding:5px 14px;border-radius:100px;letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;">Marketing</div>
        <h1 style="font-size:32px;font-weight:800;letter-spacing:-1px;">Codes <span class="gradient-text">promo</span></h1>
      </div>
      <button @click="showForm = !showForm" class="btn-primary" style="padding:11px 22px;font-size:13px;border:none;cursor:pointer;">
        {{ showForm ? '✕ Annuler' : '+ Nouveau code' }}
      </button>
    </div>

    <!-- Create form -->
    <div v-if="showForm" class="card" style="padding:28px;margin-bottom:24px;">
      <div style="font-size:13px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:1px;margin-bottom:20px;">Créer un code promo</div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;">
        <div>
          <label class="field-label">Code</label>
          <input v-model="form.code" placeholder="EX: SUMMER20" class="field-input" style="text-transform:uppercase;" />
        </div>
        <div>
          <label class="field-label">Type</label>
          <select v-model="form.type" class="field-input">
            <option value="percent">Pourcentage (%)</option>
            <option value="fixed">Montant fixe (TND)</option>
          </select>
        </div>
        <div>
          <label class="field-label">Valeur</label>
          <input v-model.number="form.value" type="number" min="0" step="0.01" class="field-input" :placeholder="form.type === 'percent' ? '10 (= 10%)' : '5 (= 5 TND)'" />
        </div>
        <div>
          <label class="field-label">Max utilisations</label>
          <input v-model.number="form.maxUses" type="number" min="0" placeholder="Illimité" class="field-input" />
        </div>
        <div>
          <label class="field-label">Expire le</label>
          <input v-model="form.expiresAt" type="datetime-local" class="field-input" />
        </div>
      </div>
      <div v-if="formError" style="margin-top:12px;color:#f87171;font-size:13px;">{{ formError }}</div>
      <button @click="createPromo" :disabled="creating" class="btn-primary" style="margin-top:16px;padding:10px 24px;font-size:13px;border:none;cursor:pointer;">
        {{ creating ? 'Création...' : '✓ Créer le code' }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" style="display:flex;flex-direction:column;gap:10px;">
      <div v-for="n in 3" :key="n" style="height:60px;border-radius:14px;background:rgba(255,255,255,0.03);animation:pulse 1.5s infinite;"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="promos.length === 0" style="text-align:center;padding:60px 0;color:#334155;">
      <div style="font-size:36px;margin-bottom:12px;">🏷️</div>
      Aucun code promo pour l'instant
    </div>

    <!-- Table -->
    <div v-else class="card" style="overflow:hidden;padding:0;">
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="border-bottom:1px solid rgba(255,255,255,0.06);">
            <th v-for="h in ['Code','Type','Valeur','Utilisations','Expire','Statut','Actions']" :key="h"
              style="text-align:left;padding:12px 16px;font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.5px;">{{ h }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in promos" :key="p.id" style="border-bottom:1px solid rgba(255,255,255,0.04);transition:background .15s;"
            onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
            <td style="padding:12px 16px;font-family:monospace;font-size:14px;font-weight:700;color:#a5b4fc;">{{ p.code }}</td>
            <td style="padding:12px 16px;font-size:13px;color:#64748b;">{{ p.type === 'percent' ? 'Pourcentage' : 'Fixe' }}</td>
            <td style="padding:12px 16px;font-size:13px;font-weight:600;color:#e2e8f0;">
              {{ p.type === 'percent' ? `${p.value}%` : `${p.value} TND` }}
            </td>
            <td style="padding:12px 16px;font-size:13px;color:#64748b;">
              {{ p.usedCount }}{{ p.maxUses ? ` / ${p.maxUses}` : ' / ∞' }}
            </td>
            <td style="padding:12px 16px;font-size:12px;color:#64748b;">
              {{ p.expiresAt ? new Date(p.expiresAt).toLocaleDateString('fr-FR') : '—' }}
            </td>
            <td style="padding:12px 16px;">
              <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:100px;"
                :style="p.isActive ? 'background:rgba(52,211,153,0.12);color:#34d399;border:1px solid rgba(52,211,153,0.25);' : 'background:rgba(71,85,105,0.2);color:#475569;border:1px solid rgba(71,85,105,0.3);'">
                {{ p.isActive ? '● Actif' : '○ Inactif' }}
              </span>
            </td>
            <td style="padding:12px 16px;display:flex;gap:8px;">
              <button @click="toggle(p)"
                style="background:none;border:1px solid rgba(255,255,255,0.1);color:#94a3b8;padding:5px 12px;border-radius:8px;font-size:12px;cursor:pointer;">
                {{ p.isActive ? 'Désactiver' : 'Activer' }}
              </button>
              <button @click="remove(p)"
                style="background:none;border:1px solid rgba(239,68,68,0.3);color:#f87171;padding:5px 12px;border-radius:8px;font-size:12px;cursor:pointer;">
                Supprimer
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'

const promos    = ref([])
const loading   = ref(true)
const showForm  = ref(false)
const creating  = ref(false)
const formError = ref('')
const form      = ref({ code: '', type: 'percent', value: 10, maxUses: null, expiresAt: '' })

async function load() {
  loading.value = true
  try {
    const r = await api.get('/admin/promos')
    promos.value = r.data
  } catch {}
  loading.value = false
}

async function createPromo() {
  formError.value = ''
  if (!form.value.code || !form.value.value) { formError.value = 'Code et valeur requis'; return }
  creating.value = true
  try {
    await api.post('/admin/promos', {
      ...form.value,
      code:     form.value.code.toUpperCase(),
      maxUses:  form.value.maxUses || null,
      expiresAt: form.value.expiresAt || null,
    })
    showForm.value = false
    form.value = { code: '', type: 'percent', value: 10, maxUses: null, expiresAt: '' }
    await load()
  } catch (e) {
    formError.value = e.response?.data?.message || 'Erreur'
  } finally {
    creating.value = false
  }
}

async function toggle(p) {
  try {
    await api.patch(`/admin/promos/${p.id}/toggle`)
    p.isActive = !p.isActive
  } catch {}
}

async function remove(p) {
  if (!confirm(`Supprimer le code "${p.code}" ?`)) return
  try {
    await api.delete(`/admin/promos/${p.id}`)
    promos.value = promos.value.filter(x => x.id !== p.id)
  } catch {}
}

onMounted(load)
</script>

<style scoped>
.field-label {
  display: block;
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: .5px;
  margin-bottom: 6px;
}
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
