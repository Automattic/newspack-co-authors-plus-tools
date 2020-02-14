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
			if ( false !== strrpos( $plugin, 'Dco-authors-plus.php' ) ) {
				$active = true;
			}
		}

		return $active;
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
	private function create_guest_authors( array $authors_names ) {
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
	private function assign_guest_authors_to_post( array $guest_author_ids, $post_id ) {
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
