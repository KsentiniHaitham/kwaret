<template>
  <div style="max-width:960px;margin:0 auto;padding:32px 24px 80px;display:flex;gap:20px;height:calc(100vh - 64px - 80px);">

    <!-- ── Sidebar liste tickets ── -->
    <div style="width:280px;flex-shrink:0;display:flex;flex-direction:column;gap:10px;">
      <h2 style="font-size:20px;font-weight:800;letter-spacing:-.5px;margin-bottom:4px;">
        💬 <span class="gradient-text">Support</span>
      </h2>
      <p style="font-size:12px;color:#475569;margin-bottom:12px;">Vos conversations avec l'équipe</p>

      <div v-if="loadingTickets" style="display:flex;flex-direction:column;gap:8px;">
        <div v-for="n in 3" :key="n" style="height:72px;border-radius:14px;background:rgba(255,255,255,0.03);animation:pulse 1.5s infinite;"></div>
      </div>

      <div v-else-if="tickets.length===0" style="text-align:center;padding:32px 16px;color:#334155;font-size:13px;">
        <div style="font-size:32px;margin-bottom:10px;">🔕</div>
        Aucune conversation pour l'instant
      </div>

      <div v-else style="display:flex;flex-direction:column;gap:6px;overflow-y:auto;flex:1;">
        <div v-for="t in tickets" :key="t.id"
          @click="openTicket(t)"
          style="padding:12px 14px;border-radius:14px;cursor:pointer;border:1px solid;transition:all .2s;"
          :style="activeTicket?.id===t.id
            ? 'background:rgba(99,102,241,0.15);border-color:rgba(129,140,248,0.4);'
            : 'background:rgba(255,255,255,0.02);border-color:rgba(255,255,255,0.06);'">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:6px;">
            <div style="font-size:13px;font-weight:600;color:#e2e8f0;line-height:1.3;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
              {{ t.subject }}
            </div>
            <span v-if="t.unread > 0"
              style="min-width:18px;height:18px;border-radius:100px;background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;padding:0 5px;flex-shrink:0;">
              {{ t.unread }}
            </span>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px;">
            <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:100px;"
              :style="t.status==='open'
                ? 'background:rgba(52,211,153,0.12);color:#34d399;border:1px solid rgba(52,211,153,0.25);'
                : 'background:rgba(71,85,105,0.2);color:#475569;border:1px solid rgba(71,85,105,0.3);'">
              {{ t.status==='open' ? '● Ouvert' : '✓ Fermé' }}
            </span>
            <span style="font-size:10px;color:#334155;">{{ timeAgo(t.createdAt) }}</span>
          </div>
          <div v-if="t.lastMessage" style="font-size:11px;color:#475569;margin-top:5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            {{ t.lastMessage.isAdmin ? '👨‍💼 ' : '👤 ' }}{{ t.lastMessage.content || '📎 Pièce jointe' }}
          </div>
        </div>
      </div>
    </div>

    <!-- ── Zone chat ── -->
    <div style="flex:1;min-width:0;display:flex;flex-direction:column;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:20px;overflow:hidden;">

      <!-- Pas de ticket sélectionné -->
      <div v-if="!activeTicket" style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#334155;">
        <div style="font-size:48px;margin-bottom:16px;">💬</div>
        <div style="font-size:15px;font-weight:600;">Sélectionnez une conversation</div>
        <div style="font-size:13px;margin-top:6px;color:#334155;">Ou attendez qu'un ticket soit créé automatiquement</div>
      </div>

      <template v-else>
        <!-- Header chat -->
        <div style="padding:14px 20px;border-bottom:1px solid rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
          <div>
            <div style="font-size:14px;font-weight:700;color:#e2e8f0;">{{ activeTicket.subject }}</div>
            <div style="font-size:11px;margin-top:2px;" :style="activeTicket.status==='open' ? 'color:#34d399;' : 'color:#475569;'">
              {{ activeTicket.status==='open' ? '● En ligne — Support Kwaret' : '✓ Conversation fermée' }}
            </div>
          </div>
          <span style="font-size:10px;font-weight:700;padding:3px 10px;border-radius:100px;"
            :style="activeTicket.status==='open'
              ? 'background:rgba(52,211,153,0.12);color:#34d399;border:1px solid rgba(52,211,153,0.25);'
              : 'background:rgba(71,85,105,0.2);color:#475569;border:1px solid rgba(71,85,105,0.3);'">
            {{ activeTicket.status==='open' ? 'Ouvert' : 'Fermé' }}
          </span>
        </div>

        <!-- Messages -->
        <div ref="messagesEl" style="flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:12px;">
          <div v-if="loadingMessages" style="display:flex;justify-content:center;padding:24px;">
            <div style="width:24px;height:24px;border:2px solid rgba(99,102,241,0.3);border-top-color:#6366f1;border-radius:50%;animation:spin 1s linear infinite;"></div>
          </div>

          <template v-else>
            <div v-for="msg in messages" :key="msg.id"
              style="display:flex;flex-direction:column;"
              :style="msg.isAdmin ? 'align-items:flex-start;' : 'align-items:flex-end;'">
              <div style="max-width:75%;">
                <!-- Bulle -->
                <div style="padding:10px 14px;border-radius:18px;font-size:13px;line-height:1.55;white-space:pre-wrap;word-break:break-word;"
                  :style="msg.isAdmin
                    ? 'background:rgba(99,102,241,0.15);border:1px solid rgba(99,102,241,0.25);border-bottom-left-radius:4px;color:#e2e8f0;'
                    : 'background:linear-gradient(135deg,rgba(99,102,241,0.6),rgba(168,85,247,0.5));border-bottom-right-radius:4px;color:#fff;'">
                  {{ msg.content }}
                </div>
                <!-- Pièce jointe -->
                <div v-if="msg.attachmentPath" style="margin-top:6px;">
                  <img v-if="isImage(msg.attachmentPath)"
                    :src="apiBase + msg.attachmentPath"
                    style="max-width:260px;border-radius:12px;cursor:pointer;"
                    @click="previewImg = apiBase + msg.attachmentPath" />
                  <a v-else :href="apiBase + msg.attachmentPath" target="_blank"
                    style="font-size:12px;color:#818cf8;display:flex;align-items:center;gap:6px;background:rgba(99,102,241,0.1);padding:6px 12px;border-radius:10px;text-decoration:none;border:1px solid rgba(99,102,241,0.2);">
                    📎 Voir le fichier
                  </a>
                </div>
                <!-- Heure -->
                <div style="font-size:10px;color:#334155;margin-top:4px;padding:0 4px;">
                  {{ msg.isAdmin ? '👨‍💼 Support · ' : '' }}{{ formatTime(msg.createdAt) }}
                </div>
              </div>
            </div>
          </template>
        </div>

        <!-- Rating si fermé et pas encore noté -->
        <div v-if="activeTicket.status==='closed' && !activeTicket.rating"
          style="padding:16px 20px;border-top:1px solid rgba(255,255,255,0.06);background:rgba(99,102,241,0.05);">
          <div style="font-size:13px;font-weight:600;color:#94a3b8;margin-bottom:10px;text-align:center;">
            La discussion est fermée. Notez votre expérience :
          </div>
          <div style="display:flex;justify-content:center;gap:8px;margin-bottom:10px;">
            <span v-for="s in 5" :key="s"
              @click="hoverRating=s; pendingRating=s"
              @mouseover="hoverRating=s" @mouseleave="hoverRating=0"
              style="font-size:28px;cursor:pointer;transition:transform .15s;"
              :style="(hoverRating||pendingRating) >= s ? 'transform:scale(1.2);' : 'opacity:.4;'">
              ⭐
            </span>
          </div>
          <button v-if="pendingRating" @click="submitRating"
            class="btn-primary" style="width:100%;padding:10px;border:none;cursor:pointer;font-size:13px;">
            Envoyer mon avis ({{ pendingRating }}/5) →
          </button>
        </div>

        <!-- Already rated -->
        <div v-else-if="activeTicket.status==='closed' && activeTicket.rating"
          style="padding:14px 20px;border-top:1px solid rgba(255,255,255,0.06);text-align:center;font-size:13px;color:#475569;">
          Vous avez noté cette conversation {{ activeTicket.rating }}/5 ⭐ · Merci !
        </div>

        <!-- Input zone si ouvert -->
        <div v-else style="padding:12px 16px;border-top:1px solid rgba(255,255,255,0.06);display:flex;flex-direction:column;gap:8px;">
          <!-- Preview pièce jointe -->
          <div v-if="attachFile" style="display:flex;align-items:center;gap:8px;background:rgba(99,102,241,0.08);padding:8px 12px;border-radius:10px;border:1px solid rgba(99,102,241,0.2);">
            <span style="font-size:13px;color:#a5b4fc;">📎 {{ attachFile.name }}</span>
            <button @click="attachFile=null" style="background:none;border:none;color:#f87171;cursor:pointer;font-size:16px;padding:0;margin-left:auto;">✕</button>
          </div>
          <div style="display:flex;align-items:flex-end;gap:10px;">
            <!-- Attachment btn -->
            <button @click="$refs.attachInput.click()"
              style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);cursor:pointer;font-size:16px;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:background .2s;"
              onmouseover="this.style.background='rgba(99,102,241,0.15)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">
              📎
            </button>
            <input ref="attachInput" type="file" accept="image/*,application/pdf,.doc,.docx" style="display:none" @change="onAttach" />
            <!-- Text -->
            <textarea v-model="inputText" @keydown.enter.exact.prevent="send"
              placeholder="Écrivez votre message… (Entrée pour envoyer)"
              maxlength="300" rows="1"
              style="flex:1;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:10px 14px;color:#e2e8f0;font-size:13px;resize:none;outline:none;font-family:inherit;line-height:1.5;transition:border .2s;max-height:120px;overflow-y:auto;"
              onfocus="this.style.borderColor='rgba(129,140,248,0.5)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'"
              @input="autoResize"></textarea>
            <!-- Counter + send -->
            <div style="display:flex;flex-direction:column;align-items:center;gap:4px;flex-shrink:0;">
              <span style="font-size:10px;color:#334155;">{{ 300 - inputText.length }}</span>
              <button @click="send" :disabled="sending || (!inputText.trim() && !attachFile)"
                style="width:38px;height:38px;border-radius:10px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;transition:all .2s;"
                :style="(!inputText.trim() && !attachFile) ? 'background:rgba(255,255,255,0.05);opacity:.5;' : 'background:linear-gradient(135deg,#6366f1,#a855f7);'">
                {{ sending ? '…' : '➤' }}
              </button>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Image preview overlay -->
    <div v-if="previewImg" @click="previewImg=null"
      style="position:fixed;inset:0;z-index:500;background:rgba(0,0,0,0.9);display:flex;align-items:center;justify-content:center;cursor:zoom-out;">
      <img :src="previewImg" style="max-width:90vw;max-height:90vh;border-radius:12px;" />
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted, onUnmounted } from 'vue'
import api from '@/api'

