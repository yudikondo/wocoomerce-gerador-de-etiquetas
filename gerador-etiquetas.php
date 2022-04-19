<?php
/*
  Plugin Name: Agência K - Gerador de Etiquetas WooCommerce
  Plugin URI: http://www.agenciak.digital
  Description: Um plugin simples para impressão de etiquetas do WooCommerce para envio por Correios. 
  Inspirado no plugin elaborado por Fernando Acosta
  Version: 0.0.1
  Author: Agência K
  Author URI: http://www.agenciak.digital
  License: GPL v3
*/

function bulk_admin_etiqueta_footer() {
	global $post_type;

	if ( 'shop_order' == $post_type ) {
		?>
		<script type="text/javascript">
		jQuery(function() {
			jQuery('<option>').val('gerar_etiqueta').text('<?php _e( 'Gerar Etiquetas', 'woocommerce' )?>').appendTo("select[name='action']");
			jQuery('<option>').val('gerar_etiqueta').text('<?php _e( 'Gerar Etiquetas', 'woocommerce' )?>').appendTo("select[name='action2']");
		});
		</script>
		<?php
	}
}

/**
 * Process the new bulk actions for changing order status
 */
function bulk_action_etiqueta() {

	$wp_list_table 	= _get_list_table( 'WP_Posts_List_Table' );
	$action 		= $wp_list_table->current_action();

	// print_r($action);

	// Bail out if this is not a status-changing action
	if ( strpos( $action, 'gerar_' ) === false ) {
		return;
	}

	$new_status    	= substr( $action, 5 ); // get the status name from action
	$report_action 	= 'gerada' . $new_status;
	$changed 		= 0;
	$post_ids 		= array_map( 'absint', (array) $_REQUEST['post'] );
	$sendback 		= add_query_arg( array( 'post_type' => 'shop_order', $report_action => true, 'changed' => $changed, 'ids' => join( ',', $post_ids ) ), '' );
	wp_redirect( $sendback ); // esse é o padrão

	exit();
}

function bulk_action_etiqueta_notices() {
	global $post_type, $pagenow;

	// Bail out if not on shop order list page
	if ( 'edit.php' !== $pagenow || 'shop_order' !== $post_type ) {
		return;
	}

	if ( isset( $_REQUEST[ 'gerada_etiqueta' ] ) ) {
		$number 	= isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0;
		$message 	= 'Etiquetas geradas em uma nova aba';
		echo '<div class="updated"><p>' . $message . '</p></div>';
	}
}

/**
 * 
 */
