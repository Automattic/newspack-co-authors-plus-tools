<?php
/**
 * Main plugin class.
 *
 * @package Newspack
 */

/**
 * Class Newspack_Tags_To_Guest_Authors.
 */
class Newspack_Tags_To_Guest_Authors {

	/**
	 * Prefix of tags which get converted to Guest Authors.
	 *
	 * @var string Prefix of a tag which contains a Guest Author's name.
	 */
	private $tag_author_prefix = 'author:';

	/**
	 * Object instance.
	 *
	 * @var CoAuthors_Plus $coauthors_plus
	 */
	private $coauthors_plus;

	/**
	 * Object instance.
	 *
	 * @var CoAuthors_Guest_Authors
	 */
	private $coauthors_guest_authors;

	/**
	 * Newspack_Tags_To_Guest_Authors constructor.
	 *
	 * @param CoAuthors_Plus          $coauthors_plus          Object instance.
	 * @param CoAuthors_Guest_Authors $coauthors_guest_authors Object instance.
	 */
	public function __construct( CoAuthors_Plus $coauthors_plus, CoAuthors_Guest_Authors $coauthors_guest_authors ) {
		$this->coauthors_plus          = $coauthors_plus;
		$this->coauthors_guest_authors = $coauthors_guest_authors;
	}

	/**
	 * Checks whether Co-authors Plus is installed and active.
	 *
	 * @return bool Is active.
	 */
	public function is_coauthors_active() {
		$active = false;
		foreach ( wp_get_active_and_valid_plugins() as $plugin ) {
			if ( false !== strrpos( $plugin, 'co-authors-plus.php' ) ) {
				$active = true;
			}
		}

		return $active;
	}

	/**
	 * Gets posts which have tags with taxonomy.
	 *
	 * @param string $tag_taxonomy Tag taxonomy.
	 *
	 * @return array Array of post IDs found.
	 */
	public function get_posts_with_tag_with_taxonomy( $tag_taxonomy ) {
		global $wpdb;
		$post_ids = [];

		// TODO: switch to WP_Query instead of raw SQL ( e.g. if ( ! taxonomy_exists( $tag ) ) register_taxonomy( $tag, $object_type ) ).
		$sql_get_post_ids_with_taxonomy = <<<SQL
			SELECT DISTINCT wp.ID
			FROM wp_posts wp
			JOIN wp_term_relationships wtr ON wtr.object_id = wp.ID
			JOIN wp_term_taxonomy wtt ON wtt.term_taxonomy_id = wtr.term_taxonomy_id AND wtt.taxonomy = %s
			JOIN wp_terms wt ON wt.term_id = wtt.term_id
			WHERE wp.post_type = 'post'
			AND wp.post_status = 'publish'
			ORDER BY wp.ID;
SQL;
		// phpcs:ignore -- false positive, all params are fully sanitized.
		$results_post_ids               = $wpdb->get_results( $wpdb->prepare( $sql_get_post_ids_with_taxonomy, $tag_taxonomy ), ARRAY_A );

		if ( ! empty( $results_post_ids ) ) {
			foreach ( $results_post_ids as $result_post_id ) {
				$post_ids[] = $result_post_id['ID'];
			}
		}

		return $post_ids;
	}

	/**
	 * For a post ID, gets tags which have the given taxonomy.
	 *
	 * @param int    $post_id         Post ID.
	 * @param string $tag_taxonomy Tag tagxonomy.
	 *
	 * @return array Tag names with given taxonomy which this post has.
	 */
	public function get_post_tags_with_taxonomy( $post_id, $tag_taxonomy ) {
		global $wpdb;
		$names = [];

		// TODO: switch to WP_Query instead of raw SQL ( e.g. if ( ! taxonomy_exists( $tag ) ) register_taxonomy( $tag, $object_type ) ).
		$sql_get_post_ids_with_taxonomy = <<<SQL
			SELECT DISTINCT wt.name
			FROM wp_terms wt
			JOIN wp_term_taxonomy wtt ON wtt.taxonomy = %s AND wtt.term_id = wt.term_id
			JOIN wp_term_relationships wtr ON wtt.term_taxonomy_id = wtr.term_taxonomy_id
			JOIN wp_posts wp ON wp.ID = wtr.object_id AND wp.ID = %d
			WHERE wp.post_type = 'post'
			AND wp.post_status = 'publish'
SQL;
		// phpcs:ignore -- false positive, all params are fully sanitized.
		$results_names                  = $wpdb->get_results( $wpdb->prepare( $sql_get_post_ids_with_taxonomy, $tag_taxonomy, $post_id ), ARRAY_A );
		if ( ! empty( $results_names ) ) {
			foreach ( $results_names as $results_name ) {
				$names[] = $results_name['name'];

			}
		}

		return $names;
	}

