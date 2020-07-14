<?php
// defined('BASEPATH') OR exit('No direct script access allowed');


class HistoryController extends CI_Controller {
    public function __construct()
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        parent::__construct();

        $this->load->model("History");

    }

    public function index(){
        $data = $this->History->getAll();

        echo json_encode($data);
    }

    public function create(){
        $post = $this->input->post();

        $input['user_id'] = $this->input->post('user_id');
        $input['nama'] = $this->input->post('nama');
        $input['input'] = $this->input->post('input');
        $input['tanggal'] = $this->input->post('tanggal');

        $status = $this->History->save($input);

        echo json_encode($status);
    }

}