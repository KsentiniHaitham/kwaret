<template>
  <div style="display:flex;gap:20px;height:calc(100vh - 64px - 64px);min-height:500px;">

    <!-- ── Liste tickets ── -->
    <div style="width:300px;flex-shrink:0;display:flex;flex-direction:column;gap:8px;overflow-y:auto;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;flex-shrink:0;">
        <h1 style="font-size:20px;font-weight:800;">💬 Tickets support</h1>
        <button @click="loadTickets" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:8px;padding:5px 10px;color:#94a3b8;cursor:pointer;font-size:12px;">↻</button>
      </div>

      <!-- Tabs -->
      <div style="display:flex;gap:4px;background:rgba(255,255,255,0.03);border-radius:10px;padding:3px;flex-shrink:0;">
        <button v-for="tab in ['all','open','closed']" :key="tab" @click="activeTab=tab"
          style="flex:1;padding:5px;border-radius:8px;border:none;cursor:pointer;font-size:11px;font-weight:600;transition:all .2s;"
          :style="activeTab===tab ? 'background:rgba(99,102,241,0.2);color:#a5b4fc;' : 'background:transparent;color:#475569;'">
          {{ { all:'Tous', open:'Ouverts', closed:'Fermés' }[tab] }}
          <span v-if="tab!=='all'" style="margin-left:3px;font-size:10px;">({{ countByStatus(tab) }})</span>
        </button>
      </div>

      <div v-if="loading" style="display:flex;flex-direction:column;gap:6px;">
        <div v-for="n in 5" :key="n" style="height:72px;border-radius:12px;background:rgba(255,255,255,0.03);animation:pulse 1.5s infinite;"></div>
      </div>

      <div v-for="t in filteredTickets" :key="t.id"
        @click="openTicket(t)"
        style="padding:12px 14px;border-radius:14px;cursor:pointer;border:1px solid;transition:all .15s;flex-shrink:0;"
        :style="activeTicket?.id===t.id
          ? 'background:rgba(99,102,241,0.15);border-color:rgba(129,140,248,0.4);'
          : t.unread > 0
            ? 'background:rgba(99,102,241,0.05);border-color:rgba(99,102,241,0.2);'
            : 'background:rgba(255,255,255,0.02);border-color:rgba(255,255,255,0.06);'">
        <div style="display:flex;align-items:flex-start;gap:6px;">
          <!-- Avatar -->
          <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,rgba(99,102,241,0.3),rgba(168,85,247,0.3));display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#a5b4fc;flex-shrink:0;">
            {{ (t.user?.firstName?.[0]||'') + (t.user?.lastName?.[0]||'') }}
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:12px;font-weight:600;color:#e2e8f0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
              {{ t.user?.firstName }} {{ t.user?.lastName }}
            </div>
            <div style="font-size:11px;color:#475569;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:1px;">
              {{ t.subject }}
            </div>
          </div>
          <span v-if="t.unread > 0"
            style="min-width:18px;height:18px;border-radius:100px;background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;padding:0 5px;flex-shrink:0;">
            {{ t.unread }}
          </span>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:7px;">
          <span style="font-size:10px;font-weight:700;padding:1px 7px;border-radius:100px;"
            :style="t.status==='open'
              ? 'background:rgba(52,211,153,0.12);color:#34d399;border:1px solid rgba(52,211,153,0.25);'
              : 'background:rgba(71,85,105,0.15);color:#475569;border:1px solid rgba(71,85,105,0.25);'">
            {{ t.status==='open' ? '● Ouvert' : '✓ Fermé' }}
          </span>
          <span style="font-size:10px;color:#334155;">{{ timeAgo(t.createdAt) }}</span>
        </div>
        <div v-if="t.lastMessage" style="font-size:11px;color:#475569;margin-top:5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
          {{ t.lastMessage.isAdmin ? '👨‍💼 ' : '👤 ' }}{{ t.lastMessage.content || '📎 Pièce jointe' }}
        </div>
      </div>
    </div>

    <!-- ── Zone chat ── -->
    <div style="flex:1;min-width:0;display:flex;flex-direction:column;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:20px;overflow:hidden;">

      <div v-if="!activeTicket" style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#334155;">
        <div style="font-size:48px;margin-bottom:16px;">💬</div>
        <div style="font-size:15px;font-weight:600;color:#475569;">Sélectionnez un ticket</div>
      </div>

      <template v-else>
        <!-- Header -->
        <div style="padding:12px 18px;border-bottom:1px solid rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
          <div>
            <div style="font-size:13px;font-weight:700;color:#e2e8f0;">{{ activeTicket.subject }}</div>
            <div style="font-size:11px;color:#475569;margin-top:1px;">
              {{ activeTicket.user?.firstName }} {{ activeTicket.user?.lastName }} · {{ activeTicket.user?.email }}
              <span v-if="activeTicket.rating" style="margin-left:8px;">{{ '⭐'.repeat(activeTicket.rating) }} ({{ activeTicket.rating }}/5)</span>
            </div>
          </div>
          <div style="display:flex;gap:8px;align-items:center;">
            <button v-if="activeTicket.status==='open'" @click="closeTicket"
              style="padding:6px 14px;border-radius:10px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#f87171;font-size:11px;font-weight:700;cursor:pointer;">
              🔒 Fermer
            </button>
            <button v-else @click="reopenTicket"
              style="padding:6px 14px;border-radius:10px;background:rgba(52,211,153,0.1);border:1px solid rgba(52,211,153,0.25);color:#34d399;font-size:11px;font-weight:700;cursor:pointer;">
              🔓 Rouvrir
            </button>
          </div>
        </div>

        <!-- Messages -->
        <div ref="messagesEl" style="flex:1;overflow-y:auto;padding:18px;display:flex;flex-direction:column;gap:12px;">
          <div v-if="loadingMessages" style="display:flex;justify-content:center;padding:24px;">
            <div style="width:22px;height:22px;border:2px solid rgba(99,102,241,0.3);border-top-color:#6366f1;border-radius:50%;animation:spin 1s linear infinite;"></div>
          </div>
          <template v-else>
            <div v-for="msg in messages" :key="msg.id"
              style="display:flex;flex-direction:column;"
              :style="msg.isAdmin ? 'align-items:flex-end;' : 'align-items:flex-start;'">
              <div style="max-width:72%;">
                <div style="padding:10px 14px;border-radius:18px;font-size:13px;line-height:1.55;white-space:pre-wrap;word-break:break-word;"
                  :style="msg.isAdmin
                    ? 'background:linear-gradient(135deg,rgba(99,102,241,0.6),rgba(168,85,247,0.5));border-bottom-right-radius:4px;color:#fff;'
                    : 'background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-bottom-left-radius:4px;color:#e2e8f0;'">
                  {{ msg.content }}
                </div>
                <div v-if="msg.attachmentPath" style="margin-top:6px;">
                  <img v-if="isImage(msg.attachmentPath)"
                    :src="apiBase + msg.attachmentPath"
                    style="max-width:240px;border-radius:12px;cursor:pointer;"
                    @click="previewImg = apiBase + msg.attachmentPath" />
                  <a v-else :href="apiBase + msg.attachmentPath" target="_blank"
                    style="font-size:12px;color:#818cf8;display:inline-flex;align-items:center;gap:6px;background:rgba(99,102,241,0.1);padding:5px 12px;border-radius:8px;text-decoration:none;">
                    📎 Pièce jointe
                  </a>
                </div>
                <div style="font-size:10px;color:#334155;margin-top:4px;padding:0 4px;text-align:right;">
                  {{ msg.isAdmin ? '👨‍💼 ' : '👤 ' }}{{ formatTime(msg.createdAt) }}
                  <span v-if="msg.isAdmin && msg.isRead" style="color:#34d399;margin-left:4px;">✓✓</span>
                </div>
              </div>
            </div>
          </template>
        </div>

        <!-- Input -->
        <div v-if="activeTicket.status==='open'" style="padding:12px 16px;border-top:1px solid rgba(255,255,255,0.06);display:flex;flex-direction:column;gap:8px;">
          <div v-if="attachFile" style="display:flex;align-items:center;gap:8px;background:rgba(99,102,241,0.08);padding:7px 12px;border-radius:10px;border:1px solid rgba(99,102,241,0.2);">
            <span style="font-size:12px;color:#a5b4fc;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">📎 {{ attachFile.name }}</span>
            <button @click="attachFile=null" style="background:none;border:none;color:#f87171;cursor:pointer;font-size:15px;">✕</button>
          </div>
          <div style="display:flex;align-items:flex-end;gap:8px;">
            <button @click="$refs.attachInput.click()"
              style="width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);cursor:pointer;font-size:15px;flex-shrink:0;display:flex;align-items:center;justify-content:center;">📎</button>
            <input ref="attachInput" type="file" accept="image/*,application/pdf" style="display:none" @change="onAttach" />
            <textarea v-model="inputText" @keydown.enter.exact.prevent="send"
              placeholder="Répondre au client… (Entrée pour envoyer)"
              maxlength="300" rows="1"
              style="flex:1;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:9px 13px;color:#e2e8f0;font-size:13px;resize:none;outline:none;font-family:inherit;line-height:1.5;max-height:100px;overflow-y:auto;"
              @input="autoResize"></textarea>
            <button @click="send" :disabled="sending || (!inputText.trim() && !attachFile)"
              style="width:36px;height:36px;border-radius:10px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;"
              :style="(!inputText.trim() && !attachFile) ? 'background:rgba(255,255,255,0.05);opacity:.4;' : 'background:linear-gradient(135deg,#6366f1,#a855f7);'">
              {{ sending ? '…' : '➤' }}
            </button>
          </div>
        </div>
        <div v-else style="padding:14px;text-align:center;border-top:1px solid rgba(255,255,255,0.06);font-size:12px;color:#475569;">
          Ticket fermé{{ activeTicket.rating ? ` · Note client : ${'⭐'.repeat(activeTicket.rating)} (${activeTicket.rating}/5)` : '' }}
        </div>
      </template>
    </div>

    <!-- Image preview -->
    <div v-if="previewImg" @click="previewImg=null"
      style="position:fixed;inset:0;z-index:500;background:rgba(0,0,0,0.9);display:flex;align-items:center;justify-content:center;cursor:zoom-out;">
      <img :src="previewImg" style="max-width:90vw;max-height:90vh;border-radius:12px;" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue'
