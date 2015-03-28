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

/*
 *
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
                <input type="text" name="cwt_strikes" id="cwt_strikes"
                       value="<?php echo esc_attr(get_the_author_meta('cwt_strikes', $user->ID)); ?>"
                       class="regular-text" <?php echo $disabled; ?>/>
                <br><span class="description"><?php _e("Your strikes. (3 and you're out!)", 'Your strikes.'); ?></span>
            </td>
        </tr>
    </table>
<?php
}

/**
 * Fires before the page loads on the 'Edit User' screen.
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

add_action('show_user_profile', 'cwt_custom_user_profile_fields');
add_action('edit_user_profile', 'cwt_custom_user_profile_fields');
add_action('edit_user_profile_update', 'cwt_update_user');

?>