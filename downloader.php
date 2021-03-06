<?php
define('SITE_DIR', dirname(__FILE__) . '/');
define('LIB_DIR', SITE_DIR . 'lib/');

require_once LIB_DIR . 'FWI/Downloader/HttpDownloader.php';
require_once LIB_DIR . 'FWI/Stream/SocketStreamReader.php';
require_once LIB_DIR . 'FWI/Stream/FileStreamWriter.php';
require_once LIB_DIR . 'FWI/Event/EventDispatcher.php';

function calcPercent($readBytes, $sourceSize)
{
	return floor($readBytes / $sourceSize * 100);
}

function simple(DownloadEvent $event)
{
	static $previous = -1;
	
	$percent = calcPercent($event->getReadBytes(), $event->getSourceSize());
	if ($previous != $percent) {
		$i = 0;
		echo json_encode(array(
				'read_bytes' => $event->getReadBytes(),
				'size' => $event->getSourceSize(),
				'percent' => $percent,
				'transfer_speed' => $event->getTransferSpeed()
		)) . "\n";
		ob_flush();
		
		$previous = $percent;
	}
}

function exception_handler($exception)
{
	header('HTTP/1.1 500 Internal Server Error');
	
	echo $exception;
}

$file = isset($_GET['file']) ? $_GET['file'] : null;
$to = isset($_GET['to']) ? $_GET['to'] : null;
if (!$file) {
	return;
}

$eventDis = new EventDispatcher();
$eventDis->addListener('download.read', 'simple');
$downloader = new HttpDownloader();
$downloader->setStreamReader(new SocketStreamReader());
$downloader->setStreamWriter(new FileStreamWriter(true));
$downloader->setEventDispatcher($eventDis);

// Przygotowanie
header('Content-type: text/plain');
ob_implicit_flush(true);
set_exception_handler('exception_handler');

// Główna metoda
$downloader->download($file, SITE_DIR . $to);
