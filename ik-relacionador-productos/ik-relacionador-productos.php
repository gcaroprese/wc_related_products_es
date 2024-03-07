<?php
/*
Plugin Name: Relacionador de Productos
Description: Relaciona productos agregados al carrito
Version: 1.1.1
Author: Gabriel Caroprese / Inforket
Author URI: https://inforket.com/
*/ 

// if plugin WooCommerce is not installed a message will show up
add_action( 'admin_notices', 'ik_relacionadorprod_dependencies' );

function ik_relacionadorprod_dependencies() {
    if (!class_exists('woocommerce')) {
    echo '<div class="error"><p>' . __( 'Atención: El plugin Relacionador de Productos depende directamente de tener instalado Woocommerce para funcionar.' ) . '</p></div>';
    }
}

// I add menus on WP-admin
add_action('admin_menu', 'ik_relacionador_productos_menu');
function ik_relacionador_productos_menu(){
    add_menu_page('Relacionador de Productos - Panel', 'Relacionador de Productos', 'manage_options', 'ik_relacionador_productos', 'ik_relacionador_productos', plugin_dir_url( __FILE__ ) . 'imagenes/relacionador-icon.png' );
}

// I create the page for the menu item
function ik_relacionador_productos(){
   echo '
   <style>
   h1 { text-align: center; }
   </style>';
   include('menu-templates/relacionador-page.php');
}

// I create a DB table to manage relationships between product categories
register_activation_hook( __FILE__, 'ik_relacionador_dbcrear' );
function ik_relacionador_dbcrear() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . 'ik_relacionador_meta';


	$sql = "CREATE TABLE $table_name (
		id bigint(10) NOT NULL AUTO_INCREMENT,
		category_id bigint(20) NOT NULL,
		category_relacionada bigint(20) NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}


// Function to show select option with different product categories
function ik_relcategorias_options($typeRel){
    
    global $post;
    $taxonomies = get_terms( array(
        'hide_empty' => false, 
        'taxonomy' => 'product_cat', 
		'orderby' => 'name',
    ) );
     
    if ( !empty($taxonomies) ){
        $relacionadorOptions = '<select name="'.$typeRel.'" required><option disabled selected>Categoría</option>';
        foreach( $taxonomies as $category ) {
            $parentcat = $category->category_parent;
            if( $categoryParent = get_term_by( 'id', $parentcat, 'product_cat' ) ){
                $categoryParent->name;;
             } else {
                 $categoryParent = '';
             }
            $relacionadorOptions .= '<option value="'. esc_attr( $category->term_id ) .'">'.$categoryParent. esc_html( $category->name ) .'</option>';
        }
        $relacionadorOptions .= '</select>';
    } else {
        $relacionadorOptions = "No hay categorías creadas para relacionar";
    }
    
    echo $relacionadorOptions;
}

// Function to show relationships already added
function ik_carga_viejasRelaciones(){
    global $wpdb;
    global $post;
    $getRelQuery = "SELECT * FROM ".$wpdb->prefix."ik_relacionador_meta";
    $relaciones = $wpdb->get_results($getRelQuery);
    
    // I check if value is not null 
    if (isset($relaciones[0]->id)){
        $relacionCounter = 1;
        foreach( $relaciones as $relacion ) {
            echo '<div class="relacion-cargada" id="relacion-cargada-'.$relacion->id.'"><span class="ik-box-relacion">'.get_the_category_by_ID($relacion->category_id). '</span> > <span class="ik-box-relacion">'.get_the_category_by_ID($relacion->category_relacionada).'</span> <span class="borrar-relacion" onclick="ik_borrar_relacion('.$relacion->id.')">X</span></div>';
            $relacionCounter = $relacionCounter +1;
        }
    } else{
        echo '';
    }
}

// Function to check if category is parent or not
function ik_cat_parent_true( $term_id = '', $taxonomy = 'product_cat' ){
    // Check if a term or category and if not it returns false
    if ( !$term_id ){
        return false;
    }
    
    // Return subcategories
    $cat_children = get_term_children( filter_var( $term_id, FILTER_VALIDATE_INT ), filter_var( $taxonomy, FILTER_SANITIZE_STRING ) );

    // Confirms if category is parent
    if ( empty( $cat_children ) || is_wp_error( $cat_children ) ){
        return false;
    } else {
        return true;
    }
}