function get_etiquetas_pdf(){

	if( is_admin() )
	{
		/**
		 * DomPDF
		 * Version: 1.2.1
		 */
		require_once 'dompdf/autoload.inc.php';

		/**
		 * Order(s) ID
		 */
		$orders_id = $_GET['ids'];
		
		if( isset( $orders_id ) && !empty( $orders_id ) )
		{
			
			$html .= '<!DOCTYPE html>';
			$html .= '<html>';
			$html .= '<head>';
			$html .= '<title>Agência K - Etiquetas Correios</title>';
			$html .= '<link rel="stylesheet" href=" '. plugins_url('assets/css/kreativos-commerce-etiquetas.css', __FILE__) .' " type="text/css" />';
			$html .= ' </head>';
			$html .= ' <body>';
			$html .= ' <page class="kreativos-commerce-etiquetas">';
		
			$i=0; $a=0;
			$orders = explode(",", $orders_id);
			foreach ($orders as $key => $value) {
		
				$pedido 		= $value;
				$order  		= wc_get_order( $value );
				$nome 			= get_post_meta($pedido, '_billing_first_name', TRUE);
				$sobrenome 		= get_post_meta($pedido, '_billing_last_name', TRUE);
				$endereco 		= get_post_meta($pedido, '_billing_address_1', TRUE);
				$endereco2 		= get_post_meta($pedido, '_billing_address_2', TRUE);
				$cidade 		= get_post_meta($pedido, '_billing_city', TRUE);
				$uf 			= get_post_meta($pedido, '_billing_state', TRUE);
				$cep 			= get_post_meta($pedido, '_billing_postcode', TRUE);

				$rates 			= $order->get_shipping_methods();
				foreach ( $rates as $key => $rate ) {
					$envioNome = $rate['method_title'];
					$envioID   = $rate['method_id'];

					/**
					 * correios-pac
					 * correios-sedex
					 */
					break;
				}
		
				$html .= '<div class="order">';
					
					// 
					$html .= '<div class="data">';
						$html .= '<h3 class="recipient">'.__( 'Destinatário:', 'kreativos-commerce-etiquetas' ).'</h3>';

						$html .= '<div class="customer">';
						$html .= '<div class="name">'.$nome ." ". $sobrenome.'</div>';
						$html .= '</div>';

						$html .= '<div class="address">';
							if ( is_plugin_active( 'woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php' ) ) {
								$numero = get_post_meta($pedido, '_billing_number', TRUE);
								$bairro = get_post_meta($pedido, '_billing_neighborhood', TRUE);
							}

							$html .= '
							<div class="street">
								'.$endereco.' '.(isset($numero) ? ' - '.$numero : null).'
								'.(isset($endereco2) && !empty($endereco2) ? ' <br> '.$endereco2 : null).'
								'.(isset($bairro) ? ' <br> '.$bairro : null).'
								<div class="city">'.$cidade.'/'.$uf.'</div>
								<div class="cep">'.$cep.'</div>
							</div>';
						$html .= '</div>';

						//
						$html .= '<div class="sender">';
							$html .= '<h3 class="sender">'.__( 'Remetente:', 'kreativos-commerce-etiquetas' ).'</h3>';
							$html .= '<div class="name">'.get_bloginfo('name').'</div>';

							$html .= '<div class="address">';
								$store_address     = get_option( 'woocommerce_store_address' );
								$store_address_2   = get_option( 'woocommerce_store_address_2' );
								$store_city        = get_option( 'woocommerce_store_city' );
								$store_postcode    = get_option( 'woocommerce_store_postcode' );
								$store_raw_country = get_option( 'woocommerce_default_country' );
								$store_postcode    = get_option( 'woocommerce_store_postcode' );
								// Split the country/state
								$split_country = explode( ":", $store_raw_country );

								// Country and state separated:
								$store_country = $split_country[0];
								$store_state   = $split_country[1];

								$html .= '
								<div class="street">
									'.$store_address.'
									'.(isset($store_address_2) && !empty($store_address_2) ? ' <br> '.$store_address_2 : null).'
									'.(isset($bairro) ? ' <br> '.$bairro : null).'
									<div class="city">'.$store_country.' - '.$store_state.'</div>
									<div class="cep">'.$store_postcode.'</div>
								</div>';
							$html .= '</div>';
						$html .= '</div>';

					$html .= '</div>';

					// 
					$html .= '<div class="shipping">';
						if ( $tipoEnvio == 'free_shipping' ) {
							$html .= __( 'Carta Registrada', 'kreativos-commerce-etiquetas' );
						} 
						else {
							$selo = __DIR__ . '/assets/img/' . $envioID . '.jpg';
							if( file_exists( $selo ) ){
								$html .= '<img src="'. plugins_url('assets/img/'.$envioID.'.jpg', __FILE__) .'">';
							}
							else {
								$html .= $envioNome;
							}
						}
					$html .= '</div>';

				$html .= '</div>';
			}

			$html .= '</page>';
			$html .= '</body>';
			$html .= '</html>';
		
			echo $html;
		}
	}
	exit;
}


function custom_admin_etiqueta_js() {
	if ( isset($_GET['gerada_etiqueta']) && $_GET['gerada_etiqueta'] == "1" )
    	echo '<script type="text/javascript" language="Javascript">window.open("'. get_admin_url() .'admin-ajax.php/?action=get_etiquetas_pdf&ids='.$_GET['ids'].'")</script>';
}


add_action( 'wp_ajax_get_etiquetas_pdf', 'get_etiquetas_pdf');
add_action( 'wp_ajax_nopriv_get_etiquetas_pdf', 'get_etiquetas_pdf');
add_action( 'admin_footer', 'bulk_admin_etiqueta_footer', 1000 );
add_action( 'load-edit.php', 'bulk_action_etiqueta' );
add_action( 'admin_notices', 'bulk_action_etiqueta_notices' );
add_action( 'admin_head', 'custom_admin_etiqueta_js');
