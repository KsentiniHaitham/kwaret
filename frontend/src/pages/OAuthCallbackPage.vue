<template>
  <div style="min-height:80vh;display:flex;align-items:center;justify-content:center;">
    <div style="text-align:center;">
      <div v-if="error" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#f87171;font-size:14px;padding:16px 24px;border-radius:14px;max-width:360px;">
        {{ error }}<br>
        <span style="font-size:12px;color:#64748b;margin-top:8px;display:block;">Redirection dans quelques secondes…</span>
      </div>
      <div v-else style="display:flex;flex-direction:column;align-items:center;gap:16px;">
        <div style="width:40px;height:40px;border:3px solid rgba(129,140,248,0.2);border-top-color:#818cf8;border-radius:50%;animation:spin 1s linear infinite;"></div>
        <span style="color:#94a3b8;font-size:14px;">Connexion en cours…</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route  = useRoute()
const auth   = useAuthStore()
const error  = ref('')

onMounted(() => {
  const { token, user, error: err } = route.query

  if (err) {
    error.value = err === 'no_email'
      ? 'Aucune adresse email associée à ce compte.'
      : 'La connexion a échoué. Veuillez réessayer.'
    setTimeout(() => router.push('/login'), 3000)
    return
  }

  if (token && user) {
    try {
      const userData = JSON.parse(decodeURIComponent(user))
      auth.setFromOAuth(token, userData)
      router.push(route.query.redirect || '/')
    } catch {
      error.value = 'Erreur lors de la connexion.'
      setTimeout(() => router.push('/login'), 3000)
    }
  } else {
    router.push('/login')
  }
})
</script>

<style scoped>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
