<?php
/*
  Plugin Name: Clash of Clans War Tracker
  Plugin URI: https://github.com/richard4339/coc-war-tracker
  Description: Plugin allows for tracking of wars for Clash of Clans
  Version: 0.0.1
  Author: Richard
  Author URI: http://www.digitalxero.com
 */
/*  Copyright 2015  RICHARD LYNSKEY (email : richard@mozor.net)
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.
  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * Wraps the PHP function print_r in a <pre> tag
 * @param $a The array to print
 */
function _pr($a) {
    print '<pre>';
    print_r($a);
    print '</pre>';
}

/**
 * Prints a value with the optional label (label is the first variable if that is desired).
 * @param $a
 * @param $b
 */
function _p($a, $b = '') {
    if ($b != '') {
        if(is_array($b)) {
            print '<b>' . $a . '</b>:';
            _pr($b);
        } else {
            print '<b>' . $a . '</b>: ' . $b;
        }
    } else {
        if(is_array($a)) {
            _pr($a);
        } else {
            print $a;
        }
    }
    print '<br />';
}

/**
 * If the current user is editing their own profile:
 *    Fires after the 'About Yourself' settings table on the 'Your Profile' editing screen.
 * If editing other profiles:
 *    Fires after the 'About the User' settings table on the 'Edit User' screen.
 *
 * Action: show_user_profile | edit_user_profile
 *
 * @param WP_User $profileuser The current WP_User object.
 */
function cwt_custom_user_profile_fields($user)
{

    if (current_user_can('promote_users')) {
        $disabled = '';
    } else {
        $disabled = ' disabled="disabled"';
    }
    ?>
    <table class="form-table">
        <tr>
            <th>
                <label for="cwt_strikes"><?php _e('Strikes'); ?></label>
            </th>
            <td>
                <input type="number" name="cwt_strikes" id="cwt_strikes"
                       value="<?php echo esc_attr(get_the_author_meta('cwt_strikes', $user->ID)); ?>"
                       class="regular-text" min="0" max="3" <?php echo $disabled; ?>/>
                <br><span class="description"><?php _e("Your strikes. (3 and you're out!)", 'Your strikes.'); ?></span>
            </td>
        </tr>
    </table>
<?php
}

/**
 * Fires before the page loads on the 'Edit User' screen.
 *
 * Action: edit_user_profile_update
 *
 * @param int $user_id The user ID.
 */
function cwt_update_user($user_id)
{

    if (current_user_can('promote_users')) {
        if ($_POST['cwt_strikes'] == '') {
            delete_usermeta($user_id, 'cwt_strikes');
        } else {
            update_usermeta($user_id, 'cwt_strikes', $_POST['cwt_strikes']);
        }
    }
}

/**
 * Register a War post type.
 *
 * Action: init
 *
 * @link http://codex.wordpress.org/Function_Reference/register_post_type
 */
function cwt_war_init() {
    $args = array(
        'public' => true,
        'label'  => 'War'
    );
    register_post_type( 'cwt_war', $args );
}

function cwt_clan_sanitize( $meta_value, $meta_key, $meta_type ) {
    return $meta_value;
}

register_meta( 'post', 'cwt_clan', 'cwt_clan_sanitize');


add_action('show_user_profile', 'cwt_custom_user_profile_fields');
add_action('edit_user_profile', 'cwt_custom_user_profile_fields');
add_action('edit_user_profile_update', 'cwt_update_user');
//add_action( 'init', 'cwt_war_init' );





/**
 * Adds a box to the main column on the Post and Page edit screens.
 */
function cwt_add_meta_box() {

    $screens = array( 'post', 'page' );

    foreach ( $screens as $screen ) {

        add_meta_box(
            'cwt_war',
            __( 'War Settings', 'cwt_war' ),
            'cwt_meta_box_callback',
            $screen
        );
    }
}
add_action( 'add_meta_boxes', 'cwt_add_meta_box' );

/**
 * Prints the box content.
 *
 * @param WP_Post $post The object for the current post/page.
 */
function cwt_meta_box_callback( $post ) {

    // Add an nonce field so we can check for it later.
    wp_nonce_field( 'cwt_meta_box', 'cwt_meta_box_nonce' );

    /*
     * Use get_post_meta() to retrieve an existing value
     * from the database and use the value for the form.
     */
    $w = get_post_meta( $post->ID, 'cwt_war', true );

    //_p('cwt_war post_meta', $w);

    for($i = 1; $i <= 50; $i++) {

        echo '<label for="cwt_user_'.$i.'">';
        _e('User '.$i, 'cwt_user_'.$i);
        echo '</label> ';
        wp_dropdown_users(array('show_option_none' => '-', 'selected' => esc_attr($w[$i]), 'id' => 'cwt_user_'.$i, 'name' => 'cwt_user_'.$i, 'exclude' => 1));

        echo '<br />';

    }
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function cwt_save_meta_box_data( $post_id ) {

    /*
     * We need to verify this came from our screen and with proper authorization,
     * because the save_post action can be triggered at other times.
     */

    // Check if our nonce is set.
    if ( ! isset( $_POST['cwt_meta_box_nonce'] ) ) {
        return;
    }

    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $_POST['cwt_meta_box_nonce'], 'cwt_meta_box' ) ) {
        return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check the user's permissions.
    if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

        if ( ! current_user_can( 'edit_page', $post_id ) ) {
            return;
        }

    } else {

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }

    /* OK, it's safe for us to save the data now. */

    $meta = array();

    for($i = 1; $i <= 50; $i++) {

        $field = 'cwt_user_'.$i;

        // Make sure that it is set.
        if ( ! isset( $_POST[$field] ) ) {
            continue;
        }

        // Sanitize user input.
        $my_data = sanitize_text_field( $_POST[$field] );

        $meta[$i] = $my_data;

    }

    // Update the meta field in the database.
    update_post_meta( $post_id, 'cwt_war', $meta );
}
add_action( 'save_post', 'cwt_save_meta_box_data' );


?>