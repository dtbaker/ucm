<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
	->withPaths([
		__DIR__ . '/includes',
	])
	->withSkip([
		__DIR__ . '/includes/plugin_pdf/dompdf',
		__DIR__ . '/includes/plugin_encrypt/phpseclib',
		__DIR__ . '/includes/plugin_email/class.phpmailer.php',
		__DIR__ . '/includes/plugin_email/class.pop3.php',
		__DIR__ . '/includes/plugin_email/class.smtp.php',
		__DIR__ . '/includes/plugin_security/htmlpurifier',
		__DIR__ . '/includes/plugin_paymethod_stripe/stripe-php',
		__DIR__ . '/includes/plugin_paymethod_authorize/anet_php_1.1.8',
		__DIR__ . '/includes/plugin_paymethod_coinbase/lib',
		__DIR__ . '/includes/plugin_captcha/inc',
		__DIR__ . '/includes/plugin_paymethod_multisafepay/MultiSafepay.combined.php',
		__DIR__ . '/includes/plugin_ticket/cron/mime_parser.php',
		__DIR__ . '/includes/plugin_signature/lib',
		__DIR__ . '/includes/plugin_file/upload/plupload',
		// Temporarily skip: Rector breaks this file due to mixed PHP/HTML/JS in switch/case
		__DIR__ . '/includes/plugin_file/file.php',
	])
	->withIndent("\t")
	->withPhpSets(php84: true)
	->withPreparedSets(
		codeQuality: true,
		codingStyle: true,
		deadCode: true,
		typeDeclarations: true,
	);
