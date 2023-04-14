<?php
namespace globasa_api;

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class App_log {
    private $log;
    private $emails;
    private $debug;

    /**
     * Creates log manager.
     * 
     * @param $d    Debug mode
     */
    public function __construct($emails=null, $debug=false) {
        $this->log = array();
        $this->emails = $emails;
        $this->debug = $debug;
    }

    /**
     * Add message to app log, display in debug mode.
     * 
     * $param $msg  Message to add to log
     */
    public function add($msg) {
        $this->log[] = $msg;
        if ($this->debug) {
            echo("App Log added:".PHP_EOL);
            echo($msg.PHP_EOL);
        }
    }

    /**
     * Emails log to configured email addresses.
     */
    public function email_log($c) {
        $mail = new PHPMailer(true); //Create an instance; passing `true` enables exceptions
    

        $message = "Log from nightly update. (This is API2 work-in-progress and currently the globasa-dictionary website uses API1 to collect data. So there may be discrepencies between this and actual data on the website.)".PHP_EOL.PHP_EOL;
        foreach($this->log as $item) {
            $message .= "- ".html_entity_decode($item).PHP_EOL.PHP_EOL;
        }
        
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
            $mail->Body    = $message;
    
            $mail->send();
            echo 'Message has been sent to '.$email."\n";
        } catch (Exception $e) {
            echo "Message could not be sent to ".$email."\n";
            echo "Mailer Error: {$mail->ErrorInfo}\n\n";
        }
    }
}