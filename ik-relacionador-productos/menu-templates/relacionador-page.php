<?php
/*

Template: Relacionador de Productos - Panel

*/

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['catOrigen']) && isset($_POST['catRelacionada'])){

    $catOrigen = $_POST['catOrigen'];
    $catRelacionada = $_POST['catRelacionada'];

    // I create a counter for foreach
    $countRelSub = 0;
    
    // I check if $catOrigen is array or not. If not I don't need repetitions and I do an insert once
    if (is_array($catOrigen)){
        foreach( $catOrigen as $catOrigeni ) {
            if (isset($catOrigeni) && isset($catRelacionada[$countRelSub])) {
                if (intval($catOrigeni) !== 0 && intval($catRelacionada[$countRelSub]) !== 0 && intval($catRelacionada[$countRelSub]) != intval($catOrigeni)){
                	global $wpdb;
                	$queryRelacionRep = "SELECT * FROM ".$wpdb->prefix."ik_relacionador_meta WHERE category_id = ".intval($catOrigeni)." AND category_relacionada = ".intval($catRelacionada[$countRelSub]);
                    $relRepetida = $wpdb->get_results($queryRelacionRep);
                
                    // I check if relationship is not already exisiting. If not I insert the relationship to DB
                    if (!isset($relRepetida[0]->id)){
            
                    	global $wpdb;
                        $data_relacion  = array (
                        			'id' => NULL,
                        			'category_id' => intval($catOrigeni),
                        			'category_relacionada' => intval($catRelacionada[$countRelSub]),
                        	);
                    
                    		
                    		$tableInsert = $wpdb->prefix.'ik_relacionador_meta';
                    		$rowResult = $wpdb->insert($tableInsert,  $data_relacion , $format = NULL);
                    
                        }
            
                    $countRelSub = $countRelSub + 1;
            
                }
            }
        }
    } else {
        if (isset($catOrigen) && isset($catRelacionada)) {
            if (intval($catOrigen) !== 0 && intval($catRelacionada) !== 0 && intval($catRelacionada) !== intval($catOrigen)){
            	global $wpdb;
            	$queryRelacionRep = "SELECT * FROM ".$wpdb->prefix."ik_relacionador_meta WHERE category_id = ".intval($catOrigen)." AND category_relacionada = ".intval($catRelacionada);
                $relRepetida = $wpdb->get_results($queryRelacionRep);
            
                // I check if relationship is not already exisiting. If not I insert the relationship to DB
                if (!isset($relRepetida[0]->id)){
        
                	global $wpdb;
                    $data_relacion  = array (
                    			'id' => NULL,
                    			'category_id' => intval($catOrigen),
                    			'category_relacionada' => intval($catRelacionada),
                    	);
                
                		
                		$tableInsert = $wpdb->prefix.'ik_relacionador_meta';
                		$rowResult = $wpdb->insert($tableInsert,  $data_relacion , $format = NULL);
                
                    }
            }
        }
    }
}
?>
<style>
.error{display: none;}
.ik_relacion_categorias input[type=number]{
    position: relative;
    top: 3px;
    width: 80px;
    text-align: center;
}
.ik_relacion_categorias select{
    width: 126px;
    margin: 2px 0;
}
.ik-box-relacion {
    background: #fff;
    padding: 5px 9px;
    width: 106px! important;
    border: 1px solid #999;
    border-radius: 4px;
    font-size: 14px;
    display: inline-block;
    margin: 2px 0;
}
.ik-orden-importancia{
    background: #fff;
    padding: 5px;
    border: 1px solid #999;
    border-radius: 4px;
    margin-left: 1px;
    width: 68px;
    display: inline-block;
    text-align: center;
}
.relacion-cargada {
    margin: 2.58px 0;
}
.relacion-cargada:last-child {
    margin: 2.58px 0;
}
#ik-relacionador {
    margin: -3px 0 0! important;
}
.borrar-relacion{
    color: red;
    background: #fff;
    border: 1px solid #999;
    border-radius: 62px;
    padding: 1px 2px 4px;
    height: 16px;
    width: 16px;
    display: inline-block;
    text-align: center;
    cursor: pointer;
}

