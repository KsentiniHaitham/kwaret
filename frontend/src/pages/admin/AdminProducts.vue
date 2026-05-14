<template>
  <div>
    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;">
      <div>
        <h1 style="font-size:26px;font-weight:800;letter-spacing:-.5px;">Produits <span class="gradient-text">({{ products.length }})</span></h1>
        <p style="color:#475569;font-size:13px;margin-top:4px;">Créer, modifier, supprimer des produits</p>
      </div>
      <button @click="openCreate" class="btn-primary" style="border:none;cursor:pointer;padding:10px 18px;font-size:13px;">
        + Ajouter un produit
      </button>
    </div>

    <!-- Search + filter -->
    <div style="display:flex;gap:10px;margin-bottom:18px;">
      <input v-model="search" placeholder="Rechercher..." style="flex:1;padding:10px 14px;border-radius:12px;font-size:13px;" />
      <select v-model="filterCat" style="width:180px;padding:10px 14px;border-radius:12px;font-size:13px;">
        <option value="">Toutes catégories</option>
        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
    </div>

    <!-- Table -->
    <div style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:20px;overflow:hidden;">
      <div v-if="loading" style="padding:24px;display:flex;flex-direction:column;gap:8px;">
        <div v-for="n in 5" :key="n" style="height:52px;border-radius:10px;background:rgba(255,255,255,0.03);animation:pulse 1.5s infinite;"></div>
      </div>
      <div v-else-if="filtered.length === 0" style="text-align:center;padding:48px;color:#334155;">
        <div style="font-size:36px;margin-bottom:10px;">📦</div>
        <p>Aucun produit trouvé</p>
      </div>
      <table v-else style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
          <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
            <th style="padding:12px 16px;text-align:left;font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;">Produit</th>
            <th style="padding:12px 16px;text-align:left;font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;">Catégorie</th>
            <th style="padding:12px 16px;text-align:right;font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;">Prix</th>
            <th style="padding:12px 16px;text-align:center;font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;">Cashback</th>
            <th style="padding:12px 16px;text-align:center;font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;">Stock</th>
            <th style="padding:12px 16px;text-align:center;font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;">Statut</th>
            <th style="padding:12px 16px;text-align:center;font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:1px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in filtered" :key="p.id"
            style="border-bottom:1px solid rgba(255,255,255,0.04);transition:background .15s;"
            onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
            <td style="padding:13px 16px;">
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,rgba(99,102,241,0.15),rgba(168,85,247,0.15));display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">
                  <img v-if="p.image" :src="p.image" style="width:100%;height:100%;object-fit:cover;border-radius:10px;" />
                  <span v-else>💎</span>
                </div>
                <div>
                  <div style="font-weight:600;color:#e2e8f0;">{{ p.name }}</div>
                  <div style="font-size:11px;color:#334155;margin-top:1px;">{{ p.slug }}</div>
                </div>
              </div>
            </td>
            <td style="padding:13px 16px;color:#64748b;">{{ p.category?.name }}</td>
            <td style="padding:13px 16px;text-align:right;">
              <span class="gradient-text-cyan" style="font-weight:700;">{{ parseFloat(p.price).toFixed(2) }} TND</span>
            </td>
            <td style="padding:13px 16px;text-align:center;">
              <span v-if="p.cashback && parseFloat(p.cashback) > 0"
                style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:100px;background:rgba(52,211,153,0.12);border:1px solid rgba(52,211,153,0.25);color:#34d399;">
                {{ parseFloat(p.cashback) }}%
              </span>
              <span v-else style="color:#334155;font-size:12px;">—</span>
            </td>
            <td style="padding:13px 16px;text-align:center;">
              <div style="display:flex;align-items:center;justify-content:center;gap:6px;">
                <button @click="changeStock(p, -1)" style="width:22px;height:22px;border-radius:6px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:#94a3b8;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center;">−</button>
                <span :style="p.stock === 0 ? 'color:#f87171;' : p.stock <= 5 ? 'color:#fbbf24;' : 'color:#34d399;'" style="font-weight:700;min-width:24px;text-align:center;">{{ p.stock }}</span>
                <button @click="changeStock(p, 1)" style="width:22px;height:22px;border-radius:6px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:#94a3b8;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center;">+</button>
              </div>
            </td>
            <td style="padding:13px 16px;text-align:center;">
              <span :style="p.isActive ? 'background:rgba(52,211,153,0.1);border:1px solid rgba(52,211,153,0.25);color:#34d399;' : 'background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#f87171;'"
                style="font-size:10px;font-weight:700;padding:3px 10px;border-radius:100px;">
                {{ p.isActive ? 'Actif' : 'Inactif' }}
              </span>
              <span v-if="p.isFeatured" style="margin-left:4px;font-size:10px;font-weight:700;padding:3px 10px;border-radius:100px;background:rgba(234,179,8,0.1);border:1px solid rgba(234,179,8,0.25);color:#fbbf24;">⭐</span>
            </td>
            <td style="padding:13px 16px;text-align:center;">
              <div style="display:flex;align-items:center;justify-content:center;gap:6px;">
                <button @click="openEdit(p)" style="padding:5px 12px;border-radius:8px;background:rgba(99,102,241,0.12);border:1px solid rgba(99,102,241,0.25);color:#818cf8;font-size:11px;font-weight:600;cursor:pointer;">Éditer</button>
                <button @click="confirmDelete(p)" style="padding:5px 12px;border-radius:8px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#f87171;font-size:11px;font-weight:600;cursor:pointer;">Suppr.</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" style="position:fixed;inset:0;background:rgba(6,9,20,0.85);z-index:200;display:flex;align-items:center;justify-content:center;padding:20px;" @click.self="showModal=false">
        <div style="background:#0d1117;border:1px solid rgba(255,255,255,0.1);border-radius:24px;padding:32px;width:100%;max-width:540px;max-height:90vh;overflow-y:auto;">
          <h2 style="font-size:20px;font-weight:800;color:#e2e8f0;margin-bottom:24px;">{{ editingProduct ? 'Modifier le produit' : 'Nouveau produit' }}</h2>

          <div style="display:flex;flex-direction:column;gap:14px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div>
                <label class="field-label">Nom *</label>
                <input v-model="form.name" placeholder="Nom du produit" class="field-input" />
              </div>
              <div>
                <label class="field-label">Prix (TND) *</label>
                <input v-model="form.price" type="number" step="0.01" min="0" placeholder="0.00" class="field-input" />
              </div>
            </div>

            <div>
              <label class="field-label">Description</label>
              <textarea v-model="form.description" placeholder="Description du produit..." class="field-input" style="height:80px;resize:vertical;"></textarea>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div>
                <label class="field-label">Catégorie *</label>
                <select v-model="form.categoryId" class="field-input">
                  <option value="">Choisir...</option>
                  <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.icon }} {{ c.name }}</option>
                </select>
              </div>
              <div>
                <label class="field-label">Stock</label>
                <input v-model="form.stock" type="number" min="0" placeholder="0" class="field-input" />
              </div>
            </div>

            <!-- Zone upload image -->
            <div>
              <label class="field-label">Image du produit</label>
              <div
                class="upload-zone"
                :class="{ 'drag-over': isDragging }"
                @click="$refs.imageInput.click()"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="onDrop"
              >
                <!-- Prévisualisation -->
                <div v-if="imagePreview || form.image" style="position:relative;display:inline-block;">
                  <img :src="imagePreview || form.image" style="max-height:120px;max-width:100%;border-radius:10px;object-fit:contain;" />
                  <button type="button" @click.stop="removeImage" style="position:absolute;top:-8px;right:-8px;width:22px;height:22px;border-radius:50%;background:#ef4444;border:none;color:#fff;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-weight:700;">×</button>
                </div>
                <!-- Placeholder -->
                <div v-else style="display:flex;flex-direction:column;align-items:center;gap:8px;color:#334155;">
                  <span style="font-size:28px;">🖼️</span>
                  <span style="font-size:12px;font-weight:600;">Cliquer ou glisser une image</span>
                  <span style="font-size:11px;">PNG, JPG, WEBP — max 5 Mo</span>
                </div>
                <input ref="imageInput" type="file" accept="image/*" style="display:none;" @change="onFileChange" />
              </div>
              <!-- URL externe en alternative -->
              <input v-if="!imageFile && !imagePreview" v-model="form.image" placeholder="Ou coller une URL externe https://..." class="field-input" style="margin-top:8px;font-size:12px;" />
            </div>

            <div>
              <label class="field-label">Cashback % (optionnel)</label>
              <div style="display:flex;align-items:center;gap:10px;">
                <input v-model.number="form.cashback" type="number" min="0" max="100" step="0.5" placeholder="0" class="field-input" style="max-width:120px;" />
                <span style="font-size:12px;color:#475569;">% crédité sur le portefeuille lors du paiement par solde</span>
              </div>
            </div>

            <div style="display:flex;gap:20px;">
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:#94a3b8;">
                <input type="checkbox" v-model="form.isActive" style="width:auto;" />
                Actif
              </label>
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:#94a3b8;">
                <input type="checkbox" v-model="form.isFeatured" style="width:auto;" />
                Mis en avant (⭐ Top)
              </label>
            </div>

            <div v-if="formError" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#f87171;font-size:13px;padding:10px 14px;border-radius:10px;">{{ formError }}</div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:6px;">
              <button @click="showModal=false" class="btn-ghost" style="padding:10px 20px;font-size:13px;cursor:pointer;">Annuler</button>
              <button @click="saveProduct" :disabled="saving" class="btn-primary" style="border:none;cursor:pointer;padding:10px 24px;font-size:13px;">
                {{ saving ? 'Enregistrement...' : (editingProduct ? 'Mettre à jour' : 'Créer') }}
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
import { ref, computed, onMounted } from 'vue'
import api from '@/api'

