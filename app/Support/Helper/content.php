<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Services\Content\ContentService;
use App\Services\PostService;
use App\Services\TermService;
use App\Models\Taxonomy;
use App\Models\Post;
use App\Models\Term;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Content management helpers.
 */
if (! function_exists('register_post_type')) {
    function register_post_type(string $name, array $args = [])
    {
        $args['name'] = $name;
        return app(ContentService::class)->registerPostType($args);
    }
}

/**
 * Register a taxonomy.
 *
 * @param  string  $name  Taxonomy name
 * @param  array  $args  Arguments for the taxonomy
 * @param  mixed  $postTypes  Post types to associate with the taxonomy
 * @return Taxonomy
 */
if (! function_exists('register_taxonomy')) {
    function register_taxonomy(string $name, array $args = [], $postTypes = null)
    {
        $args['name'] = $name;
        return app(ContentService::class)->registerTaxonomy($args, $postTypes);
    }
}

if (! function_exists('get_post_types')) {
    function get_post_types()
    {
        return app(ContentService::class)->getPostTypes();
    }
}

if (! function_exists('get_post_type')) {
    function get_post_type(string $name)
    {
        return app(ContentService::class)->getPostType($name);
    }
}

if (! function_exists('get_taxonomies')) {
    function get_taxonomies()
    {
        return app(ContentService::class)->getTaxonomies();
    }
}

if (! function_exists('get_posts')) {
    function get_posts(array $args = []): LengthAwarePaginator
    {
        return app(PostService::class)->getPosts($args);
    }
}

if (! function_exists('get_post')) {
    function get_post($id, ?string $postType = null): ?Post
    {
        return app(PostService::class)->getPostById($id, $postType);
    }
}

if (! function_exists('get_post_date')) {
    function get_post_date(Post|int|null $post, string $format = 'M d, Y'): ?string
    {
        return app(PostService::class)->getPostDate($post, $format);
    }
}

if (! function_exists('get_permalink')) {
    function get_permalink(Post|int|null $post)
    {
        return app(PostService::class)->getPostPermalink($post);
    }
}

if (! function_exists('get_post_terms')) {
    function get_post_terms(Post|int|null $post, ?string $taxonomy = null)
    {
        return app(PostService::class)->getPostTerms($post, $taxonomy);
    }
}

if (! function_exists('get_post_type_icon')) {
    function get_post_type_icon(string $postType): string
    {
        return match ($postType) {
            'post' => 'lucide:file-text',
            'news' => 'lucide:newspaper',
            'announcement' => 'lucide:megaphone',
            'page' => 'lucide:file',
            default => 'lucide:files'
        };
    }
}

if (! function_exists('get_taxonomy_icon')) {
    function get_taxonomy_icon(string $taxonomy): string
    {
        return match ($taxonomy) {
            'category' => 'lucide:folder',
            'tag' => 'lucide:tags',
            default => 'lucide:bookmark'
        };
    }
}

if (! function_exists('get_post_status_class')) {
    function get_post_status_class(string $status): string
    {
        return match ($status) {
            PostStatus::PUBLISHED->value => 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
            PostStatus::DRAFT->value => 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
            PostStatus::PENDING->value => 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
            PostStatus::SCHEDULED->value => 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400',
            PostStatus::PRIVATE->value => 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-400',
            'created' => 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
            'edited' => 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800 dark:bg-cyan-900/20 dark:text-cyan-400',
            'approved' => 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400',
            'unpublished' => 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400',
            'archived' => 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
            default => 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
        };
    }
}

if (! function_exists('get_terms')) {
    function get_terms(array $args = []): LengthAwarePaginator
    {
        return app(TermService::class)->getTerms($args);
    }
}

if (! function_exists('get_term')) {
    function get_term($id, ?string $taxonomy = null): ?Term
    {
        return app(TermService::class)->getTermById($id, $taxonomy);
    }
}
