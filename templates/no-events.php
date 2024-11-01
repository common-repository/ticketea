<?php
/**
 * The no-events template
 *
 * @author     Ticketea
 * @package    Ticketea\Templates
 * @since      1.0.0
 */

?>
<div class="ticketea-no-events">

	<?php if ( current_user_can( 'edit_ticketea_event_lists'  ) ) : ?>

		<?php if ( ticketea_event_list_is_synchronizing( $event_list_id ) ) : ?>

			<p><?php _ex( 'The list is being synchronized at this moment. Please wait.', 'Template: no-events', 'ticketea' ); ?></p>

		<?php else: ?>

			<p><?php _ex( 'The list is empty. Try to force a sync or update the list filters.', 'Template: no-events', 'ticketea' ); ?></p>

		<?php endif; ?>

	<?php else: ?>

		<p><?php _ex( 'There is no events at this moment.', 'Template: no-events', 'ticketea' ); ?></p>

	<?php endif; ?>

</div>
