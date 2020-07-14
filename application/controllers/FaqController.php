<?php
// defined('BASEPATH') OR exit('No direct script access allowed');
use Sastrawi\Dictionary\ArrayDictionary;

class FaqController extends CI_Controller {
    public function __construct()
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        parent::__construct();

        $this->load->model("Faq");
        $this->load->model("Stoplist");
        $this->load->model("Twitter");
    }

    public function index(){
        $data = $this->Faq->getAll();

        echo json_encode($data);
    }

    public function create(){
        $post = $this->input->post();

        $input['pertanyaan'] = $this->input->post('pertanyaan');
        $input['jawaban'] = $this->input->post('jawaban');
        $input['hasil_pertanyaan'] = $this->textProcessing($input['pertanyaan']);

        $status = $this->Faq->save($input);

        echo json_encode($status);
    }

    public function update(){
        $post = $this->input->post();
        $id = $this->input->post('id');

        $input['pertanyaan'] = $this->input->post('pertanyaan');
        $input['jawaban'] = $this->input->post('jawaban');
        $input['hasil_pertanyaan'] = $this->textProcessing($input['pertanyaan']);

        $status = $this->Faq->update($id, $input);

        echo json_encode($status);
    }

    public function delete($id)
    {
        $return = $this->Faq->delete($id);
        $msg = 'success';
        echo json_encode($msg);
    }

    public function textProcessing($kalimat){
        $hasil = $this->caseFolding($kalimat);
        $hasil = $this->removeRedundanChar($hasil);
        $hasil = $this->removeNewLine($hasil);
        // $hasil = $this->removeNonWord($hasil);
        $hasil = $this->removeSimbol($hasil);
        $hasil = $this->removeNumber($hasil);
        $hasil = $this->removeSpace($hasil);
        $hasil = $this->removeStopWord($hasil);
        $input = $this->stemmer($hasil);

        if ($input == "" || $input == " ") {
            $input = $kalimat;
        }

        return $input;
    }

    public function removeRedundanChar($kalimat){
        $string = explode(" ",$kalimat);
        for ($j=0; $j < count($string); $j++) { 
            $split = str_split($string[$j]);
            for ($k=0; $k < count($split); $k++) { 
                if ($k != 0 && $split[$k] == $split[$k-1]) {
                    unset($split[$k]);
                    $split = array_values($split);
                    $k--;
                }  
            }
            $string[$j] = implode("",$split);
        }
        $string = implode(" ",$string);
        
        return $string;
    }

    public function caseFolding($kalimat){
        
        $kalimat = strtolower($kalimat);
        
        return $kalimat;
    }

    public function removeNonWord($kalimat){
        
        $kalimat = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $kalimat);
    
        return $kalimat;
    }

    public function removeSimbol($kalimat){
        $simbol = [",", ".", "?", "!", "(", ")","…",'/','%','@'];
        
        for ($j=0; $j < count($simbol); $j++) { 
            $kalimat = str_replace($simbol[$j],' ',$kalimat);
        }
        
        return $kalimat;
    }

    public function removeNumber($kalimat){

        $kalimat = preg_replace('/[0-9]+/', '', $kalimat);
        
        return $kalimat;
    }

    public function removeSpace($kalimat){
        $string = explode(" ",$kalimat);
        $string = array_filter($string);
        $string = implode(" ",$string);
    
        return $string;
    }

    public function removeNewLine($kalimat){
        $string = trim(preg_replace('/\s+/', ' ', $kalimat));

        return $string;
    }

    public function removeStopWord($kalimat){
        $sentence = $kalimat;

        $stopWordRemoverFactory = new \Sastrawi\StopWordRemover\StopWordRemoverFactory();
        $stemmerFactory = new \Sastrawi\Stemmer\StemmerFactory();

        $stoplist = $this->Stoplist->getAll();

        for ($i=0; $i < count($stoplist); $i++) { 
            $words[$i] = $stoplist[$i]['stopword'];
        }
    
        $dictionary = new ArrayDictionary($words);
        
        $stopword = new \Sastrawi\StopWordRemover\StopWordRemover($dictionary);

        $output = $stopword->remove($sentence);

        return $output;
    }

    public function stemmer($kalimat){
        $sentence = $kalimat;

        $stemmerFactory = new \Sastrawi\Stemmer\StemmerFactory();

        $dictionary = $stemmerFactory->createDefaultDictionary();
        $dictionary->addWordsFromTextFile(__DIR__.'/my-dictionary.txt');

        $stemmer = new \Sastrawi\Stemmer\Stemmer($dictionary);
        $output = $stemmer->stem($sentence);

        return $output;
    }

    public function refreshData(){
        $data = $this->Faq->getAll();

        for ($i=0; $i < count($data); $i++) { 
            $input['pertanyaan'] = $data[$i]['pertanyaan'];
            $input['jawaban'] = $data[$i]['jawaban'];
            $input['hasil_pertanyaan'] = $this->textProcessing($input['pertanyaan']);

            $status = $this->Faq->update($data[$i]['id'], $input);
            echo $input['hasil_pertanyaan'] . "<br><br>";
        }
        
    }

    public function hapusTwitter(){
        $return = $this->Faq->deleteTwitter();
        echo json_encode($return);
    }

    public function tambahTwitter(){
        $data_tweet = $this->Twitter->getAll();

        for ($i=0; $i < count($data_tweet); $i++) { 
            $input['pertanyaan'] = $data_tweet[$i]['pertanyaan'];
            $input['jawaban'] = $data_tweet[$i]['jawaban'];
            $input['hasil_pertanyaan'] = $this->textProcessing($data_tweet[$i]['pertanyaan']);

            $status = $this->Faq->save($input);

            echo json_encode($status);
        }

    }

    public function percobaan(){
        $kalimat = "@KAI121
        min KA jombang-jakarta sudah beroprasi belum ya?";

        $hasil = $this->caseFolding($kalimat);
        $hasil = $this->removeRedundanChar($hasil);
        $hasil = $this->removeNewLine($hasil);
        $hasil = $this->removeSimbol($hasil);
        $hasil = $this->removeNumber($hasil);
        $hasil = $this->removeSpace($hasil);
        $hasil = $this->removeStopWord($hasil);
        $input = $this->stemmer($hasil);

        echo json_encode($hasil);
    }

}