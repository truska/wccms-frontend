<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?php echo $baseURL; ?>/js/site.js"></script>
<?php
  // Optional per-site footer scripts (e.g. deferred one-off integrations).
  $customFooterPath = __DIR__ . '/custom-footer.php';
  if (file_exists($customFooterPath)) {
    include $customFooterPath;
  }
?>
</body>
</html>
