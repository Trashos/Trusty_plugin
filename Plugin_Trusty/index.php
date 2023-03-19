<?php
/**
 * Plugin Name: Trusty Studio Plugin
 * Plugin URI: https://trustystudio.fr
 * Description: Suivi de projet par Trusty Studio
 * Version: 1.0.0
 * Author: Trusty Studio
 * Author URI: https://trustystudio.fr
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: mon-plugin
 * Domain Path: /languages
 */

 function mon_super_plugin_check_updates() {
    $remote_api_url = 'https://api.github.com/repos/your_username/your_repository/releases/latest';
    $plugin_slug = 'mon-super-plugin';
    $current_version = '1.0.0'; // Remplacez cette valeur par la version actuelle de votre plugin
    $plugin_path = plugin_dir_path(__FILE__);

    $response = wp_remote_get($remote_api_url);

    if (!is_wp_error($response)) {
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $latest_version = $data['tag_name'];

        if (version_compare($current_version, $latest_version, '<')) {
            $download_url = $data['zipball_url'];
            $temp_zip = download_url($download_url);

            if (!is_wp_error($temp_zip)) {
                $plugin_zip = new ZipArchive;
                if ($plugin_zip->open($temp_zip) === true) {
                    $plugin_zip->extractTo($plugin_path);
                    $plugin_zip->close();
                    unlink($temp_zip);
                    // Mettez à jour la version du plugin dans votre base de données ou dans un fichier de configuration
                } else {
                    // Gérer l'erreur d'extraction du fichier ZIP
                }
            } else {
                // Gérer l'erreur de téléchargement du fichier ZIP
            }
        }
    }
}
add_action('admin_init', 'mon_super_plugin_check_updates');

 

function trusty_studio_post_type() {
    register_post_type('trusty_studio',
        array(
            'labels' => array(
                'name' => __('Trusty Studio'),
                'singular_name' => __('Trusty Studio')
            ),
            'public' => true,
            'has_archive' => false,
            'show_in_menu' => false,
            'menu_position' => 0,
        )
    );
}
add_action('init', 'trusty_studio_post_type');


// Ajout des onglets pour le Post Type en tant que menu principal
function trusty_studio_admin_menu() {
    add_menu_page(
        __('Trusty Studio Onglets'),
        __('Trusty Studio'),
        'manage_options',
        'trusty_studio_onglets',
        'trusty_studio_onglets_callback',
        'dashicons-welcome-widgets-menus',
        0
    );
}
add_action('admin_menu', 'trusty_studio_admin_menu');

function trusty_studio_fetch_client_information($website_name) {
    $remote_db_url = 'http://tma.trusty-projet.fr/admin/fetch_client_information.php';

    $data = array('website_name' => $website_name);
    $data = http_build_query($data);

    $response = wp_remote_post($remote_db_url, array(
        'body' => $data,
        'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
    ));

    if (is_wp_error($response)) {
        // Gérer l'erreur ici, si nécessaire
        return null;
    } else {
        $response_body = wp_remote_retrieve_body($response);
        return json_decode($response_body, true);
    }
}


function trusty_studio_dashboard_alerts() {
    $site_title = get_bloginfo('name');
    $sanitized_site_title = sanitize_file_name(sanitize_title($site_title));
    $url = "http://tma.trusty-projet.fr/{$sanitized_site_title}.json";

    $show_alert = false;
    $alert_message = '';

    $handle = @fopen($url, 'r');
    if ($handle === false) {
        $show_alert = true;
        $alert_message = __("Vous n'avez pas encore de maintenance. Contactez Trusty Studio pour en savoir plus.", "textdomain");
    } else {
        fclose($handle);
        $json_data = file_get_contents($url);
        $data = json_decode($json_data, true);

        $remaining_time = $data["remaining_time"];
        $minutes = floor($remaining_time / 60);
        $hours = floor($minutes / 60);

        if ($hours == 2) {
            $show_alert = true;
            $alert_message = __("Attention, il ne vous reste que 2h00 de maintenance.", "textdomain");
        }
    }

    if ($show_alert) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php echo $alert_message; ?></p>
        </div>
        <?php
    }
}

