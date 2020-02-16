<?php
/**
 * Plugin Name: Newspack Co-Authors Plus Tools
 * Description: Helper CLI tools for mass processing content for use with the Co-Authors Plus plugin.
 * Author: Automattic
 * Author URI: https://newspack.blog/
 * License: GPL2
 *
 * @package Newspack
 **/

if ( class_exists( 'WP_CLI' ) ) {
	require_once 'lib/cli/class-newspack-co-authors-plus-tools-tags-with-prefix-to-guest-authors-command.php';
	require_once 'lib/cli/class-newspack-co-authors-plus-tools-tags-with-taxonomy-to-guest-authors-command.php';
	WP_CLI::add_command( 'newspack-co-authors-plus-tools tags-with-prefix-to-guest-authors', 'Newspack_Co_Authors_Plus_Tools_Tags_With_Prefix_To_Guest_Authors_Command' );
	WP_CLI::add_command( 'newspack-co-authors-plus-tools tags-with-taxonomy-to-guest-authors', 'Newspack_Co_Authors_Plus_Tools_Tags_With_Taxonomy_To_Guest_Authors_Command' );
}
