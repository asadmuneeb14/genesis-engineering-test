<?php
/**
 * Plugin Name: Clinic Patient Manager
 * Description: A plugin to manage patients in a clinic.
 * Version: 1.0
 * Author: Your Name
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('CPM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CPM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Activation hook to create tables
register_activation_hook(__FILE__, 'cpm_create_tables');

function cpm_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $patients_table = "CREATE TABLE {$wpdb->prefix}cpm_patients (
        id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(200) NOT NULL,
        age INT NOT NULL,
        gender VARCHAR(100) NOT NULL,
        phone VARCHAR(150) NOT NULL,
        address TEXT NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    $visits_table = "CREATE TABLE {$wpdb->prefix}cpm_visits (
        id INT NOT NULL AUTO_INCREMENT,
        patient_id INT NOT NULL,
        visit_date DATETIME NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (patient_id) REFERENCES {$wpdb->prefix}cpm_patients(id) ON DELETE CASCADE
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($patients_table);
    dbDelta($visits_table);
}

// Include core functions
require_once CPM_PLUGIN_DIR . 'includes/functions.php';

// Add admin menu
add_action('admin_menu', 'cpm_add_admin_menu');
function cpm_add_admin_menu() {
    add_menu_page('Patient Manager', 'Patient Manager', 'manage_options', 'patient-manager', 'cpm_patient_manager_page');
    add_submenu_page('patient-manager', 'Add New Patient', 'Add New Patient', 'manage_options', 'add-new-patient', 'cpm_add_new_patient_page');
}

// Admin page callback for listing patients
function cpm_patient_manager_page() {
    ?>
    <div class="wrap">
        <h1>Patient Manager</h1>
        <?php if (isset($_GET['message']) && $_GET['message'] == '1') : ?>
            <div class="updated notice is-dismissible"><p>Patient saved successfully.</p></div>
        <?php elseif (isset($_GET['message']) && $_GET['message'] == '2') : ?>
            <div class="updated notice is-dismissible"><p>Patient deleted successfully.</p></div>
        <?php elseif (isset($_GET['message']) && $_GET['message'] == '3') : ?>
            <div class="updated notice is-dismissible"><p>Patient updated successfully.</p></div>
        <?php endif; ?>

        <form method="get">
            <input type="hidden" name="page" value="patient-manager">
            <table class="form-table">
                <tr>
                    <th><label for="age">Age</label></th>
                    <td><input name="age" id="age" type="number" class="regular-text" value="<?php echo isset($_GET['age']) ? intval($_GET['age']) : ''; ?>"></td>
                </tr>
                <tr>
                    <th><label for="gender">Gender</label></th>
                    <td>
                        <select name="gender" id="gender">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php selected(isset($_GET['gender']) && $_GET['gender'] === 'Male'); ?>>Male</option>
                            <option value="Female" <?php selected(isset($_GET['gender']) && $_GET['gender'] === 'Female'); ?>>Female</option>
                            <option value="Other" <?php selected(isset($_GET['gender']) && $_GET['gender'] === 'Other'); ?>>Other</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="visit_date">Visit Date</label></th>
                    <td><input name="visit_date" id="visit_date" type="date" class="regular-text" value="<?php echo isset($_GET['visit_date']) ? esc_attr($_GET['visit_date']) : ''; ?>"></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Filter"></p>
            <style>
                .reset{
                    box-shadow: none;
                    vertical-align: baseline;
                    background: #2271b1;
                    border-color: #2271b1;
                    color: #fff;
                    text-shadow: none;
                    display: inline-block;
                    text-decoration: none;
                    font-size: 13px;
                    line-height: 2.15384615;
                    min-height: 30px;
                    margin: 0;
                    padding: 0 10px;
                    cursor: pointer;
                    border-width: 1px;
                    border-style: solid;
                    -webkit-appearance: none;
                    border-radius: 3px;
                    white-space: nowrap;
                    box-sizing: border-box;
                }
            </style>
            <p class="submit"><a class="reset" href="?page=patient-manager">Reset</a></p>
        </form>

        <?php
        global $wpdb;
        $per_page = 10;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;

        $query = "SELECT p.*, v.visit_date FROM {$wpdb->prefix}cpm_patients p
                  LEFT JOIN {$wpdb->prefix}cpm_visits v ON p.id = v.patient_id WHERE 1=1";

        if (isset($_GET['age']) && $_GET['age'] !== '') {
            $query .= $wpdb->prepare(" AND p.age = %d", intval($_GET['age']));
        }

        if (isset($_GET['gender']) && $_GET['gender'] !== '') {
            $query .= $wpdb->prepare(" AND p.gender = %s", $_GET['gender']);
        }

        if (isset($_GET['visit_date']) && $_GET['visit_date'] !== '') {
            $query .= $wpdb->prepare(" AND DATE(v.visit_date) = %s", $_GET['visit_date']);
        }

        // Get total number of patients matching the filters
        $total_patients = $wpdb->get_var(str_replace('p.*, v.visit_date', 'COUNT(*)', $query));
        $total_pages = ceil($total_patients / $per_page);

        // Add pagination to the query
        $query .= " LIMIT $per_page OFFSET $offset";

        $patients = $wpdb->get_results($query);

        /*global $wpdb;
        $query = "SELECT p.*, v.visit_date FROM {$wpdb->prefix}cpm_patients p
                  LEFT JOIN {$wpdb->prefix}cpm_visits v ON p.id = v.patient_id WHERE 1=1";

        if (isset($_GET['age']) && $_GET['age'] !== '') {
            $query .= $wpdb->prepare(" AND p.age = %d", intval($_GET['age']));
        }

        if (isset($_GET['gender']) && $_GET['gender'] !== '') {
            $query .= $wpdb->prepare(" AND p.gender = %s", $_GET['gender']);
        }

        if (isset($_GET['visit_date']) && $_GET['visit_date'] !== '') {
            $query .= $wpdb->prepare(" AND DATE(v.visit_date) = %s", $_GET['visit_date']);
        }

        $patients = $wpdb->get_results($query);*/
        ?>
        
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th><b>Name</b></th>
                    <th><b>Age</b></th>
                    <th><b>Gender</b></th>
                    <th><b>Phone</b></th>
                    <th><b>Address</b></th>
                    <th><b>Visit Date</b></th>
                    <th><b>Actions</b></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($patients) {
                    foreach ($patients as $patient) {
                        echo "<tr>
                            <td>{$patient->name}</td>
                            <td>{$patient->age}</td>
                            <td>{$patient->gender}</td>
                            <td>{$patient->phone}</td>
                            <td>{$patient->address}</td>
                            <td>{$patient->visit_date}</td>
                            <td>
                                <a href='?page=add-new-patient&action=edit&id={$patient->id}'>Edit</a> |
                                <a href='?page=patient-manager&action=delete&id={$patient->id}' onclick='return confirm(\"Are you sure?\");'>Delete</a>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No patients found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <?php
        // Pagination
        if ($total_pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">';
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => $total_pages,
                'current' => $current_page,
                'add_args' => array(
                    'age' => isset($_GET['age']) ? $_GET['age'] : '',
                    'gender' => isset($_GET['gender']) ? $_GET['gender'] : '',
                    'visit_date' => isset($_GET['visit_date']) ? $_GET['visit_date'] : ''
                )
            ));
            echo '</div></div>';
        }
        ?>
    </div>
    <?php
}