const products   = ref([])
const categories = ref([])
const loading    = ref(true)
const search     = ref('')
const filterCat  = ref('')
const showModal  = ref(false)
const editingProduct = ref(null)
const saving     = ref(false)
const formError  = ref('')
const toast      = ref('')
const toastError = ref(false)

const form         = ref({ name:'', price:'', description:'', categoryId:'', stock:0, image:'', isActive:true, isFeatured:false, cashback:0 })
const imageFile    = ref(null)
const imagePreview = ref('')
const isDragging   = ref(false)

const filtered = computed(() => {
  let list = products.value
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    list = list.filter(p => p.name.toLowerCase().includes(q))
  }
  if (filterCat.value) {
    list = list.filter(p => p.category?.id === parseInt(filterCat.value))
  }
  return list
})

function resetImage() {
  imageFile.value = null
  imagePreview.value = ''
  isDragging.value = false
}

function onFileChange(e) {
  const file = e.target.files[0]
  if (!file) return
  imageFile.value = file
  imagePreview.value = URL.createObjectURL(file)
  form.value.image = ''         // on ignore l'URL externe si un fichier est sélectionné
}

function onDrop(e) {
  isDragging.value = false
  const file = e.dataTransfer.files[0]
  if (!file || !file.type.startsWith('image/')) return
  imageFile.value = file
  imagePreview.value = URL.createObjectURL(file)
  form.value.image = ''
}

