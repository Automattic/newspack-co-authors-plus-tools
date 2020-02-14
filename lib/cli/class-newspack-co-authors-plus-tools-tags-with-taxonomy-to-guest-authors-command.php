<?php
/**
 * Command class, tags with taxonomy to authors.
 *
 * @package Newspack
 */

/**
 * Class Newspack_Co_Authors_Plus_Tools_Tags_With_Taxonomy_To_Guest_Authors_Command.
 */
class Newspack_Co_Authors_Plus_Tools_Tags_With_Taxonomy_To_Guest_Authors_Command extends WP_CLI_Command {

	/**
	 * Convert all published Posts tags which have assigned a certain taxonomy to Guest Authors.
	 *
	 * @param array $args       Args.
	 * @param array $assoc_args AssocArgs: --unset-author-tags will allso unset the "author tags" from the post after converting
	 *                          them to Guest Users.
	 */
	public function __invoke( $args = array(), $assoc_args = array() ) {

		// Validate and get arguments/params.
		if ( ! isset( $args[0] ) || empty( $args[0] ) ) {
			WP_CLI::error( 'Invalid taxonomy name.' );
		}
		$taxonomy = $args[0];
		$unset_author_tags = isset( $assoc_args['unset-author-tags'] ) ? true : false;

		global $coauthors_plus;
		include_once __DIR__ . '/../class-newspack-tags-to-guest-authors.php';
		include_once __DIR__ . '/../../../co-authors-plus/co-authors-plus.php';
		$coauthors_guest_authors = new CoAuthors_Guest_Authors();
		$tags_to_guest_authors   = new Newspack_Tags_To_Guest_Authors( $coauthors_plus, $coauthors_guest_authors );

		if ( false === $tags_to_guest_authors->is_coauthors_active() ) {
			WP_CLI::error( 'The Co-authors Plus plugin does not seem to be active.' );
		}

		// TODO
	}
}
