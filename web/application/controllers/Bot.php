<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bot extends MY_Controller {

    public function __construct()
    {
        parent::__construct();

        // Check if user is an admin
        if ($this->role !== 'admin') {
            redirect('dashboard');
        }
    }

    public function index()
    {
        $this->load->view('bot');
    }
}
