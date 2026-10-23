<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-brand">
      <a href="#home" class="brand">
        <span class="brand-mark"><img src="{{ asset('moderning_xelenic/assets/img/logo-mark.png') }}" alt="" width="38" height="38" /></span>
        <span class="brand-name">Xelenic</span>
      </a>
      <p>AI middleware, business systems, and technology strategy, unified to help your
        business grow.</p>
      <div class="social-links">
        <a href="#" aria-label="LinkedIn"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M4.98 3.5a2.5 2.5 0 11-.02 5 2.5 2.5 0 01.02-5zM3 9h4v12H3V9zm7 0h3.8v1.7h.1c.5-1 1.9-2 3.9-2 4.2 0 5 2.7 5 6.3V21h-4v-5.4c0-1.3 0-3-1.9-3s-2.1 1.4-2.1 2.9V21h-4V9z"/></svg></a>
        <a href="#" aria-label="X / Twitter"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h4.2l4 5.4L15.8 3H20l-6.6 8.4L20.4 21H16l-4.4-5.9L6.4 21H2l7-8.9L3 3z"/></svg></a>
        <a href="#" aria-label="GitHub"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 00-3.2 19.5c.5.1.7-.2.7-.5v-1.7c-2.8.6-3.4-1.3-3.4-1.3-.4-1.1-1-1.4-1-1.4-.9-.6.1-.6.1-.6 1 .1 1.5 1 1.5 1 .9 1.5 2.3 1.1 2.9.8.1-.7.3-1.1.6-1.4-2.2-.2-4.6-1.1-4.6-5A3.9 3.9 0 016 9.5c-.1-.2-.5-1.3.1-2.7 0 0 .9-.3 3 1.1a10.4 10.4 0 015.5 0c2.1-1.4 3-1.1 3-1.1.6 1.4.2 2.5.1 2.7a3.9 3.9 0 011 2.7c0 3.9-2.4 4.8-4.6 5 .3.3.6.8.6 1.7v2.5c0 .3.2.6.7.5A10 10 0 0012 2z"/></svg></a>
      </div>
    </div>

    <div class="footer-col">
      <h5>Solutions</h5>
      <ul>
        <li><a href="#solutions">AI Middleware</a></li>
        <li><a href="#solutions">CRM &amp; ERP</a></li>
        <li><a href="#solutions">POS Systems</a></li>
        <li><a href="#solutions">Business Automation</a></li>
        <li><a href="#solutions">Payroll</a></li>
        <li><a href="#solutions">AI Kiosk Apps</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h5>Company</h5>
      <ul>
        <li><a href="#why-us">Why Xelenic</a></li>
        <li><a href="#process">Our Process</a></li>
        <li><a href="#technology">Technology</a></li>
        <li><a href="#contact">Contact</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h5>Stay Updated</h5>
      <p>Get occasional updates on AI middleware and business technology trends.</p>
      <form class="footer-form" onsubmit="return false;">
        <input type="email" placeholder="Your email" aria-label="Email for newsletter" />
        <button type="submit" class="btn btn-primary btn-sm">Subscribe</button>
      </form>
    </div>
  </div>

  <div class="container footer-bottom">
    <p>&copy; <span id="year">{{ now()->year }}</span> Xelenic. All rights reserved.</p>
    <p>Powered by AI. Grounded in Open Source.</p>
  </div>
</footer>
