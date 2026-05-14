<template>
  <!-- Backdrop -->
  <div @click.self="$emit('close')" style="position:fixed;inset:0;z-index:100;background:rgba(0,0,0,0.75);display:flex;align-items:center;justify-content:center;padding:16px;">
    <div style="width:100%;max-width:540px;background:rgba(10,14,30,0.98);border:1px solid rgba(129,140,248,0.25);border-radius:24px;overflow:hidden;box-shadow:0 24px 80px rgba(99,102,241,0.15);">

      <!-- Header -->
      <div style="padding:24px 28px 0;display:flex;align-items:center;justify-content:space-between;">
        <div>
          <div style="font-size:18px;font-weight:800;" class="gradient-text">Recharger mon portefeuille</div>
          <div style="font-size:12px;color:#475569;margin-top:4px;">Étape {{ step }} / 2</div>
        </div>
        <button @click="$emit('close')" style="background:none;border:none;color:#475569;cursor:pointer;font-size:20px;padding:4px;">✕</button>
      </div>

      <!-- Progress bar -->
      <div style="display:flex;gap:4px;padding:16px 28px 0;">
        <div v-for="s in 2" :key="s"
          style="flex:1;height:3px;border-radius:100px;transition:background .3s;"
          :style="s <= step ? 'background:linear-gradient(90deg,#6366f1,#a855f7)' : 'background:rgba(255,255,255,0.08)'"></div>
      </div>

      <!-- ── STEP 1 : Montant ── -->
      <div v-if="step===1" style="padding:28px;">
        <div style="font-size:13px;font-weight:600;color:#94a3b8;margin-bottom:20px;">Montant à recharger</div>

        <!-- Amount control -->
        <div style="text-align:center;margin-bottom:28px;">
          <div style="display:flex;align-items:center;justify-content:center;gap:16px;">
            <button @click="adjustAmount(-10)" class="amt-btn">−10</button>
            <div style="position:relative;">
              <input
                v-model.number="amount"
                type="number" min="1" step="1"
                style="width:140px;text-align:center;font-size:36px;font-weight:800;background:transparent;border:none;color:#e2e8f0;outline:none;"
              />
              <span style="position:absolute;right:-32px;top:50%;transform:translateY(-50%);font-size:14px;color:#475569;font-weight:600;">TND</span>
            </div>
            <button @click="adjustAmount(10)" class="amt-btn">+10</button>
          </div>
        </div>

        <!-- Quick presets -->
        <div style="display:flex;gap:8px;margin-bottom:28px;flex-wrap:wrap;justify-content:center;">
          <button v-for="q in [10,20,50,100,200]" :key="q" @click="amount=q"
            style="padding:7px 16px;border-radius:100px;font-size:13px;font-weight:600;cursor:pointer;transition:all .2s;border:1px solid;"
            :style="amount===q ? 'background:rgba(99,102,241,0.2);border-color:rgba(129,140,248,0.5);color:#a5b4fc;' : 'background:transparent;border-color:rgba(255,255,255,0.1);color:#475569;'"
          >{{ q }} TND</button>
        </div>

        <p v-if="amount < 1" style="text-align:center;color:#f87171;font-size:12px;margin-bottom:12px;">Montant minimum : 1 TND</p>

        <button @click="step=2" :disabled="amount < 1" class="btn-primary" style="width:100%;padding:14px;border:none;cursor:pointer;font-size:15px;">
          Choisir le moyen de paiement →
        </button>
      </div>

      <!-- ── STEP 2 : Méthode + justificatif ── -->
      <div v-else-if="step===2" style="padding:28px;max-height:75vh;overflow-y:auto;">

        <!-- Method grid -->
        <div style="font-size:13px;font-weight:600;color:#94a3b8;margin-bottom:14px;">Moyen de paiement</div>
        <div v-if="loadingMethods" style="text-align:center;padding:20px;color:#475569;font-size:13px;">Chargement…</div>
        <div v-else style="display:flex;flex-direction:column;gap:6px;margin-bottom:20px;">
          <div
            v-for="(m, key) in methods" :key="key"
            @click="m.enabled && (selectedMethod = key)"
            style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-radius:12px;cursor:pointer;transition:all .2s;border:1px solid;"
            :style="[
              !m.enabled ? 'opacity:.35;cursor:not-allowed;border-color:rgba(255,255,255,0.06);background:transparent;' :
              selectedMethod===key ? 'background:rgba(99,102,241,0.15);border-color:rgba(129,140,248,0.4);' :
              'background:rgba(255,255,255,0.02);border-color:rgba(255,255,255,0.06);'
            ]"
          >
            <div style="display:flex;align-items:center;gap:10px;">
              <span style="font-size:20px;">{{ methodIcon(key) }}</span>
              <div>
                <div style="font-size:13px;font-weight:600;color:#e2e8f0;">{{ m.label }}</div>
                <div style="font-size:11px;color:#475569;margin-top:1px;">
                  <template v-if="!m.enabled">Bientôt disponible</template>
                  <template v-else-if="m.fee === 0">Sans frais</template>
                  <template v-else-if="m.fee === -1">Frais réseau crypto</template>
                  <template v-else>Frais {{ m.fee }}%</template>
                </div>
              </div>
            </div>
            <div v-if="selectedMethod===key"
              style="width:20px;height:20px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a855f7);display:flex;align-items:center;justify-content:center;font-size:11px;color:#fff;flex-shrink:0;">✓</div>
          </div>
        </div>

        <!-- Instructions + justificatif (apparaissent après sélection) -->
        <Transition name="slide-down">
          <div v-if="selectedMethod && methods[selectedMethod]" style="display:flex;flex-direction:column;gap:14px;">

            <!-- Instructions -->
            <div style="background:rgba(99,102,241,0.08);border:1px solid rgba(129,140,248,0.2);border-radius:14px;padding:16px;">
              <div style="font-size:11px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;">
                📋 Instructions de virement
              </div>
              <div style="display:flex;flex-direction:column;gap:7px;">
                <template v-for="(val, key) in methods[selectedMethod].details" :key="key">
                  <!-- Texte simple (index numérique) -->
                  <div v-if="typeof val === 'string'" style="font-size:13px;color:#94a3b8;">{{ val }}</div>
                  <!-- Liste de notes (tableau) -->
                  <div v-else-if="Array.isArray(val)">
                    <div style="font-size:10px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">{{ key }}</div>
                    <ul style="margin:0;padding-left:18px;display:flex;flex-direction:column;gap:4px;">
                      <li v-for="note in val" :key="note" style="font-size:12px;color:#94a3b8;">{{ note }}</li>
                    </ul>
                  </div>
                  <!-- Valeur copiable (email, phone, address…) -->
                  <div v-else style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span style="font-size:10px;font-weight:600;color:#475569;text-transform:uppercase;min-width:48px;">{{ key }}</span>
                    <code style="font-size:13px;font-weight:700;color:#e2e8f0;background:rgba(255,255,255,0.06);padding:4px 10px;border-radius:8px;flex:1;">{{ val }}</code>
                    <button @click="copy(val)" style="background:none;border:none;cursor:pointer;font-size:11px;color:#6366f1;padding:0;font-weight:600;white-space:nowrap;">📋 Copier</button>
                  </div>
                </template>
              </div>
              <div v-if="methods[selectedMethod].fee > 0"
                style="margin-top:12px;padding-top:12px;border-top:1px solid rgba(255,255,255,0.06);font-size:12px;color:#fbbf24;display:flex;align-items:center;gap:6px;">
                ⚠️ Envoyez <strong style="font-size:14px;">{{ effectiveAmount.toFixed(2) }} TND</strong> (frais {{ methods[selectedMethod].fee }}% inclus)
              </div>
            </div>

            <!-- Sender info -->
            <div>
              <label style="font-size:11px;font-weight:600;color:#475569;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">
                Votre identifiant expéditeur (optionnel)
              </label>
              <input v-model="senderInfo"
                :placeholder="senderPlaceholder"
                style="width:100%;padding:10px 12px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:12px;color:#e2e8f0;font-size:13px;box-sizing:border-box;outline:none;transition:border .2s;"
                onfocus="this.style.borderColor='rgba(129,140,248,0.5)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'"
              />
            </div>

            <!-- Proof upload -->
            <div>
              <label style="font-size:11px;font-weight:600;color:#94a3b8;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">
                Justificatif de paiement <span style="color:#6366f1;">*</span>
              </label>
              <div
                @click="$refs.fileInput.click()"
                @dragover.prevent
                @drop.prevent="onDrop"
                style="border:2px dashed;border-radius:14px;padding:22px;text-align:center;cursor:pointer;transition:all .2s;"
                :style="proofFile
                  ? 'border-color:rgba(52,211,153,0.5);background:rgba(52,211,153,0.05);'
                  : 'border-color:rgba(129,140,248,0.25);background:rgba(99,102,241,0.04);'"
              >
                <div v-if="proofFile">
                  <div style="font-size:26px;margin-bottom:6px;">✅</div>
                  <div style="font-size:13px;font-weight:600;color:#34d399;">{{ proofFile.name }}</div>
                  <div style="font-size:11px;color:#475569;margin-top:3px;">{{ (proofFile.size/1024).toFixed(0) }} KB — cliquez pour changer</div>
                </div>
                <div v-else>
                  <div style="font-size:28px;margin-bottom:8px;">📸</div>
                  <div style="font-size:13px;font-weight:600;color:#94a3b8;">Glissez ou cliquez pour joindre</div>
                  <div style="font-size:11px;color:#475569;margin-top:4px;">Capture d'écran de la confirmation · PNG, JPG, PDF — max 5 MB</div>
                </div>
              </div>
              <input ref="fileInput" type="file" accept="image/*,application/pdf" style="display:none" @change="onFileChange" />
            </div>

          </div>
        </Transition>

        <!-- Summary + submit -->
        <div v-if="selectedMethod" style="margin-top:18px;">
          <!-- Amount recap -->
          <div style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:12px;padding:12px 16px;display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <span style="font-size:12px;color:#475569;">Montant à créditer</span>
            <span style="font-size:18px;font-weight:800;" class="gradient-text-cyan">{{ amount.toFixed(2) }} TND</span>
          </div>

          <p v-if="error" style="color:#f87171;font-size:13px;margin-bottom:12px;text-align:center;">{{ error }}</p>

          <div style="display:flex;gap:10px;">
            <button @click="step=1; proofFile=null; error=''" class="btn-ghost"
              style="flex:1;padding:12px;border:1px solid rgba(255,255,255,0.1);border-radius:12px;cursor:pointer;font-size:14px;">
              ← Retour
            </button>
            <button @click="submit" :disabled="submitting || !proofFile"
              class="btn-primary"
              style="flex:2;padding:12px;border:none;cursor:pointer;font-size:14px;transition:opacity .2s;"
              :style="!proofFile ? 'opacity:.5;cursor:not-allowed;' : ''">
              {{ submitting ? 'Envoi…' : 'Soumettre la demande ✓' }}
            </button>
          </div>
          <p v-if="!proofFile" style="text-align:center;font-size:11px;color:#475569;margin-top:8px;">
            Le justificatif est requis pour valider la demande
          </p>
        </div>

        <button v-else @click="step=1" class="btn-ghost"
          style="width:100%;margin-top:16px;padding:12px;border:1px solid rgba(255,255,255,0.1);border-radius:12px;cursor:pointer;font-size:14px;">
          ← Retour
        </button>
      </div>

      <!-- ── SUCCESS ── -->
      <div v-else style="padding:48px 28px;text-align:center;">
        <div style="font-size:56px;margin-bottom:16px;">🎉</div>
        <div style="font-size:20px;font-weight:800;color:#34d399;margin-bottom:10px;">Demande envoyée !</div>
        <div style="font-size:13px;color:#475569;line-height:1.8;max-width:320px;margin:0 auto 28px;">
          Votre recharge de <strong style="color:#e2e8f0;">{{ amount.toFixed(2) }} TND</strong> est en attente de validation.<br>
          Votre justificatif a bien été reçu.
        </div>
        <button @click="$emit('success')" class="btn-primary" style="padding:14px 32px;border:none;cursor:pointer;font-size:15px;">
          Fermer
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api'

