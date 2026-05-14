<template>
  <div>
    <div style="margin-bottom:28px;">
      <h1 style="font-size:26px;font-weight:800;letter-spacing:-.5px;">Utilisateurs <span class="gradient-text">({{ users.length }})</span></h1>
      <p style="color:#475569;font-size:13px;margin-top:4px;">Gestion des comptes, rôles et soldes</p>
    </div>

    <!-- Search -->
    <div style="margin-bottom:16px;">
      <input v-model="search" placeholder="Rechercher un utilisateur..." style="max-width:320px;padding:10px 14px;border-radius:12px;font-size:13px;" />
    </div>

    <!-- Table -->
    <div style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:20px;overflow:hidden;">
      <div v-if="loading" style="padding:24px;display:flex;flex-direction:column;gap:8px;">
        <div v-for="n in 5" :key="n" style="height:56px;border-radius:10px;background:rgba(255,255,255,0.03);animation:pulse 1.5s infinite;"></div>
      </div>
      <table v-else style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
          <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
            <th style="padding:12px 16px;text-align:left;font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;">Utilisateur</th>
            <th style="padding:12px 16px;text-align:center;font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;">Rôle</th>
            <th style="padding:12px 16px;text-align:right;font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;">Solde</th>
            <th style="padding:12px 16px;text-align:center;font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;">Téléphone</th>
            <th style="padding:12px 16px;text-align:center;font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;">Inscrit le</th>
            <th style="padding:12px 16px;text-align:center;font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in filtered" :key="user.id"
            style="border-bottom:1px solid rgba(255,255,255,0.04);transition:background .15s;"
            onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
            <td style="padding:14px 16px;">
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,rgba(99,102,241,0.3),rgba(168,85,247,0.3));display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#a5b4fc;flex-shrink:0;">
                  {{ (user.firstName?.[0] || '') + (user.lastName?.[0] || '') }}
                </div>
                <div>
                  <div style="font-weight:600;color:#e2e8f0;">{{ user.firstName }} {{ user.lastName }}</div>
                  <div style="font-size:11px;color:#334155;">{{ user.email }}</div>
                </div>
              </div>
            </td>
            <td style="padding:14px 16px;text-align:center;">
              <span v-if="isAdmin(user)"
                style="font-size:10px;font-weight:700;padding:3px 10px;border-radius:100px;background:rgba(168,85,247,0.15);border:1px solid rgba(168,85,247,0.3);color:#c084fc;">
                👑 Admin
              </span>
              <span v-else style="font-size:10px;font-weight:700;padding:3px 10px;border-radius:100px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:#475569;">
                Utilisateur
              </span>
            </td>
            <td style="padding:14px 16px;text-align:right;">
              <span style="font-size:14px;font-weight:700;" :style="parseFloat(user.balance||0) > 0 ? 'color:#34d399;' : 'color:#334155;'">
                {{ parseFloat(user.balance||0).toFixed(2) }} TND
              </span>
            </td>
            <td style="padding:14px 16px;text-align:center;color:#475569;font-size:12px;">{{ user.phone || '—' }}</td>
            <td style="padding:14px 16px;text-align:center;color:#475569;font-size:12px;">{{ formatDate(user.createdAt) }}</td>
            <td style="padding:14px 16px;text-align:center;">
              <div style="display:flex;align-items:center;justify-content:center;gap:6px;flex-wrap:wrap;">
                <button @click="openRefund(user)"
                  style="padding:5px 12px;border-radius:8px;background:rgba(52,211,153,0.1);border:1px solid rgba(52,211,153,0.25);color:#34d399;font-size:11px;font-weight:600;cursor:pointer;">
                  💸 Rembourser
                </button>
                <button @click="toggleAdmin(user)"
                  :style="isAdmin(user)
                    ? 'background:rgba(234,179,8,0.1);border:1px solid rgba(234,179,8,0.25);color:#fbbf24;'
                    : 'background:rgba(168,85,247,0.1);border:1px solid rgba(168,85,247,0.25);color:#c084fc;'"
                  style="padding:5px 12px;border-radius:8px;font-size:11px;font-weight:600;cursor:pointer;">
                  {{ isAdmin(user) ? 'Rétrograder' : 'Promouvoir' }}
                </button>
                <button @click="confirmDelete(user)"
                  style="padding:5px 12px;border-radius:8px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#f87171;font-size:11px;font-weight:600;cursor:pointer;">
                  Suppr.
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Refund modal -->
    <div v-if="refundTarget" @click.self="refundTarget=null"
      style="position:fixed;inset:0;z-index:200;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;padding:16px;">
      <div style="background:rgba(10,14,30,0.98);border:1px solid rgba(52,211,153,0.25);border-radius:24px;padding:32px;width:100%;max-width:440px;">
        <div style="margin-bottom:20px;">
          <div style="font-size:18px;font-weight:800;color:#34d399;margin-bottom:4px;">💸 Rembourser / Créditer</div>
          <div style="font-size:13px;color:#475569;">
            {{ refundTarget.firstName }} {{ refundTarget.lastName }} —
            solde actuel : <strong style="color:#e2e8f0;">{{ parseFloat(refundTarget.balance||0).toFixed(2) }} TND</strong>
          </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:14px;">
          <div>
            <label style="font-size:11px;font-weight:600;color:#475569;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">Montant à créditer (TND)</label>
            <input v-model.number="refundAmount" type="number" min="0.01" step="0.01" placeholder="0.00"
              style="width:100%;padding:12px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:12px;color:#e2e8f0;font-size:16px;font-weight:700;box-sizing:border-box;outline:none;" />
          </div>

          <!-- Quick amounts -->
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button v-for="q in [5,10,20,50,100]" :key="q"
              @click="refundAmount=q"
              style="padding:5px 14px;border-radius:100px;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;border:1px solid;"
              :style="refundAmount===q ? 'background:rgba(52,211,153,0.15);border-color:rgba(52,211,153,0.4);color:#34d399;' : 'background:transparent;border-color:rgba(255,255,255,0.1);color:#475569;'"
            >{{ q }} TND</button>
          </div>

          <div>
            <label style="font-size:11px;font-weight:600;color:#475569;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">Motif (optionnel)</label>
            <input v-model="refundNote" placeholder="Ex: remboursement commande #42, geste commercial..."
              style="width:100%;padding:10px 12px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:12px;color:#e2e8f0;font-size:13px;box-sizing:border-box;outline:none;" />
          </div>

          <!-- Preview -->
          <div v-if="refundAmount > 0" style="background:rgba(52,211,153,0.06);border:1px solid rgba(52,211,153,0.2);border-radius:12px;padding:12px 14px;display:flex;justify-content:space-between;">
            <span style="font-size:12px;color:#475569;">Nouveau solde après crédit</span>
            <span style="font-size:14px;font-weight:700;color:#34d399;">{{ (parseFloat(refundTarget.balance||0) + refundAmount).toFixed(2) }} TND</span>
          </div>

          <p v-if="refundError" style="color:#f87171;font-size:13px;margin:0;">{{ refundError }}</p>

          <div style="display:flex;gap:10px;margin-top:4px;">
            <button @click="refundTarget=null" style="flex:1;padding:12px;border-radius:12px;border:1px solid rgba(255,255,255,0.1);background:transparent;color:#475569;cursor:pointer;font-size:13px;">Annuler</button>
            <button @click="submitRefund" :disabled="refundLoading || refundAmount <= 0"
              style="flex:2;padding:12px;border-radius:12px;border:none;background:linear-gradient(135deg,rgba(52,211,153,0.3),rgba(16,185,129,0.2));color:#34d399;cursor:pointer;font-size:14px;font-weight:700;">
              {{ refundLoading ? 'Traitement…' : '✓ Confirmer le crédit' }}
            </button>
          </div>
        </div>
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
import { ref, computed, onMounted } from 'vue'
import api from '@/api'