/* Chrome, Safari, Edge, Opera */
.ik_relacion_categorias input::-webkit-outer-spin-button,
.ik_relacion_categorias input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Firefox */
.ik_relacion_categorias input[type=number] {
  -moz-appearance: textfield;
}
#agregarquitar-relacionador{ margin: 20px 0;}
#agregarquitar-relacionador a {
    float: left;
    color: #fff;
    background: #000;
    padding: 5px 12px;
    text-align: center;
    display: block;
    max-width: 200px;
    margin-right: 5px;
}
input[type=submit] {
    margin: 10px 1px;
    cursor: pointer;
    background: #333;
    border: 0;
    color: #fff;
    padding: 7px 20px;
}
</style>
<script>
    // This function adds a new relacionador field with their checkboxes
    jQuery(document).on('click', '#agregarOptionRelacionar', function(){
        var LastrelacionadorDiv = parseInt(jQuery("#ik-relacionador").attr("class"));
        var relacionadorDiv = LastrelacionadorDiv + 1;
        var relacionadorDivElement = '<div class="relacionador-' + relacionadorDiv + '  relacionador-field-id"><span class="optionrelacionar"><?php ik_relcategorias_options('edit'); ?></span> > <span class="optionrelacionada"><?php ik_relcategorias_options('edit'); ?></span></div>';
    	jQuery(relacionadorDivElement).appendTo('#ik-relacionador');
    	jQuery("#ik-relacionador").attr("class", relacionadorDiv);
    	jQuery("#eliminarOptionRelacionar").attr("style", "display: block; cursor: pointer;");
    	jQuery('.relacionador-'+relacionadorDiv+' .optionrelacionar select').attr('name', 'catOrigen['+relacionadorDiv+']');
    	jQuery('.relacionador-'+relacionadorDiv+' .optionrelacionada select').attr('name', 'catRelacionada['+relacionadorDiv+']');
    });
    
    // This function deletes a new relacionador field with their checkboxes
    jQuery(document).on('click', '#eliminarOptionRelacionar', function(){
        var relacionadorToDelete = parseInt(jQuery("#ik-relacionador").attr("class"));
        if (relacionadorToDelete !== 0){
            jQuery( ".relacionador-"+relacionadorToDelete).remove();
            var changeTranslatorDiv = relacionadorToDelete - 1;
            jQuery("#ik-relacionador").attr("class", changeTranslatorDiv);
            if (relacionadorToDelete == 1) {
                jQuery("#eliminarOptionRelacionar").attr("style", "display: none");
            }
        }
    });
    
    // function to delete category relationship
    function ik_borrar_relacion(idRelationship){
    if (confirm("¿Estás seguro de eliminar esta relación de categorías?")) {
        jQuery.post('<?php echo plugin_dir_url( __DIR__)."functions/borrar-relacion.php"; ?>',
        {idRelationship:idRelationship},
        function(response){
        console.log(response);
        })
        jQuery("#relacion-cargada-"+idRelationship).remove();
    }
}
</script>
<div class="ik_relacion_categorias">
<h2>Relacionador de Categorías Productos al agregar al carrito</h2>
<form action="" method="post" enctype="multipart/form-data" autocomplete="no">
            <?php ik_carga_viejasRelaciones(); ?>
            <fieldset id="ik-relacionador" class="0">
                <div class='relacionador-0 relacionador-field-id'>
					<span class="optionrelacionar"><?php ik_relcategorias_options('catOrigen[0]'); ?></span> > </span class="optionrelacionada"><?php ik_relcategorias_options('catRelacionada[0]'); ?></span> 
				</div>
            </fieldset>
            <fieldset id="agregarquitar-relacionador">
                <a id="agregarOptionRelacionar" style="cursor: pointer;">Agregar Campo</a>
                <a id="eliminarOptionRelacionar" style="display: none;">Eliminar último campo</a>
            </fieldset>
        </div>



	<input type="submit" value="Guardar">
	

</form>