<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    public function get_user($user_id)
    {
        $this->db->where('id', $user_id);
        return $this->db->get('users')->row();
    }

    public function get_user_content($user_id)
    {
        $this->db->select('pc.*');
        $this->db->from('purchases p');
        $this->db->join('paid_contents pc', 'p.content_id = pc.id');
        $this->db->where('p.user_id', $user_id);
        return $this->db->get()->result();
    }

    public function get_purchase_history($user_id)
    {
        $this->db->where('user_id', $user_id);
        return $this->db->get('purchases')->result();
    }
}
