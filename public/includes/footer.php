<!-- Footer -->
<footer class="footer-nln">
    <div class="container">
        <div class="footer-inner d-flex flex-column flex-md-row justify-content-between align-items-center py-4">
            <div class="text-muted small">&copy; Musicalisation <?= date('Y') ?>. All rights reserved.</div>
            <div class="social">
                <a class="me-2" href="#"><i class="fab fa-twitter"></i></a>
                <a class="me-2" href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-github"></i></a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap core JS (kept) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Core theme JS (keep your existing scripts.js) -->
<script src="js/scripts.js"></script>
<script>
document.querySelectorAll('.dropdown-item[data-noti-id]').forEach(item => {
    item.addEventListener('click', function () {
        fetch('includes/api_mark_notification_read.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + this.dataset.notiId
        });
    });
});
</script>

</body>
</html>

<style>
.footer-nln { border-top:1px solid rgba(255,255,255,0.04); background: transparent; color: var(--muted); }
.footer-inner .social a { color: var(--muted); text-decoration:none; }
.footer-inner .social a:hover { color: var(--accent); }
</style>
