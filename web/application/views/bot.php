<?php $this->load->view('partials/header'); ?>

<div id="container">
    <h1>Bot Management</h1>

    <div id="body">
        <p>This is the bot management page. Here you can configure and manage your bot.</p>

        <div class="feature-box">
            <h2>Bot Settings</h2>
            <p>Configure your bot's token, webhook, and other settings.</p>
            <a href="<?php echo site_url('settings'); ?>">Go to Settings</a>
        </div>
    </div>
</div>

<?php $this->load->view('partials/footer'); ?>
