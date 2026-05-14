<template>
  <div ref="wrapper" style="position:relative;width:100%;">
    <!-- Input -->
    <div style="position:relative;">
      <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#334155;font-size:14px;pointer-events:none;">🔍</span>
      <input
        ref="inputEl"
        v-model="query"
        type="text"
        placeholder="Rechercher un produit..."
        @focus="onFocus"
        @keydown.escape="close"
        @keydown.down.prevent="moveDown"
        @keydown.up.prevent="moveUp"
        @keydown.enter.prevent="selectActive"
        style="width:100%;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:9px 14px 9px 38px;color:#e2e8f0;font-size:13px;outline:none;transition:border .2s;box-sizing:border-box;"
        :style="open ? 'border-color:rgba(129,140,248,0.5);border-bottom-left-radius:0;border-bottom-right-radius:0;border-bottom-color:transparent;' : ''"
        @focus.once="() => {}"
      />
      <!-- Clear -->
      <button v-if="query" @click="query=''; inputEl.focus()"
        style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#334155;cursor:pointer;font-size:16px;line-height:1;padding:2px 4px;transition:color .2s;"
        onmouseover="this.style.color='#94a3b8'" onmouseout="this.style.color='#334155'">✕</button>
    </div>

    <!-- Dropdown -->
    <Transition name="drop">
      <div v-if="open" style="position:absolute;top:100%;left:0;right:0;background:#0d1117;border:1px solid rgba(129,140,248,0.4);border-top:none;border-bottom-left-radius:14px;border-bottom-right-radius:14px;overflow:hidden;z-index:200;box-shadow:0 20px 60px rgba(0,0,0,0.6);">

        <!-- Loading -->
        <div v-if="loading" style="padding:16px;display:flex;flex-direction:column;gap:8px;">
          <div v-for="n in 3" :key="n" style="height:48px;border-radius:10px;background:rgba(255,255,255,0.04);animation:pulse 1.5s infinite;display:flex;align-items:center;gap:10px;padding:0 12px;">
            <div style="width:36px;height:36px;border-radius:8px;background:rgba(255,255,255,0.06);flex-shrink:0;"></div>
            <div style="flex:1;">
              <div style="height:10px;border-radius:4px;background:rgba(255,255,255,0.06);margin-bottom:6px;width:60%;"></div>
              <div style="height:8px;border-radius:4px;background:rgba(255,255,255,0.04);width:35%;"></div>
            </div>
          </div>
        </div>

        <!-- No results -->
        <div v-else-if="results.length === 0 && query.length >= 2" style="padding:20px;text-align:center;color:#334155;font-size:13px;">
          <div style="font-size:28px;margin-bottom:8px;">🔍</div>
          Aucun produit pour « {{ query }} »
        </div>

        <!-- Results list -->
        <div v-else-if="results.length > 0" style="max-height:380px;overflow-y:auto;">
          <RouterLink
            v-for="(p, idx) in results"
            :key="p.id"
            :to="`/product/${p.slug}`"
            @click="close"
            style="display:flex;align-items:center;gap:12px;padding:10px 14px;text-decoration:none;transition:background .15s;"
            :style="idx === activeIdx
              ? 'background:rgba(129,140,248,0.12);'
              : 'background:transparent;'"
            @mouseenter="activeIdx = idx"
          >
            <!-- Thumbnail -->
            <div style="width:44px;height:44px;border-radius:10px;overflow:hidden;flex-shrink:0;background:linear-gradient(135deg,rgba(99,102,241,0.15),rgba(168,85,247,0.15));display:flex;align-items:center;justify-content:center;font-size:20px;">
              <img v-if="p.image" :src="p.image" :alt="p.name" style="width:100%;height:100%;object-fit:cover;" />
              <span v-else>{{ iconFor(p.category?.slug) }}</span>
            </div>

            <!-- Info -->
            <div style="flex:1;min-width:0;">
              <div style="font-size:13px;font-weight:600;color:#e2e8f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <!-- Highlight matching text -->
                <span v-html="highlight(p.name)"></span>
              </div>
              <div style="font-size:11px;color:#475569;margin-top:2px;">{{ p.category?.name }}</div>
            </div>

            <!-- Price -->
            <div style="text-align:right;flex-shrink:0;">
              <div class="gradient-text-cyan" style="font-size:14px;font-weight:800;">{{ parseFloat(p.price).toFixed(2) }} TND</div>
              <div v-if="p.stock === 0" style="font-size:10px;color:#f87171;margin-top:2px;">Indisponible</div>
            </div>
          </RouterLink>

          <!-- View all -->
          <RouterLink
            :to="`/shop?q=${encodeURIComponent(query)}`"
            @click="close"
            style="display:flex;align-items:center;justify-content:center;gap:6px;padding:12px;border-top:1px solid rgba(255,255,255,0.06);text-decoration:none;color:#818cf8;font-size:12px;font-weight:600;transition:background .15s;"
            onmouseover="this.style.background='rgba(129,140,248,0.06)'" onmouseout="this.style.background='transparent'"
          >
            Voir tous les résultats pour « {{ query }} » →
          </RouterLink>
        </div>

      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api'

