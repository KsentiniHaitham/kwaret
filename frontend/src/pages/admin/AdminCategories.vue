<template>
  <div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;">
      <div>
        <h1 style="font-size:26px;font-weight:800;letter-spacing:-.5px;">Catégories <span class="gradient-text">({{ categories.length }})</span></h1>
        <p style="color:#475569;font-size:13px;margin-top:4px;">Organiser le catalogue produits</p>
      </div>
      <button @click="openCreate" class="btn-primary" style="border:none;cursor:pointer;padding:10px 18px;font-size:13px;">
        + Nouvelle catégorie
      </button>
    </div>

    <!-- Grid -->
    <div v-if="loading" style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
      <div v-for="n in 4" :key="n" style="height:120px;border-radius:16px;background:rgba(255,255,255,0.03);animation:pulse 1.5s infinite;"></div>
    </div>

    <div v-else style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
      <div v-for="cat in categories" :key="cat.id" class="card" style="padding:22px;display:flex;flex-direction:column;gap:14px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;">
          <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:32px;filter:drop-shadow(0 4px 8px rgba(129,140,248,0.3));">{{ cat.icon || '📁' }}</span>
            <div>
              <div style="font-size:15px;font-weight:700;color:#e2e8f0;">{{ cat.name }}</div>
              <div style="font-size:11px;color:#334155;margin-top:2px;font-family:monospace;">{{ cat.slug }}</div>
            </div>
          </div>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;">
          <span style="font-size:12px;color:#475569;">{{ productCount(cat.id) }} produit(s)</span>
          <div style="display:flex;gap:6px;">
            <button @click="openEdit(cat)" style="padding:5px 12px;border-radius:8px;background:rgba(99,102,241,0.12);border:1px solid rgba(99,102,241,0.25);color:#818cf8;font-size:11px;font-weight:600;cursor:pointer;">Éditer</button>
            <button @click="confirmDelete(cat)" style="padding:5px 12px;border-radius:8px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#f87171;font-size:11px;font-weight:600;cursor:pointer;">Suppr.</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" style="position:fixed;inset:0;background:rgba(6,9,20,0.85);z-index:200;display:flex;align-items:center;justify-content:center;padding:20px;" @click.self="showModal=false">
        <div style="background:#0d1117;border:1px solid rgba(255,255,255,0.1);border-radius:24px;padding:32px;width:100%;max-width:420px;">
          <h2 style="font-size:20px;font-weight:800;color:#e2e8f0;margin-bottom:24px;">{{ editing ? 'Modifier la catégorie' : 'Nouvelle catégorie' }}</h2>

          <div style="display:flex;flex-direction:column;gap:14px;">
            <div>
              <label class="field-label">Nom *</label>
              <input v-model="form.name" placeholder="Ex: Gaming, Streaming..." class="field-input" />
            </div>
            <div>
              <label class="field-label">Icône (emoji)</label>
              <input v-model="form.icon" placeholder="🎮" class="field-input" style="font-size:20px;" />
              <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;">
                <button v-for="e in emojiPicker" :key="e" @click="form.icon=e"
                  style="font-size:20px;padding:6px;border-radius:8px;border:1px solid rgba(255,255,255,0.1);background:rgba(255,255,255,0.04);cursor:pointer;transition:border .2s;"
                  :style="form.icon===e ? 'border-color:rgba(129,140,248,0.5);background:rgba(99,102,241,0.1);' : ''">
                  {{ e }}
                </button>
              </div>
            </div>

            <div v-if="formError" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#f87171;font-size:13px;padding:10px 14px;border-radius:10px;">{{ formError }}</div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:4px;">
              <button @click="showModal=false" class="btn-ghost" style="padding:10px 20px;font-size:13px;cursor:pointer;">Annuler</button>
              <button @click="save" :disabled="saving" class="btn-primary" style="border:none;cursor:pointer;padding:10px 22px;font-size:13px;">
                {{ saving ? 'Enregistrement...' : (editing ? 'Mettre à jour' : 'Créer') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

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

const categories = ref([])
const loading    = ref(true)
const showModal  = ref(false)
const editing    = ref(null)
const saving     = ref(false)
const formError  = ref('')
const toast      = ref('')
const toastError = ref(false)
const productCounts = ref({})

const form = ref({ name: '', icon: '' })

const emojiPicker = ['🎮', '📺', '🤖', '🎵', '💎', '🎬', '🖥️', '📱', '🎧', '🌐', '🔑', '⚡']

function productCount(id) { return productCounts.value[id] ?? 0 }

function openCreate() {
  editing.value = null
  form.value = { name: '', icon: '' }
  formError.value = ''
  showModal.value = true
}

function openEdit(cat) {
  editing.value = cat
  form.value = { name: cat.name, icon: cat.icon || '' }
  formError.value = ''
  showModal.value = true
}

async function save() {
  if (!form.value.name.trim()) { formError.value = 'Le nom est requis'; return }
  saving.value = true
  formError.value = ''
  try {
    if (editing.value) {
      const res = await api.put(`/admin/categories/${editing.value.id}`, form.value)
      const idx = categories.value.findIndex(c => c.id === editing.value.id)
      if (idx !== -1) categories.value[idx] = res.data
    } else {
      const res = await api.post('/admin/categories', form.value)
      categories.value.push(res.data)
    }
    showModal.value = false
    showToast(editing.value ? 'Catégorie mise à jour ✓' : 'Catégorie créée ✓')
  } catch(e) {
    formError.value = e.response?.data?.message || 'Erreur lors de la sauvegarde'
  } finally {
    saving.value = false
  }
}

async function confirmDelete(cat) {
  if (!confirm(`Supprimer "${cat.name}" ?`)) return
  try {
    await api.delete(`/admin/categories/${cat.id}`)
    categories.value = categories.value.filter(c => c.id !== cat.id)
    showToast('Catégorie supprimée')
  } catch(e) {
    showToast(e.response?.data?.message || 'Impossible de supprimer', true)
  }
}

function showToast(msg, isError = false) {
  toast.value = msg; toastError.value = isError
  setTimeout(() => toast.value = '', 3000)
}

onMounted(async () => {
  const [cr, pr] = await Promise.all([
    api.get('/admin/categories').catch(() => ({ data: [] })),
    api.get('/admin/products').catch(() => ({ data: [] })),
  ])
  categories.value = cr.data
  pr.data.forEach(p => {
    const cid = p.category?.id
    if (cid) productCounts.value[cid] = (productCounts.value[cid] || 0) + 1
  })
  loading.value = false
})
</script>

<style scoped>
.field-label { display:block; font-size:11px; font-weight:600; color:#475569; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
.field-input { width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:10px; padding:10px 12px; color:#e2e8f0; font-size:13px; outline:none; transition:border .2s; box-sizing:border-box; }
.field-input:focus { border-color:rgba(129,140,248,0.5); }
.field-input::placeholder { color:#334155; }
.slide-enter-active, .slide-leave-active { transition:all .3s ease; }
.slide-enter-from, .slide-leave-to { opacity:0; transform:translateY(20px); }
@keyframes pulse { 0%,100%{opacity:.5} 50%{opacity:.2} }
</style>
