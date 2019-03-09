<?php
/*
Plugin Name: Mon Super Plugin
Description: Un super plugin de caroussel
Version: 0.1
Author: Ndrantonavalona Eddy
*/

add_action('init', 'monsuperplugin_init');

add_action('add_meta_boxes', 'monsuperplugin_meta_boxes');
add_action('save_post', 'monsuperplugin_savepost', 10, 2);
//gestion des colonnes
add_action('manage_edit-slide_columns', 'monsuperplugin_column_filter');
add_action('manage_posts_custom_column', 'monsuperplugin_column');
/*
 * Permet d'initialiser les fonctionnalites liees au caroussel
 */
function monsuperplugin_init() {

    $labels = [
        'name' => 'Slide',
        'singular_name' => 'Slide',
        'add_new' => 'Ajouter un slide',
        'add_new_item' => 'Ajouter un nouveau slide',
        'edit_new' => 'Editer un slide',
        'new_item' => 'Nouveau slide',
        'view_item' => 'Voir le slide',
        'search_items' => 'Rechercher un slide',
        'not_found' => 'Aucun slide',
        'not_found_in_trash' => 'Aucun slide dans la corbeille',
        'parent_item_colon' => '',
        'menu_name' => 'Slides'

    ];
    register_post_type('slide', [
        'public' => true,
        //Ne pas mettre l'url en public
        'publicly_queryable' => false,
        'labels' => $labels,
        'menu_position' => 9,
        //Mettre le meme permission que pour les articles
        'capability_type' => 'post',
        //Mettre juste les elements a supporter
        'supports' => ['title', 'thumbnail']
    ]);

    //Rajouter un format d'image
    add_image_size('slider', 1000, 500, true);
}

function monsuperplugin_column_filter($columns) {
    $thumb = ['thumbnail' => 'Image'];
    $columns = array_slice($columns, 0, 1) + $thumb + array_slice($columns, 1, null);
    return $columns;
}

function monsuperplugin_column($column) {
    global $post;
    if ($column == 'thumbnail') {
        echo edit_post_link(get_the_post_thumbnail($post->ID), null, null, $post->ID);
    }
}

/*
 * Permet de gerer les metaboxes
 */
function monsuperplugin_meta_boxes() {
    add_meta_box('monsuperplugin', 'Lien', 'monsuperplugin_meta_box', 'slide', 'normal', 'high');
}

/*
 * Metabox pour gerer les liens
 */

function monsuperplugin_meta_box($object) {
    //Token
    wp_nonce_field('monsuperplugin', 'monsuperplugin_nonce');
    ?>
        <div class="meta-box-item-title">
            <h4>Lien de ce slide</h4>
        </div>
        <div class="meta-box-item-content">
            <input type="text" name="monsuperplugin_link" style="width: 100%;" value="<?= esc_attr(get_post_meta($object->ID, '_link', true)) ;?>">
        </div>
    <?php
}

/*
 * Permet de gerer la sauvegarde de la metabox
 */
function monsuperplugin_savepost($post_id, $post) {
    if (!isset($_POST['monsuperplugin_link']) && wp_verify_nonce($_POST['monsuperplugin_nonce'], 'monsuperplugin')) {
        return $post_id;
    }

    $type = get_post_type_object($post->post_type);

    if (!current_user_can($type->cap->edit_post)) {
        //Ne pas editer les posts metas
        return $post_id;
    }
    update_post_meta($post_id, '_link', $_POST['monsuperplugin_link']);
}

/*
 * Permet d'afficher le caroussel
 */
function monsuperplugin_show($limit = 10) {
    //Importation du javascript
    wp_enqueue_script(
        'caroufredsel',
        plugins_url().'/mon-slider-plugin/js/jquery.carouFredSel-6.2.1-packed.js',
        array('jquery'),
        '6.2.1',
        true
    );

    add_action('wp_footer', 'monsuperplugin_script', 30);

    //Code HTML
    $slides = new WP_Query("post_type=slide&posts_per_page=$limit");
    echo '<div id="monsuperplugin">';
    while ($slides->have_posts()) {
        $slides->the_post();
        global $post;
        //the_post_thumbnail('slider', ['style' => 'width: 1000px!important;']);
        echo '<a style="display: block; float: left; height: 300px;" href="'.esc_attr(get_post_meta($post->ID, '_link', true)).'">';
        the_post_thumbnail('slider');
        echo '</a>';
    }
    echo '</div>';
}

function monsuperplugin_script() {
    ?>
        <script type="text/javascript">
            (function ($) {
                $('#monsuperplugin').caroufredsel();
            })(jQuery);
        </script>
    <?php
}