// This function creates an arg to retrieve suggestions on cart after getting a random category slug from a category related to a product ID
function ik_relationship_catslug($prodIDrelated, $cantProductosRel){
    // I get category ID of product ID
    global $post;
    global $woocommerce;
    $relation_catID = wc_get_product_term_ids( $prodIDrelated, 'product_cat');

    if (is_array($relation_catID)){
		// Hago un foreach para verificar y guardar la relación que no sea parent de ninguna subcategoría
		foreach ($relation_catID as $subcatExisting){
		    if (ik_cat_parent_true($subcatExisting) == false){
		        $subcat_noParent = $subcatExisting;
		    }
		}
    } else {
        $subcat_noParent = $relation_catID;
    }
    
    global $wpdb;
    $relCatQuery = "SELECT * FROM ".$wpdb->prefix."ik_relacionador_meta WHERE category_id = ".$subcat_noParent." ORDER BY RAND()";
    $catRelacionada = $wpdb->get_results($relCatQuery);
    
    // I check if value is not null 
    if (isset($catRelacionada[0]->id)){
        
    // I get category slug of category related
    $relcat_info = get_term( $catRelacionada[0]->category_relacionada );
    $relCatSlug = $relcat_info->slug;
     
	$relationshipArgs = array(
        	'post_type'            	=> 'product',
        	'post_status'    		=> 'publish',
        	'posts_per_page'        => $cantProductosRel,
        	'ignore_sticky_posts'  	=> 1,
        	'no_found_rows'       	=> 1,
        	'post__not_in'        	=> array( $prodIDrelated ),
        	'product_cat' => $relCatSlug,
        	);
    } else {
        $relationshipArgs = array(
        	'post__not_in'        	=> array( $prodIDrelated ),
        	'meta_query' => array(
	        array(
	            'key' => '_stock_status',
	            'value' => 'instock'
	        ))
        );
    }
    
    return $relationshipArgs;

}


// Elimino los productos relacionados nativos de Woocommerce
function ik_eliminar_prod_relacionados() {
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
}
add_action( 'init', 'ik_eliminar_prod_relacionados' );


//De esta manera hago que se listen los productos relacionados
function ik_productos_relacionados_listado($idProductoRel, $columnasProd){
    global $post, $product, $woocommerce;
    $args = ik_relationship_catslug($idProductoRel, $columnasProd );
    $productos = get_posts( $args );
    $output = '<ul data-view="grid" data-toggle="regular-products" class="products columns-5 columns__wide--5">';
    foreach ( $productos as $producto ){
        $output .= '
        <li class="product type-product post-'.$producto->get_id().' status-publish first instock sale taxable shipping-taxable purchasable product-type-simple">
            <div class="product-outer product-item__outer">
                <div class="product-inner product-item__inner">
                    <div class="product-loop-header product-item__header">
                        <a href="get_permalink($producto->get_id())" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">
                            <h2 class="woocommerce-loop-product__title">'.$producto->get_name().'</h2>
                            <div class="product-thumbnail product-item__thumbnail">
                                <img width="300" height="225" src="$product->'.$producto->get_featured().'" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail lazyloaded" alt="'.$producto->get_name().'" data-was-processed="true">
                                <noscript>
                                    <img width="300" height="225" src="'.$producto->get_featured().'" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="'.$producto->get_name().'" />
                                </noscript>
                            </div>
                        </a>
                    </div>
                    <div class="product-loop-body product-item__body">
                        <span class="loop-product-categories">
                            <a href="'.get_permalink($producto->get_id()).'" rel="tag">'.$product->get_name().'</a>
                        </span>
                        <a href="'.get_permalink($producto->get_id()).'" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">
                            <h2 class="woocommerce-loop-product__title">'.$producto->get_name().'</h2>
                            <div class="product-sku">SKU: '.$producto->get_sku().'</div>
                        </a>
                    </div>
                    <div class="product-loop-footer product-item__footer">
                        <div class="price-add-to-cart"> 
                            <span class="price">
                                <span class="electro-price">
                                    '.$producto->get_price().';
                                </span>
                            </span>
                            <div class="add-to-cart-wrap" data-toggle="tooltip" data-title="Añadir al carrito" data-original-title="" title="">
                                <a href="?add-to-cart='.$producto->get_id().'" data-quantity="1" class="button product_type_simple add_to_cart_button ajax_add_to_cart" data-product_id="'.$producto->get_id().'" rel="nofollow">Añadir al carrito</a>
                            </div>
                        </div>
                        <div class="hover-area">
                            <div class="action-buttons"></div>
                        </div>
                    </div>
                </div>
            </div>
        </li>';
    } 
    $output .= '</ul>';
	return $output;
}


// Agrego el listado de productos relacionados
add_action( 'woocommerce_after_single_product_summary', 'ik_productos_relacionados_listar', 20 );
function ik_productos_relacionados_listar(){
    global $product, $woocommerce;
    $IDproducto = $product->get_id();
    
    // Llamo al listado poniendo 5 por el número de productos a mostrar
    ik_productos_relacionados_listado($IDproducto, 5);
}

?>