// Admin page callback for adding a new patient
function cpm_add_new_patient_page() {
    ?>
    <div class="wrap">
        <h1><?php echo isset($_GET['id']) ? 'Edit Patient' : 'Add New Patient'; ?></h1>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('cpm_save_patient_action', 'cpm_save_patient_nonce'); ?>
            <input type="hidden" name="action" value="cpm_save_patient">
            <input type="hidden" name="id" value="<?php echo isset($_GET['id']) ? intval($_GET['id']) : ''; ?>">
            <?php
            $patient = isset($_GET['id']) ? cpm_get_patient(intval($_GET['id'])) : null;
            $visit = null; // Define $visit variable
            if ($patient) {
                global $wpdb;
                $visit = $wpdb->get_row($wpdb->prepare("SELECT visit_date FROM {$wpdb->prefix}cpm_visits WHERE patient_id = %d", $patient->id));
                $visit_date = $visit ? esc_attr(str_replace(' ', 'T', $visit->visit_date)) : ''; // Get visit date
            }
            ?>
            <table class="form-table">
                <tr>
                    <th><label for="name">Name</label></th>
                    <td><input name="name" id="name" type="text" class="regular-text" value="<?php echo $patient ? esc_attr($patient->name) : ''; ?>"></td>
                </tr>
                <tr>
                    <th><label for="age">Age</label></th>
                    <td><input name="age" id="age" type="number" class="regular-text" value="<?php echo $patient ? esc_attr($patient->age) : ''; ?>"></td>
                </tr>
                <tr>
                    <th><label for="gender">Gender</label></th>
                    <td>
                        <select name="gender" id="gender">
                            <option value="Male" <?php selected($patient && $patient->gender == 'Male'); ?>>Male</option>
                            <option value="Female" <?php selected($patient && $patient->gender == 'Female'); ?>>Female</option>
                            <option value="Other" <?php selected($patient && $patient->gender == 'Other'); ?>>Other</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="phone">Phone</label></th>
                    <td><input name="phone" id="phone" type="text" class="regular-text" value="<?php echo $patient ? esc_attr($patient->phone) : ''; ?>"></td>
                </tr>
                <tr>
                    <th><label for="address">Address</label></th>
                    <td><textarea name="address" id="address" class="regular-text"><?php echo $patient ? esc_textarea($patient->address) : ''; ?></textarea></td>
                </tr>
                <!-- <tr>
                    <th><label for="visit_date">Visit Date</label></th>
                    <td><input name="visit_date" id="visit_date" type="datetime-local" class="regular-text" value="<?php echo $patient ? esc_attr(str_replace(' ', 'T', $visit->visit_date)) : ''; ?>"></td>
                </tr> -->
                <tr>
                    <th><label for="visit_date">Visit Date</label></th>
                    <td><input name="visit_date" id="visit_date" type="datetime-local" class="regular-text" value="<?php echo $visit_date; ?>"></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo isset($_GET['id']) ? 'Update Patient' : 'Save Patient'; ?>"></p>
        </form>
    </div>
    <?php
}

