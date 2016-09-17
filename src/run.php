#!/usr/local/bin/php
<?php

error_reporting(-1);

require '../vendor/autoload.php';
require 'color.php';

use Dompdf\Dompdf;

$pdf = new Dompdf();
$pdf->setPaper('A4', 'portrait');

if (php_sapi_name() !== 'cli') {
	$pdf->loadHtml(file_get_contents('template.php'));
	$pdf->render();
	$pdf->stream('tmp.pdf', ['Attachment' => false]);

	exit(0);
}

function println($s) {
	echo trim($s) . "\n";
}

function ask($q, $fn = null, $def = null) {
	println($q . ($def ? ' (' . $def . ')' : '') . ':');

	$ret = null;

	if (!$fn) {
		$ret = readline('$ ');
		if (!$ret) {
			$ret = $def;
		}
	} else {
		do {
			$ret = readline('$ ');
			if (!$ret) {
				$ret = $def;
			}
		} while (!$fn($ret));
	}

	return $ret;
}

function vars() {
	$config = [
		'client_profile' => 'usw',
		'company_profile' => 'codemash',
	];

	// Client profile.
	$config['client_profile'] = ask('Client profile', function ($v) {
		$found = count(glob('profiles/' . $v . '.php'));
		if (!$found) {
			println(color::set('Profile "' . $v . '" cannot be found. Try again.', 'red'));
		}
		return $found;
	}, $config['client_profile']);

	// Company profile.
	// $config['company_profile'] = ask('Company profile', function ($v) {
	// 	$found = count(glob('profiles/' . $v . '.php'));
	// 	if (!$found) {
	// 		println(color::set('Profile "' . $v . '" cannot be found. Try again.', 'red'));
	// 	}
	// 	return $found;
	// }, $config['company_profile']);

	$vars = array_merge(
		eval('?> ' . file_get_contents('profiles/' . $config['company_profile'] . '.php')),
		eval('?> ' . file_get_contents('profiles/' . $config['client_profile'] . '.php')),
		eval('?> ' . file_get_contents('settings.php'))
	);

	return $vars;
}

$vars = vars();

function uploadToGoogleDrive($file_name) {
	global $gservice;
	global $vars;

	println('Uploading to Google Drive...');

	$mime = 'application/pdf';

	$file = new Google_Service_Drive_DriveFile();
	$file->setMimeType($mime);
	$file->setName(pathinfo($file_name, PATHINFO_FILENAME));

	$file->setParents([$vars['google_upload_folder']]);

  	$gservice->files->create($file, [
        'data' => file_get_contents($file_name),
        'mimeType' => $mime
    ]);

    println(color::set('Uploaded.', 'green'));

    unlink($file_name);
}

function getGoogleDriveService() {
	global $vars;

	$gclient = new Google_Client();

	// Credentials come from the API console.
	$gcred = __DIR__ . '/google_credentials.json';
	if (!file_exists($gcred)) {
		println(color::red('Cannot upload, no google_credentials.json found. Download it from the API console.'));
		return;
	}

	$gclient->setAuthConfig($gcred);
	$gclient->setApplicationName('InvoiceUploader');
	$gclient->setScopes(Google_Service_Drive::DRIVE);
	$gclient->setAccessType('offline');

	$gauth = @file_get_contents('google_access_token.json');
	if ($gauth) {
		$gclient->setAccessToken($gauth);
	}

	if ($gclient->isAccessTokenExpired()) {
		$response = $gclient->refreshToken($vars['google_refresh_token']);
		if (isset($response['access_token'])) {
			file_put_contents('google_access_token.json', json_encode($response));
		}
	}

	return new Google_Service_Drive($gclient);
}

$gservice = getGoogleDriveService();

