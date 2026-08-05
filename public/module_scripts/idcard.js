/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Staff ID Card client runtime: QR rendering into SVG faces, high-DPI PNG export
 * (self-contained via same-origin image inlining), and print. Depends on the
 * vendored qrcodejs (public/assets/plugins/qrcode/qrcode.min.js).
 */
(function (w) {
  'use strict';

  // Render the QR (from data-qr) into each face's .idcard-qr <image>.
  function renderQR(root) {
    root = root || document;
    if (typeof QRCode === 'undefined') { return; }
    root.querySelectorAll('.idcard-face').forEach(function (svg) {
      var url = svg.getAttribute('data-qr');
      var img = svg.querySelector('.idcard-qr');
      if (!url || !img || img.getAttribute('data-rendered') === '1') { return; }
      var holder = document.createElement('div');
      holder.style.position = 'fixed'; holder.style.left = '-9999px'; holder.style.top = '0';
      document.body.appendChild(holder);
      try {
        new QRCode(holder, { text: url, width: 512, height: 512, correctLevel: QRCode.CorrectLevel.M });
      } catch (e) { holder.remove(); return; }
      setTimeout(function () {
        var canvas = holder.querySelector('canvas');
        var data = canvas ? canvas.toDataURL('image/png') : (holder.querySelector('img') || {}).src;
        if (data) {
          img.setAttributeNS('http://www.w3.org/1999/xlink', 'href', data);
          img.setAttribute('href', data);
          img.setAttribute('data-rendered', '1');
        }
        holder.remove();
      }, 40);
    });
  }

  // Fetch a same-origin URL and return a data: URI (keeps exported SVG self-contained).
  function toDataURL(url) {
    return fetch(url, { credentials: 'same-origin' })
      .then(function (r) { return r.blob(); })
      .then(function (b) {
        return new Promise(function (res) {
          var fr = new FileReader();
          fr.onload = function () { res(fr.result); };
          fr.onerror = function () { res(null); };
          fr.readAsDataURL(b);
        });
      })
      .catch(function () { return null; });
  }

  // Inline every external <image> href in an SVG clone.
  function inlineImages(svg) {
    var imgs = Array.prototype.slice.call(svg.querySelectorAll('image'));
    return Promise.all(imgs.map(function (im) {
      var href = im.getAttribute('href') || im.getAttributeNS('http://www.w3.org/1999/xlink', 'href');
      if (!href || href.indexOf('data:') === 0) { return Promise.resolve(); }
      return toDataURL(href).then(function (d) {
        if (d) {
          im.setAttributeNS('http://www.w3.org/1999/xlink', 'href', d);
          im.setAttribute('href', d);
        }
      });
    }));
  }

  // Export one face SVG as a high-DPI PNG. 10 SVG units == 1mm.
  function exportPNG(svg, filename, dpi) {
    dpi = dpi || 300;
    var clone = svg.cloneNode(true);
    inlineImages(clone).then(function () {
      var vb = svg.viewBox.baseVal;
      var mmW = vb.width / 10, mmH = vb.height / 10;
      var pxmm = dpi / 25.4;
      var W = Math.round(mmW * pxmm), H = Math.round(mmH * pxmm);
      var xml = new XMLSerializer().serializeToString(clone);
      var url = 'data:image/svg+xml;base64,' + w.btoa(unescape(encodeURIComponent(xml)));
      var image = new Image();
      image.onload = function () {
        var canvas = document.createElement('canvas');
        canvas.width = W; canvas.height = H;
        var ctx = canvas.getContext('2d');
        ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, W, H);
        ctx.drawImage(image, 0, 0, W, H);
        var a = document.createElement('a');
        a.download = filename; a.href = canvas.toDataURL('image/png'); a.click();
      };
      image.src = url;
    });
  }

  function printNode() { w.print(); }

  w.IdCard = { renderQR: renderQR, exportPNG: exportPNG, print: printNode, inlineImages: inlineImages };

  // Auto-render QR on standalone card/verify pages.
  if (document.readyState !== 'loading') { renderQR(); }
  else { document.addEventListener('DOMContentLoaded', function () { renderQR(); }); }
})(window);
