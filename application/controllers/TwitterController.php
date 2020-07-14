<?php
// defined('BASEPATH') OR exit('No direct script access allowed');
use Sastrawi\Dictionary\ArrayDictionary;


class TwitterController extends CI_Controller {
    public function __construct()
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        parent::__construct();

        $this->load->model("Twitter");
        $this->load->model("Faq");
        $this->load->model("Stoplist");

    }

    public function index(){
        $data = $this->Twitter->getAll();

        echo json_encode($data);
    }

    public function create(){
        $stream_clean = $this->security->xss_clean($this->input->raw_input_stream);
        $request = json_decode($stream_clean);

        $duplicate = $this->Twitter->getByUrl($request->urlPost);

        $input['pertanyaan'] = $request->pertanyaan;
        $input['jawaban'] = $request->jawaban;
        $input['pengirim'] = $request->pengirim;
        $input['tanggal'] = $request->tanggal;
        $input['url'] = $request->urlPost;

        if (count($duplicate) == 0) {
            $status = $this->Twitter->save($input);
            $this->addFaq($input);
            $status = "Berhasil disimpan";
            
        }

        else {
            $status = "Gagal disimpan, data sudah ada";
        }

        echo json_encode($status);
    }

    public function addFaq($data){
        $input['pertanyaan'] = $data['pertanyaan'];
        $input['jawaban'] = $data['jawaban'];
        $input['sumber'] = 'twitter';
        $input['hasil_pertanyaan'] = $this->textProcessing($data['pertanyaan']);

        $status = $this->Faq->save($input);

        // echo json_encode($status);
    }

    public function textProcessing($kalimat){
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
}