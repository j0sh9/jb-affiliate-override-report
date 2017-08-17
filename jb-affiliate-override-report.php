<?php
/*
Plugin Name: Affiliate Override Report
Description: Admin Page for affilaite reports and overrides
Version: 1.0
*/

function jb_affiliate_override_report() {
	$jb_page_title = 'Affiliate Reports with Overrides';
	$jb_menu_title = 'Affiliate Reports';
	$jb_capability = 'manage_affiliates';
	$jb_menu_slug = 'jb-affiliate-reports';
	$jb_callback = 'jb_affilaite_report_html';
	$jb_icon_url = 'dashicons-chart-pie';
	$jb_menu_position = 120;
	add_menu_page(  $jb_page_title,  $jb_menu_title,  $jb_capability,  $jb_menu_slug,  $jb_callback,  $jb_icon_url,  $jb_menu_position );
}

function jb_affilaite_report_html() {
?>
	<style>
		.jb-affiliate-report, .jb-affiliate-report th, .jb-affiliate-report td {
			border: 1px solid #cdcdcd;
			border-collapse: collapse;
		}
		.jb-affiliate-report {
			width: 97%;
			margin: 3vw 1vw;
		}
		.jb-affiliate-report td {
			padding: 4px 8px;
		}
	</style>
	<h2>Custom Affiliate Reports</h2>
	<div>
	<?php
/** Date Range **/
	if ( isset( $_POST['start_date'] ) && isset( $_POST['end_date'] ) ) {
		$start_date = $_POST['start_date'].' 00:00:00';
		$end_date = $_POST['end_date'].' 23:59:59';
		$referral_status = $_POST['referral_status'];
	} else {
		$start_date = '';
		$end_date = '';
		$referral_status = 'paid';
	}
?>

		<form action="" method="post">
			<label>Start Date: <input type="date" name="start_date" id="start_date" value="<?=$_POST['start_date'];?>"   /></label>
			<br>
			<label>End Date: <input type="date" name="end_date" id="end_date" value="<?=$_POST['end_date'];?>"   /></label>
			<br>
			<label>Referral Status: <input type="text" name="referral_status" id="referral_status" value="<?=$referral_status;?>"   /></label> pending | unpaid | paid | rejected
			<p><input type="submit"/></p>
		</form>

<?php

	
/** Get Affiliates **/
	$affiliates_db = new Affiliate_WP_DB_Affiliates();
	$aff_args = array(
		'number'       => -1,
		'offset'       => 0,
		'exclude'      => array(),
		'user_id'      => 0,
		'affiliate_id' => 0,
		'status'       => '',
		'order'        => 'ASC',
		'orderby'      => 'affiliate_id',
		'fields'       => '',
	);
	$affiliates = $affiliates_db->get_affiliates( $aff_args );
	foreach ( $affiliates as $affiliate ) {
		$affiliate_id = $affiliate->affiliate_id;
		
		/** Get Referrals **/

		$ref_args = array(
			'number'       => -1,
			//'offset'       => 0,
			//'referral_id'  => 0,
			//'payout_id'    => 0,
			'affiliate_id' => $affiliate_id,
			//'amount'       => 0,
			//'amount_compare' => '=',
			'date'         => array('start'=>$start_date,'end'=>$end_date),
			//'reference'    => '',
			//'context'      => '',
			//'campaign'     => '',
			'status'       => $referral_status,
			//'orderby'      => 'referral_id',
			//'order'        => 'DESC',
			//'search'       => false,
			//'fields'       => '',
		);
		$referral_db = new Affiliate_WP_Referrals_DB();
		$referrals = $referral_db->get_referrals( $ref_args );
		$ref_count = $referral_db->count($ref_args);
		
		?>
		<table class="jb-affiliate-report">
		<tr><th colspan="7"><?php echo $affiliates_db->get_affiliate_name($affiliate)." - ID: ".$affiliate_id." - Referral Count: ".$ref_count; ?></th></tr>
		<tr><th>#</th><th>Order#</th><th>Date</th><th>Order Amount</th><th>Referral Amount</th><th>Status</th><th>Description</th></tr>
		<?php
		$ref_number = 0;
		$ref_total = 0;
		$order_total = 0;
		foreach ( $referrals as $referral ) {
			$ref_number = $referral->referral_id;
			$order = wc_get_order($referral->reference);
			if ( $order ) {
			?>
			<tr>
				<td><a href="/wp-admin/admin.php?page=affiliate-wp-referrals&referral_id=<?php echo $ref_number; ?>&action=edit_referral" target="_blank"><?php echo $ref_number; ?></a></td>
				<td><a href="/wp-admin/post.php?post=<?php echo $referral->reference; ?>&action=edit" target="_blank"><?php echo $referral->reference; ?></a></td>
				<td><?php echo $referral->date; ?></td>
				<td><?php echo $order->get_total(); ?></td>
				<td><?php echo $referral->amount; ?></td>
				<td><?php echo $referral->status; ?></td>
				<td><?php echo $referral->description; ?></td>
			</tr>
			<?php
				if ( $referral->status != 'rejected' ) {
					$ref_total = $ref_total + $referral->amount;
					$order_total = $order_total + $order->get_total();
				}
			} else {
			?>
			<tr>
				<td><a href="/wp-admin/admin.php?page=affiliate-wp-referrals&referral_id=<?php echo $ref_number; ?>&action=edit_referral" target="_blank"><?php echo $ref_number; ?></a></td>
				<td><?php echo $referral->reference; ?> not found</td>
				<td><?php echo $referral->date; ?></td>
				<td>N/A</td>
				<td><?php echo $referral->amount; ?></td>
				<td><?php echo $referral->status; ?></td>
				<td><?php echo $referral->description; ?></td>
			</tr>
			<?php
				if ( $referral->status != 'rejected' ) {
					$ref_total = $ref_total + $referral->amount;
				}
			}
		}
		?>
		<tr><td></td><th colspan="2">Totals:</th><th><?php echo $order_total; ?></th><th><?php echo $ref_total; ?></th><td></td><td></td></tr>
		<tr><td></td><th colspan="2">1% Override:</th><th><?php echo number_format(($order_total*0.01),2); ?></th><th></th><td></td><td></td></tr>
		</table>
		<?php
	}
echo "</div>";
}

add_action( 'admin_menu', 'jb_affiliate_override_report' );
