<?php
/*
Plugin Name: Jiti Search Logger
Description: Enregistre les recherches effectuées sur le site et affiche les 50 dernières dans le tableau de bord.
Version: 0.2
Author: Jiti
Author URI: https://jiti.me/
License: Copyleft
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Empêcher l'accès direct.
}

// Chemin du fichier pour stocker les mots-clés.
define( 'SEARCH_LOGGER_FILE', plugin_dir_path( __FILE__ ) . 'keywords.php' );

/**
 * Initialise le fichier keywords.php s'il n'existe pas.
 */
function search_logger_initialize_file() {
    if ( ! file_exists( SEARCH_LOGGER_FILE ) ) {
        file_put_contents( SEARCH_LOGGER_FILE, "<?php return [];");
    }
}
add_action( 'init', 'search_logger_initialize_file' );

/**
 * Capture les recherches via la requête de recherche.
 */
function search_logger_capture_query( $query ) {
    if ( is_admin() || ! is_search() || ! $query->is_main_query() ) {
        return;
    }

    $search_term = get_search_query();

    if ( ! empty( $search_term ) ) {
        $search_term = sanitize_text_field( stripslashes( $search_term ) );
        $file = SEARCH_LOGGER_FILE;
        $current_keywords = file_exists( $file ) ? include $file : [];

        // Ajouter le terme de recherche en haut du tableau si unique.
        if ( ! in_array( $search_term, $current_keywords ) ) {
            array_unshift( $current_keywords, $search_term );
        }

        // Conserver uniquement les 50 derniers termes.
        $current_keywords = array_slice( $current_keywords, 0, 50 );

        file_put_contents( $file, "<?php return " . var_export( $current_keywords, true ) . ";" );
    }
}
add_action( 'pre_get_posts', 'search_logger_capture_query' );

/**
 * Ajoute un widget au tableau de bord pour afficher les recherches récentes.
 */
function search_logger_dashboard_widget() {
    wp_add_dashboard_widget( 'search_logger_widget', 'Dernières recherches', 'search_logger_display_widget' );
}
add_action( 'wp_dashboard_setup', 'search_logger_dashboard_widget' );

/**
 * Affiche le contenu du widget.
 */
function search_logger_display_widget() {
    $file = SEARCH_LOGGER_FILE;

    if ( isset( $_POST['search_logger_clear'] ) ) {
        if ( file_exists( $file ) ) {
            file_put_contents( $file, "<?php return [];");
        }
        echo '<p>L\'historique a été effacé avec succès.</p>';
    }

    if ( file_exists( $file ) ) {
        $keywords = include $file;
        if ( !empty( $keywords ) ) {
            echo '<ul style="list-style: none; padding: 0; margin: 0">';
            $index = 0;
            foreach ( $keywords as $keyword ) {
                $search_url = home_url( '?s=' . urlencode( $keyword ) );
                $background_color = $index % 2 === 0 ? '#FAFAFB' : 'transparent';
                echo '<li style="background-color: ' . esc_attr( $background_color ) . '; padding: 8px">✎ <a href="' . esc_url( $search_url ) . '" target="_blank" style="text-decoration: none; color: inherit;">' . esc_html( $keyword ) . '</a></li>';
                $index++;
            }
            echo '</ul>';
        } else {
            echo '<p>Aucune recherche pour le moment.</p>';
        }
    } else {
        echo '<p>Aucune recherche pour le moment.</p>';
    }

    echo '<form method="post" style="margin-top: 10px;">';
    echo '<button type="submit" name="search_logger_clear" class="button">Effacer l\'historique</button>';
    echo '</form>';
}
