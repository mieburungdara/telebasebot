<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('user_model');
    }

    public function index()
    {
        $data['user'] = $this->user_model->get_user($this->user_id);
        $this->load->view('dashboard', $data);
    }

    public function koleksi()
    {
        $data['content'] = $this->user_model->get_user_content($this->user_id);
        $this->load->view('koleksi', $data);
    }

    public function riwayat()
    {
        $data['history'] = $this->user_model->get_purchase_history($this->user_id);
        $this->load->view('riwayat', $data);
    }

    public function saldo()
    {
        $data['user'] = $this->user_model->get_user($this->user_id);
        $this->load->view('saldo', $data);
    }
}
