<?php
// defined('BASEPATH') OR exit('No direct script access allowed');
// require_once __DIR__ . '/vendor/autoload.php';
use Sastrawi\Dictionary\ArrayDictionary;

class Preprocessing extends CI_Controller {
    public function __construct()
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        parent::__construct();
        $this->load->model("Faq");
        $this->load->model("Stoplist");
        $this->load->model("Sinonim");
    }

    public function index(){
        // $kalimat = "nini sangat pengertian";
        $kalimat = $this->input->post('kalimat');
        
        $hasil = $this->removeRedundanChar($kalimat);
        $hasil = $this->removeNonWord($hasil);
        $hasil = $this->caseFolding($hasil);
        $hasil = $this->removeSimbol($hasil);
        $hasil = $this->removeNumber($hasil);
        $hasil = $this->removeSpace($hasil);
        $hasil = $this->removeStopWord($hasil);
        $input = $this->stemmer($hasil);

        if ($input == "" || $input == " ") {
            $input = $kalimat;
        }

        $data_faq = $this->cekFaq();

        $nilai_max = 0;
        $index_output = 0;

        for ($i=0; $i < count($data_faq); $i++) { 
            $pertanyaan = $data_faq[$i]['hasil_pertanyaan'];
            // echo $input . " - " . $pertanyaan . "<br>";
            $input2 = $this->cek_sinonim($input, $pertanyaan);
            // echo $input2 . "<br><br>";

            $url = 'https://loko-cosine.herokuapp.com/';
            $data = array('text1' => $input2, 'text2' => $pertanyaan);

            $options = array(
                'http' => array(
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data)
                )
            );
            $context  = stream_context_create($options);
            $result[$i]['id'] = $data_faq[$i]['id'];
            $result[$i]['score'] = file_get_contents($url, false, $context);
            $result[$i]['pertanyaan'] = $data_faq[$i]['pertanyaan'];
            $result[$i]['jawaban'] = $data_faq[$i]['jawaban'];
            
            if ($result[$i]['score'] >= $nilai_max && $result[$i]['score'] != 0) {
                $nilai_max = $result[$i]['score'];
                $output[$index_output] = $result[$i];
                $index_output+=1;
            }
            
        }

        $index_final = 0;

        $status_jadwal = $this->cekJadwal($kalimat);

        if ($status_jadwal) {
            $final_output[$index_final]['pertanyaan'] = "Menu cek jadwal keberangkatan kereta";
            $final_output[$index_final]['jawaban'] = "init";
            $index_final++;
        }

        if($nilai_max == 0) {
            $final_output[$index_final]['jawaban'] = "Maaf aku tidak tau maksud kamu";
        }

        else {
            if (count($output) >= 1) {
                for ($i=0; $i < count($output); $i++) { 
                    if ($output[$i]['score'] >= $nilai_max) {
                        $final_output[$index_final] = $output[$i];
                        $index_final++;
                    }
                }
            }
        }

        // echo count($output);

        echo json_encode($final_output);
    }

    public function cekFaq(){
        $data = $this->Faq->getAll();
       return $data;
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
        $simbol = [",", ".", "?", "!", "(", ")","…",'/','%'];
        
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

    public function removeStopWord($kalimat){
        $sentence = $kalimat;

        $stopWordRemoverFactory = new \Sastrawi\StopWordRemover\StopWordRemoverFactory();
        $stemmerFactory = new \Sastrawi\Stemmer\StemmerFactory();

        $stoplist = $this->Stoplist->getAll();

        for ($i=0; $i < count($stoplist); $i++) { 
            $words[$i] = $stoplist[$i]['stopword'];
        }
        // $words = [
        //     'atau', 'ataupun', 'yang', 'untuk', 'pada', 'ke', 'para', 'namun', 'menurut', 'antara', 'dia', 'dua',
        //     'ia', 'seperti', 'jika', 'jika', 'sehingga', 'kembali', 'dan', 'tidak', 'ini', 'karena',
        //     'kepada', 'oleh', 'saat', 'harus', 'sementara', 'setelah', 'belum', 'kami', 'sekitar',
        //     'bagi', 'serta', 'di', 'dari', 'telah', 'sebagai', 'masih', 'hal', 'ketika', 'adalah',
        //     'itu', 'dalam', 'bisa', 'bahwa', 'atau', 'hanya', 'kita', 'dengan', 'akan', 'juga',
        //     'ada', 'mereka', 'sudah', 'saya', 'terhadap', 'secara', 'agar', 'lain', 'anda',
        //     'begitu', 'mengapa', 'kenapa', 'yaitu', 'yakni', 'daripada', 'itulah', 'lagi', 'maka',
        //     'tentang', 'demi', 'dimana', 'kemana', 'pula', 'sambil', 'sebelum', 'sesudah', 'supaya',
        //     'guna', 'kah', 'pun', 'sampai', 'sedangkan', 'selagi', 'sementara', 'tetapi', 'apakah',
        //     'kecuali', 'sebab', 'selain', 'seolah', 'seraya', 'seterusnya', 'tanpa', 'agak', 'boleh',
        //     'dapat', 'dsb', 'dst', 'dll', 'dahulu', 'dulunya', 'anu', 'demikian', 'tapi', 'ingin',
        //     'juga', 'nggak', 'mari', 'nanti', 'melainkan', 'oh', 'ok', 'seharusnya', 'sebetulnya',
        //     'setiap', 'setidaknya', 'sesuatu', 'pasti', 'saja', 'toh', 'ya', 'walau', 'tolong',
        //     'tentu', 'amat', 'apalagi', 'bagaimanapun', 'apa', 'apakah', 'bagaimana', 'anda', 'saya', 'kamu', 'agak',
        //     'berapa', 'siapa', 'berapakah',
        // ];

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

    public function cek_sinonim($kalimat1, $kalimat2){

        $sinonim = $this->Sinonim->getAll();
      
        for ($i=0; $i < count($sinonim); $i++){
            $sinonim[$i]['words'] = json_decode($sinonim[$i]['words']);
            for ($j=0; $j < count($sinonim[$i]['words']); $j++) { 
                $data_sinonim[$i][$j] = $sinonim[$i]['words'][$j];
            }
        }

        $kalimat1 = explode(" ", $kalimat1);
        $kalimat2 = explode(" ", $kalimat2);

        for ($i=0; $i < count($kalimat1); $i++) { 
            $status = 0;
            
            for ($j=0; $j < count($kalimat2); $j++) { 
                if($kalimat1[$i] == $kalimat2[$j]){
                    $status = 1;
                }
            }
            if ($status == 0) {
                for ($k=0; $k < count($data_sinonim); $k++) { 
                    $score1 = false;
                    $score2 = false;
                    for ($l=0; $l < count($data_sinonim[$k]); $l++) { 
                        for ($m=0; $m < count($kalimat2); $m++) { 
                            if ($kalimat2[$m] == $data_sinonim[$k][$l]) {
                                $score2 = true;
                                $kata = $kalimat2[$m];
                            }
                        }
                        if ($kalimat1[$i] == $data_sinonim[$k][$l]) {
                            $score1 = true;
                            if ($score2 == true && $score1 == true) {
                                $kalimat1[$i] = $kata;
                            }
                        }
                    }
                }
            }
        }
        $kalimat1 = implode(" ", $kalimat1);
        return $kalimat1;
    }

    public function cekJadwal($kalimat){
        $kalimat = explode(" ", $kalimat);

        $status = false;

        for ($i=0; $i < count($kalimat); $i++) { 
            if ($kalimat[$i] == "jadwal") {
                $status = true;
            }
        }

        return $status;
    }

    public function cobaSinonim(){
        $kalimat1 = "railcard";
        $kalimat2 = "dear info kereta reguler non pso rute jakarta-yogya operasi kalau tidak salah ingat batal jalan cuma juni moga tidak panjang";

        $sinonim = $this->Sinonim->getAll();
      
        for ($i=0; $i < count($sinonim); $i++){
            $sinonim[$i]['words'] = json_decode($sinonim[$i]['words']);
            for ($j=0; $j < count($sinonim[$i]['words']); $j++) { 
                $data_sinonim[$i][$j] = $sinonim[$i]['words'][$j];
            }
        }

        $kalimat1 = explode(" ", $kalimat1);
        $kalimat2 = explode(" ", $kalimat2);

        for ($i=0; $i < count($kalimat1); $i++) { 
            $status = 0;
            
            for ($j=0; $j < count($kalimat2); $j++) { 
                if($kalimat1[$i] == $kalimat2[$j]){
                    $status = 1;
                }
            }
            if ($status == 0) {
                for ($k=0; $k < count($data_sinonim); $k++) { 
                    $score1 = false;
                    $score2 = false;
                    for ($l=0; $l < count($data_sinonim[$k]); $l++) { 
                        for ($m=0; $m < count($kalimat2); $m++) { 
                            if ($kalimat2[$m] == $data_sinonim[$k][$l]) {
                                $score2 = true;
                                $kata = $kalimat2[$m];
                            }
                        }
                        if ($kalimat1[$i] == $data_sinonim[$k][$l]) {
                            $score1 = true;
                            if ($score2 == true && $score1 == true) {
                                $kalimat1[$i] = $kata;
                            }
                        }
                    }
                }
            }
        }
        $kalimat1 = implode(" ", $kalimat1);
        print_r($kalimat1);
    }

}