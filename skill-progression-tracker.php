<?php
/**
 * Plugin Name:       Skill Progression Tracker
 * Plugin URI:        https://ad.lupus.fun
 * Description:       Track EVE Online skill training progression for players. Visible only to Admins and Editors.
 * Version:           1.3.1
 * Author:            Surama Badasaz
 * Author URI:        https://ad.lupus.fun
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       spt
 */

// Security: Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Global variable for our database table name
global $spt_table_name;
global $wpdb;
$spt_table_name = $wpdb->prefix . 'skill_tracker';

// 1. Activation Hook: Create the database table on plugin activation
register_activation_hook(__FILE__, 'spt_install_database_table');

function spt_install_database_table() {
    global $wpdb;
    global $spt_table_name;
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $spt_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        player_name tinytext NOT NULL,
        skills text NOT NULL,
        delta_t varchar(255) DEFAULT '' NOT NULL,
        status varchar(20) DEFAULT 'active' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// 2. Add the admin menu page - *** USER'S CHANGE INCORPORATED ***
add_action('admin_menu', 'spt_add_admin_menu_page');

function spt_add_admin_menu_page() {
    add_menu_page(
        'Skill Progression Tracker', 'Skill Progression Tracker', 'edit_posts',
        'skill-progression-tracker', 'spt_render_admin_page',
        'dashicons-chart-bar', 25
    );
}

// 3. The main function to render the admin page content
function spt_render_admin_page() {
    global $wpdb, $spt_table_name;
    $records = $wpdb->get_results("SELECT * FROM $spt_table_name ORDER BY status ASC, id DESC");
    ?>
    <div class="wrap" id="spt-plugin-wrapper">
        <h1><span class="dashicons-before dashicons-chart-bar"></span> Skill Progression Tracker</h1>
        <button id="spt-toggle-form-btn" class="button button-secondary">Add New Skill Plan</button>
        <div id="spt-form-container" style="display:none; margin-top: 15px; padding: 20px; border: 1px solid #ccc; background: #f9f9f9;">
            <form id="spt-add-new-form">
                <?php wp_nonce_field('spt_add_nonce_action', 'spt_add_nonce'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="player_name">Player Name</label></th>
                        <td><input type="text" id="player_name" name="player_name" class="regular-text" required /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="skills">Skills (one per line)</label></th>
                        <td><textarea id="skills" name="skills" rows="5" class="large-text" required></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="delta_t">Time Required (Delta T)</label></th>
                        <td><input type="text" id="delta_t" name="delta_t" class="regular-text" placeholder="e.g., 3d 14h 22m" required /></td>
                    </tr>
                </table>
                <?php submit_button('Add Skill Plan', 'primary', 'spt_submit_button'); ?>
            </form>
        </div>
        <hr style="margin: 20px 0;">
        <h2>Current Skill Plans</h2>
        <table class="wp-list-table widefat fixed striped" id="spt-data-table">
            <thead>
                <tr>
                    <th style="width:5%;">ID</th>
                    <th>Player Name & Skills</th>
                    <th style="width:15%;">Check Time</th>
                    <th style="width:15%;">Delta T</th>
                    <th style="width:20%;">Options</th>
                </tr>
            </thead>
            <tbody id="spt-table-body">
                <?php
                if ($records) {
                    foreach ($records as $record) { spt_render_table_row($record); }
                } else {
                    echo '<tr><td colspan="5">No skill plans found. Add one above!</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
    spt_add_inline_styles_and_scripts();
}

// Helper function to render a single table row
function spt_render_table_row($record) {
    $skills_html = nl2br(esc_textarea($record->skills));
    $is_completed = $record->status === 'completed';
    $row_class = $is_completed ? 'completed' : '';
    $complete_button_text = $is_completed ? 'Mark Active' : 'Mark Completed';
    $new_status = $is_completed ? 'active' : 'completed';
    $time_diff = human_time_diff(strtotime($record->created_at), current_time('timestamp')) . ' ago';
    ?>
    <tr id="record-<?php echo $record->id; ?>" class="<?php echo $row_class; ?>">
        <td><?php echo $record->id; ?></td>
        <td>
            <strong><?php echo esc_html($record->player_name); ?></strong>
            <div class="spt-skills-summary">
                <a href="#" class="spt-toggle-skills">(Show/Hide Skills)</a>
                <div class="spt-skills-details" style="display:none;"><?php echo $skills_html; ?></div>
            </div>
        </td>
        <td><?php echo $time_diff; ?></td>
        <td><?php echo esc_html($record->delta_t); ?></td>
        <td>
            <button class="button button-secondary spt-edit-btn" data-id="<?php echo $record->id; ?>">Edit</button>
            <button class="button button-secondary spt-complete-btn" data-id="<?php echo $record->id; ?>" data-new-status="<?php echo $new_status; ?>"><?php echo $complete_button_text; ?></button>
            <button class="button button-danger spt-delete-btn" data-id="<?php echo $record->id; ?>">Delete</button>
        </td>
    </tr>
    <?php
}

// 4. Inline CSS and JavaScript
function spt_add_inline_styles_and_scripts() {
    ?>
    <style>
        #spt-plugin-wrapper .button-danger { background: #d63638; border-color: #b02a2c; color: #fff; }
        #spt-plugin-wrapper .button-danger:hover { background: #b02a2c; border-color: #8c2123; }
        #spt-data-table tr.completed td { background-color: #e7f7e7; text-decoration: line-through; color: #999; }
        #spt-data-table tr.completed strong, #spt-data-table tr.completed a { text-decoration: none; }
        .spt-skills-summary { margin-top: 5px; }
        .spt-skills-details { padding: 10px; margin-top: 5px; background: #fff; border: 1px solid #ddd; border-radius: 4px; }
        #spt-data-table tr.editing td { background-color: #fffbcc !important; }
    </style>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#spt-toggle-form-btn').on('click', function() { $('#spt-form-container').slideToggle(); });
            $('#spt-table-body').on('click', '.spt-toggle-skills', function(e) {
                e.preventDefault();
                $(this).closest('.spt-skills-summary').find('.spt-skills-details').slideToggle();
            });
            $('#spt-add-new-form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this), submitButton = form.find('#spt_submit_button');
                submitButton.prop('disabled', true).val('Adding...');
                var data = {
                    action: 'spt_add_record', spt_add_nonce: $('#spt_add_nonce').val(),
                    player_name: $('#player_name').val(), skills: $('#skills').val(), delta_t: $('#delta_t').val()
                };
                $.ajax({
                    type: 'POST', url: ajaxurl, data: data,
                    success: function(response) {
                        if (response.success) {
                            if ($('#spt-table-body').find('td[colspan="5"]').length) { $('#spt-table-body').empty(); }
                            $('#spt-table-body').prepend(response.data.row_html);
                            form[0].reset(); $('#spt-form-container').slideUp();
                        } else { alert('Error: ' + response.data.message); }
                    },
                    error: function() { alert('An unexpected server error occurred. Please try again.'); },
                    complete: function() { submitButton.prop('disabled', false).val('Add Skill Plan'); }
                });
            });
            $('#spt-table-body').on('click', '.spt-delete-btn', function() {
                if (!confirm('Are you sure you want to delete this entry?')) return;
                var recordId = $(this).data('id');
                $.ajax({
                    type: 'POST', url: ajaxurl, data: { action: 'spt_delete_record', record_id: recordId, _ajax_nonce: '<?php echo wp_create_nonce("spt_delete_nonce_action"); ?>' },
                    success: function(response) {
                        if (response.success) { $('#record-' + recordId).fadeOut(300, function() { $(this).remove(); });
                        } else { alert('Error: Could not delete the record.'); }
                    }
                });
            });
            $('#spt-table-body').on('click', '.spt-complete-btn', function() {
                var button = $(this), recordId = button.data('id'), newStatus = button.data('new-status');
                $.ajax({
                    type: 'POST', url: ajaxurl, data: { action: 'spt_update_status', record_id: recordId, new_status: newStatus, _ajax_nonce: '<?php echo wp_create_nonce("spt_status_nonce_action"); ?>' },
                    success: function(response) {
                        if (response.success) {
                            $('#record-' + recordId).toggleClass('completed');
                            button.text(response.data.new_button_text).data('new-status', response.data.new_status_attr);
                        } else { alert('Error: Could not update status.'); }
                    }
                });
            });
            $('#spt-table-body').on('click', '.spt-edit-btn', function() {
                var recordId = $(this).data('id'), row = $('#record-' + recordId);
                if (row.hasClass('editing')) return;
                row.addClass('editing');
                $.ajax({
                    type: 'POST', url: ajaxurl, data: { action: 'spt_get_edit_form', record_id: recordId, _ajax_nonce: '<?php echo wp_create_nonce("spt_edit_nonce_action"); ?>' },
                    success: function(response) {
                        if (response.success) { row.html(response.data.form_html);
                        } else { alert('Error loading edit form.'); row.removeClass('editing'); }
                    }
                });
            });
            $('#spt-table-body').on('submit', '.spt-edit-form', function(e) {
                e.preventDefault();
                var form = $(this), recordId = form.data('id'), submitButton = form.find('button[type="submit"]');
                submitButton.prop('disabled', true).text('Saving...');
                var data = {
                    action: 'spt_save_edit_form', record_id: recordId,
                    spt_save_edit_nonce: form.find('[name="spt_save_edit_nonce"]').val(),
                    player_name: form.find('[name="player_name"]').val(),
                    skills: form.find('[name="skills"]').val(), delta_t: form.find('[name="delta_t"]').val()
                };
                $.ajax({
                    type: 'POST', url: ajaxurl, data: data,
                    success: function(response) {
                        if (response.success) { $('#record-' + recordId).replaceWith(response.data.row_html);
                        } else { alert('Error: ' + response.data.message); submitButton.prop('disabled', false).text('Save Changes'); }
                    },
                    error: function() { alert('An unexpected server error occurred while saving.'); submitButton.prop('disabled', false).text('Save Changes'); }
                });
            });
            $('#spt-table-body').on('click', '.spt-cancel-edit-btn', function() {
                var recordId = $(this).data('id');
                $.ajax({
                    type: 'POST', url: ajaxurl, data: { action: 'spt_get_row_view', record_id: recordId, _ajax_nonce: '<?php echo wp_create_nonce("spt_cancel_edit_nonce_action"); ?>' },
                    success: function(response) {
                        if (response.success) { $('#record-' + recordId).replaceWith(response.data.row_html);
                        } else { alert('Could not cancel edit.'); }
                    }
                });
            });
        });
    </script>
    <?php
}

// 5. AJAX Handler Functions
add_action('wp_ajax_spt_add_record', 'spt_ajax_add_record_handler');
function spt_ajax_add_record_handler() {
    check_ajax_referer('spt_add_nonce_action', 'spt_add_nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'Permission denied.']);
    global $wpdb, $spt_table_name;
    $player_name = isset($_POST['player_name']) ? sanitize_text_field($_POST['player_name']) : '';
    $skills = isset($_POST['skills']) ? sanitize_textarea_field($_POST['skills']) : '';
    $delta_t = isset($_POST['delta_t']) ? sanitize_text_field($_POST['delta_t']) : '';
    if (empty($player_name) || empty($skills) || empty($delta_t)) wp_send_json_error(['message' => 'All fields are required.']);
    $result = $wpdb->insert($spt_table_name, [ 'created_at' => current_time('mysql'), 'player_name' => $player_name, 'skills' => $skills, 'delta_t' => $delta_t, 'status' => 'active' ]);
    if ($result) {
        $new_record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $spt_table_name WHERE id = %d", $wpdb->insert_id));
        ob_start(); spt_render_table_row($new_record); $row_html = ob_get_clean();
        wp_send_json_success(['row_html' => $row_html]);
    } else { wp_send_json_error(['message' => 'Database error. Could not add record.']); }
}

add_action('wp_ajax_spt_delete_record', 'spt_ajax_delete_record_handler');
function spt_ajax_delete_record_handler() {
    check_ajax_referer('spt_delete_nonce_action');
    if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'Permission denied.']);
    global $wpdb, $spt_table_name;
    $record_id = intval($_POST['record_id']);
    if ($wpdb->delete($spt_table_name, ['id' => $record_id], ['%d'])) wp_send_json_success();
    else wp_send_json_error();
}

