// Rastreador leve: tempo na página + cliques em botões. Sem cookies de terceiros, sem dados pessoais.
(function () {
  var pv = window.MV_PV;
  if (!pv || !navigator.sendBeacon) return;

  var active = 0, last = Date.now(), visible = !document.hidden, clicks = [], sentDur = 0;

  function tick() {
    var now = Date.now();
    if (visible) active += (now - last) / 1000;
    last = now;
  }

  function label(el) {
    var t = el.getAttribute('data-track') || el.getAttribute('aria-label') || el.getAttribute('title') || el.textContent || '';
    t = t.replace(/\s+/g, ' ').trim();
    if (!t && el.querySelector('img')) t = el.querySelector('img').getAttribute('alt') || '';
    return t.slice(0, 80);
  }

  function kind(el, href, text) {
    var l = (text || '').toLowerCase();
    if (/wa\.me|whatsapp/i.test(href) || el.classList.contains('wa-float')) return 'whatsapp';
    if (el.hasAttribute('data-size') || el.classList.contains('size-opt')) return 'tamanho';
    if (el.classList.contains('swatch') || el.hasAttribute('data-color')) return 'cor';
    if (/favorit/.test(href) || /favorit/.test(l)) return 'favorito';
    if (/comprar/.test(l)) return 'comprar';
    if (/sacola|carrinho|adicionar/.test(l)) return 'carrinho';
    if (el.hasAttribute('data-menu-toggle') || el.hasAttribute('data-search-toggle')) return 'menu';
    return el.tagName === 'A' ? 'link' : 'botao';
  }

  document.addEventListener('click', function (e) {
    var el = e.target.closest && e.target.closest('a,button,[data-track],.size-opt,.swatch');
    if (!el) return;
    var href = el.getAttribute('href') || '';
    var text = label(el);
    var k = kind(el, href, text);
    if (!text) text = k === 'whatsapp' ? 'Botão do WhatsApp' : '';
    if (!text) return;
    if (k === 'tamanho') text = 'Tamanho ' + text;
    if (k === 'cor' && !/^cor/i.test(text)) text = 'Cor ' + text;
    clicks.push({ l: text, k: k });
    if (clicks.length >= 8) send();
  }, true);

  function send() {
    tick();
    var dur = Math.round(active);
    if (!clicks.length && dur === sentDur) return;
    sentDur = dur;
    var body = JSON.stringify({ pv: pv, dur: dur, path: location.pathname, clicks: clicks });
    clicks = [];
    navigator.sendBeacon('/a/ping', new Blob([body], { type: 'application/json' }));
  }

  document.addEventListener('visibilitychange', function () {
    tick();
    visible = !document.hidden;
    last = Date.now();
    if (document.hidden) send();
  });
  window.addEventListener('pagehide', send);
  setTimeout(send, 3000);          // garante a cidade e o 1º registro mesmo se sair rápido
  setInterval(send, 20000);
})();
