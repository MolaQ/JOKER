{{-- Motyw Jasny / Ciemny / Auto: ustawiany przed narysowaniem strony, żeby nie mrugała jasnym tłem. Domyślnie jasny. --}}
<meta name="color-scheme" content="light dark">
<script>
    (() => {
        let theme = 'light';
        try { theme = localStorage.getItem('theme') || 'light'; } catch (e) {}
        const dark = theme === 'dark' || (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.dataset.themeChoice = theme;
    })();
</script>
