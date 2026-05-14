<template>
  <div style="position:relative; min-height:100vh;">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div style="position:relative; z-index:1;">
      <NavBar />
      <main>
        <RouterView />
      </main>
      <FooterBar />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import NavBar from '@/components/NavBar.vue'
import FooterBar from '@/components/FooterBar.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
onMounted(() => auth.fetchUser())
</script>