function guessGoogleDriveInvoiceId() {
	global $gservice;
	global $vars;

	$page_token = null;
	$uploaded_invoices = [];

	do {
		try {
			$params = [
				'q' => '\'' . $vars['google_upload_folder'] . '\' in parents'
			];
			if ($page_token) {
				$params['pageToken'] = $page_token;
			}
			$files = $gservice->files->listFiles($params);

			$uploaded_invoices = array_merge($uploaded_invoices, $files->getFiles());

			$page_token = $files->getNextPageToken();
		} catch (Exception $e) {
			$page_token = null;
		}
	} while ($page_token);

	if (!count($uploaded_invoices)) {
		return null;
	}

	$sorter = function (&$v) {
		$parts = explode('_', $v->name);
		$ret = 0;

		if ($v->trashed) {
			return 0;
		}
		if (count($parts) && is_numeric($parts[0])) {
			$ret = (int) $parts[0];
		}
		$v->invoice_id = $ret;
		return $ret;
	};

	usort($uploaded_invoices, function ($a, $b) use ($sorter) {
		return $sorter($a) < $sorter($b);
	});

	if (count($uploaded_invoices)) {
		$first = reset($uploaded_invoices);
		return [
			'last_invoice_name' => $first->name,
			'next_id' => $first->invoice_id + 1
		];
	}

	return null;
}

println('');

$guessed = guessGoogleDriveInvoiceId();
if ($guessed) {
	println('Last saved file: ' . $guessed['last_invoice_name'] . ', next invoice id is ' . $guessed['next_id']);
}

$invoice_nr = ask('Invoice nr', function ($v) {
	$is_numeric = is_numeric($v);
	if (!$is_numeric) {
		println(color::set('Invoice nr must be numeric. Try again.', 'red'));
	}
	return $is_numeric;
}, $guessed ? $guessed['next_id'] : null);
$vars['num'] = $invoice_nr;

$date = ask('Date', function ($v) {
	$dt = DateTime::createFromFormat('Y-m-d', $v);
	$is_date = ($dt !== false && !array_sum($dt->getLastErrors()));
	if (!$is_date) {
		println(color::set('Date must be in Y-m-d format. Try again.', 'red'));
	}
	return $is_date;
}, date('Y-m-d'));

$temp = DateTime::createFromFormat('Y-m-d', $date);
$date = $temp->format('Y-m-d');

$vars['date'] = $date;
$vars['pay_date'] = date('Y-m-d', strtotime('+30 days', strtotime($date)));

println('');
println('Enter invoice rows below. Leave empty to proceed.');

$rows = [];
$sub_total_price = 0;

do {
	$row = [];

	$description = ask('Row description', function($v) use ($rows) {
		if (!count($rows) && !$v) {
			println(color::set('A description is required.', 'red'));
			return $v;
		}
		return true;
	});

	if (!$description && count($rows)) {
		break;
	}

	$row['desc'] = $description;

	$price = ask('Row price', function($v) {
		$valid = (is_numeric($v) && $v > 0);
		if (!$valid) {
			println(color::set('Price must be numeric and is required.', 'red'));
		}
		return $valid;
	});
	$price = (int) $price;

	$row['price'] = $price;

	println(color::set('- ' . $description . ': ' . $price . ' EUR', 'yellow_bg+white'));

	$sub_total_price += $price;

	$rows[] = $row;
} while(true);

println('Added ' + count($rows) . ' rows, with a sub total of ' . $sub_total_price . ' EUR');

$vat = $vars['vat'];
$vat_price = $sub_total_price * ($vat / 100);
println('VAT: ' . $vat_price . ' EUR (' . $vat . '%)');

$total_price = $vat_price + $sub_total_price;
println(color::set('Total invoice price: ' . $total_price . ' EUR', 'green_bg+white'));

$vars['sub_total_price'] = $sub_total_price;
$vars['vat_price'] = $vat_price;
$vars['total_price'] = $total_price;

ob_start();
include 'template.php';
$content = ob_get_contents();
ob_end_clean();

foreach ($vars as $k => $v) {
	$content = str_replace('{{ ' . $k . ' }}', $v, $content);
}

$pdf->loadHtml($content);
$pdf->render();

$invoice_file_name = $invoice_nr . '_' . str_replace('-', '_', $date) . '_' . $vars['client_on_invoice_name'];

$output = $pdf->output();
$file_name = '../' . $invoice_file_name . '.pdf';
file_put_contents($file_name, $output);

uploadToGoogleDrive($file_name);

exit(0);