add_action('admin_notices', 'trusty_studio_dashboard_alerts');

function trusty_studio_get_db_contents() {
    $remote_db_url = 'http://tma.trusty-projet.fr/admin/db.php';

    $response = wp_remote_get($remote_db_url);

    if (is_wp_error($response)) {
        // Gérer l'erreur ici, si nécessaire
        return null;
    } else {
        $response_body = wp_remote_retrieve_body($response);
        return $response_body;
    }
}


// Affichage des onglets et du contenu
function trusty_studio_onglets_callback() {
	 $db_contents = trusty_studio_get_db_contents();
    // Vérifiez si le contenu de db.php a été récupéré avec succès
    if ($db_contents !== null) {
        // Traitez ou affichez le contenu de db.php ici, si nécessaire
    }
    // Traitement du formulaire de support
if (isset($_POST['submit'])) {
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $message = sanitize_textarea_field($_POST['message']);

    $to = 'support@trustystudio.fr'; // Remplacez par l'adresse e-mail de votre support
    $subject = 'Demande de support depuis le tableau de bord WordPress - Art et creation';
    $headers = 'From: ' . $name . ' <' . $email . '>' . "\r\n";
    $body = "Nom: " . $name . "\n";
    $body .= "Email: " . $email . "\n\n";
    $body .= "Message:\n" . $message . "\n";

    if (wp_mail($to, $subject, $body, $headers)) {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Votre message a été envoyé avec succès. Notre équipe de support vous répondra dans les plus brefs délais.', 'textdomain') . '</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>' . __("Échec de l'envoi du message. Veuillez réessayer ou contacter notre support par d'autres moyens.", "textdomain") . '</p></div>';
    }
}

    ?>
    <div class="wrap">
        <h1><?php _e('Trusty Studio - Suivi de projet', 'textdomain'); ?></h1>
        <h2 class="nav-tab-wrapper">
            <a href="#tab1" class="nav-tab nav-tab-active"><?php _e('Un texte', 'textdomain'); ?></a>
            <a href="#tab2" class="nav-tab"><?php _e('Maintenance', 'textdomain'); ?></a>
            <a href="#tab3" class="nav-tab"><?php _e('Informations de contact', 'textdomain'); ?></a>
            <a href="#tab4" class="nav-tab"><?php _e('Support', 'textdomain'); ?></a>
          
        </h2>
        <div id="tab1" class="tab-content">
    <h2><?php _e('Documentation et tutoriels', 'textdomain'); ?></h2>
    <p><?php _e("Voici quelques informations et tutoriels pour vous aider à tirer le meilleur parti de nos services de TMA.", "textdomain"); ?></p>
    <?php
    $website_name = get_bloginfo('name');
    $client_information = trusty_studio_fetch_client_information($website_name);

    if ($client_information) {
        echo "<p><strong>Nom du client :</strong> " . $client_information["client_name"] . "</p>";
        echo "<p><strong>Message :</strong> " . $client_information["message"] . "</p>";
    } else {
        echo "<p>Aucune information sur le client n'a été trouvée.</p>";
    }
    ?>

    <h3><?php _e('Guide de démarrage', 'textdomain'); ?></h3>
    <p><?php _e("Apprenez comment configurer et utiliser votre TMA pour votre projet WordPress.", "textdomain"); ?></p>

    <h3><?php _e('Bonnes pratiques', 'textdomain'); ?></h3>
    <p><?php _e("Découvrez les bonnes pratiques pour gérer votre TMA et assurer le succès de votre projet.", "textdomain"); ?></p>

    <h3><?php _e('FAQ', 'textdomain'); ?></h3>
    <p><?php _e("Consultez les questions fréquemment posées sur nos services de TMA et leur utilisation.", "textdomain"); ?></p>
</div>
<div id="tab2" class="tab-content" style="display:none;">
            <h2><?php _e('Temps restant de leur TMA', 'textdomain'); ?></h2>
            <?php
                $site_title = get_bloginfo('name');
                $sanitized_site_title = sanitize_file_name(sanitize_title($site_title));
                $url = "http://tma.trusty-projet.fr/{$sanitized_site_title}.json";

                $handle = @fopen($url, 'r');
                if ($handle !== false) {
                    fclose($handle);
                    $json_data = file_get_contents($url);
                    $data = json_decode($json_data, true);

                    $project_name = $data["project_name"];
                    $remaining_time = $data["remaining_time"];
                    $minutes = floor($remaining_time / 60);
                    $seconds = $remaining_time % 60;
                    $hours = floor($minutes / 60);

                    ?>

                    <h3><?php _e('Nom du projet:', 'textdomain'); ?> <?= $project_name ?></h3>
                    <p><?php _e('Temps restant:', 'textdomain'); ?> <?= $hours ?>h <?= $minutes % 60 ?>m <?= $seconds ?>s</p>

                    <?php
                } else {
                    ?>
                    <p><?php _e("Pour commander une TMA et assurer un suivi de projet, veuillez contacter Trusty Studio.", "textdomain"); ?></p>
                    <?php
                }
            ?>
        </div>

        <div id="tab3" class="tab-content" style="display:none;">
            <h2><?php _e('Informations de contact', 'textdomain'); ?></h2>
            <p><?php _e('Email: contact@trustystudio.com', 'textdomain'); ?></p>
            <p><?php _e('Téléphone: +33 1 23 45 67 89', 'textdomain'); ?></p>
            <p><?php _e('Support: https://trustystudio.com/support', 'textdomain'); ?></p>
        </div>
    </div>
    <div id="tab4" class="tab-content" style="display:none;">
        <h2><?php _e('Support Trusty Studio', 'textdomain'); ?></h2>
        <p><?php _e("Si vous avez besoin d'aide ou si vous avez des questions, veuillez remplir le formulaire ci-dessous pour contacter notre équipe de support.", "textdomain"); ?></p>
        
        <form action="" method="post" id="support-form">
            <p>
                <label for="name"><?php _e('Nom:', 'textdomain'); ?></label>
                <input type="text" name="name" id="name" required>
            </p>
            <p>
                <label for="email"><?php _e('Email:', 'textdomain'); ?></label>
                <input type="email" name="email" id="email" required>
            </p>
            <p>
                <label for="message"><?php _e('Message:', 'textdomain'); ?></label>
                <textarea name="message" id="message" rows="6" required></textarea>
            </p>
            <p
            <p>
                <input type="submit" name="submit" value="<?php _e('Envoyer', 'textdomain'); ?>">
            </p>
        </form>
    </div>
    <style>
        .tab-content {
            display: none;
        }
        .nav-tab-wrapper {
        border-bottom: 1px solid #ccc;
        padding-bottom: 5px;
        margin-bottom: 20px;
    }

    .nav-tab {
        background-color: #f1f1f1;
        border: 1px solid #ccc;
        padding: 8px 12px;
        text-decoration: none;
        color: #333;
        margin-right: 2px;
        border-bottom: none;
    }

    .nav-tab:hover {
        background-color: #e5e5e5;
    }

    .nav-tab-active {
        background-color: #fff;
        border-bottom: 1px solid #fff;
    }

    .tab-content {
        display: none;
        background-color: #fff;
        padding: 20px;
        border: 1px solid #ccc;
    }

    /* Ajoutez vos propres styles personnalisés ici */
h1{
    text-align: center;
}

    h2, h3 {
        font-family: 'Arial', sans-serif;
    }

    p {
        font-family: 'Arial', sans-serif;
        font-size: 16px;
    }

    </style>
    <script>
        const tabs = document.querySelectorAll('.nav-tab');
        const tabContents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();

                tabs.forEach(t => t.classList.remove('nav-tab-active'));
                tab.classList.add('nav-tab-active');

                tabContents.forEach(content => content.style.display = 'none');
                document.querySelector(tab.getAttribute('href')).style.display = 'block';
            });
        });

        document.querySelector('.nav-tab-active').click();
    </script>
    <?php
}
