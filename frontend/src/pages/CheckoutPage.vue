<template>
  <div style="max-width:680px;margin:0 auto;padding:40px 32px 80px;">
    <div style="margin-bottom:36px;">
      <h1 style="font-size:38px;font-weight:800;letter-spacing:-1px;">
        Finaliser la <span class="gradient-text">commande</span>
      </h1>
    </div>

    <!-- Panier vide -->
    <div v-if="cart.items.length === 0" style="text-align:center;padding:60px 0;">
      <p style="color:#475569;margin-bottom:16px;">Votre panier est vide.</p>
      <RouterLink to="/shop" style="color:#818cf8;text-decoration:none;">← Boutique</RouterLink>
    </div>

    <div v-else style="display:flex;flex-direction:column;gap:20px;">

      <!-- Récapitulatif -->
      <div class="card" style="padding:28px;">
        <div style="font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:1px;margin-bottom:18px;">Récapitulatif</div>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <div v-for="item in cart.items" :key="item.id"
            style="display:flex;justify-content:space-between;align-items:center;font-size:14px;">
            <span style="color:#64748b;">{{ item.name }} × {{ item.quantity }}</span>
            <span style="color:#e2e8f0;font-weight:500;">{{ (parseFloat(item.price) * item.quantity).toFixed(2) }} TND</span>
          </div>
        </div>
        <div style="border-top:1px solid rgba(255,255,255,0.07);margin-top:16px;padding-top:16px;display:flex;justify-content:space-between;align-items:center;">
          <span style="color:#e2e8f0;font-weight:600;">Total</span>
          <span class="gradient-text-cyan" style="font-size:26px;font-weight:800;">{{ cart.total.toFixed(2) }} TND</span>
        </div>
      </div>

      <!-- Paiement par portefeuille -->
      <div class="card" style="padding:28px;">
        <div style="font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:1px;margin-bottom:18px;">Moyen de paiement</div>

        <!-- Wallet card -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px;border-radius:16px;border:1px solid;"
          :style="canPay
            ? 'background:linear-gradient(135deg,rgba(99,102,241,0.12),rgba(168,85,247,0.08));border-color:rgba(129,140,248,0.35);'
            : 'background:rgba(239,68,68,0.05);border-color:rgba(239,68,68,0.2);'">
          <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,rgba(99,102,241,0.25),rgba(168,85,247,0.2));display:flex;align-items:center;justify-content:center;font-size:22px;">
              💰
            </div>
            <div>
              <div style="font-size:14px;font-weight:700;color:#e2e8f0;">Portefeuille Kwaret</div>
              <div style="font-size:13px;margin-top:3px;font-weight:600;"
                :style="canPay ? 'color:#34d399;' : 'color:#f87171;'">
                {{ balanceLoading ? '…' : `${balance.toFixed(2)} TND disponible` }}
              </div>
            </div>
          </div>
          <div style="text-align:right;flex-shrink:0;">
            <div v-if="canPay" style="font-size:12px;font-weight:700;padding:4px 12px;border-radius:100px;background:rgba(52,211,153,0.12);border:1px solid rgba(52,211,153,0.25);color:#34d399;">
              ✓ Solde suffisant
            </div>
            <div v-else style="font-size:12px;font-weight:700;padding:4px 12px;border-radius:100px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#f87171;white-space:nowrap;">
              Solde insuffisant
            </div>
            <div style="font-size:11px;color:#334155;margin-top:6px;">
              Requis : <strong style="color:#e2e8f0;">{{ cart.total.toFixed(2) }} TND</strong>
            </div>
          </div>
        </div>

        <!-- Cashback preview si des produits ont du cashback -->
        <div v-if="canPay && totalCashback > 0"
          style="margin-top:12px;background:rgba(168,85,247,0.08);border:1px solid rgba(168,85,247,0.2);border-radius:12px;padding:11px 14px;display:flex;align-items:center;gap:10px;">
          <span style="font-size:18px;">🎁</span>
          <div style="font-size:12px;color:#c084fc;">
            Vous recevrez <strong>{{ totalCashback.toFixed(2) }} TND</strong> de cashback après validation de la commande
          </div>
        </div>
      </div>

      <!-- Bloc insuffisant -->
      <div v-if="!canPay && !balanceLoading"
        style="background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.2);border-radius:16px;padding:20px 22px;">
        <div style="font-size:14px;font-weight:700;color:#f87171;margin-bottom:8px;">⚠️ Solde insuffisant</div>
        <div style="font-size:13px;color:#64748b;line-height:1.6;margin-bottom:16px;">
          Il vous manque <strong style="color:#fbbf24;">{{ (cart.total - balance).toFixed(2) }} TND</strong> pour passer cette commande.
          Rechargez votre portefeuille pour continuer.
        </div>
        <RouterLink to="/wallet"
          class="btn-primary"
          style="display:inline-flex;align-items:center;gap:8px;text-decoration:none;padding:11px 22px;font-size:13px;font-weight:700;">
          💳 Recharger mon portefeuille →
        </RouterLink>
      </div>

      <!-- Erreur API -->
      <div v-if="orderError"
        style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:14px;padding:14px 18px;color:#f87171;font-size:13px;">
        {{ orderError }}
      </div>

      <!-- Bouton commander -->
      <button
        @click="placeOrder"
        :disabled="loading || !canPay || balanceLoading"
        class="btn-primary"
        style="width:100%;padding:16px;font-size:16px;font-weight:700;display:flex;align-items:center;justify-content:center;gap:10px;border:none;transition:opacity .2s;"
        :style="(!canPay || loading || balanceLoading) ? 'opacity:.4;cursor:not-allowed;' : 'cursor:pointer;'"
      >
        <span v-if="loading" style="width:18px;height:18px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 1s linear infinite;display:inline-block;"></span>
        {{ loading ? 'Traitement…' : canPay ? 'Confirmer et payer →' : 'Solde insuffisant' }}
      </button>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api'
import { useCartStore } from '@/stores/cart'

const cart          = useCartStore()
const router        = useRouter()
const loading       = ref(false)
const balanceLoading = ref(true)
const balance       = ref(0)
const orderError    = ref('')

const canPay = computed(() => !balanceLoading.value && balance.value >= cart.total)

// Calcul du cashback total prévisible
const totalCashback = computed(() => {
  return cart.items.reduce((sum, item) => {
    const cb = parseFloat(item.cashback ?? 0)
    return sum + (parseFloat(item.price) * item.quantity * cb / 100)
  }, 0)
})

async function placeOrder() {
  if (!canPay.value) return
  loading.value = true
  orderError.value = ''
  try {
    const items = cart.items.map(i => ({ productId: i.id, quantity: i.quantity }))
    await api.post('/orders', { items, payWithWallet: true })
    cart.clear()
    router.push('/orders')
  } catch (e) {
    orderError.value = e.response?.data?.message || 'Erreur lors de la commande. Veuillez réessayer.'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  try {
    const r = await api.get('/wallet')
    balance.value = parseFloat(r.data.balance)
  } catch {}
  finally { balanceLoading.value = false }
})
</script>

<style scoped>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
