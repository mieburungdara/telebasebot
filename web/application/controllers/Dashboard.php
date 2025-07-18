<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('user_model');
    }

    public function index()
    {
        // Hardcoded user_id for now
        $user_id = 1;

        $data['user'] = $this->user_model->get_user($user_id);
        $this->load->view('dashboard', $data);
    }

    public function koleksi()
    {
        // Hardcoded user_id for now
        $user_id = 1;

        $data['content'] = $this->user_model->get_user_content($user_id);
        $this->load->view('koleksi', $data);
    }

    public function riwayat()
    {
        // Hardcoded user_id for now
        $user_id = 1;

        $data['history'] = $this->user_model->get_purchase_history($user_id);
        $this->load->view('riwayat', $data);
    }

    public function saldo()
    {
        // Hardcoded user_id for now
        $user_id = 1;

        $data['user'] = $this->user_model->get_user($user_id);
        $this->load->view('saldo', $data);
    }
}
