<template>
  <div style="min-height:80vh;display:flex;align-items:center;justify-content:center;padding:40px 32px;">
    <div style="width:100%;max-width:420px;">
      <div style="text-align:center;margin-bottom:36px;">
        <RouterLink to="/" style="text-decoration:none;">
          <span class="gradient-text" style="font-size:28px;font-weight:800;">✦ Kwaret</span>
        </RouterLink>
        <h2 style="font-size:26px;font-weight:800;color:#e2e8f0;margin-top:20px;letter-spacing:-.5px;">Connexion</h2>
        <p style="color:#475569;margin-top:6px;font-size:14px;">Accédez à votre espace client</p>
      </div>

      <div class="card" style="padding:36px;">

        <!-- Boutons OAuth -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:24px;">
          <a :href="oauthUrl('discord')" class="social-btn" style="--c:#5865F2;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057c.003.022.015.041.031.056a19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>
            Discord
          </a>
          <a :href="oauthUrl('github')" class="social-btn" style="--c:#e2e8f0;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0 1 12 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/></svg>
            GitHub
          </a>
        </div>

        <!-- Divider -->
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
          <div style="flex:1;height:1px;background:rgba(255,255,255,0.07);"></div>
          <span style="color:#334155;font-size:12px;font-weight:600;">OU</span>
          <div style="flex:1;height:1px;background:rgba(255,255,255,0.07);"></div>
        </div>

        <!-- Formulaire email -->
        <form @submit.prevent="login" style="display:flex;flex-direction:column;gap:20px;">
          <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Adresse email</label>
            <input v-model="form.email" type="email" required placeholder="votre@email.com" class="form-input"/>
          </div>
          <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Mot de passe</label>
            <input v-model="form.password" type="password" required placeholder="••••••••" class="form-input"/>
          </div>

          <div v-if="error" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#f87171;font-size:13px;padding:12px 16px;border-radius:12px;">
            {{ error }}
          </div>

          <button type="submit" :disabled="loading" class="btn-primary" style="width:100%;padding:14px;font-size:15px;display:flex;align-items:center;justify-content:center;gap:8px;border:none;cursor:pointer;">
            <span v-if="loading" style="width:16px;height:16px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 1s linear infinite;display:inline-block;"></span>
            {{ loading ? 'Connexion...' : 'Se connecter →' }}
          </button>
        </form>

        <p style="margin-top:24px;text-align:center;color:#475569;font-size:13px;">
          Pas encore de compte ?
          <RouterLink to="/register" style="color:#818cf8;text-decoration:none;font-weight:600;">Créer un compte</RouterLink>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth    = useAuthStore()
const router  = useRouter()
const route   = useRoute()

const form    = ref({ email: '', password: '' })
const loading = ref(false)
const error   = ref(route.query.error === 'no_email'
  ? 'Aucune adresse email associée à ce compte social.'
  : route.query.error === 'oauth_failed'
    ? 'La connexion sociale a échoué. Réessayez.'
    : '')

const BACKEND = import.meta.env.VITE_API_URL?.replace('/api', '') ?? 'http://localhost:8000'
const oauthUrl = (provider) => `${BACKEND}/auth/${provider}`

async function login() {
  loading.value = true
  error.value = ''
  try {
    await auth.login(form.value.email, form.value.password)
    router.push(route.query.redirect || '/')
  } catch {
    error.value = 'Email ou mot de passe incorrect'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.form-input {
  width: 100%;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 12px;
  padding: 12px 16px;
  color: #e2e8f0;
  font-size: 14px;
  outline: none;
  transition: border .2s;
  box-sizing: border-box;
}
.form-input:focus { border-color: rgba(129,140,248,0.5); }
.form-input::placeholder { color: #334155; }

.social-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 11px 16px;
  border-radius: 12px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.09);
  color: #94a3b8;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  transition: all .2s;
}
.social-btn:hover {
  background: rgba(255,255,255,0.08);
  border-color: color-mix(in srgb, var(--c) 40%, transparent);
  color: var(--c);
  transform: translateY(-1px);
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
