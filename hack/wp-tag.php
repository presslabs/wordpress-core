#!/usr/bin/env php
<?php
// vim: set ft=php:
@$wp_version = $argv[1];

if (empty($wp_version)) {
	require dirname( __DIR__ ) . "/wp-includes/version.php";
}

@list( $major, $minor, $patch ) = explode('.', $wp_version, 3);

if ( $patch == null ) {
	echo "$major.$minor.0\n";
} else {
	echo "$wp_version\n";
}
