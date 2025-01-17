<?php 

/*
* functions.php is only needed to enable WordPress Features we would like to use later for our REST API
*/

function blank_wordpress_theme_support() {

		 #Enables RSS Feed Links
        add_theme_support( 'automatic-feed-links' );
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );

        add_theme_support( 'custom-logo' );
        add_theme_support(
			'custom-logo',
			array(
				'height'      => 256,
				'width'       => 256,
				'flex-height' => true,
				'flex-width'  => true,
				'header-text' => array( 'site-title', 'site-description' ),
			)
		);
    }

add_action( 'after_setup_theme', 'blank_wordpress_theme_support' );

/* Disable WordPress Admin Bar for all users */
add_filter( 'show_admin_bar', '__return_false' );


/* API Routes */

// Register custom REST API endpoint
function custom_api_get_all_posts()
{
    // Fetch all published posts
    $args = array(
        'post_type' => 'post',
		'post_status' => array('publish', 'pending', 'draft', 'future'),
        'posts_per_page' => -1, // No pagination; retrieves all posts
        'fields' => 'ids', // Retrieve only IDs to improve performance
    );

    $posts = get_posts($args);

    // Format response to include only ID and Title
    $response = array();
    foreach ($posts as $post_id) {
        $response[] = array(
            'id' => $post_id,
            'title' => html_entity_decode(get_the_title($post_id)),
            'updatedAt' => get_the_modified_date('c', $post_id),
            'postStatus' => get_post_status($post_id),
            'customAuthor' => get_field('custom_author', $post_id),
			'featuredImage' => get_the_post_thumbnail_url($post_id, 'thumbnail'),
        );
    }

    return rest_ensure_response($response);
}


add_action('rest_api_init', 'custom_content_wp_api');

// Register the custom endpoint
add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/posts', array(
        'methods' => 'GET',
        'callback' => 'custom_api_get_all_posts',
    ));
});

function wp_custom_content($object, $field_name, $request)
{
	
    if ($object['content']) {
        // Disabled till Adbridg Live ads start displaying on production
        return $object['content']['rendered'];

        $html = $object['content']['rendered'];
        
        $dom = new DOMDocument();
        libxml_use_internal_errors(true); // Suppress HTML warnings
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        $images = $dom->getElementsByTagName('img');
        
        // Define the HTML content to inject
        $adHtml = <<<ADHTML
            <div class="adbridg">
            <div class="medium-rectangle">
            <div class="ad-container ad-container-lg">
            <span class="ad-label">Advertisement</span>
            <div data-adbridg-ad-class="medium_rectangle_article"><div></div></div>
            </div>
            </div>
            <div class="leaderboard">
            <div class="ad-container ad-container-lg">
            <span class="ad-label">Advertisement</span>
            <div data-adbridg-ad-class="leaderboard_article"><div></div></div>
            </div>
            </div>
            </div>
        ADHTML;


        foreach ($images as $img) {
            // Create a new DOMDocument to parse the ad HTML
            $adFragment = new DOMDocument();
            $adFragment->loadHTML($adHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
            // Import the ad HTML into the main DOMDocument
            $adNode = $dom->importNode($adFragment->documentElement, true);
        
            // Insert the ad HTML before the img element
            $img->parentNode->insertBefore($adNode, $img);
        }        
        
        return $dom->saveHTML();
    }
    return false;
}

function custom_content_wp_api()
{
    register_rest_field(array('post'), //name of post type 'post', 'page'
        'custom_content', //name of field to display
        array(
            'get_callback' => 'wp_custom_content',
            'update_callback' => null,
            'schema' => null,
        )
    );
}


add_filter( 'acf/rest/get_fields', function ( $fields, $resource, $http_method ) {
    if ( ! is_array( $fields ) ) {
        return $fields;
    }

    // Get our field type by the field name.
    $field_type = acf_get_field_type( 'custom_author' );

    // Get the field array (by the field name) and add it to the array of fields supported by REST.
    $fields[] = acf_get_field( 'custom_author');

    return $fields;
}, 10, 3 );