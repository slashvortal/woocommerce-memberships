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
 * @package   WC-Memberships/Admin/Meta-Boxes
 * @author    SkyVerge
 * @category  Admin
 * @copyright Copyright (c) 2014-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * User Membership Notes Meta Box
 *
 * @since 1.0.0
 */
class WC_Memberships_Meta_Box_User_Membership_Notes extends WC_Memberships_Meta_Box {


	/**
	 * Constructor
	 *
	 * @since 1.7.0
	 */
	public function __construct() {

		$this->id      = 'wc-memberships-user-membership-notes';
		$this->screens = array( 'wc_user_membership' );

		parent::__construct();
	}


	/**
	 * Get the meta box title
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_title() {
		return __( 'Membership Notes', 'woocommerce-memberships' );
	}


	/**
	 * Add meta box to the supported screen(s)
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function add_meta_box() {
		global $pagenow;

		// do not display on new membership screen
		if ( 'post-new.php' === $pagenow ) {
			return;
		}

		parent::add_meta_box();
	}


	/**
	 * Display the membership notes meta box
	 *
	 * @param \WP_Post $post
	 * @since 1.0.0
	 */
	public function output( WP_Post $post ) {

		// prepare variables
		$this->post            = $post;
		$this->user_membership = $user_membership = wc_memberships_get_user_membership( $post->ID );
		$this->order           = $order           = $user_membership->get_order();
		$this->product         = $product         = $user_membership->get_product();
		$this->user            = $user            = $this->get_membership_user( $user_membership );

		// bail out if no user id
		if ( ! $user ) {
			return;
		}

		$notes = $user_membership->get_notes();

		/**
		 * Fires at the beginning of the user membership notes meta box
		 *
		 * @since 1.0.0
		 * @param \WC_Memberships_User_Membership $user_membership The user membership
		 */
		do_action( 'wc_memberships_before_user_membership_notes', $user_membership );

		?>
		<div class="wc-user-membership-add-note">

			<h4><?php esc_html_e( 'Add note', 'woocommerce' ); ?>
				<?php echo SV_WC_Plugin_Compatibility::wc_help_tip( __( 'Add a note for your reference, or add a customer note (the user will be notified).', 'woocommerce-memberships' ) ); ?></h4>

			<p>
				<textarea
					name="user_membership_note"
					id="user-membership-note"
					class="input-text"
					cols="100" rows="5"></textarea>
			</p>

			<p class="note-controls">
				<label>
					<input
						type="checkbox"
						name="notify_member"
						id="note-notify"
						class="notify-member"
						value="1"
					/><?php esc_html_e( 'Notify Member', 'woocommerce-memberships' ); ?>
				</label>
				<a href="#" class="add-note js-add-note button"><?php esc_html_e( 'Add Note', 'woocommerce-memberships' ); ?></a>
			</p>

		</div>

		<ul class="wc-user-membership-notes">
			<?php

			if ( $notes ) :

				// load membership note view
				require( wc_memberships()->get_plugin_path() . '/includes/admin/meta-boxes/views/class-wc-memberships-meta-box-view-membership-note.php' );

				foreach ( $notes as $note ) :

					$note_classes = get_comment_meta( $note->comment_ID, 'notified', true ) ? array( 'notified', 'note' ) : array( 'note' );

					// output membership notes views
					$view = new WC_Memberships_Meta_Box_View_Membership_Note( $this );
					$view->output( array(
						'note'         => $note,
						'note_classes' => $note_classes,
					) );

				endforeach;

			else :

				?><li><?php esc_html_e( 'There are no notes yet.', 'woocommerce-memberships' ); ?></li><?php

			endif;

			?>
		</ul>

		<?php

		/**
		 * Fires at the end of the user membership notes meta box
		 *
		 * @since 1.0.0
		 * @param \WC_Memberships_User_Membership $user_membership The user membership
		 */
		do_action( 'wc_memberships_after_user_membership_notes', $user_membership );
	}


}
