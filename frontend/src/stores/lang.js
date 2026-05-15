import { defineStore } from 'pinia'
import { ref } from 'vue'

const T = {
  // ── NavBar ──────────────────────────────────────────────────────
  'nav.home':         { fr: 'Accueil',          ar: 'الرئيسية' },
  'nav.shop':         { fr: 'Boutique',          ar: 'المتجر' },
  'nav.admin':        { fr: 'Admin',             ar: 'الإدارة' },
  'nav.orders':       { fr: 'Commandes',         ar: 'الطلبات' },
  'nav.support':      { fr: 'Support',           ar: 'الدعم' },
  'nav.wallet':       { fr: 'Portefeuille',      ar: 'المحفظة' },
  'nav.login':        { fr: 'Connexion',         ar: 'تسجيل الدخول' },
  'nav.register':     { fr: "S'inscrire",        ar: 'إنشاء حساب' },
  'nav.logout':       { fr: 'Déconnexion',       ar: 'تسجيل الخروج' },
  'nav.balance':      { fr: 'SOLDE',             ar: 'الرصيد' },
  'nav.lang_switch':  { fr: 'AR عربي',           ar: 'FR' },
  'nav.lang_switch_full': { fr: 'Passer en Arabe (عربي)', ar: 'Passer en Français (FR)' },

  // ── Home ────────────────────────────────────────────────────────
  'home.hero.badge':      { fr: 'Livraison instantanée',            ar: 'تسليم فوري' },
  'home.hero.title1':     { fr: 'Rechargez vos',                    ar: 'أشحن' },
  'home.hero.title2':     { fr: 'jeux & apps',                      ar: 'ألعابك وتطبيقاتك' },
  'home.hero.title3':     { fr: 'instantanément',                   ar: 'فورياً' },
  'home.hero.sub':        { fr: 'Points, abonnements, cartes cadeaux — tout en un clic.', ar: 'نقاط، اشتراكات، بطاقات هدايا — كل شيء بنقرة واحدة.' },
  'home.hero.btn_shop':   { fr: 'Découvrir la boutique →',          ar: 'اكتشف المتجر ←' },
  'home.hero.btn_account':{ fr: 'Créer un compte →',                ar: 'إنشاء حساب ←' },
  'home.categories':      { fr: 'Catégories populaires',            ar: 'الفئات الشائعة' },
  'home.top_products':    { fr: 'Top produits',                     ar: 'أفضل المنتجات' },
  'home.see_all':         { fr: 'Voir tout →',                      ar: 'عرض الكل ←' },
  'home.add_cart':        { fr: 'Ajouter',                          ar: 'أضف' },
  'home.sold_out':        { fr: 'Rupture',                          ar: 'نفد' },
  'home.features.instant':{ fr: 'Livraison instantanée',            ar: 'تسليم فوري' },
  'home.features.instant.sub': { fr: 'Codes envoyés en quelques secondes', ar: 'الرموز تُرسل في ثوانٍ' },
  'home.features.secure': { fr: 'Paiement sécurisé',               ar: 'دفع آمن' },
  'home.features.secure.sub': { fr: 'Portefeuille chiffré & protégé', ar: 'محفظة مشفرة ومحمية' },
  'home.features.support':{ fr: 'Support 7j/7',                    ar: 'دعم 7/7' },
  'home.features.support.sub': { fr: 'Une équipe disponible pour vous', ar: 'فريق متاح لمساعدتك' },

  // ── Shop ────────────────────────────────────────────────────────
  'shop.title':           { fr: 'Boutique',               ar: 'المتجر' },
  'shop.all':             { fr: 'Tous',                   ar: 'الكل' },
  'shop.search':          { fr: 'Rechercher un produit…', ar: '…ابحث عن منتج' },
  'shop.sort':            { fr: 'Trier par',              ar: 'ترتيب حسب' },
  'shop.sort.default':    { fr: 'Défaut',                 ar: 'افتراضي' },
  'shop.sort.price_asc':  { fr: 'Prix croissant',         ar: 'السعر: الأقل' },
  'shop.sort.price_desc': { fr: 'Prix décroissant',       ar: 'السعر: الأعلى' },
  'shop.sort.name':       { fr: 'Nom A-Z',                ar: 'الاسم أ-ي' },
  'shop.empty':           { fr: 'Aucun produit trouvé',   ar: 'لا يوجد منتج' },
  'shop.add_cart':        { fr: 'Ajouter au panier',      ar: 'أضف للسلة' },
  'shop.sold_out':        { fr: 'Rupture de stock',       ar: 'نفد من المخزون' },
  'shop.filters':         { fr: 'Filtres',                ar: 'فلاتر' },

  // ── Cart ────────────────────────────────────────────────────────
  'cart.title':           { fr: 'Mon panier',             ar: 'سلتي' },
  'cart.title1':          { fr: 'Mon',                    ar: 'سلتي' },
  'cart.title2':          { fr: 'panier',                 ar: '' },
  'cart.empty':           { fr: 'Votre panier est vide',  ar: 'سلتك فارغة' },
  'cart.empty.btn':       { fr: 'Découvrir la boutique →', ar: 'اكتشف المتجر ←' },
  'cart.subtotal':        { fr: 'Sous-total',             ar: 'المجموع الفرعي' },
  'cart.shipping':        { fr: 'Livraison',              ar: 'الشحن' },
  'cart.shipping.free':   { fr: 'Gratuite',               ar: 'مجاني' },
  'cart.total':           { fr: 'Total',                  ar: 'المجموع' },
  'cart.checkout':        { fr: 'Commander →',            ar: 'اطلب الآن ←' },
  'cart.clear':           { fr: 'Vider le panier',        ar: 'إفراغ السلة' },
  'cart.items':           { fr: 'Sous-total',             ar: 'المجموع الفرعي' },

  // ── Checkout ────────────────────────────────────────────────────
  'checkout.title':       { fr: 'Finaliser la',           ar: 'إتمام' },
  'checkout.title2':      { fr: 'commande',               ar: 'الطلب' },
  'checkout.summary':     { fr: 'Récapitulatif',          ar: 'ملخص الطلب' },
  'checkout.payment':     { fr: 'Moyen de paiement',      ar: 'طريقة الدفع' },
  'checkout.wallet':      { fr: 'Portefeuille Kwaret',    ar: 'محفظة Kwaret' },
  'checkout.available':   { fr: 'disponible',             ar: 'متاح' },
  'checkout.sufficient':  { fr: '✓ Solde suffisant',      ar: '✓ الرصيد كافٍ' },
  'checkout.insufficient':{ fr: 'Solde insuffisant',      ar: 'الرصيد غير كافٍ' },
  'checkout.required':    { fr: 'Requis',                 ar: 'مطلوب' },
  'checkout.promo':       { fr: 'Code promo',             ar: 'رمز الخصم' },
  'checkout.promo.ph':    { fr: 'Entrez votre code…',     ar: '…أدخل رمز الخصم' },
  'checkout.promo.apply': { fr: 'Appliquer',              ar: 'تطبيق' },
  'checkout.promo.remove':{ fr: 'Retirer',                ar: 'إزالة' },
  'checkout.total':       { fr: 'Total',                  ar: 'المجموع' },
  'checkout.pay':         { fr: 'Confirmer et payer →',   ar: 'تأكيد والدفع ←' },
  'checkout.processing':  { fr: 'Traitement…',            ar: '…جارٍ المعالجة' },
  'checkout.recharge':    { fr: '💳 Recharger mon portefeuille →', ar: '← 💳 اشحن محفظتي' },
  'checkout.missing':     { fr: 'Il vous manque',         ar: 'تحتاج إلى' },
  'checkout.missing2':    { fr: 'pour passer cette commande. Rechargez votre portefeuille pour continuer.', ar: 'لإتمام هذا الطلب. اشحن محفظتك للمتابعة.' },
  'checkout.cashback':    { fr: 'Vous recevrez',          ar: 'ستحصل على' },
  'checkout.cashback2':   { fr: 'de cashback après validation', ar: 'كاشباك بعد التأكيد' },

  // ── Orders ──────────────────────────────────────────────────────
  'orders.title':         { fr: 'Mes',                    ar: 'طلباتي' },
  'orders.title2':        { fr: 'commandes',              ar: '' },
  'orders.empty':         { fr: "Vous n'avez pas encore de commande", ar: 'ليس لديك أي طلب بعد' },
  'orders.empty.btn':     { fr: 'Commander maintenant →', ar: 'اطلب الآن ←' },
  'orders.total':         { fr: 'Total commande',         ar: 'مجموع الطلب' },
  'orders.review.btn':    { fr: '⭐ Laisser un avis',     ar: '⭐ اترك تقييماً' },
  'orders.review.rate':   { fr: 'Évaluez votre commande :', ar: ':قيّم طلبك' },
  'orders.review.done':   { fr: 'Votre avis a été soumis', ar: 'تم إرسال تقييمك' },
  'orders.review.send':   { fr: 'Envoyer',                ar: 'إرسال' },
  'orders.review.sending':{ fr: 'Envoi...',               ar: '...جارٍ الإرسال' },
  'orders.review.cancel': { fr: 'Annuler',                ar: 'إلغاء' },
  'orders.review.ph':     { fr: 'Commentaire optionnel…', ar: '…تعليق اختياري' },
  'orders.status.pending':   { fr: 'En attente',          ar: 'قيد الانتظار' },
  'orders.status.paid':      { fr: 'Payée',               ar: 'مدفوعة' },
  'orders.status.delivered': { fr: 'Livrée',              ar: 'مُسلَّمة' },
  'orders.status.cancelled': { fr: 'Annulée',             ar: 'ملغاة' },

  // ── Auth ────────────────────────────────────────────────────────
  'auth.login.title':     { fr: 'Bon retour !',           ar: 'مرحباً بعودتك!' },
  'auth.login.sub':       { fr: 'Connectez-vous à votre compte', ar: 'سجّل الدخول إلى حسابك' },
  'auth.login.btn':       { fr: 'Se connecter →',         ar: '← تسجيل الدخول' },
  'auth.login.loading':   { fr: 'Connexion...',           ar: '...جارٍ الاتصال' },
  'auth.login.no_account':{ fr: 'Pas encore de compte ?', ar: 'ليس لديك حساب؟' },
  'auth.login.create':    { fr: 'Créer un compte',        ar: 'إنشاء حساب' },
  'auth.forgot':          { fr: 'Mot de passe oublié ?',  ar: 'نسيت كلمة المرور؟' },
  'auth.email':           { fr: 'Email',                  ar: 'البريد الإلكتروني' },
  'auth.email.ph':        { fr: 'votre@email.com',        ar: 'بريدك@الإلكتروني.com' },
  'auth.password':        { fr: 'Mot de passe',           ar: 'كلمة المرور' },
  'auth.password.ph':     { fr: '••••••••',               ar: '••••••••' },
  'auth.register.title':  { fr: 'Créer un compte',        ar: 'إنشاء حساب' },
  'auth.register.sub':    { fr: 'Rejoignez 2000+ clients satisfaits', ar: '+انضم إلى 2000 عميل سعيد' },
  'auth.register.btn':    { fr: 'Créer mon compte →',     ar: '← إنشاء حسابي' },
  'auth.register.loading':{ fr: 'Création...',            ar: '...جارٍ الإنشاء' },
  'auth.register.has_account': { fr: 'Déjà un compte ?', ar: 'لديك حساب بالفعل؟' },
  'auth.firstname':       { fr: 'Prénom',                 ar: 'الاسم' },
  'auth.lastname':        { fr: 'Nom',                    ar: 'اللقب' },
  'auth.phone':           { fr: 'Téléphone (optionnel)',  ar: '(اختياري) الهاتف' },
  'auth.password.min':    { fr: '8 caractères minimum',   ar: '8 أحرف على الأقل' },

  // ── Wallet ──────────────────────────────────────────────────────
  'wallet.title':         { fr: 'Mon',                    ar: 'محفظتي' },
  'wallet.title2':        { fr: 'portefeuille',           ar: '' },
  'wallet.balance':       { fr: 'Solde disponible',       ar: 'الرصيد المتاح' },
  'wallet.balance.sub':   { fr: 'Utilisable immédiatement pour vos achats', ar: 'قابل للاستخدام فوراً في مشترياتك' },
  'wallet.recharge':      { fr: '+ Recharger',            ar: '+ شحن' },
  'wallet.history':       { fr: 'Historique des recharges', ar: 'سجل الشحنات' },
  'wallet.empty':         { fr: 'Aucune recharge pour l\'instant', ar: 'لا توجد شحنات بعد' },
  'wallet.gift.title':    { fr: '🎁 Cartes cadeaux',      ar: '🎁 بطاقات الهدايا' },
  'wallet.gift.redeem':   { fr: 'Utiliser un code cadeau', ar: 'استخدام رمز هدية' },
  'wallet.gift.redeem.ph':{ fr: 'KWARET-XXXX-XXXX',       ar: 'KWARET-XXXX-XXXX' },
  'wallet.gift.redeem.btn':{ fr: 'Utiliser',              ar: 'استخدام' },
  'wallet.gift.buy':      { fr: 'Acheter une carte cadeau', ar: 'شراء بطاقة هدية' },
  'wallet.gift.buy.sub':  { fr: 'déduits de votre portefeuille → vous recevrez un code cadeau à partager.', ar: 'مخصومة من محفظتك ← ستحصل على رمز هدية لمشاركته.' },
  'wallet.gift.buy.btn':  { fr: 'Acheter',                ar: 'شراء' },
  'wallet.gift.created':  { fr: '✅ Carte cadeau créée !', ar: '✅ تم إنشاء بطاقة الهدية!' },
  'wallet.gift.mycards':  { fr: 'Mes cartes cadeaux',     ar: 'بطاقات هداياي' },
  'wallet.gift.available':{ fr: 'Disponible',             ar: 'متاحة' },
  'wallet.gift.used':     { fr: 'Utilisée',               ar: 'مستخدمة' },

  // ── Support ─────────────────────────────────────────────────────
  'support.title':        { fr: 'Support',                ar: 'الدعم' },
  'support.sub':          { fr: 'Vos conversations avec l\'équipe', ar: 'محادثاتك مع الفريق' },
  'support.empty':        { fr: 'Aucune conversation pour l\'instant', ar: 'لا توجد محادثات بعد' },
  'support.select':       { fr: 'Sélectionnez une conversation', ar: 'اختر محادثة' },
  'support.select.sub':   { fr: 'Ou attendez qu\'un ticket soit créé automatiquement', ar: 'أو انتظر إنشاء تذكرة تلقائياً' },
  'support.open':         { fr: '● Ouvert',               ar: '● مفتوح' },
  'support.closed':       { fr: '✓ Fermé',                ar: '✓ مغلق' },
  'support.online':       { fr: '● En ligne — Support Kwaret', ar: '● متصل — دعم Kwaret' },
  'support.closed_msg':   { fr: '✓ Conversation fermée',  ar: '✓ المحادثة مغلقة' },
  'support.placeholder':  { fr: 'Écrivez votre message… (Entrée pour envoyer)', ar: '…(اكتب رسالتك (Enter للإرسال' },
  'support.rate':         { fr: 'La discussion est fermée. Notez votre expérience :', ar: ':المحادثة مغلقة. قيّم تجربتك' },
  'support.rated':        { fr: 'Vous avez noté cette conversation', ar: 'لقد قيّمت هذه المحادثة' },
  'support.rated2':       { fr: '· Merci !',              ar: '· شكراً!' },

  // ── Product ─────────────────────────────────────────────────────
  'product.add_cart':     { fr: 'Ajouter au panier',      ar: 'أضف للسلة' },
  'product.added':        { fr: '✓ Ajouté !',             ar: '✓ تمت الإضافة!' },
  'product.sold_out':     { fr: 'Rupture de stock',       ar: 'نفد من المخزون' },
  'product.description':  { fr: 'Description',            ar: 'الوصف' },
  'product.stock':        { fr: 'En stock',               ar: 'متوفر في المخزون' },
  'product.cashback':     { fr: 'Cashback',               ar: 'استرداد نقدي' },

  // ── Common ──────────────────────────────────────────────────────
  'common.loading':       { fr: 'Chargement…',            ar: '…جارٍ التحميل' },
  'common.error':         { fr: 'Une erreur s\'est produite', ar: 'حدث خطأ' },
  'common.send':          { fr: 'Envoyer',                ar: 'إرسال' },
  'common.cancel':        { fr: 'Annuler',                ar: 'إلغاء' },
  'common.save':          { fr: 'Enregistrer',            ar: 'حفظ' },
  'common.confirm':       { fr: 'Confirmer',              ar: 'تأكيد' },
  'common.or':            { fr: 'OU',                     ar: 'أو' },
  'common.tnd':           { fr: 'TND',                    ar: 'دت' },
  'common.back_shop':     { fr: '← Boutique',             ar: 'المتجر ←' },
}

export const useLangStore = defineStore('lang', () => {
  const lang = ref(localStorage.getItem('kwaret-lang') || 'fr')

  function toggle() {
    lang.value = lang.value === 'fr' ? 'ar' : 'fr'
    localStorage.setItem('kwaret-lang', lang.value)
    applyDir()
  }

  function t(key) {
    const entry = T[key]
    if (!entry) return key
    return entry[lang.value] ?? entry['fr'] ?? key
  }

  function applyDir() {
    document.documentElement.setAttribute('dir',  lang.value === 'ar' ? 'rtl' : 'ltr')
    document.documentElement.setAttribute('lang', lang.value)
  }

  function init() { applyDir() }

  return { lang, toggle, t, init }
})
