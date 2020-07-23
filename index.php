<?php
function blazter_ninjaforms_submission_list ($atts = []) {
    // Set which form to check
	if ($atts['id'] == null) {
        // Default to form 1 if no id attribute is set
		$form_id = 1;
	}
	else {
        // Set the form id by checking the id attribute
		$form_id = $atts['id'];
    }
	
	// Get maximum of allowed submissions
	if (!is_numeric(intval($atts['limit'])) || $atts['limit'] <= 0) {
		$atts['limit'] = 10;
	}
	else {
		$atts['limit'] = intval($atts['limit']);
	}
    
    // Get filters
    $filters = json_decode($atts['filters'], true);

    // Get output format
    $output = json_decode($atts['output'], true);
        
	// Fetch all submissions
	$submissions = Ninja_Forms()->form( $form_id )->get_subs();
    
    // Set submission list to an empty string
    $submissionList = '';
	
	// Set submission count to 0
    $submissionCount = 0;

    // Check if filters are not empty
    if (count($filters) > 0) {
		// loop over each submission
        foreach($submissions as $submission) {
			
			// Check if limit hasn't been reached
			if ($submissionCount < $atts['limit']) {
				
				// Get submission values
				$values = $submission->get_field_values();

				// Set initial approval value
				$status = 'unknown';

				// Go over each specified filter
				foreach($filters as $filter => $filterValue) {
					if ($status == 'unknown' || $status == 'approved') {
						// Check if any value besides NULL '*' is the filter
						if ($filterValue == '*') {
							if (strlen($values[$filter]) > 0) {
								$status = 'approved';
							}
							else {
								$status = 'denied';
							}
						}
						// Check if value is the same as filter value
						else {
							if (strcmp(rtrim($values[$filter]),$filterValue) == 0) {
								$status = 'approved';
							}
							else {
								$status = 'denied';
							}
						}
					}
					else {
						break;
					}
				}

				if ($status == 'approved') {

					// If listStatus is true by the end of the second check, add it to the list
					$listStatus = true;

					// Create new list element
					$listElement = '<li>';

					// Fill the list element with the designated output
					foreach ($output as $field => $fieldAtts) {
						// As long as the listStatus is true keep doing the checks
						if ($listStatus == true) {
							// If the value contains a disallowed character stop adding it to the list
							if (
								(preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $values[$field]) && $fieldAtts['email'] != true)
							) {
								$listStatus = false;
							}
							// Here is the logic for when the value is good to go :)
							else {
								if ($fieldAtts['name'] == true) {
									$listElement =
										$listElement.
										$fieldAtts['before'].
										ucfirst(htmlspecialchars($values[$field])).
										$fieldAtts['after']
									;
								}
								else {
									$listElement =
										$listElement.
										$fieldAtts['before'].
										htmlspecialchars($values[$field]).
										$fieldAtts['after']
									;
								}
							}
						}
					}

					// Close list element
					$listElement = $listElement.'</li>';

					if ($listStatus == true) {
						$submissionList = $submissionList.$listElement;
						
						// Add another successful addition to the submissionCount
						$submissionCount++;
					}
				}
			}
			// No need to loop over the rest if we have already reached the designated submission limit
			else {
				break;
			}
        }
    }

    // Return the valid submissions in a list
    return $submissionList;
}

add_shortcode( 'blazter_ninjaforms_submission_list', 'blazter_ninjaforms_submission_list' );