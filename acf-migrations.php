<?php
/**
 * Plugin Name: ACF Migrations
 * Plugin URI: https://github.com/hex-digital/acf-migrations
 * Description: An easy way to migrate local fields and field groups using readable object oriented syntax (without the need for huge arrays)
 * Author: Hex Digital
 * Author URI: http://hexdigital.com
 * Version: 0.0.1
 */

// Include the migrations class
include 'migrations.php';

// Add the generation button to the admin menu
add_action( 'admin_menu', 'migrations_menu' );

function migrations_menu() {
    add_management_page( 'My Plugin Options', 'ACF Migrations', 'manage_options', 'acf-migrations', 'migrations_options' );
}

function migrations_options() {
    if ( ! current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    echo '<div class="wrap">';
    echo '<h1>ACF Migrations</h1>';
    echo '<p>Click on the button below to generate the ACF Migrations code for the Advanced Custom Fields plugin.</p>';

    if ( isset( $_POST['generate'] ) && $_POST['generate'] == 'true' ) {
        if ( migrate() ) {
            echo '<p><strong>Fields successfully migrated.</strong></p>';
        }
    }

    echo '<form method="post">';
    echo '<p class="submit">';
    echo '<input type="hidden" name="generate" value="true">';
    echo '<input type="submit" name="submit" id="submit" class="button button-primary" value="Generate export code">';
    echo '</p>';
    echo '</form>';
    echo '</div>';
}

function migrate() {
    $migrations_file = get_template_directory() . '/' . Migrations::STORAGE_DIRECTORY . '/migrations.php';
    if ( ! file_exists( $migrations_file ) ) wp_die( __( 'Migrations file does not exist.' ) );

    include $migrations_file;

    if ( $migrations instanceof Migrations ) {
        return $migrations->generate( get_template_directory() );
    }

    return false;
}
