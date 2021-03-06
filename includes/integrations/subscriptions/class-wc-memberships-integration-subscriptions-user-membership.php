<?php
/**
 * WooCommerce Memberships
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Memberships to newer
 * versions in the future. If you wish to customize WooCommerce Memberships for your
 * needs please refer to http://docs.woothemes.com/document/woocommerce-memberships/ for more information.
 *
 * @package   WC-Memberships/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2014-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Helper object to get subscription-specific properties of a user membership
 *
 * @since 1.7.0
 */
class WC_Memberships_Integration_Subscriptions_User_Membership extends WC_Memberships_User_Membership {


	/** @var string|int the subscription meta key name */
	private $subscription_meta = '';

	/** @var string Installment plan meta key */
	protected $installment_plan_meta = '';


	/**
	 * Subscription-tied User Membership constructor
	 *
	 * @since 1.7.0
	 *
	 * @param int|\WP_Post $user_membership id or post object
	 */
	public function __construct( $user_membership ) {

		$integration                 = wc_memberships()->get_integrations_instance()->get_subscriptions_instance();
		$this->subscription_meta     = $integration->get_subscription_meta_key_name();
		$this->installment_plan_meta = '_has_installment_plan';

		parent::__construct( $user_membership );

		$this->type = 'subscription';
	}


	/**
	 * Get plan
	 *
	 * @since 1.7.0
	 * @return \WC_Memberships_Membership_Plan|\WC_Memberships_Integration_Subscriptions_Membership_Plan
	 */
	public function get_plan() {
		return parent::get_plan();
	}


	/**
	 * Check whether the order that granted access contains a subscription
	 *
	 * @since 1.7.0
	 * @return bool
	 */
	private function order_contains_subscription() {

		$integration = wc_memberships()->get_integrations_instance()->get_subscriptions_instance();

		if ( ! $this->get_order() ) {
			return false;
		} elseif ( $integration->is_subscriptions_gte_2_0() ) {
			return wcs_order_contains_subscription( $this->get_order_id() );
		} else {
			return WC_Subscriptions_Order::order_contains_subscription( $this->get_order_id() );
		}
	}


	/**
	 * Whether the subscription follows an installment plan option
	 *
	 * @since 1.7.0
	 * @return bool
	 */
	public function has_installment_plan() {

		$has_installment_plan = get_post_meta( $this->id, $this->installment_plan_meta, true );

		if ( ! is_numeric( $has_installment_plan ) && $this->has_subscription() ) {

			// maybe set this membership to have an installment plan
			// if no previous record found
			$has_installment_plan = $this->maybe_set_installment_plan();
		}

		return (bool) $has_installment_plan;
	}


	/**
	 * Flag subscription tied membership to have an installment plan
	 *
	 * @since 1.7.0
	 * @return bool
	 */
	private function maybe_set_installment_plan() {

		$plan = $this->get_plan();

		// sanity check
		if ( ! $plan ) {
			return false;
		}

		$plan = new WC_Memberships_Integration_Subscriptions_Membership_Plan( $plan->post );

		// if this plan has a subscription and the subscription is in installment mode
		// save this condition in a post meta which will persist also after unlinking
		if ( $this->has_subscription() && $plan->has_installment_plan() ) {

			return (bool) update_post_meta( $this->id, $this->installment_plan_meta, $this->get_subscription_id() );
		}

		return false;
	}


	/**
	 * Whether the membership is tied to a subscription
	 *
	 * @since 1.7.0
	 * @return bool
	 */
	public function has_subscription() {
		return (bool) $this->get_subscription_id();
	}


	/**
	 * Set the linked Subscription id
	 *
	 * @since 1.7.0
	 *
	 * @param string|int $subscription_id
	 */
	public function set_subscription_id( $subscription_id ) {

		if ( $subscription = wcs_get_subscription( $subscription_id ) ) {

			update_post_meta( $this->id, $this->subscription_meta, $subscription_id );

			$this->maybe_set_installment_plan();
		}
	}


	/**
	 * Get the linked Subscription id
	 *
	 * @since 1.7.0
	 * @return int|string|false
	 */
	public function get_subscription_id() {

		$integration       = wc_memberships()->get_integrations_instance()->get_subscriptions_instance();
		$subscription_meta = get_post_meta( $this->id, $this->subscription_meta, true );

		if ( ! empty( $subscription_meta ) && $integration->is_subscriptions_gte_2_0() ) {
			$subscription_meta = (int) $subscription_meta;
		}

		return $subscription_meta;
	}


	/**
	 * Get the linked Subscription
	 *
	 * @since 1.7.0
	 * @return null|false|\WC_Subscription
	 */
	public function get_subscription() {

		$subscription_id = $this->get_subscription_id();

		return ! empty( $subscription_id ) ? wcs_get_subscription( $subscription_id ) : null;
	}


	/**
	 * Remove the Subscription link
	 *
	 * @since 1.7.0
	 */
	public function delete_subscription_id() {

		delete_post_meta( $this->id, $this->subscription_meta );
	}


	/**
	 * Whether the user membership can be renewed by the user:
	 * subscription-tied memberships can be renewed if the subscription has expired
	 *
	 * Note: does not check whether the user has capability to renew
	 *
	 * @since 1.7.0
	 * @return bool
	 */
	public function can_be_renewed() {

		$can_be_renewed = parent::can_be_renewed();

		// make sure that besides the subscription the membership has an order linked
		if ( $this->has_subscription() && $this->order_contains_subscription() && $subscription = $this->get_subscription() ) {
			// check if the subscription has a valid status to be resubscribed
			$can_be_renewed = $subscription->has_status( array( 'expired', 'cancelled', 'pending-cancel', 'on-hold' ) );
		}

		return $can_be_renewed;
	}


}
