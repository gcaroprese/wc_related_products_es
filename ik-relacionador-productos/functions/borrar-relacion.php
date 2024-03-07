<?php

//Function to delete relationships created between categories
require('../../../../wp-load.php');

$idRelationship = strip_tags($_POST['idRelationship']);

if(isset($idRelationship))
{
    
    // Delete relationship from DB
        global $wpdb;
        $tableDelete = $wpdb->prefix.'ik_relacionador_meta';
        $rowResult = $wpdb->delete( $tableDelete , array( 'id' => $idRelationship ) );
}
?>