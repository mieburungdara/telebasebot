<?php $this->load->view('partials/header'); ?>

<div id="container">
    <h1>Koleksi Konten Saya</h1>

    <div id="body">
        <?php if (empty($content)): ?>
            <p>Anda belum membeli konten apa pun.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($content as $item): ?>
                    <li><?php echo $item->caption; ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php $this->load->view('partials/footer'); ?>
