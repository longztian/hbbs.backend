<div id="content">

  <div id="content-header">
    <h1 class="title">近期活动</h1>
  </div>

  <div><a href="/help#activity">如何发布活动</a></div>

  <?php if (isset($pager)): ?>
    <div class="item-list"><ul class="pager"><?php print $pager; ?></ul></div>
  <?php endif; ?>

  <ul class='activity_list'><?php print $data; ?></ul>
</div>