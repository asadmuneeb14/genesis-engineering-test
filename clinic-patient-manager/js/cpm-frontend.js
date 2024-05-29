jQuery(document).ready(function($) {
    var table = $('#patients-table').DataTable({
        ajax: {
            url: cpm_ajax.ajax_url,
            type: 'POST',
            data: function(d) {
                d.action = 'cpm_get_patients_data';
                d.age = $('#filter-age').val();
                d.gender = $('#filter-gender').val();
                d.visit_date = $('#filter-visit_date').val();
            }
        },
        columns: [
            { data: 'name' },
            { data: 'age' },
            { data: 'gender' },
            { data: 'phone' },
            { data: 'address' },
            { data: 'visit_date' },
            {
                data: 'id',
                render: function(data, type, row) {
                    return `
                        <button class="btn btn-info view-details" data-id="${data}">View</button>
                        <button class="btn btn-warning btn_display edit-patient" data-id="${data}">Edit</button>
                        <button class="btn btn-danger btn_display delete-patient" data-id="${data}">Delete</button>
                    `;
                }
            }
        ]
    });

    // Filter patients on form submission
    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    // View patient details in a modal
    $('#patients-table').on('click', '.view-details', function(e) {
        e.preventDefault();
        var patientId = $(this).data('id');

        $.post(cpm_ajax.ajax_url, { action: 'cpm_get_patient_details', id: patientId }, function(response) {
            if (response.success) {
                var patient = response.data;
                var details = `
                    <p><strong>Name:</strong> ${patient.name}</p>
                    <p><strong>Age:</strong> ${patient.age}</p>
                    <p><strong>Gender:</strong> ${patient.gender}</p>
                    <p><strong>Phone:</strong> ${patient.phone}</p>
                    <p><strong>Address:</strong> ${patient.address}</p>
                    <p><strong>Visit Date:</strong> ${patient.visit_date}</p>
                `;
                $('#patient-details').html(details);
                $('#patientModal').modal('show');
            } else {
                alert('Error: ' + response.data);
            }
        });
    });

    // Show edit modal with patient details
    $('#patients-table').on('click', '.edit-patient', function(e) {
        e.preventDefault();
        var patientId = $(this).data('id');

        $.post(cpm_ajax.ajax_url, { action: 'cpm_get_patient_details', id: patientId }, function(response) {
            if (response.success) {
                var patient = response.data;
                $('#edit-id').val(patientId);
                $('#edit-name').val(patient.name);
                $('#edit-age').val(patient.age);
                $('#edit-gender').val(patient.gender);
                $('#edit-phone').val(patient.phone);
                $('#edit-address').val(patient.address);
                $('#edit-visit_date').val(patient.visit_date);
                $('#editPatientModal').modal('show');
            } else {
                alert('Error: ' + response.data);
            }
        });
    });

    // Handle edit patient form submission
    $('#edit-patient-form').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();

        $.post(cpm_ajax.ajax_url, formData + '&action=cpm_edit_patient', function(response) {
            if (response.success) {
                $('#editPatientModal').modal('hide');
                table.ajax.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    });

    // Handle add patient form submission
    $('#add-patient-form').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();

        $.post(cpm_ajax.ajax_url, formData + '&action=cpm_add_patient', function(response) {
            if (response.success) {
                $('#addPatientModal').modal('hide');
                table.ajax.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    });

    // Handle delete patient
    $('#patients-table').on('click', '.delete-patient', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this patient?')) return;

        var patientId = $(this).data('id');

        $.post(cpm_ajax.ajax_url, { action: 'cpm_delete_patient', id: patientId }, function(response) {
            if (response.success) {
                table.ajax.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
});
