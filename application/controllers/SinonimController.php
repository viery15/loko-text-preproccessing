<?php
// defined('BASEPATH') OR exit('No direct script access allowed');


class SinonimController extends CI_Controller {
    public function __construct()
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        parent::__construct();

        $this->load->model("Sinonim");

    }

    public function index(){
        $data = $this->Sinonim->getAll();

        // for ($i=0; $i < count($data); $i++) { 
        //     $words[$i] = json_decode($data[0]['words']);
        // }

        echo json_encode($data);
    }

    public function create(){
        $post = $this->input->post();

        $input['words'] = $post['words'];

        $status = $this->Sinonim->save($input);
    }

    public function update(){
        $post = $this->input->post();

        $id = $post['id'];
        $input['words'] = $post['words'];

        $status = $this->Sinonim->update($id, $input);

        echo json_encode($status);
    }

    public function delete()
    {
        $post = $this->input->post();
        $id = $post['id'];
        $return = $this->Sinonim->delete($id);
        $msg = 'success';
        echo json_encode($msg);
    }

    public function import(){
        $words = [
            'atau', 'ataupun', 'yang', 'untuk', 'pada', 'ke', 'para', 'namun', 'menurut', 'antara', 'dia', 'dua',
            'ia', 'seperti', 'jika', 'jika', 'sehingga', 'kembali', 'dan', 'tidak', 'ini', 'karena',
            'kepada', 'oleh', 'saat', 'harus', 'sementara', 'setelah', 'belum', 'kami', 'sekitar',
            'bagi', 'serta', 'di', 'dari', 'telah', 'sebagai', 'masih', 'hal', 'ketika', 'adalah',
            'itu', 'dalam', 'bisa', 'bahwa', 'atau', 'hanya', 'kita', 'dengan', 'akan', 'juga',
            'ada', 'mereka', 'sudah', 'saya', 'terhadap', 'secara', 'agar', 'lain', 'anda',
            'begitu', 'mengapa', 'kenapa', 'yaitu', 'yakni', 'daripada', 'itulah', 'lagi', 'maka',
            'tentang', 'demi', 'dimana', 'kemana', 'pula', 'sambil', 'sebelum', 'sesudah', 'supaya',
            'guna', 'kah', 'pun', 'sampai', 'sedangkan', 'selagi', 'sementara', 'tetapi', 'apakah',
            'kecuali', 'sebab', 'selain', 'seolah', 'seraya', 'seterusnya', 'tanpa', 'agak', 'boleh',
            'dapat', 'dsb', 'dst', 'dll', 'dahulu', 'dulunya', 'anu', 'demikian', 'tapi', 'ingin',
            'juga', 'nggak', 'mari', 'nanti', 'melainkan', 'oh', 'ok', 'seharusnya', 'sebetulnya',
            'setiap', 'setidaknya', 'sesuatu', 'pasti', 'saja', 'toh', 'ya', 'walau', 'tolong',
            'tentu', 'amat', 'apalagi', 'bagaimanapun', 'apa', 'apakah', 'bagaimana', 'anda', 'saya', 'kamu', 'agak',
            'berapa', 'siapa', 'berapakah',
        ];

        for ($i=0; $i < count($words); $i++) { 
            $input['stopword'] = $words[$i];

            $status = $this->Stoplist->save($input);
            echo $status;
        }
    }

}