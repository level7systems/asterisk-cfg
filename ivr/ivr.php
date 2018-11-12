#!/usr/bin/php -q
<?php

class Ivr
{
    const ERROR_SOUND_ID = 580;

    protected $debug = true;
    protected $stdin;
    protected $stdout;
    protected $srderr;
    protected $pid;
    private $ami;

    private $sounds_played   = 0;
        
    protected $sounds_dir = '/etc/asterisk/sounds';
    
    protected $sounds = array(
        "pass" => 0,
        "sounds" => array(),
        "repeats" => 3,
    );
    
    
    /**
     * Class constructor
     */
    public function __construct()
    {
        global $argv;
        
        $this->pid = getmypid();
        
        date_default_timezone_set('UTC');
        error_reporting(E_ALL);
        set_time_limit(0);
        set_error_handler(array(__CLASS__, 'error_handler'));
        register_shutdown_function(array(__CLASS__, 'shutdown_handler'));

        $channel = $argv[1];
        
        /* here we create STDIN and STDOUT to talk to asterisk */
        $this->stdin = fopen('php://stdin', 'r');
        $this->stdout = fopen('php://stdout', 'w');
        $this->stderr = fopen('php://stderr', 'w');

        if (!isset($argv[1])) {
            $this->error("Error: argv[1] missing");
        }
        
        $this->logMsg("Starting [".$this->pid."]");
        
        sleep(1);

        // preExecute

        $sound = $this->sounds_dir . '/' . $argv[1];
        
        $this->sounds['sounds'] = array($sound);
        
        $this->sendSounds();
        
        /* Start main Loop */
        $this->mainLoop();
    }
    
    public static function error_handler($n, $m, $f, $l)
    {
        syslog(LOG_ERR, "Error: ".basename($f)." [line $l]: ($n) $m");
    }
    
    public static function shutdown_handler()
    {
        $error = error_get_last();
        
        if ($error['type'] == 1) {
            syslog(LOG_ERR, "Error: ".$error['message']);
        }
    }
    
    /**
     * Class destructor
     */
    public function __destruct()
    {
        $this->logMsg("Cleaning up.");
    }
    
    
    /**
     * This must be overwriten by implementations
     */
    protected function processLastSound($data)
    {
        $this->sendSounds();
    }
    
    /* 
     * Main Loop, here we listen for DTMF
     * and send commands via STDOUT
     * 
     */
    protected function mainLoop()
    {
        while (!feof($this->stdin)) {
            $temp = trim(fgets($this->stdin));
            
            
            $t = explode(",",$temp);
            
            $tag = null;
            $timestamp = null;
            $data = null;
            
            if (isset($t[0])) $tag = trim($t[0]);
            if (isset($t[1])) $timestamp = trim($t[1]);
            if (isset($t[2])) $data = trim($t[2]);
            
            $this->logMsg("$tag, $timestamp, $data");
            
            /* Catch the last sound */
            if ($tag == 'F') {
                $sounds_total = count($this->sounds['sounds']);
                
                $this->sounds_played++;
                
                $this->logMsg("*** Played sounds count ".$this->sounds_played.' out of '.$sounds_total);
                
                if ($this->sounds_played == $sounds_total) {
                    $this->logMsg("*** Last sound - $data");
                    
                    $this->processLastSound($data);
                }
            }
        }
        
        sleep(1);
    }
    
    /**
     * Sends sounds to Asterisk
     */
    protected function sendSounds()
    {
        $this->sounds_played = 0;
        
        $this->logMsg("*** sendSounds()");
        
        if (!$this->sounds['sounds']) {
            $this->error('No sounds to send');
        }
        
        $i = 0;
        
        foreach ($this->sounds['sounds'] as $sound) {
            if ($i == 0) {
                fwrite($this->stdout,'S,'.$sound."\n");
            } else {
                fwrite($this->stdout,'A,'.$sound."\n");
            }
            
            $i++;
        }
    }
    
    
    /**
     * Error occured
     * 
     * $param string $msg
     * 
     * Sets X-VM-ERROR variable
     */
    protected function error($msg)
    {
        global $argv;
        
        if ($this->debug) {
            syslog(LOG_DEBUG,$argv[0]."Error: ".$msg);
        }
        
        $msg = str_replace(","," ",$msg);
        
        fwrite($this->stdout,"V,X-IVR-OP=error,X-IVR-ERROR=$msg\n");
        fwrite($this->stdout,"E,Error\n");
        
        sleep(1);
        
        die;
    }
    
    /**
     * Logs message to the console (and log file in debug mode)
     *
     * @param string 
     */
    protected function logMsg($msg)
    {
        if ($this->debug) {
            fwrite($this->stderr, $msg."\n");
            
            syslog(LOG_DEBUG,$msg);
        }
    }
}

$ivr = new Ivr();
