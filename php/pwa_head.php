<?php // php/pwa_head.php ?>
<link rel="manifest" href="/Grade/manifest.json">
<meta name="theme-color" content="#4F46E5">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Grade">
<link rel="apple-touch-icon" href="/Grade/img/icon-192.png">
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/Grade/sw.js');
    });
  }
  // Force a fresh server request when the browser restores this page from bfcache,
  // so PHP session guards run and expired sessions can't be seen via Back/Forward.
  window.addEventListener('pageshow', function(e) {
    if (e.persisted) window.location.reload();
  });
</script>