const emit = defineEmits(['close', 'success'])

const step           = ref(1)
const amount         = ref(50)
const selectedMethod = ref('')
const senderInfo     = ref('')
const proofFile      = ref(null)
const submitting     = ref(false)
const error          = ref('')
const methods        = ref({})
const loadingMethods = ref(true)
const fileInput      = ref(null)

const methodIconMap = { paypal:'💳', flouci:'📲', ooredoo:'📡', d17:'🏦', poste:'📮', crypto:'₿' }
const methodIcon = k => methodIconMap[k] || '💰'

const senderPlaceholderMap = {
  paypal:  'Adresse email PayPal utilisée',
  ooredoo: 'Numéro Ooredoo expéditeur',
  d17:     'Numéro D17 expéditeur',
  poste:   'Numéro de compte CCP',
  crypto:  'Adresse wallet source',
}
const senderPlaceholder = computed(() => senderPlaceholderMap[selectedMethod.value] || 'Votre identifiant / numéro utilisé')

const effectiveAmount = computed(() => {
  const fee = methods.value[selectedMethod.value]?.fee ?? 0
  return fee > 0 ? amount.value * (1 + fee / 100) : amount.value
})

function adjustAmount(delta) {
  amount.value = Math.max(1, (amount.value || 0) + delta)
}