add_action('wp_ajax_spt_update_status', 'spt_ajax_update_status_handler');
function spt_ajax_update_status_handler() {
    check_ajax_referer('spt_status_nonce_action');
    if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'Permission denied.']);
    global $wpdb, $spt_table_name;
    $record_id = intval($_POST['record_id']);
    $new_status = sanitize_text_field($_POST['new_status']) === 'completed' ? 'completed' : 'active';
    if ($wpdb->update($spt_table_name, ['status' => $new_status], ['id' => $record_id]) !== false) {
        wp_send_json_success(['new_button_text' => $new_status === 'completed' ? 'Mark Active' : 'Mark Completed', 'new_status_attr' => $new_status === 'completed' ? 'active' : 'completed']);
    } else { wp_send_json_error(); }
}

add_action('wp_ajax_spt_get_edit_form', 'spt_ajax_get_edit_form_handler');
function spt_ajax_get_edit_form_handler() {
    check_ajax_referer('spt_edit_nonce_action');
    if (!current_user_can('edit_posts')) wp_send_json_error();
    global $wpdb, $spt_table_name;
    $record_id = intval($_POST['record_id']);
    $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $spt_table_name WHERE id = %d", $record_id));
    if (!$record) wp_send_json_error();
    ob_start();
    ?>
    <td colspan="5">
        <form class="spt-edit-form" data-id="<?php echo $record->id; ?>">
            <?php wp_nonce_field('spt_save_edit_nonce_action', 'spt_save_edit_nonce'); ?>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; padding: 10px;">
                <div>
                    <label><strong>Player Name</strong></label><br>
                    <input type="text" name="player_name" value="<?php echo esc_attr($record->player_name); ?>" class="regular-text">
                    <br><br>
                    <label><strong>Delta T</strong></label><br>
                    <input type="text" name="delta_t" value="<?php echo esc_attr($record->delta_t); ?>" class="regular-text">
                </div>
                <div>
                    <label><strong>Skills</strong></label><br>
                    <textarea name="skills" rows="5" class="large-text"><?php echo esc_textarea($record->skills); ?></textarea>
                </div>
            </div>
            <div style="padding: 10px; text-align: right;">
                <button type="submit" class="button button-primary">Save Changes</button>
                <button type="button" class="button button-secondary spt-cancel-edit-btn" data-id="<?php echo $record->id; ?>">Cancel</button>
            </div>
        </form>
    </td>
    <?php
    $form_html = ob_get_clean();
    wp_send_json_success(['form_html' => $form_html]);
}

