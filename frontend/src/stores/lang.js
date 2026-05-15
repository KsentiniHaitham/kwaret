import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

// ─── Translations dictionary ─────────────────────────────────────────────────
const translations = {
  // NavBar
  'nav.home':       { fr: 'Accueil',    ar: 'الرئيسية' },
  'nav.shop':       { fr: 'Boutique',   ar: 'المتجر' },
  'nav.cart':       { fr: 'Panier',     ar: 'السلة' },
  'nav.orders':     { fr: 'Commandes',  ar: 'الطلبات' },
  'nav.wallet':     { fr: 'Portefeuille', ar: 'المحفظة' },
  'nav.support':    { fr: 'Support',    ar: 'الدعم' },
  'nav.login':      { fr: 'Connexion',  ar: 'تسجيل الدخول' },
  'nav.register':   { fr: 'Créer un compte', ar: 'إنشاء حساب' },
  'nav.logout':     { fr: 'Déconnexion', ar: 'تسجيل الخروج' },
  'nav.admin':      { fr: 'Admin',      ar: 'الإدارة' },

  // Home
  'home.hero.title':   { fr: 'Rechargez vos jeux',   ar: 'أشحن ألعابك' },
  'home.hero.sub':     { fr: 'instantanément',        ar: 'فورياً' },
  'home.hero.btn':     { fr: 'Découvrir la boutique →', ar: 'اكتشف المتجر ←' },
  'home.categories':   { fr: 'Catégories populaires', ar: 'الفئات الشائعة' },
  'home.top':          { fr: 'Top produits',          ar: 'أفضل المنتجات' },

  // Shop
  'shop.title':        { fr: 'Boutique',        ar: 'المتجر' },
  'shop.search':       { fr: 'Rechercher…',     ar: 'بحث…' },
  'shop.all':          { fr: 'Tous',            ar: 'الكل' },
  'shop.empty':        { fr: 'Aucun produit trouvé', ar: 'لا يوجد منتج' },

  // Cart
  'cart.title':        { fr: 'Mon panier',         ar: 'سلتي' },
  'cart.empty':        { fr: 'Votre panier est vide', ar: 'سلتك فارغة' },
  'cart.total':        { fr: 'Total',              ar: 'المجموع' },
  'cart.checkout':     { fr: 'Commander →',        ar: 'اطلب الآن ←' },

  // Orders
  'orders.title':      { fr: 'Mes commandes',  ar: 'طلباتي' },
  'orders.empty':      { fr: 'Aucune commande', ar: 'لا يوجد طلب' },
  'orders.total':      { fr: 'Total commande',  ar: 'مجموع الطلب' },
  'orders.review':     { fr: 'Laisser un avis', ar: 'اترك تقييم' },

  // Auth
  'auth.email':       { fr: 'Email',           ar: 'البريد الإلكتروني' },
  'auth.password':    { fr: 'Mot de passe',    ar: 'كلمة المرور' },
  'auth.login.btn':   { fr: 'Se connecter →',  ar: 'تسجيل الدخول ←' },
  'auth.forgot':      { fr: 'Mot de passe oublié ?', ar: 'نسيت كلمة المرور؟' },

  // Checkout
  'checkout.title':   { fr: 'Finaliser la commande', ar: 'إتمام الطلب' },
  'checkout.promo':   { fr: 'Code promo',            ar: 'رمز الخصم' },
  'checkout.pay':     { fr: 'Confirmer et payer →',  ar: 'تأكيد والدفع ←' },
  'checkout.total':   { fr: 'Total',                 ar: 'المجموع' },

  // Common
  'common.tnd':        { fr: 'TND', ar: 'دت' },
  'common.loading':    { fr: 'Chargement…', ar: 'جاري التحميل…' },
  'common.error':      { fr: 'Une erreur s\'est produite', ar: 'حدث خطأ' },
  'common.send':       { fr: 'Envoyer', ar: 'إرسال' },
  'common.cancel':     { fr: 'Annuler', ar: 'إلغاء' },
  'common.save':       { fr: 'Enregistrer', ar: 'حفظ' },
  'common.confirm':    { fr: 'Confirmer', ar: 'تأكيد' },
}

// ─── Store ────────────────────────────────────────────────────────────────────
export const useLangStore = defineStore('lang', () => {
  const lang = ref(localStorage.getItem('kwaret-lang') || 'fr')

  const isRTL = computed(() => lang.value === 'ar')

  function toggle() {
    lang.value = lang.value === 'fr' ? 'ar' : 'fr'
    localStorage.setItem('kwaret-lang', lang.value)
    document.documentElement.setAttribute('dir', lang.value === 'ar' ? 'rtl' : 'ltr')
    document.documentElement.setAttribute('lang', lang.value)
  }

  function t(key) {
    const entry = translations[key]
    if (!entry) return key
    return entry[lang.value] ?? entry['fr'] ?? key
  }

  // Apply on load
  function init() {
    document.documentElement.setAttribute('dir', lang.value === 'ar' ? 'rtl' : 'ltr')
    document.documentElement.setAttribute('lang', lang.value)
  }

  return { lang, isRTL, toggle, t, init }
})