function onFileChange(e) {
  const f = e.target.files[0]
  if (!f) return
  if (f.size > 5 * 1024 * 1024) { error.value = 'Fichier trop volumineux (max 5 MB)'; return }
  proofFile.value = f
  error.value = ''
}

function onDrop(e) {
  const f = e.dataTransfer.files[0]
  if (!f) return
  if (f.size > 5 * 1024 * 1024) { error.value = 'Fichier trop volumineux (max 5 MB)'; return }
  proofFile.value = f
  error.value = ''
}

function copy(text) {
  navigator.clipboard.writeText(String(text)).catch(() => {})
}

async function submit() {
  if (!proofFile.value) { error.value = 'Veuillez joindre un justificatif'; return }
  error.value = ''
  submitting.value = true
  try {
    const fd = new FormData()
    fd.append('amount', amount.value)
    fd.append('method', selectedMethod.value)
    fd.append('senderInfo', senderInfo.value)
    fd.append('proof', proofFile.value)
    await api.post('/wallet/recharge', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    step.value = 3
  } catch (e) {
    const status = e.response?.status
    const msg    = e.response?.data?.message
    if (status === 401) error.value = 'Session expirée — veuillez vous reconnecter'
    else if (status === 413) error.value = 'Fichier trop volumineux pour le serveur'
    else error.value = msg || `Erreur ${status ?? 'réseau'} — vérifiez que le serveur est démarré`
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  try {
    const r = await api.get('/wallet/methods')
    methods.value = r.data
  } catch {}
  finally { loadingMethods.value = false }
})
</script>

<style scoped>
.amt-btn {
  width: 40px; height: 40px; border-radius: 50%;
  border: 1px solid rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.04); color: #94a3b8;
  font-size: 18px; cursor: pointer; transition: all .2s;
  display: flex; align-items: center; justify-content: center;
}
.amt-btn:hover { border-color: rgba(129,140,248,0.4); color: #a5b4fc; }
.slide-down-enter-active { transition: all .25s ease; }
.slide-down-enter-from   { opacity: 0; transform: translateY(-8px); }
</style>
