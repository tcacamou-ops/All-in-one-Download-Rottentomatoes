<?php
/**
 * Plugin Name: All-in-one Download Rotten Tomatoes
 * Plugin URI: https://github.com/tcacamou-ops/All-in-one-Download-Rottentomatoes
 * Description: Add-on for All-in-one Download that adds support for **Rotten Tomatoes** URLs.
 * Version: 0.0.2
 * Author: tcacamou
 * Author URI: https://github.com/tcacamou-ops
 * Text Domain: all-in-one-download-rotten-tomatoes
 * Domain Path: /languages
 * Requires PHP: 7.4
 * License: Proprietary
 */

namespace AllI1D\RottenTomatoes;

use AllI1D\Actions\Logs;

// Standard plugin security, keep this line in place.
defined( 'ABSPATH' ) || die();

// Include Composer autoload for any dependencies (if needed)
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

if ( is_admin() ) {
    new Admin();
    $updater = new Updater(
        __FILE__,                                      // Main plugin file.
        'https://github.com/tcacamou-ops/All-in-one-Download-Rottentomatoes'  // Repository URL.
    );

    $updater->init();
}

function process_movie( $media ) {
    do_action('alli1d_log', 'Rotten tomatoes - Processing movie: ' . $media->url, Logs::NOTICE, Logs::MEDIAS_LOG);

    // Initialize cURL to fetch the page content
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, $media->url );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
    $html = curl_exec( $ch );
    curl_close( $ch );

    if ( !$html ) {
        do_action('alli1d_log', 'Rotten tomatoes - Failed to fetch content from URL: ' . $media->url, Logs::ERROR, Logs::MEDIAS_LOG);
        return $media;
    }

    // Extract the title from the <rt-text slot="title" ...> tag
    if ( preg_match( '/<rt-text[^>]*slot="title"[^>]*>(.*?)<\/rt-text>/i', $html, $matches ) ) {
        $media->title = trim($matches[1]); // Sanitize the title to use it as a file name
    } else {
        do_action('alli1d_log', 'Rotten tomatoes - Failed to extract title from URL: ' . $media->url, Logs::ERROR, Logs::MEDIAS_LOG);
        return $media;
    }

    // Extract the image URL from the <rt-img slot="posterImage" ...> tag
    if ( preg_match( '/<rt-img[^>]*slot="posterImage"[^>]*src="([^"]+)"[^>]*>/i', $html, $matches ) ) {
        $image_url = $matches[1];
    } else if (preg_match( '/<rt-img[^>]*alt="poster image"[^>]*src="([^"]+)"[^>]*>/i', $html, $matches ) ) {
        $image_url = $matches[1];
    } else {
        do_action('alli1d_log', 'Rotten tomatoes - Failed to extract image URL from URL: ' . $media->url, Logs::ERROR, Logs::MEDIAS_LOG);
        return $media;
    }

    // Download the image and save it to the uploads folder
    $upload_dir = wp_upload_dir();
    $rottentomatoes_dir = $upload_dir['basedir'] . '/rottentomatoes/';
    if ( ! file_exists( $rottentomatoes_dir ) ) {
        wp_mkdir_p( $rottentomatoes_dir );
    }

    $image_name = sanitize_file_name( $media->title ) . '.jpeg';
    $image_path = $rottentomatoes_dir . $image_name;

    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, $image_url );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
    $image_data = curl_exec( $ch );
    curl_close( $ch );

    if ( $image_data ) {
        file_put_contents( $image_path, $image_data );
        $media->cover_image = $upload_dir['baseurl'] . '/rottentomatoes/'. $image_name;
    } else {
        do_action('alli1d_log', 'Rotten tomatoes - Failed to download image from URL: ' . $image_url, Logs::ERROR, Logs::MEDIAS_LOG);
    }

    $media->type = 'movie';
    $media->found = 'true';
    do_action('alli1d_log', 'Rotten tomatoes - Movie updated: ' . $media->title, Logs::DEBUG, Logs::MEDIAS_LOG);
    return $media;
}

