<?php

namespace globasa_api;

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class App_log
{
    private $log = [];
    private $emails;
    private $debug = false;
    private $start_usage;
    private $level;
    private $instance_name;

    /**
     * Creates log manager.
     * 
     * @param $d    Debug mode
     */
    public function __construct(array $config)
    {
        $this->start_usage = getrusage();
        if (isset($config['report_level'])) {
            $this->level = $config['report_level'];
        } else {
            $this->level = 1;
        }
        $this->instance_name = $config['instance_name'];
    }

    /**
     * Add message to app log, display in debug mode.
     * 
     * $param $msg  Message to add to log
     */
    public function add(string $text, bool $indent = false)
    {

        $this->log[] = ($indent ? "\t- " : '- ') . $text;

        // Display only if in debug mode
        if ($this->debug) {
            // \pard\m(html_entity_decode($text));
        }
    }

    public function add_report(array $report, string $title)
    {
        $this->add($title . " (add log)");
        foreach ($report as $data) {
            if (is_array($data)) {
                $this->add(text: "_" . $data['term'] . "_ : " . $data['msg'], indent: true);
            } else {
                $this->add(text: $data . " (**This log entry didn't have a term**)", indent: true);
                print($data . PHP_EOL);
            }
        }
    }

    /**
     * Emails log to configured email addresses.
     * 
     * param array $c   Config for username/password
     */
    public function email_log(array $c)
    {
        $mail = new PHPMailer(true); //Create an instance; passing `true` enables exceptions

        $message = "Log from nightly update. (This is API2 work-in-progress and currently the globasa-dictionary website uses API1 to collect data. So there may be discrepencies between this and actual data on the website.)" . PHP_EOL . PHP_EOL;
        foreach ($this->log as $item) {
            $message .= html_entity_decode($item) . PHP_EOL;
        }


        //
        // Time Stuff
        //

        $human_time = "This script executed in " . number_format(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4) . " seconds.";
        $usage = getrusage();
        $udelta =
            ($usage["ru_utime.tv_sec"] - $this->start_usage["ru_utime.tv_sec"]) +
            (($usage["ru_utime.tv_usec"] - $this->start_usage["ru_utime.tv_usec"]) / 1000);
        $sdelta =
            ($usage["ru_stime.tv_sec"] - $this->start_usage["ru_stime.tv_sec"]) +
            (($usage["ru_stime.tv_usec"] - $this->start_usage["ru_stime.tv_usec"]) / 1000);

        $computer_time = "Also, this script's CPU execution time was system: {$sdelta} / user: {$udelta}.";
        \pard\m($human_time, "Human Time");
        \pard\m($computer_time, "Computer Time");
        \pard\m($sdelta, "System time");
        \pard\m($udelta, "User time");

        $message .= "- " . $human_time . PHP_EOL . "- " . $computer_time . PHP_EOL . PHP_EOL;
        $message_html = $c['parsedown']->text($message);
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
            $mail->setFrom($c['smtp_from'],  $this->instance_name . ' Update');
            foreach ($c['app_log_emails'] as $email) {
                $mail->addAddress($email);     //Add a recipient
            }

            //Content
            $mail->isHTML(false);                                  //Set email format to HTML
            $mail->Subject = $this->instance_name . ' update ' . date("M d");
            if ($c['dev']) $mail->Subject = $this->instance_name . " Import Script";
            $mail->Body    = $message_html;
            $mail->AltBody = $message;

            $mail->send();
            \pard\m(implode(", ", $this->emails), "Mail sent");
        } catch (Exception $e) {
            \pard\m("Message could not be sent to " . $email . "\n", "Mailer Error", true);
            \pard\m("{$mail->ErrorInfo}", "Mailer Error", true);
        }
    }


    /**
     * Returns the most recently added message. Used for testing.
     */
    public function get_last_message()
    {
        return $this->log[array_key_last($this->log)];
    }


    public function setDebug()
    {
        $this->debug = true;
    }

    public function setEmails($emails)
    {
        $this->emails = $emails;
    }
}
