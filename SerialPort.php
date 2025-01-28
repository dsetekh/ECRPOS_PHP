<?php

/**
 * Simple Serial Port Class 
 * Inspired by https://github.com/Xowap/PHP-Serial
 * A simple PHP class to interact with serial ports under mostly Win
 *
 * @author Setekh
 * @license MIT License
 */
class SerialPort
{
    private $device;
    private $handle;
    private $baudRate;
    private $parity;
    private $dataBits;
    private $stopBits;
    private $flowControl;
    private $os;

    public function __construct()
    {
        $this->os = PHP_OS_FAMILY;
    }

    /**
     * Sets the device (e.g., COM1, /dev/ttyS0).
     *
     * @param string $device
     * @return bool
     */
    public function setDevice($device)
    {
        $this->device = $device;
        if (!is_string($device) || empty($device)) {
            trigger_error("Invalid device specified.", E_USER_WARNING);
            return false;
        }
        return true;
    }

    /**
     * Configures the serial port parameters.
     *
     * @param int $baudRate
     * @param string $parity ("none", "even", "odd")
     * @param int $dataBits (5-8)
     * @param int $stopBits (1-2)
     * @param string $flowControl ("none", "rts/cts", "xon/xoff")
     * @return bool
     */
    public function configure($baudRate, $parity = "none", $dataBits = 8, $stopBits = 1, $flowControl = "none")
    {
        $this->baudRate = $baudRate;
        $this->parity = $parity;
        $this->dataBits = $dataBits;
        $this->stopBits = $stopBits;
        $this->flowControl = $flowControl;

        if ($this->os === "Windows") {
            return $this->configureWindows();
        } elseif ($this->os === "Linux" || $this->os === "Darwin") {
            return $this->configureUnix();
        } else {
            trigger_error("Unsupported operating system.", E_USER_WARNING);
            return false;
        }
    }

    /**
     * Opens the serial port.
     *
     * @param string $mode
     * @return bool
     */
    public function open($mode = "r+b")
    {
        if (!$this->device) {
            trigger_error("Device not set. Call setDevice() first.", E_USER_WARNING);
            return false;
        }

		
$this->handle = @fopen($this->device, $mode);


        if (!$this->handle) {
            $error = error_get_last();
            echo "Failed to open device: {$this->device}. Error: {$error['message']}\n";

            trigger_error("Failed to open device: $this->device", E_USER_WARNING);
            return false;
        }

        stream_set_blocking($this->handle, false);
        return true;
    }

    /**
     * Writes data to the serial port.
     *
     * @param string $data
     * @return bool
     */
    public function write($data)
    {
        if (!$this->handle) {
            trigger_error("Device not opened. Call open() first.", E_USER_WARNING);
            return false;
        }

        return fwrite($this->handle, $data) !== false;
    }

    /**
     * Reads data from the serial port.
     *
     * @param int $length
     * @return string|false
     */
    public function read($length = 128)
    {
        if (!$this->handle) {
            trigger_error("Device not opened. Call open() first.", E_USER_WARNING);
            return false;
        }

        return fread($this->handle, $length);
    }

    /**
     * Closes the serial port.
     *
     * @return bool
     */
    public function close()
    {
        if (!$this->handle) {
            return true;
        }

        if (fclose($this->handle)) {
            $this->handle = null;
            return true;
        }

        trigger_error("Failed to close the serial port.", E_USER_WARNING);
        return false;
    }

    /**
     * Configures the serial port on Linux/macOS.
     *
     * @return bool
     */
    private function configureUnix()
    {
        $cmd = sprintf(
            "stty -F %s %d cs%d %s %s %s",
            escapeshellarg($this->device),
            $this->baudRate,
            $this->dataBits,
            $this->stopBits === 1 ? "-cstopb" : "cstopb",
            $this->parity === "none" ? "-parenb" : ($this->parity === "even" ? "parenb -parodd" : "parenb parodd"),
            $this->flowControl === "none" ? "-crtscts -ixon" : ($this->flowControl === "rts/cts" ? "crtscts" : "ixon")
        );

        exec($cmd, $output, $returnVar);
        if ($returnVar !== 0) {
            trigger_error("Failed to configure serial port: " . implode("\n", $output), E_USER_WARNING);
            return false;
        }

        return true;
    }

    /**
     * Configures the serial port on Windows.
     *
     * @return bool
     */
    private function configureWindows()
    {
		
		$fixdevice = preg_replace('/[^A-Za-z0-9]/', '', $this->device); // try what works for you
		
        $cmd = sprintf(
            "mode %s BAUD=%d PARITY=%s DATA=%d STOP=%d",
            //escapeshellarg($this->device),
			$fixdevice,
            $this->baudRate,
            strtoupper($this->parity[0]),
            $this->dataBits,
            $this->stopBits
        );



echo nl2br($cmd."\n");

        exec($cmd, $output, $returnVar);
        if ($returnVar !== 0) {
			
			echo nl2br("ERROR:". implode("\n", $output));
			
            trigger_error("Failed to configure serial port: " . implode("\n", $output), E_USER_WARNING);
            return false;
        }


echo nl2br("Mode command output: ". implode("\n", $output));

        return true;
    }
}

?>
