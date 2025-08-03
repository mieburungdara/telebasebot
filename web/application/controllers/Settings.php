<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends MY_Controller {

    public function __construct()
    {
        parent::__construct();

        // Check if user is an admin
        if ($this->role !== 'admin') {
            redirect('dashboard');
        }

        // Load helpers
        $this->load->helper('url');
        $this->load->helper('form');
        $this->load->helper('config');
    }

    public function index()
    {
        $data['config'] = get_config_values();
        $this->load->view('settings', $data);
    }

    public function update()
    {
        // In a real app, you'd do more robust validation here
        $new_config = [
            'BOT_TOKEN' => $this->input->post('bot_token'),
            'PUBLIC_CHANNEL_USERNAME' => $this->input->post('public_channel_username')
        ];

        // This is a simplified update. A more robust solution would be needed for a complex config file.
        $config_file_path = FCPATH . '../config/config.php';
        $config_file_content = file_get_contents($config_file_path);

        foreach ($new_config as $key => $value) {
            $config_file_content = preg_replace(
                "/define\s*\(\s*'$key'\s*,\s*'.*?'\s*\)/",
                "define('$key', '$value')",
                $config_file_content
            );
        }

        file_put_contents($config_file_path, $config_file_content);

        // Set the webhook
        $webhook_url = $this->input->post('webhook_url');
        $bot_token = $this->input->post('bot_token');
        $telegram_api_url = "https://api.telegram.org/bot{$bot_token}/setWebhook?url={$webhook_url}";

        // Using cURL to send the request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $telegram_api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        // We can add a session flash message to show the result
        // For now, just redirect back to the settings page
        redirect('settings');
    }
}
