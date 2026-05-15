<template>
  <div style="min-height:80vh;display:flex;align-items:center;justify-content:center;padding:40px 32px;">
    <div style="width:100%;max-width:420px;">
      <div style="text-align:center;margin-bottom:36px;">
        <RouterLink to="/" style="text-decoration:none;">
          <span class="gradient-text" style="font-size:28px;font-weight:800;">✦ Kwaret</span>
        </RouterLink>
        <h2 style="font-size:24px;font-weight:800;color:#e2e8f0;margin-top:20px;letter-spacing:-.5px;">Nouveau mot de passe</h2>
        <p style="color:#475569;margin-top:6px;font-size:14px;">Choisissez un mot de passe sécurisé</p>
      </div>

      <div class="card" style="padding:36px;">
        <!-- Invalid token state -->
        <div v-if="!token" style="text-align:center;padding:12px 0;">
          <div style="font-size:48px;margin-bottom:16px;">❌</div>
          <p style="color:#f87171;font-weight:700;font-size:15px;margin-bottom:8px;">Lien invalide</p>
          <p style="color:#475569;font-size:13px;">Ce lien est invalide ou a expiré.</p>
          <RouterLink to="/forgot-password" style="display:inline-block;margin-top:20px;color:#818cf8;text-decoration:none;font-size:13px;font-weight:600;">
            Faire une nouvelle demande →
          </RouterLink>
        </div>

        <!-- Success state -->
        <div v-else-if="done" style="text-align:center;padding:12px 0;">
          <div style="font-size:48px;margin-bottom:16px;">✅</div>
          <p style="color:#34d399;font-weight:700;font-size:15px;margin-bottom:8px;">Mot de passe modifié !</p>
          <p style="color:#475569;font-size:13px;">Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.</p>
          <RouterLink to="/login" class="btn-primary" style="display:inline-block;margin-top:24px;text-decoration:none;padding:10px 24px;font-size:13px;">
            Se connecter →
          </RouterLink>
        </div>

        <!-- Form state -->
        <form v-else @submit.prevent="submit" style="display:flex;flex-direction:column;gap:18px;">
          <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Nouveau mot de passe</label>
            <input v-model="password" type="password" required minlength="8" placeholder="8 caractères minimum"
              style="width:100%;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:12px 16px;color:#e2e8f0;font-size:14px;outline:none;transition:border .2s;box-sizing:border-box;"
              onfocus="this.style.borderColor='rgba(129,140,248,0.5)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'" />
          </div>
          <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Confirmer le mot de passe</label>
            <input v-model="confirm" type="password" required placeholder="Répétez le mot de passe"
              style="width:100%;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:12px 16px;color:#e2e8f0;font-size:14px;outline:none;transition:border .2s;box-sizing:border-box;"
              onfocus="this.style.borderColor='rgba(129,140,248,0.5)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'" />
          </div>

          <div v-if="error" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#f87171;font-size:13px;padding:12px 16px;border-radius:12px;">
            {{ error }}
          </div>

          <button type="submit" :disabled="loading" class="btn-primary"
            style="width:100%;padding:14px;font-size:15px;display:flex;align-items:center;justify-content:center;gap:8px;border:none;cursor:pointer;">
            <span v-if="loading" style="width:16px;height:16px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 1s linear infinite;display:inline-block;"></span>
            {{ loading ? 'Enregistrement...' : 'Enregistrer le mot de passe →' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api'

const route    = useRoute()
const token    = ref('')
const password = ref('')
const confirm  = ref('')
const loading  = ref(false)
const error    = ref('')
const done     = ref(false)

onMounted(() => {
  token.value = route.query.token || ''
})

async function submit() {
  error.value = ''
  if (password.value !== confirm.value) {
    error.value = 'Les mots de passe ne correspondent pas'
    return
  }
  loading.value = true
  try {
    await api.post('/auth/reset-password', { token: token.value, password: password.value })
    done.value = true
  } catch (e) {
    error.value = e.response?.data?.message || 'Lien invalide ou expiré'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