import api from '@/api'

const apiBase       = 'http://localhost:8000'
const tickets       = ref([])
const loading       = ref(true)
const activeTab     = ref('open')
const activeTicket  = ref(null)
const messages      = ref([])
const loadingMessages = ref(false)
const inputText     = ref('')
const attachFile    = ref(null)
const sending       = ref(false)
const messagesEl    = ref(null)
const attachInput   = ref(null)
const previewImg    = ref(null)
let   pollInterval  = null

function isImage(p) { return /\.(png|jpg|jpeg|gif|webp)$/i.test(p) }
function timeAgo(d) {
  const diff = Math.floor((Date.now() - new Date(d)) / 1000)
  if (diff < 60) return 'À l\'instant'
  if (diff < 3600) return `${Math.floor(diff/60)} min`
  if (diff < 86400) return `${Math.floor(diff/3600)} h`
  return new Date(d).toLocaleDateString('fr-FR', { day:'2-digit', month:'short' })
}
function formatTime(d) {
  return new Date(d).toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' })
}
function countByStatus(s) { return tickets.value.filter(t => t.status===s).length }

const filteredTickets = computed(() => {
  if (activeTab.value === 'all') return tickets.value
  return tickets.value.filter(t => t.status === activeTab.value)
})

async function loadTickets() {
  try {
    const r = await api.get('/admin/tickets')
    tickets.value = r.data
    if (activeTicket.value) {
      const fresh = r.data.find(t => t.id === activeTicket.value.id)
      if (fresh) activeTicket.value.unread = fresh.unread
    }
  } catch {}
  loading.value = false
}

