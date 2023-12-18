<?php
/*
Plugin Name: Connector
Description: Bu eklenti, connector veritabanına kayıtlar ekler.
Version: 1.0
Author: Your Name
*/

// WordPress çerçevesini yükleme
if (!function_exists('wp_get_current_user')) {
    include_once(ABSPATH . 'wp-includes/pluggable.php');
}

// Eklentinin etkinleştirildiğinde yapılacak işlemler
register_activation_hook(__FILE__, 'connector_install');

function connector_install() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'connectors';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        image VARCHAR(255),
        description TEXT,
        category VARCHAR(50),
        slug VARCHAR(100),  -- Slug alanı eklendi
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Eklenti kaldırıldığında yapılacak işlemler
register_deactivation_hook(__FILE__, 'connector_uninstall');

function connector_uninstall() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'connectors';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

// WordPress menüsüne bağlantı ekleme
add_action('admin_menu', 'connector_menu');

function connector_menu() {
    add_menu_page(
        'Connector',
        'Connector',
        'manage_options',
        'connector',
        'connector_main_page'
    );

    add_submenu_page(
        'connector',
        'Connector Ekle',
        'Ekle',
        'manage_options',
        'connector_ekle',
        'connector_ekle_page'
    );

    add_submenu_page(
        'connector',
        'Connector Sil',
        'Sil',
        'manage_options',
        'connector_sil',
        'connector_sil_page'
    );
}

function connector_main_page() {
    echo '<div class="wrap"><h2>Connector Ana Sayfa</h2> Ana sayfa içeriği buraya gelecek.</div>';
}

function connector_ekle_page() {
    ?>
    <div class="wrap">
        <h2>Connector Ekle</h2>
        <form method="post" action="" enctype="multipart/form-data">
            <label for="connector_name">Connector Adı:</label>
            <input type="text" name="connector_name" required><br>

            <label for="connector_image">Connector Görseli:</label>
            <input type="file" name="connector_image"><br>

            <label for="connector_description">Connector Açıklama:</label>
            <textarea name="connector_description"></textarea><br>

            <label for="connector_category">Connector Kategori:</label>
            <input type="text" name="connector_category"><br>

            <input type="submit" name="submit_connector_ekle" value="Connector Ekle">
        </form>
    </div>
    <?php
}

function connector_sil_page() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'connectors';
    $connectors = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

    echo '<div class="wrap"><h2>Connector Sil</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Name</th><th>Image</th><th>Description</th><th>Category</th><th>Action</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($connectors as $connector) {
        echo '<tr>';
        echo '<td>' . $connector['id'] . '</td>';
        echo '<td>' . $connector['name'] . '</td>';
        
        // Görseli görüntülemek için wp_get_attachment_url kullanılıyor
        $image_url = wp_get_attachment_url($connector['image']);
        echo '<td><img src="' . esc_url($image_url) . '" alt="Connector Image" style="max-width: 100px;"></td>';
        
        echo '<td>' . $connector['description'] . '</td>';
        echo '<td>' . $connector['category'] . '</td>';
        echo '<td><form method="post" action=""><input type="hidden" name="connector_id" value="' . $connector['id'] . '"><input type="submit" name="submit_connector_sil" value="Sil"></form></td>';
        echo '</tr>';
    }
    
    echo '</tbody></table></div>';
}

// Form verisi işleme
if (isset($_POST['submit_connector_ekle'])) {
    connector_ekle();
} elseif (isset($_POST['submit_connector_sil'])) {
    connector_sil();
}

// Form verisi veritabanına ekleme
function connector_ekle() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'connectors';

    $name = sanitize_text_field($_POST['connector_name']);
    $description = sanitize_textarea_field($_POST['connector_description']);
    $category = sanitize_text_field($_POST['connector_category']);

    // Görseli medya kütüphanesine ekleme işlemi
    $image_id = 0;
    if (!empty($_FILES['connector_image']['name'])) {
        $image_id = connector_upload_image($_FILES['connector_image']);
    }

    // Slug oluştur
    $slug = sanitize_title($name);

    $wpdb->insert(
        $table_name,
        array(
            'name' => $name,
            'image' => $image_id,
            'description' => $description,
            'category' => $category,
            'slug' => $slug, // Slug ekle
        )
    );

    echo '<div class="updated"><p>Connector başarıyla eklendi!</p></div>';
}

// Form verisi veritabanından silme
function connector_sil() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'connectors';
    $connector_id = absint($_POST['connector_id']);

    $wpdb->delete(
        $table_name,
        array('id' => $connector_id),
        array('%d')
    );

    echo '<div class="updated"><p>Connector başarıyla silindi!</p></div>';
}

// Görseli medya kütüphanesine yükleme işlemi
function connector_upload_image($file) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $upload_overrides = array('test_form' => false);

    $file_id = media_handle_upload('connector_image', 0, array(), $upload_overrides);

    if (!is_wp_error($file_id)) {
        return $file_id;
    }

    return 0;
}
?>
