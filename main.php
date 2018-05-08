<?php

// Add the action setting
add_filter( 'wc_points_rewards_action_settings', 'points_rewards_lov_action_settings' );

/**
 * [points_rewards_lov_action_settings description]
 *
 * @param  [type] $settings [description]
 * @return [type]           [description]
 */
function points_rewards_lov_action_settings( $settings ) {

	$settings[] = array(
		'title'    => __( 'Points earned for lifetime order value' ),
		'desc_tip' => __( 'Enter the amount of points earned for lifetime order value' ),
		'id'       => 'wc_points_rewards_lov_1000',
	);

	return $settings;
}



// add the event descriptions
add_filter( 'wc_points_rewards_event_description', 'add_points_rewards_lov_action_event_description', 10, 3 );

/**
 * [add_points_rewards_lov_action_event_description description]
 *
 * @param [type] $event_description [description]
 * @param [type] $event_type        [description]
 * @param [type] $event             [description]
 */
function add_points_rewards_lov_action_event_description( $event_description, $event_type, $event ) {

	$points_label = get_option( 'wc_points_rewards_points_label' );

	// set the description if we know the type
	switch ( $event_type ) {
		case 'lov':
			$event_description = sprintf( __( '%s earned for lifetime order value' ), $points_label );
			break;
	}

	return $event_description;
}

// perform the event (of course this depends on your particular plugin/action)
add_action( 'woocommerce_order_status_processing', 'points_rewards_lov_action' );

/**
 * [points_rewards_lov_action description]
 *
 * @param  integar $user_id   The user ID.
 * @param  float   $threshold The threshold that has been met.
 * @return [type]            [description]
 */
function points_rewards_lov_action( $order_id ) {

	// Initiate the instance of the order.
	$order = new WC_Order( $order_id );
	// Now let's get our user ID so we can do something with the account.
	$user_id = $order->user_id;
	// We need to find out how much the customer has spent Mr Money Bags!
	$total_spent = wc_get_customer_total_spent( $user_id );

	// No let's get the figure we are checking against.
	$lifetime_order_value = 1000;

	// Checking a bunch of vars here to make sure we don't assign points if they have not been earnt, or most importantly, earnt already. The eye is in 'lifetime_order_value', this gets set after we increase the points via this function.
	if ( $total_spent >= $lifetime_order_value && $user_meta['lifetime_order_value_received'][0] !== ( null || 'received' ) ) {

		// And now how many points should be actually apply (Keep this outside of the next IF statement as it doesn't need to fire yet).
		$points = get_option( 'wc_points_rewards_lov_1000' );

		// Let's make sure we have some points set as adding "you gained 'null' for your lifetime purchase value" would be pretty shitty from a UI point of view.
		if ( ! empty( $points ) ) {
			// arbitrary data can be passed in with the points change, this will be persisted to the points event log. We don't need this but handy if you want to do something magical in the future!
			$data = array(
				'user_id' => $user_id,
				'lifetime_order_value' => $lifetime_order_value,
				'total_spent' => $total_spent,
			);

			// Woah, so you got this far, best give you some points right?!
			WC_Points_Rewards_Manager::increase_points( $user_id, $points, 'lov', $data );
			// Let's now mark the customer as received this benefit to prevent this action from firing again in the future.
			update_user_meta( $user_id, 'lifetime_order_value', 'received', '' );
		}
	}
}