function process_tv_show( $media ) {
    do_action('alli1d_log', 'Rotten tomatoes - Processing TV show: ' . $media->url, Logs::NOTICE, Logs::MEDIAS_LOG);

    // Initialize cURL to fetch the page content
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, $media->url );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
    $html = curl_exec( $ch );
    curl_close( $ch );

    if ( !$html ) {
        do_action('alli1d_log', 'Rotten tomatoes - Failed to fetch content from URL: ' . $media->url, Logs::ERROR, Logs::MEDIAS_LOG);
        return $media;
    }

    // Extract the title from the <rt-text slot="title" ...> tag
    if ( preg_match( '/<rt-text[^>]*slot="title"[^>]*>(.*?)<\/rt-text>/i', $html, $matches ) ) {
        $media->title = trim( $matches[1] ); // Sanitize the title to use it as a file name
    } else {
        do_action('alli1d_log', 'Rotten tomatoes - Failed to extract title from URL: ' . $media->url, Logs::ERROR, Logs::MEDIAS_LOG);
        return $media;
    }

    // Extract the image URL from the <rt-img slot="posterImage" ...> tag
    if ( preg_match( '/<rt-img[^>]*slot="posterImage"[^>]*src="([^"]+)"[^>]*>/i', $html, $matches ) ) {
        $image_url = $matches[1];
    } else if (preg_match( '/<rt-img[^>]*alt="poster image"[^>]*src="([^"]+)"[^>]*>/i', $html, $matches ) ) {
        $image_url = $matches[1];
    } else {
        do_action('alli1d_log', 'Rotten tomatoes - Failed to extract image URL from URL: ' . $media->url, Logs::ERROR, Logs::MEDIAS_LOG);
        return $media;
    }

    // Download the image and save it to the uploads folder
    $upload_dir = wp_upload_dir();
    $rottentomatoes_dir = $upload_dir['basedir'] . '/rottentomatoes/';
    if ( ! file_exists( $rottentomatoes_dir ) ) {
        wp_mkdir_p( $rottentomatoes_dir );
    }

    $image_name = sanitize_file_name( $media->title ) . '.jpeg';
    $image_path = $rottentomatoes_dir . $image_name;

    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, $image_url );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
    $image_data = curl_exec( $ch );
    curl_close( $ch );

    if ( $image_data ) {
        file_put_contents( $image_path, $image_data );
        $media->cover_image = $upload_dir['baseurl'] . '/rottentomatoes/'. $image_name;
    } else {
        do_action('alli1d_log', 'Rotten tomatoes - Failed to download image from URL: ' . $image_url, Logs::ERROR, Logs::MEDIAS_LOG);
    }

    $media->type = 'tv_show';
    $media->found = 'true';
    do_action('alli1d_log', 'Rotten tomatoes - TV Show updated: ' . $media->title, Logs::DEBUG, Logs::MEDIAS_LOG);
    return $media;
}

function process_media( $media ) {
    // Check if the URL does not contain "rottentomatoes.com"
    if ( strpos( $media->url, 'rottentomatoes.com' ) === false ) {
        do_action('alli1d_log', 'Rotten tomatoes - URL does not contain rottentomatoes.com: ' . $media->url, Logs::ERROR, Logs::MEDIAS_LOG);
        return $media;
    }

    // Check if the URL corresponds to a movie
    if ( strpos( $media->url, '/m/' ) !== false ) {
        return process_movie( $media );
    }

    // Check if the URL corresponds to a TV show
    if ( strpos( $media->url, '/tv/' ) !== false ) {
        return process_tv_show( $media );
    }

    // If the URL matches neither a movie nor a TV show
    do_action('alli1d_log', 'Rotten tomatoes - Unknown media type: ' . $media->url, Logs::NOTICE, Logs::MEDIAS_LOG);
    return $media;
}

add_filter( 'alli1d_process_media', __NAMESPACE__.'\process_media');