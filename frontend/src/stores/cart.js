import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useCartStore = defineStore('cart', () => {
  const items = ref(JSON.parse(localStorage.getItem('cart') || '[]'))

  const total = computed(() =>
    items.value.reduce((sum, i) => sum + parseFloat(i.price) * i.quantity, 0)
  )

  const count = computed(() =>
    items.value.reduce((sum, i) => sum + i.quantity, 0)
  )

  function save() {
    localStorage.setItem('cart', JSON.stringify(items.value))
  }

  function addItem(product) {
    const existing = items.value.find((i) => i.id === product.id)
    if (existing) {
      existing.quantity++
    } else {
      items.value.push({ ...product, quantity: 1 })
    }
    save()
  }

  function removeItem(productId) {
    items.value = items.value.filter((i) => i.id !== productId)
    save()
  }

  function updateQty(productId, qty) {
    const item = items.value.find((i) => i.id === productId)
    if (item) {
      item.quantity = Math.max(1, qty)
      save()
    }
  }

  function clear() {
    items.value = []
    localStorage.removeItem('cart')
  }

  return { items, total, count, addItem, removeItem, updateQty, clear }
})
