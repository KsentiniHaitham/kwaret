<template>
  <div style="position:relative;">
    <!-- Bell button -->
    <button @click="toggle" style="position:relative;background:none;border:none;cursor:pointer;padding:8px;border-radius:10px;transition:background .2s;"
      :style="open ? 'background:rgba(99,102,241,0.15);' : ''"
      title="Notifications">
      <span style="font-size:18px;">🔔</span>
      <span v-if="unread > 0"
        style="position:absolute;top:2px;right:2px;min-width:17px;height:17px;border-radius:100px;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;padding:0 4px;line-height:1;">
        {{ unread > 99 ? '99+' : unread }}
      </span>
    </button>

    <!-- Dropdown -->
    <Teleport to="body">
      <div v-if="open" @click.self="open=false"
        style="position:fixed;inset:0;z-index:400;">
        <div
          style="position:fixed;top:64px;right:24px;width:380px;max-height:520px;background:rgba(10,14,30,0.98);border:1px solid rgba(255,255,255,0.1);border-radius:20px;box-shadow:0 24px 80px rgba(0,0,0,0.5);display:flex;flex-direction:column;overflow:hidden;z-index:401;">

          <!-- Header -->
          <div style="padding:16px 18px;border-bottom:1px solid rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <div style="font-size:14px;font-weight:700;color:#e2e8f0;">
              Notifications
              <span v-if="unread > 0" style="margin-left:8px;font-size:11px;font-weight:700;padding:2px 8px;border-radius:100px;background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.3);color:#f87171;">
                {{ unread }} non lue{{ unread > 1 ? 's' : '' }}
              </span>
            </div>
            <button v-if="unread > 0" @click="readAll"
              style="font-size:11px;color:#6366f1;background:none;border:none;cursor:pointer;font-weight:600;padding:4px 8px;border-radius:6px;transition:background .2s;"
              onmouseover="this.style.background='rgba(99,102,241,0.1)'" onmouseout="this.style.background='none'">
              Tout marquer lu
            </button>
          </div>

          <!-- List -->
          <div style="overflow-y:auto;flex:1;">
            <div v-if="loading" style="padding:24px;text-align:center;color:#475569;font-size:13px;">Chargement…</div>

            <div v-else-if="notifications.length === 0"
              style="padding:48px 24px;text-align:center;">
              <div style="font-size:36px;margin-bottom:10px;">🔕</div>
              <div style="color:#334155;font-size:13px;">Aucune notification</div>
            </div>

            <div v-else>
              <div
                v-for="n in notifications" :key="n.id"
                @click="markRead(n)"
                style="display:flex;align-items:flex-start;gap:12px;padding:14px 18px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,0.04);transition:background .15s;"
                :style="!n.isRead ? 'background:rgba(99,102,241,0.06);' : ''"
                onmouseover="this.style.background='rgba(255,255,255,0.03)'" onmouseout="this.style.background=this.dataset.unread==='1'?'rgba(99,102,241,0.06)':'transparent'"
                :data-unread="!n.isRead ? '1' : '0'"
              >
                <!-- Icon -->
                <div style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;"
                  :style="iconBg(n.type)">
                  {{ typeIcon(n.type) }}
                </div>

                <div style="flex:1;min-width:0;">
                  <div style="font-size:13px;font-weight:500;line-height:1.4;"
                    :style="!n.isRead ? 'color:#e2e8f0;' : 'color:#64748b;'">
                    {{ n.message }}
                  </div>
                  <div style="font-size:11px;color:#334155;margin-top:4px;">{{ timeAgo(n.createdAt) }}</div>
                </div>

                <!-- Redirect arrow + unread dot -->
                <div style="display:flex;flex-direction:column;align-items:center;gap:4px;flex-shrink:0;">
                  <span v-if="NOTIF_ROUTES[n.type]" style="font-size:10px;color:#334155;">→</span>
                  <div v-if="!n.isRead" style="width:7px;height:7px;border-radius:50%;background:#6366f1;"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api'

const router = useRouter()

const open          = ref(false)
const loading       = ref(true)
const notifications = ref([])
const unread        = ref(0)
let   pollInterval  = null

function typeIcon(t) {
  return { new_order: '🛒', out_of_stock: '⚠️', new_recharge: '💳' }[t] || '📢'
}
function iconBg(t) {
  return {
    new_order:    'background:rgba(99,102,241,0.15);',
    out_of_stock: 'background:rgba(239,68,68,0.12);',
    new_recharge: 'background:rgba(52,211,153,0.1);',
  }[t] || 'background:rgba(255,255,255,0.05);'
}

function timeAgo(d) {
  const diff = Math.floor((Date.now() - new Date(d)) / 1000)
  if (diff < 60)    return 'À l\'instant'
  if (diff < 3600)  return `Il y a ${Math.floor(diff / 60)} min`
  if (diff < 86400) return `Il y a ${Math.floor(diff / 3600)} h`
  return new Date(d).toLocaleDateString('fr-FR', { day:'2-digit', month:'short' })
}

async function fetch() {
  try {
    const r = await api.get('/admin/notifications')
    notifications.value = r.data.notifications
    unread.value = r.data.unread
  } catch {}
  loading.value = false
}

const NOTIF_ROUTES = {
  new_order:    '/admin/orders',
  out_of_stock: '/admin/products',
  new_recharge: '/admin/recharges',
  new_ticket:   '/admin/tickets',
}

async function markRead(n) {
  // Mark as read
  if (!n.isRead) {
    try {
      await api.patch(`/admin/notifications/${n.id}/read`)
      n.isRead = true
      unread.value = Math.max(0, unread.value - 1)
    } catch {}
  }
  // Redirect to relevant page
  const dest = NOTIF_ROUTES[n.type]
  if (dest) {
    open.value = false
    router.push(dest)
  }
}

async function readAll() {
  try {
    await api.patch('/admin/notifications/read-all')
    notifications.value.forEach(n => n.isRead = true)
    unread.value = 0
  } catch {}
}

function toggle() {
  open.value = !open.value
  if (open.value) fetch()
}

onMounted(() => {
  fetch()
  // Poll every 30s for new notifications
  pollInterval = setInterval(fetch, 30_000)
})

onUnmounted(() => {
  clearInterval(pollInterval)
})
</script>