async function openTicket(t) {
  activeTicket.value = t
  messages.value = []
  loadingMessages.value = true
  try {
    const r = await api.get(`/admin/tickets/${t.id}`)
    activeTicket.value = { ...t, ...r.data }
    messages.value = r.data.messages
    t.unread = 0
    await nextTick(); scrollBottom()
  } catch {}
  loadingMessages.value = false
}

async function send() {
  if (sending.value || (!inputText.value.trim() && !attachFile.value)) return
  sending.value = true
  try {
    const fd = new FormData()
    fd.append('content', inputText.value.trim())
    if (attachFile.value) fd.append('attachment', attachFile.value)
    const r = await api.post(`/admin/tickets/${activeTicket.value.id}/messages`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    messages.value.push(r.data)
    inputText.value = ''
    attachFile.value = null
    await nextTick(); scrollBottom()
  } catch {}
  sending.value = false
}

async function closeTicket() {
  if (!confirm('Fermer ce ticket ?')) return
  try {
    await api.patch(`/admin/tickets/${activeTicket.value.id}/close`)
    activeTicket.value.status = 'closed'
    const r = await api.get(`/admin/tickets/${activeTicket.value.id}`)
    messages.value = r.data.messages
    await nextTick(); scrollBottom()
    loadTickets()
  } catch {}
}

async function reopenTicket() {
  try {
    await api.patch(`/admin/tickets/${activeTicket.value.id}/reopen`)
    activeTicket.value.status = 'open'
    loadTickets()
  } catch {}
}

function onAttach(e) {
  const f = e.target.files[0]
  if (f && f.size <= 10 * 1024 * 1024) attachFile.value = f
}
function autoResize(e) {
  e.target.style.height = 'auto'
  e.target.style.height = Math.min(e.target.scrollHeight, 100) + 'px'
}
function scrollBottom() {
  if (messagesEl.value) messagesEl.value.scrollTop = messagesEl.value.scrollHeight
}

async function pollMessages() {
  if (!activeTicket.value || activeTicket.value.status === 'closed') return
  try {
    const r = await api.get(`/admin/tickets/${activeTicket.value.id}`)
    if (r.data.messages.length > messages.value.length) {
      messages.value = r.data.messages
      await nextTick(); scrollBottom()
    }
  } catch {}
  loadTickets()
}

onMounted(() => { loadTickets(); pollInterval = setInterval(pollMessages, 15_000) })
onUnmounted(() => clearInterval(pollInterval))
</script>

<style scoped>
@keyframes pulse { 0%,100%{opacity:.5} 50%{opacity:.2} }
@keyframes spin   { to { transform: rotate(360deg); } }
</style>
