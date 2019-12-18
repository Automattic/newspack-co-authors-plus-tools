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
	 * Converts tags starting with $tag_author_prefix to Guest Authors, and assigns them to the Post.
	 *
	 * @param int $post_id Post ID.
	 */
	public function convert_tags_to_guest_authors( $post_id ) {
		$post_tags = get_the_tags( $post_id );
		if ( false === $post_tags ) {
			return;
		}

		$authors_names    = $this->convert_tags_to_author_names( $post_tags );
		$guest_author_ids = $this->create_guest_authors( $authors_names );
		$this->assign_guest_authors_to_post( $guest_author_ids, $post_id );
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
	 * @return array An array of author names.
	 */
	private function convert_tags_to_author_names( array $tags ) {
		$authors_names = [];
		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				if ( substr( $tag->name, 0, strlen( $this->tag_author_prefix ) ) == $this->tag_author_prefix ) {
					$authors_names[] = substr( $tag->name, strlen( $this->tag_author_prefix ) );
				}
			}
		}

		return $authors_names;
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
}
