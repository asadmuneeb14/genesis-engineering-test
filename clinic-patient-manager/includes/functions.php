<?php

// Save a patient and their visit
function cpm_save_patient($name, $age, $gender, $phone, $address, $visit_date) {
    global $wpdb;

    // Logging the received data
    error_log("Saving patient: Name = $name, Age = $age, Gender = $gender, Phone = $phone, Address = $address, Visit Date = $visit_date");

    // Insert patient
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

    if ($result === false) {
        error_log("Error inserting patient: " . $wpdb->last_error);
        wp_die("Error inserting patient: " . $wpdb->last_error); // Show error message on screen
    }

    $patient_id = $wpdb->insert_id;

    // Insert visit
    $result = $wpdb->insert(
        "{$wpdb->prefix}cpm_visits",
        [
            'patient_id' => $patient_id,
            'visit_date' => $visit_date
        ],
        ['%d', '%s']
    );

    if ($result === false) {
        error_log("Error inserting visit: " . $wpdb->last_error);
        wp_die("Error inserting visit: " . $wpdb->last_error); // Show error message on screen
    }
}

// Retrieve all patients
function cpm_get_all_patients() {
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}cpm_patients");
}

// Filter patients
function cpm_filter_patients($age = null, $gender = null, $visit_date = null) {
    global $wpdb;
    $query = "SELECT p.* FROM {$wpdb->prefix}cpm_patients p
              LEFT JOIN {$wpdb->prefix}cpm_visits v ON p.id = v.patient_id WHERE 1=1";

    if ($age !== null) {
        $query .= $wpdb->prepare(" AND p.age = %d", $age);
    }

    if ($gender !== null) {
        $query .= $wpdb->prepare(" AND p.gender = %s", $gender);
    }

    if ($visit_date !== null) {
        $query .= $wpdb->prepare(" AND DATE(v.visit_date) = %s", $visit_date);
    }

    return $wpdb->get_results($query);
}

// Retrieve a specific patient
function cpm_get_patient($patient_id) {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}cpm_patients WHERE id = %d", $patient_id));
}

// Update a patient record
function cpm_update_patient($patient_id, $name, $age, $gender, $phone, $address) {
    global $wpdb;
    return $wpdb->update(
        "{$wpdb->prefix}cpm_patients",
        [
            'name' => $name,
            'age' => $age,
            'gender' => $gender,
            'phone' => $phone,
            'address' => $address
        ],
        ['id' => $patient_id],
        ['%s', '%d', '%s', '%s', '%s'],
        ['%d']
    );
}

// Delete a patient
function cpm_delete_patient($patient_id) {
    global $wpdb;
    return $wpdb->delete("{$wpdb->prefix}cpm_patients", ['id' => $patient_id], ['%d']);
}
?>
