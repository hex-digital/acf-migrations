<?php

function console( $message, $type = false ) {

    $output = '--> ';

    if ( $type == 'error' ) {
        $output .= "\033[1;31mError:\033[0m " . $message;
    } else {
        $output .= $message;
    }

    echo $output . PHP_EOL;
    exit;
}

// Get the absolute template directory
$options = getopt("t:");

// Check for the existance of the -t flag
if (!isset($options['t'])) {
    echo '--> Please specify an absolute template directory with the -t flag' . PHP_EOL;
    exit;
}

// Get the template directory
$template_directory = $options['t'];

// Include the migrations class
include 'migrations.php';

// Get the local migrations file
$migrations_file = $template_directory . '/' . Migrations::STORAGE_DIRECTORY . '/migrations.php';

// Include the local migrations file
if ( ! file_exists( $migrations_file ) ) {
    console( 'Migrations file does not exist', 'error' );
} else {
    include $migrations_file;
}

global $migrations;

if ( ! isset( $migrations ) ) {
    console( 'Migrations variable is not set', 'error' );
}

// Check for the instance of $migrations
if ( ! $migrations instanceof Migrations ) {
    console( 'Migrations variable is not an instance of Migrations', 'error' );
}

// Generate the fields
if ( ! $migrations instanceof Migrations || ! $migrations->generate( $template_directory ) ) {
    console( 'Failed to generate migrations export file', 'error' );
} else {
    console( 'Successfully generated migrations export file' );
}
