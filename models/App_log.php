<?php
namespace globasa_api;

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class App_log {
    private $log = [];
    private $emails;
    private $debug = false;
    private $start_usage;

    /**
     * Creates log manager.
     * 
     * @param $d    Debug mode
     */
    public function __construct() {
        $this->start_usage = getrusage();
    }

    /**
     * Add message to app log, display in debug mode.
     * 
     * $param $msg  Message to add to log
     */
    public function add($msg) {
        $this->log[] = $msg;
        if ($this->debug) {
            echo("> ".html_entity_decode($msg).PHP_EOL);
        }
    }

    /**
     * Emails log to configured email addresses.
     * 
     * param array $c   Config for username/password
     */
    public function email_log(array $c) {
        $mail = new PHPMailer(true); //Create an instance; passing `true` enables exceptions
    
        $message = "Log from nightly update. (This is API2 work-in-progress and currently the globasa-dictionary website uses API1 to collect data. So there may be discrepencies between this and actual data on the website.)".PHP_EOL.PHP_EOL;
        foreach($this->log as $item) {
            $message .= "- ".html_entity_decode($item).PHP_EOL.PHP_EOL;
        }


        //
        // Time Stuff
        //
        
        $human_time = "This script executed in " . number_format(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4) . " seconds.";
        $usage = getrusage();
        $udelta =
            ($usage["ru_utime.tv_sec"] - $this->start_usage["ru_utime.tv_sec"]) +
            (($usage["ru_utime.tv_usec"] - $this->start_usage["ru_utime.tv_usec"])/1000);
        $sdelta =
            ($usage["ru_stime.tv_sec"] - $this->start_usage["ru_stime.tv_sec"]) +
            (($usage["ru_stime.tv_usec"] - $this->start_usage["ru_stime.tv_usec"])/1000);
            
            $computer_time = "Also, this script's CPU execution time was system: {$sdelta} / user: {$udelta}.";
        echo("Time >\t{$human_time}\nTime >\t{$computer_time}\n");
        $message .= "- ".$human_time.PHP_EOL.PHP_EOL."- ".$computer_time.PHP_EOL.PHP_EOL;

        try {
            //Server settings
            $mail->SMTPDebug = SMTP::DEBUG_OFF;                         //Enable verbose debug output SMTP::DEBUG_CLIENT
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = $c['smtp_host'];                        //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->CharSet = "UTF-8";
            $mail->Encoding = 'base64';
            $mail->Username   = $c['smtp_username'];                    //SMTP username
            $mail->Password   = $c['smtp_password'];                    //SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
            $mail->Port       = 465;                                    //TCP port to connect to 465; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
            //Recipients
            $mail->setFrom($c['smtp_username'], 'Menalari Update');
            foreach($c['app_log_emails'] as $email) {
                $mail->addAddress($email);     //Add a recipient
            }
    
            //Content
            $mail->isHTML(false);                                  //Set email format to HTML
            $mail->Subject = 'Today\'s update';     
            if ($c['dev']) $mail->Subject = "Dev update";
            $mail->Body    = $message;
    
            $mail->send();
            echo "Mail >\tMessage has been sent to ".implode(", ", $this->emails)."\n";
        } catch (Exception $e) {
            echo "Message could not be sent to ".$email."\n";
            echo "Mailer Error: {$mail->ErrorInfo}\n\n";
        }
    }


    /**
     * Returns the most recently added message. Used for testing.
     */
    public function get_last_message() {
        return $this->log[array_key_last($this->log)];
    }


    public function setDebug() {
        $this->debug = true;
    }

    public function setEmails($emails) {
        $this->emails = $emails;
    }
}