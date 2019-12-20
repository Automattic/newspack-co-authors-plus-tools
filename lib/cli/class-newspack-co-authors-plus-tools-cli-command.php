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
	 * @param array $assoc_args AssocArgs: --unset-author-tags will allso unset the "author tags" from the post after converting
	 *                          them to Guest Users.
	 */
	public function __invoke( $args = array(), $assoc_args = array() ) {
		$unset_author_tags = isset( $assoc_args['unset-author-tags'] ) ? true : false;

		global $coauthors_plus;
		include_once __DIR__ . '/../class-newspack-tags-to-guest-authors.php';
		include_once __DIR__ . '/../../../co-authors-plus/co-authors-plus.php';
		$coauthors_guest_authors = new CoAuthors_Guest_Authors();
		$tags_to_guest_authors   = new Newspack_Tags_To_Guest_Authors( $coauthors_plus, $coauthors_guest_authors );

		$args      = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			// The WordPress.VIP.PostsPerPage.posts_per_page_posts_per_page coding standard doesn't like '-1' (all posts) for
			// posts_per_page value, so we'll set it to something really high.
			'posts_per_page' => 1000000,
		);
		$the_query = new WP_Query( $args );

		$total_posts = $the_query->found_posts;
		WP_CLI::line( 'Converting tags to Guest Authors for ' . $total_posts . ' Posts...' );

		$posts_number = 0;
		$progress_bar = \WP_CLI\Utils\make_progress_bar( 'Progress', $total_posts );
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$progress_bar->tick();
				$posts_number++;
				$the_query->the_post();
				$tags_to_guest_authors->convert_tags_to_guest_authors( get_the_ID(), $unset_author_tags );
			}
		}

		wp_reset_postdata();

		WP_CLI::line( 'Done!' );
	}
}
