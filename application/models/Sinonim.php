<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Sinonim extends CI_Model
{
    private $_table = "chatbot.sinonim";

    public function getAll()
    {
        $this->db->select('*');
        $this->db->from($this->_table);
        $this->db->order_by("id", "asc");
        return $this->db->get()->result_array();
    }

    public function save($input){
        $this->db->insert($this->_table, $input);

        return 'success';
    }

    public function update($id, $input) {
        $this->db->update($this->_table, $input, array('id' => $id));

        return 'success';
    }

    public function delete($id) {
        $this->db->delete($this->_table, array('id' => $id));

        return 'success';
    }
}
