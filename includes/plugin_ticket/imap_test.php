<?php


$login    = 'your@gmail.com'; // your full gmail or google apps email address
$password = 'password'; // your gmail or google apps password

$server = '{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX';
$mbox = imap_open( $server, $login, $password ) or die( "can't connect: " . imap_last_error() );


imap_errors();

$MC = imap_check( $mbox );
echo 'Connected! We found ' . $MC->Nmsgs . " messages<br>\n";

imap_errors();

echo "Getting the first 10 emails:<br>\n";
$result = imap_fetch_overview( $mbox, "1:" . min( 10, $MC->Nmsgs ), 0 );
foreach ( $result as $overview ) {
	echo " - Email subject: " . (string) $overview->subject . " <br>\n";
}

imap_errors();


imap_close( $mbox );
imap_errors();

echo "Complete! <br>\n";
