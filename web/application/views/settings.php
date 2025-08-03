<?php $this->load->view('partials/header'); ?>

<div id="container">
    <h1>Bot Settings</h1>

    <div id="body">
        <p>Use this page to configure your Telegram bot settings.</p>

        <?php echo form_open('settings/update'); ?>

        <div class="form-group">
            <label for="bot_token">Bot Token</label>
            <input type="text" name="bot_token" id="bot_token" class="form-control" value="<?php echo isset($config['BOT_TOKEN']) ? $config['BOT_TOKEN'] : ''; ?>" placeholder="Enter your Telegram Bot Token">
        </div>

        <div class="form-group">
            <label for="webhook_url">Webhook URL</label>
            <input type="text" name="webhook_url" id="webhook_url" class="form-control" value="<?php echo base_url('../bot.php'); ?>" placeholder="https://your.domain/path/to/bot.php">
            <small class="form-text text-muted">The URL that Telegram will send updates to. This should point to the `bot.php` file in your project root.</small>
        </div>

        <div class="form-group">
            <label for="public_channel_username">Public Channel Username</label>
            <input type="text" name="public_channel_username" id="public_channel_username" class="form-control" value="<?php echo isset($config['PUBLIC_CHANNEL_USERNAME']) ? $config['PUBLIC_CHANNEL_USERNAME'] : ''; ?>" placeholder="your_public_channel_username">
            <small class="form-text text-muted">The username of your public channel (without the @).</small>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>

        <?php echo form_close(); ?>
    </div>
</div>

<?php $this->load->view('partials/footer'); ?>
