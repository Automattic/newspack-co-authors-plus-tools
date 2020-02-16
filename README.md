# Newspack Co-Authors Plus Tools

Helper CLI tools for mass processing content for use with the Co-Authors Plus plugin.

## Installation

First install the [Co-Authors Plus plugin](https://wordpress.org/plugins/co-authors-plus/), and then install this Plugin (see Releases).

## Example CLI uses

`wp newspack-co-authors-plus-tools tags-to-guest-authors [--unset-author-tags]`
- runs through all the public Posts, and converts those tags beginning with "author:" to Co-Authors Plus Guest Authors, and assigns them to the post as (co-)authors;
- the optional `--unset-author-tags` argument will also unset these author tags from the posts.

`wp newspack-co-authors-plus-tools tags-with-taxonomy-to-guest-authors writers`
- runs through all the public Posts, and converts those tags that have the "writers" taxonomy assigned to them to Co-Authors Plus Guest Authors, and assigns them to the post as (co-)authors;
