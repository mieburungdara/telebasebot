<?php $this->load->view('partials/header'); ?>

<div id="container">
    <h1>Riwayat Pembelian</h1>

    <div id="body">
        <?php if (empty($history)): ?>
            <p>Anda tidak memiliki riwayat pembelian.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID Konten</th>
                        <th>Harga</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $item): ?>
                        <tr>
                            <td><?php echo $item->content_id; ?></td>
                            <td><?php echo $item->price; ?></td>
                            <td><?php echo $item->purchase_date; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php $this->load->view('partials/footer'); ?>
