<template>
  <div style="max-width:1000px;margin:0 auto;padding:40px 32px 80px;">
    <div style="margin-bottom:36px;">
      <h1 style="font-size:40px;font-weight:800;letter-spacing:-1px;">{{ $t('cart.title1') }} <span class="gradient-text">{{ $t('cart.title2') }}</span></h1>
    </div>

    <!-- Empty -->
    <div v-if="cart.items.length === 0" style="text-align:center;padding:80px 0;">
      <div style="font-size:64px;margin-bottom:20px;">🛒</div>
      <p style="color:#475569;font-size:16px;margin-bottom:28px;">{{ $t('cart.empty') }}</p>
      <RouterLink to="/shop" class="btn-primary" style="text-decoration:none;padding:12px 28px;">{{ $t('cart.empty.btn') }}</RouterLink>
    </div>

    <div v-else style="display:grid;grid-template-columns:1fr 320px;gap:28px;align-items:start;">
      <!-- Items -->
      <div style="display:flex;flex-direction:column;gap:12px;">
        <div
          v-for="item in cart.items"
          :key="item.id"
          class="card"
          style="padding:16px;display:flex;align-items:center;gap:16px;"
        >
          <div style="width:72px;height:72px;border-radius:14px;overflow:hidden;flex-shrink:0;background:linear-gradient(135deg,rgba(99,102,241,0.1),rgba(168,85,247,0.1));display:flex;align-items:center;justify-content:center;font-size:28px;">
            <img v-if="item.image" :src="item.image" :alt="item.name" style="width:100%;height:100%;object-fit:cover;"/>
            <span v-else>💎</span>
          </div>

          <div style="flex:1;min-width:0;">
            <div style="font-size:14px;font-weight:600;color:#e2e8f0;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ item.name }}</div>
            <div class="gradient-text-cyan" style="font-size:16px;font-weight:700;">{{ parseFloat(item.price).toFixed(2) }} TND</div>
          </div>

          <!-- Qty controls -->
          <div style="display:flex;align-items:center;gap:8px;">
            <button @click="cart.updateQty(item.id, item.quantity - 1)"
              style="width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:#e2e8f0;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;">−</button>
            <span style="width:28px;text-align:center;font-weight:600;color:#e2e8f0;">{{ item.quantity }}</span>
            <button @click="cart.updateQty(item.id, item.quantity + 1)"
              style="width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:#e2e8f0;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;">+</button>
          </div>

          <div style="width:100px;text-align:right;font-weight:700;color:#e2e8f0;font-size:15px;">
            {{ (parseFloat(item.price) * item.quantity).toFixed(2) }} TND
          </div>

          <button @click="cart.removeItem(item.id)"
            style="color:#334155;background:none;border:none;cursor:pointer;font-size:18px;padding:4px;transition:color .2s;"
            onmouseover="this.style.color='#f87171'" onmouseout="this.style.color='#334155'">✕</button>
        </div>
      </div>

      <!-- Summary -->
      <div class="card" style="padding:28px;position:sticky;top:84px;">
        <div style="font-size:16px;font-weight:700;color:#e2e8f0;margin-bottom:20px;">{{ $t('checkout.summary') }}</div>

        <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px;">
          <div style="display:flex;justify-content:space-between;font-size:13px;">
            <span style="color:#475569;">{{ $t('cart.subtotal') }}</span>
            <span style="color:#e2e8f0;font-weight:500;">{{ cart.total.toFixed(2) }} TND</span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:13px;">
            <span style="color:#475569;">{{ $t('cart.shipping') }}</span>
            <span style="color:#34d399;font-weight:600;">{{ $t('cart.shipping.free') }}</span>
          </div>
          <div style="border-top:1px solid rgba(255,255,255,0.07);padding-top:14px;display:flex;justify-content:space-between;">
            <span style="color:#e2e8f0;font-weight:600;">{{ $t('cart.total') }}</span>
            <span class="gradient-text-cyan" style="font-size:22px;font-weight:800;">{{ cart.total.toFixed(2) }} TND</span>
          </div>
        </div>

        <RouterLink to="/checkout" class="btn-primary" style="display:block;text-align:center;text-decoration:none;padding:14px;font-size:15px;">
          {{ $t('cart.checkout') }}
        </RouterLink>

        <button @click="cart.clear()"
          style="margin-top:12px;width:100%;background:none;border:none;color:#334155;font-size:12px;cursor:pointer;padding:8px;transition:color .2s;"
          onmouseover="this.style.color='#f87171'" onmouseout="this.style.color='#334155'">
          {{ $t('cart.clear') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useCartStore } from '@/stores/cart'
const cart = useCartStore()
</script>