// Handle form submission for adding/updating a patient
add_action('admin_post_cpm_save_patient', 'cpm_handle_save_patient');
function cpm_handle_save_patient() {
    // Check nonce
    if (!isset($_POST['cpm_save_patient_nonce']) || !wp_verify_nonce($_POST['cpm_save_patient_nonce'], 'cpm_save_patient_action')) {
        wp_die('Nonce verification failed');
    }

    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    // Validate form fields
    if (!empty($_POST['name']) && !empty($_POST['age']) && !empty($_POST['gender']) && !empty($_POST['phone']) && !empty($_POST['address']) && !empty($_POST['visit_date'])) {
        if (!empty($_POST['id'])) {
            // Update patient
            cpm_update_patient(intval($_POST['id']), $_POST['name'], intval($_POST['age']), $_POST['gender'], $_POST['phone'], $_POST['address']);
            global $wpdb;
            $visit_date = $_POST['visit_date'];
            $patient_id = intval($_POST['id']);
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}cpm_visits SET visit_date = %s WHERE patient_id = %d", $visit_date, $patient_id));
            wp_redirect(admin_url('admin.php?page=patient-manager&message=3'));
        } else {
            // Save new patient
            cpm_save_patient($_POST['name'], intval($_POST['age']), $_POST['gender'], $_POST['phone'], $_POST['address'], $_POST['visit_date']);
            wp_redirect(admin_url('admin.php?page=patient-manager&message=1'));
        }
        exit;
    } else {
        wp_die('All fields are required.');
    }
}

// Handle deletion of a patient
add_action('admin_init', 'cpm_handle_delete_patient');
function cpm_handle_delete_patient() {
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        cpm_delete_patient(intval($_GET['id']));
        wp_redirect(admin_url('admin.php?page=patient-manager&message=2'));
        exit;
    }
}