const users       = ref([])
const loading     = ref(true)
const search      = ref('')
const toast       = ref('')
const toastError  = ref(false)

// Refund state
const refundTarget = ref(null)
const refundAmount = ref(0)
const refundNote   = ref('')
const refundLoading = ref(false)
const refundError  = ref('')

const filtered = computed(() => {
  if (!search.value.trim()) return users.value
  const q = search.value.toLowerCase()
  return users.value.filter(u =>
    u.email.toLowerCase().includes(q) ||
    `${u.firstName} ${u.lastName}`.toLowerCase().includes(q)
  )
})

function isAdmin(user) { return user.roles?.includes('ROLE_ADMIN') }

function formatDate(d) {
  return new Date(d).toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric' })
}

function openRefund(user) {
  refundTarget.value = user
  refundAmount.value = 0
  refundNote.value = ''
  refundError.value = ''
}

async function submitRefund() {
  if (!refundTarget.value || refundAmount.value <= 0) return
  refundLoading.value = true
  refundError.value = ''
  try {
    const res = await api.post(`/admin/users/${refundTarget.value.id}/refund`, {
      amount: refundAmount.value,
      note: refundNote.value,
    })
    // Update balance in place
    refundTarget.value.balance = String(res.data.newBalance)
    const u = users.value.find(x => x.id === refundTarget.value.id)
    if (u) u.balance = String(res.data.newBalance)
    refundTarget.value = null
    showToast(`Crédit de ${refundAmount.value.toFixed(2)} TND effectué ✓`)
  } catch(e) {
    refundError.value = e.response?.data?.message || 'Erreur lors du remboursement'
  } finally {
    refundLoading.value = false
  }
}

async function toggleAdmin(user) {
  const makeAdmin = !isAdmin(user)
  const action = makeAdmin ? 'promouvoir admin' : 'rétrograder'
  if (!confirm(`${action} ${user.firstName} ${user.lastName} ?`)) return
  try {
    const res = await api.patch(`/admin/users/${user.id}/role`, { isAdmin: makeAdmin })
    user.roles = res.data.roles
    showToast(`${user.firstName} ${makeAdmin ? 'est maintenant Admin ✓' : 'est redevenu utilisateur ✓'}`)
  } catch(e) {
    showToast(e.response?.data?.message || 'Erreur', true)
  }
}

async function confirmDelete(user) {
  if (!confirm(`Supprimer le compte de ${user.firstName} ${user.lastName} ? Cette action est irréversible.`)) return
  try {
    await api.delete(`/admin/users/${user.id}`)
    users.value = users.value.filter(u => u.id !== user.id)
    showToast('Utilisateur supprimé')
  } catch(e) {
    showToast(e.response?.data?.message || 'Impossible de supprimer', true)
  }
}

function showToast(msg, isError = false) {
  toast.value = msg; toastError.value = isError
  setTimeout(() => toast.value = '', 3000)
}

onMounted(async () => {
  const res = await api.get('/admin/users').catch(() => ({ data: [] }))
  users.value = res.data
  loading.value = false
})
</script>

<style scoped>
.slide-enter-active, .slide-leave-active { transition:all .3s ease; }
.slide-enter-from, .slide-leave-to { opacity:0; transform:translateY(20px); }
@keyframes pulse { 0%,100%{opacity:.5} 50%{opacity:.2} }
</style>
