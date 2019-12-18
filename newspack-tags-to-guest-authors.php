<?php
/**
 * Plugin Name: Newspack Co-Authors Plus Tools
 * Description: Helper CLI tools for mass processing content for use with the Co-Authors Plus plugin.
 * Author: Automattic
 * Author URI: https://newspack.blog/
 * License: GPL2
 **/

if ( class_exists( 'WP_CLI' ) ) {
	require_once 'lib/cli/class-newspack-co-authors-plus-tools-cli-command.php';
	WP_CLI::add_command( 'newspack_tags_to_authors assign_authors', 'Newspack_Co_Authors_Plus_Tools_Cli_Command' );
}
