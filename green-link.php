<?php
/*
Plugin Name: Green Short Links - By GreenWPX
Description: GreenWPX Short Link & URL Shortener Management is a powerful and easy-to-use plugin for shortening and managing your website links
Version: 2.95
Author: <a href="https://greenwpx.com/greenshortlinkpro">GreenWPX</a>
License: GPL v3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$green_short_links_version = "2.95";

// Create database table for short links
function gwsl_create_short_links_table()
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'short_links';

  $charset_collate = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        campaign_name VARCHAR(255) NOT NULL,
        original_link VARCHAR(255) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);

}
register_activation_hook(__FILE__, 'gwsl_create_short_links_table');

// Add admin menu
function gwsl_add_short_links_menu()
{
  add_menu_page(
    'Short Links',
    'Green Short Links - By GreenWPX',
    'manage_options',
    'green-short-links',
    'gwsl_green_short_links_page',
    'dashicons-admin-links', // Use a green link icon
    20 // Adjust the position in the menu
  );
}
add_action('admin_menu', 'gwsl_add_short_links_menu');

// Admin page
function gwsl_green_short_links_page()
{
  // Handle form submissions here
  global $wpdb;

  $acceptedHtml = array(
    'a' => array(
        'href' => array(),
        'title' => array()
    ),
    'br' => array(),
    'em' => array(),
    'strong' => array(),
    'p' => array(
      'class' => array()
    )
  );
  $nonce = wp_create_nonce('my-nonce');

  $stats_table_name = $wpdb->prefix . 'short_link_stats';

  $update_link = isset($_POST['update_link']) ? sanitize_text_field($_POST['update_link']):'';
  if (isset($_REQUEST['_wpnonce']) && wp_verify_nonce( sanitize_text_field( wp_unslash ($_REQUEST['_wpnonce'])), 'my-nonce') && $update_link != '') {

    // Process and update the link
    // Don't forget to sanitize and validate user inputs
    $campaign_name = sanitize_text_field($_POST['campaign_name']);
    $original_link = esc_url_raw($_POST['original_link']);
    $slug = sanitize_text_field($_POST['slug']);
    $link_id = sanitize_text_field($_POST['link_id']);

    // Check if the updated slug is already in use
    $existing_link = $wpdb->get_row(
      $wpdb->prepare("SELECT id FROM {$wpdb->prefix}short_links WHERE slug = %s AND id != %d", $slug, $link_id)
    );

    if (!$existing_link) {
      $wpdb->update(
        $wpdb->prefix . 'short_links',
        array(
          'campaign_name' => $campaign_name,
          'original_link' => $original_link,
          'slug' => $slug,
        ),
        array('id' => $link_id)
      );

      $message = '<p class="text-success">Link updated successfully!</p>';
  
    }
  }

  // Update or delete link
  $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']): '';
  $id = isset($_GET['id']) ? sanitize_text_field($_GET['id']):'';

  if (isset($_REQUEST['_wpnonce']) && wp_verify_nonce( sanitize_text_field( wp_unslash ($_REQUEST['_wpnonce'])), 'my-nonce') && $action != '' && $id != '') {

    if ($action === 'delete') {
      // Delete the link
      $wpdb->delete(
        $wpdb->prefix . 'short_links',
        array('id' => $id)
      );

      // Delete the stats for the link
      $wpdb->delete(
        $stats_table_name,
        array('link_id' => $id)
      );

      $message = '<p class="text-success">Link deleted successfully!</p>';

    } elseif ($action === 'edit') {
      // Retrieve the link details for editing
      $link = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$wpdb->prefix}short_links WHERE id = %d", $id)
      );
?>
      <div class="wrap green-links-wrap">
        <img src="<?php echo esc_html(plugins_url('/', __FILE__) . 'images/greenlink.png') ?>">
        <h2>Edit Link</h2>
        <!-- Edit Link Form -->
        <form method="post" action="/wp-admin/admin.php?page=green-short-links">
          <input type="text" class="mb-2" name="campaign_name" placeholder="Campaign Name" value="<?php echo esc_attr($link->campaign_name); ?>" required><br>
          <input type="url" class="mb-2" name="original_link" placeholder="Original Link" value="<?php echo esc_url($link->original_link); ?>" required><br>
          <input type="text" class="mb-2" name="slug" placeholder="Custom Slug" value="<?php echo esc_attr($link->slug); ?>" required><br>
          <input type="hidden" name="link_id" value="<?php echo esc_html($id); ?>">
          <input type="hidden" name="update_link" value="update_link" />

          <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce) ?>" />

          <button type="submit" class="button button-primary">Update Link</button>
          <a href="?page=green-short-links" class="button">Cancel</a>
        </form>
      </div>
  <?php
      return;
    }
  }

  $create_link =  isset($_POST['create_link']) ? sanitize_text_field($_POST['create_link']):'';

  if ($create_link != '') {
    // Process and save the link
    // Don't forget to sanitize and validate user inputs
    $campaign_name = sanitize_text_field($_POST['campaign_name']);
    $original_link = esc_url_raw($_POST['original_link']);
    $slug = sanitize_text_field($_POST['slug']);

    // Check if the slug is already in use
    $existing_link = $wpdb->get_row(
      $wpdb->prepare("SELECT id FROM {$wpdb->prefix}short_links WHERE slug = %s", $slug)
    );

    if (!$existing_link) {
      $wpdb->insert(
        $wpdb->prefix . 'short_links',
        array(
          'campaign_name' => $campaign_name,
          'original_link' => $original_link,
          'slug' => $slug,
        )
      );

      $message = '<p class="text-success">Link created successfully!</p>';
      // Redirect back to the main page
  

    } else {
      $message = '<p class="text-danger">Link already exists. Please choose a different one.</p>';
    }
  }

  ?>

  <div class="wrap green-links-wrap">
    <img src="<?php echo esc_html(plugins_url('/', __FILE__) . 'images/greenlink.png') ?>">
    <h1>Short Links</h1>

    <?php
    $add_new = isset($_GET['add-new']) ? sanitize_text_field($_GET['add-new']):'';

    if ($add_new != '') : ?>
      <!-- Create New Link Form Popup -->
      <div class="short-links-form">
        <h2>Add New Campaign</h2>

        <form id="create-link-form" method="post" action="/wp-admin/admin.php?page=green-short-links">
          <input type="text" name="campaign_name" placeholder="Campaign Name" required><br>
          <input type="url" name="original_link" placeholder="Original Link" required><br>
          <input type="text" name="slug" placeholder="Custom Slug" required><br>
          <input type="hidden" name="create_link" value="create_link">
          <button type="submit" class="button button-primary">Create Link</button>
        </form>
      </div>
    <?php else : ?>
      <p>
        <a href="?page=green-short-links&add-new=1" class="d-flex align-items-center button button-primary addNewCampaign"><span class="dashicons dashicons-plus-alt"></span> Add New Campaign</a>
      </p>

      <?php
      // Retrieve short links and order them by ID (latest first)
      $links = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}short_links ORDER BY id DESC");

      $total_campaigns = count($links);
      $today_clicks = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}short_link_stats WHERE DATE(date) = DATE(NOW())");
      ?>

      <!-- Short Links Stats -->
      <div class="short-links-stats">
        <div class="upgradetopro"><a href="https://greenwpx.com/greenshortlinkpro" target="_blank">Upgrade to PRO</a></div>
        <div class="stat">
          <div class="value"><?php echo ($total_campaigns) ? esc_html($total_campaigns) : 0; ?></div>
          <div class="label">Total Campaigns</div>
        </div>
        <div class="stat bl">
          <div class="value">0</div>
          <div class="label">Today's Clicks</div>
        </div>
        <div class="stat bl">
          <div class="value">0</div>
          <div class="label">Yesterday's Clicks</div>
        </div>
        <div class="stat bl">
          <div class="value">0</div>
          <div class="label">This Month's Clicks</div>
        </div>
        <div class="stat bl">
          <div class="value">0</div>
          <div class="label">Last Month's Clicks</div>
        </div>
      </div>

      <!-- List of Short Links -->
      <div class="short-links-list desktopShow">
        <?php echo isset($message) ? wp_kses($message, $acceptedHtml) : '' ?>
        <h2>Short Links List</h2>
        <table>
          <thead>
            <tr>
              <th>Campaign Name</th>
              <th>Original Link</th>
              <th>Short Link</th>
              <th>Stats</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            foreach ($links as $link) {
              ?>
              <tr>
              <td><?php echo esc_html($link->campaign_name) ?></td>
              <td><a class="linktext" href="<?php echo esc_url($link->original_link) ?>" target="_blank"><?php echo esc_html($link->original_link) ?></a></td>
              <td><span class="d-flex"><input class="w-100" type="text" value="<?php echo esc_url(home_url('/') . $link->slug) ?>" readonly>
              <div class="mw-2 copy-button">
              <button onclick="copyText('<?php echo esc_url(home_url('/') . $link->slug) ?>')" class="d-flex"><span class="dashicons dashicons-clipboard"></span> Copy</button>
              </span></td><td class="upgrade">
              <a href="#" data-link="/<?php echo esc_url($link->slug) ?>" data-link-id="<?php echo esc_html($link->id) ?>" class="statsButton button button-primary"><span class="dashicons dashicons-dashboard"></span>&nbsp;Stats</a><div class="upgradetopro"><a href="https://greenwpx.com/greenshortlinkpro
              " target="_blank">Upgrade to PRO</a></div>
              </div>
              </td>
              <td><span class="d-flex">
              <a href="?page=green-short-links&action=edit&id=<?php echo esc_html($link->id) ?>&_wpnonce=<?php echo esc_attr($nonce) ?>" class="d-flex align-items-center button button-primary"><span class="dashicons dashicons-edit"></span> Edit</a> 
              <a href="?page=green-short-links&action=delete&id=<?php echo esc_html($link->id) ?>&_wpnonce=<?php echo esc_attr($nonce) ?>" class="ms-2 d-flex align-items-center button button-primary" onclick="return confirm('Are you sure you want to delete this campaign?');"><span class="dashicons dashicons-trash"></span> Delete</a>
              </span></td>
              </tr>

              <?php
            }
            ?>
          </tbody>
        </table>
      </div>

      <!-- List of Short Links -->
      <div class="short-links-list mobileShow">
        <div class="row">
          <?php echo isset($message) ? wp_kses($message, $acceptedHtml) : '' ?>
          <h2>Short Links List</h2>
          <?php
          foreach ($links as $link) {
          ?>
            <div class="col-md-3">
              <div class="linkBox">
                <div class="campaign-name">
                  <h3>Campaing Name</h3>
                  <?php echo esc_html($link->campaign_name) ?>
                </div>
                <div class="original-link">
                  <h3>Original Link</h3>
                  <a class="linktext" href="<?php echo esc_url($link->original_link) ?>" target="_blank"><?php echo esc_html($link->original_link) ?></a>
                </div>
                <div class="short-link">
                  <h3>Short Link</h3>
                  <span class="d-flex">
                    <input class="w-100" type="text" value="<?php echo esc_url(home_url('/') . $link->slug) ?>" readonly>
                    <div class="mw-2 copy-button">
                      <button onclick="copyText('<?php echo esc_url(home_url('/') . $link->slug) ?>')" class="d-flex"><span class="dashicons dashicons-clipboard"></span> Copy</button>
                    </div>
                  </span>
                </div>
                <div class="actions d-flex justify-content-between mt-3">
                  <a href="#" data-link="/<?php echo esc_url($link->slug) ?>" data-link-id="<?php echo esc_attr($link->id) ?>" class="statsButton button button-primary"><span class="dashicons dashicons-dashboard"></span>&nbsp;Stats</a>
                  <a href="?page=green-short-links&action=edit&id=<?php echo esc_html($link->id) ?>&_wpnonce=<?php echo esc_attr($nonce) ?>" class="d-flex align-items-center button button-primary"><span class="dashicons dashicons-edit"></span> Edit</a>
                  <a href="?page=green-short-links&action=delete&id=<?php echo esc_html($link->id) ?>&_wpnonce=<?php echo esc_attr($nonce) ?>" class="d-flex align-items-center button button-primary" onclick="return confirm('Are you sure you want to delete this campaign?');"><span class="dashicons dashicons-trash"></span> Delete</a>
                </div>
              </div>
            </div>

          <?php

          }
          ?>


        </div>
      </div>
    <?php endif; ?>
    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce) ?>" />

   <!-- New section for other products -->
   <div class="other-products-section mt-8">
  <h3 class="text-lg font-semibold">Check our other Products:</h3>
  <ul class="m-0 p-0">
    <li>
      <i class="dashicons dashicons-email me-1"></i> GreenMail
      <a href="https://greenwpx.com" target="_blank" class="green-button-link text-white px-2 py-1 ms-2">Learn More</a>
    </li>
    <li>
      <i class="dashicons dashicons-admin-links me-1"></i> Green Short Links
      <a href="https://greenwpx.com" target="_blank" class="green-button-link text-white px-2 py-1 ms-2">Learn More</a>
    </li>
    <li>
      <i class="dashicons dashicons-backup me-1"></i> Green Backup
      <a href="https://greenwpx.com" target="_blank" class="green-button-link text-white px-2 py-1 ms-2">Learn More</a>
    </li>
    <li>
      <i class="dashicons dashicons-dashboard me-1"></i> Green Analytics
      <a href="https://greenwpx.com" target="_blank" class="green-button-link text-white px-2 py-1 ms-2">Learn More</a>
    </li>
  </ul>
    </div>
<?php
}

// Handle short link redirects (301)
function gwsl_handle_short_link_redirect()
{
  global $wpdb;
  $slug = sanitize_text_field($_SERVER['REQUEST_URI']);
  $slug = trim($slug, '/'); // Remove leading and trailing slashes

  $link = $wpdb->get_row(
    $wpdb->prepare("SELECT original_link FROM {$wpdb->prefix}short_links WHERE slug = %s", $slug)
  );

  if ($link) {
    // Update click and visit stats for the link
    $link_id = $wpdb->get_var(
      $wpdb->prepare("SELECT id FROM {$wpdb->prefix}short_links WHERE slug = %s", $slug)
    );

    $wpdb->get_results(
      $wpdb->prepare("UPDATE {$wpdb->prefix}short_links SET clicks = clicks + 1 WHERE id = %d", $link_id)
    );

    wp_redirect($link->original_link, 301);
    exit;
  }
}
add_action('template_redirect', 'gwsl_handle_short_link_redirect');

// Add CSS styles for admin page
function gwsl_add_admin_styles()
{

  global $green_short_links_version;

  wp_enqueue_style('green-link-bootstrap', plugins_url('css/bootstrap.css', __FILE__), $green_short_links_version);
  wp_enqueue_style('green-link-styles', plugins_url('css/style.css', __FILE__), $green_short_links_version);

  wp_enqueue_script('green-link-script', plugins_url('js/green-link.js', __FILE__), array('jquery'), $green_short_links_version);

  // Localize the script with the AJAX URL
  wp_localize_script('green-link-script', 'green_link_ajax', array(
    'ajax_url' => admin_url('admin-ajax.php'),
  ));
}

add_action('admin_enqueue_scripts', 'gwsl_add_admin_styles');

// Activate the plugin
function gwsl_activate_green_link()
{
  gwsl_create_short_links_table();
}
register_activation_hook(__FILE__, 'gwsl_activate_green_link');
?>