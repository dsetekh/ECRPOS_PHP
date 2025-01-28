<?php
/*
* Example usage of Simple Serial Port Access Library.
* Heavily commented + lot's of output messages 
*/
$handle = @fopen("\\\\.\\COM3", "r+b");
if (!$handle) {
    echo nl2br("Failed to open COM3\n");
} else {
    echo nl2br("COM3 opened successfully\n");
    fclose($handle);
}

// Use full path to ensure the file is found
$filePath = __DIR__ . '/SerialPort.php';


if (file_exists($filePath)) {
    try {
        require $filePath;
        //echo nl2br("SerialPort.php included successfully\n");
    } catch (Throwable $e) {
        // Catch any errors during the `require` process
        echo nl2br("Error including SerialPort.php: " . $e->getMessage() . "\n");
        die("Critical error: Unable to continue\n");
    }
} else {
    echo nl2br("Error: SerialPort.php not found.\n");
    die("Critical error: Unable to continue\n");
}


try {
    $serial = new SerialPort();
    
    // Configure COM port

  	$serial->setDevice("\\\\.\\COM3"); // Replace with your COM port
    // $serial->setDevice("COM3");  // Try if above does not work for you
	
    $serial->configure(2400, "none", 8, 1, "none"); // Configure baud rate and other settings
	
    // Open connection
    if ($serial->open()) {
        echo nl2br(">Com port opened\n");

        // Example: V200T protocol - Send handshake request
        $message = "\x02" . "101000999001" . "\x1C" . "MTEST0100" . "\x03";
        $lrc = calculateLRC($message); // Use custom LRC function
        $fullMessage = $message . $lrc;

        $serial->write($fullMessage);
        echo nl2br("<B>Sent: " . bin2hex($fullMessage) . "\n");

        // Receive response (with timeout)
        $start = time();
        $response = '';
        while (time() - $start < 5) { // 5-second timeout, change as needed
            $char = $serial->read(1); // Read 1 byte at a time
            if ($char !== false && $char !== "") {
                $response .= $char;
                if (strpos($response, "\x03") !== false) {
                    break;
                }
            }
        }

        echo nl2br("<B>Received: " . bin2hex($response) . "\n"); // response will have leading \x06 for ACK \x15 for NAK
        // Handle response, send ACK / NAK accordingly, parse etc...
        $serial->close();
    } else {
        echo nl2br("Failed to open serial port\n");
        die("Critical error: Unable to continue\n");
    }
} catch (Throwable $e) {
    echo nl2br("An error occurred: " . $e->getMessage() . "\n");
    die("Critical error: Unable to continue\n");
}

/**
 * Calculate the Longitudinal Redundancy Check (LRC) for a message / as per V200T protocol /.
 *
 * @param string $message
 * @return string
 */
function calculateLRC($message)
{
    $lrc = 0;
    for ($i = 1; $i < strlen($message); $i++) { // Start at index 1 to skip initial \x02
        $lrc ^= ord($message[$i]);
    }
    return chr($lrc);
}

?>