function cpm_enqueue_scripts() {
    // Enqueue DataTables CSS and JS
    wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css');
    wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js', array('jquery'), null, true);
    
    // Enqueue Bootstrap CSS and JS
    wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css');
    wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js', array('jquery'), null, true);

    // Enqueue custom script
    wp_enqueue_script('cpm-frontend-js', plugins_url('/js/cpm-frontend.js', __FILE__), array('jquery', 'datatables-js', 'bootstrap-js'), null, true);
    wp_localize_script('cpm-frontend-js', 'cpm_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'cpm_enqueue_scripts');



// AJAX handler to get patients data
function cpm_get_patients_data() {
    global $wpdb;

    $age = isset($_POST['age']) ? intval($_POST['age']) : null;
    $gender = isset($_POST['gender']) ? sanitize_text_field($_POST['gender']) : null;
    $visit_date = isset($_POST['visit_date']) ? sanitize_text_field($_POST['visit_date']) : null;

    $query = "SELECT p.*, v.visit_date FROM {$wpdb->prefix}cpm_patients p
              LEFT JOIN {$wpdb->prefix}cpm_visits v ON p.id = v.patient_id WHERE 1=1";

    if ($age !== null && !empty($age) && $age !== 0) {
        $query .= $wpdb->prepare(" AND p.age = %d", $age);
    }

    if ($gender !== null && !empty($gender)) {
        $query .= $wpdb->prepare(" AND p.gender = %s", $gender);
    }

    if ($visit_date !== null && !empty($visit_date)) {
        $query .= $wpdb->prepare(" AND DATE(v.visit_date) = %s", $visit_date);
    }

    $patients = $wpdb->get_results($query);
    $data = array();

    foreach ($patients as $patient) {
        $data[] = array(
            'name' => $patient->name,
            'age' => $patient->age,
            'gender' => $patient->gender,
            'phone' => $patient->phone,
            'address' => $patient->address,
            'visit_date' => $patient->visit_date,
            'id' => $patient->id,
            'actions' => '<a href="#" class="view-details" data-id="' . $patient->id . '">View</a>'
        );
    }

    wp_send_json(array('data' => $data));
}
add_action('wp_ajax_cpm_get_patients_data', 'cpm_get_patients_data');
add_action('wp_ajax_nopriv_cpm_get_patients_data', 'cpm_get_patients_data');

// AJAX handler to get a specific patient data
function cpm_get_patient_details() {
    if (!isset($_POST['id'])) {
        wp_send_json_error('No patient ID provided.');
        return;
    }

    global $wpdb;
    $patient_id = intval($_POST['id']);
    
    // Get patient details
    $patient = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}cpm_patients WHERE id = %d", $patient_id));

    // Get visit date
    $visit = $wpdb->get_row($wpdb->prepare("SELECT visit_date FROM {$wpdb->prefix}cpm_visits WHERE patient_id = %d", $patient_id));
    
    if (!$patient) {
        wp_send_json_error('Patient not found.');
        return;
    }

    $response = array(
        'name' => $patient->name,
        'age' => $patient->age,
        'gender' => $patient->gender,
        'phone' => $patient->phone,
        'address' => $patient->address,
        'visit_date' => $visit ? $visit->visit_date : 'N/A'
    );

    wp_send_json_success($response);
}
add_action('wp_ajax_cpm_get_patient_details', 'cpm_get_patient_details');
add_action('wp_ajax_nopriv_cpm_get_patient_details', 'cpm_get_patient_details');

function cpm_patients_datatable_shortcode() {
    ob_start();
    if (current_user_can('administrator')) {
        ?><style>.btn_display{display: inline-block;}</style><?php
    }else{
        ?><style>.btn_display{display: none;}</style><?php
    }
    ?>
    <form id="filter-form">
        <label for="filter-age">Age:</label>
        <input type="number" min="1" id="filter-age" name="age">
        <label for="filter-gender">Gender:</label>
        <select id="filter-gender" name="gender">
            <option value="">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
        </select>
        <label for="filter-visit_date">Visit Date:</label>
        <input type="date" id="filter-visit_date" name="visit_date">
        <button type="submit" style="    margin: 15px 0px;">Filter</button>
        <button type="button" style="float: right; margin: 15px 0px;" class="btn_display btn btn-primary" data-toggle="modal" data-target="#addPatientModal">Add Patient</button>
    </form>

    

    <table id="patients-table" class="display" style="width:100%">
        <thead>
            <tr>
                <th>Name</th>
                <th>Age</th>
                <th>Gender</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Visit Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th>Name</th>
                <th>Age</th>
                <th>Gender</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Visit Date</th>
                <th>Actions</th>
            </tr>
        </tfoot>
    </table>

    <!-- Modal Structure -->
        <div class="modal fade" id="patientModal" tabindex="-1" role="dialog" aria-labelledby="patientModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="patientModalLabel">Patient Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="patient-details">
                        <!-- Patient details will be loaded here -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

    <!-- Add Patient Modal -->
    <div class="modal fade" id="addPatientModal" tabindex="-1" role="dialog" aria-labelledby="addPatientModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPatientModalLabel">Add Patient</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="add-patient-form">
                        <div class="form-group">
                            <label for="add-name">Name</label>
                            <input type="text" class="form-control" id="add-name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="add-age">Age</label>
                            <input type="number" class="form-control" id="add-age" name="age" required>
                        </div>
                        <div class="form-group">
                            <label for="add-gender">Gender</label>
                            <select class="form-control" id="add-gender" name="gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="add-phone">Phone</label>
                            <input type="text" class="form-control" id="add-phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="add-address">Address</label>
                            <textarea class="form-control" id="add-address" name="address" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="add-visit_date">Visit Date</label>
                            <input type="datetime-local" class="form-control" id="add-visit_date" name="visit_date" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Patient</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Patient Modal -->
    <div class="modal fade" id="editPatientModal" tabindex="-1" role="dialog" aria-labelledby="editPatientModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPatientModalLabel">Edit Patient</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="edit-patient-form">
                        <input type="hidden" id="edit-id" name="id">
                        <div class="form-group">
                            <label for="edit-name">Name</label>
                            <input type="text" class="form-control" id="edit-name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-age">Age</label>
                            <input type="number" class="form-control" id="edit-age" name="age" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-gender">Gender</label>
                            <select class="form-control" id="edit-gender" name="gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit-phone">Phone</label>
                            <input type="text" class="form-control" id="edit-phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-address">Address</label>
                            <textarea class="form-control" id="edit-address" name="address" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="edit-visit_date">Visit Date</label>
                            <input type="datetime-local" class="form-control" id="edit-visit_date" name="visit_date" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('cpm_patients_datatable', 'cpm_patients_datatable_shortcode');

// AJAX handler to add a patient
function cpm_add_patient() {
    global $wpdb;

    $name = sanitize_text_field($_POST['name']);
    $age = intval($_POST['age']);
    $gender = sanitize_text_field($_POST['gender']);
    $phone = sanitize_text_field($_POST['phone']);
    $address = sanitize_textarea_field($_POST['address']);
    $visit_date = sanitize_text_field($_POST['visit_date']);

    $result = $wpdb->insert(
        "{$wpdb->prefix}cpm_patients",
        [
            'name' => $name,
            'age' => $age,
            'gender' => $gender,
            'phone' => $phone,
            'address' => $address
        ],
        ['%s', '%d', '%s', '%s', '%s']
    );

    if ($result) {
        $patient_id = $wpdb->insert_id;
        $wpdb->insert(
            "{$wpdb->prefix}cpm_visits",
            [
                'patient_id' => $patient_id,
                'visit_date' => $visit_date
            ],
            ['%d', '%s']
        );
        wp_send_json_success('Patient added successfully.');
    } else {
        wp_send_json_error('Failed to add patient.');
    }
}
add_action('wp_ajax_cpm_add_patient', 'cpm_add_patient');

// AJAX handler to edit a patient
function cpm_edit_patient() {
    global $wpdb;

    $id = intval($_POST['id']);
    $name = sanitize_text_field($_POST['name']);
    $age = intval($_POST['age']);
    $gender = sanitize_text_field($_POST['gender']);
    $phone = sanitize_text_field($_POST['phone']);
    $address = sanitize_textarea_field($_POST['address']);
    $visit_date = sanitize_text_field($_POST['visit_date']);

    $result = $wpdb->update(
        "{$wpdb->prefix}cpm_patients",
        [
            'name' => $name,
            'age' => $age,
            'gender' => $gender,
            'phone' => $phone,
            'address' => $address
        ],
        ['id' => $id],
        ['%s', '%d', '%s', '%s', '%s'],
        ['%d']
    );

    if ($result !== false) {
        $wpdb->update(
            "{$wpdb->prefix}cpm_visits",
            [
                'visit_date' => $visit_date
            ],
            ['patient_id' => $id],
            ['%s'],
            ['%d']
        );
        wp_send_json_success('Patient updated successfully.');
    } else {
        wp_send_json_error('Failed to update patient.');
    }
}
add_action('wp_ajax_cpm_edit_patient', 'cpm_edit_patient');

// AJAX handler to delete a patient
function cpm_front_end_delete_patient() {
    if (!isset($_POST['id'])) {
        wp_send_json_error('No patient ID provided.');
        return;
    }

    global $wpdb;
    $patient_id = intval($_POST['id']);

    $result = $wpdb->delete("{$wpdb->prefix}cpm_patients", ['id' => $patient_id], ['%d']);

    if ($result) {
        wp_send_json_success('Patient deleted successfully.');
    } else {
        wp_send_json_error('Failed to delete patient.');
    }
}
add_action('wp_ajax_cpm_delete_patient', 'cpm_front_end_delete_patient');

