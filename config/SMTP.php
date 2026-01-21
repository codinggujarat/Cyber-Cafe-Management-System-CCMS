<?php

class SMTPMailer {
    private $host;
    private $port;
    private $username;
    private $password;
    private $debug = false;

    public function __construct($host, $port, $username, $password) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    public function send($to, $subject, $message) {
        try {
            // For port 465, use ssl:// wrapper. For 587, use tcp:// and upgrade later.
            $protocol = ($this->port == 465) ? "ssl://" : "tcp://";
            $socket = fsockopen($protocol . $this->host, $this->port, $errno, $errstr, 15);
            
            if (!$socket) {
                throw new Exception("Error connecting to '$this->host': $errno - $errstr");
            }

            $this->serverResponse($socket, "220");
            $this->serverRequest($socket, "250", "EHLO " . $this->host . "\r\n");
            
            // Should properly use STARTTLS for 587
            if ($this->port == 587) {
                $this->serverRequest($socket, "220", "STARTTLS\r\n");
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new Exception("TLS negotiation failed");
                }
                // Resend EHLO after TLS handshake
                $this->serverRequest($socket, "250", "EHLO " . $this->host . "\r\n");
            }

            $this->serverRequest($socket, "334", "AUTH LOGIN\r\n");
            $this->serverRequest($socket, "334", base64_encode($this->username) . "\r\n");
            $this->serverRequest($socket, "235", base64_encode($this->password) . "\r\n");
            
            $this->serverRequest($socket, "250", "MAIL FROM: <" . $this->username . ">\r\n");
            $this->serverRequest($socket, "250", "RCPT TO: <" . $to . ">\r\n");
            $this->serverRequest($socket, "354", "DATA\r\n");

            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=utf-8\r\n";
            $headers .= "To: $to\r\n";
            $headers .= "From: " . APP_NAME . " <" . $this->username . ">\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "Date: " . date("r") . "\r\n";

            fwrite($socket, $headers . "\r\n" . $message . "\r\n.\r\n");
            $this->serverResponse($socket, "250");
            
            $this->serverRequest($socket, "221", "QUIT\r\n");
            fclose($socket);

            return true;
        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            // Log for user visibility since they are checking logs
            file_put_contents(__DIR__ . '/../email_log.txt', "SMTP ERROR: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
            return false;
        }
    }

    private function serverRequest($socket, $expected_response, $command = null) {
        if ($command) {
            fwrite($socket, $command);
        }
        
        $this->serverResponse($socket, $expected_response);
    }
    
    private function serverResponse($socket, $expected_response) {
        $server_response = "";
        while (substr($server_response, 3, 1) != ' ') {
            if (!($server_response = fgets($socket, 256))) {
                throw new Exception("Error while fetching server response codes.");
            }
        }

        if (substr($server_response, 0, 3) != $expected_response) {
            throw new Exception("Unable to send email: " . $server_response);
        }
    }
}