const query     = ref('')
const results   = ref([])
const loading   = ref(false)
const open      = ref(false)
const activeIdx = ref(-1)
const inputEl   = ref(null)
const wrapper   = ref(null)

let allProducts = []
let debounceTimer = null

// Load all products once on mount for instant local filtering
onMounted(async () => {
  const res = await api.get('/products').catch(() => ({ data: [] }))
  allProducts = res.data
})

watch(query, (val) => {
  activeIdx.value = -1
  clearTimeout(debounceTimer)
  if (val.trim().length < 2) {
    results.value = []
    open.value = false
    return
  }
  loading.value = true
  open.value = true
  debounceTimer = setTimeout(() => {
    const q = val.toLowerCase()
    results.value = allProducts
      .filter(p => p.name.toLowerCase().includes(q) || p.description?.toLowerCase().includes(q) || p.category?.name.toLowerCase().includes(q))
      .slice(0, 8)
    loading.value = false
  }, 150)
})

function onFocus() {
  if (query.value.trim().length >= 2) open.value = true
}

function close() {
  open.value = false
  activeIdx.value = -1
}

function moveDown() {
  if (!open.value) return
  activeIdx.value = Math.min(activeIdx.value + 1, results.value.length - 1)
}

function moveUp() {
  activeIdx.value = Math.max(activeIdx.value - 1, -1)
}

function selectActive() {
  if (activeIdx.value >= 0 && results.value[activeIdx.value]) {
    const p = results.value[activeIdx.value]
    useRouter().push(`/product/${p.slug}`)
    close()
    query.value = ''
  }
}

function highlight(text) {
  if (!query.value) return text
  const regex = new RegExp(`(${query.value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi')
  return text.replace(regex, '<mark style="background:rgba(129,140,248,0.3);color:#a5b4fc;border-radius:3px;padding:0 2px;">$1</mark>')
}

function iconFor(slug = '') {
  if (slug?.includes('gaming')) return '🎮'
  if (slug?.includes('streaming')) return '📺'
  if (slug?.includes('ia')) return '🤖'
  return '🎵'
}

// Close on outside click
function handleClickOutside(e) {
  if (wrapper.value && !wrapper.value.contains(e.target)) close()
}
onMounted(() => document.addEventListener('mousedown', handleClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', handleClickOutside))
</script>

<style scoped>
.drop-enter-active, .drop-leave-active { transition: opacity .15s, transform .15s; }
.drop-enter-from, .drop-leave-to { opacity: 0; transform: translateY(-6px); }
@keyframes pulse { 0%,100%{opacity:.5} 50%{opacity:.2} }
</style>
