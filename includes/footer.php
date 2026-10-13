<footer class="footer">
  <p><strong><?= e($SITE['name']) ?></strong> · <?= e($SITE['tagline']) ?></p>
  <p>LINE: <a href="<?= e($SITE['line_url']) ?>" target="_blank" rel="noopener"><?= e($SITE['line_id']) ?></a>
   · โทร <?= e($SITE['phone']) ?>
   <?php if ($SITE['facebook']): ?> · <a href="<?= e($SITE['facebook']) ?>" target="_blank" rel="noopener">Facebook</a><?php endif; ?></p>
  <p class="muted small">คำทำนายเป็นแนวทางเพื่อความบันเทิงและกำลังใจ โปรดใช้วิจารณญาณในการตัดสินใจ</p>
</footer>
<a class="line-float" href="<?= e($SITE['line_url']) ?>" target="_blank" rel="noopener">💬 แอดไลน์</a>
</body>
</html>