	/**
	 * Converts tags starting with $tag_author_prefix to Guest Authors, and assigns them to the Post.
	 *
	 * @param int  $post_id           Post ID.
	 * @param bool $unset_author_tags Should the "author tags" be unset from the post once they've been converted to Guest Users.
	 */
	public function convert_tags_to_guest_authors( $post_id, $unset_author_tags = true ) {
		$all_tags = get_the_tags( $post_id );
		if ( false === $all_tags ) {
			return;
		}

		$author_tags_with_names = $this->get_tags_with_author_names( $all_tags );
		if ( empty( $author_tags_with_names ) ) {
			return;
		}

		$author_tags  = [];
		$author_names = [];
		foreach ( $author_tags_with_names as $author_tag_with_name ) {
			$author_tags[]  = $author_tag_with_name['tag'];
			$author_names[] = $author_tag_with_name['author_name'];
		}

		$guest_author_ids = $this->create_guest_authors( $author_names );
		$this->assign_guest_authors_to_post( $guest_author_ids, $post_id );

		if ( $unset_author_tags ) {
			$new_tags      = $this->get_tags_diff( $all_tags, $author_tags );
			$new_tag_names = [];
			foreach ( $new_tags as $new_tag ) {
				$new_tag_names[] = $new_tag->name;
			}

			wp_set_post_terms( $post_id, implode( ',', $new_tag_names ), 'post_tag' );
		}
	}

	/**
	 * Takes an array of tags, and returns those which begin with the $this->tag_author_prefix prefix, stripping the result
	 * of this prefix before returning.
	 *
	 * Example, if this tag is present in the input $tags array:
	 *      '{$this->tag_author_prefix}Some Name' is present in the $tagas array
	 * it will be detected, the prefix stripped, and the rest of the tag returned as an element of the array:
	 *      'Some Name'.
	 *
	 * @param array $tags An array of tags.
	 *
	 * @return array An array with elements containing two keys:
	 *      'tag' holding the full WP_Term object (tag),
	 *      and 'author_name' with the extracted author name.
	 */
	private function get_tags_with_author_names( array $tags ) {
		$author_tags = [];
		if ( empty( $tags ) ) {
			return $author_tags;
		}

		foreach ( $tags as $tag ) {
			if ( substr( $tag->name, 0, strlen( $this->tag_author_prefix ) ) == $this->tag_author_prefix ) {
				$author_tags[] = [
					'tag'         => $tag,
					'author_name' => substr( $tag->name, strlen( $this->tag_author_prefix ) ),
				];
			}
		}

		return $author_tags;
	}

	/**
	 * Creates Guest Authors from their full names.
	 *
	 * @param array $authors_names Authors' names.
	 *
	 * @return array An array of Guest Author IDs.
	 */
	public function create_guest_authors( array $authors_names ) {
		$guest_author_ids = [];

		foreach ( $authors_names as $author_name ) {
			$author_login = sanitize_title( $author_name );
			$guest_author = $this->coauthors_guest_authors->get_guest_author_by( 'user_login', $author_login );

			// If the Guest author doesn't exist, creates it first.
			if ( false === $guest_author ) {
				$coauthor_id = $this->coauthors_guest_authors->create(
					array(
						'display_name' => $author_name,
						'user_login'   => $author_login,
					)
				);
			} else {
				$coauthor_id = $guest_author->ID;
			}

			$guest_author_ids[] = $coauthor_id;
		}

		return $guest_author_ids;
	}

	/**
	 * Assigns Guest Authors to the Post. Completely overwrites the existing list of authors.
	 *
	 * @param array $guest_author_ids Guest Author IDs.
	 * @param int   $post_id          Post IDs.
	 */
	public function assign_guest_authors_to_post( array $guest_author_ids, $post_id ) {
		$coauthors = [];
		foreach ( $guest_author_ids as $guest_author_id ) {
			$guest_author = $this->coauthors_guest_authors->get_guest_author_by( 'id', $guest_author_id );
			$coauthors[]  = $guest_author->user_nicename;
		}
		$this->coauthors_plus->add_coauthors( $post_id, $coauthors, $append_to_existing_users = false );
	}

	/**
	 * A helper function, returns a diff of $tags_a - $tags_b (filters out $tags_b from $tags_a).
	 *
	 * @param array $tags_a Array of WP_Term objects (tags).
	 * @param array $tags_b Array of WP_Term objects (tags).
	 *
	 * @return array An array of resulting WP_Term objects.
	 */
	private function get_tags_diff( $tags_a, $tags_b ) {
		$tags_diff = [];

		foreach ( $tags_a as $tag ) {
			$tag_found_in_tags_b = false;
			foreach ( $tags_b as $author_tag ) {
				if ( $author_tag->term_id === $tag->term_id ) {
					$tag_found_in_tags_b = true;
					break;
				}
			}

			if ( ! $tag_found_in_tags_b ) {
				$tags_diff[] = $tag;
			}
		}

		return $tags_diff;
	}
}