add_action('wp_ajax_spt_save_edit_form', 'spt_ajax_save_edit_form_handler');
function spt_ajax_save_edit_form_handler() {
    check_ajax_referer('spt_save_edit_nonce_action', 'spt_save_edit_nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'Permission denied.']);
    global $wpdb, $spt_table_name;
    $record_id = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;
    $player_name = isset($_POST['player_name']) ? sanitize_text_field($_POST['player_name']) : '';
    $skills = isset($_POST['skills']) ? sanitize_textarea_field($_POST['skills']) : '';
    $delta_t = isset($_POST['delta_t']) ? sanitize_text_field($_POST['delta_t']) : '';
    if (empty($player_name) || empty($skills) || empty($delta_t) || empty($record_id)) wp_send_json_error(['message' => 'All fields are required and record ID must be present.']);
    $wpdb->update($spt_table_name, ['player_name' => $player_name, 'skills' => $skills, 'delta_t' => $delta_t], ['id' => $record_id]);
    $updated_record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $spt_table_name WHERE id = %d", $record_id));
    ob_start(); spt_render_table_row($updated_record); $row_html = ob_get_clean();
    wp_send_json_success(['row_html' => $row_html]);
}

add_action('wp_ajax_spt_get_row_view', 'spt_ajax_get_row_view_handler');
function spt_ajax_get_row_view_handler() {
    check_ajax_referer('spt_cancel_edit_nonce_action');
    if (!current_user_can('edit_posts')) wp_send_json_error();
    global $wpdb, $spt_table_name;
    $record_id = intval($_POST['record_id']);
    $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $spt_table_name WHERE id = %d", $record_id));
    if (!$record) wp_send_json_error();
    ob_start(); spt_render_table_row($record); $row_html = ob_get_clean();
    wp_send_json_success(['row_html' => $row_html]);
}