function removeImage() {
  resetImage()
  form.value.image = ''
}

function openCreate() {
  editingProduct.value = null
  form.value = { name:'', price:'', description:'', categoryId:'', stock:0, image:'', isActive:true, isFeatured:false, cashback:0 }
  formError.value = ''
  resetImage()
  showModal.value = true
}

function openEdit(p) {
  editingProduct.value = p
  form.value = {
    name: p.name, price: p.price, description: p.description || '',
    categoryId: p.category?.id || '', stock: p.stock,
    image: p.image || '', isActive: p.isActive, isFeatured: p.isFeatured,
    cashback: p.cashback ? parseFloat(p.cashback) : 0,
  }
  formError.value = ''
  resetImage()
  showModal.value = true
}

async function saveProduct() {
  if (!form.value.name || !form.value.price || !form.value.categoryId) {
    formError.value = 'Veuillez remplir les champs obligatoires (*)'; return
  }
  saving.value = true
  formError.value = ''
  try {
    const payload = { ...form.value, price: parseFloat(form.value.price), stock: parseInt(form.value.stock) }
    let saved

    if (editingProduct.value) {
      const res = await api.put(`/admin/products/${editingProduct.value.id}`, payload)
      saved = res.data
      const idx = products.value.findIndex(p => p.id === editingProduct.value.id)
      if (idx !== -1) products.value[idx] = saved
    } else {
      const res = await api.post('/admin/products', payload)
      saved = res.data
      products.value.unshift(saved)
    }

    // Upload du fichier image si sélectionné
    if (imageFile.value) {
      const fd = new FormData()
      fd.append('image', imageFile.value)
      const imgRes = await api.post(`/admin/products/${saved.id}/image`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      const idx = products.value.findIndex(p => p.id === saved.id)
      if (idx !== -1) products.value[idx] = imgRes.data
    }

    showModal.value = false
    showToast(editingProduct.value ? 'Produit mis à jour ✓' : 'Produit créé ✓')
  } catch(e) {
    formError.value = e.response?.data?.message || 'Erreur lors de la sauvegarde'
  } finally {
    saving.value = false
  }
}

async function changeStock(p, delta) {
  const newStock = Math.max(0, p.stock + delta)
  await api.patch(`/admin/products/${p.id}/stock`, { stock: newStock })
  p.stock = newStock
}

async function confirmDelete(p) {
  if (!confirm(`Supprimer "${p.name}" ?`)) return
  try {
    await api.delete(`/admin/products/${p.id}`)
    products.value = products.value.filter(x => x.id !== p.id)
    showToast('Produit supprimé')
  } catch(e) {
    showToast(e.response?.data?.message || 'Erreur suppression', true)
  }
}

function showToast(msg, isError = false) {
  toast.value = msg; toastError.value = isError
  setTimeout(() => toast.value = '', 3000)
}

onMounted(async () => {
  const [pr, cr] = await Promise.all([
    api.get('/admin/products').catch(() => ({ data: [] })),
    api.get('/admin/categories').catch(() => ({ data: [] })),
  ])
  products.value   = pr.data
  categories.value = cr.data
  loading.value    = false
})
</script>

<style scoped>
.field-label { display:block; font-size:11px; font-weight:600; color:#475569; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
.field-input { width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:10px; padding:10px 12px; color:#e2e8f0; font-size:13px; outline:none; transition:border .2s; box-sizing:border-box; }
.field-input:focus { border-color:rgba(129,140,248,0.5); }
.field-input::placeholder { color:#334155; }
select.field-input option { background:#0d1117; }
.slide-enter-active, .slide-leave-active { transition:all .3s ease; }
.slide-enter-from, .slide-leave-to { opacity:0; transform:translateY(20px); }
@keyframes pulse { 0%,100%{opacity:.5} 50%{opacity:.2} }

.upload-zone {
  width: 100%;
  min-height: 110px;
  border: 2px dashed rgba(255,255,255,0.1);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all .2s;
  padding: 16px;
  box-sizing: border-box;
  background: rgba(255,255,255,0.02);
}
.upload-zone:hover, .upload-zone.drag-over {
  border-color: rgba(129,140,248,0.5);
  background: rgba(99,102,241,0.05);
}
</style>