const apiBase       = 'http://localhost:8000'
const tickets       = ref([])
const loadingTickets = ref(true)
const activeTicket  = ref(null)
const messages      = ref([])
const loadingMessages = ref(false)
const inputText     = ref('')
const attachFile    = ref(null)
const sending       = ref(false)
const messagesEl    = ref(null)
const attachInput   = ref(null)
const previewImg    = ref(null)
const pendingRating = ref(0)
const hoverRating   = ref(0)
let   pollInterval  = null

function isImage(path) { return /\.(png|jpg|jpeg|gif|webp)$/i.test(path) }

function timeAgo(d) {
  const diff = Math.floor((Date.now() - new Date(d)) / 1000)
  if (diff < 60)    return 'À l\'instant'
  if (diff < 3600)  return `${Math.floor(diff/60)} min`
  if (diff < 86400) return `${Math.floor(diff/3600)} h`
  return new Date(d).toLocaleDateString('fr-FR', { day:'2-digit', month:'short' })
}
function formatTime(d) {
  return new Date(d).toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' })
}

async function loadTickets() {
  try {
    const r = await api.get('/tickets')
    tickets.value = r.data
    // Refresh active ticket data if open
    if (activeTicket.value) {
      const fresh = r.data.find(t => t.id === activeTicket.value.id)
      if (fresh) { activeTicket.value.unread = fresh.unread; activeTicket.value.rating = fresh.rating }
    }
  } catch {}
  loadingTickets.value = false
}

