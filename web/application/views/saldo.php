<?php $this->load->view('partials/header'); ?>

<div id="container">
    <h1>Manajemen Saldo</h1>

    <div id="body">
        <p>Saldo Anda saat ini: <?php echo $user->balance; ?></p>

        <form action="/dashboard/withdraw" method="post">
            <input type="number" name="amount" placeholder="Jumlah penarikan">
            <button type="submit">Tarik Dana</button>
        </form>
    </div>
</div>

<?php $this->load->view('partials/footer'); ?>
