<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        $this->load->model('JobQueue');
        $this->load->model('RunningInstance');
        $this->load->helper('url');
    }
    
    public function index()
    {
        $ip = $this->input->ip_address();
        $item = $this->RunningInstance->getByIP($ip);
        
        $data = [
            'running_demo' => false,
            'port' => 0
        ];
        
        if ($item) {
            $data['running_demo'] = true;
            $data['port'] = $item->docker_public_port;
            $data['time_left'] = 3600 - ((new DateTime())->getTimestamp() - $item->start_time);
        }
        
        $this->load->view('welcome_message', $data);
    }

    public function createContainer() {
        $ip = $this->input->ip_address();
        $item = $this->RunningInstance->getByIP($ip);
        
        if (!$item) {
            $job = new JobQueue_Item();
            $job->action = 'RunDockerImage';
            $job->parameters = $this->input->ip_address();
            $job->date = (new DateTime())->getTimestamp();

            $this->JobQueue->add($job);
        }
        
        header('Refresh:0;url='.  site_url('Welcome/demoLoop'));
    }
    
    public function demoLoop() {
        $ip = $this->input->ip_address();
        $item = $this->RunningInstance->getByIP($ip);
        
        echo "Please, wait while we create your demo";
        
        if ($item) {
            redirect('http://demo.passman.cc:' . $item->docker_public_port);
        }
        else {
            header('Refresh:10;url='.  site_url('Welcome/demoLoop'));
            exit();
        }
    }
    
    public function instanceReady() {
        $ip = $this->input->ip_address();
        $item = $this->RunningInstance->getByIP($ip);
        
        if ($item) {
            echo json_encode([
                'status' => 'ok', 
                'port' => $item->docker_public_port
            ]);
        }
        else {
            echo json_encode(['status' => 'off']);
        }
    }
}