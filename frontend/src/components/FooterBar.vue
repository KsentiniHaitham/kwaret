<template>
  <footer style="margin-top:80px;border-top:1px solid rgba(255,255,255,0.07);padding:48px 32px 32px;">
    <div style="max-width:1200px;margin:0 auto;display:grid;grid-template-columns:2fr 1fr 1fr;gap:48px;margin-bottom:40px;">
      <div>
        <span class="gradient-text" style="font-size:22px;font-weight:800;">✦ Kwaret</span>
        <p style="color:#4b5563;font-size:14px;margin-top:12px;line-height:1.7;max-width:280px;">Votre destination pour les produits numériques premium. Gaming, streaming, IA et plus encore.</p>
      </div>
      <div>
        <div style="font-size:13px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;">Navigation</div>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <RouterLink to="/" style="color:#4b5563;text-decoration:none;font-size:14px;transition:color .2s;" onmouseover="this.style.color='#818cf8'" onmouseout="this.style.color='#4b5563'">Accueil</RouterLink>
          <RouterLink to="/shop" style="color:#4b5563;text-decoration:none;font-size:14px;" onmouseover="this.style.color='#818cf8'" onmouseout="this.style.color='#4b5563'">Boutique</RouterLink>
          <RouterLink to="/orders" style="color:#4b5563;text-decoration:none;font-size:14px;" onmouseover="this.style.color='#818cf8'" onmouseout="this.style.color='#4b5563'">Mes commandes</RouterLink>
        </div>
      </div>
      <div>
        <div style="font-size:13px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;">Contact</div>
        <div style="display:flex;flex-direction:column;gap:10px;font-size:14px;color:#4b5563;">
          <span>support@kwaret.shop</span>
          <span>Disponible 24h/24</span>
        </div>
      </div>
    </div>
    <div style="border-top:1px solid rgba(255,255,255,0.05);padding-top:24px;text-align:center;font-size:13px;color:#1f2937;">
      © {{ new Date().getFullYear() }} Kwaret. Tous droits réservés.
    </div>
  </footer>
</template>
