<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');

        // Since the auth is handled outside CI, we check the session directly.
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) {
            // The root index.php has the login button.
            redirect('/');
        }

        // Make user data available to all controllers that extend MY_Controller
        $this->user_id = $_SESSION['user_id'];
        $this->username = $_SESSION['username'];
        $this->role = $_SESSION['role'];
    }
}
