# Newspack Co-Authors Plus Tools

Helper CLI tools for mass processing content for use with the Co-Authors Plus plugin.

Depends on the Co-Authors Plus plugin - this plugin's not fancy :) so make sure you install it yourself, or it will break in errors.

## Example CLI uses

`wp newspack-co-authors-plus-tools tags-to-guest-authors [--unset-author-tags]`
- runs through all the public Posts, and converts those tags beginning with "author:" to Co-Authors Plus Guest Authors, and assigns them to the post as (co-)authors;
- the optional `--unset-author-tags` argument will also unset these author tags from the posts.
