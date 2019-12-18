<?php
/**
 * Main plugin class.
 *
 * @package Newspack
 */

/**
 * Class Newspack_Co_Authors_Plus_Tools_Cli_Command.
 */
class Newspack_Co_Authors_Plus_Tools_Cli_Command extends WP_CLI_Command {

	/**
	 * Convert all published Posts tags which start with Newspack_Tags_To_Guest_Authors::$tag_author_prefix to Guest Authors.
	 *
	 * @param array $args       Args.
	 * @param array $assoc_args AssocArgs.
	 */
	public function __invoke( $args = array(), $assoc_args = array() ) {
		global $coauthors_plus;
		include_once __DIR__ . '/../class-newspack-tags-to-guest-authors.php';
		include_once __DIR__ . '/../../../co-authors-plus/co-authors-plus.php';
		$coauthors_guest_authors = new CoAuthors_Guest_Authors();
		$tags_to_guest_authors   = new Newspack_Tags_To_Guest_Authors( $coauthors_plus, $coauthors_guest_authors );
		WP_CLI::line( 'Converting...' );

		$args      = array(
			'post_type'   => 'post',
			'post_status' => 'publish',
		);
		$the_query = new WP_Query( $args );

		$posts_number = 0;
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$posts_number++;
				$tags_to_guest_authors->convert_tags_to_guest_authors( get_the_ID() );
			}
		}

		wp_reset_postdata();

		WP_CLI::line( 'Converted tags to Guest Authors for ' . $posts_number . ' Posts.' );
		WP_CLI::line( 'Done!' );
	}

}