async function openTicket(t) {
  activeTicket.value  = t
  messages.value      = []
  loadingMessages.value = true
  pendingRating.value = 0
  try {
    const r = await api.get(`/tickets/${t.id}`)
    activeTicket.value = { ...t, ...r.data }
    messages.value = r.data.messages
    t.unread = 0
    await nextTick()
    scrollBottom()
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
    const r = await api.post(`/tickets/${activeTicket.value.id}/messages`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    messages.value.push(r.data)
    inputText.value = ''
    attachFile.value = null
    await nextTick(); scrollBottom()
  } catch {}
  sending.value = false
}

async function submitRating() {
  if (!pendingRating.value) return
  try {
    await api.patch(`/tickets/${activeTicket.value.id}/rate`, { rating: pendingRating.value })
    activeTicket.value.rating = pendingRating.value
  } catch {}
}

function onAttach(e) {
  const f = e.target.files[0]
  if (f && f.size <= 10 * 1024 * 1024) attachFile.value = f
}

function autoResize(e) {
  e.target.style.height = 'auto'
  e.target.style.height = Math.min(e.target.scrollHeight, 120) + 'px'
}

function scrollBottom() {
  if (messagesEl.value) messagesEl.value.scrollTop = messagesEl.value.scrollHeight
}

async function pollMessages() {
  if (!activeTicket.value || activeTicket.value.status === 'closed') return
  try {
    const r = await api.get(`/tickets/${activeTicket.value.id}`)
    const newMsgs = r.data.messages
    if (newMsgs.length > messages.value.length) {
      messages.value = newMsgs
      await nextTick(); scrollBottom()
    }
  } catch {}
  // Also refresh list for unread counts
  loadTickets()
}

onMounted(() => {
  loadTickets()
  pollInterval = setInterval(pollMessages, 15_000)
})
onUnmounted(() => clearInterval(pollInterval))
</script>

<style scoped>
@keyframes pulse { 0%,100%{opacity:.5} 50%{opacity:.2} }
@keyframes spin   { to { transform: rotate(360deg); } }
</style>
