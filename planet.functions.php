<?php

function get_user_by_id( $ID ) {
	global $wpdb;
	if(is_numeric( $ID )) {
		return $wpdb->get_row("SELECT * FROM $wpdb->users where ID = $ID");
	} else {
		return false;
	}
}

?>
