<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bot extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('user_model');

        // Hardcoded user_id for now
        $user_id = 1;
        $user = $this->user_model->get_user($user_id);

        // Check if user is an admin
        if (!isset($user->role) || $user->role !== 'admin') {
            redirect('dashboard');
        }
    }

    public function index()
    {
        $this->load->view('bot');
    }
}
