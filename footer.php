    <footer class="site-footer">
        <div class="footer-content" style="max-width: 1200px; margin: 0 auto; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 32px; padding: 0 24px;">
            <div style="flex: 1; min-width: 260px;">
                <div class="site-logo" style="font-size:1.25rem;"><i class="fa-solid fa-hand-holding-heart"></i> Sayog</div>
                <p style="color: #6b7280; margin-top: 12px; font-size: 0.9rem; line-height: 1.6; max-width: 360px;">
                    Built to connect surplus food with communities.
                </p>
            </div>
            <div style="min-width: 160px;">
                <h4 style="font-size:0.9rem; font-weight:700; margin-bottom:12px; color: #111827;" data-i18n="footer.quick_links">Quick Links</h4>
                <ul style="list-style:none; padding:0; margin:0;">
                    <li style="margin-bottom:8px;"><a href="/frontend/index.php" style="color:#6b7280;text-decoration:none;font-size:0.85rem;" data-i18n="nav.home">Home</a></li>
                    <li style="margin-bottom:8px;"><a href="/frontend/donations.php" style="color:#6b7280;text-decoration:none;font-size:0.85rem;" data-i18n="nav.food_listings">Food Listings</a></li>
                    <li style="margin-bottom:8px;"><a href="/frontend/volunteers.php" style="color:#6b7280;text-decoration:none;font-size:0.85rem;" data-i18n="nav.volunteers">Volunteers</a></li>
                    <li style="margin-bottom:8px;"><a href="/frontend/team.php" style="color:#6b7280;text-decoration:none;font-size:0.85rem;" data-i18n="nav.our_team">Our Team</a></li>
                    <li style="margin-bottom:8px;"><a href="/frontend/about.php" style="color:#6b7280;text-decoration:none;font-size:0.85rem;" data-i18n="nav.about">About</a></li>
                    <li style="margin-bottom:8px;"><a href="/frontend/contact.php" style="color:#6b7280;text-decoration:none;font-size:0.85rem;" data-i18n="nav.contact">Contact</a></li>
                </ul>
            </div>
            <div style="min-width: 120px;">
                <h4 style="font-size:0.9rem; font-weight:700; margin-bottom:12px; color: #111827;" data-i18n="footer.follow_us">Follow Us</h4>
                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                    <a href="#" target="_blank" style="width:36px;height:36px;border-radius:50%;background:#f3f4f6;display:inline-flex;align-items:center;justify-content:center;color:#374151;text-decoration:none;transition:all 0.2s;" onmouseover="this.style.background='#059669';this.style.color='#fff'" onmouseout="this.style.background='#f3f4f6';this.style.color='#374151'"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" target="_blank" style="width:36px;height:36px;border-radius:50%;background:#f3f4f6;display:inline-flex;align-items:center;justify-content:center;color:#374151;text-decoration:none;transition:all 0.2s;" onmouseover="this.style.background='#059669';this.style.color='#fff'" onmouseout="this.style.background='#f3f4f6';this.style.color='#374151'"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" target="_blank" style="width:36px;height:36px;border-radius:50%;background:#f3f4f6;display:inline-flex;align-items:center;justify-content:center;color:#374151;text-decoration:none;transition:all 0.2s;" onmouseover="this.style.background='#059669';this.style.color='#fff'" onmouseout="this.style.background='#f3f4f6';this.style.color='#374151'"><i class="fa-brands fa-whatsapp"></i></a>
                </div>
            </div>
        </div>
        <div style="border-top: 1px solid #e5e7eb; margin-top: 32px; padding-top: 24px; text-align: center; color: #9ca3af; font-size: 0.85rem;">
            <p>&copy; <?php echo date('Y'); ?> Sayog. Built to connect surplus food with communities.</p>
        </div>
    </footer>

  <script>
    (function() {
      var toggle = document.getElementById('mobileNavToggle');
      var nav = document.getElementById('mobileNav');
      var overlay = document.getElementById('mobileNavOverlay');
      var icon = toggle ? toggle.querySelector('i') : null;

      if (!toggle || !nav || !overlay) return;

      function openMenu() {
        nav.classList.add('mobile-nav-open');
        overlay.classList.add('mobile-nav-open');
        if (icon) { icon.className = 'fa-solid fa-xmark'; }
        toggle.setAttribute('aria-label', 'Close navigation menu');
        document.body.style.overflow = 'hidden';
      }

      function closeMenu() {
        nav.classList.remove('mobile-nav-open');
        overlay.classList.remove('mobile-nav-open');
        if (icon) { icon.className = 'fa-solid fa-bars'; }
        toggle.setAttribute('aria-label', 'Toggle navigation menu');
        document.body.style.overflow = '';
      }

      toggle.addEventListener('click', function(e) {
        e.stopPropagation();
        if (nav.classList.contains('mobile-nav-open')) { closeMenu(); }
        else { openMenu(); }
      });

      overlay.addEventListener('click', closeMenu);

      nav.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', closeMenu);
      });

      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && nav.classList.contains('mobile-nav-open')) { closeMenu(); }
      });
    })();
  </script>
</body>
</html>
