{{-- Script de tête du hero : active le mode animé et garde un filet de sécurité. --}}
<script>
    (function () {
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        document.documentElement.classList.add('rj-motion');
        var hero = document.currentScript.parentNode;
        setTimeout(function () { hero.classList.add('p-arme'); document.querySelectorAll('[data-p-reveal]').forEach(function (e) { if (!e.style.visibility) e.style.visibility = 'visible'; }); }, 12000);
    })();
</script>
